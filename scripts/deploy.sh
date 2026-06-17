#!/bin/bash
set -e

echo "🚀 Starting MeetRooms Production Deployment..."

if [ "$(git branch --show-current)" != "prod" ]; then
  echo "❌ Must be on the prod branch before deploying. Run: git checkout prod"
  exit 1
fi

echo "Pulling latest image from DockerHub..."
docker compose --file docker-compose.prod.yml --env-file .env.prod pull

echo "Starting containers..."
docker compose --file docker-compose.prod.yml --env-file .env.prod up -d

echo ""
echo "✅ Deployment complete!"
echo "📍 PROD is running at: http://localhost:8086"
