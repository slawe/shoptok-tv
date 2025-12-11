<?php

namespace App\Console\Commands;

use App\Services\Shoptok\ShoptokTvImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ShoptokScrapeAllFixtures extends Command
{
    protected $signature = 'shoptok:scrape-all-fixtures';

    protected $description = 'Import all Shoptok televizorji fixtures into database';

    public function __construct(private readonly ShoptokTvImportService $importService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $baseDir = resource_path('fixtures/shoptok/televizorji');

        if (!File::exists($baseDir)) {
            $this->error("Directory not found: {$baseDir}");

            return self::FAILURE;
        }

        $files = collect(File::files($baseDir))
            ->filter(fn ($file) => $file->getExtension() === 'html')
            ->sortBy(fn ($file) => $file->getFilename());

        $this->info(sprintf(
            'Found %d fixture file(s) in %s.',
            $files->count(),
            $baseDir
        ));

        $total = 0;

        foreach ($files as $file) {
            $relative = 'fixtures/shoptok/televizorji/' . $file->getFilename();

            $this->info("→ Importing {$relative} ...");

            $count = $this->importService->importFromHtmlFixture($relative);

            $this->info("   Imported/updated {$count} product(s) from {$relative}.");

            $total += $count;
        }

        $this->info("Done. Total imported/updated products from all fixtures: {$total}.");

        return self::SUCCESS;
    }
}
