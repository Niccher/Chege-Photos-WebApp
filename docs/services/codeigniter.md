# CodeIgniter 4 Service Handbook

Engineering guide for developers working on the CodeIgniter 4 WebApp codebase.

---

## 1. Directory Organization

```
app/
├── Config/                   # Framework configuration (Routes.php, Database.php, Filters.php)
├── Controllers/
│   ├── Admin.php             # Superadmin dashboard, metrics, task scheduler, batch rescan
│   ├── Api/
│   │   └── ApiController.php # Mobile REST endpoints with token authentication
│   ├── Faces.php             # Face recognition UI, person naming, and clustering proxy
│   ├── MediaFallback.php     # On-demand media hydration from Google Cloud Storage
│   ├── Photos.php            # Photo grid, upload, memories, explore, albums, trash
│   └── Settings.php          # User profile, security, and storage quota management
├── Models/
│   ├── PhotoModel.php        # Primary media ORM model with multi-tenant scoping
│   ├── PersonModel.php       # Clustered identities with face counts
│   ├── AlbumModel.php        # Albums and smart album rule evaluation
│   └── AuthTokenModel.php    # Mobile device authentication tokens
├── Views/                    # Blade-like PHP views styled with Bootstrap 5
├── Commands/                 # Spark CLI tasks (CronRun, CloudSync, TrashPurge, etc.)
└── Services/                 # GcpStorageService and external API clients
```

---

## 2. Available Spark CLI Commands

Run these inside the container or on a local PHP 8.3 installation:

```bash
# Execute master task scheduler
php spark cron:run

# Synchronize local media with Google Cloud Storage bucket
php spark cloud:sync --direction=up --limit=100

# Process queued large media asynchronously (SHA-256 calculation & cloud upload)
php spark media:process-pending --id=105

# Permanently purge soft-deleted photos older than configured retention (default: 60 days)
php spark trash:purge

# Trigger HDBSCAN face clustering across unassigned face embeddings
php spark ml:cluster

# Batch re-scan library for missing face embeddings, tags, or CLIP vectors
php spark ml:sweep --limit=200

# Validate synchronization between MySQL face records and Qdrant points
php spark ml:sync-qdrant

# Prune local cache of media verified safely preserved in GCP
php spark storage:prune-cache --max-age=30

# Clean temporary abandoned upload chunks
php spark storage:clean-temp --hours=24

# Batch generate thumbnails for legacy photos
php spark storage:generate-thumbs

# Migrate storage structure to users/{id}/{YYYY}/{MM} hierarchy
php spark storage:migrate-structure
```

---

## 3. Recipes for Common Changes

### Recipe A: Adding a New Web Route & Controller Action
1. Open `app/Config/Routes.php` and define the route:
   ```php
   $routes->get('my-feature', 'Photos::myFeature', ['filter' => 'session']);
   ```
2. Implement the controller method in `app/Controllers/Photos.php`:
   ```php
   public function myFeature()
   {
       $photoModel = new \App\Models\PhotoModel();
       $data['photos'] = $photoModel->where('user_id', auth()->id())->findAll();
       return view('photos/my_feature', $data);
   }
   ```
3. Create the view template in `app/Views/photos/my_feature.php`.

### Recipe B: Adding a New Database Migration
1. Generate migration:
   ```bash
   php spark make:migration AddCustomFieldToPhotosTable
   ```
2. Add column definition in the generated migration file:
   ```php
   $this->forge->addColumn('tbl_photos', [
       'custom_field' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true]
   ]);
   ```
3. Apply migration:
   ```bash
   php spark migrate
   ```
4. Update `$allowedFields` in `app/Models/PhotoModel.php`.
