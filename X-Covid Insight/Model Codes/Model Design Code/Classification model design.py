import torch
import torch.nn as nn
import torch.optim as optim
from torchvision import transforms
from torch.utils.data import DataLoader, Subset, Dataset
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, confusion_matrix, f1_score
import os
import numpy as np
from PIL import Image
import matplotlib.pyplot as plt
import seaborn as sns
from torchvision.models import densenet121, DenseNet121_Weights
from collections import defaultdict
from torch.utils.data import WeightedRandomSampler

# Config
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
print(f'Using device: {device}')

root = r"C:\archive\COVID-19_Radiography_Dataset"
categories = ["COVID", "Lung_Opacity", "Normal", "Viral Pneumonia"]


# Dataset
class XRayLungDataset(Dataset):
    def __init__(self, root_dir, categories, transform_map=None):
        self.image_paths = []
        self.mask_paths = []
        self.labels = []
        self.categories = categories
        self.transform_map = transform_map

        for label, category in enumerate(categories):
            img_dir = os.path.join(root_dir, category, "images")
            mask_dir = os.path.join(root_dir, category, "masks")
            img_files = sorted(os.listdir(img_dir))
            mask_files = sorted(os.listdir(mask_dir))
            assert len(img_files) == len(mask_files), f"Mismatch in {category} images and masks!"

            for img, mask in zip(img_files, mask_files):
                self.image_paths.append(os.path.join(img_dir, img))
                self.mask_paths.append(os.path.join(mask_dir, mask))
                self.labels.append(label)

    def __len__(self):
        return len(self.image_paths)

    def __getitem__(self, idx):
        image = Image.open(self.image_paths[idx]).convert("L").resize((224, 224), Image.BILINEAR)
        mask = Image.open(self.mask_paths[idx]).convert("L").resize((224, 224), Image.NEAREST)

        label = self.labels[idx]
        image_np = np.array(image).astype(np.float32)
        mask_np = (np.array(mask) / 255.0 > 0.5).astype(np.float32)

        lung_image = image_np * mask_np
        lung_image_3ch = np.stack([lung_image] * 3, axis=-1)
        lung_image_pil = Image.fromarray(lung_image_3ch.astype(np.uint8))

        # Use different augmentation strategies for different classes
        class_name = self.categories[self.labels[idx]]
        transform = self.transform_map[class_name]

        if transform:
            lung_image_tensor = transform(lung_image_pil)
        else:
            lung_image_tensor = torch.from_numpy(lung_image_3ch).permute(2, 0, 1)

        return lung_image_tensor, label


# Apply transforms with different augmentation strengths
strong_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.RandomHorizontalFlip(p=0.5),
    transforms.RandomRotation(degrees=10),
    transforms.RandomAffine(degrees=0, translate=(0.03, 0.03), scale=(0.97, 1.03)),
    transforms.ColorJitter(brightness=0.1, contrast=0.1),
    transforms.ToTensor(),
    transforms.Normalize(mean=0.41, std=0.16)
])

light_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.RandomRotation(degrees=5),
    transforms.ColorJitter(brightness=0.1, contrast=0.1),
    transforms.ToTensor(),
    transforms.Normalize(mean=0.41, std=0.16)
])

noAug_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize(mean=0.41, std=0.16)
])

transform_map = {
    "COVID": strong_transform,
    "Lung_Opacity": light_transform,
    "Viral Pneumonia": strong_transform,
    "Normal": light_transform
}
val_transform_map = defaultdict(lambda: noAug_transform)

# Load data with different transform
dataset = XRayLungDataset(root, categories, val_transform_map)
train_dataset = XRayLungDataset(root, categories, transform_map)
print("\nDataset loaded!")

# Data spilt
indices = list(range(len(dataset)))
train_idx, temp_idx = train_test_split(indices, test_size=0.3, stratify=[dataset.labels[i] for i in indices], random_state=42)
val_idx, test_idx = train_test_split(temp_idx, test_size=0.5, stratify=[dataset.labels[i] for i in temp_idx], random_state=42)

train_labels = [dataset.labels[i] for i in train_idx]

# Use WeightedRandomSampler and manually adjust class selection probabilities to solve class imbalance
# From 3:6:10:1 -> 4:5:6:2
class_weights_manual = {
    0: 0.0000963,
    1: 0.0000708,
    2: 0.0000504,
    3: 0.0001251
}

sample_weights = [class_weights_manual[label] for label in train_labels]

sampler = WeightedRandomSampler(
    weights=sample_weights,
    num_samples=len(sample_weights),
    replacement=True
)

# Build dataloader
train_loader = DataLoader(Subset(train_dataset, train_idx), batch_size=16, sampler=sampler)
val_loader = DataLoader(Subset(dataset, val_idx), batch_size=16, shuffle=False)
test_loader = DataLoader(Subset(dataset, test_idx), batch_size=16, shuffle=False)

print("\nData split completed!")

# Load and configure the pre-trained model Densenet121
densenet_model = densenet121(weights=DenseNet121_Weights.IMAGENET1K_V1)
densenet_model.classifier = nn.Linear(1024, 4)

# After tuning, all feature extraction layers should be unfrozen
for param in densenet_model.parameters():
    param.requires_grad = True

densenet_model = densenet_model.to(device)

optimizer = optim.Adam(densenet_model.parameters(), lr=1e-4)

# Set class weights in the loss function and tune them based on the current dataset
class_weights = torch.tensor([0.35, 0.30, 0.25, 0.30], dtype=torch.float).to(device)
criterion = nn.CrossEntropyLoss(weight=class_weights)

# Training
savePath = "densenet_model_8.pth"
num_epochs = 15
train_losses, val_losses = [], []
train_accuracies, val_accuracies = [], []
macro_f1_scores = []
best_f1 = 0.0

print("\nStarting Training...")

for epoch in range(num_epochs):
    densenet_model.train()
    total_loss, correct, total = 0, 0, 0

    for images, labels in train_loader:
        images, labels = images.to(device), labels.to(device)
        outputs = densenet_model(images)
        loss = criterion(outputs, labels)

        optimizer.zero_grad()
        loss.backward()
        optimizer.step()

        total_loss += loss.item()
        _, preds = torch.max(outputs, 1)
        correct += (preds == labels).sum().item()
        total += labels.size(0)

    train_loss = total_loss / len(train_loader)
    train_acc = correct / total
    train_losses.append(train_loss)
    train_accuracies.append(train_acc)

    # Validation
    densenet_model.eval()
    val_loss, correct, total = 0, 0, 0
    all_preds, all_labels = [], []

    with torch.no_grad():
        for imgs, labels in val_loader:
            imgs, labels = imgs.to(device), labels.to(device)
            outputs = densenet_model(imgs)
            loss = criterion(outputs, labels)
            val_loss += loss.item()

            _, preds = torch.max(outputs, 1)
            correct += (preds == labels).sum().item()
            total += labels.size(0)

            all_preds.extend(preds.cpu().numpy())
            all_labels.extend(labels.cpu().numpy())

    report_dict = classification_report(all_labels, all_preds, target_names=categories, output_dict=True)
    val_loss = val_loss / len(val_loader)
    val_acc = correct / total
    val_f1 = f1_score(all_labels, all_preds, average='macro')
    val_losses.append(val_loss)
    val_accuracies.append(val_acc)
    macro_f1_scores.append(val_f1)

    print(f"Epoch {epoch + 1}/{num_epochs} \n"
          f"Train Loss: {train_loss:.4f} | Train Acc: {train_acc:.4f} | "
          f"Val Loss: {val_loss:.4f} | Val Acc: {val_acc:.4f} | "
          f"Macro F1 Score: {val_f1:.4f}")

    for cat in categories:
        print(f"   - {cat}: {report_dict[cat]['f1-score']:.4f}")

    if val_f1 > best_f1:
        best_f1 = val_f1
        torch.save(densenet_model.state_dict(), savePath)
        print(f"Best model saved with macro-F1: {val_f1:.4f}")

print("\nTraining Completed!")
print(f"\nModel saved to {savePath}")

# Generate training report
plt.figure(figsize=(12, 5))

plt.subplot(1, 2, 1)
plt.plot(range(1, num_epochs + 1), train_losses, label='Training Loss')
plt.plot(range(1, num_epochs + 1), val_losses, label='Validation Loss')
plt.xlabel('Epoch')
plt.ylabel('Loss')
plt.title('Training and Validation Loss')
plt.legend()

plt.subplot(1, 2, 2)
plt.plot(range(1, num_epochs + 1), train_accuracies, label='Training Accuracy')
plt.plot(range(1, num_epochs + 1), val_accuracies, label='Validation Accuracy')
plt.xlabel('Epoch')
plt.ylabel('Accuracy')
plt.title('Training and Validation Accuracy')
plt.legend()

plt.tight_layout()
plt.savefig("train_val_curves.png")
plt.show()

plt.figure(figsize=(6, 5))
plt.plot(range(1, num_epochs + 1), macro_f1_scores, label='Validation Macro F1')
plt.xlabel('Epoch')
plt.ylabel('Macro F1-score')
plt.title('Validation Macro F1 over Epochs')
plt.legend()
plt.savefig('macro_f1_curve.png')
plt.show()

# Test on test set
densenet_model.load_state_dict(torch.load(savePath))
densenet_model.eval()

all_preds, all_labels = [], []

with torch.no_grad():
    for images, labels in test_loader:
        outputs = densenet_model(images.to(device))
        _, preds = torch.max(outputs, 1)
        all_preds.extend(preds.cpu().numpy())
        all_labels.extend(labels.numpy())

print("Test Set Evaluation:\n")
report = classification_report(all_labels, all_preds, target_names=categories, digits=4)
print("Classification Report:\n")
print(report)
cm = confusion_matrix(all_labels, all_preds)
plt.figure(figsize=(8, 6))
sns.heatmap(cm, annot=True, fmt='d', cmap='Blues', xticklabels=categories, yticklabels=categories)
plt.xlabel('Predicted')
plt.ylabel('True')
plt.title('Confusion Matrix on Test Set')
plt.savefig('test_confusion_matrix.png')
plt.show()
