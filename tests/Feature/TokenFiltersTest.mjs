import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import {runInNewContext} from 'node:vm';

const template = readFileSync(new URL('../../resources/views/tokens/index.blade.php', import.meta.url), 'utf8');
const script = readFileSync(new URL('../../public/assets/js/token-filters.js', import.meta.url), 'utf8');
const formMarkup = template.match(/<form[^>]*id="token-filter-form"[\s\S]*?<\/form>/)[0];

const createFilterForm = ({flatpickrDates = false} = {}) => {
    const formListeners = new Map();
    const submissions = [];
    const requests = [];
    const navigations = [];
    const history = [];
    const timers = new Map();
    let nextTimerId = 0;
    const fields = [...formMarkup.matchAll(/<(input|select)\b([^>]+)>/g)].map(([, tag, attributes]) => {
        const listeners = new Map();
        const type = attributes.match(/\btype="([^"]+)"/)?.[1];

        const field = {
            name: attributes.match(/\bname="([^"]+)"/)[1],
            value: '',
            matches: () => tag === 'input' && ['search', 'text'].includes(type),
            addEventListener: (name, listener) => listeners.set(name, listener),
            dispatch: (name) => listeners.get(name)?.(),
        };

        if (flatpickrDates && type === 'date') {
            field._flatpickr = {
                altInput: {value: ''},
                clear: (triggerChange) => {
                    assert.equal(triggerChange, false);
                    field.value = '';
                    field._flatpickr.altInput.value = '';
                },
            };
        }

        return field;
    });
    const element = () => {
        const listeners = new Map();

        return {
            hidden: true,
            innerHTML: 'Original results',
            textContent: '',
            attributes: {},
            setAttribute(name, value) { this.attributes[name] = value; },
            addEventListener: (name, listener) => listeners.set(name, listener),
            dispatch: (name, event) => listeners.get(name)?.(event),
        };
    };
    const results = element();
    const matchingCount = element();
    const status = element();
    const errorMessage = element();
    const clearFilters = element();
    const form = {
        action: 'https://platform.test/tokens',
        querySelectorAll: () => fields,
        addEventListener: (name, listener) => formListeners.set(name, listener),
        requestSubmit: () => {
            const event = {defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }};
            formListeners.get('submit')?.(event);

            if (! event.defaultPrevented) navigations.push('submit');
        },
    };
    const elements = {
        'token-filter-form': form,
        'token-results': results,
        'token-matching-count': matchingCount,
        'token-filter-status': status,
        'token-filter-error': errorMessage,
        'token-filter-clear': clearFilters,
    };
    const document = {
        getElementById: (id) => elements[id] ?? null,
        addEventListener: (name, listener) => {
            if (name === 'DOMContentLoaded') listener();
        },
    };

    runInNewContext(script, {
        document,
        window: {history: {replaceState: (state, title, url) => history.push(url.toString())}},
        URL,
        URLSearchParams,
        AbortController,
        FormData: class {
            *[Symbol.iterator]() {
                yield* fields.map((field) => [field.name, field.value]);
            }
        },
        fetch: (url, options) => new Promise((resolve, reject) => {
            submissions.push(Object.fromEntries(url.searchParams));
            requests.push({
                url,
                options,
                complete: (data, status = 200) => resolve({ok: status === 200, status, json: async () => data}),
                fail: reject,
            });
        }),
        setTimeout: (callback) => {
            timers.set(++nextTimerId, callback);

            return nextTimerId;
        },
        clearTimeout: (id) => timers.delete(id),
    });

    return {
        form,
        document,
        fields: Object.fromEntries(fields.map((field) => [field.name, field])),
        submissions,
        requests,
        navigations,
        history,
        results,
        matchingCount,
        status,
        errorMessage,
        clearFilters,
        flushTimers: () => {
            const callbacks = [...timers.values()];
            timers.clear();
            callbacks.forEach((callback) => callback());
        },
    };
};

for (const name of ['q', 'company_name', 'agency_name', 'bhc_number']) {
    test(`${name} updates after typing pauses and when cleared`, () => {
        const {fields, submissions, flushTimers} = createFilterForm();
        const field = fields[name];

        field.value = 'A';
        field.dispatch('input');
        field.value = 'AB';
        field.dispatch('input');
        assert.equal(submissions.length, 0);
        flushTimers();
        assert.equal(submissions.length, 1);
        assert.equal(submissions[0][name], 'AB');

        field.value = '';
        field.dispatch('input');
        flushTimers();
        assert.equal(submissions.length, 2);
        assert.equal(submissions[1][name], '');
    });
}

for (const [name, value] of Object.entries({
    category_id: '1',
    created_by: '2',
    holder_id: '3',
    boesl_status: 'submitted',
    from_date: '2026-09-01',
    to_date: '2026-09-22',
    pre_selected: '0',
})) {
    test(`${name} updates immediately on change and when cleared`, () => {
        const {fields, submissions, flushTimers} = createFilterForm();
        fields.q.value = 'Pending search';
        fields.q.dispatch('input');
        fields[name].value = value;
        fields[name].dispatch('change');

        assert.equal(submissions.length, 1);
        assert.equal(submissions[0][name], value);
        assert.equal(submissions[0].q, 'Pending search');
        flushTimers();
        assert.equal(submissions.length, 1);

        fields[name].value = '';
        fields[name].dispatch('change');
        assert.equal(submissions.length, 2);
        assert.equal(submissions[1][name], '');
    });
}

test('manual filtering cancels a pending automatic submission', () => {
    const {form, fields, submissions, flushTimers, navigations} = createFilterForm();
    fields.company_name.value = 'Harbour';
    fields.company_name.dispatch('input');

    form.requestSubmit();
    flushTimers();

    assert.equal(submissions.length, 1);
    assert.equal(submissions[0].company_name, 'Harbour');
    assert.deepEqual(navigations, []);
});

const settleRequests = () => new Promise(setImmediate);
const clickEvent = (link = null, overrides = {}) => ({
    button: 0,
    defaultPrevented: false,
    target: {closest: () => link},
    preventDefault() { this.defaultPrevented = true; },
    ...overrides,
});

test('filtering replaces results and counts while preserving the focused input and caret', async () => {
    const state = createFilterForm();
    state.fields.q.value = 'Harbour';
    state.fields.q.selectionStart = 3;
    state.document.activeElement = state.fields.q;
    state.form.requestSubmit();

    assert.equal(state.results.attributes['aria-busy'], 'true');
    assert.equal(state.status.hidden, false);
    assert.equal(state.requests[0].options.headers.Accept, 'application/json');
    assert.equal(state.requests[0].url.searchParams.has('page'), false);
    state.requests[0].complete({html: '<table>Filtered tokens</table>', matching_count: '3'});
    await settleRequests();

    assert.equal(state.results.innerHTML, '<table>Filtered tokens</table>');
    assert.equal(state.matchingCount.textContent, '3 matching tokens');
    assert.equal(state.document.activeElement, state.fields.q);
    assert.equal(state.fields.q.value, 'Harbour');
    assert.equal(state.fields.q.selectionStart, 3);
    assert.equal(state.results.attributes['aria-busy'], 'false');
    assert.equal(state.status.hidden, true);
    assert.equal(new URL(state.history[0]).searchParams.get('q'), 'Harbour');
    assert.deepEqual(state.navigations, []);
});

test('an older response cannot overwrite newer results', async () => {
    const state = createFilterForm();
    state.fields.q.value = 'Old';
    state.form.requestSubmit();
    state.fields.q.value = 'New';
    state.form.requestSubmit();

    assert.equal(state.requests[0].options.signal.aborted, true);
    state.requests[1].complete({html: 'New results', matching_count: '2'});
    await settleRequests();
    state.requests[0].complete({html: 'Old results', matching_count: '99'});
    await settleRequests();

    assert.equal(state.results.innerHTML, 'New results');
    assert.equal(state.matchingCount.textContent, '2 matching tokens');
    assert.equal(state.history.length, 1);
    assert.equal(state.errorMessage.hidden, true);
});

test('typing cancels an in-flight request before the next typing pause', async () => {
    const state = createFilterForm();
    state.form.requestSubmit();
    state.fields.company_name.value = 'New company';
    state.fields.company_name.dispatch('input');
    state.requests[0].complete({html: 'Stale results', matching_count: '1'});
    await settleRequests();

    assert.equal(state.requests[0].options.signal.aborted, true);
    assert.equal(state.results.innerHTML, 'Original results');
    assert.equal(state.requests.length, 1);
});

test('pagination updates results without navigation and retains the current filters', async () => {
    const state = createFilterForm();
    state.fields.q.value = 'Harbour';
    const event = clickEvent({href: 'https://platform.test/tokens?q=Harbour&page=2'});
    state.results.dispatch('click', event);
    state.requests[0].complete({html: 'Second page', matching_count: '20'});
    await settleRequests();

    assert.equal(event.defaultPrevented, true);
    assert.equal(state.submissions[0].page, '2');
    assert.equal(state.submissions[0].q, 'Harbour');
    assert.equal(state.results.innerHTML, 'Second page');
    assert.equal(state.fields.q.value, 'Harbour');
    assert.equal(new URL(state.history[0]).searchParams.get('page'), '2');
});

test('modified pagination clicks and token action clicks keep their normal behavior', () => {
    const state = createFilterForm();
    const modified = clickEvent({href: 'https://platform.test/tokens?page=2'}, {ctrlKey: true});
    const action = clickEvent();

    state.results.dispatch('click', modified);
    state.results.dispatch('click', action);

    assert.equal(modified.defaultPrevented, false);
    assert.equal(action.defaultPrevented, false);
    assert.equal(state.requests.length, 0);
});

test('pagination during a typing pause applies the new filters from the first page', () => {
    const state = createFilterForm();
    state.fields.q.value = 'New search';
    state.fields.q.dispatch('input');
    const event = clickEvent({href: 'https://platform.test/tokens?q=Old+search&page=2'});

    state.results.dispatch('click', event);
    state.flushTimers();

    assert.equal(event.defaultPrevented, true);
    assert.equal(state.requests.length, 1);
    assert.equal(state.submissions[0].q, 'New search');
    assert.equal(state.requests[0].url.searchParams.has('page'), false);
});

test('clear filters empties inputs and date pickers and refreshes the list in place', async () => {
    const state = createFilterForm({flatpickrDates: true});
    state.fields.q.value = 'Harbour';
    state.fields.created_by.value = '2';
    state.fields.from_date.value = '2026-09-01';
    state.fields.from_date._flatpickr.altInput.value = '01 Sep 2026';
    state.fields.q.dispatch('input');
    const event = clickEvent();

    state.clearFilters.dispatch('click', event);
    state.flushTimers();
    state.requests[0].complete({html: 'All tokens', matching_count: '30'});
    await settleRequests();

    assert.equal(event.defaultPrevented, true);
    assert.ok(Object.values(state.fields).every((field) => field.value === ''));
    assert.equal(state.fields.from_date._flatpickr.altInput.value, '');
    assert.equal(state.requests.length, 1);
    assert.equal(state.requests[0].url.search, '');
    assert.equal(state.results.innerHTML, 'All tokens');
});

test('validation errors preserve inputs and existing results without redirecting', async () => {
    const state = createFilterForm();
    state.fields.from_date.value = '2026-09-04';
    state.fields.to_date.value = '2026-09-02';
    state.form.requestSubmit();
    state.requests[0].complete({errors: {to_date: ['The end date must be on or after the start date.']}}, 422);
    await settleRequests();

    assert.equal(state.errorMessage.hidden, false);
    assert.equal(state.errorMessage.textContent, 'The end date must be on or after the start date.');
    assert.equal(state.fields.to_date.value, '2026-09-02');
    assert.equal(state.results.innerHTML, 'Original results');
    assert.equal(state.history.length, 0);
    assert.deepEqual(state.navigations, []);
});

test('a failed request preserves results and allows retrying the current filters', async () => {
    const state = createFilterForm();
    state.fields.q.value = 'Harbour';
    state.form.requestSubmit();
    state.requests[0].fail(new Error('Network unavailable'));
    await settleRequests();

    assert.equal(state.results.innerHTML, 'Original results');
    assert.equal(state.errorMessage.hidden, false);
    assert.equal(state.errorMessage.textContent, 'Unable to update tokens. Please try again.');
    assert.equal(state.status.hidden, true);
    assert.equal(state.results.attributes['aria-busy'], 'false');

    state.form.requestSubmit();
    state.requests[1].complete({html: 'Recovered results', matching_count: '1'});
    await settleRequests();

    assert.equal(state.errorMessage.hidden, true);
    assert.equal(state.results.innerHTML, 'Recovered results');
    assert.equal(state.submissions[1].q, 'Harbour');
});
