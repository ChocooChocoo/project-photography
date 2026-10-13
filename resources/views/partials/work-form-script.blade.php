<script>
document.querySelectorAll('[data-work-form]').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const summary = form.querySelector('[role="status"]');
        form.querySelectorAll('[data-field-error]').forEach(node => node.remove());
        form.querySelectorAll('[aria-invalid]').forEach(node => { node.removeAttribute('aria-invalid'); node.removeAttribute('aria-describedby'); });
        button.disabled = true;
        summary.textContent = 'Saving...';
        try {
            const response = await fetch(form.action, {method: 'POST', headers: {Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: new FormData(form)});
            const result = await response.json();
            if (!response.ok || result.success === false) {
                Object.entries(result.errors || {}).forEach(([name, messages]) => {
                    const input = form.elements.namedItem(name) || form.elements.namedItem(name.split('.')[0] + '[]');
                    if (!input?.id) return;
                    const error = document.createElement('p');
                    error.id = input.id + '-error';
                    error.dataset.fieldError = '';
                    error.textContent = messages.join(' ');
                    input.setAttribute('aria-invalid', 'true');
                    input.setAttribute('aria-describedby', error.id);
                    input.after(error);
                });
                throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Could not save. Try again.');
            }
            summary.textContent = 'Saved. Refreshing...';
            window.location.reload();
        } catch (error) {
            summary.textContent = error.message;
            summary.focus();
            button.disabled = false;
        }
    });
});
</script>
