import torch
import gradio as gr
from PIL import Image
import numpy as np
from torchvision.models import densenet121, DenseNet121_Weights
import albumentations as A
from albumentations.pytorch import ToTensorV2
from torchvision import transforms
import segmentation_models_pytorch as smp
import torch.nn as nn
import cv2
import matplotlib.cm as cm

device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

# Segmentation Model
m1 = smp.Unet(
    encoder_name="resnet34",
    encoder_weights=None,
    in_channels=1,
    classes=1
).to(device)
m1.load_state_dict(torch.load("weights/Segmentation_Model.pth", map_location=device))
m1.eval()

# Classification Model
m2 = densenet121(weights=None)
m2.classifier = nn.Linear(1024, 4)
m2.load_state_dict(torch.load("weights/Classification_Model.pth", map_location=device))
m2.eval().to(device)

# Transforms
unet_transform = A.Compose([
    A.Resize(256, 256),
    A.Normalize(mean=0.5, std=0.5),
    ToTensorV2()
])

classifier_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize(mean=0.41, std=0.16)
])


# Inference Function
def analyze(image):
    # Grayscale image for UNet
    image_gray = image.convert("L")
    img_np = np.array(image_gray)

    # UNet input
    augmented = unet_transform(image=img_np)
    unet_input = augmented["image"].unsqueeze(0).to(device)

    # Segmentation
    with torch.no_grad():
        mask_pred = m1(unet_input)
        mask_pred = torch.sigmoid(mask_pred)
        mask = (mask_pred > 0.5).float().squeeze().cpu().numpy()

    # Resize to match classifier input
    resized_gray = image_gray.resize((224, 224), Image.BILINEAR)
    mask_img = Image.fromarray((mask * 255).astype(np.uint8))
    mask_resized = transforms.functional.resize(mask_img, [224, 224])
    image_np = np.array(resized_gray).astype(np.float32)
    mask_np = (np.array(mask_resized) > 127).astype(np.float32)

    lung_image = image_np * mask_np
    lung_image_3ch = np.stack([lung_image] * 3, axis=-1)
    lung_image_3ch = np.clip(lung_image_3ch, 0, 255).astype(np.uint8)
    lung_image_pil = Image.fromarray(lung_image_3ch)

    input_tensor = classifier_transform(lung_image_pil).unsqueeze(0).to(device)

    # Classification
    with torch.no_grad():
        logits = m2(input_tensor)
        probs = torch.softmax(logits, dim=1)
        confidence, pred_class = torch.max(probs, dim=1)

    classes = ["COVID", "Lung_Opacity", "Normal", "Viral Pneumonia"]
    confidence_percent = f"{confidence.item() * 100:.2f}%"
    
    return classes[pred_class.item()], confidence_percent

# Gradio UI
interface = gr.Interface(
    fn=analyze,
    inputs=gr.Image(type="pil"),
    outputs=[
    gr.Text(label="Prediction"),
    gr.Text(label="Confidence")],
    title="Chest X-Ray Analysis",
    description="Upload a chest X-ray to detect disease."
)

interface.launch()