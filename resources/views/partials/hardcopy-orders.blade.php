<h5>Printed photos and albums</h5>
<p>Review the items and price before accepting. Accepted costs are added to the booking balance.</p>
@php($open = in_array($booking->status, ['confirmed', 'in_progress'], true))
@forelse($orders as $order)
    <article class="border rounded p-3 mb-3" style="overflow-wrap:anywhere">
        <h6>Order #{{ $order->id }}: {{ ucfirst($order->status) }}</h6>
        <div class="table-responsive">
            <table class="table"><caption class="visually-hidden">Hardcopy items and prices</caption>
                <thead><tr><th>Item / size</th><th>Quantity</th><th>Unit price</th></tr></thead>
                <tbody>@foreach($order->items as $item)<tr><td>{{ $item->name }} {{ $item->size }}</td><td>{{ $item->quantity }}</td><td>PHP {{ number_format($item->unit_price, 2) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <p><strong>Total: PHP {{ number_format($order->amount, 2) }}</strong></p>
        <p>Fulfillment: {{ ucfirst($order->fulfillment_method) }}. {{ $order->accepted_at ? 'Client accepted.' : 'Awaiting client acceptance.' }}</p>
        @if($order->delivery_address)<p>Address: {{ $order->delivery_address }}<br>Contact: {{ $order->contact_number }}</p>@endif
        @if($order->tracking_number)<p>Courier: {{ $order->courier_name }}<br>Tracking: {{ $order->tracking_number }}</p>@endif
        @if(!$owner && !$order->accepted_at && $order->status === 'pending' && $open)
            <form data-hardcopy-action="{{ route('client.hardcopy.accept', [$booking->id, $order->id]) }}">
                <label class="form-label" for="hc-method-{{ $order->id }}">Choose fulfillment</label>
                <select id="hc-method-{{ $order->id }}" name="fulfillment_method" class="form-select mb-2" required><option value="pickup">Studio pickup</option><option value="courier">Third-party courier</option></select>
                <label class="form-label" for="hc-address-{{ $order->id }}">Delivery address (required for courier)</label>
                <textarea id="hc-address-{{ $order->id }}" name="delivery_address" class="form-control mb-2" maxlength="2000"></textarea>
                <label class="form-label" for="hc-contact-{{ $order->id }}">Contact number (required for courier)</label>
                <input id="hc-contact-{{ $order->id }}" name="contact_number" class="form-control mb-2" maxlength="40">
                <button class="btn btn-primary" style="min-height:44px;--ins-btn-bg:#2e69c5;--ins-btn-border-color:#2e69c5">Accept quote</button>
            </form>
        @elseif($owner && $open && !in_array($order->status, ['cancelled', 'delivered'], true))
            <form data-hardcopy-action="{{ route('owner.hardcopy.status', [$booking->id, $order->id]) }}">
                @php($next = ['pending' => 'preparing', 'preparing' => 'ready', 'ready' => $order->fulfillment_method === 'pickup' ? 'delivered' : 'shipped', 'shipped' => 'delivered'][$order->status])
                <input type="hidden" name="status" value="{{ $order->accepted_at ? $next : 'cancelled' }}">
                @if($order->accepted_at && $next === 'shipped')
                    <label class="form-label" for="hc-courier-{{ $order->id }}">Courier name</label><input id="hc-courier-{{ $order->id }}" name="courier_name" class="form-control mb-2" maxlength="255" required>
                    <label class="form-label" for="hc-tracking-{{ $order->id }}">Tracking number</label><input id="hc-tracking-{{ $order->id }}" name="tracking_number" class="form-control mb-2" maxlength="255" required>
                @endif
                <button class="btn btn-outline-primary" style="min-height:44px;--ins-btn-color:var(--ins-body-color);--ins-btn-border-color:var(--ins-body-color);--ins-btn-hover-bg:#2e69c5;--ins-btn-hover-border-color:#2e69c5">{{ $order->accepted_at ? 'Mark '.$next : 'Cancel quote' }}</button>
            </form>
        @endif
    </article>
@empty
    <p>No hardcopy quote yet. Your provider can add printed photos, frames, or albums here.</p>
@endforelse
@if($owner && $open && !$orders->contains(fn($o) => !in_array($o->status, ['cancelled', 'delivered'], true)))
    <form data-hardcopy-action="{{ route('owner.hardcopy.store', $booking->id) }}" data-hardcopy-quote>
        <h6>Create hardcopy quote</h6>
        <div data-hardcopy-items></div>
        <button type="button" class="btn btn-outline-secondary mb-3" data-hardcopy-add style="min-height:44px">Add item</button>
        <label class="form-label" for="hc-quote-method">Suggested fulfillment</label>
        <select id="hc-quote-method" name="fulfillment_method" class="form-select mb-3" required><option value="pickup">Studio pickup</option><option value="courier">Third-party courier</option></select>
        <button class="btn btn-primary" style="min-height:44px;--ins-btn-bg:#2e69c5;--ins-btn-border-color:#2e69c5">Create hardcopy quote</button>
    </form>
@endif
@if(!$owner && $orders->contains(fn($o) => $o->accepted_at) && $booking->remaining_balance > 0)
    <p class="mt-3">Remaining booking balance: PHP {{ number_format($booking->remaining_balance, 2) }}. Use the booking's payment button to pay through existing checkout.</p>
@endif
