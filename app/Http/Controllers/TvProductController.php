<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TvCategory;
use App\Models\TvProduct;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class TvProductController extends Controller
{
    public function index(): View
    {
        $products = TvProduct::query()
            ->where('category', TvCategory::TELEVIZORJI->value)
            ->orderBy('title')
            ->paginate(20);     // 20 per page, as required by the assignment

        return view('tv.index', compact('products'));
    }

    public function receivers(Request $request): View
    {
        $leafCategories = $this->getTvReceiverLeafCategories();

        $activeCategory = $this->resolveActiveCategory(
            $request->query('category'),
            $leafCategories,
        );

        $productsQuery = TvProduct::query()
            ->whereIn('category', $leafCategories);

        if ($activeCategory !== null) {
            $productsQuery->where('category', $activeCategory);
        }

        $products = $productsQuery
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        $categoryCounts = TvProduct::query()
            ->selectRaw('category, COUNT(*) as aggregate')
            ->whereIn('category', $leafCategories)
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        return view('tv.receivers', [
            'products'        => $products,
            'leafCategories'  => $leafCategories,
            'activeCategory'  => $activeCategory,
            'categoryCounts'  => $categoryCounts,
        ]);
    }

    /**
     * @return string[]
     */
    private function getTvReceiverLeafCategories(): array
    {
        return array_map(
            static fn (TvCategory $category) => $category->value,
            TvCategory::tvReceiversLeaf(),
        );
    }

    /**
     * Returns a valid category or null if the parameter is not allowed.
     *
     * @param  string[]  $allowedCategories
     */
    private function resolveActiveCategory(?string $rawCategory, array $allowedCategories): ?string
    {
        if ($rawCategory === null) {
            return null;
        }

        return \in_array($rawCategory, $allowedCategories, true)
            ? $rawCategory
            : null;
    }
}
