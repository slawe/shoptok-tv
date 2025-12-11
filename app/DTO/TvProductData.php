<?php

declare(strict_types=1);

namespace App\DTO;

use App\ValueObjects\Money;

final class TvProductData
{
    public function __construct(
        public string $title,
        public ?string $brand,
        public ?string $shop,
        public string $productUrl,
        public ?string $imageUrl,
        public ?Money $price,
        public ?string $category,
        public ?string $externalId,
    ) {
    }
}
