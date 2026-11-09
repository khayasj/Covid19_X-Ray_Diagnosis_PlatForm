# X-COVID Insight — Multi-role Clinic Workflow & Data Validation Platform

An academic full-stack project combining an X-ray analysis demonstration with a clinic workflow platform. The work covers application features, role-based access, validation steps, documentation and functional testing.

## Project scope

The platform demonstrates workflows for patients, doctors, COVID testers, test-centre administrators and system administrators.

Key application areas include:
- Appointment booking and patient record workflows
- Image upload and AI prediction demonstration
- Doctor review of low-confidence predictions
- Role-based access across six user roles
- Clinic administration, reporting and content management

## Data controls and testing

- Implemented role-based access controls for six user roles.
- Documented functional requirements, user requirements, test cases and user guidance.
- Tested end-to-end workflows, permission handling, validation rules and application behaviour.
- Included a review step for predictions below the configured confidence threshold.

## Important project note

This is an academic project and demonstration. It is not a clinically validated diagnostic product and must not be used to make medical decisions.

For security, test-account details, local configuration and runtime logs are not included in the repository. Set up local test accounts when running the project. Do not commit passwords, API keys, personal data or production credentials.

## Technology

- **Backend and APIs:** Python, FastAPI, PHP
- **AI demonstration:** PyTorch, segmentation and classification models
- **Data:** MongoDB
- **Frontend:** HTML, CSS, JavaScript and Tailwind CSS
- **Deployment and services:** Hugging Face Spaces, Render and third-party APIs

## Documentation

The `QA-Documentation-Suite` folder contains project requirements, user requirements, test cases and user documentation.

## Author

Shin Than Thar Aung
