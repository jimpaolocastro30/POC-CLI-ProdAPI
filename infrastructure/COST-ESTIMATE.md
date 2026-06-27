# GCP Infrastructure Cost Estimates

Indicative **monthly** costs in **USD** for `us-central1`. Actual bills depend on usage, egress, build frequency, and sustained load.

**Pricing basis:** Google Cloud list pricing (approximate). Use the [GCP Pricing Calculator](https://cloud.google.com/products/calculator) for quotes tied to your project.

---

## Summary by environment

| Environment | Monthly estimate | Best for |
|-------------|------------------|----------|
| **Dev** (minimal) | **$70 – $140** | POC, developer testing |
| **Staging** (minimal) | **$130 – $260** | Pre-prod, QA, demos |
| **Production** (TRD enterprise) | **$850 – $1,500** | Live workloads, HA |
| **Dev + Staging** (same project) | **$200 – $400** | Typical pre-prod |
| **All three** (dev + staging + prod) | **$1,050 – $1,900** | Full SDLC on GCP |

---

## Dev (minimal) — line items

Terraform: `environments/dev.tfvars.example` · K8s: 1 pod · LoadBalancer ingress

| Component | Configuration | Est. monthly |
|-----------|---------------|--------------|
| GKE Autopilot | 1 pod (~100m CPU, 256Mi), low traffic | $15 – $35 |
| Cloud SQL PostgreSQL | `db-f1-micro`, ZONAL, 10 GB | $12 – $20 |
| Memorystore Redis | 1 GB, BASIC, single zone | $35 – $45 |
| Load Balancer (regional) | 1 forwarding rule + low egress | $18 – $30 |
| Cloud Storage | 3 buckets, &lt;10 GB, no lifecycle tiers | $1 – $5 |
| Secret Manager | 1 DB password secret | &lt; $1 |
| Artifact Registry | &lt;5 GB images (shared if multi-env) | $1 – $3 |
| VPC / private networking | Peering, minimal egress | $5 – $15 |
| Cloud Build | ~5–10 deploys/month | $0 – $15 |
| Monitoring / Logging | Alerts disabled; basic logs | $5 – $15 |
| Pub/Sub | Low volume | &lt; $1 |
| **Total** | | **$70 – $140** |

---

## Staging (minimal) — line items

Terraform: `environments/staging.tfvars.example` · K8s: 2 pods (HPA 2–4) · LoadBalancer

| Component | Configuration | Est. monthly |
|-----------|---------------|--------------|
| GKE Autopilot | 2 pods (~150m CPU, 384Mi), moderate bursts | $35 – $70 |
| Cloud SQL PostgreSQL | `db-g1-small`, ZONAL, 20 GB, PITR | $35 – $55 |
| Memorystore Redis | 1 GB, BASIC | $35 – $45 |
| Load Balancer (regional) | 1 forwarding rule + egress | $18 – $35 |
| Cloud Storage | 3 buckets, &lt;20 GB | $2 – $8 |
| Secret Manager | Per-env secret | &lt; $1 |
| Artifact Registry | Shared (no new repo) | $0 – $3 |
| VPC / networking | Separate VPC per env | $5 – $15 |
| Cloud Build | Tests + deploys (~15–20/month) | $10 – $30 |
| Monitoring / Logging | Basic alerts + logs | $10 – $25 |
| Pub/Sub | Low volume | &lt; $1 |
| **Total** | | **$130 – $260** |

---

## Production (enterprise TRD) — line items

Terraform defaults: `db-custom-4-16384` REGIONAL HA · Redis 5 GB STANDARD_HA · 3–20 pods · Global LB + Armor

| Component | Configuration | Est. monthly |
|-----------|---------------|--------------|
| GKE Autopilot | 3 pods baseline, HPA to 20 under load | $150 – $400 |
| Cloud SQL PostgreSQL | 4 vCPU / 16 GB, **REGIONAL HA**, 100 GB, PITR | $400 – $600 |
| Memorystore Redis | 5 GB, **STANDARD_HA** | $200 – $300 |
| Global HTTPS Load Balancer | Static IP, proxy, forwarding rules | $25 – $45 |
| Cloud Armor | WAF policies (Enterprise tier higher) | $25 – $150 |
| Managed SSL | Google-managed certificates | $0 |
| Cloud DNS | 1 zone + queries | $1 – $5 |
| Cloud Storage | 3 buckets, lifecycle, versioning, 50–200 GB | $10 – $50 |
| Secret Manager | Multiple secrets | $1 – $5 |
| Artifact Registry | Image storage + egress to GKE | $5 – $20 |
| VPC / networking | Private GKE, SQL, Redis | $15 – $40 |
| Cloud Build | CI on every merge + scans | $20 – $80 |
| Monitoring / Logging | Dashboards, alerts, log volume | $30 – $80 |
| Pub/Sub + BigQuery (light analytics) | Topics + occasional queries | $5 – $30 |
| Backup storage (SQL + GCS) | Retained backups | $10 – $40 |
| **Total** | | **$850 – $1,500** |

Under **heavy traffic** (HPA maxed, high egress, Armor Enterprise), production can exceed **$2,000/month**.

---

## Combined scenarios (one GCP project)

| Scenario | What's running | Est. monthly |
|----------|----------------|--------------|
| Dev only | 1 cluster, 1 SQL, 1 Redis, 1 LB | $70 – $140 |
| Staging only | 1 cluster, 1 SQL, 1 Redis, 1 LB | $130 – $260 |
| Dev + Staging | 2 clusters, 2 SQL, 2 Redis, 2 LB, shared Artifact Registry | $200 – $400 |
| Production only | Full HA stack | $850 – $1,500 |
| Dev + Staging + Production | 3 isolated stacks | $1,050 – $1,900 |

**Cost tip:** Dev and staging each provision a **separate GKE cluster** in this repo. For lowest cost, run **dev only** until you need staging.

---

## Shared vs per-environment resources

| Resource | Dev | Staging | Production | Shared in one project? |
|----------|-----|---------|------------|------------------------|
| GKE cluster | `inventory-cluster-dev` | `inventory-cluster-staging` | `inventory-cluster-production` | No — one per env |
| Cloud SQL | `inventory-db-dev` | `inventory-db-staging` | `inventory-db-production` | No |
| Redis | `inventory-redis-dev` | `inventory-redis-staging` | `inventory-redis-production` | No |
| VPC | `inventory-vpc-dev` | `inventory-vpc-staging` | `inventory-vpc-production` | No |
| Artifact Registry | `inventory-api` | (reuses) | (reuses) | **Yes** — one repo |
| Load Balancer | Regional (Service LB) | Regional | Global + Armor | Per env |

---

## What drives cost up

| Factor | Impact |
|--------|--------|
| GKE pod count / CPU / memory (HPA scale-out) | High |
| Cloud SQL REGIONAL HA vs ZONAL | ~2× database compute |
| Redis STANDARD_HA vs BASIC | ~2× Redis |
| Internet egress (API responses, image pulls) | Medium–high |
| Cloud Build minutes (tests, scans, frequent deploys) | Medium |
| Log ingestion volume | Medium |
| Cloud Armor Enterprise | High |
| Cross-region resources | High |

---

## What drives cost down

| Action | Savings |
|--------|---------|
| Deploy **dev only** (skip staging until needed) | ~$130–260/mo |
| Use ZONAL SQL in non-prod | ~40–50% on SQL |
| BASIC Redis instead of HA | ~50% on Redis |
| Skip Cloud Armor / global LB in non-prod | ~$50–150/mo |
| Scale dev to 0 pods when idle (manual) | Variable |
| Use `docker compose` locally instead of GCP dev | ~$70–140/mo |
| Committed use discounts (1y/3y) on SQL | ~20–40% on SQL |

---

## One-time / non-monthly costs

| Item | Estimate |
|------|----------|
| Domain registration | ~$10–15/year |
| First Terraform apply (no proration surprise) | Pro-rated first month |
| Data migration / seed | Negligible |
| Security scans (SonarQube, ZAP) if self-hosted | Separate |

---

## How to get an exact quote

1. Open [Google Cloud Pricing Calculator](https://cloud.google.com/products/calculator).
2. Add products matching the tables above for your region.
3. Compare with **Billing → Cost breakdown** after 7–14 days of real usage.

---

**Document version:** 1.0 · Region default: `us-central1` · Currency: USD
