#!/bin/bash

set -e  # Stop the script if any command fails

wait_for_container() {
  local container=$1
  echo "⏳ Waiting for $container to be ready..."
  until docker exec "$container" php artisan --version >/dev/null 2>&1; do
    sleep 2
  done
  echo "✅ $container is ready."
}

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

wait_for_container user-service-app
echo "✅ Running migrations and seeding for User Service..."
docker exec user-service-app php artisan migrate:refresh --seed

wait_for_container product-service-app
echo "✅ Running migrations and seeding for Product Service..."
docker exec product-service-app php artisan migrate:refresh --seed

echo "🚀 Starting queue worker in Product Service..."
docker exec -d product-service-app php artisan queue:work

wait_for_container order-service-app
echo "✅ Running migrations and seeding for Order Service..."
docker exec order-service-app php artisan migrate:refresh --seed

echo "✅ All services have been started and configured successfully."
