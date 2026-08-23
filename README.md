<div align="center">

# Chege Photos WebApp

Self-hosted personal photo management platform with ML-powered face recognition.

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4-EF4223?style=for-the-badge&logo=codeigniter)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?style=for-the-badge&logo=mysql)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-yellow?style=for-the-badge)

</div>

---

## About the Project

Chege Photos is a self-hosted photo management web application built on CodeIgniter 4 with Shield authentication. It provides upload, organisation (albums, folders), viewing (grid, fullscreen, map explore, memories timeline), sharing, and analytics — all augmented by an ML face recognition service (sibling repo: [ML Chege Photos](https://github.com/niccher/Chege-Photos-ML)) for automatic face detection, person grouping, and similarity search. An [Android companion app](https://github.com/niccher/Chege-Photos-Android) syncs with the same backend via REST API.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                          Docker (hosts-shared-network)               │
│                                                                     │
│  ┌────────────────────┐              ┌──────────────────────────┐   │
│  │  chege-photos       │  HTTP 9005   │  Browser / Android App   │   │
│  │  (PHP 8.3 Apache)   │◀────────────▶│  (Web UI + REST API)     │   │
│  │  port 80→9005      │              └──────────────────────────┘   │
│  └──────┬─────────────┘                                             │
│         │                                                           │
│         │  CURL proxy to ML service                                 │
│         ▼                                                           │
│  ┌────────────────────┐   HTTP       ┌──────────────────────────┐   │
│  │  ML Chege Photos   │◀────────────▶│  MySQL 8.4 (shared-mysql)│   │
│  │  (FastAPI)          │             │  port 3306→9306          │   │
│  │  port 9051         │             │  db_chege_photos         │   │
│  └────────────────────┘             └──────────────────────────┘   │
│                                                                     │
│  ┌────────────────────┐                                             │
│  │  phpMyAdmin        │  HTTP 9000  (dev convenience)               │
│  │  port 80→9000     │                                             │
│  └────────────────────┘                                             │
└─────────────────────────────────────────────────────────────────────┘
```

The web app acts as both a standalone photo manager and a proxy to the ML service. All face-related endpoints (`api/faces/scan`, `api/faces/cluster`, `api/faces/search`, etc.) are proxied from the CI4 controllers to `http://ml-chege-photos:8000` via cURL. The Android app communicates directly with both the web app's REST API and the ML service's API.

---

## Features

### Photo Management
- **Upload** — AJAX multi-file upload via Dropzone.js, 512 MB max file size, auto EXIF extraction
- **Grid & Fullscreen** — Responsive photo grid with infinite scroll; fullscreen lightbox carousel with keyboard navigation and slideshow mode
- **EXIF metadata** — Camera model, ISO, shutter speed, aperture, GPS coordinates, and more extracted on upload
- **Bulk operations** — Select multiple photos for batch favorite, archive, delete, trash, download, add-to-album
- **Soft delete** — Trash with 60-day retention; permanent delete only after 60 days

### Albums
- **Manual albums** — Create, name, describe, and populate with photos via drag-select
- **Smart albums** — Rule-based auto-populating albums with filters: date range, camera model, GPS bounds, file type, favorites
- **Cover photos** — Auto-selected or manually set album cover image

### Face Recognition (requires ML service)
- **Auto-detect & scan** — Face detection across all photos; per-photo scan status tracking
- **Person grouping** — HDBSCAN clustering auto-groups detected faces into persons
- **Person management** — Name persons, merge duplicates, view all photos of a person
- **Thumbnails** — Face thumbnails shown in person grid with age/gender overlay (when `INCLUDE_SENSITIVE_ATTRIBUTES=true`)
- **Photo detail overlay** — Face bounding boxes highlighted on photo with gold border for the current person; click any face to see person details
- **Similarity search** — Upload a photo to find all matching faces across the library

### Sharing
- **Share with users** — Share individual photos with other registered users (view permission)
- **Public share links** — Generate token-authenticated public links with optional expiration
- **Standalone viewer** — Public share page shows photo with metadata on a glass-themed layout

### Explore & Memories
- **Map explore** — Leaflet map with photo markers and heatmap overlay for geotagged photos
- **Memories** — "On this day" and "6 months ago" feeds based on photo timestamps

### Analytics
- **Storage usage** — Bar chart of per-user storage consumption
- **Timeline** — Monthly photo upload volume chart
- **File types** — MIME type distribution (JPG, PNG, MP4, etc.)
- **Camera models** — EXIF-derived camera model frequency table
- **Metadata stats** — Average resolution, file size trends

### User Features
- **Shield auth** — Registration, login, magic-link, password reset
- **Auth tokens** — Generate 8-character tokens for Android app authentication; QR code display for easy scanning
- **Profile & preferences** — Avatar, display name, theme selection (light/dark/solarized/grey), biometric unlock toggle
- **Data export** — ZIP download of all user photos and metadata
- **Account deletion** — Self-service account deletion with all data removed (including ML face data)

### Superuser Administration Console
- **Dashboard Overview** — Live status monitoring of standard/thumbnails directory usage, MySQL records, and FastAPI ML service health checks.
- **Dynamic Configs** — Adjust registrations options, default storage limits, and manage user roles (Standard vs Superadmin).
- **Storage & Disk Management** — Inspect uploads vs thumbnails footprints (both count and size) and configure trash purge policies.
- **SMTP Mail Configuration** — Manage mail credentials, dispatch test emails, view sent logs with tracking IDs (`CP-[HEX]`), and list trigger events.
- **ML Configuration** — Tune RetinaFace detection thresholds, swap model packs (Buffalo-L, M, S, SC), and trigger HDBSCAN clustering or vector space resets.
- **System Task Scheduler** — Modify execution schedules (via cron expressions) directly from the dashboard and inspect run logs database (`cron_logs`).

### System Logging Tables
- `sys_cron_logs` — Tracks background executions of CLI Spark commands (`trash:purge`, `ml:cluster`, `storage:clean-temp`), recording runtime statuses, outputs, execution durations, and run times.
- `sys_email_logs` — Records all outgoing SMTP emails, tracking subjects, recipients, execution times, success statuses, debug transaction trails, and unique tracking IDs.
- `sys_security_logs` — Logs security events such as login attempts, token creations, and administrative updates, storing IP address, user agent, action type, status, and custom details payload via `audit_helper.php`.

### Themes
- **5 themes** — Auto (OS), Light (default), Dark, Solarized, Grey; persisted in the settings database and `localStorage`; toggle via sidebar

---

## Superuser Default Account
For administrative overrides, a default superuser account is seeded automatically:
*   **Username:** `superadmin`
*   **Email:** `superadmin@eavesdroid.com`
*   **Password:** `SuperAdmin@2024!`
*   *Note:* Accounts in the `superadmin` group are automatically redirected to the `/admin/home` dashboard upon login.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | CodeIgniter 4 (with Shield 1.2 auth) |
| Language | PHP 8.3 |
| Database | MySQL 8.4 (via MySQLi driver) |
| Web server | Apache 2.4 (mod_rewrite) |
| Container | Docker + Compose |
| Frontend | Bootstrap 5.3, jQuery, Fabric.js, Chart.js, Leaflet, Dropzone.js |
| Face ML proxy | cURL to ML Chege Photos (FastAPI) at `http://ml-chege-photos:8000` |

---

## Prerequisites

- Docker ≥ 24.0
- Docker Compose ≥ 2.20
- A shared Docker network `hosts-shared-network` (created once via `docker network create hosts-shared-network`)

---

## Installation & Setup

### Docker (recommended)

```bash
# Build and start all services
docker compose up --build -d

# Or run only the web app + its DB (without ML):
docker compose up --build -d chege-photos mysql
```

The Compose file defines three services:

| Service | Container name | Host port | Purpose |
|---|---|---|---|
| `chege-photos` | `chege-photos-webapp` | 9005 | PHP 8.3 Apache web app |
| `mysql` | `shared-mysql` | 9306 | MySQL 8.4 database |
| `phpmyadmin` | `shared-phpmyadmin` | 9000 | phpMyAdmin (dev convenience) |

On first run, the entrypoint script runs database migrations automatically and boots the internal container `cron` service. This daemon dynamically executes the master scheduler (`cron:run`) every minute based on the settings configured via the Admin Console.

The ML face recognition service is an optional sibling dependency — the web app works without it (face features are simply unavailable).

### Local / Non-Docker

```bash
# Prerequisites: PHP 8.1+, composer, MySQL 8.4
composer install
cp .env.example .env
# Edit .env with your database and server settings
php spark migrate --all
php spark serve
```

### Environment

```bash
cp .env.example .env
```

Edit key values:

```
app.baseURL = 'http://your-server:9005/'
database.default.hostname = 'mysql'
database.default.database = 'db_chege_photos'
```

### ML Service Integration (optional)

To enable face recognition, deploy the [ML Chege Photos](https://github.com/niccher/Chege-Photos-ML) service alongside and ensure the web app's `Faces` controller can reach `http://ml-chege-photos:8000` (the default in the Docker network).

---

## Database Configuration

### `tbl_photos` — one row per uploaded photo

| Column | Type | Notes |
|---|---|---|
| `id` | INT (PK) | Auto-increment |
| `user_id` | INT (FK → `users.id`) | Owner |
| `filename` | VARCHAR(255) | Original filename |
| `path` | VARCHAR(500) | Relative path in `public/uploads/` |
| `thumbnail_path` | VARCHAR(500) (nullable) | Thumbnail path |
| `taken_at` | DATETIME (nullable) | EXIF capture timestamp |
| `width`, `height` | INT (nullable) | Image dimensions |
| `size` | BIGINT (nullable) | File size in bytes |
| `mime_type` | VARCHAR(100) (nullable) | e.g. `image/jpeg` |
| `latitude`, `longitude` | VARCHAR (nullable) | GPS coordinates |
| `exif_data` | TEXT (nullable) | Full EXIF JSON |
| `file_hash` | VARCHAR(64) (nullable) | SHA-256 for dedup |
| `is_archived` | TINYINT(1) | Default 0 |
| `is_favorite` | TINYINT(1) | Default 0 |
| `deleted_at` | DATETIME (nullable) | Soft delete timestamp |

### `tbl_face_encoding` — one row per detected face (shared with ML service)

See [ML Chege Photos README](https://github.com/niccher/Chege-Photos-ML#database-configuration) for schema.

### `tbl_photo_tags` — tags generated via object detection

| Column | Type | Notes |
|---|---|---|
| `id` | INT (PK) | Auto-increment |
| `photo_id` | INT (FK → `tbl_photos.id`) | Cascades on photo deletion |
| `tag` | VARCHAR(100) | Label generated by ML object detection |
| `confidence` | FLOAT | Confidence level score (0.0 to 1.0) |
| `created_at` | DATETIME | Timestamp of tag creation |

### Other tables

| Table | Purpose |
|---|---|
| `tbl_person` | Auto-discovered persons via HDBSCAN clustering |
| `tbl_albums` | Manual + smart album definitions |
| `tbl_album_photos` | Many-to-many pivot (album ↔ photo) |
| `tbl_photo_shares` | Photo sharing between users |
| `tbl_shared_links` | Token-authenticated public share links |
| `tbl_auth_tokens` | 8-char tokens for Android app authentication (with configurable token `scopes`) |
| `sys_security_logs` | Logs system security audits and admin/auth events |
| `tbl_photo_scan` | ML scan status per photo |
| `tbl_scan_job` | Batch scan job progress |
| `tbl_face_cluster` | Cluster centroid references and lineage |
| `tbl_face_annotation` | Face classification manual corrections & lineage |
| `tbl_photo_tags` | Object detection tags associated with photos |

> **Note:** All tables prefixed with `tbl_` are now created and managed directly via the web app's database migrations. Local model structures in the ML service map to these shared schemas. Log tables are prefixed with `sys_`.

---

## Usage / Routes

### Web UI Routes

| Method | Path | Description |
|---|---|---|
| `GET` | `/` | Photo grid (paginated, searchable) |
| `GET` | `/faces` | Face recognition dashboard (persons grid) |
| `GET` | `/faces/person/{id}` | All photos of a specific person |
| `GET` | `/faces/photo/{id}` | Photo detail with face bounding box overlays |
| `GET` | `/explore` | Map explore with Leaflet + heatmap |
| `GET` | `/memories` | On-this-day and 6-months-ago feeds |
| `GET` | `/albums` | Album list |
| `GET` | `/albums/{id}` | Album detail view |
| `GET` | `/archive` | Archived photos |
| `GET` | `/trash` | Soft-deleted photos (60-day retention) |
| `GET` | `/analytics` | Charts: storage, timeline, file types, cameras |
| `GET` | `/sharing` | Photo sharing management |
| `GET` | `/settings` | Profile, security, preferences, storage, ML stats, export, delete |
| `GET` | `/settings/tokens` | Auth token management |
| `POST` | `/upload` | AJAX multi-file upload (Dropzone) |
| `GET` | `/s/{token}` | Public shared photo view |

### Face API Endpoints (web → ML proxy + local queries)

| Method | Path | Description |
|---|---|---|
| `POST` | `api/faces/scan/{id}` | Scan a single photo for faces (ML proxy) |
| `POST` | `api/faces/scan-all` | Start batch scan of all photos |
| `POST` | `api/faces/cluster` | Run HDBSCAN clustering |
| `POST` | `api/faces/reset` | Reset all ML scan data |
| `POST` | `api/faces/force-scan` | Force re-scan of all photos |
| `GET` | `api/faces/scan-job/{id}` | Poll batch scan job status |
| `POST` | `api/faces/search` | Upload photo for face similarity search |
| `GET` | `api/faces/{photoId}` | List faces for a photo |
| `GET` | `api/faces/persons` | List all persons |
| `GET` | `api/faces/unassigned` | List unassigned faces |
| `GET` | `api/faces/by-person/{id}` | List photos containing a person |
| `POST` | `api/faces/persons/name/{id}` | Name a person |
| `POST` | `api/faces/persons/merge` | Merge two persons |

### Android API Endpoints (token auth)

| Method | Path | Description |
|---|---|---|
| `POST` | `api/login` | Email/password authentication |
| `POST` | `api/auth-with-token` | Token-based device auth |
| `GET` | `api/photos` | List remote photos |
| `GET` | `api/albums` | List albums |
| `GET` | `api/albums/{id}/photos` | Photos in an album |
| `GET` | `api/memories` | Memories feed |
| `GET` | `api/favorites` | Favorited photos |
| `GET` | `api/archive` | Archived photos |
| `GET` | `api/trash` | Trashed photos |
| `GET` | `api/explore` | Explore feed |

---

## Project Structure

```
Chege Photos WebApp/
├── app/
│   ├── Config/
│   │   ├── Routes.php            # All web + API route definitions
│   │   ├── App.php               # App config (baseURL, encoding, timezone)
│   │   ├── Database.php          # MySQLi connection config
│   │   ├── Filters.php           # Auth filter aliases (session, tokens, chain)
│   │   └── Auth.php              # Shield auth config
│   ├── Controllers/
│   │   ├── Photos.php            # Photo grid, upload, view, explore, albums, etc.
│   │   ├── Faces.php             # Face dashboard, scan, cluster, search (ML proxy)
│   │   ├── Settings.php          # Profile, security, preferences, export, delete
│   │   ├── Home.php              # Welcome page
│   │   ├── Tokens.php            # Auth token generation/revocation
│   │   └── Api/
│   │       ├── Auth.php          # Login + token auth endpoints
│   │       ├── ApiController.php # Android REST API (photos, albums, etc.)
│   │       └── TestController.php # API test endpoint
│   ├── Models/
│   │   ├── PhotoModel.php        # Photos ORM (soft deletes, search, counts)
│   │   ├── AlbumModel.php        # Albums + smart album rules
│   │   ├── AlbumPhotoModel.php   # Album↔photo pivot
│   │   ├── FaceEncodingModel.php # Face encoding queries
│   │   ├── PersonModel.php       # Person queries with face count
│   │   ├── PhotoShareModel.php   # Photo sharing between users
│   │   ├── SharedLinkModel.php   # Public share links
│   │   └── AuthTokenModel.php    # Android auth tokens
│   ├── Views/
│   │   ├── layouts/main.php      # Bootstrap 5.3 layout, sidebar, theme toggle
│   │   ├── photos/
│   │   │   ├── index.php         # Photo grid, search, upload
│   │   │   ├── faces.php         # Persons grid with thumbnails
│   │   │   ├── faces_person.php  # Photos of a single person
│   │   │   ├── faces_photo.php   # Photo detail with face overlays + modals
│   │   │   ├── albums.php        # Album list + CRUD
│   │   │   ├── album_detail.php  # Album contents
│   │   │   ├── explore.php       # Leaflet map + heatmap
│   │   │   ├── analytics.php     # Charts (Chart.js)
│   │   │   ├── memories.php      # On-this-day feeds
│   │   │   ├── archive.php       # Archived photos
│   │   │   ├── trash.php         # Soft-deleted photos
│   │   │   ├── settings.php      # Profiles, security, data management
│   │   │   └── view_shared.php   # Public share viewer
│   │   └── auth/
│   │       ├── layout.php        # Glass-themed auth layout
│   │       ├── login.php         # Login form
│   │       └── register.php      # Registration form
│   ├── Database/Migrations/      # 21 migrations (Shield + app)
│   └── Libraries/
│       └── SmartAlbumRules.php   # Rule engine for smart albums
├── public/
│   ├── js/app.js                 # Infinite scroll, lightbox, slideshow, face UI
│   ├── css/style.css             # Theme variables + base styles
│   ├── css/photos.css            # Full design system (~26k chars)
│   └── uploads/                  # User-uploaded photos (mounted by ML service)
├── Dockerfile                    # PHP 8.3 Apache with GD, Intl, MySQLi
├── docker-compose.yml            # Webapp + MySQL 8.4 + phpMyAdmin
├── .env                          # Runtime config (git-ignored)
└── composer.json                 # PHP dependencies
```

---

## Configuration / Environment Variables

| Variable | Default | Description |
|---|---|---|
| `app.baseURL` | `http://localhost:8080/` | Application base URL (set per deployment, e.g. `http://192.168.100.80:9005/`) |
| `database.default.hostname` | `mysql` | MySQL hostname (Docker service name) |
| `database.default.database` | `db_chege_photos` | MySQL database name |
| `database.default.username` | `root` | MySQL user |
| `database.default.password` | `root_password` | MySQL password |
| `encryption.key` | *(auto-generated)* | CI4 encryption key (hex2bin) |
| `AUTH_SEND_EMAIL_ON_REGISTER` | `false` | Disable registration email |
| `email.*` | *(Mailtrap defaults)* | SMTP for transaction emails |
| `CI_ENVIRONMENT` | `development` | CodeIgniter environment mode |

---

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -m 'Add feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Open a Pull Request

---

## License

MIT License. See `LICENSE` file in this repository.

---

## Support / Contact

For issues and feature requests, please open an issue on the [GitHub repository](https://github.com/niccher/Chege-Photos-WebApp/issues).
