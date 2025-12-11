<div class="card h-100 product-card">
    @if($product->image_url)
        <a href="{{ $product->product_url }}" target="_blank" rel="noopener noreferrer">
            <img
                src="{{ $product->image_url }}"
                class="card-img-top p-3"
                alt="{{ $product->title }}"
            >
        </a>
    @endif

    <div class="card-body d-flex flex-column">
        <h2 class="product-title mb-2">
            <a
                href="{{ $product->product_url }}"
                class="text-decoration-none text-dark"
                target="_blank"
                rel="noopener noreferrer"
            >
                {{ $product->title }}
            </a>
        </h2>

        @if($product->brand)
            <div class="mb-1">
                <span class="badge bg-secondary">{{ $product->brand }}</span>
            </div>
        @endif

        <div class="mt-auto">
            @if(! is_null($product->price_cents))
                @php
                    $priceInEur = $product->price_cents / 100;
                @endphp
                <div class="price text-primary mb-1">
                    {{ number_format($priceInEur, 2, ',', '.') }} {{ $product->currency ?? 'EUR' }}
                </div>
            @else
                <div class="text-muted mb-1">
                    Cena ni na voljo
                </div>
            @endif

            @if($product->shop)
                <div class="shop-label">
                    {{ $product->shop }}
                </div>
            @endif
        </div>
    </div>

    @if($product->external_id)
        <div class="card-footer text-muted small">
            ID: {{ $product->external_id }}
        </div>
    @endif
</div>
