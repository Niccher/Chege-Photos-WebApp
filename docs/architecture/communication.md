# Inter-Service Communication & Protocols

Protocols, communication tables, sequence diagrams, and failure strategies connecting Chege Photos WebApp with databases, microservices, and mobile clients.

---

## 1. Inter-Service Protocol Matrix

| Source | Target | Transport | Authentication | Base URL (Dev) | Timeout & Fallback Strategy |
|---|---|---|---|---|---|
| **Browser** | **WebApp** | HTTPS + Cookie | Shield Session Cookie | `http://localhost:9005` | CSRF token validation; 302 redirect to `/login` if unauthenticated. |
| **Android** | **WebApp** | HTTPS / REST | Bearer Token (`tbl_auth_tokens`) | `http://10.0.2.2:9005` | Returns 401 JSON; app queues actions locally in Room database. |
| **WebApp** | **MySQL** | TCP 3306 | MySQL User/Password | `mysql:3306` | Connection wait loop in `entrypoint.sh` with 30 retries. |
| **WebApp** | **ML Service** | HTTP/1.1 (cURL) | Header `X-API-KEY` | `http://ml-chege-photos:9051` | Non-blocking async (`100ms` timeout); upload succeeds if ML down. |
| **ML Service** | **WebApp** | HTTP/1.1 | Session / Internal HMAC | `http://chege-photos:80` | On-demand fallback hydration streams missing images from WebApp. |
| **WebApp** | **Google Cloud** | HTTPS 443 | Service Account OAuth2 Bearer | `storage.googleapis.com` | Photos flagged `gcp_synced=0` and retried via `php spark cloud:sync`. |

---

## 2. Authentication & Session Sequences

### Web User Login Sequence
```mermaid
sequenceDiagram
  autonumber
  actor User as Browser
  participant CI as CodeIgniter 4 (Shield)
  participant DB as MySQL (users)

  User->>CI: POST /login {email, password, csrf_token}
  CI->>CI: Validate CSRF Token
  CI->>DB: Query user by email & verify Argon2/Bcrypt hash
  DB-->>CI: User Record & Roles
  CI->>CI: Initialize Secure Session & Set Cookie
  CI-->>User: 302 Redirect to / (or /admin/home for Superadmin)
```

### Android Mobile Token Pairing Sequence
```mermaid
sequenceDiagram
  autonumber
  actor User as Photographer
  participant Web as WebApp (Settings::tokens)
  participant App as Android Client
  participant API as ApiController

  User->>Web: Generate Device Token in Web Console
  Web->>Web: Create random 8-character token & render QR Code
  User->>App: Scan QR Code with Android Camera
  App->>API: POST /api/v1/auth-with-token {token: "ABC12345"}
  API->>API: Verify token exists & active in tbl_auth_tokens
  API-->>App: 200 OK {status: "success", user: {...}}
  App->>App: Persist token securely in EncryptedSharedPreferences
```

---

## 3. Photo Upload & Asynchronous AI Scanning Data Flow

```mermaid
sequenceDiagram
    autonumber
    actor Client as User / Android Client
    participant Web as WebApp (Photos::upload)
    participant Disk as Local File Storage
    participant DB as MySQL (tbl_photos)
    participant GCP as Google Cloud Storage
    participant ML as ML Service (FastAPI)
    participant Qdrant as Qdrant Vector DB

    Client->>Web: POST /upload (Multipart Image / ContentUriRequestBody)
    Web->>Web: Validate MIME, Quota & Extension
    Web->>Web: Calculate SHA-256 Hash (Check Duplicates)
    Web->>Disk: Write file to public/uploads/users/{id}/{YYYY}/{MM}/
    Web->>Disk: Generate Thumbnails (GD/Imagick)
    Web->>DB: INSERT INTO tbl_photos (path, hash, EXIF)
    Web-->>Client: 200 OK {"status": "success", "id": 105}
    
    par Background Cloud Mirroring
        Web->>GCP: Upload Stream to GCS Bucket
        Web->>DB: UPDATE tbl_photos SET gcp_synced = 1
    and Non-Blocking AI Scan
        Web-)ML: POST /api/v1/faces/encode (photo_id, webapp_url)
        ML->>Disk: Read Image Bytes
        ML->>ML: InsightFace (Detect BBox, Age, Gender, 512-d ArcFace)
        ML->>ML: YOLOv8 (Detect Objects: "dog", "car", "beach")
        ML->>ML: CLIP (Generate 512-d Visual Embedding)
        ML->>DB: INSERT tbl_face_encodings, tbl_photo_tags
        ML->>Qdrant: Upsert Points (Vectors + photo_id Payload)
        ML->>DB: UPDATE tbl_photos SET scanned_face=1, scanned_clip=1
    end
```

---

## 4. Android Emulator Networking

```mermaid
flowchart TB
  E[Android Emulator]
  H[Host Development Machine]
  W[WebApp Container :9005]
  
  E -->|"http://10.0.2.2:9005"| H
  H -->|"Port Forward 9005->80"| W
```
