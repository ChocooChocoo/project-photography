{{-- Shared service media fields (images + videos). Include inside the service form.
     Call window.appendServiceMedia(formData, formElement) before submitting.
     In a modal, call window.renderExistingServiceMedia(service) after loading the service. --}}
@php
    $mediaImages = $service->images ?? [];
    $mediaVideos = $service->videos ?? [];
@endphp

<div class="col-12 mb-3 service-media">
    <label class="form-label">Images <small class="text-muted">(optional, up to 5)</small></label>
    <div class="d-flex flex-wrap gap-2 mb-2" id="existingServiceImages">
        @foreach ($mediaImages as $image)
            <div class="position-relative existing-service-image" data-path="{{ $image }}">
                <img src="{{ asset('storage/' . $image) }}" style="width:80px;height:80px;object-fit:cover;border-radius:6px;" alt="">
                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-existing-service-media" style="padding:0 6px;line-height:1.4;">&times;</button>
            </div>
        @endforeach
    </div>
    <input type="file" class="form-control" name="images[]" accept=".jpg,.jpeg,.png,.gif" multiple>
    <div class="form-text">JPG/PNG/GIF, max 5MB each.</div>
</div>

<div class="col-12 mb-3 service-media">
    <label class="form-label">Videos <small class="text-muted">(optional, up to 5)</small></label>
    <div class="d-flex flex-wrap gap-2 mb-2" id="existingServiceVideos">
        @foreach ($mediaVideos as $video)
            <div class="position-relative existing-service-video" data-path="{{ $video }}">
                <video src="{{ asset('storage/' . $video) }}" style="width:120px;height:80px;object-fit:cover;border-radius:6px;" muted preload="metadata"></video>
                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-existing-service-media" style="padding:0 6px;line-height:1.4;">&times;</button>
            </div>
        @endforeach
    </div>
    <input type="file" class="form-control" name="videos[]" accept=".mp4,.webm,.mov" multiple>
    <div class="form-text">MP4/WebM/MOV, max 50MB each.</div>
</div>

<script>
    (function () {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-existing-service-media');
            if (btn) btn.parentElement.remove();
        });

        const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const btn = '<button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-existing-service-media" style="padding:0 6px;line-height:1.4;">&times;</button>';

        window.renderExistingServiceMedia = function (service) {
            document.getElementById('existingServiceImages').innerHTML = (service.images || []).map((p) =>
                `<div class="position-relative existing-service-image" data-path="${esc(p)}"><img src="{{ asset('storage') }}/${esc(p)}" style="width:80px;height:80px;object-fit:cover;border-radius:6px;" alt="">${btn}</div>`).join('');
            document.getElementById('existingServiceVideos').innerHTML = (service.videos || []).map((p) =>
                `<div class="position-relative existing-service-video" data-path="${esc(p)}"><video src="{{ asset('storage') }}/${esc(p)}" style="width:120px;height:80px;object-fit:cover;border-radius:6px;" muted preload="metadata"></video>${btn}</div>`).join('');
        };

        window.appendServiceMedia = function (formData, form) {
            ['images', 'videos'].forEach((name) => {
                formData.delete(name + '[]');
                Array.from(form.querySelector(`input[name="${name}[]"]`).files).forEach((f) => formData.append(name + '[]', f));
                formData.delete('keep_' + name + '[]');
                formData.set('keep_' + name + '_sent', '1');
                form.querySelectorAll(`.existing-service-${name === 'images' ? 'image' : 'video'}`).forEach((el) => formData.append('keep_' + name + '[]', el.dataset.path));
            });
        };
    })();
</script>
