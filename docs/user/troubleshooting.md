# Troubleshooting Guide (Operator & Runtime)

Solutions to common issues encountered when booting, running, and maintaining Chege Photos WebApp.

---

## 1. Port Already Allocated

**Symptom**: Docker fails to start with error:
`Bind for 0.0.0.0:9005 failed: port is already allocated`

**Cause**: Another container or local web server is running on port 9005.

**Solution**:
1. Identify the conflicting process:
   ```bash
   sudo lsof -i :9005
   ```
2. Either stop the existing process or change the published host port in `docker-compose.yml`:
   ```yaml
   ports:
     - "9010:80" # Change host port from 9005 to 9010
   ```
3. Update `app.baseURL` in `.env` to match the new port (`http://localhost:9010/`).

---

## 2. Container Boot Stalled on "Waiting for MySQL"

**Symptom**: WebApp container logs display repeated messages:
`MySQL not ready yet... retrying (1/30)`

**Cause**: The MySQL container is still initializing its database files, or database connection credentials in `.env` do not match MySQL.

**Solution**:
1. Check MySQL container logs:
   ```bash
   docker compose logs mysql
   ```
2. Verify that MySQL health check reports `healthy`:
   ```bash
   docker compose ps mysql
   ```
3. If running bare-metal, ensure the MySQL daemon is active:
   ```bash
   sudo systemctl status mysql
   ```

---

## 3. Photos Return 404 After Railway Container Redeployment

**Symptom**: Photos uploaded prior to a new git push or container redeploy fail to load on the website.

**Cause**: Railway provisions containers with ephemeral storage. Local disk files are wiped upon restart.

**Solution**:
1. Configure your Google Cloud Storage bucket credentials in the Railway service dashboard (`GCP_BUCKET` and `GCP_SERVICE_ACCOUNT_JSON`).
2. Ensure `app/Controllers/MediaFallback.php` is active. It automatically intercepts missing image requests, downloads the photo from your GCS bucket, and recreates the local cached file on disk.

---

## 4. Permission Denied on `writable/` Directory

**Symptom**: PHP throws an exception:
`CodeIgniter\Exceptions\FrameworkException: The directory "writable/cache" is not writable.`

**Cause**: File permissions were modified by host editing or root command execution.

**Solution**:
Inside the container, run:
```bash
docker compose exec chege-photos chmod -R 777 /var/www/html/writable /var/www/html/public/uploads /var/www/html/public/thumbnails
docker compose exec chege-photos chown -R www-data:www-data /var/www/html/writable /var/www/html/public/uploads /var/www/html/public/thumbnails
```

---

## 5. Android Emulator Cannot Reach Local WebApp

**Symptom**: Android client fails with `java.net.ConnectException: Failed to connect to localhost/127.0.0.1:9005`.

**Cause**: In the Android emulator, `localhost` refers to the emulator itself, not your host PC.

**Solution**:
Configure the Android app's base URL to `http://10.0.2.2:9005/`. On a physical phone connected over Wi-Fi, use your machine's local LAN IP address (e.g. `http://192.168.100.X:9005/`).
