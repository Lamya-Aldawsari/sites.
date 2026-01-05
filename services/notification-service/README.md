# Notification Service

Microservice responsible for sending notifications.

## Responsibilities
- Send email notifications
- Send SMS notifications
- Send push notifications
- Manage notification queue

## API Endpoints

- `POST /api/notifications/send` - Send notification
- `GET /api/notifications` - List notifications
- `PUT /api/notifications/{id}/read` - Mark as read

## Environment Variables

- `MAIL_MAILER` - Mail driver
- `MAIL_HOST` - SMTP host
- `MAIL_PORT` - SMTP port
- `MAIL_USERNAME` - SMTP username
- `MAIL_PASSWORD` - SMTP password
- `REDIS_HOST` - Redis host (for queue)

