# iBoat Marine Platform - Setup Guide

## Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 8.0 or higher
- Node.js 18+ and NPM
- Git

## Installation Steps

### 1. Install PHP Dependencies

```bash
composer install
```

### 2. Install Node Dependencies

```bash
npm install
```

### 3. Environment Configuration

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

Edit `.env` and configure:

- **Database**: Set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- **Stripe**: Add your Stripe keys (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`)
- **Google Maps**: Add `GOOGLE_MAPS_API_KEY` (optional, for enhanced location features)
- **App URL**: Set `APP_URL` to your domain

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Create Storage Link

```bash
php artisan storage:link
```

### 6. Run Migrations

```bash
php artisan migrate
```

### 7. Seed Database (Optional)

```bash
php artisan db:seed
```

This will create:
- Admin user (admin@iboat.com / password)
- Sample captain (captain@iboat.com / password)
- Sample customer (customer@iboat.com / password)
- Sample vendor (vendor@iboat.com / password)
- Sample boats and equipment

### 8. Build Frontend Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

### 9. Start Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Testing the API

### Register a New User

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "customer"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@iboat.com",
    "password": "password"
  }'
```

### Get Boats (Public)

```bash
curl http://localhost:8000/api/boats
```

### Create a Boat (Requires Authentication)

```bash
curl -X POST http://localhost:8000/api/boats \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Yacht",
    "description": "A beautiful yacht",
    "type": "yacht",
    "capacity": 10,
    "hourly_rate": 500,
    "daily_rate": 4000
  }'
```

## Project Structure

```
iboat-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # API Controllers
│   │   ├── Middleware/      # Custom Middleware
│   │   └── Requests/        # Form Validation Requests
│   ├── Models/              # Eloquent Models
│   └── Services/            # Business Logic Services
├── database/
│   ├── migrations/          # Database Migrations
│   └── seeders/             # Database Seeders
├── resources/
│   ├── js/                  # React Components
│   │   ├── components/      # Reusable Components
│   │   └── pages/           # Page Components
│   └── views/               # Blade Templates
├── routes/
│   ├── api.php              # API Routes
│   └── web.php              # Web Routes
└── public/                  # Public Assets
```

## Key Features Implemented

✅ Multi-role authentication (Customer, Captain, Vendor, Admin)
✅ Boat CRUD operations with search and filtering
✅ Booking system with availability checking
✅ Equipment rental system
✅ Payment processing with Stripe
✅ Review and rating system
✅ Location tracking for boats
✅ Real-time availability management
✅ Image upload handling

## Next Steps

1. **Configure Stripe**: Set up your Stripe account and add keys to `.env`
2. **Set up Storage**: Configure file storage (local or S3)
3. **Configure Email**: Set up mail driver for notifications
4. **Add Real-time**: Configure Pusher/Laravel Echo for real-time updates
5. **Deploy**: Set up production environment

## Troubleshooting

### Migration Errors
- Ensure database exists and credentials are correct
- Check MySQL version compatibility

### Storage Link Issues
- Run `php artisan storage:link` if images aren't loading
- Check `storage/app/public` directory permissions

### API Authentication Issues
- Verify Sanctum is properly configured
- Check CORS settings if using separate frontend domain
- Ensure token is included in Authorization header

### Frontend Build Issues
- Clear node_modules and reinstall: `rm -rf node_modules && npm install`
- Check Node.js version compatibility

## Support

For issues or questions, refer to:
- Laravel Documentation: https://laravel.com/docs
- React Documentation: https://react.dev
- Stripe Documentation: https://stripe.com/docs

