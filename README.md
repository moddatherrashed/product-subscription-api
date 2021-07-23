# Product Subscription System (Lumen/php)

[![Build Status](https://travis-ci.org/laravel/lumen-framework.svg)](https://travis-ci.org/laravel/lumen-framework)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel/framework)](https://packagist.org/packages/laravel/lumen-framework)
[![Latest Stable Version](https://img.shields.io/packagist/v/laravel/framework)](https://packagist.org/packages/laravel/lumen-framework)
[![License](https://img.shields.io/packagist/l/laravel/framework)](https://packagist.org/packages/laravel/lumen-framework)

Laravel Lumen is a stunningly fast PHP micro-framework for building web applications with expressive, elegant syntax.

This project is using:
- Firebase: to send notification to the mobile app users
- Stripe: to handle the payment system 
- tymon/jwt-auth: to handle the authentication

## Documentation

Documentation for the framework can be found on the [Lumen website](https://lumen.laravel.com/docs).

## Installation
For running this example, you need to install libraries using composer:

### Using Composer
You can install the library via [Composer](https://getcomposer.org/). If you don't already have Composer installed, first install it by following one of these instructions depends on your OS of choice:
* [Composer installation instruction for Windows](https://getcomposer.org/doc/00-intro.md#installation-windows)
* [Composer installation instruction for Mac OS X and Linux](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)

After composer is installed, Then run the following command to install the libraries:

```
php composer.phar install
```
Please see configuration section below for configuring the project.

## Configuration

your configuration should be in ```.env``` file, which has to contain the following:

```
APP_NAME=product_subscription_system
APP_ENV=local
APP_KEY=app-key
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=CET

LOG_CHANNEL=stack
LOG_SLACK_WEBHOOK_URL=

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=db-name
DB_USERNAME=db-username
DB_PASSWORD=db-password

CACHE_DRIVER=file
QUEUE_CONNECTION=sync

CASHIER_MODEL=App\Models\Subscription

JWT_SECRET=your-generated-jwt-secret
JWT_TTL=525600
#stripe info
STRIPE_KEY=your-secr-public-key
STRIPE_SECRET=your-secret-key
CASHIER_CURRENCY=chf
CASHIER_CURRENCY_LOCALE=de_CH
CASHIER_LOGGER=stack
#firebase server key
FIREBASE_SERVER_KEY=your-key

```

### Runing the project

using ```php -S localhost:8000 -t public```
