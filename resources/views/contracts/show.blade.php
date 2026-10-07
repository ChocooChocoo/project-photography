@extends($layout)
@section('title', 'Booking Contract')

@section('styles')
    <style>
        .contract-sheet { max-width: 820px; margin: 0 auto; }
        .contract-text { line-height: 1.7; font-size: 15px; }
        .signature-box { border: 1px dashed var(--bs-border-color, #adb5bd); border-radius: 8px; background: #fff; touch-action: none; }
        .signature-box canvas { width: 100%; height: 180px; display: block; cursor: crosshair; }
        .signature-block img { max-height: 90px; background: #fff; border-radius: 6px; padding: 4px; }
        @media print {
            button[data-bs-target="#studioChatbotModal"], .no-print, .sidebar-menu, .topbar-custom, .app-sidebar, .topbar, nav, .left-side-menu, .footer { display: none !important; }
            .content-page, .page-content, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            .contract-sheet, .contract-sheet * { color: #000 !important; box-shadow: none !important; }
            .contract-sheet .card { border: 0 !important; background: #fff !important; }
            .signature-block img { background: #fff; }
        }
    </style>
@endsection

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="contract-sheet mt-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
                    <div>
                        <h4 class="mb-1">Booking Contract</h4>
                        <span class="text-muted">Booking #{{ $booking->booking_reference }}</span>
                        <span class="badge {{ $contract->status === 'fully_signed' ? 'badge-soft-success' : 'badge-soft-warning' }} ms-2">
                            {{ $contract->status === 'fully_signed' ? 'Fully signed' : 'Waiting for signatures' }}
                        </span>
                    </div>
                    <button type="button" class="btn btn-default" onclick="window.print()"><i class="ti ti-printer me-1"></i> Print</button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="contract-text">{!! $contract->terms_html !!}</div>

                        <hr class="my-4">

                        <div class="row g-4">
                            <div class="col-md-6 signature-block">
                                <h6 class="text-muted mb-2">{{ $providerName }} (Provider)</h6>
                                @if ($contract->studio_signed_at)
                                    <img src="{{ asset('storage/' . $contract->studio_signature) }}" alt="Provider signature">
                                    <div class="fw-medium mt-1">{{ $contract->studio_signed_by }}</div>
                                    <small class="text-muted">Signed {{ $contract->studio_signed_at->format('F j, Y g:i A') }}</small>
                                @else
                                    <span class="text-muted">Not signed yet</span>
                                @endif
                            </div>
                            <div class="col-md-6 signature-block">
                                <h6 class="text-muted mb-2">{{ $booking->client->full_name ?? 'Client' }} (Client)</h6>
                                @if ($contract->client_signed_at)
                                    <img src="{{ asset('storage/' . $contract->client_signature) }}" alt="Client signature">
                                    <div class="fw-medium mt-1">{{ $contract->client_signed_by }}</div>
                                    <small class="text-muted">Signed {{ $contract->client_signed_at->format('F j, Y g:i A') }}</small>
                                @else
                                    <span class="text-muted">Not signed yet</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if (! $signedAt && ! in_array($booking->status, ['cancelled', 'completed'], true))
                    <div class="card mt-3 no-print" id="signCard">
                        <div class="card-body">
                            <h5 class="mb-3">Sign this contract</h5>
                            <div class="mb-3">
                                <label class="form-label" for="signedName">Type your full name</label>
                                <input type="text" id="signedName" class="form-control" maxlength="255" autocomplete="name">
                            </div>
                            <label class="form-label">Draw your signature</label>
                            <div class="signature-box mb-2"><canvas id="signaturePad" aria-label="Signature drawing area"></canvas></div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="button" class="btn btn-default btn-sm" id="clearSignature">Clear</button>
                                <button type="button" class="btn btn-primary" id="submitSignature">Sign contract</button>
                                <span class="text-danger small" id="signError" role="alert"></span>
                            </div>
                            <small class="text-muted d-block mt-2">By signing you agree to the terms above.</small>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const canvas = document.getElementById('signaturePad');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            let drawing = false;
            let hasInk = false;

            function fit() {
                const ratio = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                ctx.scale(ratio, ratio);
                ctx.lineWidth = 2.2;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#111';
                hasInk = false;
            }
            fit();

            function point(e) {
                const rect = canvas.getBoundingClientRect();
                return { x: e.clientX - rect.left, y: e.clientY - rect.top };
            }

            canvas.addEventListener('pointerdown', (e) => {
                drawing = true;
                canvas.setPointerCapture(e.pointerId);
                const p = point(e);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            });
            canvas.addEventListener('pointermove', (e) => {
                if (!drawing) return;
                const p = point(e);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
                hasInk = true;
            });
            ['pointerup', 'pointercancel'].forEach((ev) => canvas.addEventListener(ev, () => { drawing = false; }));

            document.getElementById('clearSignature').addEventListener('click', fit);

            const error = document.getElementById('signError');
            const button = document.getElementById('submitSignature');

            button.addEventListener('click', async () => {
                error.textContent = '';
                const name = document.getElementById('signedName').value.trim();

                if (name.length < 3) { error.textContent = 'Please type your full name.'; return; }
                if (!hasInk) { error.textContent = 'Please draw your signature in the box.'; return; }

                button.disabled = true;
                try {
                    const response = await fetch(@json(route($role . '.contracts.sign', ['booking' => $booking->id])), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ signed_name: name, signature: canvas.toDataURL('image/png') }),
                    });
                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        error.textContent = result.message || (result.errors ? Object.values(result.errors)[0][0] : 'Could not save your signature.');
                        button.disabled = false;
                        return;
                    }
                    window.location.reload();
                } catch (e) {
                    error.textContent = 'Network error. Please try again.';
                    button.disabled = false;
                }
            });
        })();
    </script>
@endsection
