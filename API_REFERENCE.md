# AI API Quick Reference

## Base URL
- Local: `http://localhost:8000/api/ai`
- Docker: `http://localhost:8000/api/ai`
- Production: `https://your-domain.com/api/ai`

## Authentication
All endpoints require Bearer token (Sanctum):
```
Authorization: Bearer YOUR_TOKEN
```

---

## Endpoints Summary

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| GET | `/status` | Check if Ollama is running | ✓ |
| GET | `/models` | Get available models for querying | ✓ |
| POST | `/chat` | Simple chat message | ✓ |
| POST | `/analyze` | Data-aware analysis | ✓ |
| POST | `/parse-query` | Parse natural language to models/filters | ✓ |
| POST | `/conversation` | Create new conversation | ✓ |
| GET | `/conversation/{id}` | Get conversation history | ✓ |
| GET | `/conversations` | List user's conversations | ✓ |
| POST | `/conversation/{id}/message` | Add message to conversation | ✓ |

---

## Detailed Examples

### 1️⃣ Check System Status
```bash
curl -X GET http://localhost:8000/api/ai/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "status": "online",
  "message": "AI service is running",
  "models": [{"name": "deepseek-r1:14b"}]
}
```

---

### 2️⃣ Simple Chat
```bash
curl -X POST http://localhost:8000/api/ai/chat \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "message": "What are your capabilities?"
  }'
```

**Request Body:**
```json
{
  "message": "string (required, max 1000 chars)"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "What are your capabilities?",
  "response": "I can help you with inventory analysis...",
  "timestamp": "2024-03-17T10:05:00Z"
}
```

---

### 3️⃣ Analyze Data
```bash
curl -X POST http://localhost:8000/api/ai/analyze \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "question": "What products are trending?",
    "models": ["Product", "ProductCategory"],
    "limit": 20
  }'
```

**Request Body:**
```json
{
  "question": "string (required, max 1000)",
  "models": ["Product", "ProductCategory"],  // optional
  "limit": 10  // optional, max 100
}
```

**Available Models:**
- Product
- ProductCategory
- PurchaseOrder
- PurchaseOrderItem
- RestaurantMenuItem
- RestaurantMenuCategory
- RestaurantMenuItemIngredient
- User
- ActivityLog

**Response (200 OK):**
```json
{
  "success": true,
  "question": "What products are trending?",
  "models_queried": ["Product", "ProductCategory"],
  "analysis": "Based on sales data, the following products show strong trends...",
  "timestamp": "2024-03-17T10:05:00Z"
}
```

---

### 4️⃣ Parse Query
```bash
curl -X POST http://localhost:8000/api/ai/parse-query \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "query": "Show expensive products in Appetizers"
  }'
```

**Request Body:**
```json
{
  "query": "string (required, max 1000)"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "query": "Show expensive products in Appetizers",
  "parsed": {
    "models": ["Product"],
    "filters": {
      "category": {"operator": "=", "value": "Appetizers"},
      "price": {"operator": ">", "value": 50}
    }
  }
}
```

---

### 5️⃣ Get Models Schema
```bash
curl -X GET http://localhost:8000/api/ai/models \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response (200 OK):**
```json
{
  "models": [
    {
      "name": "Product",
      "table": "products",
      "fields": ["id", "name", "description", "price", "stock_quantity", ...],
      "keyName": "id",
      "timestamps": true
    }
  ],
  "total": 8
}
```

---

### 6️⃣ Start Conversation
```bash
curl -X POST http://localhost:8000/api/ai/conversation \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "Q1 Inventory Planning"
  }'
```

**Request Body:**
```json
{
  "title": "string (optional, max 255)"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "conversation": {
    "id": 42,
    "user_id": 5,
    "title": "Q1 Inventory Planning",
    "created_at": "2024-03-17T10:00:00Z",
    "updated_at": "2024-03-17T10:00:00Z"
  }
}
```

---

### 7️⃣ Send Message to Conversation
```bash
curl -X POST http://localhost:8000/api/ai/conversation/42/message \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "message": "What should we order this quarter?",
    "include_data": true,
    "models": ["Product", "PurchaseOrder"]
  }'
```

**Request Body:**
```json
{
  "message": "string (required, max 1000)",
  "include_data": "boolean (optional, default: false)",
  "models": ["Product"]  // only if include_data=true
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "conversation_id": 42,
  "user_message": "What should we order this quarter?",
  "assistant_response": "Based on current inventory levels and sales trends...",
  "timestamp": "2024-03-17T10:05:00Z"
}
```

---

### 8️⃣ Get Conversation History
```bash
curl -X GET http://localhost:8000/api/ai/conversation/42 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response (200 OK):**
```json
{
  "success": true,
  "conversation": {
    "id": 42,
    "user_id": 5,
    "title": "Q1 Inventory Planning",
    "messages": [
      {
        "id": 101,
        "conversation_id": 42,
        "role": "user",
        "content": "What should we order?",
        "created_at": "2024-03-17T10:00:00Z"
      },
      {
        "id": 102,
        "conversation_id": 42,
        "role": "assistant",
        "content": "Based on your inventory...",
        "created_at": "2024-03-17T10:05:00Z"
      }
    ]
  }
}
```

---

### 9️⃣ List User's Conversations
```bash
curl -X GET "http://localhost:8000/api/ai/conversations?page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Query Params:**
```
?page=1        // pagination
?per_page=15   // items per page
```

**Response (200 OK):**
```json
{
  "success": true,
  "conversations": {
    "data": [
      {
        "id": 42,
        "title": "Q1 Inventory Planning",
        "created_at": "2024-03-17T10:00:00Z",
        "updated_at": "2024-03-17T10:05:00Z"
      }
    ],
    "links": {...},
    "meta": {
      "current_page": 1,
      "total": 5,
      "per_page": 15
    }
  }
}
```

---

## Error Responses

### 400 Bad Request
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "message": ["The message field is required."]
  }
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 503 Service Unavailable
```json
{
  "error": "AI service is not available",
  "suggestion": "Make sure Ollama is running"
}
```

### 500 Server Error
```json
{
  "error": "Failed to generate response",
  "details": "Error message details..."
}
```

---

## JavaScript/Axios Examples

```javascript
const API_BASE = 'http://localhost:8000/api/ai';
const TOKEN = 'your_sanctum_token';

const headers = {
  'Authorization': `Bearer ${TOKEN}`,
  'Content-Type': 'application/json'
};

// Chat
async function chat(message) {
  const response = await axios.post(
    `${API_BASE}/chat`,
    { message },
    { headers }
  );
  return response.data;
}

// Analyze
async function analyze(question, models) {
  const response = await axios.post(
    `${API_BASE}/analyze`,
    { question, models, limit: 10 },
    { headers }
  );
  return response.data;
}

// Conversation
async function startConversation(title) {
  const response = await axios.post(
    `${API_BASE}/conversation`,
    { title },
    { headers }
  );
  return response.data.conversation;
}

async function sendMessage(conversationId, message, includeData = false) {
  const response = await axios.post(
    `${API_BASE}/conversation/${conversationId}/message`,
    { message, include_data: includeData },
    { headers }
  );
  return response.data;
}
```

---

## Postman Collection

Import this in Postman:

```json
{
  "info": {
    "name": "Warehouse AI API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Check Status",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/api/ai/status",
        "header": [
          {"key": "Authorization", "value": "Bearer {{token}}"}
        ]
      }
    },
    {
      "name": "Chat",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/api/ai/chat",
        "header": [
          {"key": "Authorization", "value": "Bearer {{token}}"},
          {"key": "Content-Type", "value": "application/json"}
        ],
        "body": {
          "mode": "raw",
          "raw": "{\"message\": \"Your question here\"}"
        }
      }
    }
  ]
}
```

---

## Rate Limits & Best Practices

- **Max message length**: 1000 characters
- **Max limit**: 100 records per query
- **Timeout**: 300 seconds for Ollama responses
- **Cache responses**: Ollama caches model weights (~10GB)

## Tips

1. ✅ Use `include_data=true` for better context
2. ✅ Keep messages concise and clear
3. ✅ Reuse conversation IDs for related questions
4. ✅ Handle errors gracefully in your frontend
5. ❌ Don't send extremely long documents
6. ❌ Don't make multiple simultaneous heavy requests
