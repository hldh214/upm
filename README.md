# UPM - Uniqlo/GU Price Monitor

Price monitoring system for UNIQLO and GU Japan online stores. Automatically crawls product prices daily and provides a web interface to view price history and trends.

## Features

- **Daily Price Crawling**: Automatically fetches product data from UNIQLO/GU Japan APIs
- **Price History Tracking**: Records price history, tracks lowest/highest prices
- **User Authentication**: Secure registration, login, password reset, and email verification
- **Watchlist & Notifications**: Track favorite products and get notified of price changes
- **Web Interface**: Modern SPA built with React and Inertia.js - browse products with search, filter, and sort
- **Price Charts**: Visualize price trends (30/90/180/365 days)
- **RESTful API**: Programmatic access to all data
- **Multi-language Support**: English and Japanese

## Requirements

- PHP 8.2+
- Composer
- Node.js & Yarn (or npm)
- MySQL 8.0+ or PostgreSQL 13+

## Installation

### Quick Setup

```bash
git clone https://github.com/your-username/upm.git
cd upm
composer setup
```

### Manual Setup

```bash
git clone https://github.com/your-username/upm.git
cd upm
composer install
yarn install
cp .env.example .env
php artisan key:generate
```

Configure database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=upm
DB_USERNAME=root
DB_PASSWORD=your_password
```

Run migrations and build frontend:

```bash
php artisan migrate
yarn build
```

### Development Mode

Start all services (server, queue, logs, and Vite) with one command:

```bash
composer dev
```

Or start services individually:

```bash
php artisan serve        # Server (http://localhost:8000)
yarn dev                 # Vite dev server with HMR
php artisan queue:listen # Queue worker
php artisan pail         # View logs
```

## Usage

### Crawl Prices

```bash
# Crawl all brands
php artisan upm:crawl

# Crawl specific brand
php artisan upm:crawl --brand=uniqlo
php artisan upm:crawl --brand=gu
```

### Scheduled Task

Configured to run daily at 4:00 AM. Enable with cron:

```bash
* * * * * cd /path/to/upm && php artisan schedule:run >> /dev/null 2>&1
```

## API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products` | List products (paginated) |
| GET | `/api/products/stats` | Statistics |
| GET | `/api/products/{id}` | Product details |
| GET | `/api/products/{id}/history` | Price history |

### Query Parameters

**GET /api/products**

| Parameter | Type | Description |
|-----------|------|-------------|
| `q` | string | Search by name or product ID |
| `brand` | string | Filter: `uniqlo`, `gu` |
| `gender` | string | Filter: `MEN`, `WOMEN`, `KIDS`, `BABY`, `UNISEX` |
| `sort` | string | Sort: `price_asc`, `price_desc`, `name`, `updated` |
| `page` | int | Page number |
| `per_page` | int | Items per page (max 100) |

**GET /api/products/{id}/history**

| Parameter | Type | Description |
|-----------|------|-------------|
| `days` | int | Number of days (default: 90, max: 365) |

## Project Structure

```
app/
├── Console/Commands/
│   ├── CrawlPrices.php
│   ├── CleanDuplicatePriceHistory.php
│   └── FixProductPriceStats.php
├── Http/Controllers/
│   ├── Api/ProductController.php
│   └── ProductController.php (Inertia)
├── Models/
│   ├── Product.php
│   ├── PriceHistory.php
│   ├── User.php
│   ├── Watchlist.php
│   └── Notification.php
└── Services/PriceCrawlerService.php

database/
├── factories/
└── migrations/

resources/
├── js/
│   ├── Components/       # Reusable React components
│   ├── Layouts/         # Layout components (AppLayout, GuestLayout)
│   ├── Pages/           # Page components
│   │   ├── Auth/       # Login, Register, ForgotPassword, etc.
│   │   ├── Products/   # Index, Show
│   │   ├── Watchlist/  # Index
│   │   └── Profile/    # Edit
│   └── app.jsx         # Main entry point
├── css/
│   └── app.css         # Tailwind CSS
└── views/
    └── app.blade.php   # Root Inertia template

routes/
├── api.php
├── console.php
└── web.php

tests/  (70+ tests)
├── Feature/
└── Unit/
```

## Database Schema

### products

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| product_id | varchar(20) | Product ID from API |
| price_group | varchar(10) | Price group |
| name | varchar(500) | Product name |
| brand | varchar(50) | Brand name |
| gender | varchar(20) | Gender category |
| image_url | varchar(500) | Image URL |
| current_price | unsigned int | Current price (JPY) |
| lowest_price | unsigned int | Historical lowest price |
| highest_price | unsigned int | Historical highest price |
| timestamps | | created_at, updated_at |

**Indexes:** `unique(product_id, price_group)`

### price_histories

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| product_id | unsigned bigint | Product ID |
| price | unsigned int | Price (JPY) |
| timestamps | | created_at, updated_at |

**Indexes:** `index(product_id)`

**Note:** Price history only stores records when prices change, not daily snapshots.

### users

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar(255) | User name |
| email | varchar(255) | Email (unique) |
| password | varchar(255) | Hashed password |
| email_verified_at | timestamp | Email verification time |
| timestamps | | created_at, updated_at |

### watchlists

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | unsigned bigint | User ID |
| product_id | unsigned bigint | Product ID |
| notify_on_price_drop | boolean | Enable notifications |
| timestamps | | created_at, updated_at |

**Indexes:** `unique(user_id, product_id)`

### notifications

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | unsigned bigint | User ID |
| type | varchar(255) | Notification type |
| data | json | Notification data |
| read_at | timestamp | Read time (nullable) |
| timestamps | | created_at, updated_at |

## Testing

```bash
# Run all tests
php artisan test
composer test            # Alternative (clears config first)

# Run specific test file
php artisan test tests/Feature/Api/ProductApiTest.php

# Run with filter
php artisan test --filter testIndexReturnsProductList

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## Code Style

Format code with Laravel Pint (PSR-12 standard):

```bash
./vendor/bin/pint              # Format all files
./vendor/bin/pint --test       # Check without fixing
./vendor/bin/pint app/Models   # Format specific directory
```

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+, Laravel Fortify
- **Frontend:** React 18, Inertia.js 2.0, Vite 6
- **Styling:** Tailwind CSS 3.4
- **Charts:** Chart.js 4.5
- **Database:** MySQL 8.0+ / PostgreSQL 13+
- **Testing:** PHPUnit 11.5

## License

MIT
