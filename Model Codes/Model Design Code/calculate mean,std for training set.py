import torch
from torchvision import transforms
from torch.utils.data import Dataset
from sklearn.model_selection import train_test_split
import os
import numpy as np
from PIL import Image
from collections import defaultdict
from tqdm import tqdm

root = r"F:\UOW Learning Materials\Final Year Project\archive\COVID-19_Radiography_Dataset"
categories = ["COVID", "Lung_Opacity", "Normal", "Viral Pneumonia"]

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
        lung_image_3ch = np.stack([lung_image]*3, axis=-1)
        lung_image_pil = Image.fromarray(lung_image_3ch.astype(np.uint8))

        class_name = self.categories[self.labels[idx]]
        transform = self.transform_map.get(class_name, None)

        if transform:
            lung_image_tensor = transform(lung_image_pil)
        else:
            lung_image_tensor = torch.from_numpy(lung_image_3ch).permute(2,0,1)

        return lung_image_tensor, label


noAug_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize(mean=0.41, std=0.16)
])

val_transform_map = defaultdict(lambda: noAug_transform)

dataset = XRayLungDataset(root, categories, val_transform_map)

indices = list(range(len(dataset)))
train_idx, temp_idx = train_test_split(indices, test_size=0.3, stratify=[dataset.labels[i] for i in indices], random_state=42)
val_idx, test_idx = train_test_split(temp_idx, test_size=0.5, stratify=[dataset.labels[i] for i in temp_idx], random_state=42)


def compute_stats(root, train_idx, categories, image_size=(224, 224)):


    image_paths = []
    mask_paths = []

    for category in categories:
        img_dir = os.path.join(root, category, "images")
        msk_dir = os.path.join(root, category, "masks")
        img_files = sorted([f for f in os.listdir(img_dir) if f.endswith(".png")])
        msk_files = sorted([f for f in os.listdir(msk_dir) if f.endswith(".png")])

        for img, msk in zip(img_files, msk_files):
            image_paths.append(os.path.join(img_dir, img))
            mask_paths.append(os.path.join(msk_dir, msk))

    pixel_sum = torch.zeros(3)
    pixel_sq_sum = torch.zeros(3)
    num_pixels = 0

    for idx in tqdm(train_idx, desc="Computing mean/std from lung regions"):
        img_path = image_paths[idx]
        msk_path = mask_paths[idx]

        img = Image.open(img_path).convert("L").resize(image_size)
        msk = Image.open(msk_path).convert("L").resize(image_size)

        img_np = np.array(img).astype(np.float32) / 255.0
        msk_np = (np.array(msk) / 255.0 > 0.5).astype(np.bool_)

        img_3ch = np.stack([img_np]*3, axis=0)

        mask = torch.tensor(msk_np)
        valid_pixels = torch.tensor(img_3ch)[:, mask]

        if valid_pixels.numel() > 0:
            pixel_sum += valid_pixels.sum(dim=1)
            pixel_sq_sum += (valid_pixels ** 2).sum(dim=1)
            num_pixels += valid_pixels.size(1)

    mean = pixel_sum / num_pixels
    std = torch.sqrt((pixel_sq_sum / num_pixels) - mean ** 2)
    return [round(m.item(), 2) for m in mean], [round(s.item(), 2) for s in std]

train_mean, train_std = compute_stats(root, train_idx, categories)
print("Mean:", train_mean)
print("Std:", train_std)