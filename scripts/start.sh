#!/bin/bash
echo "Building SCSS before starting..."
npm run build || echo "NPM build failed, stylesheets might not be updated."

echo "Starting Docker Compose environments (dev, recette, prod)..."
docker-compose up -d --build

echo ""
echo "Environments are running:"
echo " - DEV:     http://localhost:8084"
echo " - RECETTE: http://localhost:8085"
echo " - PROD:    http://localhost:8086"
