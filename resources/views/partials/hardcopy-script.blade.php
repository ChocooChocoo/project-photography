<script>
window.mountHardcopy = function (element, bookingId, owner) {
    const mount = Symbol();
    element.hardcopyMount = mount;
    const url = '/' + (owner ? 'owner' : 'client') + '/bookings/' + bookingId + '/hardcopy';
    let nextItem = 0;
    function addItem(form) {
        const index = nextItem++;
        const row = document.createElement('fieldset');
        row.className = 'border rounded p-3 mb-3';
        row.innerHTML = `<legend class="fs-6">Item ${index + 1}</legend>
            <label class="form-label" for="hc-name-${index}">Item name</label><input id="hc-name-${index}" name="items[${index}][name]" class="form-control mb-2" maxlength="255" required>
            <label class="form-label" for="hc-size-${index}">Size</label><input id="hc-size-${index}" name="items[${index}][size]" class="form-control mb-2" maxlength="100">
            <div class="row"><div class="col-6"><label class="form-label" for="hc-qty-${index}">Quantity</label><input id="hc-qty-${index}" name="items[${index}][quantity]" type="number" min="1" max="100" value="1" class="form-control mb-2" required></div>
            <div class="col-6"><label class="form-label" for="hc-price-${index}">Unit price (PHP)</label><input id="hc-price-${index}" name="items[${index}][unit_price]" type="number" min="0" max="99999.99" step="0.01" class="form-control mb-2" required></div></div>
            <button type="button" class="btn btn-outline-secondary" style="min-height:44px" data-hardcopy-remove>Remove item</button>`;
        row.querySelector('[data-hardcopy-remove]').onclick = () => row.remove();
        form.querySelector('[data-hardcopy-items]').append(row);
    }
    async function load() {
        if (element.hardcopyMount !== mount) return;
        element.textContent = 'Loading hardcopy orders...';
        try {
            const response = await fetch(url, {headers: {Accept: 'text/html'}});
            if (!response.ok) throw new Error('Could not load hardcopy orders. Reopen the booking to retry.');
            const html = await response.text();
            if (element.hardcopyMount !== mount) return;
            element.innerHTML = html;
            const quote = element.querySelector('[data-hardcopy-quote]');
            if (quote) { addItem(quote); quote.querySelector('[data-hardcopy-add]').onclick = () => addItem(quote); }
        } catch (error) { if (element.hardcopyMount === mount) element.textContent = error.message; }
    }
    element.onsubmit = async event => {
        const form = event.target.closest('[data-hardcopy-action]');
        if (!form) return;
        event.preventDefault();
        const buttons = form.querySelectorAll('button');
        buttons.forEach(button => button.disabled = true);
        const feedback = document.createElement('p');
        feedback.setAttribute('role', 'status');
        form.append(feedback);
        try {
            const response = await fetch(form.dataset.hardcopyAction, {method: 'POST', headers: {Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: new FormData(form)});
            const result = await response.json();
            if (element.hardcopyMount !== mount) return;
            if (!response.ok) throw new Error(result.message || 'Could not save the order. Try again.');
            await load();
            const saved = document.createElement('p'); saved.setAttribute('role', 'status'); saved.textContent = 'Hardcopy order saved. Reopen booking details to refresh payment controls.'; element.prepend(saved);
        } catch (error) { if (element.hardcopyMount === mount) feedback.textContent = error.message; }
        finally { buttons.forEach(button => button.disabled = false); }
    };
    load();
};
</script>
