<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Shoptok\ShoptokTvImportService;
use Illuminate\Console\Command;

class ScrapeShoptokTelevisions extends Command
{
    protected $signature = 'shoptok:scrape-televisions
                            {--url=https://www.shoptok.si/televizorji/cene/206 : Source URL for televisions}
                            {--category=Televizorji : Category label to assign to products}';

    protected $description = 'Scrape televisions from Shoptok and store them in the database.';

    public function __construct(private readonly ShoptokTvImportService $importService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $url = (string) $this->option('url');
        $category = $this->option('category');

        $this->info("Scraping televisions from: {$url}");

        $importedCount = $this->importService->importFromUrl($url, $category);

        $this->info("Imported / updated {$importedCount} products.");

        return self::SUCCESS;
    }
}
