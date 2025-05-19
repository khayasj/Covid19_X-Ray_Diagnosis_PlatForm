import os
import glob
import random
import numpy as np
import matplotlib.pyplot as plt
from PIL import Image
from collections import defaultdict
from sklearn.model_selection import train_test_split
from sklearn.metrics import precision_score, recall_score, f1_score

import torch
import torch.nn as nn
from torch.utils.data import Dataset, DataLoader
import segmentation_models_pytorch as smp

import albumentations as A
from albumentations.pytorch import ToTensorV2
from collections import defaultdict


# Config
device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
print(f'Using device: {device}')

IMG_DIR = r"C:\archive\COVID-19_Radiography_Dataset"
categories = ["COVID", "Lung_Opacity", "Normal", "Viral Pneumonia"]
SAVE_PATH = "unet_model_6.pth"

# Load data and data spilt
train_imgs, val_imgs, test_imgs = [], [], []
train_masks, val_masks, test_masks = [], [], []

random.seed(42)

for cat in categories:
    img_dir = os.path.join(IMG_DIR, cat, "images")
    mask_dir = os.path.join(IMG_DIR, cat, "masks")
    imgs = sorted(glob.glob(os.path.join(img_dir, '*.png')))

    img_mask_pairs = []
    for img_path in imgs:
        filename = os.path.basename(img_path)
        mask_path = os.path.join(mask_dir, filename)
        if os.path.exists(mask_path):
            img_mask_pairs.append((img_path, mask_path))

    train_pairs, temp_pairs = train_test_split(img_mask_pairs, test_size=0.3, random_state=42)
    val_pairs, test_pairs = train_test_split(temp_pairs, test_size=0.5, random_state=42)

    for img, mask in train_pairs:
        train_imgs.append(img)
        train_masks.append(mask)
    for img, mask in val_pairs:
        val_imgs.append(img)
        val_masks.append(mask)
    for img, mask in test_pairs:
        test_imgs.append(img)
        test_masks.append(mask)

# Apply transforms with different augmentation strengths
strong_transform = A.Compose([
    A.Resize(256, 256),

    A.OneOf([
        A.HorizontalFlip(p=1.0),
        A.Affine(
            scale=(0.97, 1.03),
            translate_percent=(0.0, 0.03),
            rotate=(-10, 10),
            p=1.0
        ),
    ], p=1.0),

    A.OneOf([
        A.RandomBrightnessContrast(brightness_limit=0.1, contrast_limit=0.1, p=1.0),
    ], p=0.3),

    A.Normalize(mean=0.5, std=0.5),
    ToTensorV2()
])

light_transform = A.Compose([
    A.Resize(256, 256),
    A.HorizontalFlip(p=0.1),
    A.Normalize(mean=0.5, std=0.5),
    ToTensorV2()
])

val_transform = A.Compose([
    A.Resize(256, 256),
    A.Normalize(mean=0.5, std=0.5),
    ToTensorV2()
])


# Dataset
class LungDataset(Dataset):
    def __init__(self, image_paths, mask_paths, transform_map=None):
        self.image_paths = image_paths
        self.mask_paths = mask_paths
        self.transform_map = transform_map

    def __len__(self):
        return len(self.image_paths)

    def __getitem__(self, idx):
        img = Image.open(self.image_paths[idx]).convert('L')
        mask = Image.open(self.mask_paths[idx]).convert('L')

        img = img.resize((256, 256), Image.BILINEAR)
        mask = mask.resize((256, 256), Image.NEAREST)

        img = np.array(img)
        mask = np.array(mask)

        # Use different augmentation strategies for different classes
        cat = os.path.basename(os.path.dirname(os.path.dirname(self.image_paths[idx])))
        transform = self.transform_map.get(cat, None)

        if transform:
            augmented = transform(image=img, mask=mask)
            img = augmented['image']
            mask = augmented['mask']

        if not isinstance(mask, torch.Tensor):
            mask = torch.from_numpy(mask.astype(np.float32))
        else:
            mask = mask.float()

        if mask.max() > 1:
            mask = mask / 255.0
        if mask.ndim == 2:
            mask = mask.unsqueeze(0)

        if not isinstance(img, torch.Tensor):
            img = torch.from_numpy(img.astype(np.float32)) / 255.0
        if img.ndim == 2:
            img = img.unsqueeze(0)
        elif img.ndim == 3 and img.shape[0] != 1:
            img = img[:1, :, :]

        return img, mask, self.image_paths[idx]


# Build dataloader
transform_map = {
    "COVID": strong_transform,
    "Lung_Opacity": strong_transform,
    "Viral Pneumonia": strong_transform,
    "Normal": light_transform
}

val_transform_map = defaultdict(lambda: val_transform)

train_dataset = LungDataset(train_imgs, train_masks, transform_map=transform_map)
val_dataset = LungDataset(val_imgs, val_masks, transform_map=val_transform_map)
test_dataset = LungDataset(test_imgs, test_masks, transform_map=val_transform_map)

train_loader = DataLoader(train_dataset, batch_size=8, shuffle=True)
val_loader = DataLoader(val_dataset, batch_size=8, shuffle=False)
test_loader = DataLoader(test_dataset, batch_size=1, shuffle=False)


# Load and configure the pre-trained U-Net model with a ResNet34 encoder
model = smp.Unet(
    encoder_name="resnet34",
    encoder_weights="imagenet",
    in_channels=1,
    classes=1
).to(device)

# Define a custom loss function
class DiceBCELoss(nn.Module):
    def __init__(self):
        super().__init__()
        self.bce = nn.BCELoss()

    def forward(self, preds, targets, smooth=1):
        preds = preds.view(-1)
        targets = targets.view(-1)
        intersection = (preds * targets).sum()
        dice = (2. * intersection + smooth) / (preds.sum() + targets.sum() + smooth)
        return 1 - dice + self.bce(preds, targets)

def dice_score(preds, targets, smooth=1e-5):
    preds = (preds > 0.5).float()
    targets = targets.float()
    intersection = (preds * targets).sum(dim=(1, 2, 3))
    union = preds.sum(dim=(1, 2, 3)) + targets.sum(dim=(1, 2, 3))
    dice = (2. * intersection + smooth) / (union + smooth)
    return dice.mean().item()

criterion = DiceBCELoss()
optimizer = torch.optim.Adam(model.parameters(), lr=1e-3)


# Training
epochs = 30
train_losses = []
train_dices = []
val_losses = []
val_dices = []
best_macro_dice = 0

print("\nTraining UNet with ResNet34 encoder...\n")

for epoch in range(epochs):
    model.train()
    train_loss = 0
    train_dice = 0

    for imgs, masks, _ in train_loader:
        imgs, masks = imgs.to(device), masks.to(device)
        preds = torch.sigmoid(model(imgs))
        loss = criterion(preds, masks)

        optimizer.zero_grad()
        loss.backward()
        optimizer.step()

        train_loss += loss.item()
        train_dice += dice_score(preds, masks)

    avg_train_loss = train_loss / len(train_loader)
    avg_train_dice = train_dice / len(train_loader)

    # Validation
    model.eval()
    val_loss = 0
    dice_by_class = defaultdict(list)

    with torch.no_grad():
        for imgs, masks, paths in val_loader:
            imgs, masks = imgs.to(device), masks.to(device)
            preds = torch.sigmoid(model(imgs))
            val_loss += criterion(preds, masks).item()
            pred_bin = (preds > 0.5).float()

            cat = os.path.basename(os.path.dirname(os.path.dirname(paths[0])))
            dsc = dice_score(pred_bin, masks)
            dice_by_class[cat].append(dsc)

    # Calculate the average Dice score for each class and macro dice
    avg_val_loss = val_loss / len(val_loader)
    avg_dices = {cat: np.mean(scores) for cat, scores in dice_by_class.items()}
    macro_dice = np.mean(list(avg_dices.values()))

    train_losses.append(avg_train_loss)
    train_dices.append(avg_train_dice)
    val_losses.append(avg_val_loss)
    val_dices.append(macro_dice)

    print(f"Epoch {epoch+1}/{epochs} \n"
          f"Train Loss: {avg_train_loss:.4f} | Train Dice: {avg_train_dice:.4f} | "
          f"Val Loss: {avg_val_loss:.4f} | Macro Val Dice: {macro_dice:.4f}")
    for cat in categories:
        if cat in avg_dices:
            print(f"   - {cat}: {avg_dices[cat]:.4f}")

    if macro_dice > best_macro_dice:
        best_macro_dice = macro_dice
        torch.save(model.state_dict(), SAVE_PATH)
        print("\tSaved new best model based on macro dice.")


# Generate training report
plt.figure(figsize=(12, 5))

plt.subplot(1, 2, 1)
plt.plot(range(1, epochs+1), train_losses, label='Training Loss')
plt.plot(range(1, epochs+1), val_losses, label='Validation Loss')
plt.xlabel('Epoch')
plt.ylabel('Loss')
plt.title('Training and Validation Loss')
plt.legend()

plt.subplot(1, 2, 2)
plt.plot(range(1, epochs+1), train_dices, label='Training Dice')
plt.plot(range(1, epochs+1), val_dices, label='Validation Macro Dice')
plt.xlabel('Epoch')
plt.ylabel('Macro Dice')
plt.title('Training and Validation Dice')
plt.legend()

plt.tight_layout()
plt.savefig("train_val_curves.png")
plt.show()

# Test on per class in test set
from collections import defaultdict
model.load_state_dict(torch.load(SAVE_PATH))
print("\nEvaluating best model on test set per class...")
model.eval()

results_by_class = defaultdict(lambda: {"pred": [], "true": []})

with torch.no_grad():
    for imgs, masks, paths in test_loader:
        imgs, masks = imgs.to(device), masks.to(device)
        preds = torch.sigmoid(model(imgs))

        pred_bin = (preds > 0.5).cpu().numpy().flatten()
        mask_bin = masks.cpu().numpy().flatten()

        category = os.path.basename(os.path.dirname(os.path.dirname(paths[0])))
        results_by_class[category]["pred"].extend(pred_bin)
        results_by_class[category]["true"].extend(mask_bin)

for cat, res in results_by_class.items():
    p = precision_score(res["true"], res["pred"])
    r = recall_score(res["true"], res["pred"])
    f1 = f1_score(res["true"], res["pred"])
    dsc = 2 * (p * r) / (p + r + 1e-8)
    print(f"\n[{cat}] Precision: {p:.4f}, Recall: {r:.4f}, F1: {f1:.4f}, Dice: {dsc:.4f}")
