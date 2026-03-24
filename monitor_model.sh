#!/bin/bash

# Monitor DeepSeek R1 Model Download Progress
# Usage: ./monitor_model.sh

echo "🔍 Monitoring DeepSeek R1 Model Download..."
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

while true; do
    if [ ! -f /tmp/ollama_pull.log ]; then
        echo -e "${YELLOW}⏳ Model download not started yet${NC}"
        sleep 5
        continue
    fi
    
    LATEST=$(tail -1 /tmp/ollama_pull.log 2>/dev/null)
    
    if [ -z "$LATEST" ]; then
        echo -e "${YELLOW}⏳ Waiting for model download to start...${NC}"
        sleep 5
        continue
    fi
    
    # Check if download completed
    if echo "$LATEST" | grep -q ""; then
        if ! echo "$LATEST" | grep -q "pulling"; then
            echo -e "${GREEN}✅ Model download completed!${NC}"
            echo "Latest status: $LATEST"
            
            # Verify model is available
            echo ""
            echo "Checking if DeepSeek R1 model is available in Ollama..."
            MODELS=$(curl -s http://localhost:11434/api/tags | grep -o "deepseek-r1" | head -1)
            
            if [ ! -z "$MODELS" ]; then
                echo -e "${GREEN}✅ DeepSeek R1 model is ready!${NC}"
                echo ""
                echo "You can now test the AI API:"
                echo "  - Get models: curl http://localhost:8000/api/ai/models"
                echo "  - Check status: curl http://localhost:8000/api/ai/status"
            else
                echo -e "${YELLOW}⏳ Model is still being processed...${NC}"
            fi
            break
        fi
    fi
    
    # Show progress
    clear
    echo "🔍 Monitoring DeepSeek R1 Model Download..."
    echo ""
    echo "Current Status:"
    echo "  $LATEST"
    echo ""
    echo "Services Status:"
    echo "  Ollama: $(ps aux | grep 'ollama serve' | grep -v grep > /dev/null && echo '✅ Running' || echo '❌ Not running')"
    echo "  Laravel: $(ps aux | grep 'php artisan serve' | grep -v grep > /dev/null && echo '✅ Running' || echo '❌ Not running')"
    echo ""
    echo "Full log: tail -f /tmp/ollama_pull.log"
    echo ""
    echo "Press Ctrl+C to stop monitoring"
    
    sleep 10
done
