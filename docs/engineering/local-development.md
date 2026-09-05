# Bare-Metal Local Development

How to run and debug the CodeIgniter 4 WebApp natively on your workstation without Docker.

---

## 1. System Dependencies

Ensure the following tools and extensions are installed on your workstation:
- **PHP**: 8.2 or 8.3 with extensions:
  - `ext-intl`
  - `ext-mysqli`
  - `ext-pdo_mysql`
  - `ext-gd` (with FreeType and libjpeg)
  - `ext-zip`
  - `ext-exif`
  - `ext-curl`
  - `ext-mbstring`
- **Composer**: Version 2.6+
- **MySQL**: 8.0+ or MariaDB 10.6+

---

## 2. Setup Steps

```bash
# 1. Install PHP dependencies
composer install

# 2. Configure environment
cp .env.example .env

# 3. Edit .env with local database settings
#    database.default.hostname = localhost
#    database.default.database = db_chege_photos
#    database.default.username = your_user
#    database.default.password = your_password

# 4. Generate encryption key
php spark key:generate

# 5. Run migrations
php spark migrate --all

# 6. Seed default administrator
php spark db:seed SystemDefaultSeeder

# 7. Start local server
php spark serve --port 8080
```

Access the application at [http://localhost:8080](http://localhost:8080).
