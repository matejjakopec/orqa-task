import 'bootstrap/js/dist/collapse';

document.documentElement.classList.remove('no-js');

const summary = document.querySelector('[data-validation-summary]');
if (summary) {
    requestAnimationFrame(() => summary.focus());
}

document.querySelectorAll('[data-error-field]').forEach((link) => {
    const field = link.closest('form')?.elements.namedItem(link.dataset.errorField);
    if (field?.id) {
        link.href = `#${field.id}`;
    }
});

document.querySelectorAll('form[data-prevent-repeat]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button[type="submit"]:not(:disabled)').forEach((button) => {
            button.dataset.pendingSubmit = 'true';
            button.disabled = true;
        });
    });
});

document.querySelectorAll('input[type="file"][data-max-bytes]').forEach((input) => {
    input.addEventListener('change', () => {
        const tooLarge = Array.from(input.files ?? []).some((file) => file.size > Number(input.dataset.maxBytes));
        input.setCustomValidity(tooLarge ? input.dataset.sizeError : '');
        if (tooLarge) input.reportValidity();
    });
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-prevent-repeat]').forEach((form) => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[data-pending-submit]').forEach((button) => {
            button.disabled = false;
            delete button.dataset.pendingSubmit;
        });
    });
});
