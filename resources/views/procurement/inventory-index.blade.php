{{--
    Read-only inventory built from confirmed procurement deliveries.
    Params: $stocks, $assets, $totals (from ProcurementWorkflowService::getInventorySummary).
--}}
@php
    $emptyMessage = 'Items appear here after a procurement delivery is confirmed as received.';
    $studios = $stocks->pluck('studio')->merge($assets->pluck('studio'))->filter()->unique('id')->sortBy('studio_name')->values();
    $summaryCards = [
        ['label' => 'Stock items', 'value' => $totals['stock_items'], 'icon' => 'ti ti-packages', 'class' => 'primary'],
        ['label' => 'Low stock', 'value' => $totals['low_stock'], 'icon' => 'ti ti-alert-triangle', 'class' => 'warning'],
        ['label' => 'Equipment assets', 'value' => $totals['assets'], 'icon' => 'ti ti-camera', 'class' => 'info'],
        ['label' => 'Active assets', 'value' => $totals['active_assets'], 'icon' => 'ti ti-circle-check', 'class' => 'success'],
    ];
    $formatQuantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
@endphp

<div class="content-page">
    <div class="container-fluid">
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <h4 class="mb-1">Inventory</h4>
                        <p class="text-muted mb-0">Supplies and equipment your studios received through procurement.</p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <label for="inventorySearch" class="visually-hidden">Search inventory</label>
                        <input type="search" class="form-control" id="inventorySearch" placeholder="Search item, serial no., or request">
                        @if ($studios->count() > 1)
                            <label for="inventoryStudioFilter" class="visually-hidden">Filter by studio</label>
                            <select class="form-select" id="inventoryStudioFilter">
                                <option value="">All studios</option>
                                @foreach ($studios as $studio)
                                    <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 align-items-center mb-1">
                    @foreach ($summaryCards as $summaryCard)
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="avatar avatar-lg flex-shrink-0">
                                            <span class="avatar-title bg-{{ $summaryCard['class'] }}-subtle text-{{ $summaryCard['class'] }} rounded fs-24">
                                                <i class="{{ $summaryCard['icon'] }}" aria-hidden="true"></i>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <h4 class="mb-0">{{ $summaryCard['value'] }}</h4>
                                            <p class="mb-0 text-muted">{{ $summaryCard['label'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Consumable stock</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-centered table-hover mb-0">
                                <thead class="bg-light-subtle">
                                    <tr>
                                        <th>Item</th>
                                        <th>Description</th>
                                        <th>Unit</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Reorder at</th>
                                        <th>Status</th>
                                        <th class="text-end">Last cost</th>
                                        <th>Last received</th>
                                        <th>Source request</th>
                                    </tr>
                                </thead>
                                <tbody data-inventory-table>
                                    @forelse ($stocks as $stock)
                                        @php($isLow = (float) $stock->stock_quantity <= (float) $stock->reorder_threshold)
                                        <tr data-inventory-row
                                            data-studio="{{ $stock->studio_id }}"
                                            data-search="{{ strtolower($stock->item_name . ' ' . $stock->description . ' ' . ($stock->procurementRequest->request_reference ?? '') . ' ' . ($stock->studio->studio_name ?? '')) }}">
                                            <td>
                                                <div class="fw-semibold">{{ $stock->item_name }}</div>
                                                @if ($studios->count() > 1)
                                                    <small class="text-muted">{{ $stock->studio->studio_name ?? '' }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $stock->description ?: '—' }}</td>
                                            <td>{{ $stock->unit_of_measure ?: '—' }}</td>
                                            <td class="text-end">{{ $formatQuantity($stock->stock_quantity) }}</td>
                                            <td class="text-end">{{ $formatQuantity($stock->reorder_threshold) }}</td>
                                            <td>
                                                @if ($isLow)
                                                    <span class="badge badge-soft-warning">Low stock</span>
                                                @else
                                                    <span class="badge badge-soft-success">In stock</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap">{{ $stock->last_recorded_cost !== null ? '₱' . number_format((float) $stock->last_recorded_cost, 2) : '—' }}</td>
                                            <td class="text-nowrap">{{ $stock->last_received_at?->format('M d, Y') ?? '—' }}</td>
                                            <td class="text-nowrap">{{ $stock->procurementRequest->request_reference ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-5 text-muted">{{ $emptyMessage }}</td>
                                        </tr>
                                    @endforelse
                                    @if ($stocks->isNotEmpty())
                                        <tr data-inventory-no-match class="d-none">
                                            <td colspan="9" class="text-center py-4 text-muted">No stock matches your search.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Equipment assets</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-centered table-hover mb-0">
                                <thead class="bg-light-subtle">
                                    <tr>
                                        <th>Asset</th>
                                        <th>Serial no.</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th class="text-end">Acquisition cost</th>
                                        <th>Warranty until</th>
                                        <th>Source request</th>
                                    </tr>
                                </thead>
                                <tbody data-inventory-table>
                                    @forelse ($assets as $asset)
                                        <tr data-inventory-row
                                            data-studio="{{ $asset->studio_id }}"
                                            data-search="{{ strtolower($asset->asset_name . ' ' . $asset->serial_number . ' ' . $asset->location . ' ' . ($asset->procurementRequest->request_reference ?? '') . ' ' . ($asset->studio->studio_name ?? '')) }}">
                                            <td>
                                                <div class="fw-semibold">{{ $asset->asset_name }}</div>
                                                @if ($studios->count() > 1)
                                                    <small class="text-muted">{{ $asset->studio->studio_name ?? '' }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $asset->serial_number ?: '—' }}</td>
                                            <td>{{ $asset->location ?: '—' }}</td>
                                            <td>
                                                <span class="badge badge-soft-{{ $asset->status === 'active' ? 'success' : 'secondary' }}">{{ \Illuminate\Support\Str::headline($asset->status ?? 'unknown') }}</span>
                                            </td>
                                            <td class="text-end text-nowrap">{{ $asset->acquisition_cost !== null ? '₱' . number_format((float) $asset->acquisition_cost, 2) : '—' }}</td>
                                            <td class="text-nowrap">{{ $asset->warranty_expires_at?->format('M d, Y') ?? '—' }}</td>
                                            <td class="text-nowrap">{{ $asset->procurementRequest->request_reference ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">{{ $emptyMessage }}</td>
                                        </tr>
                                    @endforelse
                                    @if ($assets->isNotEmpty())
                                        <tr data-inventory-no-match class="d-none">
                                            <td colspan="7" class="text-center py-4 text-muted">No assets match your search.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('inventorySearch');
        const studioFilter = document.getElementById('inventoryStudioFilter');

        function filterRows() {
            const keyword = (search.value || '').toLowerCase().trim();
            const studio = studioFilter ? studioFilter.value : '';

            document.querySelectorAll('[data-inventory-table]').forEach(function (body) {
                let visible = 0;
                body.querySelectorAll('[data-inventory-row]').forEach(function (row) {
                    const show = (!keyword || row.dataset.search.includes(keyword))
                        && (!studio || row.dataset.studio === studio);
                    row.classList.toggle('d-none', !show);
                    if (show) visible++;
                });
                const noMatch = body.querySelector('[data-inventory-no-match]');
                if (noMatch) noMatch.classList.toggle('d-none', visible > 0);
            });
        }

        search.addEventListener('input', filterRows);
        if (studioFilter) studioFilter.addEventListener('change', filterRows);
    });
</script>
