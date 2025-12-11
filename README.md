# Shoptok TV – Mini Crawling & Listing Project

This is a small Laravel application built for a coding assignment.

The goal is to:

1. Fetch all *Televizorji* products from Shoptok  
   (`https://www.shoptok.si/televizorji/cene/206`) and store them in a database.
2. Render them on a dedicated page with pagination (20 per page).
3. **Bonus:** implement a crawler for the whole **TV sprejemniki** category  
   (`https://www.shoptok.si/tv-prijamniki/cene/56`) and build a UI with a left-side submenu  
   for the leaf categories (e.g. *Televizorji*, *TV dodatki*).

Because the target site is protected by WAF / anti-bot (Cloudflare), the "live" HTTP
version is blocked with `403 Forbidden`.  
For that reason, this project uses **HTML fixtures** checked into the repository and
focuses on a clean, testable scraping architecture rather than bypassing protection.

---

## Tech stack

- PHP 8.2+ (tested on PHP 8.5.0 via Laravel Sail)
- Laravel 10.x
- MySQL (via Sail)
- Redis (via Sail, optional)
- Bootstrap 5 (CDN) + Blade templates
- DTO + Value Object + Service layer
- PHP 8.1+ Enums
- Symfony DomCrawler + CssSelector (for HTML parsing)

---

## Running the project

### 1. Clone & install dependencies

```bash
git clone https://github.com/slawe/shoptok-tv.git
cd shoptok-tv

cp .env.example .env

# Install PHP dependencies
composer install

# Install JS dependencies (only if you want to run Vite)
npm install
```
> Vite / asset pipeline is not required for this assignment because the UI is Blade + Bootstrap CDN.<br>
> NPM is only needed if you want to run the full Laravel frontend workflow.

### 2. Start Sail containers

```bash
./vendor/bin/sail up -d
```

This will start:
* laravel.test (app),
* mysql,
* redis.

Default ports:
* App: http://localhost
* MySQL: 127.0.0.1:3306

### 3. Database & migrations

Update `.env` to use Sail's MySQL service:
```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

Run migrations:

```bash
./vendor/bin/sail artisan migrate
```

---

## Scraping / Import – fixture-based approach

### Why fixtures?

The target site is behind Cloudflare/WAF and consistently returns `403 Forbidden` for
non-browser clients, even when providing realistic headers and cookies.

Instead of attempting to circumvent these protections (which would be out of scope for
a coding assignment and potentially against the site's ToS), this project takes a **fixture-driven approach**:

* HTML pages are saved via **"View Source"** in a browser.
* These fixtures are committed into the repository.
* The scraper is implemented against those fixtures, using the same DOM structure as the live site.
* The architecture is the same whether the HTML comes from an HTTP client or from disk.

All fixtures are located under:
```text
resources/fixtures/shoptok/televizorji/*.html
resources/fixtures/shoptok/tv_dodatki/*.html
```

> Note: The HTML is in _view-source_ format (escaped markup inside `<td class="line-content">`),<br>
> so the scraper contains a normalization step that reconstructs the original DOM.

### Scraper architecture

Core classes:

* App\DTO\TvProductData<br>
  Simple DTO for a TV product (title, brand, shop, URLs, price, category, external ID).
* App\ValueObjects\Money<br>
  Small value object representing a monetary amount in cents + ISO currency code.
* App\Enums\TvCategory<br>
  Backed enum for domain categories, e.g. Televizorji, TV dodatki.
* App\Services\Shoptok\ShoptokTvPageScraper<br>
  Responsible for turning HTML into TvProductData objects.
* App\Services\Shoptok\ShoptokTvImportService<br>
  Responsible for importing DTOs into the tv_products table.
* App\Console\Commands\ShoptokScrapeAllFixtures<br>
  Artisan command orchestrating the import from all fixtures.

The scraper is deliberately SRP-oriented:

* One public method for HTTP (`scrapePage()`) - kept for completeness, but not used in production due to WAF.
* One public method for fixtures (`scrapeHtml()`),
* Many small private methods:
  * `extractTitle()`
  * `extractBrand()`
  * `extractShopName()`
  * `extractPriceText()`
  * `extractProductUrl()`
  * `extractImageUrl()`
  * `extractExternalIdFromTitle()`
  * `extractExternalIdFromNode()`
  * `extractNextPageUrl()`
  * `normalizeHtmlForCrawler()` – converts view-source HTML into real DOM.

```php
final class ShoptokTvPageScraper
{
    public function scrapeHtml(
        string $html,
        ?string $category = null,
        ?string $currentUrl = null,
    ): ShoptokPageResult {
        // ...
    }

    // Private helpers: extractTitle, extractBrand, extractPriceText, etc.
}
```

This design adheres to SOLID:
* **S**ingle Responsibility - parsing and persistence are separated.
* **O**pen/Closed - adding another category or changing mapping rules does not require touching the controller.
* **L**iskov - DTOs are simple and never surprise callers.
* **I**nterface Segregation - responsibilities are split into DTO/VO/service layers instead of one "god class".
* **D**ependency Inversion - controllers depend on abstractions (services/models), not on raw HTTP clients.

### Importing from fixtures

Main command:
```bash
./vendor/bin/sail artisan shoptok:scrape-all-fixtures
```

This command:
* imports all fixture pages for **Televizorji** (`resources/fixtures/shoptok/televizorji`),
* imports all fixture pages for **TV dodatki** (`resources/fixtures/shoptok/tv_dodatki`),
* logs how many products were imported/updated per file,
* prints the total number of products in the database at the end.

Notes:
* The importer uses `updateOrCreate` keyed by `product_url`, so<br>
  duplicate appearances of the same product across pages **do not** create duplicates<br>
  in the database.
* The “Imported/updated … product(s)” count reflects how many products were **actually**<br>
  **created/changed** in that run; running the command multiple times will often result in low numbers<br>
  because the database is already in sync with the fixtures.

### Deprecated live-scraping command

Originally, the project also contained a command intended for live crawling over HTTP:
* shoptok:scrape-televisions

Due to Cloudflare/WAF consistently returning 403 Forbidden for non-browser clients, this flow was deprecated.

The command is left in the codebase only as documentation of the original intent and now simply prints a message pointing to the fixture-based import:

```bash
./vendor/bin/sail artisan shoptok:scrape-televisions
# => DEPRECATED: live scraping is blocked by WAF. Use "shoptok:scrape-all-fixtures" instead.
```

---

## Data Model

`tv_products` table (simplified):

* `id` (PK)
* `title` (string)
* `brand` (nullable string)
* `shop` (nullable string, e.g. "v 3 trgovinah")
* `product_url` (unique string)
* `image_url` (nullable string)
* `price_cents` (nullable integer)
* `currency` (string, default EUR)
* `category` (string, matches TvCategory enum values)
* `external_id` (nullable string, Shoptok internal ID)
* Timestamps (`created_at`, `updated_at`)

Model: `App\Models\TvProduct`

The model uses `$guarded` nothing is "mass-assignment" protected.

---

## Frontend – listing & pagination

The frontend is intentionally simple and server-side rendered using Blade + Bootstrap 5.

### 1. Televizorji listing (`/televizorji`)

Route:
```php
Route::get('/televizorji', [TvProductController::class, 'index'])
    ->name('tv.index');
```
Features:
* 20 products per page (`paginate(20)`).
* Simple card layout (brand, title, image, price, shop info).
* Pagination uses Bootstrap 5 (`Paginator::useBootstrapFive()` in `AppServiceProvider`).

### 2. TV receivers (TV sprejemniki) page (`/tv-sprejemniki`)

This page implements the BONUS part of the task: it represents the logical parent
category TV sprejemniki with leaf categories:
* `Televizorji`
* `TV dodatki`

Route:
```php
Route::get('/tv-sprejemniki', [TvProductController::class, 'receivers'])
    ->name('tv.receivers');
```

View (`resources/views/tv/receivers.blade.php`) renders:
* **Left sidebar** with submenu:
  * "Vsi izdelki"
  * "Televizorji"
  * "TV dodatki"

* **Right side:** grid of product cards (reusing the same partial as `/televizorji`).
* Pagination (20 per page) preserved with `withQueryString()`.

This mimics the original **Shoptok** layout where _TV sprejemniki_ is a parent category
and _Televizorji_ / _TV dodatki_ are its subcategories.

---

## Design decisions

* **Fixture-based scraping:**<br>
    Instead of bypassing WAF and attempting to scrape directly from production HTML, the project
    uses static fixtures and focuses on clean parsing logic, DTOs, and import.
* **Separation of concerns:**<br>
  * Scraper only knows how to translate HTML → DTO (`TvProductData`).
  * Import service only knows how to translate DTO → Eloquent (`TvProduct`).
  * Controllers only orchestrate queries and return views.
* **Enums for domain categories:**<br>
    `TvCategory` is the single source of truth for valid category values; no "magic strings"
    scattered throughout the code.
* **Value object for money:**<br>
    Eases price parsing and enforces consistent representation (integer cents + currency).
* **Bootstrap over a JS framework:**<br>
    For this assignment, server-side Blade rendering is more than enough. If needed, a
    Vue/React enhancement can be added later as a thin client-side filter/search, but is not
    required to satisfy the core task.

---

## How to extend

A few natural extensions:
* Add more fixtures for:
  * Other categories under _TV sprejemniki_ (e.g. _TV dodatki_),
  * Additional sorting or filter variants.
* Implement feature tests for:
  * Import logic from fixtures,
  * Listing pages (`/televizorji`, `/tv-sprejemniki`).
* Extract interfaces for the scraping service to allow swapping implementations<br>
(e.g. using a headless browser / external scraping API behind the same interface).

---

## Summary

This project demonstrates:
* A small but production-style Laravel codebase,
* Clean separation between scraping, import, and presentation,
* Fixture-based approach to work around WAF/403 while still writing realistic scraping code,
* Domain-driven structuring with DTOs, value objects, and enums,
* Pagination and category filtering for both _Televizorji_ and _TV sprejemniki_ views.

