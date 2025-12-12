<?php

declare(strict_types=1);

namespace App\Repositories\TvProduct;

use App\DTO\TvProductData;
use App\Enums\TvCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TvProductRepository
{
    /**
     * Upsert a collection of DTOs.
     *
     * @param TvProductData[] $products
     */
    public function upsertMany(array $products): int;

    /**
     * @param TvCategory $category
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateByCategory(
        TvCategory $category,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * For /tv-sprejemniki – all leafs or one specific category.
     *
     * @param TvCategory[] $categories
     */
    public function paginateByCategories(
        array $categories,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * Number of products per category.
     *
     * @param  TvCategory[] $categories
     * @return array<string,int>
     */
    public function countByCategories(array $categories): array;
}
