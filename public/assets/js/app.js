document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => document.querySelector('.sidebar')?.classList.toggle('show'));
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (! window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

const tokenModalElement = document.querySelector('#tokenDetailsModal');
const tokenModalContent = tokenModalElement?.querySelector('[data-token-modal-content]');
let tokenModalRequest;

const tokenModalLoading = () => `
    <div class="modal-header">
        <h2 class="modal-title" id="tokenDetailsModalLabel">Token details</h2>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close token details"></button>
    </div>
    <div class="modal-body text-center py-5">
        <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading token details</span></div>
        <p class="text-secondary mt-3 mb-0">Loading token dossier and attachments</p>
    </div>`;

const tokenModalError = () => `
    <div class="modal-header">
        <h2 class="modal-title" id="tokenDetailsModalLabel">Token details unavailable</h2>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close token details"></button>
    </div>
    <div class="modal-body py-5 text-center">
        <i class="bi bi-exclamation-triangle text-danger fs-2" aria-hidden="true"></i>
        <p class="mt-3 mb-0">The token details could not be loaded. Close this window and try again.</p>
    </div>`;

const openTokenModal = async (url) => {
    if (! tokenModalElement || ! tokenModalContent) {
        return;
    }

    tokenModalRequest?.abort();
    tokenModalRequest = new AbortController();
    tokenModalContent.innerHTML = tokenModalLoading();
    bootstrap.Modal.getOrCreateInstance(tokenModalElement).show();

    try {
        const response = await fetch(url, {
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            signal: tokenModalRequest.signal,
        });

        if (! response.ok) {
            throw new Error(`Token modal request failed with ${response.status}`);
        }

        tokenModalContent.innerHTML = await response.text();
    } catch (error) {
        if (error.name !== 'AbortError') {
            tokenModalContent.innerHTML = tokenModalError();
        }
    }
};

document.addEventListener('click', (event) => {
    const interactiveElement = event.target.closest('a, button, input, select, textarea, label');

    if (interactiveElement && ! interactiveElement.matches('[data-token-modal-url]')) {
        return;
    }

    const trigger = interactiveElement?.matches('[data-token-modal-url]')
        ? interactiveElement
        : event.target.closest('tr[data-token-modal-url]');

    if (! trigger) {
        return;
    }

    event.preventDefault();
    openTokenModal(trigger.dataset.tokenModalUrl);
});

document.addEventListener('keydown', (event) => {
    const row = event.target.closest('tr[data-token-modal-url]');

    if (! row || event.target !== row || ! ['Enter', ' '].includes(event.key)) {
        return;
    }

    event.preventDefault();
    openTokenModal(row.dataset.tokenModalUrl);
});

tokenModalElement?.addEventListener('hidden.bs.modal', () => {
    tokenModalRequest?.abort();
    tokenModalContent.innerHTML = tokenModalLoading();
});
