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
    public function __construct(
        private readonly ShoptokHtmlSource $htmlSource,
        private readonly ShoptokTvPageScraper $scraper) {}

    /**
     * Import from a live URL (kept for completeness, currently not used due to 403/WAF).
     *
     * @param string $url
     * @param TvCategory $category
     * @return int
     * @throws RequestException
     * @deprecated
     */
    #[\Deprecated(message: "Live crawling is blocked by WAF – use importFromFixture() instead.")]
    public function importFromUrl(string $url, TvCategory $category): int
    {
        $html = $this->htmlSource->fetch($url);

        $result = $this->scraper->parseHtml(
            $html,
            $category->value,
            $url,
        );

        return $this->upsertProducts($result->products);
    }

    /**
     * Import all products from a single HTML fixture file.
     *
     * @param string $relativePath
     * @param TvCategory $category
     * @return int
     */
    public function importFromFixture(string $relativePath, TvCategory $category): int
    {
        $html = $this->htmlSource->fetch($relativePath);

        $result = $this->scraper->parseHtml(
            $html,
            $category->value,
            null,
        );

        return $this->upsertProducts($result->products);
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
                $touched[$model->external_id] = true;
            }
        }

        // number of unique external_id that were actually created/modified
        return count($touched);
    }
}
