/*
 * Before-and-after photos.
 * - BeforeAfter.slider(originalUrl, editedUrl, label): markup for a comparison
 *   slider. A native range input moves the divider, so mouse, touch, and
 *   keyboard all work.
 * - Buttons with class "add-original" (data-image, data-url) upload the
 *   unedited original of a gallery photo.
 */
(function () {
    const style = document.createElement('style');
    style.textContent = `
        .before-after { position: relative; overflow: hidden; border-radius: .375rem; --pos: 50%; background: #111; }
        .before-after img { display: block; width: 100%; height: 100%; object-fit: cover; }
        .before-after .before-after-original { position: absolute; inset: 0; clip-path: inset(0 calc(100% - var(--pos)) 0 0); }
        .before-after .before-after-divider { position: absolute; top: 0; bottom: 0; left: var(--pos); width: 2px; margin-left: -1px; background: #fff; box-shadow: 0 0 0 1px rgba(0,0,0,.35); pointer-events: none; }
        .before-after .before-after-tag { position: absolute; top: .5rem; padding: .1rem .5rem; border-radius: 999px; font-size: .75rem; color: #fff; background: rgba(0,0,0,.6); pointer-events: none; }
        .before-after input[type=range] { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: ew-resize; }
        .before-after:focus-within { outline: 3px solid var(--ins-body-color, #212529); outline-offset: 2px; }
    `;
    document.head.appendChild(style);

    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    window.BeforeAfter = {
        slider(originalUrl, editedUrl, label, height = 200) {
            return `
                <div class="before-after" style="height:${Number(height)}px">
                    <img src="${escape(editedUrl)}" alt="${escape(label)}, edited" loading="lazy">
                    <img class="before-after-original" src="${escape(originalUrl)}" alt="${escape(label)}, original before editing" loading="lazy">
                    <span class="before-after-divider" aria-hidden="true"></span>
                    <span class="before-after-tag" style="left:.5rem" aria-hidden="true">Before</span>
                    <span class="before-after-tag" style="right:.5rem" aria-hidden="true">After</span>
                    <input type="range" min="0" max="100" value="50" aria-label="${escape(label)}: drag to compare the original with the edited photo">
                </div>`;
        },
    };

    document.addEventListener('input', (event) => {
        const range = event.target.closest('.before-after input[type=range]');
        if (range) range.parentElement.style.setProperty('--pos', range.value + '%');
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.add-original');
        if (!button) return;
        event.preventDefault();
        const picker = document.createElement('input');
        picker.type = 'file';
        picker.accept = 'image/jpeg,image/png';
        picker.addEventListener('change', async () => {
            if (!picker.files.length) return;
            const body = new FormData();
            body.append('image_path', button.dataset.image);
            body.append('original', picker.files[0]);
            button.disabled = true;
            try {
                const response = await fetch(button.dataset.url, {
                    method: 'POST',
                    headers: {Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    body,
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Could not upload the original.');
                }
                button.classList.replace('btn-soft-secondary', 'btn-soft-success');
                button.querySelector('.add-original-text').textContent = 'Original added';
                if (window.Swal) Swal.fire({icon: 'success', title: 'Original added', text: result.message, timer: 2500, showConfirmButton: false});
            } catch (error) {
                if (window.Swal) Swal.fire({icon: 'error', title: 'Original not added', text: error.message});
                else alert(error.message);
            } finally {
                button.disabled = false;
            }
        });
        picker.click();
    });
})();
