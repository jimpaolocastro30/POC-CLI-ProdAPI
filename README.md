# Inventory Management API Platform

Production-grade Inventory Management API built with **Laravel 12**, **JWT authentication**, **Spatie RBAC**, and **Google Cloud Platform** infrastructure.

## Features

- JWT authentication with refresh token rotation and MFA (email OTP)
- Role-Based Access Control (Super Administrator, Inventory Manager, Warehouse Staff, Auditor, Viewer)
- Product, category, and supplier management
- Inventory transactions (stock in, stock out, adjustments)
- Dashboard KPIs and reports (inventory, movements, audit)
- Full audit trail logging
- Docker + GKE Autopilot deployment
- Terraform-managed GCP infrastructure
- Cloud Build CI/CD pipeline

## Tech Stack

| Layer | Technology |
|-------|------------|
| API | Laravel 12, PHP 8.3 |
| Auth | JWT (`php-open-source-saver/jwt-auth`) |
| RBAC | Spatie Laravel Permission |
| Database | PostgreSQL (Cloud SQL) |
| Cache/Queue | Redis (Memorystore) |
| Containers | Docker, GKE Autopilot |
| IaC | Terraform |
| CI/CD | Cloud Build |

## Quick Start (Local)

### Prerequisites

- PHP 8.2+, Composer
- Docker & Docker Compose (recommended)

### Option A: Docker Compose

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan migrate --seed
```

API available at `http://localhost:8080`

### Option B: Local PHP

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

Default admin: `admin@inventory.local` / `password`

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Login |
| POST | `/api/auth/verify-mfa` | Verify MFA OTP |
| POST | `/api/auth/logout` | Logout (auth) |
| POST | `/api/auth/refresh` | Refresh token (auth) |
| POST | `/api/auth/forgot-password` | Request password reset |
| POST | `/api/auth/reset-password` | Reset password |
| GET | `/api/auth/me` | Current user (auth) |

### Users & Roles

| Method | Endpoint | Permission |
|--------|----------|------------|
| GET/POST | `/api/users` | manage users |
| GET/PUT/DELETE | `/api/users/{id}` | manage users |
| GET/POST | `/api/roles` | manage roles |
| PUT | `/api/roles/{id}` | manage roles |

### Inventory

| Method | Endpoint | Permission |
|--------|----------|------------|
| GET/POST | `/api/products` | view/create item |
| GET/PUT/DELETE | `/api/products/{id}` | view/update/delete item |
| GET/POST | `/api/categories` | manage categories |
| PUT | `/api/categories/{id}` | manage categories |
| GET/POST | `/api/suppliers` | manage suppliers |
| PUT | `/api/suppliers/{id}` | manage suppliers |
| POST | `/api/inventory/stock-in` | receive stocks |
| POST | `/api/inventory/stock-out` | release stocks |
| POST | `/api/inventory/adjustment` | manage stock adjustments |
| GET | `/api/inventory/history` | view transactions |

### Dashboard & Reports

| Method | Endpoint |
|--------|----------|
| GET | `/api/dashboard` |
| GET | `/api/reports/inventory` |
| GET | `/api/reports/movements` |
| GET | `/api/reports/audit` |

All authenticated endpoints require `Authorization: Bearer <token>` header.

## RBAC Roles

| Role | Key Permissions |
|------|-----------------|
| Super Administrator | Users, roles, permissions, inventory, audit logs, settings |
| Inventory Manager | CRUD items, categories, suppliers, adjustments, reports |
| Warehouse Staff | View inventory, receive/release stock, transactions |
| Auditor | View inventory, transactions, audit logs, reports |
| Viewer | Read-only access |

## GCP Infrastructure

Terraform provisions:

- VPC with private networking
- GKE Autopilot cluster (multi-zone, private nodes)
- Cloud SQL PostgreSQL HA (regional, PITR, automated backups)
- Memorystore Redis (5GB HA)
- Cloud Storage buckets (documents, backups, images) with lifecycle rules
- Artifact Registry
- Pub/Sub topic
- Secret Manager (DB credentials)
- Cloud Monitoring alert policies
- Service accounts (api, db, monitoring, backup)

## GCP Deployment

See **[infrastructure/DEPLOYMENT-PLAN.md](infrastructure/DEPLOYMENT-PLAN.md)** for the full step-by-step GCP deployment plan (Terraform, GKE, CI/CD, DNS, security, DR).


## CI/CD Pipeline

Cloud Build stages:

1. Composer install
2. PHPUnit / Pest tests
3. Docker build
4. Push to Artifact Registry
5. Deploy to GKE (rolling update)

## Testing

```bash
composer test
# or
php artisan test
```

## Project Structure

```
app/
  Http/Controllers/Api/    # API controllers
  Http/Requests/           # Form request validation
  Models/                  # Eloquent models
  Services/                # Business logic (inventory, audit, MFA)
  Enums/                   # UserStatus, TransactionType
database/migrations/       # Schema migrations
database/seeders/          # Roles, permissions, admin user
docker/                    # Dockerfile, nginx, supervisord
infrastructure/
  terraform/               # GCP infrastructure
  k8s/                     # Kubernetes manifests
routes/api.php             # API route definitions
tests/                     # Pest feature tests
```

## Security

- JWT access tokens with configurable TTL
- Refresh token rotation via JWT blacklist
- MFA via email OTP
- Spatie permission middleware on all routes
- Audit logging on all mutations
- Private GKE / Cloud SQL / Redis networking (Terraform)
- Secret Manager for credentials
- Cloud Armor WAF (configure at load balancer layer)

## Performance Targets

| Metric | Target |
|--------|--------|
| API response | < 200ms |
| Login | < 300ms |
| Concurrent users | 5,000+ |
| Uptime | 99.95% |

## License

MIT
