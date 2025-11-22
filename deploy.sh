#!/bin/bash

# Deploy script for SportMonks Middleware on EC2
# Usage: ./deploy.sh

echo "🚀 Starting deployment to EC2..."

# SSH connection details
EC2_HOST="ubuntu@3.1.211.162"
PROJECT_DIR="sportmonks-middleware"

# SSH and execute commands on EC2
ssh $EC2_HOST << 'ENDSSH'
    echo "📂 Navigating to project directory..."
    cd sportmonks-middleware || exit 1

    echo "📥 Pulling latest code from GitHub..."
    git pull origin main

    echo "🐳 Stopping containers..."
    docker compose down

    echo "🔨 Building and starting containers..."
    docker compose up -d --build

    echo "⏳ Waiting for services to start..."
    sleep 5

    echo "✅ Checking container status..."
    docker compose ps

    echo "📋 Showing recent logs..."
    docker compose logs --tail=20 api

    echo "✅ Deployment complete!"
ENDSSH

echo ""
echo "🎉 Deployment finished!"
echo "🔗 API URL: http://3.1.211.162:3000"
echo ""
echo "Test endpoints:"
echo "  curl http://3.1.211.162:3000/api/fixtures/today"
echo "  curl http://3.1.211.162:3000/api/fixtures/date/2025-11-23"
