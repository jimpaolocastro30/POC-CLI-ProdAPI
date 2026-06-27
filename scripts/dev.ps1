#!/usr/bin/env pwsh
# Start local development environment (no GCP required).
param(
    [switch]$Rebuild,
    [switch]$Down
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

if ($Down) {
    docker compose down
    Write-Host "Development environment stopped." -ForegroundColor Green
    exit 0
}

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "Created .env from .env.example" -ForegroundColor Yellow
}

if ($Rebuild) {
    docker compose build --no-cache app
}

Write-Host "Starting development environment (postgres, redis, api)..." -ForegroundColor Cyan
docker compose up -d

Write-Host "Waiting for API health check (may take 1-2 minutes on first build)..." -ForegroundColor Cyan
$ready = $false
for ($i = 0; $i -lt 60; $i++) {
    $status = docker compose ps app --format "{{.Health}}" 2>$null
    if ($status -match "healthy") {
        $ready = $true
        break
    }
  try {
        $r = Invoke-WebRequest -Uri "http://localhost:8080/up" -UseBasicParsing -TimeoutSec 3
        if ($r.StatusCode -eq 200) { $ready = $true; break }
    } catch { }
    Start-Sleep -Seconds 3
    Write-Host "." -NoNewline
}

Write-Host ""

if ($ready) {
    Write-Host ""
    Write-Host "Development API is running!" -ForegroundColor Green
    Write-Host "  Health:  http://localhost:8080/up"
    Write-Host "  API:     http://localhost:8080/api"
    Write-Host "  Login:   admin@inventory.local / password"
    Write-Host ""
    Write-Host "Postman: import postman/Inventory-Management-API.postman_collection.json"
    Write-Host "Logs:    docker compose logs -f app"
    Write-Host "Stop:    .\scripts\dev.ps1 -Down"
} else {
    Write-Host "API not healthy yet. Check logs:" -ForegroundColor Yellow
    Write-Host "  docker compose logs app"
}
