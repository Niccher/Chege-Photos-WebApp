# REST API Contract & Endpoints

Contract specification for mobile clients and token-authenticated API consumers.

---

## 1. Authentication & Headers

All requests to `/api/v1/...` require one of the following authentication methods:
- **Device Bearer Token**: Pass header `Authorization: Bearer <8-char-token>` generated via WebApp Settings.
- **Microservice Key**: Microservice-to-microservice calls pass `X-API-KEY: <secret_key>`.

### Error Response Convention
All API error responses adhere to this standard JSON shape:
```json
{
  "status": "error",
  "message": "Human readable explanation of the failure."
}
```

---

## 2. Core Photo Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/photos` | Paginated list of remote photos for the authenticated user. |
| `POST` | `/api/v1/upload` | Multipart file upload endpoint. Supports streaming `ContentUriRequestBody`. |
| `POST` | `/api/v1/photos/check-hashes` | Batch verify up to 500 SHA-256 hashes to skip redundant uploads. |
| `GET` | `/api/v1/photos/{id}` | Detailed metadata for a specific photo. |
| `POST` | `/api/v1/photos/{id}/favorite` | Toggles favorite status on a photo. |
| `POST` | `/api/v1/photos/{id}/archive` | Toggles archive status on a photo. |
| `DELETE` | `/api/v1/photos/{id}` | Moves photo to trash (soft-delete). |

---

## 3. Discovery & Milestone Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/memories` | Returns On This Day memories. Supports `?date=YYYY-MM-DD` query. |
| `GET` | `/api/v1/explore` | Multi-category feed returning photos with GPS, tags, or faces. |
| `GET` | `/api/v1/favorites` | Returns favorited photos for quick filtering. |
| `GET` | `/api/v1/albums` | Lists user albums with cover images and photo counts. |
