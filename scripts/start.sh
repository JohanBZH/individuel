#!/bin/bash
echo "Building SCSS before starting..."
npm run build || echo "NPM build failed, stylesheets might not be updated."

echo "Which environment do you want to start? (dev, recette, prod)"
read ENV

echo "Starting Docker Compose environments (dev, recette, prod)..."
docker compose --file docker-compose.$ENV.yml --env-file .env.$ENV up -d --build

echo ""
echo "Environments is running:"

case $ENV in
  "dev")
    echo " - DEV:     http://localhost:8084"
    ;;
  "recette")
    echo " - RECETTE: http://localhost:8085"
    ;;
  "prod")
    echo " - PROD:    http://localhost:8086"
    ;;
  *)
    echo "Invalid environment selected. Please choose dev, recette, or prod."
    ;;
esac
