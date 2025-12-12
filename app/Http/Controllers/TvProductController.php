<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TvCategory;
use App\Models\TvProduct;
use App\Repositories\TvProduct\TvProductRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class TvProductController extends Controller
{
    public function __construct(
        private readonly TvProductRepository $products,
    ) {}

    public function index(): View
    {
        $products = $this->products->paginateByCategory(TvCategory::TELEVIZORJI);

        return view('tv.index', compact('products'));
    }

    public function receivers(Request $request): View
    {
        // enums representing leaf categories
        $leafCategoryEnums = TvCategory::tvReceiversLeaf();

        // string values for query param and view
        $leafCategories = $this->getTvReceiverLeafCategories();

        $activeCategory = $this->resolveActiveCategory(
            $request->query('category'),
            $leafCategories,
        );

        // if there is no active filter -> all leaf categories.
        // if there is -> we filter the enum list to only that one.
        $categoriesForListing = $leafCategoryEnums;

        if ($activeCategory !== null) {
            $categoriesForListing = array_filter(
                $leafCategoryEnums,
                static fn (TvCategory $category) => $category->value === $activeCategory,
            );
        }

        // pagination via repository
        $products = $this->products->paginateByCategories(
            $categoriesForListing,
            20,
        );

        // number of products per leaf category (for the menu on the left)
        $categoryCounts = $this->products->countByCategories($leafCategoryEnums);
        $allProductsCount = array_sum($categoryCounts);

        return view('tv.receivers', [
            'products'        => $products,
            'leafCategories'  => $leafCategories,
            'activeCategory'  => $activeCategory,
            'categoryCounts'  => $categoryCounts,
            'allProductsCount'=> $allProductsCount,
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
