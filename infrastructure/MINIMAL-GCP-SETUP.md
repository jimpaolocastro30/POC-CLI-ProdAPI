# Minimal GCP Setup — Dev & Staging

> **Run local development first** (no GCP account required):
> ```powershell
> .\scripts\dev.ps1
> ```
> Deploy to GCP only after `http://localhost:8080/up` works and Postman login succeeds.

Low-cost Google Cloud deployment for the Inventory Management API.

| Environment | Est. monthly cost | Cluster | Pods | SQL | Redis |
|-------------|-------------------|---------|------|-----|-------|
| **Dev** | ~$80–150 | `inventory-cluster-dev` | 1 | `db-f1-micro` ZONAL | 1 GB BASIC |
| **Staging** | ~$150–300 | `inventory-cluster-staging` | 2 (HPA 2–4) | `db-g1-small` ZONAL | 1 GB BASIC |

Both use a **LoadBalancer Service** (no custom domain or managed SSL required).

---

## What you get

- Separate GKE Autopilot cluster per environment
- Private Cloud SQL + Memorystore per environment
- Namespace `inventory-dev` or `inventory-staging`
- Public HTTP endpoint via GKE LoadBalancer (for Postman testing)
- Faster/cheaper Cloud Build pipelines (`cloudbuild-dev.yaml`, `cloudbuild-staging.yaml`)

**Cheapest path:** deploy **dev only** first. Add staging when you need pre-prod.

---

## Prerequisites

```powershell
gcloud auth login
gcloud auth application-default login
gcloud config set project YOUR_PROJECT_ID
```

Enable APIs (once per project):

```powershell
gcloud services enable compute.googleapis.com container.googleapis.com `
  sqladmin.googleapis.com redis.googleapis.com secretmanager.googleapis.com `
  artifactregistry.googleapis.com cloudbuild.googleapis.com `
  servicenetworking.googleapis.com monitoring.googleapis.com logging.googleapis.com
```

---

## Deploy DEV (step by step)

### 1. Terraform

```powershell
cd infrastructure\terraform
copy environments\dev.tfvars.example dev.tfvars
# Edit dev.tfvars — set project_id

terraform init
terraform workspace new dev 2>$null; terraform workspace select dev
terraform apply -var-file=dev.tfvars
```

Save outputs:

```powershell
terraform output
```

Get DB password:

```powershell
gcloud secrets versions access latest --secret=inventory-db-password-dev
```

### 2. kubectl

```powershell
gcloud container clusters get-credentials inventory-cluster-dev `
  --region us-central1 --project YOUR_PROJECT_ID
```

### 3. Configure Kubernetes

Edit `infrastructure/k8s/environments/dev/configmap.yaml`:

- `DB_HOST` → `terraform output cloud_sql_private_ip`
- `REDIS_HOST` → `terraform output redis_host`
- `GCP_PROJECT_ID`, bucket name

Edit `serviceaccount.yaml` — set your project ID in the WI annotation.

Create secrets:

```powershell
kubectl create secret generic inventory-api-secrets `
  -n inventory-dev `
  --from-literal=APP_KEY='base64:YOUR_KEY' `
  --from-literal=JWT_SECRET='YOUR_JWT' `
  --from-literal=DB_PASSWORD='DB_PASSWORD_FROM_SECRET_MANAGER' `
  --dry-run=client -o yaml | kubectl apply -f -
```

Workload Identity:

```powershell
$PROJECT = "YOUR_PROJECT_ID"
gcloud iam service-accounts add-iam-policy-binding inventory-api-dev@${PROJECT}.iam.gserviceaccount.com `
  --role roles/iam.workloadIdentityUser `
  --member "serviceAccount:${PROJECT}.svc.id.goog[inventory-dev/inventory-api]"

kubectl annotate serviceaccount inventory-api -n inventory-dev `
  iam.gke.io/gcp-service-account=inventory-api-dev@${PROJECT}.iam.gserviceaccount.com --overwrite
```

### 4. Build & deploy

```powershell
cd d:\development\POC-CLI-ProdAPI
gcloud builds submit --config=cloudbuild-dev.yaml
```

Or manual:

```powershell
$IMAGE = "us-central1-docker.pkg.dev/YOUR_PROJECT_ID/inventory-api/inventory-api:dev1"
docker build -f docker/Dockerfile -t $IMAGE .
docker push $IMAGE
kubectl set image deployment/inventory-api inventory-api=$IMAGE -n inventory-dev
```

### 5. Migrate database

```powershell
kubectl delete job inventory-migrate -n inventory-dev --ignore-not-found
kubectl apply -f infrastructure/k8s/environments/dev/migrate-job.yaml
kubectl wait --for=condition=complete job/inventory-migrate -n inventory-dev --timeout=300s
```

### 6. Get URL

```powershell
kubectl get svc inventory-api-lb -n inventory-dev -w
```

Open `http://EXTERNAL_IP/up` and Postman against `http://EXTERNAL_IP/api/...`

Default admin after seed: `admin@inventory.local` / `password` — **change immediately**.

---

## Deploy STAGING

Same flow as dev, but:

```powershell
cd infrastructure\terraform
copy environments\staging.tfvars.example staging.tfvars
terraform workspace new staging; terraform workspace select staging
terraform apply -var-file=staging.tfvars
```

```powershell
gcloud container clusters get-credentials inventory-cluster-staging --region us-central1
```

Use manifests in `infrastructure/k8s/environments/staging/` and namespace `inventory-staging`.

```powershell
gcloud builds submit --config=cloudbuild-staging.yaml
```

**Note:** `staging.tfvars` sets `create_artifact_registry = false` — run **dev Terraform first** so the shared `inventory-api` registry exists.

---

## One-command helpers (PowerShell)

```powershell
.\scripts\deploy-gcp-dev.ps1 -ProjectId YOUR_PROJECT_ID
.\scripts\deploy-gcp-staging.ps1 -ProjectId YOUR_PROJECT_ID
```

---

## vs Production

| Feature | Dev / Staging | Production |
|---------|---------------|------------|
| SQL HA | ZONAL | REGIONAL HA |
| Redis | BASIC 1 GB | STANDARD_HA 5 GB |
| Ingress | LoadBalancer | Global LB + managed SSL |
| Cloud Armor | Not included | WAF on Ingress |
| Replicas | 1–2 | 3–20 |
| PITR | Dev: off, Staging: on | On |
| Est. cost | $80–300/mo | $850–1,500/mo |

Full production guide: [DEPLOYMENT-PLAN.md](DEPLOYMENT-PLAN.md)

---

## Tear down

```powershell
cd infrastructure\terraform
terraform workspace select dev
terraform destroy -var-file=dev.tfvars
```

Delete LoadBalancer IPs from console if they persist after cluster delete.
