# Chege Photos WebApp — Engineering Handbook

Welcome to the engineering documentation for Chege Photos WebApp. This documentation is intended for software engineers maintaining, debugging, or extending the application.

If you only need to deploy, configure, or run the web application, see the [Root README](../README.md).

---

## Documentation Navigation

| I want to… | Go here |
|---|---|
| **Run the system without coding** | [../README.md](../README.md) |
| **Understand system architecture & C4 containers** | [architecture/overview.md](architecture/overview.md) |
| **Inspect protocols, cURL proxies & sequences** | [architecture/communication.md](architecture/communication.md) |
| **Review database schemas, vectors & GCP rehydration** | [architecture/data-and-storage.md](architecture/data-and-storage.md) |
| **Deploy to Railway or Docker Compose** | [architecture/deployment.md](architecture/deployment.md) |
| **Review REST API contract & headers** | [api/contract.md](api/contract.md) |
| **Work on CodeIgniter 4 controllers & Spark CLI** | [services/codeigniter.md](services/codeigniter.md) |
| **Set up a bare-metal dev machine** | [engineering/local-development.md](engineering/local-development.md) |
| **Add a feature safely & Definition of Done** | [engineering/making-changes.md](engineering/making-changes.md) |
| **Manage database migrations & query scoping** | [engineering/database.md](engineering/database.md) |
| **Execute PHPUnit tests & linting** | [engineering/testing.md](engineering/testing.md) |
| **Review operator configuration & environment** | [user/configuration.md](user/configuration.md) |
| **Troubleshoot runtime & startup issues** | [user/troubleshooting.md](user/troubleshooting.md) |

---

## Sibling Repositories

| Repository | Responsibility | Tech Stack |
|---|---|---|
| **[Chege-Photos-WebApp](https://github.com/niccher/Chege-Photos-WebApp)** | Core Web UI, Admin, Auth & Mobile Sync | PHP 8.3 / CodeIgniter 4 |
| **[Chege-Photos-ML](https://github.com/niccher/Chege-Photos-ML)** | Face Detection, YOLOv8, CLIP & Qdrant | Python 3.12 / FastAPI |
| **[Chege-Photos-Android](https://github.com/niccher/Chege-Photos-Android)** | Native Mobile Companion Client | Kotlin / Jetpack Compose |
