# One Time Password Sharing App

A secure, self-destructing password sharing application built with Vue.js and Laravel. Share passwords securely with end-to-end encryption - passwords are encrypted in the browser before being sent to the server and can only be viewed once before being permanently deleted.

## Features

- 🔒 End-to-end encryption using AES-256-GCM
- 💥 Self-destructing passwords - viewed only once then deleted
- ⏰ Configurable expiry times (5 minutes to 30 days)
- 🔑 Client-side encryption/decryption using Web Crypto API
- 📋 Easy copy-to-clipboard functionality
- 🎨 Clean, responsive user interface
- ♿ Accessibility features included

## Security Features

- Passwords are encrypted in the browser before transmission
- Encryption keys never leave the client
- Passwords are stored encrypted and deleted after first view
- Uses secure AES-256-GCM encryption
- Implements URL-safe Base64 encoding for keys and tokens
- Automatic expiry of unused passwords

## Technical Stack

- **Frontend**: Vue 3 with Composition API
- **Backend**: Laravel
- **Database**: MySQL/PostgreSQL
- **Encryption**: Web Crypto API (AES-256-GCM)
- **HTTP Client**: Axios
- **Routing**: Vue Router

## Installation

1. Clone the repository:
```bash
git clone https://github.com/patthewebrat/otp.git
cd secure-password-share
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install JavaScript dependencies:
```bash
npm install
```

4. Copy the environment file and configure your database:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Run database migrations:
```bash
php artisan migrate
```

7. Build frontend assets:
```bash
npm run build
```

## Database Schema

The application requires a single table for storing one-time passwords:

```sql
CREATE TABLE otps (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    token varchar(255) NOT NULL,
    password text NOT NULL,
    iv varchar(255) NOT NULL,
    expires_at timestamp NOT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY otps_token_unique (token)
);
```

## Development

For local development:

1. Start the Laravel development server:
```bash
php artisan serve
```

2. Start the Vite development server:
```bash
npm run dev
```

## Production Deployment

1. Set your production environment variables in `.env`
2. Optimize Laravel:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Build frontend assets:
```bash
npm run build
```

## Security Considerations

- Always use HTTPS in production
- Regularly update dependencies
- Configure appropriate session timeouts
- Set up proper rate limiting
- Monitor server logs for suspicious activity
- Ensure proper server hardening
- Implement CSP headers

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE.md file for details.

## Acknowledgments

- Web Crypto API for secure client-side encryption
- Vue.js team for the excellent framework
- Laravel team for the robust backend framework

## Support

For support, please open an issue in the GitHub repository or contact the maintainers.
