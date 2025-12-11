<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * @deprecated Live crawling is blocked by WAF – use "shoptok:scrape-all-fixtures" instead.
 */
class ScrapeShoptokTelevisions extends Command
{
    protected $signature = 'shoptok:scrape-televisions
                            {--url=https://www.shoptok.si/televizorji/cene/206 : Start URL for televisions category}
                            {--category=Televizorji : Category label to assign to products}
                            {--single-page : Only scrape the first page (debug/development)}';

    protected $description = 'DEPRECATED: live scraping is blocked by WAF. Use "shoptok:scrape-all-fixtures" instead.';

    public function handle(): int
    {
        $this->warn('This command is deprecated.');
        $this->warn('Live crawling is blocked by WAF / Cloudflare.');
        $this->warn('Please use "php artisan shoptok:scrape-all-fixtures" instead (fixture-based import).');

        return self::FAILURE;
    }
}
