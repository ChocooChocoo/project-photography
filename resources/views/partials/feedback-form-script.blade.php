<script>
document.querySelectorAll('[data-feedback-form]').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const feedback = form.querySelector('[role="status"]');
        button.disabled = true;
        feedback.textContent = 'Saving...';
        form.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
        try {
            const response = await fetch(form.action, {method: 'POST', headers: {Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: new FormData(form)});
            const result = await response.json();
            if (!response.ok) {
                Object.keys(result.errors || {}).forEach(name => form.elements.namedItem(name)?.setAttribute('aria-invalid', 'true'));
                throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Could not save. Try again.');
            }
            feedback.textContent = 'Saved. Refreshing this page...';
            window.location.reload();
        } catch (error) {
            feedback.textContent = error.message;
            feedback.setAttribute('tabindex', '-1');
            feedback.focus();
            button.disabled = false;
        }
    });
});
</script>
