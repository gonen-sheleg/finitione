# Finitione Order Management System

A multi-vendor e-commerce order processing system built with Laravel 12, featuring intelligent price comparison, extensible discount rules, fast order processing, and automated vendor notifications.

## Features

- 🛒 Multi-vendor order processing with automatic best price selection
- 💰 Extensible discount engine with pluggable rules
- 📦 Quantity-based discounts (5%-15% based on order size)
- 👤 Loyalty customer discounts (5%-15% based on order history)
- 🏷️ Category-based discounts (5%-11% for specific categories)
- ⚡ Fast order processing with optimized performance
- 📋 Sub-order creation per vendor for easy fulfillment
- 📧 Automated vendor notifications via queued jobs
- 🔐 Sanctum-based API authentication
- 🧪 Comprehensive test suite with Pest

## What's Included

This repository contains order management system with:

- ✅ **Full source code** with organized service layer architecture
- ✅ **RESTful API endpoints** for authentication and order operations
- ✅ **Price Engine** that finds the best vendor price for each product
- ✅ **Discount Engine** with auto-discovery of discount rules
- ✅ **Vendor Order Processor** that groups items by vendor
- ✅ **Background job processing** for vendor notifications
- ✅ **Docker configuration** via Laravel Sail for easy local setup
- ✅ **MySQL and Redis** integration for data persistence and caching
- ✅ **Pest test suite** with discount and vendor grouping tests

## Requirements

- **Docker Desktop** (includes Docker Compose)
  - [Download for Mac](https://docs.docker.com/desktop/install/mac-install/)
  - [Download for Windows](https://docs.docker.com/desktop/install/windows-install/)
  - [Download for Linux](https://docs.docker.com/desktop/install/linux-install/)
- **Git**

> **Note**: No need to install PHP, Composer, MySQL, or Redis locally. Everything runs inside Docker containers via Laravel Sail.

## Installation & Running

### Quick Start

```bash
git clone https://github.com/gonen-sheleg/finitione.git
cd finitione
cp .env.example .env
docker run --rm -v $(pwd):/app composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
./vendor/bin/sail artisan queue:work
```

## Detailed Installation Guide

### Step 1: Clone and Setup Environment

```bash
git clone https://github.com/gonen-sheleg/finitione.git
cd finitione
cp .env.example .env
```

### Step 2: Install Composer Dependencies

Since Sail isn't available yet, use Docker to install Composer dependencies first:

```bash
docker run --rm -v $(pwd):/app composer install --ignore-platform-reqs
```

### Step 3: Start Docker Containers

```bash
./vendor/bin/sail up -d
```

This starts MySQL, Redis, and the Laravel app containers. First-time setup may take 5-10 minutes to download Docker images.

**Optional**: Create a shell alias for convenience:

```bash
alias sail='./vendor/bin/sail'
```

### Step 4: Configure Application

```bash
sail artisan key:generate
sail artisan migrate
sail artisan db:seed
```


### Step 5: Run Development Server

For the best development experience with logs, queue worker, and Vite:

```bash
sail up -d
sail artisan queue:work
```

## Running Tests

```bash
sail artisan test
```

## API Usage Examples

The API provides RESTful endpoints for authentication and order operations.

### Authentication

#### Login

```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'

# Response:
# {
#   "user": { "id": 1, "name": "John Doe", "email": "user@example.com" },
#   "token": "1|abc123..."
# }
```

#### Logout

```bash
curl -X POST http://localhost/api/logout \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response:
# { "message": "Successfully logged out." }
```

### Create Order

```bash
curl -X POST http://localhost/api/order \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "cart": [
      {"sku": "PROD-001", "quantity": 15},
      {"sku": "PROD-002", "quantity": 5}
    ]
  }'

# Response:
# [
#   {
#     "sku": "PROD-001",
#     "product": "Example Product 1",
#     "vendor": "Example Vendor 1",
#     "quantity": 15,
#     "price": 100.00,
#     "price_after_discount": 95.00
#   },
#   {
#     "sku": "PROD-002",
#     "product": "Example Product 2",
#     "vendor": "Example Vendor 2",
#     "quantity": 5,
#     "price": 120.00,
#     "price_after_discount": 120.00
#   }
# ]
```

**Validation**: 
- Cart must have at least one item
- SKU must exist in the products table
- Quantity must be at least 1

## Discount Rules

### Quantity Discounts

| Quantity | Discount |
|----------|----------|
| 10-19    | 5%       |
| 20-29    | 7%       |
| 30-39    | 9%       |
| 40-49    | 11%      |
| 50+      | 15%      |

### Loyalty Customer Discounts

Based on orders in the last 6 months:

| Orders | Discount |
|--------|----------|
| 6-9    | 5%       |
| 10-19  | 10%      |
| 20-29  | 12%      |
| 30+    | 15%      |

### Category Discounts

| Category ID | Discount |
|-------------|----------|
| 2           | 5%       |
| 5           | 7%       |
| 7           | 9%       |
| 9           | 11%      |

**Note**: All applicable discounts are combined additively. For example, a loyal customer (10% discount) ordering 15 items (5% discount) of a category 2 product (5% discount) receives a total 20% discount.

## How It Works

### Order Flow

1. **User submits cart** with product SKUs and quantities
2. **Validation** ensures all products exist and quantities are valid
3. **Price Engine** finds the cheapest vendor for each product
4. **Discount Engine** applies all applicable discount rules
5. **Order created** with total prices before and after discounts
6. **Items grouped by vendor** using VendorOrderProcessor
7. **Sub-orders created** for each vendor
8. **Vendor notifications** dispatched as background jobs
9. **Response returned** with detailed pricing information

### Adding New Discount Rules

1. Create a new class in `app/Services/Discount/Rules/`
2. Implement `DiscountRuleInterface`
3. Define `isApplicable()` to check if rule applies
4. Define `apply()` to return discount percentage (0.0 to 1.0)

Example:

```php
class HolidayDiscountRule implements DiscountRuleInterface
{
    public function apply(ProductVendor $productVendor, int $quantity): float
    {
        return 0.10; // 10% holiday discount
    }

    public function isApplicable(ProductVendor $productVendor, int $quantity): bool
    {
        return now()->month === 12; // Only in December
    }
}
```

The rule will be automatically discovered and applied!

## Project Structure

```
finitione/
├── app/
│   ├── Exceptions/
│   │   └── InsufficientStockException.php   # Stock validation exception
│   ├── Facades/
│   │   ├── DiscountEngine.php               # Discount facade
│   │   ├── OrderProcessor.php               # Order processing facade
│   │   ├── PriceEngine.php                  # Price comparison facade
│   │   └── VendorOrderProcessor.php         # Vendor grouping facade
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php           # Login/Logout endpoints
│   │   │   └── OrderController.php          # Order creation endpoint
│   │   └── Middleware/
│   │       └── AddRequestContext.php        # Request context middleware
│   ├── Jobs/
│   │   └── NotifyVendorJob.php              # Vendor notification job
│   ├── Models/
│   │   ├── Order.php                        # Main order model
│   │   ├── OrderItem.php                    # Order line items
│   │   ├── Product.php                      # Product catalog
│   │   ├── ProductVendor.php                # Vendor-product pricing
│   │   ├── SubOrder.php                     # Per-vendor sub-orders
│   │   ├── User.php                         # Customer model
│   │   └── Vendor.php                       # Vendor model
│   └── Services/
│       ├── Discount/
│       │   ├── DiscountEngine.php           # Rule orchestration
│       │   ├── DiscountRuleInterface.php    # Rule contract
│       │   └── Rules/
│       │       ├── CategoryDiscountRule.php
│       │       ├── LoyaltyCustomerDiscountRule.php
│       │       └── QuantityDiscountRule.php
│       ├── OrderProcessor.php               # Cart processing logic
│       ├── PriceEngine.php                  # Best price finder
│       └── VendorOrderProcessor.php         # Vendor grouping logic
├── database/
│   └── migrations/                          # Database schema
├── routes/
│   └── api.php                              # API route definitions
├── tests/
│   └── Unit/
│       ├── DiscountTest.php                 # Discount rule tests
│       └── VendorGroupingTest.php           # Vendor grouping tests
└── compose.yaml                             # Docker configuration
```

## Tech Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL 8.4
- **Cache/Queue**: Redis
- **Authentication**: Laravel Sanctum
- **Testing**: Pest PHP
- **Build Tool**: Vite
- **Container**: Docker (Laravel Sail)

## License

MIT License - feel free to use for learning and interviews.
