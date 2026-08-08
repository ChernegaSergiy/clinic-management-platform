# Deployment Guide

This document provides step-by-step instructions for deploying the **MedCore Clinic Management Platform** to a production environment using **Nginx**, **PHP-FPM**, and **MySQL/MariaDB**.

## 1. Prerequisites

Ensure your production server has the following software installed:
- **OS**: Ubuntu 22.04 LTS / 24.04 LTS (or equivalent Linux distribution)
- **Web Server**: Nginx
- **PHP**: PHP 8.2 or higher along with the following extensions:
  - `php-fpm`, `php-mysql`, `php-xml`, `php-mbstring`, `php-curl`, `php-intl`, `php-zip`, `php-apcu`
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Tools**: Git, Composer

## 2. Prepare the Application Directory

Create a directory for the application and adjust ownership:

```bash
sudo mkdir -p /var/www/medcore
sudo chown -R $USER:www-data /var/www/medcore
cd /var/www/medcore
```

Clone the repository:
```bash
git clone https://github.com/medcore-ua/medcore.git .
```

## 3. Configure the Environment

Copy the example environment file and update it with your production settings:

```bash
cp .env .env.local
```

Edit `.env.local` with your favorite text editor (e.g., `nano .env.local`) and set the following variables:
```dotenv
APP_ENV=prod
APP_SECRET=generate_a_random_secure_secret_here

# Configure your database connection
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0&charset=utf8mb4"
```

## 4. Install Dependencies

Install PHP dependencies optimized for production:

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

## 5. Database Setup

Ensure your database server is running and you have created the empty database specified in your `DATABASE_URL`.

Run the Doctrine migrations to set up the database schema:
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

## 6. Cache and Permissions

Clear and warm up the Symfony cache for the production environment:
```bash
php bin/console cache:clear
php bin/console cache:warmup
```

Ensure the web server user (`www-data`) has proper write permissions to the `var/` directory:
```bash
sudo chown -R $USER:www-data var/
sudo chmod -R 775 var/
```
> [!TIP]
> For a more robust permission setup, consider using ACLs:
> ```bash
> sudo setfacl -R -m u:www-data:rwX -m u:$(whoami):rwX var
> ```

## 7. Nginx Configuration

Create a new Nginx server block configuration:

```bash
sudo nano /etc/nginx/sites-available/medcore
```

Add the following configuration (replace `medcore.example.com` with your actual domain):

```nginx
server {
    listen 80;
    server_name medcore.example.com;
    root /var/www/medcore/public;

    error_log /var/log/nginx/medcore_error.log;
    access_log /var/log/nginx/medcore_access.log;

    location / {
        # try to serve file directly, fallback to index.php
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        # Replace the socket path with your actual PHP-FPM socket
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    # return 404 for all other php files not matching the front controller
    location ~ \.php$ {
        return 404;
    }
}
```

Enable the configuration and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/medcore /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 8. Ongoing Maintenance

When deploying updates in the future, follow this sequence:
1. `git pull origin main`
2. `composer install --no-dev --optimize-autoloader`
3. `php bin/console doctrine:migrations:migrate --no-interaction`
4. `php bin/console cache:clear`
