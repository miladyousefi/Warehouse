# DeepSeek R1 Local Integration Guide

## Architecture Overview

```
┌─────────────────┐
│  Vue Frontend   │
└────────┬────────┘
         │
    ┌────▼────────────────────┐
    │  Laravel API Endpoints   │
    │  /api/ai/* endpoints     │
    └────┬────────────────────┘
         │
    ┌────▼──────────────────────┐
    │   AI Service Layer        │
    │   - Query Builder         │
    │   - Prompt Engineering    │
    │   - Context Management    │
    └────┬──────────────────────┘
         │
    ┌────▼──────────────────────┐
    │  DeepSeek R1 via Ollama   │
    │  (Running Locally)        │
    └──────────────────────────┘
```

## Step 1: Install Ollama (Local LLM Server)

### For Linux:
```bash
# Download and install Ollama
curl -fsSL https://ollama.ai/install.sh | sh

# Start Ollama service
ollama serve
```

### For macOS:
```bash
# Download from https://ollama.ai or use Homebrew
brew install ollama

# Start Ollama
ollama serve
```

### For Windows:
Download installer from https://ollama.ai/download

## Step 2: Pull DeepSeek R1 Model

Once Ollama is running, pull the DeepSeek R1 model:

```bash
# In a new terminal
ollama pull deepseek-r1:14b  # or 7b for lighter machines

# Or use 32b version if you have resources:
# ollama pull deepseek-r1:32b
```

### Model Selection Guide:
- **7b**: Lightweight, good for testing (~/4GB RAM)
- **14b**: Recommended balance (8-16GB RAM)
- **32b**: Powerful but heavy (24GB+ RAM, GPU recommended)

## Step 3: Verify Model Installation

```bash
# Test the model (Ollama API should be running on http://localhost:11434)
curl -X POST http://localhost:11434/api/generate -d '{
  "model": "deepseek-r1:14b",
  "prompt": "Hello, what are you?",
  "stream": false
}'
```

You should see a JSON response with the model's answer.

## Step 4: Environment Variables

Add to your `.env` file:

```env
# AI Configuration
AI_ENABLED=true
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=deepseek-r1:14b
OLLAMA_TIMEOUT=300
AI_MAX_TOKENS=2000
AI_TEMPERATURE=0.7
AI_CONTEXT_LENGTH=4096

# Queue Configuration (for long-running AI operations)
QUEUE_CONNECTION=database
```

## Step 5: Database Setup for AI Features

The system tracks:
- AI chat conversations
- Query history
- Model interactions

Run migrations:
```bash
php artisan migrate
```

## Usage Examples

### Simple Query
```bash
curl -X POST http://localhost:8000/api/ai/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "What products are low on stock?"
  }'
```

### Dynamic Data Query
```bash
curl -X POST http://localhost:8000/api/ai/analyze \
  -H "Content-Type: application/json" \
  -d '{
    "question": "Show me products from Beverages category with sales > 100",
    "models": ["Product", "ProductCategory"],
    "limit": 10
  }'
```

### Conversational Chat
```bash
curl -X POST http://localhost:8000/api/ai/conversation \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": "abc123",
    "message": "What trends do you see?"
  }'
```

## Troubleshooting

### Ollama not responding
```bash
# Check if Ollama is running
ps aux | grep ollama

# Start it manually
ollama serve
```

### Model not found
```bash
# List installed models
ollama list

# Pull the model
ollama pull deepseek-r1:14b
```

### Out of memory
- Use smaller model: `ollama pull deepseek-r1:7b`
- Or increase system swap/virtual memory
- Use GPU acceleration if available: `ollama list` and check CUDA support

## Performance Tips

1. **Use GPU**: Ollama automatically uses GPU if NVIDIA CUDA is detected
2. **Keep warm**: Don't stop Ollama between requests for better performance
3. **Batch requests**: Group similar queries to Ollama for efficiency
4. **Handle timeouts**: Implement retry logic for API calls

## Next Steps

1. Run Laravel migrations
2. Start Ollama service
3. Configure `.env` file
4. Use the API endpoints to start chatting!
