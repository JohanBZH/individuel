#!/bin/bash
echo "Installing MeetRooms dependencies..."
echo "Note: NPM is required for Sass compilation."
npm install
composer install
echo "Setup complete. You can now use ./scripts/start.sh to run Docker."
