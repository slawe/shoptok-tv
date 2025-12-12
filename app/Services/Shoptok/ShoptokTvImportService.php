<?php

declare(strict_types=1);

namespace App\Services\Shoptok;

use App\Enums\TvCategory;
use App\Repositories\TvProduct\TvProductRepository;


final class ShoptokTvImportService
{
    /**
     * ShoptokTvImportService constructor.
     *
     * @param ShoptokHtmlSource $htmlSource
     * @param ShoptokTvPageScraper $scraper
     * @param TvProductRepository $repository
     */
    public function __construct(
        private readonly ShoptokHtmlSource $htmlSource,
        private readonly ShoptokTvPageScraper $scraper,
        private readonly TvProductRepository $repository,
    ) {}

    /**
     * Import from a live URL (kept for completeness, currently not used due to 403/WAF).
     *
     * @param string $url
     * @param TvCategory $category
     * @return int
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

        return $this->repository->upsertMany($result->products);
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

        return $this->repository->upsertMany($result->products);
    }
}
