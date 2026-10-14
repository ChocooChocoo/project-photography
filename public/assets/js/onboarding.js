(() => {
    const tutorial = document.querySelector('[data-tutorial]');
    if (!tutorial) return;

    const steps = [...tutorial.querySelectorAll('[data-tutorial-step]')];
    const back = tutorial.querySelector('[data-tutorial-back]');
    const next = tutorial.querySelector('[data-tutorial-next]');
    const skip = tutorial.querySelector('[data-tutorial-skip]');
    const form = tutorial.querySelector('[data-tutorial-form]');
    const progress = tutorial.querySelector('#tutorial-progress');
    let current = 0;

    function showStep(focus) {
        steps.forEach((step, index) => { step.hidden = index !== current; });
        back.hidden = current === 0;
        next.hidden = false;
        next.textContent = current === steps.length - 1 ? 'Finish' : 'Next';
        progress.textContent = `Step ${current + 1} of ${steps.length}`;
        if (focus) steps[current].querySelector('h2').focus();
    }

    next.addEventListener('click', () => {
        if (current === steps.length - 1) {
            form.requestSubmit(skip);
        } else {
            current++;
            showStep(true);
        }
    });
    back.addEventListener('click', () => {
        if (current > 0) {
            current--;
            showStep(true);
        }
    });
    form.addEventListener('submit', () => {
        back.disabled = next.disabled = skip.disabled = true;
        progress.textContent = 'Opening your dashboard…';
    });
    window.addEventListener('pageshow', () => {
        back.disabled = next.disabled = skip.disabled = false;
        showStep(false);
    });
    showStep(false);
})();
