document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('token-filter-form');
    const results = document.getElementById('token-results');
    const matchingCount = document.getElementById('token-matching-count');
    const status = document.getElementById('token-filter-status');
    const errorMessage = document.getElementById('token-filter-error');
    const clearFilters = document.getElementById('token-filter-clear');

    if (! form || ! results) {
        return;
    }

    const fields = form.querySelectorAll('input[name], select[name]');
    let submitTimer;
    let activeRequest;

    const cancelPendingUpdate = () => {
        clearTimeout(submitTimer);
        activeRequest?.abort();
        activeRequest = null;
        results.setAttribute('aria-busy', 'false');
        status.hidden = true;
    };

    const updateResults = async (url) => {
        cancelPendingUpdate();
        const request = new AbortController();
        activeRequest = request;
        results.setAttribute('aria-busy', 'true');
        status.hidden = false;
        errorMessage.hidden = true;
        let failureMessage = 'Unable to update tokens. Please try again.';

        try {
            const response = await fetch(url, {
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                signal: request.signal,
            });

            if (response.status === 422) {
                const validation = await response.json();
                failureMessage = Object.values(validation.errors).flat().join(' ');
                throw new Error(failureMessage);
            }

            if (! response.ok) {
                throw new Error('Unable to update tokens. Please try again.');
            }

            const data = await response.json();

            if (request.signal.aborted) {
                return;
            }

            results.innerHTML = data.html;
            matchingCount.textContent = `${data.matching_count} matching tokens`;
            window.history.replaceState(null, '', url);
        } catch (error) {
            if (! request.signal.aborted) {
                errorMessage.textContent = error instanceof SyntaxError
                    ? 'Unable to update tokens. Please refresh the page and try again.'
                    : failureMessage;
                errorMessage.hidden = false;
            }
        } finally {
            if (activeRequest === request) {
                activeRequest = null;
                results.setAttribute('aria-busy', 'false');
                status.hidden = true;
            }
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();
        updateResults(url);
    });

    fields.forEach((field) => {
        const isTextInput = field.matches('input[type="search"], input[type="text"]');

        field.addEventListener(isTextInput ? 'input' : 'change', () => {
            cancelPendingUpdate();

            if (isTextInput) {
                submitTimer = setTimeout(() => form.requestSubmit(), 350);
            } else {
                form.requestSubmit();
            }
        });
    });

    const isPlainClick = (event) => ! event.defaultPrevented && event.button === 0
        && ! event.ctrlKey && ! event.metaKey && ! event.shiftKey && ! event.altKey;

    clearFilters.addEventListener('click', (event) => {
        if (! isPlainClick(event)) {
            return;
        }

        event.preventDefault();
        fields.forEach((field) => {
            if (field._flatpickr) {
                field._flatpickr.clear(false);
            } else {
                field.value = '';
            }
        });
        updateResults(new URL(form.action));
    });

    results.addEventListener('click', (event) => {
        const link = event.target.closest('.list-pagination a[href]');

        if (! link || ! isPlainClick(event)) {
            return;
        }

        event.preventDefault();
        const url = new URL(link.href);

        if (Array.from(fields).some((field) => (url.searchParams.get(field.name) ?? '') !== field.value)) {
            form.requestSubmit();
            return;
        }

        updateResults(url);
    });
});
