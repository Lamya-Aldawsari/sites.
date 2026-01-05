# Microservices Architecture

## Overview

The iBoat platform is designed with a scalable microservices architecture to ensure high availability, scalability, and maintainability.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      Load Balancer / API Gateway            │
└─────────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
┌───────▼────────┐  ┌───────▼────────┐  ┌───────▼────────┐
│   Main API     │  │  Booking      │  │   Payment      │
│   Service      │  │  Service       │  │   Service      │
│   (Port 8000)  │  │  (Port 8001)  │  │  (Port 8002)   │
└───────┬────────┘  └───────┬────────┘  └───────┬────────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
┌───────▼────────┐  ┌───────▼────────┐  ┌───────▼────────┐
│  Notification │  │   PostgreSQL   │  │     Redis      │
│   Service     │  │   Database     │  │     Cache      │
│  (Port 8003)  │  │   (Port 5432)  │  │   (Port 6379)  │
└───────────────┘  └─────────────────┘  └────────────────┘
```

## Services

### 1. Main API Service (Port 8000)
**Responsibilities:**
- User authentication and authorization
- Boat CRUD operations
- Equipment marketplace
- Cart management
- Vendor dashboard
- Public API endpoints

**Technology:** Laravel 10, PHP 8.1+

### 2. Booking Service (Port 8001)
**Responsibilities:**
- On-demand booking creation
- Scheduled booking management
- Availability checking
- Booking status updates
- Captain assignment

**Technology:** Laravel 10, PHP 8.1+

**API Endpoints:**
- `POST /bookings` - Create booking
- `GET /bookings` - List bookings
- `PUT /bookings/{id}` - Update booking
- `POST /bookings/{id}/cancel` - Cancel booking

### 3. Payment Service (Port 8002)
**Responsibilities:**
- Payment processing (Stripe integration)
- Payment intent creation
- Refund processing
- Transaction history
- Payment webhooks

**Technology:** Laravel 10, PHP 8.1+, Stripe SDK

**API Endpoints:**
- `POST /payments/intent` - Create payment intent
- `POST /payments/confirm` - Confirm payment
- `POST /payments/refund` - Process refund
- `GET /payments/transactions` - Get transactions

### 4. Notification Service (Port 8003)
**Responsibilities:**
- Email notifications
- SMS notifications
- Push notifications
- In-app notifications
- Notification queue management

**Technology:** Laravel 10, PHP 8.1+, Queue Workers

**API Endpoints:**
- `POST /notifications/send` - Send notification
- `GET /notifications` - List notifications
- `PUT /notifications/{id}/read` - Mark as read

## Shared Infrastructure

### PostgreSQL Database
- **Port:** 5432
- **Purpose:** Primary relational database
- **Connection:** All services connect to shared database
- **Schema:** Shared schema with service-specific tables

### Redis Cache
- **Port:** 6379
- **Purpose:** 
  - Caching (DB 1)
  - Sessions (DB 2)
  - Queue (DB 3)
  - Rate limiting
  - Real-time data

## Inter-Service Communication

### HTTP REST API
Services communicate via HTTP REST APIs:
- Synchronous communication for real-time operations
- JSON payloads
- Authentication via API keys or JWT tokens

### Message Queue (Redis)
- Asynchronous communication for non-critical operations
- Event-driven architecture
- Decoupled services

### Shared Database
- Direct database access for read operations
- Event sourcing for write operations
- Database transactions for consistency

## Service Discovery

Services are discovered via:
1. **Environment Variables:** Service URLs configured in `.env`
2. **Docker Compose:** Service names as hostnames
3. **API Gateway:** Central routing point (future implementation)

## Data Consistency

### Eventual Consistency
- Non-critical operations use eventual consistency
- Notifications, analytics, etc.

### Strong Consistency
- Critical operations use database transactions
- Payments, bookings, inventory updates

## Scalability

### Horizontal Scaling
- Each service can be scaled independently
- Load balancer distributes traffic
- Stateless services for easy scaling

### Vertical Scaling
- Database and Redis can be scaled vertically
- Connection pooling for database
- Redis clustering for high availability

## High Availability

### Redundancy
- Multiple instances of each service
- Database replication (master-slave)
- Redis sentinel for failover

### Health Checks
- Docker health checks for all services
- Service health endpoints
- Automatic restart on failure

## Deployment

### Docker Compose (Development)
```bash
docker-compose up -d
```

### Kubernetes (Production)
- Each service as a Kubernetes deployment
- Service mesh for inter-service communication
- Auto-scaling based on metrics

## Monitoring & Logging

### Logging
- Centralized logging (ELK stack or similar)
- Structured logging (JSON format)
- Log aggregation per service

### Monitoring
- Application metrics (Prometheus)
- Health check endpoints
- Performance monitoring
- Error tracking (Sentry)

## Security

### Authentication
- JWT tokens for service-to-service communication
- API keys for external services
- OAuth 2.0 for user authentication

### Network Security
- Private network for inter-service communication
- Public endpoints only for main API
- Firewall rules for service isolation

## Future Enhancements

1. **API Gateway:** Centralized routing and rate limiting
2. **Service Mesh:** Istio or Linkerd for advanced traffic management
3. **Event Bus:** Kafka or RabbitMQ for event-driven architecture
4. **GraphQL Gateway:** Unified GraphQL API
5. **gRPC:** High-performance inter-service communication

