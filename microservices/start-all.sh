#!/bin/bash

set -e  # Stop the script if any command fails

# Create Docker network if it doesn't exist
echo "🔧 Creating Docker network 'laravel-net' if not exists..."
docker network inspect laravel-net >/dev/null 2>&1 || \
docker network create laravel-net

echo "🚀 Starting RabbitMQ..."
cd rabbitmq || { echo "Directory rabbitmq not found."; exit 1; }
docker-compose up -d --build

echo "🚀 Starting User Service..."
cd ../user-service || { echo "Directory user-service not found."; exit 1; }
docker-compose up -d --build

echo "🚀 Starting Product Service..."
cd ../product-service || { echo "Directory product-service not found."; exit 1; }
docker-compose up -d --build

echo "🚀 Starting Order Service..."
cd ../order-service || { echo "Directory order-service not found."; exit 1; }
docker-compose up -d --build

echo "✅ Running migrations and seeding for User Service..."
docker exec -it user-service-app php artisan migrate:refresh --seed

echo "✅ Running migrations and seeding for Product Service..."
docker exec -it product-service-app php artisan migrate:refresh --seed

echo "🚀 Starting queue worker in Product Service..."
docker exec -d product-service-app php artisan queue:work

echo "✅ Running migrations and seeding for Order Service..."
docker exec -it order-service-app php artisan migrate:refresh --seed

echo "✅ All services have been started and configured successfully."
