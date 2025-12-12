<?php

namespace App\Console\Commands;

use App\Enums\TvCategory;
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
        // mapping categories to directories with HTML fixtures
        $fixturesConfig = [
            TvCategory::TELEVIZORJI->value => 'televizorji',
            TvCategory::TV_DODATKI->value => 'tv_dodatki',
        ];

        $total = 0;

        foreach ($fixturesConfig as $categoryValue => $relativeDir) {
            // convert string ("Televizorji") back to enum
            $categoryEnum = TvCategory::from($categoryValue);

            $baseDir = resource_path('fixtures/shoptok/' . $relativeDir);

            $files = collect(File::files($baseDir))
                ->filter(fn ($file) => $file->getExtension() === 'html')
                ->sortBy(fn ($file) => $file->getFilename())
                ->values();

            if ($files->isEmpty()) {
                $this->warn("No HTML fixtures found for {$categoryEnum->value} in {$baseDir}");
                continue;
            }

            $this->info("Found {$files->count()} fixture file(s) for {$categoryEnum->value} in {$baseDir}.");

            foreach ($files as $file) {
                $relativePath = $relativeDir . '/' . $file->getFilename();
                $this->line("→ Importing {$relativePath} ({$categoryEnum->value}) ...");
                $importedForFile = $this->importService->importFromFixture($relativePath, $categoryEnum);
                $this->line("   Imported/updated {$importedForFile} product(s) from {$relativePath}.");

                $total += $importedForFile;
            }
        }

        $this->info("Done. Total imported/updated products from all fixtures: {$total}.");

        return self::SUCCESS;
    }

}
