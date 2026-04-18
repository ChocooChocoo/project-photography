@php
    $existingRequestPayload = $existingRequest
        ? [
            'id' => $existingRequest->id,
            'status' => $existingRequest->status,
            'request_reference' => $existingRequest->request_reference,
            'purpose' => $existingRequest->purpose,
            'required_date' => optional($existingRequest->required_date)->format('Y-m-d'),
            'is_urgent' => (bool) $existingRequest->is_urgent,
            'inventory_bypass_reason' => $existingRequest->inventory_bypass_reason,
            'items' => $existingRequest->items->map(fn ($item) => [
                'item_name' => $item->item_name,
                'description' => $item->description,
                'category' => $item->category,
                'quantity' => (float) $item->quantity,
                'unit_of_measure' => $item->unit_of_measure,
                'estimated_unit_cost' => (float) $item->estimated_unit_cost,
                'preferred_supplier' => $item->preferred_supplier,
            ])->values(),
        ]
        : null;
@endphp

<div class="content-page">
    <div class="container-fluid">
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <h4 class="mb-1">{{ $existingRequest ? 'Edit Procurement Request' : 'Request Procurement' }}</h4>
                        <p class="text-muted mb-0">
                            Create a multi-item procurement request for finance review and studio owner approval.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route($indexRoute) }}" class="btn btn-light">
                            <i class="ti ti-list-details me-1"></i> View Requests
                        </a>
                        <a href="{{ route($portalHomeRoute) }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Workflow Notes</h5>
                    </div>
                    <div class="card-body">
                        <div class="border rounded p-3 bg-light-subtle mb-3">
                            <label class="text-muted small mb-1 d-block">Portal</label>
                            <h5 class="mb-0">{{ $portalLabel }}</h5>
                            <small class="text-muted">{{ auth()->user()->full_name }}</small>
                        </div>

                        <div class="border rounded p-3 bg-light-subtle mb-3">
                            <label class="text-muted small mb-1 d-block">Assigned Studio</label>
                            <h5 class="mb-0">{{ $assignedStudio->studio_name }}</h5>
                            <small class="text-muted">Studio ID: {{ $assignedStudio->id }}</small>
                        </div>

                        <div class="border rounded p-3 bg-light-subtle mb-3">
                            <label class="text-muted small mb-1 d-block">Estimated Total</label>
                            <h3 class="mb-0">PHP <span id="estimatedTotalPreview">0.00</span></h3>
                            <small class="text-muted">Auto-calculated from all request line items.</small>
                        </div>

                        <div class="border rounded p-3">
                            <label class="text-muted small mb-1 d-block">Status on Save</label>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge badge-soft-secondary">Draft</span>
                                <span class="badge badge-soft-warning">Pending Finance Review</span>
                                <span class="badge badge-soft-info">Returned for Revision</span>
                            </div>
                            <p class="text-muted small mb-0 mt-3">
                                Save as draft to continue later, or submit once the item list, date, and business purpose are complete.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Procurement Request Form</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
                            <i class="ti ti-alert-triangle fs-18 mt-1"></i>
                            <div>
                                Include a bypass reason if the same item is already available in studio inventory and still needs to be purchased.
                            </div>
                        </div>

                        <form id="procurementRequestForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="action" id="procurementAction" value="save_draft">

                            <div class="row g-4">
                                <div class="col-md-8">
                                    <label for="purpose" class="form-label">Purpose / Project <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="purpose" name="purpose" rows="4" placeholder="Describe why the requested items are needed...">{{ $existingRequest?->purpose ?? '' }}</textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="required_date" class="form-label">Required Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="required_date" name="required_date" min="{{ now()->toDateString() }}" value="{{ optional($existingRequest?->required_date)->format('Y-m-d') }}">

                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="is_urgent" name="is_urgent" value="1" {{ !empty($existingRequest?->is_urgent) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_urgent">
                                            Mark as urgent
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="inventory_bypass_reason" class="form-label">Inventory Bypass Reason</label>
                                    <textarea class="form-control" id="inventory_bypass_reason" name="inventory_bypass_reason" rows="3" placeholder="Explain why this request should continue even if the same item already exists in inventory...">{{ $existingRequest?->inventory_bypass_reason ?? '' }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label for="request_attachments" class="form-label">Supporting Attachments</label>
                                    <input type="file" class="form-control" id="request_attachments" name="request_attachments[]" multiple>
                                    <small class="text-muted">Accepted: PDF, image, Word, or Excel files up to 5 MB each.</small>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h5 class="mb-1">Requested Items</h5>
                                            <p class="text-muted mb-0">Add each equipment or consumable item needed for this request.</p>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary" id="addItemRowBtn">
                                            <i class="ti ti-plus me-1"></i> Add Item
                                        </button>
                                    </div>

                                    <div class="table-responsive border rounded">
                                        <table class="table table-centered align-middle mb-0">
                                            <thead class="bg-light-subtle">
                                                <tr>
                                                    <th style="min-width: 180px;">Item</th>
                                                    <th style="min-width: 170px;">Description</th>
                                                    <th style="min-width: 130px;">Category</th>
                                                    <th style="min-width: 110px;">Qty</th>
                                                    <th style="min-width: 120px;">Unit</th>
                                                    <th style="min-width: 150px;">Est. Unit Cost</th>
                                                    <th style="min-width: 170px;">Preferred Supplier</th>
                                                    <th class="text-end">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="procurementItemsBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-md-row justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-light" id="saveDraftBtn">
                                    <i class="ti ti-device-floppy me-1"></i> Save Draft
                                </button>
                                <button type="button" class="btn btn-primary" id="submitRequestBtn">
                                    <i class="ti ti-send me-1"></i> Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        const existingRequest = @json($existingRequestPayload);
        const itemsBody = $('#procurementItemsBody');
        let nextAction = 'save_draft';

        function createItemRow(item = {}) {
            const index = itemsBody.children().length;

            return `
                <tr class="procurement-item-row">
                    <td><input type="text" class="form-control" name="items[${index}][item_name]" value="${item.item_name || ''}" placeholder="Camera battery"></td>
                    <td><textarea class="form-control" name="items[${index}][description]" rows="2" placeholder="Optional details">${item.description || ''}</textarea></td>
                    <td>
                        <select class="form-select" name="items[${index}][category]">
                            <option value="equipment" ${(item.category || '') === 'equipment' ? 'selected' : ''}>Equipment</option>
                            <option value="consumable" ${(item.category || '') === 'consumable' ? 'selected' : ''}>Consumable</option>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" min="0.01" class="form-control item-quantity" name="items[${index}][quantity]" value="${item.quantity || ''}" placeholder="1"></td>
                    <td><input type="text" class="form-control" name="items[${index}][unit_of_measure]" value="${item.unit_of_measure || ''}" placeholder="pcs"></td>
                    <td><input type="number" step="0.01" min="0" class="form-control item-cost" name="items[${index}][estimated_unit_cost]" value="${item.estimated_unit_cost || ''}" placeholder="0.00"></td>
                    <td><input type="text" class="form-control" name="items[${index}][preferred_supplier]" value="${item.preferred_supplier || ''}" placeholder="Optional"></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-soft-danger btn-sm remove-item-row">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }

        function recalculateEstimatedTotal() {
            let total = 0;

            itemsBody.find('.procurement-item-row').each(function () {
                const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
                const cost = parseFloat($(this).find('.item-cost').val()) || 0;
                total += quantity * cost;
            });

            $('#estimatedTotalPreview').text(total.toFixed(2));
        }

        function rebuildItemIndexes() {
            itemsBody.find('.procurement-item-row').each(function (index) {
                $(this).find('input, textarea, select').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/items\[\d+\]/, `items[${index}]`));
                    }
                });
            });
        }

        function showAjaxError(xhr) {
            const response = xhr.responseJSON || {};
            let errorText = response.message || 'Unable to save the procurement request.';

            if (response.errors) {
                errorText = Object.values(response.errors).flat().join('\n');
            }

            Swal.fire({
                icon: 'error',
                title: 'Request Error',
                text: errorText,
                confirmButtonColor: '#3475db'
            });
        }

        function setButtonLoading($button, loadingText) {
            if (!$button || !$button.length) {
                return;
            }

            if (!$button.data('original-html')) {
                $button.data('original-html', $button.html());
            }

            $button.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${loadingText}
            `);
        }

        function resetButtonLoading($button) {
            if (!$button || !$button.length) {
                return;
            }

            $button.prop('disabled', false);

            if ($button.data('original-html')) {
                $button.html($button.data('original-html'));
            }
        }

        function submitForm(actionValue) {
            nextAction = actionValue;
            $('#procurementAction').val(actionValue);
            const $activeButton = actionValue === 'submit' ? $('#submitRequestBtn') : $('#saveDraftBtn');
            const $inactiveButton = actionValue === 'submit' ? $('#saveDraftBtn') : $('#submitRequestBtn');

            const formElement = document.getElementById('procurementRequestForm');
            const formData = new FormData(formElement);
            const isEdit = Boolean(existingRequest && existingRequest.id);

            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            setButtonLoading($activeButton, actionValue === 'submit' ? 'Submitting...' : 'Saving...');
            $inactiveButton.prop('disabled', true);

            $.ajax({
                url: isEdit ? `{{ route($updateRoute, ['id' => '__id__']) }}`.replace('__id__', existingRequest.id) : '{{ route($storeRoute) }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json'
                },
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1800,
                        timerProgressBar: true,
                        didClose: function () {
                            window.location.href = '{{ route($indexRoute) }}';
                        }
                    });
                },
                error: function (xhr) {
                    resetButtonLoading($activeButton);
                    $inactiveButton.prop('disabled', false);
                    showAjaxError(xhr);
                }
            });
        }

        $('#addItemRowBtn').on('click', function () {
            itemsBody.append(createItemRow());
        });

        itemsBody.on('click', '.remove-item-row', function () {
            if (itemsBody.children().length === 1) {
                return;
            }

            $(this).closest('tr').remove();
            rebuildItemIndexes();
            recalculateEstimatedTotal();
        });

        itemsBody.on('input', '.item-quantity, .item-cost', recalculateEstimatedTotal);

        $('#saveDraftBtn').on('click', function () {
            submitForm('save_draft');
        });

        $('#submitRequestBtn').on('click', function () {
            submitForm('submit');
        });

        if (existingRequest && existingRequest.items && existingRequest.items.length) {
            existingRequest.items.forEach(function (item) {
                itemsBody.append(createItemRow(item));
            });
        } else {
            itemsBody.append(createItemRow());
        }

        recalculateEstimatedTotal();
    });
</script>
