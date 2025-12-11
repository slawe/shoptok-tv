<?php

declare(strict_types=1);

namespace App\Services\Shoptok;

use App\DTO\TvProductData;
use App\Enums\TvCategory;
use App\Models\TvProduct;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class ShoptokTvImportService
{
    public function __construct(private readonly ShoptokTvPageScraper $scraper) {}

    /**
     * Import of one page - we leave it if needed for debugging.
     *
     * @param string $url
     * @param string|null $category
     * @return int
     * @throws RequestException
     */
    public function importFromUrl(string $url, TvCategory $category): int
    {
        $result = $this->scraper->scrapePage($url, $category->value);

        return $this->upsertProducts($result->products);
    }

    public function importFromHtmlFixture(string $relativePath, TvCategory $category): int
    {
        $absolutePath = resource_path($relativePath);

        if (! File::exists($absolutePath)) {
            throw new RuntimeException("Fixture not found: {$absolutePath}");
        }

        $html = File::get($absolutePath);

        $result = $this->scraper->scrapeHtml(
            $html,
            $category->value,
            'https://www.shoptok.si/televizorji/cene/206',
        );

        return $this->upsertProducts($result->products);
    }

    /**
     * Import the entire category (all pages) starting from startUrl.
     *
     * @param string $startUrl
     * @param string|null $category
     * @return int
     * @throws RequestException
     */
    public function importCategory(string $startUrl, TvCategory $category): int
    {
        $total = 0;
        $currentUrl = $startUrl;

        while ($currentUrl !== null) {
            $result = $this->scraper->scrapePage($currentUrl, $category->value);

            $total += $this->upsertProducts($result->products);

            $currentUrl = $result->nextPageUrl;
        }

        return $total;
    }

    /**
     * @param TvProductData[] $dtos
     */
    private function upsertProducts(array $dtos): int
    {
        $touched = [];

        foreach ($dtos as $dto) {
            $model = TvProduct::updateOrCreate(
                ['external_id' => $dto->externalId],
                [
                    'title' => $dto->title,
                    'brand' => $dto->brand,
                    'shop' => $dto->shop,
                    'product_url' => $dto->productUrl,
                    'image_url' => $dto->imageUrl,
                    'price_cents' => $dto->price?->amountInCents(),
                    'currency' => $dto->price?->currency() ?? 'EUR',
                    'category' => $dto->category,
                ]
            );

            if ($model->wasRecentlyCreated || $model->wasChanged()) {
                $touched[$model->product_url] = true;
            }
        }

        // number of unique external_id that were actually created/modified
        return count($touched);
    }
}
