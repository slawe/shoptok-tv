<?php

declare(strict_types=1);

namespace App\Services\Shoptok;

use App\DTO\TvProductData;
use App\Models\TvProduct;

class ShoptokTvImportService
{
    public function __construct(private readonly ShoptokTvPageScraper $scraper) {}

    public function importFromUrl(string $url, ?string $category = null): int
    {
        $count = 0;
        $dtos = $this->scraper->scrape($url, $category);

        /** @var TvProductData $dto */
        foreach ($dtos as $dto) {
            TvProduct::updateOrCreate(
                ['product_url' => $dto->productUrl],
                [
                    'title' => $dto->title,
                    'brand' => $dto->brand,
                    'shop' => $dto->shop,
                    'image_url' => $dto->imageUrl,
                    'price_cents' => $dto->price?->amountInCents(),
                    'currency' => $dto->price?->currency() ?? 'EUR',
                    'category' => $dto->category,
                    'external_id' => $dto->externalId,
                ]
            );

            $count++;
        }

        return $count;
    }
}
