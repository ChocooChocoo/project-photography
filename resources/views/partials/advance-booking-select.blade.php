{{--
    Minimum advance-booking notice: 1 day, 2 days, or 3+ days (3-30).
    The number input carries the submitted value; the select only sets it.
    Params: $name (input name), $value (current days), $id (input id, optional).
--}}
@php
    $days = max(1, min(30, (int) ($value ?? 1) ?: 1));
    $choice = $days >= 3 ? '3+' : (string) $days;
    $inputId = $id ?? ($name.'_input');
@endphp
<div class="advance-booking-select" data-advance-booking>
    <select class="form-select" data-advance-choice aria-label="Minimum advance notice">
        <option value="1" @selected($choice === '1')>1 day before the event</option>
        <option value="2" @selected($choice === '2')>2 days before the event</option>
        <option value="3+" @selected($choice === '3+')>3 or more days before the event</option>
    </select>
    <div class="input-group mt-2 {{ $choice === '3+' ? '' : 'd-none' }}" data-advance-days-wrap>
        <input type="number" class="form-control" id="{{ $inputId }}" name="{{ $name }}" value="{{ $days }}" min="1" max="30" step="1" required data-advance-days aria-label="Number of days of advance notice">
        <span class="input-group-text">days (3 to 30)</span>
    </div>
</div>

@once
    <script>
        document.addEventListener('change', function (event) {
            const select = event.target.closest('[data-advance-choice]');
            if (!select) return;
            const root = select.closest('[data-advance-booking]');
            const input = root.querySelector('[data-advance-days]');
            const wrap = root.querySelector('[data-advance-days-wrap]');
            if (select.value === '3+') {
                wrap.classList.remove('d-none');
                if (Number(input.value) < 3) input.value = 3;
                input.min = 3;
                input.focus();
            } else {
                wrap.classList.add('d-none');
                input.min = 1;
                input.value = select.value;
            }
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-advance-booking]').forEach(function (root) {
                if (root.querySelector('[data-advance-choice]').value === '3+') {
                    root.querySelector('[data-advance-days]').min = 3;
                }
            });
        });
    </script>
@endonce
