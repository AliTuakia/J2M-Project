@if($product->ratings_count > 0)
    <p class="rating-summary">
        <span class="rating-star" aria-hidden="true">★</span>
        <span>{{ number_format($product->ratings_avg_score, 1, ',', '.') }} / 5</span>
        <span class="muted">({{ $product->ratings_count }} penilaian)</span>
    </p>
@else
    <p class="muted rating-summary">Belum ada rating</p>
@endif
