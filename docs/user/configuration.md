# Configuration Reference

Complete reference of environment variables and operational settings for Chege Photos WebApp.

---

## 1. Application & Core Settings

| Variable | Environment | Default | Description |
|---|---|---|---|
| `CI_ENVIRONMENT` | All | `production` | Framework mode: `development` (displays error backtraces) or `production`. |
| `app.baseURL` | All | `http://localhost:8080/` | Public root URL of the web application. Must end with a trailing slash. |
| `app.forceGlobalSecureRequests`| Production | `false` | When `true`, automatically redirects all HTTP traffic to HTTPS. |
| `encryption.key` | All | *None* | 32-byte hexadecimal key for encryption. Generated via `php spark key:generate`. |

---

## 2. Database Connection (MySQL 8.4)

When running on Docker or bare-metal, use the standard `database.default.*` keys. On cloud providers like Railway, standard platform variables (`MYSQLHOST`, `MYSQLUSER`, etc.) are auto-detected.

| Variable | Railway Variable | Default | Description |
|---|---|---|---|
| `database.default.hostname` | `MYSQLHOST` / `DB_HOST` | `mysql` | MySQL hostname or IP address. |
| `database.default.database` | `MYSQLDATABASE` / `DB_NAME` | `db_chege_photos` | Database name. |
| `database.default.username` | `MYSQLUSER` / `DB_USER` | `root` | Database user. |
| `database.default.password` | `MYSQLPASSWORD` / `DB_PASS` | `root_password` | Database password. |
| `database.default.port` | `MYSQLPORT` / `DB_PORT` | `3306` | MySQL port. |

---

## 3. Microservice & Vector Database Integration

| Variable | Default | Description |
|---|---|---|
| `ML_URL` | `http://ml-chege-photos:9051` | Network URL of the FastAPI `ML Chege Photos` microservice. |
| `ML_API_KEY` | *None* | Secret key sent via `X-API-KEY` header to authenticate with the ML service. |
| `QDRANT_URL` | `http://qdrant:6333` | REST URL of Qdrant vector database (used for direct telemetry). |

---

## 4. Google Cloud Storage (Persistent Offsite Backup)

| Variable | Default | Description |
|---|---|---|
| `GCP_BUCKET` | *None* | GCS Bucket name for persistent cloud storage. |
| `GCP_AUTH_TYPE` | `json` | Auth type: `json` (Service Account key) or `hmac` (S3 interoperability). |
| `GCP_SERVICE_ACCOUNT_JSON`| *None* | Raw JSON content of the Google Cloud Service Account Key. |
| `GCP_ACCESS_KEY` | *None* | Access key for HMAC authentication. |
| `GCP_SECRET_KEY` | *None* | Secret key for HMAC authentication. |

---

## 5. Superadmin Seed Credentials

Used on initial database provisioning by `SystemDefaultSeeder`:

| Variable | Default | Description |
|---|---|---|
| `SUPERADMIN_EMAIL` | `superadmin@example.com` | Root admin email. |
| `SUPERADMIN_PASSWORD` | `SuperAdmin@2024!` | Root admin initial password. |
| `SUPERADMIN_USERNAME` | `superadmin` | Root admin username. |
