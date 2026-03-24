@echo off
REM Warehouse AI - Windows Docker Deployment
REM One-command setup for Windows 10/11

echo.
echo ===============================================
echo  Warehouse AI - Docker Deployment [Windows]
echo ===============================================
echo.

REM Check Docker installation
echo [1/5] Checking Docker installation...
docker --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Docker is not installed
    echo Please install Docker Desktop from https://docker.com
    pause
    exit /b 1
)
echo OK: Docker found

REM Check Docker Compose
docker-compose --version >nul 2>&1
if errorlevel 1 (
    docker compose version >nul 2>&1
    if errorlevel 1 (
        echo ERROR: Docker Compose is not installed
        pause
        exit /b 1
    )
)
echo OK: Docker Compose found
echo.

REM Check .env file
echo [2/5] Setting up environment...
if not exist .env (
    copy .env.example .env
    echo Created .env file
)
echo OK: Environment configured
echo.

REM Build and start
echo [3/5] Building and starting services...
docker-compose build --no-cache
docker-compose up -d
echo.

REM Wait for services
echo [4/5] Waiting for services to be ready...
timeout /t 15 /nobreak
echo.

REM Run migrations
echo [5/5] Setting up database and model...
docker-compose exec -T app php artisan migrate --force

REM Pull model
echo.
echo Pulling DeepSeek R1 model (this takes 15-30 minutes)...
echo Please wait...
docker-compose exec -T ollama ollama pull deepseek-r1:14b

echo.
echo ===============================================
echo  SETUP COMPLETE!
echo ===============================================
echo.
echo Your application is ready at:
echo   Web:  http://localhost
echo   API:  http://localhost:8000/api
echo.
echo Useful commands:
echo   View logs:    docker-compose logs -f app
echo   Stop:         docker-compose down
echo   Restart:      docker-compose restart
echo.
pause
