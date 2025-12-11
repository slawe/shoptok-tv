@php use App\Enums\TvCategory; @endphp

@extends('layouts.app')

@section('title', 'TV sprejemniki')

@section('content')
    <div class="row">
        {{-- LEFT SIDEBAR --}}
        <aside class="col-12 col-md-3 col-lg-2 mb-4 mb-md-0">
            <div class="card">
                <div class="card-header fw-bold">
                    Izdelki
                </div>

                <div class="list-group list-group-flush">
                    {{-- All products --}}
                    <a
                        href="{{ route('tv.receivers') }}"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $activeCategory === null ? 'active' : '' }}"
                    >
                        <span>Vsi izdelki</span>
                        <span class="badge bg-light text-dark">
                            {{ $categoryCounts->sum() }}
                        </span>
                    </a>

                    {{-- Televizorji --}}
                    @php $tvCategory = TvCategory::TELEVIZORJI->value; @endphp
                    <a
                        href="{{ route('tv.receivers', ['category' => $tvCategory]) }}"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $activeCategory === $tvCategory ? 'active' : '' }}"
                    >
                        <span>Televizorji</span>
                        <span class="badge bg-light text-dark">
                            {{ $categoryCounts[$tvCategory] ?? 0 }}
                        </span>
                    </a>

                    {{-- TV dodatki --}}
                    @php $addonsCategory = TvCategory::TV_DODATKI->value; @endphp
                    <a
                        href="{{ route('tv.receivers', ['category' => $addonsCategory]) }}"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $activeCategory === $addonsCategory ? 'active' : '' }}"
                    >
                        <span>TV dodatki</span>
                        <span class="badge bg-light text-dark">
                            {{ $categoryCounts[$addonsCategory] ?? 0 }}
                        </span>
                    </a>
                </div>
            </div>
        </aside>

        {{-- MAIN LISTING --}}
        <section class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h4 mb-0">TV sprejemniki</h1>
                    <small class="text-muted">
                        @if($activeCategory)
                            Kategorija: <strong>{{ $activeCategory }}</strong> ·
                        @endif
                        Prikazano {{ $products->firstItem() }}–{{ $products->lastItem() }}
                        od {{ $products->total() }} izdelkov
                    </small>
                </div>
            </div>

            @if($products->isEmpty())
                <div class="alert alert-info">
                    Ni izdelkov za izbrano filtriranje.
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
        </section>
    </div>
@endsection
