# 🩻 X-COVID Insight — AI-Powered COVID-19 Chest X-Ray Diagnosis Platform

A full-stack web application combining deep learning–based COVID-19 diagnosis with a multi-role clinic management system.

## Overview
X-COVID Insight is designed to streamline COVID-19 testing and diagnosis by analyzing chest X-rays with deep learning models, reducing doctor workload, and automating feedback-driven model improvement. The platform supports multiple user roles and integrates real-world clinic workflows, from booking and image upload to AI diagnosis and incremental retraining.

## 🧠 AI Model Architecture

### Lung Segmentation (UNet + ResNet34)
- Trained using COVID-19 Radiography Dataset
- Extracts lung regions to focus classifier attention and reduce noise
- Preprocessed with data augmentation and Dice + BCE loss for accuracy
- Target Dice score ≥ 95%

### Classification (DenseNet121)
- Input: segmented lung images (224×224)
- Output: 4 categories (COVID-19, Lung Opacity, Normal, Viral Pneumonia)
- Fine-tuned using transfer learning, custom normalization, and class-balanced training
- Macro F1-score target: ≥ 90%

### Confidence & Incremental Training
- <80% confidence predictions or random high-confidence samples sent to doctors for validation
- Doctor feedback used to incrementally retrain model via FastAPI
- Retrained model is deployed only if Macro F1 improves on evaluation set

## 🖥️ Web Platform Features

### 👤 Unregistered Users
- View homepage videos, pricing plans, reviews, FAQs, and contact form
- Register as Patient or Test Centre

### 🧑‍⚕️ Patients
- Book, view, and cancel appointments
- View validated X-ray records
- Submit reviews and edit profile

### 🧪 COVID Testers
- Upload X-rays via drag-and-drop interface
- View patient details and history
- Analyze X-rays using AI and view immediate results

### 🩺 Doctors
- Validate AI predictions for low-confidence and random samples
- View/edit profile, track validation history

### 🏥 Test Centre Admins
- Manage doctors, testers, and patients
- View clinic reviews, control time slot availability
- Analyze reports and revenue

### 🧑‍💻 System Admins
- Manage applications and clinic billing
- Edit homepage content, pricing, FAQs, and About Us
- View model performance and trigger retraining

## 📁 Project Structure

- **Model Codes** → Located in the `Model Codes` folder  
  Contains all deep learning training, evaluation, and API scripts (segmentation & classification).
  
- **Web Codes** → Located in the `X-COVID Insight` folder  
  Includes PHP, HTML, JavaScript, CSS files for the entire platform UI and backend integration.

- **Login Credentials** → Located in `X-COVID Insight/Accounts.txt`  
  Stores default user credentials for login testing.

## 📂 QA & Project Documentation

To ensure reliability, scalability, and maintainability in a medical diagnostic environment, this project follows a structured **Software Development Life Cycle (SDLC)**. All requirement specifications, manuals, and testing artifacts are centralized in the [QA-Documentation-Suite](./QA-Documentation-Suite) folder.

| Document | Description | Key Focus |
| :--- | :--- | :--- |
| **[Project Requirements Documentation](./QA-Documentation-Suite/Project%20Requirements%20Documentation.docx.pdf)** | Covers project overview, business model, research findings, use cases, risk analysis, and development strategy. | SDLC planning & product analysis. |
| **[URS & SRS Documentation](./QA-Documentation-Suite/User%20Requirement%20Specification%20(URS_SRS).docx.pdf)** | Detailed functional and non-functional system requirements for all user roles and workflows. | System behavior, constraints, and specifications. |
| **[Manual Test Case Suite](./QA-Documentation-Suite/Test%20Cases.xlsx)** | Comprehensive manual test cases covering all platform modules and user roles. | Functional testing, RBAC validation, and edge cases. |
| **[User Manual](./QA-Documentation-Suite/User%20Manual.pdf)** | Step-by-step operational guide for Patients, Doctors, COVID Testers, and Admins. | User onboarding and workflow navigation. |

### 🛠️ QA Methodology
* **Role-Based Access Control (RBAC) Testing:** Verified secure permission handling across all 6 user roles.
* **End-to-End Workflow Validation:** Tested the complete diagnostic pipeline from appointment booking to AI prediction and doctor validation.
* **AI Confidence Threshold Testing:** Validated low-confidence prediction escalation and incremental retraining workflows.
* **Manual Functional Testing:** Executed detailed test cases for UI behavior, workflows, validation rules, and system responses.
* **Defect Reporting & Tracking:** Documented bugs with reproduction steps, severity levels, and developer hand-off notes.

## 📊 Key Features
- **Real-Time AI Inference** via Gradio on Hugging Face Spaces
- **Incremental Training API** via FastAPI with doctor-labeled feedback
- **Secure, Role-Based Access** for 6 user types
- **Interactive Dashboards** for analytics and feedback
- **Content Management** for homepage videos, pricing, FAQs, and more
- **Automated Emails & Location Tools** via SendGrid and OneMap API

## ⚙️ Tech Stack
- **Frontend:** PHP, JavaScript, HTML, Tailwind CSS
- **Backend:** Python (FastAPI, Gradio), MongoDB
- **AI Models:** PyTorch, segmentation_models_pytorch, torchvision
- **Deployment:** Hugging Face Spaces, Render
- **3rd Party APIs:** SendGrid (email), OneMap (address autofill)

## 🌐 Live Website
👉 [Visit X-COVID Insight](https://xcovidinsight.onrender.com)

## 👤 Author
**Shin Than Thar Aung**
