@php use App\Enums\TvCategory; @endphp

@extends('layouts.app')

@section('title', 'Televizorji')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">{{ TvCategory::TELEVIZORJI->value }}</h1>
            <small class="text-muted">
                Prikazano {{ $products->firstItem() }}–{{ $products->lastItem() }} od {{ $products->total() }} izdelkov
            </small>
        </div>
    </div>

    @if($products->isEmpty())
        <div class="alert alert-info">
            There are currently no products in the database. First, start the crawler
            (<code>php artisan shoptok:scrape-all-fixtures</code>).
        </div>
    @else
        <div class="row g-3">
            @foreach($products as $product)
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    @include('tv._card', ['product' => $product])
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $products->links() }}
        </div>
    @endif
@endsection
