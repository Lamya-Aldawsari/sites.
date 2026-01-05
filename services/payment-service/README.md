# Payment Service

Microservice responsible for payment processing.

## Responsibilities
- Process payments via Stripe
- Handle payment intents
- Process refunds
- Manage transaction history

## API Endpoints

- `POST /api/payments/intent` - Create payment intent
- `POST /api/payments/confirm` - Confirm payment
- `POST /api/payments/refund` - Process refund
- `GET /api/payments/transactions` - Get transaction history

## Environment Variables

- `STRIPE_KEY` - Stripe public key
- `STRIPE_SECRET` - Stripe secret key
- `STRIPE_WEBHOOK_SECRET` - Stripe webhook secret
- `DB_HOST` - PostgreSQL host
- `REDIS_HOST` - Redis host

