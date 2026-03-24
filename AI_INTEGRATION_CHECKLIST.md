# ✅ AI Chat Integration Checklist

Use this checklist to verify your AI Chat integration is complete.

## 📁 File Verification

- [ ] `resources/js/Pages/AI/Chat.vue` exists
- [ ] `resources/js/Components/Sidebar/AISidebarMenu.vue` exists  
- [ ] `routes/ai.php` exists
- [ ] `app/Http/Controllers/AI/ChatController.php` exists
- [ ] `app/Services/OllamaAIService.php` exists
- [ ] `app/Services/DynamicDataService.php` exists
- [ ] `app/Http/Controllers/API/AIController.php` exists
- [ ] `app/Models/AIConversation.php` exists
- [ ] `app/Models/AIConversationMessage.php` exists

## 🔌 Route Registration

- [ ] `routes/ai.php` is required in `routes/web.php`
- [ ] AI API routes are in `routes/api.php`
- [ ] `/ai/chat` route accessible
- [ ] `/ai/chat/{id}` route accessible
- [ ] `/api/ai/*` routes registered

## 💾 Database

- [ ] `ai_conversations` table created (migration run)
- [ ] `ai_conversation_messages` table created (migration run)
- [ ] Both tables have correct schema
- [ ] Can verify with: `php artisan migrate:status`

## 🔐 Authentication

- [ ] User model uses `HasApiTokens`
- [ ] Sanctum token created on login
- [ ] Token available in page props or localStorage
- [ ] API authentication working (Bearer token)

## 🎨 UI Integration

- [ ] AISidebarMenu component imported in layout
- [ ] Menu item visible in sidebar
- [ ] Chat page accessible at `/ai/chat`
- [ ] Chat page loads without errors
- [ ] Styling matches application theme

## 🌐 Environment Setup

- [ ] `.env` has `OLLAMA_HOST=http://localhost:11434`
- [ ] `.env` has `OLLAMA_MODEL=deepseek-r1:14b`
- [ ] Ollama service is running: `ps aux | grep ollama`
- [ ] Laravel service is running: `ps aux | grep "artisan serve"`

## 🤖 AI Model

- [ ] Model download status checked: `tail -f /tmp/ollama_pull.log`
- [ ] Model exists locally: `ollama list`
- [ ] Can be called: Test `/api/ai/status` endpoint

## 🧪 Testing

- [ ] Visit `http://localhost:8000/ai/chat`
- [ ] Page loads without JavaScript errors
- [ ] Can type in message input
- [ ] Can select data models (checkboxes)
- [ ] Can submit message (Enter or button)
- [ ] Message appears in history
- [ ] API call shows in browser DevTools
- [ ] Conversation saves to database

## 📊 API Endpoints Working

- [ ] `GET /api/ai/status` - returns 200
- [ ] `POST /api/ai/conversation` - creates conversation
- [ ] `POST /api/ai/conversation/{id}/message` - sends message
- [ ] `GET /api/ai/conversation/{id}` - loads conversation
- [ ] `GET /api/ai/models` - lists available models

## 🚀 Deployment Ready

- [ ] `deploy.sh` script exists and is executable
- [ ] `deploy.bat` script exists (Windows)
- [ ] `deploy.ps1` script exists (Windows)
- [ ] Docker files configured
- [ ] Can run: `bash deploy.sh` (Linux/macOS)

## 🎯 Optional Enhancements

- [ ] Floating AI button added (FloatingAIButton.vue)
- [ ] Custom styling applied
- [ ] Additional data models configured
- [ ] Reports page using similar pattern
- [ ] User preferences saved

---

## 🔍 Quick Verification Commands

```bash
# Check files exist
ls -la resources/js/Pages/AI/Chat.vue
ls -la routes/ai.php
ls -la docker-compose.yml

# Check services running
curl http://localhost:11434/api/status
curl http://localhost:8000/ai/chat

# Check database
php artisan migrate:status

# Check Ollama model
ollama list

# Check model download progress
tail -f /tmp/ollama_pull.log
```

---

## 📝 Common Issues & Solutions

### Issue: "Cannot find module './Chat.vue'"
**Solution:** Ensure correct import path:
```javascript
import Chat from '@/Pages/AI/Chat.vue'  // ✅ Correct
import Chat from '~/Pages/AI/Chat.vue'  // ❌ Wrong
```

### Issue: "Sanctum token not found"
**Solution:** Verify token is created and passed to frontend:
```php
// In login controller or middleware
session(['sanctum_token' => $user->createToken('api')->plainTextToken]);
```

### Issue: "POST /api/ai/conversation 401 Unauthorized"
**Solution:** Check API token is being sent:
```javascript
fetch('/api/ai/conversation', {
  headers: {
    'Authorization': `Bearer ${token}`,  // Must include this
    'Content-Type': 'application/json'
  }
})
```

### Issue: "Ollama service not responding"
**Solution:** Restart Ollama:
```bash
killall ollama
nohup ollama serve &
```

### Issue: "Model not found"
**Solution:** Check model is downloaded:
```bash
ollama list
ollama pull deepseek-r1:14b  # Re-download if needed
```

---

## ✨ Final Verification

Once all checkboxes are complete, your system should:

✅ Have AI Chat visible in sidebar  
✅ Allow users to access `/ai/chat` page  
✅ Enable real-time chat with DeepSeek R1  
✅ Store conversations in database  
✅ Support data selection for context  
✅ Be deployable via single command  
✅ Work on Linux, macOS, and Windows  

---

## 🎉 Integration Complete!

When all checkboxes are checked, your AI Chat system is fully integrated and production-ready.

For troubleshooting, see:\
- [AI_CHAT_INTEGRATION.md](AI_CHAT_INTEGRATION.md) - Integration guide\
- [WINDOWS_SETUP.md](WINDOWS_SETUP.md) - Windows-specific issues\
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Production deployment
