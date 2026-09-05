# Setup and Run Guide

Step-by-step instructions for operators and QA to start, verify, and shut down the Chege Photos WebApp stack.

---

## 1. Quick Start via Docker Compose (Recommended)

The easiest and most reliable way to run the WebApp is via Docker Compose, which packages PHP 8.3 Apache, MySQL 8.4, and phpMyAdmin.

### Step 1: Clone Repository
```bash
git clone https://github.com/niccher/Chege-Photos-WebApp.git
cd "Chege Photos WebApp"
```

### Step 2: Environment Configuration
Copy the example environment template:
```bash
cp .env.example .env
```

Review key configuration variables in `.env`:
- `app.baseURL`: Ensure this matches your local access host (e.g. `http://localhost:9005/`).
- `database.default.hostname`: Default is `mysql` inside Docker.

### Step 3: Launch Containers
Start all services in detached mode:
```bash
docker compose up --build -d
```

### Step 4: Verify Container Health
Inspect running status and ports:
```bash
docker compose ps
```

Expected output:
```text
NAME                  IMAGE                        COMMAND                  SERVICE        CREATED         STATUS                   PORTS
chege-photos-webapp   chege-photos-webapp:latest   "entrypoint.sh apach…"   chege-photos   1 minute ago    Up 1 minute              0.0.0.0:9005->80/tcp
shared-mysql          mysql:8.4                    "docker-entrypoint.s…"   mysql          1 minute ago    Up 1 minute (healthy)    0.0.0.0:9306->3306/tcp
shared-phpmyadmin     phpmyadmin:latest            "/docker-entrypoint.…"   phpmyadmin     1 minute ago    Up 1 minute              0.0.0.0:9000->80/tcp
```

---

## 2. Verifying the Running Application

Open your browser and test the following access points:

| Service | URL | Default Credentials | Verification Check |
|---|---|---|---|
| **Web Application** | [http://localhost:9005](http://localhost:9005) | `superadmin@eavesdroid.com` / `SuperAdmin@2024!` | Login redirects to `/admin/home` or photo grid. |
| **phpMyAdmin** | [http://localhost:9000](http://localhost:9000) | `root` / `root_password` | Database `db_chege_photos` contains 24 tables. |
| **REST Health Probe** | `http://localhost:9005/api/v1/health` | Public / Token | Returns JSON status `success`. |

---

## 3. Stopping the Application

To shut down the containers while preserving all MySQL data and uploaded photos:
```bash
docker compose stop
```

To completely tear down the containers:
```bash
docker compose down
```

> [!WARNING]
> Running `docker compose down -v` will destroy the attached Docker volumes, wiping the local database. Only use `-v` when you explicitly want a factory reset.
