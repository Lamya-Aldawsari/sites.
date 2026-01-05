# Technical Stack Setup Guide

## Prerequisites

- PHP 8.1 or higher
- Composer
- PostgreSQL 15+
- Redis 7+
- Node.js 18+ and NPM
- Docker & Docker Compose (optional, for containerized setup)

## Database Setup (PostgreSQL)

### Install PostgreSQL

**macOS:**
```bash
brew install postgresql@15
brew services start postgresql@15
```

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install postgresql-15 postgresql-contrib-15
sudo systemctl start postgresql
```

**Windows:**
Download and install from [PostgreSQL Downloads](https://www.postgresql.org/download/windows/)

### Create Database

```bash
# Connect to PostgreSQL
psql -U postgres

# Create database
CREATE DATABASE iboat_marine;

# Create user (optional)
CREATE USER iboat_user WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE iboat_marine TO iboat_user;
```

## Redis Setup

### Install Redis

**macOS:**
```bash
brew install redis
brew services start redis
```

**Ubuntu/Debian:**
```bash
sudo apt-get install redis-server
sudo systemctl start redis-server
```

**Windows:**
Download from [Redis Windows](https://github.com/microsoftarchive/redis/releases) or use WSL

### Verify Redis

```bash
redis-cli ping
# Should return: PONG
```

## Environment Configuration

### Update .env File

```env
# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=iboat_marine
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_CLIENT=phpredis

# Cache & Session (Redis)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis Databases
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3
```

## Install Dependencies

### PHP Dependencies

```bash
composer install
```

This will install:
- Laravel framework
- PostgreSQL driver (via PDO)
- Predis (Redis client)
- Stripe SDK
- Other required packages

### Node Dependencies

```bash
npm install
```

This will install:
- React 18
- React Router
- Tailwind CSS
- Vite
- Other frontend dependencies

## Database Migration

### Run Migrations

```bash
php artisan migrate
```

### Seed Database (Optional)

```bash
php artisan db:seed
```

## Redis Configuration

### Test Redis Connection

```bash
php artisan tinker
>>> Redis::ping()
# Should return: "PONG"
```

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Docker Setup (Alternative)

### Using Docker Compose

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

This will start:
- PostgreSQL (port 5432)
- Redis (port 6379)
- Main API Service (port 8000)
- Booking Service (port 8001)
- Payment Service (port 8002)
- Notification Service (port 8003)

## Verify Installation

### Check Database Connection

```bash
php artisan tinker
>>> DB::connection()->getPdo();
# Should return PDO object
```

### Check Redis Connection

```bash
php artisan tinker
>>> Cache::store('redis')->put('test', 'value', 60);
>>> Cache::store('redis')->get('test');
# Should return: "value"
```

### Start Development Server

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev
```

Visit: `http://localhost:8000`

## PostgreSQL-Specific Notes

### Migrations Compatibility

All migrations are PostgreSQL-compatible. Key differences from MySQL:
- Uses `text` instead of `varchar(255)` for longer fields
- Uses `json` type for JSON columns
- Uses `timestamp` with timezone support
- Indexes are created automatically for foreign keys

### Common PostgreSQL Commands

```sql
-- List databases
\l

-- Connect to database
\c iboat_marine

-- List tables
\dt

-- Describe table
\d users

-- View table data
SELECT * FROM users LIMIT 10;
```

## Redis Usage Examples

### Caching in Code

```php
use Illuminate\Support\Facades\Cache;

// Cache for 1 hour
Cache::put('key', 'value', 3600);

// Get cached value
$value = Cache::get('key');

// Cache forever
Cache::forever('key', 'value');

// Remove from cache
Cache::forget('key');
```

### Session Storage

Sessions are automatically stored in Redis when `SESSION_DRIVER=redis` is set.

### Queue Management

```bash
# Start queue worker
php artisan queue:work redis

# Process failed jobs
php artisan queue:retry all
```

## Troubleshooting

### PostgreSQL Connection Issues

1. Check PostgreSQL is running:
   ```bash
   sudo systemctl status postgresql
   ```

2. Verify credentials in `.env`

3. Check PostgreSQL logs:
   ```bash
   tail -f /var/log/postgresql/postgresql-15-main.log
   ```

### Redis Connection Issues

1. Check Redis is running:
   ```bash
   redis-cli ping
   ```

2. Verify Redis configuration:
   ```bash
   redis-cli CONFIG GET "*"
   ```

3. Check firewall rules for port 6379

### Migration Issues

If migrations fail:
```bash
# Reset database (WARNING: Deletes all data)
php artisan migrate:fresh

# Or rollback and re-run
php artisan migrate:rollback
php artisan migrate
```

## Performance Tuning

### PostgreSQL

```sql
-- Check connection count
SELECT count(*) FROM pg_stat_activity;

-- Check database size
SELECT pg_size_pretty(pg_database_size('iboat_marine'));

-- Analyze tables
ANALYZE;
```

### Redis

```bash
# Check memory usage
redis-cli INFO memory

# Check connected clients
redis-cli INFO clients

# Monitor commands
redis-cli MONITOR
```

## Production Considerations

### Database
- Enable connection pooling (PgBouncer)
- Set up read replicas
- Configure automated backups
- Monitor query performance

### Redis
- Configure Redis Sentinel for high availability
- Set up Redis Cluster for horizontal scaling
- Configure memory limits
- Enable persistence (AOF/RDB)

### Application
- Use environment-specific configurations
- Enable OPcache for PHP
- Configure queue workers
- Set up monitoring and logging

## Next Steps

1. Configure Stripe keys for payment processing
2. Set up email service (SMTP or service like Mailgun)
3. Configure Google Maps API for location services
4. Set up monitoring (Sentry, New Relic, etc.)
5. Configure CI/CD pipeline
6. Set up production environment

