# 🚀 Chege Photos

> A professional, highly-scalable CodeIgniter 4 web application built for modern workflows.

A server-side backbone that stores, organizes, and serves photos uploaded from a mobile client, providing a secure web interface to browse hosted galleries, view albums, and manage favorites.

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](#)
[![CodeIgniter 4](https://img.shields.io/badge/CodeIgniter-4.x-EF4223?style=for-the-badge&logo=codeigniter&logoColor=white)](#)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](#)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](#)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

---

## 📖 1. About the Project

**Chege Photos** is a robust and flexible web application designed to solve complex developer workflows with ease. Built on the lightning-fast CodeIgniter 4 framework, this project acts as a complete, out-of-the-box solution for hosting, organizing, and privately sharing high-quality personal media collections. 

Our target users are developers, power users, and teams looking for an open-source solution that emphasizes performance, security, and developer experience (DX). 

**What makes this project unique?**
Every component of this application is fully containerized. From the zero-configuration automated database migrations on boot, to the host-mapped persistent storage volumes—this project guarantees a frictionless setup experience whether you are running it on a local machine or deploying it to a cloud server.

---

## ✨ 2. Features

- 🐳 **Instant Setup**: 100% Dockerized architecture. Go from zero to running in under 60 seconds.
- 🔄 **Automated Migrations**: Database tables and schemas are built automatically when the container boots.
- 💾 **Smart Persistence**: Database records and uploaded media safely persist on your local filesystem, completely isolated from container lifecycle events.
- 🛡️ **Hardened Security**: Features built-in CSRF protection, strictly configured session handling, and environment-driven configurations.
- 📊 **Integrated Database Management**: Comes bundled with a dedicated `phpMyAdmin` container for real-time database visualization.

---

## 🛠️ 3. Tech Stack

| Layer | Technology |
|---|---|
| **Backend Framework** | [CodeIgniter 4](https://codeigniter.com/) |
| **Language** | PHP 8.3 |
| **Database** | MySQL 8.4 |
| **Database Manager** | phpMyAdmin |
| **Containerization** | Docker & Docker Compose |
| **Dependency Manager** | Composer |

---

## 📋 4. Prerequisites

Before you begin, ensure you have the following installed on your machine:
- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/install/)
- [Git](https://git-scm.com/)

*(If you choose to run without Docker, you will need PHP 8.3+, Composer, and a local MySQL server).*

---

## 🚀 5. Installation & Setup (Detailed)

### Option 1: Using Docker (Preferred & Easiest)

This repository includes a pre-configured `docker-compose.yml` file. It completely eliminates the need to manually install PHP, web servers, or databases on your local machine.

#### 1. Clone the repository
```bash
git clone https://github.com/yourusername/chege-photos-webapp.git
cd "Chege Photos WebApp"
```

#### 2. Configure Environment Variables
Copy the provided environment template:
```bash
cp .env.example .env
```
*(Note: The defaults in `.env.example` are specifically pre-configured to work perfectly with the Docker environment out of the box).*

#### 3. Build and Run
Spin up the application, MySQL database, and phpMyAdmin in detached mode:
```bash
docker compose up --build -d
```

#### 4. Access the Application
Once the containers finish booting, database migrations run automatically. You can access your services at:
- **Application**: [http://localhost:9005](http://localhost:9005)
- **phpMyAdmin**: [http://localhost:9000](http://localhost:9000)

**Useful Docker Commands:**
- Stop the application: `docker compose down`
- View live application logs: `docker compose logs -f chege-photos`
- Restart the application: `docker compose restart chege-photos`

### Option 2: Local Development (Without Docker)

If you prefer a traditional local setup (e.g., XAMPP, Laragon, or Laravel Valet):

1. **Clone the repo** and run `composer install` in the root directory.
2. **Copy `.env.example`** to `.env`.
3. **Configure the Database**: Update the `database.default.*` variables inside your `.env` to match your local MySQL credentials.
4. **Run Migrations**: Build the necessary database tables by executing:
   ```bash
   php spark migrate --all
   ```
5. **Serve the Application**:
   ```bash
   php spark serve
   ```

---

## 🗄️ 6. Database Configuration

If you are using the Docker setup, the database configuration is completely automated.

**Development Credentials:**
- **Host:** `mysql`
- **Database Name:** `db_chege_photos`
- **Username:** `root`
- **Password:** `root_password`

You can visually manage this database by navigating to [http://localhost:9000](http://localhost:9000) and logging into phpMyAdmin with the credentials above.

---

## 📖 7. Usage

1. **Sign Up / Login**: Navigate to the homepage to create your first administrative account.
2. **Dashboard**: Access the main dashboard to view analytics and metrics.
3. **File Management**: Any artifacts or files you upload within the application will be securely persisted inside the `/writable/uploads` directory on your local machine.

---

## 📁 8. Project Structure

```text
.
├── app/            # Core application logic (Controllers, Models, Views)
├── public/         # Document root (accessible to the web)
├── writable/       # Cache, logs, sessions, and persisted uploads
├── system/         # CodeIgniter 4 framework files
├── docker-compose.yml # Standalone Docker orchestration
├── entrypoint.sh   # Automated migration startup script
└── Dockerfile      # PHP-Apache container build instructions
```

---

## 🤝 9. Contributing

We welcome contributions from the community! To contribute:

1. **Fork** the repository.
2. **Create a new branch**: `git checkout -b feature/your-feature-name`
3. **Commit your changes**: `git commit -m 'Add some feature'`
4. **Push to the branch**: `git push origin feature/your-feature-name`
5. **Open a Pull Request**.

Please ensure you run tests and verify your changes inside the Docker environment before submitting.

---

## 📄 10. License

Distributed under the MIT License. See `LICENSE` for more information.

---

## 📸 11. Screenshots & Demo

*(Screenshots coming soon)*

---

## 💬 12. Support & Acknowledgments

- Built with ❤️ using [CodeIgniter 4](https://codeigniter.com/).
- UI components powered by modern web standards.
