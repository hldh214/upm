# AGENTS.md - Coding Guidelines for UPM

## Project Overview

UPM (Uniqlo/GU Price Monitor) is a Laravel 12 + React/Inertia.js application for monitoring product prices from UNIQLO and GU Japan online stores.

**Tech Stack:**
- Backend: PHP 8.2+, Laravel 12, Inertia.js 2.0, Laravel Fortify
- Frontend: React 18, Tailwind CSS 3.4, Chart.js, Vite 6
- Database: MySQL 8.0+ or PostgreSQL 13+
- Testing: PHPUnit 11.5

## Build, Test & Development Commands

### Setup
```bash
composer install          # Install PHP dependencies
yarn install             # Install Node dependencies
cp .env.example .env     # Create environment file
php artisan key:generate # Generate app key
php artisan migrate      # Run database migrations
```

### Development
```bash
# Start all services (recommended)
composer dev             # Runs server, queue, logs, and vite concurrently

# Manual approach
php artisan serve        # Start Laravel server (http://localhost:8000)
yarn dev                 # Start Vite dev server with HMR
php artisan queue:listen # Start queue worker
php artisan pail         # View logs
```

### Build
```bash
yarn build               # Build frontend assets for production
```

### Testing
```bash
# Run all tests
php artisan test
composer test            # Alternative (clears config first)

# Run specific test file
php artisan test tests/Feature/Api/ProductApiTest.php

# Run specific test method
php artisan test --filter testIndexReturnsProductList

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage (if xdebug enabled)
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

### Linting & Code Style
```bash
# Format code with Laravel Pint (PSR-12 standard)
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test

# Format specific files/directories
./vendor/bin/pint app/Models
./vendor/bin/pint app/Http/Controllers/Api/ProductController.php
```

### Artisan Commands
```bash
# Crawl product prices
php artisan upm:crawl                    # All brands
php artisan upm:crawl --brand=uniqlo     # Specific brand
php artisan upm:crawl --brand=gu

# Data maintenance
php artisan upm:clean-duplicate-price-history
php artisan upm:fix-product-price-stats
php artisan upm:import-legacy-price-history
```

## Code Style Guidelines

### General Standards (from ~/.config/opencode/AGENTS.md)

- Always use English for code comments, even when communicating in other languages
- No space-only lines; replace with empty newlines
- Never use foreign keys or cascades in RDBMS
- Never reset or wipe databases; rollback modified parts or ask user
- Never commit changes using git (ask user first)
- If frontend changed, build before finishing

### PHP/Laravel Style

**File Organization:**
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;      // Parent class first
use App\Models\Product;                   // App classes alphabetically
use Illuminate\Http\JsonResponse;         // Framework classes alphabetically
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Public methods first, then protected, then private
}
```

**Naming Conventions:**
- Classes: PascalCase (`ProductController`, `PriceCrawlerService`)
- Methods: camelCase (`priceHistory`, `getPriceChangeInfo`)
- Variables: camelCase (`$priceChange`, `$productIds`)
- Database columns: snake_case (`price_histories`, `product_id`)
- Constants: UPPER_SNAKE_CASE (`API_BASE_URL`)

**Method Documentation:**
```php
/**
 * Get price change information for given product IDs.
 *
 * Since price_histories only stores records when price changes,
 * we find the latest record within the period and its preceding record
 * to determine the price change.
 */
private function getPriceChangeInfo(array $productIds, int $days): array
```

**Type Hints:**
- Always use return type hints for methods
- Use nullable types with `?` when appropriate
- Use typed properties in models and classes

**Query Patterns:**
```php
// Use query scopes for reusable logic
$query = Product::query()
    ->search($request->input('q'))
    ->brand($request->input('brand'))
    ->gender($request->input('gender'));

// Use match() for clean conditional logic
return match ($sort) {
    'price_asc' => $query->orderBy('current_price', 'asc'),
    'price_desc' => $query->orderBy('current_price', 'desc'),
    default => $query->orderBy('id', 'desc'),
};

// Use eloquent relationships with eager loading
$product = Product::with(['priceHistories' => function ($query) {
    $query->orderBy('created_at', 'desc')->limit(90);
}])->findOrFail($id);
```

**Model Patterns:**
```php
// Always define $fillable for mass assignment
protected $fillable = ['product_id', 'name', 'brand'];

// Use $casts for type conversion
protected $casts = [
    'current_price' => 'integer',
    'created_at' => 'datetime',
];

// Define relationships with return types
public function priceHistories(): HasMany
{
    return $this->hasMany(PriceHistory::class);
}

// Use accessor methods for computed attributes
public function getUrlAttribute(): string
{
    return "https://example.com/{$this->product_id}";
}
```

**Error Handling:**
```php
// Use findOrFail() for 404 responses
$product = Product::findOrFail($id);

// Validate input limits
$perPage = min($request->input('per_page', 20), 100);
$days = min($request->input('days', 90), 365);

// Use try-catch with logging for external API calls
try {
    $response = Http::timeout(30)->get($url);
} catch (\Exception $e) {
    Log::error('API call failed', ['error' => $e->getMessage()]);
    throw $e;
}
```

### JavaScript/React Style

**File Organization:**
```javascript
// Imports: External libraries first, then internal modules
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Index({ products, stats, filters }) {
    // Extract props and setup
    const { translations } = usePage().props;
    
    // Helper functions
    const updateFilters = (newFilters) => { /* ... */ };
    
    // Render
    return ( /* ... */ );
}
```

**Naming Conventions:**
- Components: PascalCase (`AppLayout`, `Index`)
- Functions: camelCase (`updateFilters`, `goToPage`)
- Variables: camelCase (`products`, `priceChange`)
- Constants: camelCase (`translations`, `baseUrl`)

**Component Patterns:**
```javascript
// Use functional components with hooks
export default function ProductList({ products }) {
    const [selected, setSelected] = useState(null);
    
    // Destructure props early
    const { translations } = usePage().props;
    const t = translations;
    
    // Return JSX
    return <div>{/* ... */}</div>;
}
```

**Inertia.js Patterns:**
```javascript
// Navigate with Inertia router (preserves SPA behavior)
router.get('/products', params, {
    preserveState: true,
    preserveScroll: false,
    only: ['products', 'filters'],
});

// Link components for navigation
<Link href={`/products/${product.id}`}>{product.name}</Link>
```

**Styling with Tailwind:**
```javascript
// Use utility classes directly
<div className="flex items-center justify-between px-4 py-3">
    <button className="bg-uq-red text-white hover:bg-red-700 rounded px-4 py-2">
        Click me
    </button>
</div>

// Conditional classes
<div className={`p-4 ${isActive ? 'bg-blue-100' : 'bg-gray-100'}`}>
```

### EditorConfig Settings

- **Charset:** UTF-8
- **End of line:** LF (Unix style)
- **Indent style:** Spaces
- **Indent size:** 4 spaces (PHP), 2 spaces (YAML)
- **Insert final newline:** Yes
- **Trim trailing whitespace:** Yes

## Testing Guidelines

**Test Structure:**
```php
namespace Tests\Feature\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsProductList(): void
    {
        // Arrange: Create test data
        Product::factory()->count(5)->create();

        // Act: Make request
        $response = $this->getJson('/api/products');

        // Assert: Check response
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'brand', 'current_price']
                ]
            ]);
    }
}
```

**Testing Best Practices:**
- Use `RefreshDatabase` trait for database isolation
- Follow Arrange-Act-Assert pattern
- Use factories for test data generation
- Mock external API calls with `Http::fake()`
- Test both success and failure scenarios
- Use descriptive test method names

## Important Architectural Notes

1. **No Foreign Keys:** Do not use foreign key constraints or cascades in migrations
2. **Price History Optimization:** Only store records when prices change (not daily snapshots)
3. **Query Scopes:** Use model scopes for reusable query logic (`scopeSearch`, `scopeBrand`)
4. **Inertia.js:** Server-side rendering bridge - pass data via controller to React components
5. **Localization:** Support multiple languages via `lang/{locale}/ui.php` files
6. **API + Web:** Dual interface - RESTful API routes (`/api/*`) and web routes (`/*`)

## Common Pitfalls to Avoid

- Don't use raw SQL queries when Eloquent methods are available
- Don't forget to validate and sanitize user input
- Don't skip eager loading for relationships (causes N+1 queries)
- Don't use inline styles in React; use Tailwind classes
- Don't forget to run `yarn build` after frontend changes
- Don't create git commits without user permission
- Don't use `findOrFail()` in loops; use `whereIn()` instead
- Don't forget return type hints on controller methods

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.30
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- react (REACT) - v18
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `tailwindcss-development` — Styles applications using Tailwind CSS v3 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `developing-with-fortify` — Laravel Fortify headless authentication backend development. Activate when implementing authentication features including login, registration, password reset, email verification, two-factor authentication (2FA/TOTP), profile updates, headless auth, authentication scaffolding, or auth guards in Laravel applications.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `yarn run build`, `yarn run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.

=== inertia-laravel/v2 rules ===

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scrolling (merging props + `WhenVisible`), lazy loading on scroll, polling, prefetching.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `yarn run build` or ask the user to run `yarn run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== laravel/fortify rules ===

# Laravel Fortify

- Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.
- IMPORTANT: Always use the `search-docs` tool for detailed Laravel Fortify patterns and documentation.
- IMPORTANT: Activate `developing-with-fortify` skill when working with Fortify authentication features.
</laravel-boost-guidelines>
