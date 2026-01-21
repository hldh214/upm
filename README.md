# UPM - Uniqlo/GU Price Monitor

Price monitoring system for UNIQLO and GU Japan online stores. Automatically crawls product prices daily and provides a web interface to view price history and trends.

## Features

- **Daily Price Crawling**: Automatically fetches product data from UNIQLO/GU Japan APIs
- **Price History Tracking**: Records price history, tracks lowest/highest prices
- **Web Interface**: Browse products with search, filter, and sort
- **Price Charts**: Visualize price trends (30/90/180/365 days)
- **RESTful API**: Programmatic access to all data

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+ or PostgreSQL 13+

## Installation

```bash
git clone https://github.com/your-username/upm.git
cd upm
composer install
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

Run migrations and start server:

```bash
php artisan migrate
php artisan serve
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
├── Console/Commands/CrawlPrices.php
├── Http/Controllers/
│   ├── Api/ProductController.php
│   └── PageController.php
├── Models/
│   ├── Product.php
│   └── PriceHistory.php
└── Services/PriceCrawlerService.php

database/
├── factories/
└── migrations/

resources/views/
├── layouts/app.blade.php
└── products/
    ├── index.blade.php
    └── show.blade.php

routes/
├── api.php
├── console.php
└── web.php

tests/  (64 tests)
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

## Testing

```bash
php artisan test
```

## Tech Stack

- Laravel 12 / PHP 8.2
- MySQL / PostgreSQL
- Blade / Alpine.js / Tailwind CSS (CDN) / Chart.js

## License

MIT
