# Rediloquent

Fast Redis-powered model persistence for Laravel CRUD applications.

[![Tests](https://github.com/digitlimit/rediloquent/actions/workflows/tests.yml/badge.svg)](https://github.com/digitlimit/rediloquent/actions/workflows/tests.yml)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)

---

## Introduction

Rediloquent gives you an **Eloquent-style developer experience** for storing and querying models in **Redis** — no SQL database required. It is designed for high-throughput CRUD applications where sub-millisecond persistence matters and relational features are not needed.

```php
use Digitlimit\Rediloquent\Model;

class User extends Model
{
    protected string $key = 'users';

    protected array $fillable = [
        'name',
        'email',
    ];
}

// Create
$user = User::create(['name' => 'Emeka', 'email' => 'emeka@example.com']);

// Read
$user = User::find($user->id);

// Update
$user->update(['name' => 'Emeka Mbah']);

// Delete
$user->delete();

// All records
$users = User::all();
```

---

## Requirements

| Requirement | Version       |
|-------------|---------------|
| PHP         | 8.2 or higher |
| Laravel     | 10, 11, or 12 |
| Redis       | Any version supported by Laravel |

---

## Installation

Install the package via Composer:

```bash
composer require digitlimit/rediloquent
```

### Publish the configuration file

```bash
php artisan vendor:publish --tag=rediloquent-config
```

This creates `config/rediloquent.php` in your application.

---

## Configuration

```php
// config/rediloquent.php

return [
    // The Redis connection defined in config/database.php to use.
    'connection' => env('REDILOQUENT_CONNECTION', 'default'),

    // A global key prefix to avoid collisions in shared Redis instances.
    'prefix' => env('REDILOQUENT_PREFIX', 'rediloquent'),
];
```

Add the corresponding environment variables to your `.env` file if you need non-default values:

```env
REDILOQUENT_CONNECTION=default
REDILOQUENT_PREFIX=myapp
```

---

## Usage

### Defining a Model

Extend `Digitlimit\Rediloquent\Model` and define:

- **`$key`** — the Redis key segment used to namespace records (e.g. `"users"`). Defaults to the snake_case class name.
- **`$fillable`** — attributes that may be mass-assigned. Leave empty to allow all attributes.

```php
namespace App\Models;

use Digitlimit\Rediloquent\Model;

class Product extends Model
{
    protected string $key = 'products';

    protected array $fillable = ['name', 'price', 'stock'];
}
```

### Creating Records

```php
$product = Product::create([
    'name'  => 'Widget',
    'price' => 9.99,
    'stock' => 100,
]);

echo $product->id;         // UUID string
echo $product->created_at; // ISO-8601 timestamp
```

### Finding a Record

```php
$product = Product::find($id); // returns null if not found
```

### Retrieving All Records

```php
$products = Product::all(); // returns an array of model instances
```

### Updating a Record

```php
$product->update(['stock' => 95]);

echo $product->updated_at; // refreshed timestamp
```

### Saving a Manually Constructed Model

```php
$product = new Product();
$product->fill(['name' => 'Gadget', 'price' => 4.99, 'stock' => 50]);
$product->save();
```

### Deleting a Record

```php
$product->delete(); // removes from Redis and the ID index
```

### Converting to an Array

```php
$array = $product->toArray();
```

---

## How Records Are Stored

Each model record is stored as a **JSON string** in Redis:

| Redis Key | Contents |
|-----------|----------|
| `{prefix}:{key}:{id}` | JSON payload of the record (attributes + `id`, `created_at`, `updated_at`) |
| `{prefix}:{key}:ids`  | Redis **SET** containing all record IDs for `all()` retrieval |

Example with default prefix and a `User` model:

```
rediloquent:users:550e8400-e29b-...  →  {"id":"550e8400-e29b-...","name":"Emeka","email":"emeka@example.com","created_at":"...","updated_at":"..."}
rediloquent:users:ids               →  {"550e8400-e29b-..."}
```

---

## Testing

The package ships with a Pest test suite. Run the tests with:

```bash
composer test
```

or directly with:

```bash
vendor/bin/pest
```

Tests require a running Redis instance (database 9 is used by default to avoid collisions).

---

## Roadmap

- [ ] Query builder (where, limit, offset)
- [ ] Model relationships (hasMany, belongsTo)
- [ ] Soft deletes
- [ ] Model events / observers
- [ ] Custom key types (integer auto-increment)
- [ ] Expiring records (TTL support)
- [ ] Collection return type for `all()`
- [ ] Casting attributes (integers, booleans, arrays)
- [ ] Full-text search via RediSearch

---

## License

Rediloquent is open-source software licensed under the [GNU General Public License v3.0](LICENSE).
