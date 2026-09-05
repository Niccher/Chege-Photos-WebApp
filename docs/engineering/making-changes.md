# Making Changes & Definition of Done

Engineering workflow, task impact matrix, and Definition of Done for modifying Chege Photos WebApp.

---

## 1. Task Impact Matrix

| If you want to… | Touch these files |
|---|---|
| **Add a new web page** | `app/Config/Routes.php`, `app/Controllers/Photos.php`, `app/Views/photos/` |
| **Add a mobile REST endpoint** | `app/Config/Routes.php`, `app/Controllers/Api/ApiController.php`, Android `PhotoService.kt` |
| **Modify the database schema** | `app/Database/Migrations/`, `app/Models/PhotoModel.php` |
| **Change photo upload handling** | `app/Controllers/Photos.php` (`upload()`), `app/Services/GcpStorageService.php` |
| **Adjust ML scan or face logic** | `app/Controllers/Faces.php`, `app/Helpers/ml_helper.php`, ML microservice |
| **Update carousel or viewer styling** | `public/css/photos.css`, `public/js/app.js`, `app/Views/layouts/main.php` |

---

## 2. Definition of Done (DoD) for Contract Changes

Before merging any change affecting API routes, database columns, or cross-service payloads, verify the following checklist:

- [ ] **Code**: Implemented in owning service with multi-tenant row-level security (`where('user_id', auth()->id())`).
- [ ] **Tests**: Verified with `php -l` and PHPUnit test suite.
- [ ] **Environment**: `.env.example` updated if a new variable was introduced (no real secrets).
- [ ] **Documentation**: Updated `docs/api/contract.md` or `docs/architecture/communication.md`.
- [ ] **Consumers**: Android client DTOs and API clients updated or backwards compatibility preserved.
