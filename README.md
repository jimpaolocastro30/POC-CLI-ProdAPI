# Inventory Management API Platform

Production-grade Inventory Management API — **Laravel 12**, JWT auth, Spatie RBAC, with optional **GCP** deployment.

---

## Start here — local development (no GCP)

**Prerequisites:** [Docker Desktop](https://www.docker.com/products/docker-desktop/) (includes Docker Compose).

```powershell
cd d:\development\POC-CLI-ProdAPI
.\scripts\dev.ps1
```

First run builds images and may take several minutes. The script:

1. Creates `.env` from `.env.example` if missing  
2. Starts PostgreSQL, Redis, and the API  
3. Generates `APP_KEY` and `JWT_SECRET`  
4. Runs migrations and seeds the database  
5. Waits until `http://localhost:8080/up` is healthy  

| Resource | URL |
|----------|-----|
| Health | http://localhost:8080/up |
| API base | http://localhost:8080/api |
| Admin login | `admin@inventory.local` / `password` |

```powershell
# Rebuild after dependency changes
.\scripts\dev.ps1 -Rebuild

# Stop everything
.\scripts\dev.ps1 -Down

# Follow logs
docker compose logs -f app
```

**Postman:** import `postman/Inventory-Management-API.postman_collection.json`, run **Auth → Login**.

### Manual compose (alternative)

```powershell
copy .env.example .env
docker compose up -d --build
```

---

## Deployment path (after local dev works)

| Step | Environment | Guide |
|------|-------------|--------|
| 1 | **Local dev** (this repo) | `.\scripts\dev.ps1` |
| 2 | **GCP dev** | [MINIMAL-GCP-SETUP.md](infrastructure/MINIMAL-GCP-SETUP.md) |
| 3 | **GCP staging** | Same guide, staging section |
| 4 | **Production** | [DEPLOYMENT-PLAN.md](infrastructure/DEPLOYMENT-PLAN.md) |

Costs: [COST-ESTIMATE.md](infrastructure/COST-ESTIMATE.md)

---

## Features

- JWT authentication with refresh token rotation and MFA (email OTP)
- Role-Based Access Control (5 roles, 20 permissions)
- Product, category, supplier, and inventory transaction APIs
- Dashboard KPIs and reports (inventory, movements, audit)
- Audit trail on mutations
- Docker local dev · GKE + Terraform for cloud

## Tech Stack

| Layer | Technology |
|-------|------------|
| API | Laravel 12, PHP 8.3 |
| Auth | JWT (`php-open-source-saver/jwt-auth`) |
| RBAC | Spatie Laravel Permission |
| Database | PostgreSQL |
| Cache / Queue | Redis |
| Local dev | Docker Compose |
| Cloud | GKE Autopilot, Cloud SQL, Terraform |

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
| GET/POST | `/api/suppliers` | manage suppliers |
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

## RBAC Roles

| Role | Key permissions |
|------|-----------------|
| Super Administrator | All permissions |
| Inventory Manager | CRUD items, categories, suppliers, reports |
| Warehouse Staff | Receive/release stock, transactions |
| Auditor | View inventory, transactions, audit logs |
| Viewer | Read-only |

## GCP Deployment

| Guide | Use when |
|-------|----------|
| **[MINIMAL-GCP-SETUP.md](infrastructure/MINIMAL-GCP-SETUP.md)** | GCP dev / staging |
| **[DEPLOYMENT-PLAN.md](infrastructure/DEPLOYMENT-PLAN.md)** | Production |
| **[COST-ESTIMATE.md](infrastructure/COST-ESTIMATE.md)** | Monthly cost breakdown |

GCP dev (only after local dev works):

```powershell
.\scripts\deploy-gcp-dev.ps1 -ProjectId YOUR_PROJECT_ID
```

## Project Structure

```
app/                    # Laravel application
docker/                 # Dockerfile.dev (local), Dockerfile (production)
docker-compose.yml      # Local development stack
infrastructure/         # Terraform, K8s, deployment guides
postman/                # API collection
scripts/dev.ps1         # Start local dev (run this first)
tests/                  # Pest tests
```

## Testing

```powershell
docker compose exec app php artisan test
```

## License

MIT
