<?php

declare(strict_types=1);

namespace App\Repositories\TvProduct;

use App\DTO\TvProductData;
use App\Enums\TvCategory;
use App\Models\TvProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTvProductRepository implements TvProductRepository
{
    public function upsertMany(array $products): int
    {
        $touched = [];

        foreach ($products as $dto) {
            if (! $dto instanceof TvProductData) {
                continue;
            }

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

    public function paginateByCategory(
        TvCategory $category,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return TvProduct::query()
            ->where('category', $category->value)
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateByCategories(
        array $categories,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $values = array_map(
            static fn (TvCategory $c) => $c->value,
            $categories
        );

        return TvProduct::query()
            ->whereIn('category', $values)
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countByCategories(array $categories): array
    {
        $values = array_map(
            static fn (TvCategory $category) => $category->value,
            $categories
        );

        /** @var array<string,int> $counts */
        $counts = TvProduct::query()
            ->selectRaw('category, COUNT(*) as aggregate')
            ->whereIn('category', $values)
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->all();

        return $counts;
    }
}
