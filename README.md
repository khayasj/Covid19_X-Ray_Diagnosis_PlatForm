# 🦠 X-COVID Insight — AI-Powered COVID-19 Chest X-Ray Diagnosis Platform

A six-month final-year project combining deep learning-based COVID-19 chest X-ray analysis with a multi-role clinic management platform.

> **Academic project notice:** This is a technical demonstration, not a clinically validated diagnostic product. It must not be used to make medical decisions.

## 🔍 Overview

X-COVID Insight demonstrates a clinic workflow from appointment booking and X-ray upload to AI-assisted analysis, doctor review and patient record management. It also includes clinic administration, reporting and feedback workflows.

## 🧠 AI Model Architecture

### 🫁 Lung Segmentation — UNet + ResNet34

- Trained using the COVID-19 Radiography Dataset.
- Extracts lung regions to focus classifier attention and reduce image noise.
- Uses image augmentation and Dice + binary cross-entropy loss.
- **Target Dice score: ≥95%.** This is a project target, not a verified clinical result.

### 🩻 Image Classification — DenseNet121

- Receives segmented images at 224 × 224 pixels.
- Classifies images into four categories: COVID-19, Lung Opacity, Normal and Viral Pneumonia.
- Uses transfer learning, custom normalisation and class-balanced training.
- **Target macro F1-score: ≥90%.** This is a project target, not a verified clinical result.

### 🔁 Confidence Review and Incremental Training

- Sends predictions below **80% confidence**, together with selected high-confidence samples, for doctor validation.
- Uses doctor feedback as labelled data for incremental model training through FastAPI.
- Deploys an updated model only when its macro F1-score improves on the evaluation set.

## 🖥️ Web Platform Features

### 👤 Unregistered Visitors
- View homepage videos, pricing plans, reviews, FAQs and contact information.
- Register as a patient or test centre.

### 🧑‍⚕️ Patients
- Book, view and cancel appointments.
- View validated X-ray records.
- Submit reviews and manage profiles.

### 🧪 COVID Testers
- Upload X-rays through a drag-and-drop interface.
- View patient details and history.
- Request AI analysis and view the returned results.

### 🩺 Doctors
- Review AI predictions, validate selected samples and track validation history.
- Provide feedback for the model-improvement workflow.

### 🏥 Test Centre Administrators
- Manage doctors, testers and patients.
- Manage reviews and appointment-slot availability.
- View clinic reports and revenue.

### 🧑‍💻 System Administrators
- Manage platform content and clinic billing.
- Review model performance and trigger retraining workflows.

## 📁 Project Structure

- **[Model Codes](./Model%20Codes/)** — Deep learning training, evaluation, segmentation, classification and API scripts.
- **[X-COVID Insight](./X-Covid%20Insight/)** — PHP, HTML, JavaScript and CSS files for the web application and backend integration.
- **[QA-Documentation-Suite](./QA-Documentation-Suite/)** — Requirements, test cases and user documentation.

## 📂 QA and Project Documentation

The project follows a structured software development life cycle, with requirements, test cases and user guidance organised in the QA documentation folder.

| Document | Description | Main focus |
|---|---|---|
| [Project Requirements Documentation](./QA-Documentation-Suite/Project%20Requirements%20Documentation.docx.pdf) | Project scope, business model, research, use cases, risk analysis and development plan. | Requirements and project planning |
| [URS & SRS Documentation](./QA-Documentation-Suite/User%20Requirement%20Specification%20(URS_SRS).docx.pdf) | Functional and non-functional requirements for the platform's user roles and workflows. | System requirements |
| [Manual Test Case Suite](./QA-Documentation-Suite/Test%20Cases.xlsx) | Test cases covering platform modules, role permissions and edge cases. | Functional and access-control testing |
| [User Manual](./QA-Documentation-Suite/User%20Manual.pdf) | Instructions for patients, doctors, testers and administrators. | User workflows |

## 🛠️ QA Methodology

- **Role-based access control (RBAC):** Tested permission handling across all six user types.
- **End-to-end workflow testing:** Checked booking, image upload, AI analysis and doctor review.
- **Confidence-threshold testing:** Checked the review route for predictions below the configured confidence threshold.
- **Manual functional testing:** Checked page behaviour, workflows, validation rules and system responses.
- **Defect reporting:** Recorded issues with reproduction steps, severity and handover notes.

## 📊 Key Features

- Real-time AI inference demonstration through Gradio and Hugging Face Spaces.
- FastAPI endpoint for feedback-based model retraining.
- Role-based access for six user types.
- Dashboards for clinic activity and feedback.
- Content management for homepage information, pricing and FAQs.
- Email and location features using SendGrid and OneMap APIs.

## ⚙️ Technology Stack

- **Frontend:** PHP, JavaScript, HTML, Tailwind CSS.
- **Backend:** Python, FastAPI, Gradio, MongoDB.
- **AI models:** PyTorch, segmentation_models_pytorch, torchvision.
- **Deployment:** Hugging Face Spaces, Render.
- **Third-party APIs:** SendGrid for email and OneMap for address autofill.

## 🌐 Live Project

[Visit X-COVID Insight](https://xcovidinsight.onrender.com)

## 👤 Author

**Shin Than Thar Aung**
