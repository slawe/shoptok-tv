<?php

declare(strict_types=1);

namespace App\Services\Shoptok;

use App\DTO\TvProductData;

final class ShoptokPageResult
{
    /**
     * @param TvProductData[] $products
     */
    public function __construct(public array $products, public ?string $nextPageUrl) {}

    public function hasNextPage(): bool
    {
        return $this->nextPageUrl !== null;
    }
}
