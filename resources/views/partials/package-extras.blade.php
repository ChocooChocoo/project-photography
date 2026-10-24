{{-- Shared package fields: cover videos, materials, photo editing prices. Include inside the package form.
     Call window.appendPackageExtras(formData, formElement) before submitting. --}}
@php
    $extrasVideos = $package->cover_videos ?? [];
    $extrasMaterials = $package->materials ?? [];
    $extrasLegacyPrice = $package->enhanced_photos_price ?? null;
    $extrasEditingPrices = $package->editing_prices ?? [];
@endphp

<div class="col-12 mb-3 package-extras">
    <label class="form-label">Cover Videos <small class="text-muted">(optional, up to 5)</small></label>
    @if (!empty($extrasVideos))
        <div class="d-flex flex-wrap gap-2 mb-2" id="existingCoverVideos">
            @foreach ($extrasVideos as $video)
                <div class="position-relative existing-cover-video" data-path="{{ $video }}">
                    <video src="{{ asset('storage/' . $video) }}" style="width:120px;height:80px;object-fit:cover;border-radius:6px;" muted preload="metadata"></video>
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-existing-video" style="padding:0 6px;line-height:1.4;">&times;</button>
                </div>
            @endforeach
        </div>
    @endif
    <input type="file" class="form-control" name="cover_videos[]" id="coverVideos" accept=".mp4,.webm,.mov" multiple>
    <div class="form-text">MP4/WebM/MOV, max 50MB each. Shown to clients when choosing this package.</div>
</div>

<div class="col-12 mb-3 package-extras">
    <label class="form-label">Materials Included <small class="text-muted">(optional, e.g. photo frames)</small></label>
    <div id="materialsContainer">
        @foreach ($extrasMaterials as $material)
            <div class="input-group mb-2 material-row">
                <input type="text" class="form-control material-name" value="{{ $material['name'] ?? '' }}" placeholder="Material (e.g. 8x10 photo frame)" maxlength="100">
                <input type="number" class="form-control material-qty" value="{{ $material['quantity'] ?? 1 }}" min="1" max="999" style="max-width:110px;" aria-label="Quantity">
                <button class="btn btn-default remove-material-btn" type="button"><i class="ti ti-trash"></i></button>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-default" id="addMaterialBtn"><i class="ti ti-plus"></i> Add material</button>
</div>

<fieldset class="col-12 mb-3 package-extras" aria-describedby="editingPricesHelp">
    <legend class="form-label fs-6 mb-1">Photo Editing Prices <small class="text-muted">(optional)</small></legend>
    <p id="editingPricesHelp" class="form-text mt-0 mb-2">Clients can pick one or more services on top of the package price. Leave a price empty to not offer that service. With no prices, clients get unedited (RAW) photos.</p>
    @if ($extrasLegacyPrice !== null && empty(array_filter($extrasEditingPrices, fn ($price) => $price !== null && $price !== '')))
        <p class="form-text text-warning-emphasis mb-2">This package still uses the older single enhanced-photos price of ₱{{ number_format($extrasLegacyPrice, 2) }}. Setting prices below replaces it.</p>
    @endif
    <div class="row g-2">
        @foreach (\App\Models\StudioOwner\PackagesModel::EDITING_SERVICES as $serviceKey => $serviceLabel)
            <div class="col-12 col-md-4">
                <label class="form-label small" for="editingPrice-{{ $serviceKey }}">{{ $serviceLabel }}</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" class="form-control" id="editingPrice-{{ $serviceKey }}" name="editing_prices[{{ $serviceKey }}]" min="0" step="0.01" value="{{ $extrasEditingPrices[$serviceKey] ?? '' }}" placeholder="Not offered">
                </div>
            </div>
        @endforeach
    </div>
</fieldset>

<script>
    (function () {
        const container = document.getElementById('materialsContainer');
        const rowHtml = '<div class="input-group mb-2 material-row">'
            + '<input type="text" class="form-control material-name" placeholder="Material (e.g. 8x10 photo frame)" maxlength="100">'
            + '<input type="number" class="form-control material-qty" value="1" min="1" max="999" style="max-width:110px;" aria-label="Quantity">'
            + '<button class="btn btn-default remove-material-btn" type="button"><i class="ti ti-trash"></i></button></div>';

        document.getElementById('addMaterialBtn').addEventListener('click', () => {
            if (container.querySelectorAll('.material-row').length >= 30) return;
            container.insertAdjacentHTML('beforeend', rowHtml);
        });
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-material-btn, .remove-existing-video');
            if (!btn) return;
            btn.closest('.material-row, .existing-cover-video').remove();
        });
        container.closest('form').addEventListener('reset', () => { container.innerHTML = ''; });

        window.appendPackageExtras = function (formData, form) {
            const materials = [];
            container.querySelectorAll('.material-row').forEach((row) => {
                const name = row.querySelector('.material-name').value.trim();
                if (name) materials.push({ name, quantity: parseInt(row.querySelector('.material-qty').value, 10) || 1 });
            });
            formData.set('materials', JSON.stringify(materials));

            formData.delete('cover_videos[]');
            Array.from(form.querySelector('#coverVideos').files).forEach((f) => formData.append('cover_videos[]', f));

            formData.delete('keep_cover_videos[]');
            formData.set('keep_cover_videos_sent', '1');
            document.querySelectorAll('.existing-cover-video').forEach((el) => formData.append('keep_cover_videos[]', el.dataset.path));

            // Freelancer forms build FormData by hand, so add the editing prices here.
            form.querySelectorAll('[name^="editing_prices["]').forEach((input) => formData.set(input.name, input.value));
        };
    })();
</script>
