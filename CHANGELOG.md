# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0] - 2026-08-27

### Changed
- **Dockerization Optimizations**: Refactored `Dockerfile` to implement efficient multi-stage builds.
  - Leveraged BuildKit cache mounts (`--mount=type=cache`) for `apt` package manager and `composer` downloads.
  - Added dependency layering to separate Composer installation (`composer.json`, `composer.lock`) from application code changes, improving build speed.
  - Configured system package installation with `--no-install-recommends` to keep the image slim.
  - Cleaned up Composer binary after dependency installation to reduce attack surface and final image size.
  - Moved Apache configuration updates into organized blocks and automated PHP runtime limits configuration.
- **Docker Ignore Updates**: Updated `.dockerignore` to exclude `.gitignore` and `vendor/` directory while ensuring `composer.lock` is included to lock dependencies during Docker build stages.
