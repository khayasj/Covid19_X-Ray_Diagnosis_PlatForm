# X-COVID Insight — Chest X-Ray Analysis & Clinic Workflow Platform

An academic final-year project combining a chest X-ray analysis demonstration with a multi-role clinic workflow platform. The project covers application development, model interpretation, role-based access, validation workflows, functional testing and technical documentation.

## Project scope

The platform demonstrates workflows for patients, doctors, COVID testers, test-centre administrators and system administrators.

Key application areas include:
- Appointment booking and patient record workflows
- Chest X-ray upload and CNN-based classification demonstration
- Grad-CAM visualisations to help inspect which image regions influenced model predictions
- Doctor review of low-confidence predictions
- Role-based access across six user roles
- Clinic administration, reporting and content management

## Data controls and testing

- Implemented role-based access controls for six user roles.
- Documented functional requirements, user requirements, test cases and user guidance.
- Tested end-to-end workflows, permission handling, validation rules and application behaviour.
- Included a review step for predictions below the configured confidence threshold.

## Important project note

This is an academic project and demonstration. It is not a clinically validated diagnostic product and must not be used to make medical decisions. Model outputs and Grad-CAM visualisations are for project demonstration and interpretation only.

## Technology

- **Backend and APIs:** Python, FastAPI, PHP
- **AI demonstration:** PyTorch, CNN classification, image segmentation and Grad-CAM
- **Data:** MongoDB
- **Frontend:** HTML, CSS, JavaScript and Tailwind CSS
- **Deployment and services:** Hugging Face Spaces, Render and third-party APIs

## Documentation

The `QA-Documentation-Suite` folder contains project requirements, user requirements, test cases and user documentation.

## Author

Shin Than Thar Aung
