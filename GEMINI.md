# Chege Photos WebApp — Agent Guidelines & Ground Truth

## 1. Ground Truth Architecture
* **Primary WebApp Port**: `9005` (Apache / PHP 8.3 container).
* **Database**: MySQL 8.4 on port `9306` (service name `mysql`).
* **ML Microservice**: Located at `ML_URL` (`http://localhost:9051` or `http://ml-chege-photos:8000`).
* **Auxiliary Tools Policy**: `phpMyAdmin` and other third-party DB inspection tools are strictly NOT part of this stack. Do NOT add or document them as running containers.

## 2. Multi-Repo Integration Boundaries
* **Android Companion**: Communicates exclusively through the WebApp REST API (`/api/v1/`). Android never connects directly to the ML service or Qdrant.
* **ML Service**: WebApp dispatches asynchronous facial recognition, YOLO tagging, and CLIP embedding jobs to the ML container.

## 3. Privacy & Sanitization
* Never commit or document real personal emails, personal usernames, or absolute user home paths.
* Always use `admin@example.com` and generic placeholders.
