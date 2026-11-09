# X-COVID Insight; AI-Powered COVID-19 Chest X-Ray Diagnosis Platform

A six-month final-year project combining deep learning-based COVID-19 chest X-ray analysis with a multi-role clinic management platform.

## Overview

X-COVID Insight demonstrates a clinic workflow from appointment booking and X-ray upload to AI-assisted analysis, doctor review and patient record management. It also includes clinic administration, reporting and feedback workflows.

This is an academic project and a technical demonstration. It is **not a clinically validated diagnostic product** and must not be used to make medical decisions.

## AI model architecture

### Lung segmentation — UNet + ResNet34

- Trained using the COVID-19 Radiography Dataset.
- Segments lung regions to focus the classifier on relevant image areas.
- Uses image augmentation and Dice + binary cross-entropy loss.
- **Target Dice score: at least 95%.** This is a project target, not a claim of independently verified clinical performance.

### Image classification — DenseNet121

- Receives segmented images at 224 × 224 pixels.
- Classifies images into four categories: COVID-19, Lung Opacity, Normal and Viral Pneumonia.
- Uses transfer learning, custom normalisation and class-balanced training.
- **Target macro F1-score: at least 90%.** This is a project target, not a claim of independently verified clinical performance.

### Confidence review and incremental training

- Sends predictions below 80% confidence, together with selected high-confidence samples, for doctor validation.
- Uses doctor feedback as labelled data for incremental model training through FastAPI.
- Deploys an updated model only when its macro F1-score improves on the evaluation set.

## Web platform features

The platform supports six user types:

| User type | Main functions |
|---|---|
| Unregistered visitors | View information, pricing, reviews, FAQs and contact details; register as a patient or test centre |
| Patients | Book, view and cancel appointments; access validated X-ray records; submit reviews and manage profiles |
| COVID testers | Upload X-rays; view patient details and history; request AI analysis |
| Doctors | Review AI predictions, validate selected results and track validation history |
| Test-centre administrators | Manage doctors, testers and patients; manage reviews and appointment slots; view reports and revenue |
| System administrators | Manage platform content, clinic billing, model performance and retraining workflows |

## Project structure

- **Model Codes** — Deep learning training, evaluation, segmentation, classification and API scripts.
- **X-Covid Insight** — PHP, HTML, JavaScript and CSS files for the web application and backend integration.
- **QA-Documentation-Suite** — Requirements, test cases and user documentation.

For security, test credentials and local configuration should be created locally and must not be committed to the public repository.

## QA and project documentation

The project includes documentation covering:

| Document | Purpose |
|---|---|
| Project Requirements Documentation | Project scope, business model, research, use cases, risk analysis and development plan |
| User Requirements and Software Requirements (URS/SRS) | Functional and non-functional requirements for each user type |
| Manual Test Case Suite | Functional tests, role-based access checks and edge cases |
| User Manual | Instructions for patients, doctors, testers and administrators |

### QA methodology

- **Role-based access control:** Tested permissions across all six user types.
- **End-to-end workflow testing:** Checked booking, image upload, AI analysis and doctor review.
- **Confidence-threshold handling:** Tested the review route for predictions below the configured threshold.
- **Functional testing:** Checked page behaviour, workflows, validation rules and system responses.
- **Defect reporting:** Recorded issues with reproduction steps, severity and handover notes.

## Key features

- AI inference demonstration through Gradio and Hugging Face Spaces.
- FastAPI endpoint for feedback-based model retraining.
- Role-based access across six user types.
- Dashboards for clinic activity and feedback.
- Content management for homepage information, pricing and FAQs.
- Email and location features using SendGrid and OneMap APIs.

## Technology stack

- **Frontend:** PHP, JavaScript, HTML and Tailwind CSS.
- **Backend:** Python, FastAPI, Gradio and MongoDB.
- **AI models:** PyTorch, segmentation_models_pytorch and torchvision.
- **Deployment:** Hugging Face Spaces and Render.
- **Third-party APIs:** SendGrid for email and OneMap for address autofill.

## Live project

[Visit X-COVID Insight](https://xcovidinsight.onrender.com)

## Author

Shin Than Thar Aung
