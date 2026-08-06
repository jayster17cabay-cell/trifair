# TriFair - Tricycle Driver Rating System

A web application for rating tricycle drivers. Passengers scan a QR code on the driver's tricycle to rate their trip and provide feedback.

## Features

- **Passenger Rating**: Scan QR code to rate drivers (1-5 stars)
- **Trip Tracking**: GPS-based trip route mapping with Leaflet.js
- **Complaint System**: Photo/video proof upload for low ratings
- **Driver Dashboard**: View ratings and respond to feedback
- **Admin Panel**: Manage drivers, view reports, review complaints
- **Superadmin**: Full system control, manage admins and drivers

## User Roles

- **Passenger** (anonymous) - Scans QR code, rates driver
- **Driver** - Views ratings, responds to feedback
- **TFRB Officer** - Manages drivers, reviews complaints, oversees TODAs
- **Superadmin** - Full system control, manages officers and drivers

## Tech Stack

- **Backend**: Laravel 8.80 (PHP 8.0.2+)
- **Database**: PostgreSQL (production), SQLite in-memory (tests)
- **Frontend**: Tailwind CSS 3, Leaflet.js, Blade templates
- **Build Tool**: Vite 4
- **CI**: GitHub Actions (lint, view compile, feature tests)
- **Hosting**: Render (free tier)

## Requirements

- PHP 8.0.2+ (extensions: pdo_pgsql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath)
- PostgreSQL 12+
- Composer
- Node.js + npm (for asset compilation)
- Apache with `mod_rewrite` enabled

## Local Development Setup

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd trifair
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install JS dependencies:
   ```bash
   npm install
   ```

4. Create `.env` file:
   ```bash
   cp .env.example .env
   ```

5. Generate application key:
   ```bash
   php artisan key:generate
   ```

6. Create database `trifair_db` in PostgreSQL, then run migrations:
   ```bash
   php artisan migrate
   ```

7. Seed default admin accounts:
   ```bash
   php artisan db:seed
   ```

8. Create storage symlink:
   ```bash
   php artisan storage:link
   ```

9. Build frontend assets:
   ```bash
   npm run build
   ```

10. Start development server:
    ```bash
    php artisan serve
    ```

## Default Accounts

After running `php artisan db:seed`, three accounts are created. Passwords come from
`.env` (`SUPERADMIN_PASSWORD`, `TFRB_OFFICER_PASSWORD`, `OPERATOR_PASSWORD`) or are
generated randomly and printed to the console — nothing hardcoded in the repo.

| Role | Email |
|------|-------|
| Superadmin | superadmin@trifair.com |
| TFRB Officer | tfrbofficer@trifair.com |
| Operator | jayster@trifair.com |

## Production Deployment

1. Set environment variables in `.env`:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   DB_CONNECTION=pgsql
   DB_DATABASE=your-db
   DB_USERNAME=your-user
   DB_PASSWORD=your-secure-password
   ```

2. Configure SMTP mail settings in `.env`:
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_ENCRYPTION=tls
   ```

3. Run production commands:
   ```bash
   composer install --optimize-autoloader --no-dev
   npm install && npm run build
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan migrate --force
   php artisan db:seed
   php artisan storage:link
   ```

4. Set file permissions (Linux):
   ```bash
   chmod -R 775 storage/ bootstrap/cache/
   chown -R www-data:www-data storage/ bootstrap/cache/
   ```

5. Configure Apache virtual host:
   ```apache
   <VirtualHost *:80>
       ServerName your-domain.com
       DocumentRoot /var/www/trifair/public
       <Directory /var/www/trifair/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

## Project Structure

```
trifair/
├── app/
│   ├── Console/          # Artisan commands
│   ├── Helpers/          # ActivityLogger helper
│   ├── Http/
│   │   ├── Controllers/  # Route controllers
│   │   └── Middleware/    # Auth & role middleware
│   ├── Models/           # Eloquent models
│   └── Providers/        # Service providers
├── config/               # Configuration files
├── database/
│   ├── migrations/       # Database migrations
│   └── seeders/          # Database seeders
├── public/               # Public assets
├── resources/
│   └── views/            # Blade templates
├── routes/               # Route definitions
└── storage/              # Uploaded files & logs
```

## License

MIT License
