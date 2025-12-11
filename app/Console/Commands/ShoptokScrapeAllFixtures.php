<?php

namespace App\Console\Commands;

use App\Enums\TvCategory;
use App\Services\Shoptok\ShoptokTvImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ShoptokScrapeAllFixtures extends Command
{
    protected $signature = 'shoptok:scrape-all-fixtures
                            {--category= : Optional TvCategory value (e.g. "Televizorji")}';

    protected $description = 'Import all Shoptok televizorji fixtures into database';

    public function __construct(private readonly ShoptokTvImportService $importService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $categoryOption = $this->option('category');
        $categoryEnum = TvCategory::TELEVIZORJI;

        // convert CLI option to enum
        if ($categoryOption !== null) {
            try {
                $categoryEnum = TvCategory::from($categoryOption);
            } catch (\ValueError) {
                $this->warn("Unknown category '{$categoryOption}', falling back to Televizorji.");
                $categoryEnum = TvCategory::TELEVIZORJI;
            }
        }

        $baseDir = resource_path('fixtures/shoptok/televizorji');

        if (!File::exists($baseDir)) {
            $this->error("Directory not found: {$baseDir}");

            return self::FAILURE;
        }

        $files = collect(File::files($baseDir))
            ->filter(fn ($file) => $file->getExtension() === 'html')
            ->sortBy(fn ($file) => $file->getFilename())
            ->values();

        if ($files->isEmpty()) {
            $this->warn("No HTML fixtures found in {$baseDir}");

            return self::SUCCESS;
        }

        $this->info("Found {$files->count()} fixture file(s) in {$baseDir}.");

        $total = 0;

        foreach ($files as $file) {
            $relative = 'fixtures/shoptok/televizorji/' . $file->getFilename();

            $this->info("→ Importing {$relative} ...");

            $count = $this->importService->importFromHtmlFixture($relative, $categoryEnum);

            $this->info("   Imported/updated {$count} product(s) from {$relative}.");

            $total += $count;
        }

        $this->info("Done. Total imported/updated products from all fixtures: {$total}.");

        return self::SUCCESS;
    }
}
