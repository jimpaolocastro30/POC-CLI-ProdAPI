param(
    [Parameter(Mandatory = $true)]
    [string]$ProjectId,
    [string]$Region = "us-central1"
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot

Write-Host "=== Inventory API — GCP DEV deploy ===" -ForegroundColor Cyan
Write-Host "Tip: run .\scripts\dev.ps1 first to validate the app locally." -ForegroundColor Yellow
Write-Host "Project: $ProjectId  Region: $Region"

gcloud config set project $ProjectId

Push-Location "$Root\infrastructure\terraform"
if (-not (Test-Path "dev.tfvars")) {
    Copy-Item "environments\dev.tfvars.example" "dev.tfvars"
    Write-Host "Created dev.tfvars — edit project_id then re-run." -ForegroundColor Yellow
    Pop-Location
    exit 1
}

terraform init
terraform workspace select dev 2>$null
if ($LASTEXITCODE -ne 0) { terraform workspace new dev }
terraform apply -var-file=dev.tfvars -auto-approve

$Cluster = terraform output -raw gke_cluster_name
$DbIp = terraform output -raw cloud_sql_private_ip
$RedisHost = terraform output -raw redis_host
$DbSecret = terraform output -raw db_password_secret
Pop-Location

gcloud container clusters get-credentials $Cluster --region $Region --project $ProjectId

Write-Host ""
Write-Host "Terraform complete. Next manual steps:" -ForegroundColor Green
Write-Host "1. Update infrastructure/k8s/environments/dev/configmap.yaml"
Write-Host "   DB_HOST=$DbIp  REDIS_HOST=$RedisHost"
Write-Host "2. Create K8s secret inventory-api-secrets in namespace inventory-dev"
Write-Host "   DB password: gcloud secrets versions access latest --secret=$DbSecret"
Write-Host "3. kubectl apply -f infrastructure/k8s/environments/dev/"
Write-Host "4. gcloud builds submit --config=cloudbuild-dev.yaml"
Write-Host "5. kubectl apply -f infrastructure/k8s/environments/dev/migrate-job.yaml"
Write-Host "6. kubectl get svc inventory-api-lb -n inventory-dev"
