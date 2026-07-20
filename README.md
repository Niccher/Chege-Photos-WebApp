# Chege Photos Web App

A CodeIgniter 4 PHP web application for photo management with ML-powered face recognition. Acts as the primary UI and API gateway within the Chege Photos ecosystem, serving a web frontend and Android mobile client.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, CodeIgniter 4 |
| Database | MySQL 8.4 |
| Auth | CodeIgniter Shield |
| Frontend | Bootstrap 5, Chart.js, jQuery |
| Containerization | Docker & Docker Compose |
| ML Service | Companion Python ML service (face detection, embedding, clustering) |
| Vector DB | Qdrant (stores face embeddings for similarity search) |

## Architecture

The Chege Photos ecosystem has four components:

```
Android App  ──┐
                ├──> Chege Photos WebApp (CI4) ──> MySQL
Web Browser  ──┘         │
                         v
               ML Service (Python) ──> Qdrant (vector DB)
```

- **Chege Photos WebApp** — The CI4 PHP application. Serves the web UI and exposes REST API endpoints consumed by the Android app. Handles uploads, EXIF extraction, thumbnail generation, album management, sharing, and search.
- **Android App** — Mobile client that uploads photos via the API and can trigger face scanning.
- **ML Service** — A standalone Python service (`ml-chege-photos:8000`) that performs face detection, extracts 512-dimensional embeddings, stores them in Qdrant, and runs HDBSCAN clustering.
- **Qdrant** — Vector database for efficient similarity search across face embeddings.

The webapp communicates with the ML service via HTTP POST requests (proxied through `Faces` controller methods). It does not call Qdrant directly — all vector operations go through the ML service.

## Why ML over Heuristics

Traditional photo grouping relies on EXIF metadata (camera model, timestamp, GPS). ML-based face recognition is superior because:

- **Varied conditions** — Faces are detected reliably across different poses, lighting conditions, and occlusions (sunglasses, masks, angles). EXIF-based heuristics cannot identify *who* is in a photo.
- **Consistent embeddings** — Each detected face is converted to a 512-dimensional vector embedding. Cosine distance between embeddings is a robust similarity metric regardless of camera or settings.
- **Automatic clustering** — HDBSCAN clustering groups embeddings into persons without manual tagging. The same person across different years, cameras, and locations is grouped automatically.
- **Cross-device identification** — A person is recognized across photos taken with different phones, DSLRs, or scanned prints. EXIF data differs per device and cannot link subjects across cameras.
- **Search by face** — Upload a reference photo and search for all occurrences of that person across the entire library.

## Docker Containers

The `docker-compose.yml` defines three services:

| Container | Image | Purpose |
|---|---|---|
| `chege-photos-webapp` | `chege-photos-webapp:latest` (custom) | Apache + PHP 8.3 serving the CI4 app on port 9005 |
| `shared-mysql` | `mysql:8.4` | MySQL database on port 9306 |
| `shared-phpmyadmin` | `phpmyadmin:latest` | Database management UI on port 9000 |

The webapp container:
- Extends `php:8.3-apache` with GD, intl, mysqli, pdo_mysql, zip, and exif extensions.
- Sets upload limits to 512 MB, execution timeout to 300 seconds.
- Configures the Apache document root to `/var/www/html/public`.
- Runs `entrypoint.sh` on boot, which waits for MySQL then executes `php spark migrate --all`.
- Mounts the project root as a volume for live code updates.

## Usefulness of Docker

- **Dev/prod parity** — The same environment runs on any machine with Docker.
- **One-command startup** — `docker compose up --build -d` starts all services.
- **Service isolation** — Each component runs in its own container with explicit network dependencies.
- **Automated migrations** — The entrypoint script runs pending database migrations on every boot.
- **No host installation** — No need to install PHP, Composer, or MySQL locally.

## Setup

### Prerequisites

- Docker & Docker Compose
- A shared `.env` file (details below)

### Build and Run

```bash
docker compose up --build -d
```

This builds the webapp image (if not cached), pulls MySQL and phpMyAdmin, creates a shared Docker network, and starts all three containers. The entrypoint waits for MySQL to accept connections, runs `php spark migrate --all`, then starts Apache.

### Access

| Service | URL |
|---|---|
| Web App | http://localhost:9005 |
| phpMyAdmin | http://localhost:9000 |

## Key Features

- **Photo upload** — Drag-and-drop or file picker upload with automatic face scan trigger. 512 MB max file size. Duplicate detection via MD5 hash.
- **Gallery** — Grid view with pagination (100 per page). Infinite scroll support via AJAX.
- **Search** — Search by filename, EXIF data, or date.
- **Favorites** — Toggle favorite status on any photo. Filter to show only favorites.
- **Archive / Trash** — Archive photos to hide from the main gallery. Soft-delete to trash with permanent delete option.
- **Albums** — Create manual albums (add photos individually) or smart albums (dynamically populated by rules: date range, camera model, GPS-tagged, favorites only, image/video type).
- **Sharing** — Two mechanisms:
  - *Public links* — Generate a unique token-based URL (`/s/{token}`) for anyone to view a photo.
  - *Internal shares* — Share a photo with another registered user by their ID.
- **Faces page** — View all detected persons with face thumbnails. Click a person to see all photos containing them. Name persons, merge duplicate clusters, rescan unprocessed photos.
- **Explore** — Map view of photos with GPS coordinates (latitude/longitude).
- **Memories** — "On this day" and "6 months ago" photo groupings.
- **Analytics** — Storage usage, photo/video breakdown, monthly activity chart (Chart.js), hourly activity, camera model statistics, GPS-tagged count, yearly growth, sharing stats.
- **Settings** — Profile editing, avatar upload, password change, theme selection (auto/light/dark/solarized/grey), ML panel stats, data export (ZIP with metadata JSON), full data clear, account deletion.
- **EXIF extraction** — Reads DateTimeOriginal, GPS coordinates, camera make/model, exposure settings, flash, focal length, ISO, and more. Stores as JSON in `exif_data`.
- **Token auth** — Generate API tokens for the Android app. QR code display for easy mobile pairing.

## Face Recognition Pipeline

```
1. Upload  ──>  2. ML Service (POST /api/v1/faces/encode)
                     │
                     ├── Face detection (RetinaFace / MTCNN)
                     ├── Landmark detection (5-point)
                     ├── Embedding extraction (512-d vector)
                     └── Store in Qdrant, save metadata to MySQL face_encoding table
                         │
                    3. Clustering (POST /api/v1/faces/cluster)
                         │
                         ├── HDBSCAN on all embeddings in Qdrant
                         ├── Create/update person records
                         └── Assign person_id to face_encoding rows
                             │
                        4. Web UI: Faces page displays persons with count + thumbnail
                           Click person → /faces/person/{id} → all photos for that person
```

- **Auto-scan on upload** — After a photo uploads, the webapp fires a non-blocking HTTP request to the ML service to encode faces (`triggerFaceScanAsync` uses a 100 ms timeout CURL call).
- **Bulk scan** — The Faces page has a "Rescan All" button that iterates over all unprocessed photos.
- **Clustering** — Triggered manually from the ML settings panel. HDBSCAN groups similar embeddings into `person` records. The same person across different photos gets the same `person_id`.
- **Merge persons** — Users can merge two person clusters via the API if HDBSCAN splits the same person.
- **Name persons** — Assign a human-readable name to a person cluster.

## Routes / Pages

| Route | Page | Description |
|---|---|---|
| `/` | Photos | Main gallery grid |
| `/upload` | — | POST endpoint for photo upload |
| `/scan` | — | Scan filesystem for new photos |
| `/backfill-exif` | — | Re-extract EXIF for photos missing metadata |
| `/faces` | Faces | All detected persons with face thumbnails |
| `/faces/person/{id}` | Person Photos | All photos containing a specific person |
| `/faces/photo/{id}` | Photo Faces | Face bounding boxes on a single photo |
| `/explore` | Explore | Map of GPS-tagged photos |
| `/albums` | Albums | Album list with thumbnails |
| `/albums/{id}` | Album | Photos in a specific album |
| `/favorites` | Favorites | Favorite photos |
| `/memories` | Memories | "On this day" and "6 months ago" |
| `/archive` | Archive | Archived photos |
| `/trash` | Trash | Soft-deleted photos |
| `/sharing` | Sharing | Public links and internal shares |
| `/analytics` | Analytics | Storage and activity charts |
| `/settings` | Settings | Profile, security, preferences, ML settings |
| `/s/{token}` | Shared Photo | Public shared photo view (no auth required) |
| `/api/*` | — | REST API endpoints for Android app |

### API Endpoints (under `/api`)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/photos` | List photos |
| GET | `/api/albums` | List albums |
| GET | `/api/albums/{id}/photos` | Photos in album |
| POST | `/api/upload` | Upload a photo |
| GET | `/api/memories` | Memories feed |
| GET | `/api/favorites` | Favorites list |
| GET | `/api/archive` | Archive list |
| GET | `/api/trash` | Trash list |
| GET | `/api/explore` | GPS-tagged photos |
| GET | `/api/faces/{id}` | Faces for a photo |
| GET | `/api/faces/persons` | All persons |
| GET | `/api/faces/unassigned` | Unassigned faces |
| GET | `/api/faces/by-person/{id}` | Photos for a person |
| POST | `/api/faces/scan/{id}` | Scan a photo for faces |
| POST | `/api/faces/scan-all` | Scan all unprocessed photos |
| POST | `/api/faces/search` | Search by face (upload reference image) |
| POST | `/api/faces/cluster` | Run HDBSCAN clustering |
| POST | `/api/faces/persons/name/{id}` | Name a person |
| POST | `/api/faces/persons/merge` | Merge two persons |
| POST | `/api/faces/bulk-scan` | Scan specific photo IDs |

## Database

Key tables managed by the webapp (excluding Shield auth tables):

### `photos`
| Column | Type | Description |
|---|---|---|
| id | INT UNSIGNED PK | Auto-increment |
| user_id | INT UNSIGNED | Owner |
| device_id | VARCHAR | Optional device identifier |
| filename | VARCHAR(255) | Stored filename |
| path | VARCHAR(500) | Relative path under public/ |
| thumbnail_path | VARCHAR(500) | Relative thumbnail path |
| mime_type | VARCHAR(100) | image/jpeg, video/mp4, etc. |
| width / height | INT | Pixel dimensions |
| size | BIGINT | File size in bytes |
| file_hash | VARCHAR(32) | MD5 hash for dedup |
| taken_at | DATETIME | Photo capture time |
| latitude / longitude | DECIMAL | GPS coordinates |
| exif_data | TEXT | Full EXIF as JSON |
| is_favorite | BOOLEAN | Favorite flag |
| is_archived | BOOLEAN | Archive flag |
| deleted_at | DATETIME | Soft delete timestamp |

### `albums`
| Column | Type | Description |
|---|---|---|
| id | INT UNSIGNED PK | Auto-increment |
| user_id | INT UNSIGNED | Owner |
| name | VARCHAR(255) | Album name |
| description | TEXT | Optional |
| cover_photo_id | INT UNSIGNED | Cover photo reference |
| is_smart | TINYINT(1) | Whether rules-driven |
| smart_rules | TEXT | JSON rules config |

### `album_photos`
Pivot table: `album_id` + `photo_id` with `added_at` timestamp.

### `face_encoding`
| Column | Type | Description |
|---|---|---|
| id | INT PK | Auto-increment |
| photo_id | INT | Parent photo |
| person_id | INT | Assigned person (nullable) |
| qdrant_point_id | VARCHAR | Qdrant vector point ID |
| bbox_x/y/w/h | FLOAT | Face bounding box (normalized) |
| landmark_* | FLOAT | 5 facial landmarks |
| detection_score | FLOAT | Confidence score |
| face_image_path | VARCHAR | Cropped face image |
| age / gender | INT/VARCHAR | Optional attributes |

### `person`
| Column | Type | Description |
|---|---|---|
| id | INT PK | Auto-increment |
| name | VARCHAR | Human-readable name (nullable) |
| thumbnail_face_id | INT | Face used for thumbnail |
| cluster_label | INT | HDBSCAN cluster label |

### `shared_links`
| Column | Type | Description |
|---|---|---|
| id | INT PK | Auto-increment |
| photo_id | INT | Shared photo |
| access_token | VARCHAR(64) | Unique token for URL |
| expires_at | DATETIME | Optional expiration |

### `photo_shares`
| Column | Type | Description |
|---|---|---|
| id | INT PK | Auto-increment |
| photo_id | INT | Shared photo |
| shared_by | INT | Sender user ID |
| shared_with | INT | Recipient user ID |
| permission | VARCHAR(20) | `view` (default) |

### `settings`
Stores application settings per user context (theme, storage limit, etc.) via CodeIgniter Settings library.

## Port Mapping

| Container | Internal | Host |
|---|---|---|
| chege-photos-webapp | 80 | 9005 |
| shared-mysql | 3306 | 9306 |
| shared-phpmyadmin | 80 | 9000 |

## Environment Variables

The app reads from a shared `.env` file located at `../.env` (one level above the project root, shared across the Chege Photos ecosystem). Key variables:

| Variable | Description | Example |
|---|---|---|
| `CI_ENVIRONMENT` | CI4 environment mode | `development` or `production` |
| `app.baseURL` | Public-facing base URL | `http://192.168.1.212:9005/` |
| `database.default.hostname` | MySQL host | `mysql` |
| `database.default.database` | Database name | `db_chege_photos` |
| `database.default.username` | DB user | `root` |
| `database.default.password` | DB password | `root_password` |
| `database.default.port` | DB port | `3306` |
| `encryption.key` | CI4 encryption key | `hex2bin:...` |
| `email.*` | SMTP configuration | Mailtrap defaults |

For Docker, a local `.env` or `.env.docker` file in the project root overrides the shared one.

## Sidebar

The navigation sidebar (visible when authenticated) contains:

- **Photos** — Main gallery (count of non-archived photos)
- **Faces** — ML-detected persons page
- **Explore** — Map of GPS-tagged photos
- **Favorites** — Starred photos
- **Memories** — "On this day" and "6 months ago"
- **Albums** — User-created and smart albums (with album names listed below)
- **Archive** — Hidden/archived photos
- **Trash** — Soft-deleted photos
- **Settings** — Profile, security, preferences, ML panel
- **Sharing** — Public links and internal shares (count of shared items)

Each sidebar item shows a badge count (e.g., number of photos, albums, shared items) cached for 10 seconds.
