<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Shoptok\ShoptokTvImportService;
use Illuminate\Console\Command;
use JetBrains\PhpStorm\Deprecated;

#[Deprecated]
class ScrapeShoptokTelevisions extends Command
{
    protected $signature = 'shoptok:scrape-televisions
                            {--url=https://www.shoptok.si/televizorji/cene/206 : Start URL for televisions category}
                            {--category=Televizorji : Category label to assign to products}
                            {--single-page : Only scrape the first page (debug/development)}';

    protected $description = 'Scrape televisions from Shoptok and store them in the database.';

    public function __construct(private readonly ShoptokTvImportService $importService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $url = (string) $this->option('url');
        $category = $this->option('category');
        $singlePage = (bool) $this->option('single-page');

        if ($singlePage) {
            $this->info("Scraping ONLY first page from: {$url}");
            $importedCount = $this->importService->importFromUrl($url, $category);
        } else {
            $this->info("Scraping ALL pages for category starting from: {$url}");
            $importedCount = $this->importService->importCategory($url, $category);
        }

        $this->info("Imported / updated {$importedCount} products.");

        return self::SUCCESS;
    }
}
