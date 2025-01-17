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

## Installation and configuration

1. Clone the repository:
```bash
git clone https://github.com/patthewebrat/otp.git
cd otp
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

Note - WebCrypto will not function without HTTPS, as such you will require HTTPS in all environments.

## Development

The project is set up to use ddev, or you can use the built-in Laravel server.

### ddev

With ddev you shouldn't have to edit .env.

1. Start ddev
```
ddev start
```

2. Run database migrations:
```bash
ddev exec php artisan migrate
```

3. Start the Vite development server:
```bash
npm run dev
```

### Laravel server

1. Spin up your database and enter the details into `.env`.

2. Start the Laravel development server:
```bash
php artisan serve
```

2. Run database migrations:
```bash
php artisan migrate
```

3. Start the Vite development server:
```bash
npm run dev
```

## Production Deployment

1. Set your production environment variables in `.env`

2. Optimise Laravel:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Build your frontend assets:
```bash
npm run build
```

4. Configure cron to run this command every minute (or at least every 5 minute), which will clear out expired passwords:
```
php artisan schedule:run
```

## Customisation for Your Project

If you wish to customise this application for your own use, we recommend creating a fork of this repository. Forking allows you to:
- Maintain your own changes without affecting the original repository.
- Easily merge updates from the core repository into your custom version.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Demo

You can view a production version of this app here - https://otp.indulge.digital/

## License

This project is licensed under the MIT License - see the LICENSE.md file for details.

## Acknowledgments

- Web Crypto API for secure client-side encryption
- Vue.js team for the excellent framework
- Laravel team for the robust backend framework

## Support

For support, please open an issue in the GitHub repository or contact the maintainers.
