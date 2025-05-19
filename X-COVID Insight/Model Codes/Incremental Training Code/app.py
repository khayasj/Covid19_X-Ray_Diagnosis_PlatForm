from fastapi import FastAPI, Request
import torch
import torch.nn as nn
import torch.optim as optim
from torchvision import transforms
from torch.utils.data import Dataset, DataLoader
from PIL import Image
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, f1_score
import segmentation_models_pytorch as smp
from torchvision.models import densenet121, DenseNet121_Weights
import requests
from io import BytesIO
from torchvision.transforms.functional import to_pil_image
import logging
import os


logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# FastAPI app instance
app = FastAPI()

device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

# Load segmentation model
m1 = smp.Unet(
    encoder_name="resnet34",
    encoder_weights=None,
    in_channels=1,
    classes=1
).to(device)
m1.load_state_dict(torch.load("Segmentation_Model.pth", map_location=device))
m1.eval()

# Dataset for incremental training
class LungXrayDataset(Dataset):
    def __init__(self, entries, transform):
        self.entries = entries
        self.transform = transform
        self.label_map = {label: i for i, label in enumerate(sorted(set(s["true_label"] for s in entries)))}

    def __len__(self):
        return len(self.entries)

    def __getitem__(self, idx):
        image = self.entries[idx]["pil_image"]
        if self.transform:
            image = self.transform(image)
        label = self.label_map[self.entries[idx]["true_label"]]
        return image, label

@app.get("/")
def root():
    return {"status": "Service is up!"}

@app.post("/trigger_incremental_train")
async def trigger_train(request: Request):
    data = await request.json()
    samples = data.get("samples", [])

    if not samples or len(samples) < 100:
        return {"error": "Not enough validated samples (minimum 100 required)."}

    for entry in samples:
        try:
            logger.info(f"Downloading image: {entry['image_path']}")
            resp = requests.get(entry["image_path"], timeout=5)
            resp.raise_for_status()
            image = Image.open(BytesIO(resp.content)).convert("L").resize((256, 256))
    
            image_tensor = transforms.ToTensor()(image).unsqueeze(0).to(device)
            with torch.no_grad():
                mask = m1(image_tensor).sigmoid()
                mask = (mask > 0.5).float()
                masked = image_tensor * mask
    
            masked_rgb = masked.squeeze(0).repeat(3, 1, 1).cpu()
            masked_pil = to_pil_image(masked_rgb)
    
            entry["pil_image"] = masked_pil
    
        except Exception as e:
            error_msg = f"Failed to load image: {entry['image_path']} | Error: {e}"
            logger.error(error_msg)
            return {"error": error_msg}

    logger.info("All data loaded.")
    
    # Load models
    def load_model():
        model = densenet121(weights=None)
        model.classifier = nn.Linear(1024, 4)
        model.load_state_dict(torch.load("Classification_Model.pth", map_location=device))
        return model.to(device)

    logger.info("Loading new classification model for incremental training...")
    m2_new = load_model()
    logger.info("Loading old classification model for futher compare...")
    m2_old = load_model().eval()

    transform = transforms.Compose([
        transforms.Resize((224, 224)),
        transforms.ToTensor(),
        transforms.Normalize(mean=[0.41]*3, std=[0.16]*3)
    ])

    train_entries, val_entries = train_test_split(
        samples, test_size=0.2, stratify=[s["true_label"] for s in samples], random_state=42
    )

    train_loader = DataLoader(LungXrayDataset(train_entries, transform), batch_size=16, shuffle=True)
    val_loader = DataLoader(LungXrayDataset(val_entries, transform), batch_size=16)

    criterion = nn.CrossEntropyLoss()
    optimizer = optim.Adam(m2_new.parameters(), lr=1e-4)
    
    logger.info(f"Training start...")
    for epoch in range(5):
        logger.info(f"Epoch {epoch+1}/5")
        m2_new.train()
        total_loss = 0
        for imgs, labels in train_loader:
            imgs, labels = imgs.to(device), labels.to(device)
            out = m2_new(imgs)
            loss = criterion(out, labels)
            optimizer.zero_grad()
            loss.backward()
            optimizer.step()
            total_loss += loss.item()
        logger.info(f"Epoch {epoch+1} complete. Loss: {round(total_loss, 4)}")

    def evaluate(model, loader):
        model.eval()
        y_true, y_pred = [], []
        with torch.no_grad():
            for imgs, labels in loader:
                imgs = imgs.to(device)
                outputs = model(imgs)
                y_pred.extend(outputs.argmax(1).cpu().numpy())
                y_true.extend(labels.numpy())
        return {
            "accuracy": round(accuracy_score(y_true, y_pred), 4),
            "f1_macro": round(f1_score(y_true, y_pred, average="macro"), 4),
        }

    logger.info(f"Evaluating old model...")
    eval_old = evaluate(m2_old, val_loader)
    logger.info(f"Evaluating new model...")
    eval_new = evaluate(m2_new, val_loader)

    os.makedirs("/tmp/data", exist_ok=True)
    model_used = "new" if eval_new["f1_macro"] > eval_old["f1_macro"] else "old"
    if model_used == "new":
        torch.save(m2_new.state_dict(), "/tmp/data/Classification_Model.pth")
        logger.info("New model saved as /tmp/data/Classification_Model.pth")

    logger.info(f"Model used: {model_used}")

    return {
        "old_model": eval_old,
        "new_model": eval_new,
        "model_used": model_used,
        "updated_model_path": "/tmp/data/Classification_Model.pth" if model_used == "new" else "unchanged"
    }