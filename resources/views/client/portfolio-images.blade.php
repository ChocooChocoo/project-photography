@php
    $portfolioImages = $galleries->flatMap(function ($gallery) {
        return collect($gallery->images ?? [])
            ->filter(fn ($path) => is_string($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
            ->map(fn ($path, $index) => ['path' => $path, 'label' => ($gallery->gallery_name ?: 'Portfolio').' photo '.($index + 1)]);
    });
@endphp
<section class="mb-4" aria-labelledby="portfolio-heading">
    <h2 id="portfolio-heading" class="h5 mb-3">Portfolio</h2>
    @if($portfolioImages->isEmpty())
        <p class="mb-0">No portfolio images yet.</p>
    @else
        <p>Choose a photo to view it at full size.</p>
        <div class="row g-3">
            @foreach($portfolioImages as $image)
                <div class="col-12 col-sm-6 col-xl-4">
                    <a href="{{ asset('storage/'.$image['path']) }}" class="d-block rounded" aria-label="View {{ $image['label'] }} at full size">
                        <span data-portfolio-loading role="status">Loading photo…</span>
                        <img src="{{ asset('storage/'.$image['path']) }}" class="rounded w-100" width="480" height="360" style="aspect-ratio: 4 / 3; height: auto; object-fit: cover;" alt="{{ $image['label'] }}" loading="lazy" onload="this.previousElementSibling.hidden = true;" onerror="this.previousElementSibling.hidden = true; this.hidden = true; this.nextElementSibling.hidden = false; this.parentElement.removeAttribute('href');">
                        <span class="p-3 border rounded" hidden>Photo unavailable. Reload this page to try again.</span>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</section>
