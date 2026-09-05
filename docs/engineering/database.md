# Database Engineering & Migrations

Guidelines for managing MySQL database migrations, seeders, and multi-tenant query isolation.

---

## 1. Managing Migrations

Database tables are version-controlled via CodeIgniter migrations in `app/Database/Migrations/`.

```bash
# Run all pending migrations
php spark migrate --all

# Rollback last migration batch
php spark migrate:rollback

# Check current migration status
php spark migrate:status
```

---

## 2. Multi-Tenant Query Isolation

All models and custom queries must enforce tenant separation by scoping to `auth()->id()`.

### The Parenthesis / Operator Precedence Leak Rule
Never mix `where()` and `orWhere()` at the top-level. Wrap complex conditions inside `groupStart()` and `groupEnd()`:

```php
// SECURE PATTERN
$photos = $photoModel->where('user_id', $userId)
                     ->where('is_archived', false)
                     ->groupStart()
                         ->where("DATE_FORMAT(taken_at, '%m-%d') =", $today)
                         ->orWhere('DATE(taken_at) =', $sixMonthsAgo)
                     ->groupEnd()
                     ->findAll();
```

---

## 3. Seeders

Default system credentials and roles are provisioned by `SystemDefaultSeeder`:
```bash
php spark db:seed SystemDefaultSeeder
```
