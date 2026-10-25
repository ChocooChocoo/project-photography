<script>
// Loads partials/booking-reschedule into a booking modal and sends its forms.
window.mountReschedule = function (element, url) {
    const mount = Symbol();
    element.rescheduleMount = mount;
    async function load(message) {
        if (element.rescheduleMount !== mount) return;
        try {
            const response = await fetch(url, {headers: {Accept: 'text/html'}});
            if (!response.ok) throw new Error('Could not load reschedule details. Reopen the booking to retry.');
            const html = await response.text();
            if (element.rescheduleMount !== mount) return;
            element.innerHTML = html;
            if (message) {
                const saved = document.createElement('p');
                saved.setAttribute('role', 'status');
                saved.className = 'fw-medium';
                saved.textContent = message;
                saved.tabIndex = -1;
                element.prepend(saved);
                saved.focus({preventScroll: true});
            }
        } catch (error) {
            if (element.rescheduleMount === mount) element.textContent = error.message;
        }
    }
    element.onsubmit = async event => {
        const form = event.target.closest('[data-reschedule-action]');
        if (!form) return;
        event.preventDefault();
        const buttons = form.querySelectorAll('button');
        buttons.forEach(button => button.disabled = true);
        let feedback = form.querySelector('[data-reschedule-feedback]');
        if (!feedback) {
            feedback = document.createElement('p');
            feedback.setAttribute('role', 'alert');
            feedback.dataset.rescheduleFeedback = '';
            feedback.className = 'text-danger mt-2 mb-0';
            form.append(feedback);
        }
        feedback.textContent = '';
        try {
            const response = await fetch(form.dataset.rescheduleAction, {
                method: 'POST',
                headers: {Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: new FormData(form),
            });
            const result = await response.json();
            if (element.rescheduleMount !== mount) return;
            if (!response.ok) {
                throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Could not save. Try again.');
            }
            await load(result.message);
        } catch (error) {
            if (element.rescheduleMount === mount) feedback.textContent = error.message;
        } finally {
            buttons.forEach(button => button.disabled = false);
        }
    };
    element.textContent = 'Loading reschedule details...';
    load();
};
</script>
