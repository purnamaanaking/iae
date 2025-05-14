
# Microservices

Example of implementing microservices with Docker (multi-repo) and RabbitMQ for asynchronous communication.

## How to Setup

- Run `docker-compose` in each folder (rabbitmq, user-service, product-service, order-service).
- Enter each container (user-service, product-service, order-service) then run laravel migration and seeder.
- Enter the product-service container then run the queue worker
- Simulate asynchronous communication by making many continuous requests to the order-service `create an order` endpoint.