{{-- Shared package fields: materials, photo editing prices. Include inside the package form.
     Cover videos are hidden (T02 Phase 10: the studios are photo-only). Stored videos are kept,
     because the form sends no video fields and syncMedia then leaves them as they are.
     Call window.appendPackageExtras(formData, formElement) before submitting. --}}
@php
    $extrasMaterials = $package->materials ?? [];
    $extrasLegacyPrice = $package->enhanced_photos_price ?? null;
    $extrasEditingPrices = $package->editing_prices ?? [];
@endphp

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
            const btn = e.target.closest('.remove-material-btn');
            if (!btn) return;
            btn.closest('.material-row').remove();
        });
        container.closest('form').addEventListener('reset', () => { container.innerHTML = ''; });

        window.appendPackageExtras = function (formData, form) {
            const materials = [];
            container.querySelectorAll('.material-row').forEach((row) => {
                const name = row.querySelector('.material-name').value.trim();
                if (name) materials.push({ name, quantity: parseInt(row.querySelector('.material-qty').value, 10) || 1 });
            });
            formData.set('materials', JSON.stringify(materials));

            // Freelancer forms build FormData by hand, so add the editing prices here.
            form.querySelectorAll('[name^="editing_prices["]').forEach((input) => formData.set(input.name, input.value));
        };
    })();
</script>
