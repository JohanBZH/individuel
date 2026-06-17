#!/bin/bash
echo "Building SCSS before starting..."
npm run build || echo "NPM build failed, stylesheets might not be updated."

echo "Which environment do you want to start? (dev, recette, prod, tous)"
read ENV

echo ""

case $ENV in
  "tous")
    echo "Starting all environments..."
    docker compose --file docker-compose.dev.yml --env-file .env.dev up -d --build
    docker compose --file docker-compose.recette.yml --env-file .env.recette up -d --build
    docker compose --file docker-compose.prod.yml --env-file .env.prod up -d --build
    echo ""
    echo "Environments running:"
    echo " - DEV:     http://localhost:8084"
    echo " - RECETTE: http://localhost:8085"
    echo " - PROD:    http://localhost:8086"
    ;;
  "dev"|"recette"|"prod")
    echo "Starting Docker Compose environment ($ENV)..."
    docker compose --file docker-compose.$ENV.yml --env-file .env.$ENV up -d --build
    echo ""
    echo "Environment running:"
    case $ENV in
      "dev")     echo " - DEV:     http://localhost:8084" ;;
      "recette") echo " - RECETTE: http://localhost:8085" ;;
      "prod")    echo " - PROD:    http://localhost:8086" ;;
    esac
    ;;
  *)
    echo "Invalid environment selected. Please choose dev, recette, prod, or tous."
    exit 1
    ;;
esac
