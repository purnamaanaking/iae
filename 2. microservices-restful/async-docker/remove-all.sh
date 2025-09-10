#!/bin/bash

set -e  # Exit immediately if a command exits with a non-zero status

echo "🛑 Stopping and removing RabbitMQ..."
cd rabbitmq || { echo "Directory /rabbitmq not found."; exit 1; }
docker compose down --rmi all -v

echo "🛑 Stopping and removing User Service..."
cd ../user-service || { echo "Directory /user-service not found."; exit 1; }
docker compose down --rmi all -v

echo "🛑 Stopping and removing Product Service..."
cd ../product-service || { echo "Directory /product-service not found."; exit 1; }
docker compose down --rmi all -v

echo "🛑 Stopping and removing Order Service..."
cd ../order-service || { echo "Directory /order-service not found."; exit 1; }
docker compose down --rmi all -v

echo "🗑️ Removing Docker network 'laravel-net'..."
docker network rm laravel-net || { echo "Network 'laravel-net' not found."; exit 1; }

echo "✅ All services have been stopped and cleaned up."
