#!/bin/bash

# Warehouse AI - Docker Deploy Script
# One-command setup for Linux and macOS

set -e

echo "🚀 Warehouse AI - Docker Deployment"
echo "===================================="
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check prerequisites
echo -e "${BLUE}📋 Checking prerequisites...${NC}"

if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}❌ Docker is not installed${NC}"
    echo "Please install Docker from https://docker.com"
    exit 1
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo -e "${YELLOW}❌ Docker Compose is not installed${NC}"
    echo "Please install Docker Compose from https://docker.com"
    exit 1
fi

echo -e "${GREEN}✅ Docker & Docker Compose found${NC}"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${BLUE}📝 Creating .env file...${NC}"
    cp .env.example .env
    echo -e "${GREEN}✅ .env created${NC}"
fi

echo ""
echo -e "${BLUE}🐳 Starting Docker services...${NC}"

# Build and start services
docker-compose build --no-cache
docker-compose up -d

echo ""
echo -e "${BLUE}⏳ Waiting for services to be ready...${NC}"
sleep 10

# Run migrations
echo -e "${BLUE}🗄️  Running database migrations...${NC}"
docker-compose exec -T app php artisan migrate --force

# Pull DeepSeek model
echo ""
echo -e "${BLUE}📦 Pulling DeepSeek R1 model (this takes 15-30 minutes)...${NC}"
docker-compose exec -T ollama ollama pull deepseek-r1:14b

echo ""
echo -e "${GREEN}✅ Setup complete!${NC}"
echo ""
echo "🌐 Access your application:"
echo -e "   Web: ${BLUE}http://localhost${NC}"
echo -e "   API: ${BLUE}http://localhost:8000/api${NC}"
echo ""
echo "📝 Useful commands:"
echo "   View logs:    docker-compose logs -f app"
echo "   Stop:         docker-compose down"
echo "   Restart:      docker-compose restart"
echo ""
