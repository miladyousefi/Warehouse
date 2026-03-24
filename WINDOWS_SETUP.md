# 🪟 Windows Installation Guide

## Complete Setup for Windows 10/11 with Docker

### Option A: Automatic Installation (Recommended)

#### Step 1: Install Docker Desktop

1. Download from: https://www.docker.com/products/docker-desktop
2. Run the installer
3. Follow the installation wizard
4. Enable **WSL 2** (Windows Subsystem for Linux 2) when prompted
5. Restart your computer

**Verify Docker installation:**
```cmd
docker --version
docker-compose --version
```

#### Step 2: Run Deployment Script

Navigate to your project folder in terminal or PowerShell:

**Option A1: Using Command Prompt (CMD)**
```cmd
cd C:\path\to\warehouse
deploy.bat
```

**Option A2: Using PowerShell**
```powershell
cd C:\path\to\warehouse
powershell -ExecutionPolicy Bypass -File deploy.ps1
```

**Option A3: Using Git Bash**
```bash
cd /c/path/to/warehouse
bash deploy.sh
```

The script will:
- ✅ Check Docker installation
- ✅ Create `.env` file if needed
- ✅ Build and start all services
- ✅ Run database migrations
- ✅ Download DeepSeek R1 model (15-30 minutes)

---

### Option B: Manual Installation

#### Step 1: Install Docker Desktop

Follow Option A Step 1

#### Step 2: Prepare Environment

1. Copy `.env.example` to `.env`:
```cmd
copy .env.example .env
```

2. Edit `.env` and ensure these settings:
```env
DB_HOST=mysql
REDIS_HOST=redis
OLLAMA_HOST=http://ollama:11434
```

#### Step 3: Build Services

```cmd
docker-compose build --no-cache
```

#### Step 4: Start Services

```cmd
docker-compose up -d
```

#### Step 5: Setup Database

```cmd
docker-compose exec app php artisan migrate --force
```

#### Step 6: Download Model

```cmd
docker-compose exec ollama ollama pull deepseek-r1:14b
```

⏳ This takes 15-30 minutes. Grab a coffee! ☕

---

## 🎯 Common Issues & Solutions

### Issue: "Docker is not installed"

**Solution:**
1. Download Docker Desktop from https://docker.com
2. Run the installer
3. Restart your computer
4. Open new Command Prompt/PowerShell and verify:
```cmd
docker --version
```

### Issue: WSL 2 Not Installed

**Solution:**
1. Open PowerShell as Administrator
2. Run:
```powershell
wsl --install
```
3. Restart computer
4. Open Docker Desktop again

### Issue: "Cannot connect to Docker daemon"

**Solution:**
1. Make sure Docker Desktop is running
2. Click the Docker icon in system tray
3. Wait for it to say "Docker is running"
4. Try again

### Issue: Port Already in Use

**Solutions:**
Find what's using the port:
```cmd
netstat -ano | findstr :8000
netstat -ano | findstr :3306
netstat -ano | findstr :11434
```

Kill the process (replace PID with the number shown):
```cmd
taskkill /PID 1234 /F
```

Or change the port in `.env`:
```env
APP_PORT=8001  # Change from 8000
DB_PORT=3307   # Change from 3306
```

### Issue: "Host machine does not support virtualization"

**Solution:**
1. Restart computer
2. Enter BIOS (F2, Del, or F12 during startup)
3. Find "Virtualization" or "VT-x" setting
4. Enable it
5. Save and restart

---

## 💻 After Installation

### Access Your Application

Open your browser and navigate to:
- **Web Interface**: http://localhost
- **API**: http://localhost:8000/api
- **Ollama API**: http://localhost:11434/api/tags

### View Logs

**Using CMD/PowerShell:**
```cmd
docker-compose logs -f

docker-compose logs -f app        # Laravel logs only
docker-compose logs -f ollama     # Ollama logs only
docker-compose logs -f mysql      # Database logs only
```

**Using Docker Desktop GUI:**
1. Open Docker Desktop
2. Go to "Containers"
3. Click on container name
4. View logs in the panel

### Stop Services

```cmd
docker-compose down
```

### Restart Services

```cmd
docker-compose restart
```

### View Running Containers

```cmd
docker-compose ps
```

---

## 🔧 Common Commands

### Enter Application Container

```cmd
docker-compose exec app bash
```

Then run Laravel commands:
```bash
php artisan tinker
php artisan migrate
php artisan db:seed
```

### View Database

Using MySQL client (if installed):
```cmd
mysql -h 127.0.0.1 -u root -p warehouse
```

Or use a GUI tool like:
- **MySQL Workbench**: https://www.mysql.com/products/workbench/
- **DBeaver**: https://dbeaver.io/
- **TablePlus**: https://tableplus.com/

Connection details:
- Host: 127.0.0.1
- Port: 3306
- User: root
- Password: (from `.env` DB_PASSWORD)
- Database: warehouse

### Export Logs

```cmd
docker-compose logs > logs.txt
```

---

## 🧹 Cleanup

### Remove Containers

```cmd
docker-compose down -v
```

The `-v` flag removes volumes (data will be deleted!)

### Remove All Docker Unused Resources

```cmd
docker system prune -a
```

### Remove Specific Container

```cmd
docker-compose down mysql
```

---

## 🆘 Getting Help

### Check Service Status

```cmd
docker-compose ps
```

Should show all services as "Up"

### View Full Logs

```cmd
docker-compose logs
```

### Check Docker Events

```cmd
docker events
```

---

## 📱 Using with WSL Terminal

If you're using WSL (Windows Subsystem for Linux):

1. **Install WSL 2:**
```powershell
wsl --install
```

2. **Install your favorite Linux distro** (Ubuntu recommended)

3. **Install Docker CLI in WSL** (Docker Desktop handles the backend)

4. **Navigate to project:**
```bash
cd /mnt/c/path/to/warehouse
```

5. **Run deployment:**
```bash
bash deploy.sh
```

---

## ⚡ Quick Start Checklist

- [ ] Docker Desktop installed and running
- [ ] Project cloned/extracted to a folder
- [ ] Run `deploy.bat` or `deploy.ps1`
- [ ] Wait for model to download (~30 minutes)
- [ ] Open http://localhost in browser
- [ ] Start developing!

---

## 🎉 You're Ready!

Your application is now running in Docker containers on Windows!

### Next Steps:
1. Create a user account
2. Get API token
3. Test the AI Chat endpoints
4. Read the API_REFERENCE.md for detailed documentation

**Happy Coding! 🚀**
