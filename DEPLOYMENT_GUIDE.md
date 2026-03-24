# Complete Deployment & Usage Guide

## 🚀 Quick Start (5 minutes)

### Option A: Local Development (Without Docker)

#### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Redis 7.0+ (optional but recommended)
- Ollama installed locally

#### 1. Install Ollama
```bash
# macOS & Linux
curl -fsSL https://ollama.ai/install.sh | sh
ollama serve  # Start service in background

# Windows
# Download from https://ollama.ai/download
```

#### 2. Pull DeepSeek R1 Model
```bash
# In a new terminal after Ollama starts
ollama pull deepseek-r1:14b
# Takes ~10-20 minutes first time, uses ~10GB disk space
```

#### 3. Setup Laravel Project
```bash
cd /home/milad/W/Laravel/Warehouse

# Copy environment
cp .env.example .env

# Install dependencies
composer install

# Generate key
php artisan key:generate

# Run migrations (including new AI tables)
php artisan migrate

# Start development server
php artisan serve
# App runs on http://localhost:8000
```

#### 4. Test AI Service
```bash
# In another terminal
curl -X POST http://localhost:8000/api/ai/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello, what is your purpose?"}'
```

---

### Option B: Docker Deployment (Recommended for Production)

#### Prerequisites
- Docker & Docker Compose installed
- 20GB+ free disk space
- 8GB+ RAM

#### 1. Build and Start Services
```bash
cd /home/milad/W/Laravel/Warehouse

# Start all services
docker-compose up -d

# Check status
docker-compose ps
```

#### 2. Run Migrations in Container
```bash
docker-compose exec app php artisan migrate
```

#### 3. Pull DeepSeek Model in Ollama Container
```bash
# Option 1: Via container
docker-compose exec ollama ollama pull deepseek-r1:14b

# Option 2: Wait for it to pull automatically (first request will trigger it)
```

#### 4. Access Application
- Web UI: http://localhost
- API: http://localhost:8000/api/*
- Ollama: http://localhost:11434/api/*

---

## 📝 Environment Variables

Key variables in `.env`:

```env
# Database
DB_HOST=localhost          # or 'mysql' in Docker
DB_DATABASE=warehouse
DB_USERNAME=root
DB_PASSWORD=your_password

# Redis (for queues, cache, sessions)
REDIS_HOST=localhost       # or 'redis' in Docker
REDIS_PORT=6379

# Ollama AI Configuration
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=deepseek-r1:14b
OLLAMA_TIMEOUT=300         # seconds
AI_MAX_TOKENS=2000
AI_TEMPERATURE=0.7         # 0=deterministic, 1=creative

# Queue
QUEUE_CONNECTION=redis     # or 'database' if no Redis
```

---

## 🎯 API Endpoints

All endpoints require authentication (`auth:sanctum`).

### 1. Check AI Status
```bash
GET /api/ai/status
```
Response:
```json
{
  "status": "online",
  "message": "AI service is running",
  "models": [
    {
      "name": "deepseek-r1:14b",
      "modified_at": "2024-03-17T10:00:00Z",
      "size": 10000000000
    }
  ]
}
```

### 2. Simple Chat
```bash
POST /api/ai/chat
{
  "message": "What's the current inventory status?"
}
```
Response:
```json
{
  "success": true,
  "message": "What's the current inventory status?",
  "response": "Based on your warehouse system, I can help you check inventory. Please ask for specific products or categories...",
  "timestamp": "2024-03-17T10:05:00Z"
}
```

### 3. Data Analysis (with Dynamic Selection)
```bash
POST /api/ai/analyze
{
  "question": "Which products have low stock in the Beverages category?",
  "models": ["Product", "ProductCategory"],
  "limit": 10
}
```
Response:
```json
{
  "success": true,
  "models_queried": ["Product", "ProductCategory"],
  "analysis": "Looking at your data, the Beverages category has 3 products with stock below minimum levels...",
  "timestamp": "2024-03-17T10:05:00Z"
}
```

### 4. Parse Natural Language to Query
```bash
POST /api/ai/parse-query
{
  "query": "Show me expensive products from the Appetizers category"
}
```
Response:
```json
{
  "success": true,
  "parsed": {
    "models": ["Product"],
    "filters": {
      "category": {"operator": "=", "value": "Appetizers"},
      "price": {"operator": ">", "value": 50}
    }
  }
}
```

### 5. Get Available Models
```bash
GET /api/ai/models
```
Response:
```json
{
  "models": [
    {
      "name": "Product",
      "table": "products",
      "fields": ["id", "name", "price", "stock_quantity", ...],
      "keyName": "id"
    },
    ...
  ],
  "total": 8
}
```

### 6. Start Conversation
```bash
POST /api/ai/conversation
{
  "title": "Quarterly Inventory Analysis"
}
```
Response:
```json
{
  "success": true,
  "conversation": {
    "id": 1,
    "user_id": 5,
    "title": "Quarterly Inventory Analysis",
    "created_at": "2024-03-17T10:00:00Z",
    "updated_at": "2024-03-17T10:00:00Z"
  }
}
```

### 7. Send Message in Conversation
```bash
POST /api/ai/conversation/1/message
{
  "message": "What are the top 5 selling products?",
  "include_data": true,
  "models": ["Product"]
}
```
Response:
```json
{
  "success": true,
  "conversation_id": 1,
  "user_message": "What are the top 5 selling products?",
  "assistant_response": "Based on your warehouse data, the top 5 selling products are...",
  "timestamp": "2024-03-17T10:05:00Z"
}
```

### 8. Get Conversation History
```bash
GET /api/ai/conversation/1
```
Response:
```json
{
  "success": true,
  "conversation": {
    "id": 1,
    "title": "Quarterly Inventory Analysis",
    "messages": [
      {"role": "user", "content": "message...", "created_at": "..."},
      {"role": "assistant", "content": "response...", "created_at": "..."}
    ]
  }
}
```

### 9. List All Conversations
```bash
GET /api/ai/conversations?page=1
```
Response:
```json
{
  "success": true,
  "conversations": {
    "data": [...],
    "links": {...},
    "meta": {...}
  }
}
```

---

## 🗺️ Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│           Frontend (Vue.js / Inertia)                   │
│     - Chat Interface                                    │
│     - Query Builder                                     │
│     - Conversation History                              │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│        Laravel Backend (API Endpoints)                  │
│     - Authentication (Sanctum)                          │
│     - Request Validation                                │
│     - Permission Checks                                 │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│       Service Layer (Business Logic)                    │
│  ┌─────────────────────────────────────────────┐        │
│  │  OllamaAIService                            │        │
│  │  - Manages Ollama API calls                 │        │
│  │  - Token management                         │        │
│  │  - Prompt engineering                       │        │
│  └─────────────────────────────────────────────┘        │
│  ┌─────────────────────────────────────────────┐        │
│  │  DynamicDataService                         │        │
│  │  - Query builder                            │        │
│  │  - Model filtering                          │        │
│  │  - Data context generation                  │        │
│  └─────────────────────────────────────────────┘        │
└──────────────────────┬──────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
┌───────▼──────┐ ┌────▼────────┐ ┌──▼──────────┐
│  MySQL DB    │ │  Redis      │ │   Ollama    │
│  (Data)      │ │  (Cache)    │ │  (DeepSeek  │
│              │ │  (Queue)    │ │   R1 LLM)   │
└──────────────┘ └─────────────┘ └─────────────┘
```

---

## 🔧 Configuration & Customization

### Change Model
Edit `.env`:
```env
OLLAMA_MODEL=deepseek-r1:7b   # Lighter weight
# or
OLLAMA_MODEL=deepseek-r1:32b  # More powerful (needs GPU)
```

### Adjust Temperature (Randomness)
```env
AI_TEMPERATURE=0.3    # More focused, deterministic
AI_TEMPERATURE=0.9    # More creative, varied
```

### Max Response Length
```env
AI_MAX_TOKENS=4000    # Longer responses
AI_MAX_TOKENS=500     # Short, concise responses
```

### Add More Models to Query
Edit [app/Services/DynamicDataService.php](app/Services/DynamicDataService.php):
```php
private const ALLOWED_MODELS = [
    'Product',
    'ProductCategory',
    'PurchaseOrder',
    // Add your model here
    'CustomModel',
];
```

---

## 📊 Performance Tips

### 1. Cache Model Weights
Ollama automatically caches models, but you can clear cache:
```bash
rm -rf ~/.ollama/models  # Will re-download on next use
```

### 2. Use Smaller Models for Testing
```bash
ollama pull deepseek-r1:7b  # ~4GB, faster responses
```

### 3. Enable GPU (if available)
Ollama auto-detects NVIDIA CUDA. Install:
```bash
# Ubuntu/Debian
sudo apt-cache search nvidia-docker
sudo apt-get install -y nvidia-docker2

# Or use Docker script
curl https://get.docker.com | sh
distribution=$(. /etc/os-release;echo $ID$VERSION_ID)
curl -s -L https://nvidia.github.io/nvidia-docker/gpgkey | sudo apt-key add -
curl -s -L https://nvidia.github.io/nvidia-docker/$distribution/nvidia-docker.list | \
  sudo tee /etc/apt/sources.list.d/nvidia-docker.list
```

### 4. Queue Long-Running Requests
```php
// Use queue for heavy analysis
dispatch(new \App\Jobs\AnalyzeDataJob($question, $models));
```

### 5. Batch Requests
Group similar queries to avoid model reload:
```bash
# Instead of individual requests
# Do: 1 request with multiple questions in context
```

---

## 🐛 Troubleshooting

### Ollama Not Responding
```bash
# Check service status
ps aux | grep ollama

# Restart service
killall ollama
ollama serve  # in background

# Or in Docker
docker-compose restart ollama
docker-compose logs ollama
```

### Model Not Found
```bash
# List installed models
ollama list

# Pull the model
ollama pull deepseek-r1:14b

# Or in Docker
docker-compose exec ollama ollama pull deepseek-r1:14b
```

### Out of Memory
```bash
# Check system RAM
free -h

# Use smaller model
OLLAMA_MODEL=deepseek-r1:7b

# Increase swap (Linux)
sudo fallocate -l 8G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

### Slow Responses
```bash
# Check model status
curl http://localhost:11434/api/tags | jq

# If model is loading (few seconds), wait
# If consistently slow:
# - Use GPU (install nvidia-docker)
# - Use smaller model
# - Increase max_tokens less
```

### Database Connection Error
```bash
# Check MySQL
docker-compose logs mysql

# Verify credentials in .env
# Run migrations
php artisan migrate
# Or in Docker
docker-compose exec app php artisan migrate
```

---

## 📈 Monitoring

### Check Ollama Health
```bash
# Local
curl http://localhost:11434/api/status

# Docker
curl http://ollama:11434/api/status
```

### Monitor Response Quality
```bash
# Check conversation logs
php artisan tinker
>>> AI\ConversationMessage::latest()->limit(10)->get();
```

### View Application Logs
```bash
# Local
tail -f storage/logs/laravel.log

# Docker
docker-compose logs app -f
```

---

## 🚀 Production Deployment

### 1. Environment Setup
```bash
APP_ENV=production
APP_DEBUG=false
OLLAMA_HOST=https://your-domain.com:11434  # Use HTTPS
```

### 2. SSL/HTTPS
Place certificates in `docker/nginx/ssl/`:
```bash
# Self-signed (testing only)
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/ssl/private.key \
  -out docker/nginx/ssl/certificate.crt
```

### 3. Scale Services
```bash
# Docker: Scale with replicas
docker-compose up -d --scale app=3
```

### 4. Backup Database
```bash
# Regular backups
docker-compose exec mysql mysqldump -u root -p warehouse > backup.sql
```

### 5. Monitor Resources
```bash
docker stats  # Real-time resource usage
```

---

## 📞 Support & Issues

- Check logs: `docker-compose logs [service-name]`
- Test connectivity: `curl http://localhost:11434/api/tags`
- Verify database: `php artisan tinker`
- Check API: `php artisan route:list | grep ai`

---

## 🎓 Learning Resources

- Ollama: https://ollama.ai
- DeepSeek: https://www.deepseek.com
- Laravel: https://laravel.com
- Docker: https://docker.com
