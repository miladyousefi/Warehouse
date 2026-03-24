# ⚡ AI Chat Quick Reference Guide

**Everything you need at a glance**

---

## 🚀 Quick Start (After Setup)

### **Start Everything**
```bash
# Terminal 1: Start Ollama
ollama serve

# Terminal 2: Start Laravel
cd /home/milad/W/Laravel/Warehouse
php artisan serve

# Terminal 3: Build frontend (if using Vite)
npm run dev
```

### **Access AI Chat**
```
http://localhost:8000/ai/chat
```

---

## 📂 File Quick Reference

| File | Purpose |
|------|---------|
| `resources/js/Pages/AI/Chat.vue` | Main chat interface |
| `resources/js/Components/Sidebar/AISidebarMenu.vue` | Sidebar menu |
| `routes/ai.php` | Page routes |
| `app/Http/Controllers/AI/ChatController.php` | Page controller |
| `app/Http/Controllers/API/AIController.php` | API endpoints |
| `app/Services/OllamaAIService.php` | Ollama integration |
| `app/Services/DynamicDataService.php` | Data querying |
| `.env` | Configuration |

---

## 🔌 API Endpoints Quick Reference

### Base URL: `http://localhost:8000/api/ai/`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/status` | Check if Ollama is running |
| POST | `/conversation` | Create new conversation |
| GET | `/conversation/{id}` | Load conversation |
| POST | `/conversation/{id}/message` | Send message |
| GET | `/conversations` | List all conversations |
| POST | `/chat` | Quick chat (no history) |
| POST | `/analyze` | Analyze data with AI |

### Example Request:
```bash
curl -X POST http://localhost:8000/api/ai/conversation/1/message \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "How many products are in stock?",
    "include_data": ["Product", "Category"]
  }'
```

---

## 🎨 Common Customizations

### Change Chat Color Theme
**In `Chat.vue`, find these Tailwind classes:**

```vue
<!-- Blue theme (default) -->
<div class="bg-blue-600">...</div>  <!-- Primary color -->
<div class="bg-gray-100">...</div>  <!-- Message background -->

<!-- Change to Green -->
<div class="bg-green-600">...</div>
<div class="bg-green-100">...</div>

<!-- Change to Purple -->
<div class="bg-purple-600">...</div>
<div class="bg-purple-100">...</div>
```

### Add Custom Data Models
**In `Chat.vue`, find `selectedModels` and add:**

```javascript
selectedModels: {
  'Product': false,
  'Category': false,
  'PurchaseOrder': false,  // ← Add new
  'Supplier': false,        // ← Add new
  // ... rest
}
```

### Change Model Temperature
**In `.env`:**
```env
AI_TEMPERATURE=0.7  # Range: 0.0 (precise) to 1.0 (creative)
```

### Change Model Max Tokens
**In `.env`:**
```env
AI_MAX_TOKENS=2000  # Max response length
```

---

## 🔐 Authentication Quick Setup

### Create Token for User
```php
$user = User::find(1);
$token = $user->createToken('api')->plainTextToken;

// Store for frontend
session(['sanctum_token' => $token]);
```

### Get Token in Frontend
```javascript
// From page props (server-side)
const props = usePageProps()
const token = props.auth.sanctum_token

// Or from localStorage
const token = localStorage.getItem('sanctum_token')
```

---

## 📊 Database

### Check Conversation Tables
```bash
php artisan tinker

# List all conversations
App\Models\AIConversation::all()

# List specific conversation messages
App\Models\AIConversation::find(1)->messages
```

### Clear All Conversations
```bash
php artisan tinker
App\Models\AIConversation::truncate()
App\Models\AIConversationMessage::truncate()
```

---

## 🐳 Docker Quick Commands

### Deploy Everything
```bash
# Linux/macOS
bash deploy.sh

# Windows CMD
deploy.bat

# Windows PowerShell
powershell -ExecutionPolicy Bypass -File deploy.ps1
```

### Docker Compose
```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f

# Rebuild images
docker-compose build --no-cache

# Run migrations
docker-compose exec app php artisan migrate
```

---

## 🧪 Testing Endpoints

### Test AI Status
```bash
curl http://localhost:11434/api/status
```
Expected: `200` with model info

### Test Laravel
```bash
curl http://localhost:8000/api/ai/status
```
Expected: `200` with connection status

### Send Test Message
```bash
curl -X POST http://localhost:8000/api/ai/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "What is 2+2?"}' \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🐛 Debugging

### View Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

### View Ollama Logs
```bash
tail -f /tmp/ollama.log
```

### View Model Download Progress
```bash
tail -f /tmp/ollama_pull.log
```

### Check Ollama Service
```bash
# Is it running?
ps aux | grep ollama

# Check on port 11434
netstat -an | grep 11434

# Or using curl
curl http://localhost:11434/api/status
```

### Check Laravel Service
```bash
# Is it running?
ps aux | grep "artisan serve"

# Check on port 8000
netstat -an | grep 8000
```

---

## 🔄 Restarting Services

### Restart Ollama
```bash
killall ollama
nohup ollama serve > /tmp/ollama.log 2>&1 &
```

### Restart Laravel
```bash
# Kill current process
# pkill -f "artisan serve"

# Restart
php artisan serve
```

### Restart Docker Services
```bash
docker-compose down
docker-compose up -d
```

---

## 📦 Installation Verification

### Check if All Files Exist
```bash
ls -la resources/js/Pages/AI/Chat.vue
ls -la resources/js/Components/Sidebar/AISidebarMenu.vue
ls -la routes/ai.php
ls -la app/Http/Controllers/AI/ChatController.php
```

### Check if Services Are Running
```bash
# Ollama
curl http://localhost:11434/api/status

# Laravel
curl http://localhost:8000

# Docker (if deployed)
docker ps
```

---

## 💡 Common Scenarios

### Scenario: "Chat says authentication failed"

**Fix:**
```php
// In LoginController or similar
$token = auth()->user()->createToken('api')->plainTextToken;
session(['sanctum_token' => $token]);
```

### Scenario: "Chat page shows blank"

**Fix:**
```bash
# Clear cache
php artisan cache:clear
php artisan view:clear

# Rebuild frontend
npm run dev
npm run build
```

### Scenario: "Ollama model not found"

**Fix:**
```bash
# Check if model exists
ollama list

# Download if missing
ollama pull deepseek-r1:14b

# Check download progress
tail -f /tmp/ollama_pull.log
```

### Scenario: "Can't access http://localhost:8000"

**Fix:**
```bash
# Check Laravel is running
ps aux | grep "artisan serve"

# Start it if not running
php artisan serve

# Check port 8000 is available
lsof -i :8000
```

---

## 📞 Support Resources

- **Integration Issues**: [AI_CHAT_INTEGRATION.md](AI_CHAT_INTEGRATION.md)
- **API Reference**: [API_REFERENCE.md](API_REFERENCE.md)
- **Advanced Setup**: [EXTENSION_GUIDE.md](EXTENSION_GUIDE.md)
- **Production Deployment**: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
- **Windows Setup**: [WINDOWS_SETUP.md](WINDOWS_SETUP.md)
- **Checklist**: [AI_INTEGRATION_CHECKLIST.md](AI_INTEGRATION_CHECKLIST.md)

---

## ⌨️ Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Enter` | Send message (if not in multi-line mode) |
| `Ctrl+Enter` | Send message |
| `Shift+Enter` | New line in message |
| `Esc` | Close (if dialog) |

---

## 🎯 Next Steps

1. ✅ **Setup**: Models downloaded, services running
2. ✅ **Integration**: Chat UI created
3. → **Testing**: Visit `/ai/chat` and send messages
4. → **Customization**: Adjust colors, models, responses
5. → **Deployment**: Use deploy.sh/bat/ps1 for production
6. → **Monitoring**: Set up logs and alerts

---

**Last Updated**: After integration v1.0
**Status**: Production Ready ✅
