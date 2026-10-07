{{-- Read-only strip of images and videos. Pass $images and $videos as relative public-disk paths. --}}
@if (!empty($images) || !empty($videos))
    <div class="d-flex flex-wrap gap-2 {{ $class ?? 'mt-2' }}">
        @foreach ($images ?? [] as $image)
            <a href="{{ asset('storage/' . $image) }}" target="_blank" rel="noopener">
                <img src="{{ asset('storage/' . $image) }}" alt="" loading="lazy" style="width:90px;height:70px;object-fit:cover;border-radius:6px;">
            </a>
        @endforeach
        @foreach ($videos ?? [] as $video)
            <video src="{{ asset('storage/' . $video) }}" controls preload="metadata" style="width:140px;height:70px;object-fit:cover;border-radius:6px;background:#000;"></video>
        @endforeach
    </div>
@endif
