/*
 * Client photo revisions, provider side.
 * - GalleryRevision.badge(): "Revision requested" badge markup.
 * - GalleryRevision.button(image, url): "Replace photo" button markup.
 * - Clicking a ".replace-photo" button (data-image, data-url) picks a file and
 *   posts it; the card's image is swapped in place and the flag is removed.
 */
(function () {
    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    window.GalleryRevision = {
        badge() {
            return '<span class="badge badge-soft-warning position-absolute bottom-0 start-0 m-2 revision-flag"><i class="ti ti-photo-edit me-1" aria-hidden="true"></i>Revision requested</span>';
        },
        button(image, url) {
            return `<button type="button" class="btn btn-sm btn-soft-warning replace-photo w-100 mt-2" data-image="${escape(image)}" data-url="${escape(url)}" title="Upload the revised version of this photo">
                        <i class="ti ti-replace me-1" aria-hidden="true"></i>Replace photo
                    </button>`;
        },
        note(text) {
            return text
                ? `<div class="alert alert-warning py-2 mb-3" role="note"><i class="ti ti-message-2 me-1" aria-hidden="true"></i><strong>Client revision note:</strong> ${escape(text)}</div>`
                : '';
        },
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.replace-photo');
        if (!button || button.disabled) return;
        event.preventDefault();
        const picker = document.createElement('input');
        picker.type = 'file';
        picker.accept = 'image/jpeg,image/png';
        picker.addEventListener('change', async () => {
            if (!picker.files.length) return;
            const body = new FormData();
            body.append('image_path', button.dataset.image);
            body.append('image', picker.files[0]);
            button.disabled = true;
            try {
                const response = await fetch(button.dataset.url, {
                    method: 'POST',
                    headers: {Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    body,
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Could not replace the photo.');
                }
                const card = button.closest('.card');
                const oldPath = button.dataset.image;
                const newPath = result.image_path;
                const img = card?.querySelector('img');
                if (img && newPath) img.src = img.src.replace(oldPath, newPath);
                // Keep the other per-photo buttons (original, delete) pointing at the new file.
                card?.querySelectorAll('[data-image]').forEach((el) => {
                    if (el.dataset.image === oldPath && newPath) el.dataset.image = newPath;
                });
                card?.querySelector('.revision-flag')?.remove();
                button.remove();
                if (window.Swal) Swal.fire({icon: 'success', title: 'Photo replaced', text: result.message, timer: 2500, showConfirmButton: false});
            } catch (error) {
                button.disabled = false;
                if (window.Swal) Swal.fire({icon: 'error', title: 'Photo not replaced', text: error.message});
                else alert(error.message);
            }
        });
        picker.click();
    });
})();
