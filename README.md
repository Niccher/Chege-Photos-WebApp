# Chege Photos WebApp

Self-hosted personal photo and video management platform featuring automated ML face clustering, multimodal semantic search, Google Cloud Storage hydration, and companion Android synchronization.

**Stack**: PHP 8.3 / CodeIgniter 4, MySQL 8.4, Apache, Docker Compose.  
**Audience**: If you only need to run or test the system, this page is enough. Software engineers: [docs/README.md](docs/README.md).

---

## What “Running” Looks Like

| Piece | URL / How to open | Port | Purpose / Default Credential |
|---|---|---|---|
| **Web Application** | [http://localhost:9005](http://localhost:9005) | `9005` | Web UI & Admin (`admin@example.com` / `SuperAdmin@2024!`) |
| **MySQL 8.4** | `localhost:9306` | `9306` | Database (`db_chege_photos` / `root_password`) |
| **REST Health Check**| `GET http://localhost:9005/api/v1/health` | `9005` | Returns `200 OK` JSON |
| **Android Client** | Companion APK | — | Connects to WebApp at `http://10.0.2.2:9005` (emulator) or LAN IP |

---

## Prerequisites

### Option A — Docker (Recommended)
* Docker Engine 24.0+ & Docker Compose v2 (or Docker Desktop)
* Git
* 2 GB available RAM

### Option B — Bare-Metal
* PHP 8.2 or 8.3 (`intl`, `mysqli`, `gd`, `zip`, `exif`, `curl`, `mbstring`)
* MySQL 8.0+ or MariaDB 10.6+
* Composer 2.x
* Apache 2.4 (with `mod_rewrite` enabled)

---

## Setup and Run

From a fresh terminal:

```bash
# 1. Clone repository
git clone https://github.com/niccher/Chege-Photos-WebApp.git
cd "Chege Photos WebApp"

# 2. Copy environment configuration
cp .env.example .env

# 3. Launch containers in background
docker compose up --build -d

# 4. Verify running services
docker compose ps

# 5. Open browser at http://localhost:9005 and log in with default superadmin credentials.

# 6. To stop containers when finished:
docker compose stop
```

> [!NOTE]
> On first boot, the container automatically waits for MySQL, executes database migrations, seeds the default superuser, and initializes the background cron runner.

---

## Configuration

| Variable | Default | Purpose |
|---|---|---|
| `app.baseURL` | `http://localhost:8080/` | Public access URL (set to `http://localhost:9005/` for Docker). |
| `database.default.hostname` | `mysql` | MySQL hostname (use `localhost` for bare-metal). |
| `database.default.database` | `db_chege_photos` | Database name. |
| `ML_URL` | `http://ml-chege-photos:9051` | Network address of the FastAPI ML microservice. |
| `GCP_BUCKET` | *None* | Google Cloud Storage bucket for persistent media backup. |

Full list of operational environment variables: [docs/user/configuration.md](docs/user/configuration.md).

---

## Troubleshooting

* **Port 9005 already in use**: Change host port in `docker-compose.yml` to `9010:80` and update `app.baseURL`.
* **MySQL not ready yet**: First-time database initialization takes ~15–30 seconds. Run `docker compose logs -f mysql`.
* **Android emulator cannot connect**: Use `http://10.0.2.2:9005` as the server URL instead of `localhost`.
* **Photos return 404 after container redeploy**: Configure GCP credentials to enable on-demand cloud rehydration.

Detailed symptoms and step-by-step fixes: [docs/user/troubleshooting.md](docs/user/troubleshooting.md).

---

## Engineering Documentation

For architecture, database schemas, API specs, and development workflows, see the **[Engineering Handbook](docs/README.md)**:

* [Architecture Overview & C4 Containers](docs/architecture/overview.md)
* [Protocols & Inter-Service Communication](docs/architecture/communication.md)
* [Database Schemas & Storage Hierarchy](docs/architecture/data-and-storage.md)
* [Production Deployment (Railway & Docker)](docs/architecture/deployment.md)
* [REST API Contract](docs/api/contract.md)
* [CodeIgniter 4 Service Handbook & Spark CLI](docs/services/codeigniter.md)
* [Local Development Guide](docs/engineering/local-development.md)
* [Database Migrations & Management](docs/engineering/database.md)
* [Making Changes & Definition of Done](docs/engineering/making-changes.md)
* [Testing Guide](docs/engineering/testing.md)

---

## Ecosystem & Multi-Repo Architecture

Chege Photos is composed of three decoupled repositories that work together:

```
[ Android Companion App ]
         │
         │ (HTTPS / Bearer Token - port 9005)
         ▼
[ Chege Photos WebApp ] (Port 9005) ─── talks to ───► [ MySQL 8.4 ] (Port 9306)
         │
         │ (HTTP / X-API-KEY - port 9051)
         ▼
[ ML Chege Photos ] (Port 9051) ─────── talks to ───► [ Qdrant Vector DB ] (Port 9052)
```

### How the Repositories Interact
1. **Android App** talks **exclusively to WebApp**:
   - The Android client never communicates with the ML service directly.
   - It only needs the WebApp address (`http://10.0.2.2:9005` in emulator, or host LAN IP e.g. `http://192.168.1.50:9005` on physical devices).
2. **WebApp delegates AI to ML Service**:
   - When photos are uploaded (from WebApp UI or Android), WebApp dispatches asynchronous scan jobs to the ML service via `ML_URL` (`http://localhost:9051` or `http://ml-chege-photos:8000`).
   - The ML service detects faces (InsightFace), tags objects (YOLOv8), and generates vector embeddings (CLIP) stored in Qdrant.
   - When Android or WebApp searches or explores faces, the WebApp queries the ML service/Qdrant transparently.

### Startup Order for Complete Stack
1. **Start WebApp First** (spins up MySQL and creates `hosts-shared-network`):
   ```bash
   cd "Chege Photos WebApp"
   docker compose up -d
   ```
2. **Start ML Service Second** (joins `hosts-shared-network` and spins up Qdrant):
   ```bash
   cd "ML Chege Photos"
   docker compose up -d
   ```
3. **Run Android Companion** (build & run in Android Studio; scan QR code in WebApp Settings to pair).

---

## Sibling Repositories

| Repository | Responsibility | Tech Stack |
|---|---|---|
| **[Chege-Photos-WebApp](https://github.com/niccher/Chege-Photos-WebApp)** | Core Web UI, Admin, Auth & Mobile Sync | PHP 8.3 / CodeIgniter 4 |
| **[Chege-Photos-ML](https://github.com/niccher/Chege-Photos-ML)** | Face Detection, YOLOv8, CLIP & Qdrant | Python 3.12 / FastAPI |
| **[Chege-Photos-Android](https://github.com/niccher/Chege-Photos-Android)** | Native Mobile Companion Client | Kotlin / Jetpack Compose |

---

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.
