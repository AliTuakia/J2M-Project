<article class="product-card">
    @if($imagePath = $product->imagePath())
        <a class="product-photo-frame" href="{{ route('products.show', $product) }}">
            <img class="product-photo" src="{{ asset(str_replace(' ', '%20', $imagePath)) }}" alt="{{ $product->name }}" width="600" height="450" loading="lazy">
            @if($product->usesIllustration())
                <span class="photo-label">FOTO HANYA ILUSTRASI</span>
            @endif
        </a>
    @endif
    <div class="product-body">
        <p class="eyebrow">{{ $product->category->name }}</p>
        <h3><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
        <p class="price">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
        @if($product->usesIllustration())
            <p class="muted">Foto merupakan referensi visual, bukan desain yang wajib diikuti.</p>
        @endif
        <a class="text-link" href="{{ route('products.show', $product) }}">Lihat detail →</a>
    </div>
</article>