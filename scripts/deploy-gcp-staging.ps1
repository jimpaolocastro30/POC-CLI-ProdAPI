param(
    [Parameter(Mandatory = $true)]
    [string]$ProjectId,
    [string]$Region = "us-central1"
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot

Write-Host "=== Inventory API — minimal STAGING deploy ===" -ForegroundColor Cyan
Write-Host "Project: $ProjectId  Region: $Region"

gcloud config set project $ProjectId

Push-Location "$Root\infrastructure\terraform"
if (-not (Test-Path "staging.tfvars")) {
    Copy-Item "environments\staging.tfvars.example" "staging.tfvars"
    Write-Host "Created staging.tfvars — edit project_id then re-run." -ForegroundColor Yellow
    Pop-Location
    exit 1
}

terraform init
terraform workspace select staging 2>$null
if ($LASTEXITCODE -ne 0) { terraform workspace new staging }
terraform apply -var-file=staging.tfvars -auto-approve

$Cluster = terraform output -raw gke_cluster_name
$DbIp = terraform output -raw cloud_sql_private_ip
$RedisHost = terraform output -raw redis_host
$DbSecret = terraform output -raw db_password_secret
Pop-Location

gcloud container clusters get-credentials $Cluster --region $Region --project $ProjectId

Write-Host ""
Write-Host "Terraform complete. Next manual steps:" -ForegroundColor Green
Write-Host "1. Update infrastructure/k8s/environments/staging/configmap.yaml"
Write-Host "   DB_HOST=$DbIp  REDIS_HOST=$RedisHost"
Write-Host "2. Create K8s secret in namespace inventory-staging"
Write-Host "   DB password: gcloud secrets versions access latest --secret=$DbSecret"
Write-Host "3. kubectl apply -f infrastructure/k8s/environments/staging/"
Write-Host "4. gcloud builds submit --config=cloudbuild-staging.yaml"
Write-Host "5. kubectl apply -f infrastructure/k8s/environments/staging/migrate-job.yaml"
Write-Host "6. kubectl get svc inventory-api-lb -n inventory-staging"
