# AGENTS.md — Cross-Agent Instructions for Bagisto 2.4.x

## Do Not Edit

- `vendor/`, `node_modules/`, `composer.lock`, `package-lock.json`
- `public/themes/*/build/` — Vite build output
- `storage/` — runtime caches, logs, compiled views
- `*.hot` files — Vite HMR markers
- `packages/Webkul/*/src/Resources/assets/` — only edit if working on frontend; always run `npm run build` from the respective package directory after

## Repository Map

```
├── app/                        # Thin Laravel app shell (middleware, providers)
├── bootstrap/
│   ├── app.php                 # Middleware, exceptions, routing
│   └── providers.php           # All service provider registrations
├── config/
│   ├── concord.php             # Concord module (model proxy) registrations
│   ├── themes.php              # Shop + Admin theme config (Vite paths)
│   ├── elasticsearch.php       # Elasticsearch connection
│   └── ...                     # Standard Laravel configs
├── database/
│   ├── migrations/             # App-level migrations
│   └── seeders/
├── packages/Webkul/            # ★ All Bagisto packages live here (40 packages)
│   ├── Admin/                  # Admin panel (controllers, views, DataGrids, reporting, e2e-pw tests)
│   ├── Shop/                   # Customer storefront (controllers, views, e2e-pw tests)
│   ├── Core/                   # Helpers, models, jobs, listeners, exchange rates
│   ├── Product/                # Product models, types, indexers, repositories
│   ├── Sales/                  # Orders, invoices, shipments, refunds
│   ├── Checkout/               # Cart, checkout flow
│   ├── Customer/               # Customer models, auth
│   ├── Category/               # Category tree (nested set)
│   ├── Attribute/              # EAV attribute system
│   ├── Payment/                # Base payment classes (CashOnDelivery, MoneyTransfer)
│   ├── Paypal/                 # PayPal integration
│   ├── Stripe/                 # Stripe integration
│   ├── Razorpay/               # Razorpay integration
│   ├── PayU/                   # PayU integration
│   ├── Shipping/               # Base shipping carriers
│   ├── Inventory/              # Stock management
│   ├── CartRule/               # Cart promotion rules
│   ├── CatalogRule/            # Catalog price rules
│   ├── Tax/                    # Tax calculation
│   ├── DataGrid/               # Admin data table component
│   ├── DataTransfer/           # Import/export
│   ├── CMS/                    # CMS pages
│   ├── Marketing/              # SEO, URL rewrites, search terms, campaigns
│   ├── Theme/                  # Theme management
│   ├── MagicAI/                # AI features (Laravel AI SDK)
│   ├── Notification/           # Notifications
│   ├── BookingProduct/         # Booking product type
│   ├── Rule/                   # Shared rule engine base
│   ├── User/                   # Admin user management
│   ├── Installer/              # Installation wizard
│   ├── SocialLogin/            # OAuth social login
│   ├── SocialShare/            # Social sharing
│   ├── Sitemap/                # XML sitemap generation
│   ├── GDPR/                   # GDPR compliance
│   ├── RMA/                    # Return merchandise authorization
│   ├── FPC/                    # Full page cache
│   ├── ImageCache/             # Image caching/resizing
│   ├── DebugBar/               # Debug toolbar
│   ├── BreezeFront/            # Breeze frontend theme
│   └── NewTheme/               # New theme scaffold
├── routes/
│   ├── web.php                 # Minimal — packages define their own routes
│   └── console.php
├── tests/
│   └── Pest.php                # Pest configuration binding test cases to packages
├── phpunit.xml                 # Test suites per package
├── pint.json                   # Pint config (preset: laravel)
├── vite.config.js              # Root Vite config
└── docker-compose.yml          # Sail: MySQL 8, Redis, Elasticsearch 7.17, Kibana, Mailpit
```

## Package Internal Structure

Every package in `packages/Webkul/{Name}/src/` follows:

```
├── Config/                     # admin-menu.php, system.php, acl.php, carriers.php, etc.
├── Contracts/                  # Interfaces for each model
├── Database/
│   ├── Migrations/
│   ├── Factories/
│   └── Seeders/
├── DataGrids/                  # DataGrid classes (extends Webkul\DataGrid\DataGrid)
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/               # Form Request validation classes
├── Jobs/
├── Listeners/
├── Models/                     # Eloquent models + Proxy classes
├── Observers/
├── Providers/
│   ├── {Name}ServiceProvider.php
│   └── ModuleServiceProvider.php  # Concord model registration
├── Repositories/               # Prettus L5 repositories
├── Resources/
│   ├── assets/                 # JS, CSS, images (Vite-compiled)
│   ├── lang/{locale}/          # 21 locales
│   └── views/
├── Routes/
│   ├── admin-routes.php
│   └── shop-routes.php
└── Type/                       # (Product package) Product type classes
```

## Key Architecture Patterns

- **Concord Module System**: Models registered in each package's `ModuleServiceProvider`, wired via `config/concord.php`. Every data entity has a Contract (interface), Model, and Proxy (three-component system).
- **Repository Pattern**: All DB access through repositories extending `Webkul\Core\Eloquent\Repository` (Prettus L5). Repository `model()` returns the Contract class, not the Model.
- **Path Repositories**: `composer.json` uses `"type": "path"` for `packages/*/*`, packages are symlinked — no `composer update` needed for package code changes. Run `composer dump-autoload` after adding new packages.
- **Service Providers**: Each package has a main ServiceProvider (routes, views, translations, migrations, config) registered in `bootstrap/providers.php`.
- **Dual Route Files**: Admin routes (`['web', 'admin']` middleware, `config('app.admin_url')` prefix) and Shop routes (`['web', 'locale', 'theme', 'currency']` middleware).
- **21 Locales**: ar, bn, ca, de, en, es, fa, fr, he, hi_IN, id, it, ja, nl, pl, pt_BR, ru, sin, tr, uk, zh_CN. Translation changes must be applied to ALL locale files. Verify with `php artisan bagisto:translations:check`.

## Commands

### Testing
```bash
# Pest (PHP)
php artisan test --compact                              # Run all tests
php artisan test --compact --filter=testName             # Run specific test
php artisan test --compact packages/Webkul/Admin/tests   # Run package tests

# Playwright (E2E) — Admin (run from packages/Webkul/Admin)
cd packages/Webkul/Admin && npm install && npx playwright install --with-deps chromium
cd packages/Webkul/Admin && npx playwright test --config=tests/e2e-pw/playwright.config.ts

# Playwright (E2E) — Shop (run from packages/Webkul/Shop)
cd packages/Webkul/Shop && npm install && npx playwright install --with-deps chromium
cd packages/Webkul/Shop && npx playwright test --config=tests/e2e-pw/playwright.config.ts
```

### Code Style
```bash
vendor/bin/pint --dirty          # Fix changed files only
vendor/bin/pint                  # Fix all files
vendor/bin/pint --test           # Check only (CI uses this)
```

### Frontend (run from within each package: Admin, Shop, or Installer)
```bash
cd packages/Webkul/Admin && npm install && npm run build    # Admin production build
cd packages/Webkul/Shop && npm install && npm run build     # Shop production build
cd packages/Webkul/Admin && npm run dev                     # Admin dev server with HMR
cd packages/Webkul/Shop && npm run dev                      # Shop dev server with HMR
```

### Database
```bash
php artisan migrate              # Run migrations
php artisan db:seed              # Seed database
```

## CI Workflows (.github/workflows/)

| Workflow | Trigger | What it does |
|----------|---------|--------------|
| `pest_tests.yml` | push, PR | Installs Bagisto, runs `vendor/bin/pest` |
| `pint_tests.yml` | push, PR | Runs `pint --test` (style check) |
| `admin_playwright_tests.yml` | push, PR | Admin E2E tests |
| `shop_playwright_tests.yml` | push, PR | Shop E2E tests |
| `translation_tests.yml` | push, PR | Translation key consistency |

## Safety Rails

- **Never modify `bootstrap/providers.php` or `config/concord.php`** without understanding the full provider chain — removing a provider breaks the entire module.
- **Translations are 21 files per key.** Missing a locale will fail CI. When adding/removing translation keys, hit all 21 files.
- **Pint must pass.** Run `vendor/bin/pint --dirty` before finalizing any PHP change.
- **Tests must pass.** Run affected package tests after changes. Do not delete tests without approval.
- **Do not add/remove composer dependencies without approval.**
- **Do not create documentation files unless explicitly requested.**

## Validation Checklist (Before Marking Complete)

1. `vendor/bin/pint --dirty` — no style violations
2. `php artisan test --compact` — affected tests pass
3. `php artisan bagisto:translations:check` — translation keys exist in all 21 locale files (if changed)
4. No `env()` calls outside `config/` files
5. New models have Contract + Model + Proxy + Repository
6. New packages registered in `bootstrap/providers.php` and `config/concord.php`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- laravel/ai (AI) - v0
- laravel/cashier (CASHIER) - v16
- laravel/framework (LARAVEL) - v12
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- laravel/telescope (TELESCOPE) - v5
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

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

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== ai/core rules ===

## Laravel AI SDK

- This application uses the Laravel AI SDK (`laravel/ai`) for all AI functionality.
- Activate the `developing-with-ai-sdk` skill when building, editing, updating, debugging, or testing AI agents, text generation, chat, streaming, structured output, tools, image generation, audio, transcription, embeddings, reranking, vector stores, files, conversation memory, or any AI provider integration (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== octane/core rules ===

# Laravel Octane

This application uses Laravel Octane, a long-running PHP server. The application bootstraps once and handles many requests within the same process.

- Never store request-specific state in singletons or static properties, because it can leak across requests.
- Use `config('octane.server')` to detect the active driver (`swoole`, `roadrunner`, or `frankenphp`).
- Prefer scoped bindings (`$this->app->scoped()`) over singletons for per-request services.

When working on Octane-specific features (concurrency, shared tables, memory, driver configuration, testing), invoke `octane-development` for detailed rules.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

<bagisto-guidelines>
=== foundation rules ===

# Bagisto Guidelines

Bagisto is a Laravel-based e-commerce platform. These guidelines are specifically curated for developing with Bagisto and its package-based architecture.

## Foundational Context

This application is a **Bagisto** e-commerce platform built on Laravel 12. You must be familiar with both Laravel and Bagisto's modular package architecture.

### Technology Stack

- **PHP**: 8.3+
- **Laravel**: v12
- **Vue.js**: For admin panel interactivity
- **Tailwind CSS**: For styling
- **Laravel Octane**: v2
- **Laravel Sanctum**: v4
- **Laravel Socialite**: v5
- **Laravel Boost**: v2
- **Laravel MCP**: v0
- **Laravel Pint**: v1
- **Pest**: v3
- **PHPUnit**: v11

### Bagisto Core Packages

Bagisto uses a modular package structure in `packages/Webkul/`:

| Package | Purpose |
|---------|---------|
| **Admin** | Admin panel functionality |
| **Shop** | Customer storefront |
| **Core** | Common utilities and helpers |
| **Product** | Product management |
| **Category** | Category management |
| **Checkout** | Cart and checkout process |
| **Payment** | Payment methods (CashOnDelivery, MoneyTransfer) |
| **Paypal** | PayPal integration |
| **Shipping** | Shipping methods |
| **Sales** | Order management |
| **Customer** | Customer management |
| **Attribute** | Product attributes |
| **Inventory** | Stock management |
| **CartRule** | Cart promotions |
| **CatalogRule** | Catalog promotions |
| **DataGrid** | Admin data tables |
| **Tax** | Tax calculation |
| **CMS** | Content management |
| **Theme** | Theme management |

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `package-development` — Package development in Bagisto. Activates when creating packages, migrations, models, repositories, routes, controllers, views, localization, DataGrid, menus, ACL, or system configuration. Use references: @core (package structure, service providers), @data (migrations, models, repositories), @ui (routes, controllers, views), @features (localization, DataGrid, menus, ACL, system config).

- `shipping-method-development` — Shipping method development in Bagisto. Activates when creating shipping methods, integrating shipping carriers like FedEx, UPS, DHL, or any third-party shipping provider; or when the user mentions shipping, shipping method, shipping carrier, delivery, or needs to add a new shipping option to checkout.

- `payment-method-development` — Payment gateway development in Bagisto. Activates when creating payment methods, integrating payment gateways like Stripe, PayPal, or any third-party payment processor; or when the user mentions payment, payment gateway, payment method, Stripe, PayPal, or needs to add a new payment option to the checkout.

- `product-type-development` — Product type development in Bagisto. Activates when creating custom product types, defining product behaviors, or implementing specialized product logic. Use references: @config (product type configuration), @abstract (AbstractType methods), @build (complete subscription implementation).

- `shop-theme-development` — Shop theme development in Bagisto. Activates when creating custom storefront themes, modifying shop layouts, building theme packages, or working with Vite-powered assets for the customer-facing side of the application.

- `admin-theme-development` — Admin theme development in Bagisto. Activates when creating custom admin themes, modifying admin layouts, building admin theme packages, or working with admin panel styling and interface customization.

- `pest-testing` — Tests applications using the Pest 3 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, architecture testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.

- `blade-conventions` — Blade template conventions for any Bagisto package (Admin, Shop, or a custom Webkul-style module). Activates when creating or editing Blade views, building anonymous `@props` or Vue-backed `x-template` components, wiring forms, datagrids, modals, layouts, or slots; or when matching the project's markup, attribute-binding, indentation, comment, and formatting style.

- `bagisto-api-develop` — Install / remove / extend the `bagisto-api` package (REST + GraphQL). Activates when installing or removing the package, or adding/changing an endpoint, resource, or admin menu's API; or when the user mentions `ApiResource`, `Provider`, `Processor`, `DTO`, "install the package", or "add an endpoint". Install/remove run only on explicit request.

- `bagisto-api-shop` — Build a storefront app/UI on the Shop API (`/api/shop/*` + `/api/graphql`). Activates when building a customer-facing storefront, catalog/cart/checkout flow, customer account, or shopping app; or when the user mentions products, cart, checkout, coupons, wishlist, or customer login/account.

- `bagisto-api-admin` — Build an admin app/UI on the Admin API (`/api/admin/*` + `/api/admin/graphql`). Activates when building an admin dashboard, back-office panel, or an order/catalog/customer/marketing/CMS/settings management screen; or when the user mentions admin orders, products, customers, reporting, or "admin panel on the API".

## Bagisto Architecture

### Package Structure

Every Bagisto package follows a standardized structure:

```
packages/Webkul/{PackageName}/
├── src/
│   ├── Config/
│   │   ├── admin-menu.php
│   │   └── system.php
│   ├── Database/
│   │   ├── Migrations/
│   │   ├── Seeders/
│   │   └── Factories/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   └── Shop/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   │   └── {Package}Proxy.php
│   ├── Repositories/
│   │   └── {Package}Repository.php
│   ├── Resources/
│   │   ├── views/
│   │   ├── lang/
│   │   └── manifest.php
│   └── Providers/
│       └── {Package}ServiceProvider.php
└── composer.json
```

### Repository Pattern

Bagisto uses the Prettus L5 Repository pattern. Always use repositories for data access:

```php
// Correct way - inject the repository and let the container resolve it.
public function __construct(
    protected ProductRepository $productRepository,
) {}

$products = $this->productRepository->all();

// Outside a constructor, resolve it from the container.
$products = app(ProductRepository::class)->all();

// Avoid reaching for the model directly.
$products = Product::all(); // Less preferred
```

### Service Providers

Service providers must:
- Load routes from `Routes/admin-routes.php` and `Routes/shop-routes.php`
- Load migrations automatically
- Load translations from `Resources/lang`
- Load views from `Resources/views`
- Merge package configuration using `$this->mergeConfigFrom()`

## Conventions

- Always follow existing code conventions used in this application.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing new one.
- Use PHPDoc blocks with proper punctuation for all classes and methods.
- Follow the package structure when creating new packages.
- Use repositories for database operations.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work.
- Unit and feature tests are more important than manual verification.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.
- Custom packages should be placed in `packages/Webkul/`.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages.
- This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for your circumstance.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared.

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
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.
- Always use proper punctuation at the end of descriptions.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input.

## Database

- Always use proper Eloquent relationship methods with return type hints.
- Use Eloquent models and relationships before suggesting raw database queries.
- Use Repository pattern for Bagisto packages.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files.
- Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== boost/core rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation

- Use `search-docs` tool before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== payment-method-development rules ===

# Payment Gateway Development

- CRITICAL: ALWAYS use the payment-method-development skill when working with payment methods in Bagisto.
- Payment methods in Bagisto are located in `packages/Webkul/Payment/src/Payment/` and `packages/Webkul/Paypal/src/Payment/`.
- All payment methods extend `Webkul\Payment\Payment\Payment` abstract class.
- Payment configuration is defined in `Config/payment-methods.php` files.
- System configuration for admin panel is defined in `Config/system.php` files.
- Service providers must merge payment method configuration using `$this->mergeConfigFrom()`.
- Always follow the existing code patterns and PHPDoc conventions when creating payment methods.
- For testing payment methods, refer to `packages/Webkul/Shop/tests/Feature/Checkout/CheckoutTest.php`.

=== shipping-method-development rules ===

# Shipping Method Development

- CRITICAL: ALWAYS use the shipping-method-development skill when working with shipping methods in Bagisto.
- Shipping methods in Bagisto are located in `packages/Webkul/Shipping/src/Carriers/`.
- All shipping methods extend `Webkul\Shipping\Carriers\AbstractShipping` abstract class.
- Shipping carrier configuration is defined in `Config/carriers.php` files.
- System configuration for admin panel is defined in `Config/system.php` files.
- Service providers must merge carrier configuration using `$this->mergeConfigFrom()`.
- Always follow the existing code patterns and PHPDoc conventions when creating shipping methods.
- Use `core()->convertPrice()` for multi-currency support when setting prices.
- Check `$item->getTypeInstance()->isStockable()` for per-item shipping calculations.

=== blade-conventions rules ===

# Blade Conventions

- CRITICAL: ALWAYS use the blade-conventions skill when creating or editing any `.blade.php` file.
- Binding: `attr="text"` is a literal, `:attr="expr"` is a Blade/PHP expression, `::attr="expr"` escapes to a literal `:attr` for Vue. Getting `:` vs `::` wrong is the most common source of bugs.
- Reuse the globally registered `x-admin::` / `x-shop::` components and layouts; prefix only your own new components with your package namespace.
- Vue-backed components: `<v-name>` wrapper + `<script type="text/x-template" id="v-name-template">` + `app.component("v-name", ...)`, all inside `@pushOnce('scripts')` … `@endPushOnce`. Emit runtime values as `@{{ … }}`.
- Formatting: 4 spaces; more than one attribute means one per line with the closing `>` on its own line; a single attribute stays inline; no blank lines between a tag's attributes; one blank line between sibling blocks.
- Align the `=>` in Blade `@props`/arrays. Do NOT align them in real `.php` files — Pint single-spaces those.
- Comments follow the layer they sit in: `{{-- --}}` for Blade/PHP notes, `<!-- -->` for markup section dividers, and `/** … */` JSDoc blocks inside `<script>` and `<style>` — never `//` or bare `/* */` there.
- Comment casing: a sentence is capitalized and punctuated; a bare title/label is Title Case with no trailing period.
- Never hardcode UI strings — use `@lang('<ns>::app…')`, and add new keys to every locale.
- Gate admin actions with `@if (bouncer()->hasPermission('resource.action'))`.
- Bracket meaningful content with `{!! view_render_event('bagisto.<area>.<path>.before') !!}` / `.after`.

=== package-development rules ===

# Package Development

- CRITICAL: ALWAYS use the package-development skill when creating packages in Bagisto.
- Use the Bagisto Package Generator (`composer require bagisto/bagisto-package-generator`) for quick setup.
- Package structure must follow the standardized layout in `packages/Webkul/`.
- Service providers must be registered in `bootstrap/providers.php`.
- Always run `composer dump-autoload` after adding new packages.
- Break a condition with more than one clause (`&&` / `||`) across lines, operator leading each line; keep single-clause conditions inline. Pint does not enforce this — see the skill's PHP Code Style section.
- Use references: @core for structure/service providers, @data for models/migrations, @ui for routes/controllers/views, @features for localization/DataGrid/menus/ACL/config.

#### @core - Core
- Use `$this->loadMigrationsFrom()`, `$this->loadRoutesFrom()`, `$this->loadViewsFrom()`, `$this->loadTranslationsFrom()` in service provider boot() method.
- Use `$this->mergeConfigFrom()` in service provider register() method for config merging.
- Register models in ModuleServiceProvider using Concord for proxy resolution.

#### @data - Data Layer
- Use migrations in `src/Database/Migrations/` for database schema.
- Always create Contract, Model, and Proxy for each data entity (three-component model system).
- Use Prettus L5 Repository pattern for data access (extends `Webkul\Core\Eloquent\Repository`).
- Repository model() method must return the contract class path, not the model class.
- Use package prefix for table names (e.g., `rma_requests` instead of `return_requests`).
- Register models in `config/concord.php` via ModuleServiceProvider.

#### @ui - UI Layer
- Routes must be in `src/Routes/admin-routes.php` and `src/Routes/shop-routes.php`.
- Admin routes use middleware `['web', 'admin']` with prefix from `config('app.admin_url')`.
- Shop routes use middleware `['web', 'locale', 'theme', 'currency']`.
- Controllers must extend package base Controller which extends `Illuminate\Routing\Controller`.
- Use dependency injection for repositories in controllers.
- Use `<x-admin::layouts>` and `<x-shop::layouts>` for Blade views.
- Use `<x-admin::datagrid>` component for admin tables.
- Views must be loaded with namespace prefix (e.g., `rma::admin.return-requests.index`).

#### @features - Features
- Translation files go in `src/Resources/lang/{locale}/` and use namespace `rma::`.
- DataGrid classes extend `Webkul\DataGrid\DataGrid` and are placed in `src/DataGrids/Admin/`.
- Admin menu is configured in `src/Config/admin-menu.php` and merged to `menu.admin`.
- ACL is configured in `src/Config/acl.php` and merged to `acl`.
- System configuration is in `src/Config/system.php` and merged to `core`.
- Use `core()->getConfigData('key.path')` to retrieve configuration values.
- Use `bouncer()->hasPermission('key')` to check ACL permissions in controllers and views.

=== product-type-development rules ===

# Product Type Development

- CRITICAL: ALWAYS use the product-type-development skill when working with product types in Bagisto.
- Product types extend `Webkul\Product\Type\AbstractType` base class.
- Product type configuration is defined in `Config/product-types.php` files.
- Use references: @config for configuration structure, @abstract for AbstractType methods, @build for complete implementation.
- Product types must be registered in service provider using `$this->mergeConfigFrom()`.
- Key methods to override: `isSaleable()`, `isStockable()`, `showQuantityBox()`, `haveSufficientQuantity()`, `prepareForCart()`, `getTypeValidationRules()`.
- Use `$additionalViews` for custom admin interface sections.
- Use `$skipAttributes` to hide irrelevant attributes for product type.

=== api-platform-development rules ===

# Bagisto API Platform (REST + GraphQL)

- CRITICAL: use the `bagisto-api-develop` skill when installing / removing / extending the `bagisto-api` package; use `bagisto-api-shop` or `bagisto-api-admin` when building an app or UI on the API.
- Two surfaces: **Storefront** — `/api/shop/*` (REST) + `/api/graphql`, authed by the `X-STOREFRONT-KEY` header (plus a customer or guest-cart Bearer token for cart/account calls). **Admin** — `/api/admin/*` (REST) + `/api/admin/graphql`, authed by a pre-issued admin Integration Bearer token. The admin API mirrors the admin panel menu-for-menu.
- The api-docs (`https://api-docs.bagisto.com` and its `/llms.txt` index) are the source of truth for exact request/response shapes — open the endpoint page; never invent a payload from memory.
- GraphQL `id` is selectable only on fetchable (noun) resources (product, customer, order). Action/result mutations (cart writes, place-order, cancel, comment) return result fields (`cartId`, `orderId`, `success`, `message`) — never `id`. GraphQL inputs are camelCase.
- Admin collections return a `{ data, meta }` envelope; storefront paginated collections expose `X-Total-*` headers; page size is `?per_page=N` (+ `?page=N`).
- Extending the package: REST + GraphQL share the same Provider/Processor — any change to one must keep the other working, so run the resource's GraphQL test before the REST test. Mirror the admin panel, not a superset. No auto-commit — the user commits.

</bagisto-guidelines>

