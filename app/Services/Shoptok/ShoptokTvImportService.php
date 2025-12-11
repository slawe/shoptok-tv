<?php

declare(strict_types=1);

namespace App\Services\Shoptok;

use App\DTO\TvProductData;
use App\Models\TvProduct;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class ShoptokTvImportService
{
    public function __construct(
        private readonly ShoptokTvPageScraper $scraper,
    ) {
    }

    /**
     * Import of one page - we leave it if needed for debugging.
     *
     * @param string $url
     * @param string|null $category
     * @return int
     * @throws RequestException
     */
    public function importFromUrl(string $url, ?string $category = null): int
    {
        $result = $this->scraper->scrapePage($url, $category);

        return $this->upsertProducts($result->products);
    }

    public function importFromHtmlFixture(string $relativePath, ?string $category = null): int
    {
        $absolutePath = resource_path($relativePath);

        if (! File::exists($absolutePath)) {
            throw new RuntimeException("Fixture not found: {$absolutePath}");
        }

        $html = File::get($absolutePath);

        $result = $this->scraper->scrapeHtml(
            $html,
            $category,
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
    public function importCategory(string $startUrl, ?string $category = null): int
    {
        $total = 0;
        $currentUrl = $startUrl;

        while ($currentUrl !== null) {
            $result = $this->scraper->scrapePage($currentUrl, $category);

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
        $count = 0;

        foreach ($dtos as $dto) {
            TvProduct::updateOrCreate(
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

            $count++;
        }

        return $count;
    }
}
