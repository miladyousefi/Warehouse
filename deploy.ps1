# Warehouse AI - Windows PowerShell Deployment
# Usage: powershell -ExecutionPolicy Bypass -File deploy.ps1

Write-Host "`n===============================================" -ForegroundColor Cyan
Write-Host "  Warehouse AI - Docker Deployment [Windows]" -ForegroundColor Cyan
Write-Host "===============================================`n" -ForegroundColor Cyan

# Check Docker
Write-Host "[1/5] Checking Docker installation..." -ForegroundColor Yellow
$dockerCheck = docker --version 2>$null
if (-not $?) {
    Write-Host "ERROR: Docker is not installed" -ForegroundColor Red
    Write-Host "Please install Docker Desktop from https://docker.com" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host "OK: Docker found" -ForegroundColor Green

# Check Docker Compose
$composeCheck = docker-compose --version 2>$null
if (-not $?) {
    $composeCheck = docker compose version 2>$null
    if (-not $?) {
        Write-Host "ERROR: Docker Compose is not installed" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}
Write-Host "OK: Docker Compose found`n" -ForegroundColor Green

# Setup environment
Write-Host "[2/5] Setting up environment..." -ForegroundColor Yellow
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    Write-Host "Created .env file"
}
Write-Host "OK: Environment configured`n" -ForegroundColor Green

# Build and start
Write-Host "[3/5] Building and starting services..." -ForegroundColor Yellow
docker-compose build --no-cache
docker-compose up -d
Write-Host ""

# Wait
Write-Host "[4/5] Waiting for services to be ready..." -ForegroundColor Yellow
Write-Host "Please wait..."
Start-Sleep -Seconds 15
Write-Host ""

# Migrations
Write-Host "[5/5] Setting up database and model..." -ForegroundColor Yellow
docker-compose exec -T app php artisan migrate --force

# Model
Write-Host "`nPulling DeepSeek R1 model (this takes 15-30 minutes)..." -ForegroundColor Yellow
Write-Host "Please wait..." -ForegroundColor Yellow
docker-compose exec -T ollama ollama pull deepseek-r1:14b

Write-Host "`n===============================================" -ForegroundColor Cyan
Write-Host "  SETUP COMPLETE!" -ForegroundColor Green
Write-Host "===============================================`n" -ForegroundColor Cyan

Write-Host "Your application is ready at:" -ForegroundColor Green
Write-Host "  Web:  http://localhost" -ForegroundColor Cyan
Write-Host "  API:  http://localhost:8000/api" -ForegroundColor Cyan
Write-Host ""
Write-Host "Useful commands:" -ForegroundColor Green
Write-Host "  View logs:    docker-compose logs -f app" -ForegroundColor White
Write-Host "  Stop:         docker-compose down" -ForegroundColor White
Write-Host "  Restart:      docker-compose restart" -ForegroundColor White
Write-Host ""
Read-Host "Press Enter to exit"
