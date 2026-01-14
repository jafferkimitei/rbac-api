# RBAC API (Laravel + Sanctum + Spatie)

A production-minded RBAC backend API built with **Laravel**, **Sanctum (token auth)**, and **Spatie Laravel Permission**.
Designed to serve a separate frontend (e.g. Next.js) while keeping auth tokens server-side where possible.

---

## Stack

- **Laravel** (API)
- **Laravel Sanctum** (Bearer tokens)
- **Spatie Laravel Permission** (roles + permissions)
- **PostgreSQL** (primary DB)
- **Docker** (containerized runtime)
- **GitHub Actions** (CI)
- **Render** (CD via deploy hook)

---

## Features

- Email/password login → returns bearer token + user payload (roles + permissions)
- `/api/me` for current user session context
- Role + permission protected routes (Spatie middleware)
- Feature tests for RBAC enforcement
- Docker-first deployment compatible with Render

---

## Requirements

- PHP **8.4+**
- Composer 2.x
- PostgreSQL 15+ (or 16 recommended)
- Node.js (optional, not required for backend)

---

## Local Setup

### 1) Install dependencies
```bash
composer install
cp .env.example .env
php artisan key:generate
