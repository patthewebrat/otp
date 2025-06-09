# CLAUDE.md - OTP & File Sharing App

## Project Overview
This is a secure, self-destructing password and file sharing application built with Vue.js and Laravel. It features end-to-end encryption using AES-256-GCM where content is encrypted in the browser before being sent to the server.

## Tech Stack
- **Frontend**: Vue 3 with Composition API, Vue Router
- **Backend**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL/PostgreSQL
- **File Storage**: Amazon S3 with Laravel Flysystem
- **Build Tools**: Vite, Sass
- **Testing**: PHPUnit

## Development Commands

### PHP/Laravel Commands
```bash
# Install dependencies
composer install

# Run migrations
php artisan migrate

# Start Laravel server
php artisan serve

# Run tests
php artisan test

# Code formatting
./vendor/bin/pint

# Clear expired content
php artisan otps:delete-expired
php artisan files:delete-expired

# Laravel optimization (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### JavaScript/Vue Commands
```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build
```

### Testing Commands
```bash
# Run PHP tests
php artisan test

# Run specific test
php artisan test --filter=TestName
```

## Project Structure

### Backend (Laravel)
- `app/Http/Controllers/` - API controllers for OTP and file sharing
- `app/Models/` - Eloquent models (OTP, SharedFile, User)
- `app/Console/Commands/` - Artisan commands for cleanup
- `database/migrations/` - Database schema
- `routes/api.php` - API routes
- `config/` - Configuration files

### Frontend (Vue)
- `resources/js/components/` - Vue components
- `resources/js/router.js` - Vue Router configuration
- `resources/scss/` - Sass stylesheets
- `resources/views/` - Blade templates

## Key Features
- Client-side encryption using Web Crypto API
- Self-destructing content (view once, then delete)
- Configurable expiry times
- File upload with encryption
- S3 storage integration
- Automatic cleanup of expired content
- IP-based file upload restrictions (configurable whitelist)

## Environment Setup
Requires HTTPS for WebCrypto API functionality. Configure S3 credentials and optional IP whitelist in `.env`:
```
AWS_ACCESS_KEY_ID=your-key-id
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=your-region
AWS_BUCKET=your-bucket-name

# Optional: Comma-separated list of IPs allowed to upload files
# If empty, all IPs can upload files
FILE_UPLOAD_WHITELIST=192.168.1.100,10.0.0.50
```

## Important Notes
- Uses ddev for local development
- Requires scheduled tasks for content cleanup
- All encryption/decryption happens client-side
- Content is permanently deleted after first access