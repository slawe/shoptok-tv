<?php

declare(strict_types=1);

namespace App\Services\Shoptok;

use App\DTO\TvProductData;

class ShoptokTvPageScraper
{
    private const DEFAULT_CATEGORY = 'Televizorji';

    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @param string $url
     * @param string|null $category
     * @return TvProductData[]
     */
    public function scrape(string $url, ?string $category = null): array
    {
        // TODO: implement scrape
        // check request
        // select every title -> fallback if doesnt work
    }
}
