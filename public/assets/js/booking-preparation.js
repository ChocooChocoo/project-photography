(() => {
    'use strict';
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    async function request(url, method = 'GET', data) {
        const response = await fetch(url, {
            method, headers: {Accept: 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            body: data === undefined ? undefined : JSON.stringify(data)
        });
        const body = await response.json();
        if (!response.ok || body.success === false) {
            throw new Error(Object.values(body.errors || {}).flat().join(' ') || body.message || 'Request failed. Please try again.');
        }
        return body;
    }

    window.mountBookingPreparation = (root, url) => {
        const load = async () => {
            root.innerHTML = '<p role="status">Loading booking preparation...</p>';
            try {
                const response = await fetch(url, {headers: {Accept: 'text/html'}});
                if (!response.ok) throw new Error('Could not load booking preparation.');
                const html = await response.text();
                if (root.isConnected) root.innerHTML = html;
            } catch (error) {
                if (!root.isConnected) return;
                root.innerHTML = '<p role="alert">Could not load booking preparation. Please try again.</p><button type="button" class="btn btn-outline-primary">Retry preparation</button>';
                root.querySelector('button').onclick = load;
            }
        };
        load();
    };

    window.mountBookingEquipment = (root, booking, assignments) => {
        const routes = window.bookingEquipmentRoutes;
        const url = (key, id) => routes[key].replace('__ID__', id);
        const photographers = assignments.filter(a => a.status !== 'cancelled');
        const assignable = photographers.length > 0 && !['cancelled', 'completed'].includes(booking.status);
        let catalog = [];
        root.innerHTML = `
            <h5>Equipment preparation</h5>
            <p>Preferred camera brand: <strong>${escape(booking.preferred_camera_brand || 'No preference')}</strong></p>
            <div role="status" class="equipment-feedback mb-2"></div>
            <div class="equipment-list">Loading equipment...</div>
            <form class="equipment-assign mt-3">
                <fieldset ${assignable ? '' : 'disabled'}>
                    <legend class="h6">Assign equipment</legend>
                    ${assignable ? '' : '<p>Assign a photographer first. Closed bookings cannot receive equipment.</p>'}
                    <label for="catalogChoice" class="form-label">Catalog item</label>
                    <select id="catalogChoice" name="equipment_id" class="form-select mb-2"><option value="">Use free-text equipment</option></select>
                    <div class="free-text-fields row g-2">
                        <div class="col-md-4"><label for="equipmentName" class="form-label">Equipment name</label><input id="equipmentName" name="equipment_name" class="form-control" maxlength="255"></div>
                        <div class="col-md-4"><label for="equipmentType" class="form-label">Type</label><input id="equipmentType" name="equipment_type" class="form-control" maxlength="255"></div>
                        <div class="col-md-4"><label for="equipmentBrand" class="form-label">Brand (optional)</label><input id="equipmentBrand" name="brand" class="form-control" maxlength="255"></div>
                    </div>
                    <label for="equipmentPhotographer" class="form-label mt-2">Photographer (optional)</label>
                    <select id="equipmentPhotographer" name="assignment_id" class="form-select"><option value="">Whole booking</option>${photographers.map(a => `<option value="${a.id}">${escape((a.photographer?.first_name || '')+' '+(a.photographer?.last_name || ''))}</option>`).join('')}</select>
                    <label for="equipmentNotes" class="form-label mt-2">Notes (optional)</label><textarea id="equipmentNotes" name="notes" class="form-control" maxlength="2000"></textarea>
                    <button class="btn btn-primary mt-2" type="submit">Assign equipment</button>
                </fieldset>
            </form>
            <details class="mt-4">
                <summary class="mb-3">Manage studio equipment catalog</summary>
                <div class="catalog-list">Loading catalog...</div>
                <form class="catalog-form mt-3">
                    <h6 class="catalog-form-title">Add catalog item</h6>
                    <input type="hidden" name="id">
                    <div class="row g-2">
                        ${[['name','Name'],['type','Type'],['brand','Brand (optional)'],['model','Model (optional)']].map(([name,label]) => `<div class="col-md-6"><label for="catalog-${name}" class="form-label">${label}</label><input id="catalog-${name}" name="${name}" class="form-control" maxlength="${name === 'type' ? 80 : 255}" ${['name','type'].includes(name) ? 'required' : ''}></div>`).join('')}
                    </div>
                    <label for="catalogStatus" class="form-label mt-2">Status</label><select id="catalogStatus" name="status" class="form-select"><option value="available">Available</option><option value="maintenance">Maintenance</option><option value="retired">Retired</option></select>
                    <button type="submit" class="btn btn-primary mt-2">Save catalog item</button>
                    <button type="reset" class="btn btn-outline-secondary mt-2">Clear catalog form</button>
                </form>
            </details>`;
        const feedback = root.querySelector('.equipment-feedback');
        const report = (message, failed = false) => {
            feedback.textContent = message;
            feedback.setAttribute('role', failed ? 'alert' : 'status');
            feedback.classList.toggle('text-danger', failed);
        };
        const refresh = async () => {
            try {
                const [items, assigned] = await Promise.all([request(url('catalog', booking.provider_id)), request(url('assigned', booking.id))]);
                if (!root.isConnected) return;
                catalog = items.data;
                root.querySelector('#catalogChoice').innerHTML = '<option value="">Use free-text equipment</option>' + catalog.filter(i => i.status === 'available').map(i => `<option value="${i.id}">${escape([i.name,i.brand,i.model].filter(Boolean).join(' / '))}</option>`).join('');
                root.querySelector('.equipment-list').innerHTML = '<h6>Assigned equipment</h6>' + (assigned.data.length ? `<ul class="ps-3">${assigned.data.map(i => `<li class="mb-2 text-break">${escape(i.equipment_name)} (${escape(i.equipment_type)})<br>Brand: ${escape(i.brand || 'Not specified')}. ${i.confirmed ? 'Confirmed by photographer' : 'Awaiting confirmation'} ${['completed','cancelled'].includes(booking.status) ? '' : `<button type="button" class="btn btn-outline-danger ms-2" data-remove="${i.id}">Remove equipment</button>`}</li>`).join('')}</ul>` : '<p>No equipment assigned yet.</p>');
                root.querySelector('.catalog-list').innerHTML = catalog.length ? `<ul class="ps-3">${catalog.map(i => `<li class="mb-2 text-break">${escape([i.name,i.brand,i.model,i.status].filter(Boolean).join(' / '))}<div class="d-flex gap-2 mt-1"><button type="button" class="btn btn-outline-primary" data-edit="${i.id}">Edit ${escape(i.name)}</button><button type="button" class="btn btn-outline-danger" data-delete="${i.id}">Delete ${escape(i.name)}</button></div></li>`).join('')}</ul>` : '<p>No catalog items. Add equipment below, or assign free-text equipment.</p>';
            } catch (error) {
                report(error.message, true);
                root.querySelector('.equipment-list').innerHTML = '<button type="button" class="btn btn-outline-primary equipment-retry">Retry equipment</button>';
            }
        };
        root.querySelector('#catalogChoice').onchange = event => {
            root.querySelector('.free-text-fields').hidden = !!event.target.value;
        };
        const assignForm = root.querySelector('.equipment-assign');
        assignForm.onsubmit = async event => {
            event.preventDefault();
            const button = assignForm.querySelector('button');
            button.disabled = true;
            try {
                const data = Object.fromEntries(new FormData(assignForm));
                if (!data.equipment_id) delete data.equipment_id;
                await request(url('assigned', booking.id), 'POST', data);
                report('Equipment assigned.');
                assignForm.reset();
                root.querySelector('.free-text-fields').hidden = false;
                await refresh();
            } catch (error) { report(error.message, true); }
            finally { button.disabled = false; }
        };
        const catalogForm = root.querySelector('.catalog-form');
        catalogForm.onreset = () => { root.querySelector('.catalog-form-title').textContent = 'Add catalog item'; };
        catalogForm.onsubmit = async event => {
            event.preventDefault();
            const button = catalogForm.querySelector('[type=submit]');
            button.disabled = true;
            try {
                const data = Object.fromEntries(new FormData(catalogForm));
                const id = data.id; delete data.id;
                await request(id ? url('update', id) : url('catalog', booking.provider_id), id ? 'PUT' : 'POST', data);
                catalogForm.reset(); report('Catalog saved.'); await refresh();
            } catch (error) { report(error.message, true); }
            finally { button.disabled = false; }
        };
        root.onclick = async event => {
            const button = event.target.closest('button');
            if (!button) return;
            if (button.classList.contains('equipment-retry')) { await refresh(); return; }
            if (button.dataset.edit) {
                const item = catalog.find(i => String(i.id) === button.dataset.edit);
                for (const key of ['id','name','type','brand','model','status']) catalogForm.elements[key].value = item[key] ?? '';
                root.querySelector('.catalog-form-title').textContent = 'Edit catalog item';
                catalogForm.elements.name.focus(); return;
            }
            const id = button.dataset.remove || button.dataset.delete;
            if (!id) return;
            const confirmation = await Swal.fire({title: 'Remove equipment?', text: 'Booking history will remain unchanged.', showCancelButton: true, confirmButtonText: 'Remove equipment', cancelButtonText: 'Keep equipment'});
            if (!confirmation.isConfirmed) return;
            button.disabled = true;
            try { await request(url(button.dataset.remove ? 'remove' : 'delete', id), 'DELETE'); report('Equipment removed.'); await refresh(); }
            catch (error) { report(error.message, true); button.disabled = false; }
        };
        refresh();
    };
})();
