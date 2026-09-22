import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import {runInNewContext} from 'node:vm';

const template = readFileSync(new URL('../../resources/views/tokens/index.blade.php', import.meta.url), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];
const formMarkup = template.match(/<form[^>]*id="token-filter-form"[\s\S]*?<\/form>/)[0];

const createFilterForm = () => {
    const formListeners = new Map();
    const submissions = [];
    const timers = new Map();
    let nextTimerId = 0;
    const fields = [...formMarkup.matchAll(/<(input|select)\b([^>]+)>/g)].map(([, tag, attributes]) => {
        const listeners = new Map();
        const type = attributes.match(/\btype="([^"]+)"/)?.[1];

        return {
            name: attributes.match(/\bname="([^"]+)"/)[1],
            value: '',
            matches: () => tag === 'input' && ['search', 'text'].includes(type),
            addEventListener: (name, listener) => listeners.set(name, listener),
            dispatch: (name) => listeners.get(name)?.(),
        };
    });
    const form = {
        querySelectorAll: () => fields,
        addEventListener: (name, listener) => formListeners.set(name, listener),
        requestSubmit: () => {
            formListeners.get('submit')?.();
            submissions.push(Object.fromEntries(fields.map((field) => [field.name, field.value])));
        },
    };
    const document = {
        getElementById: (id) => id === 'token-filter-form' ? form : null,
        addEventListener: (name, listener) => {
            if (name === 'DOMContentLoaded') listener();
        },
    };

    runInNewContext(script, {
        document,
        setTimeout: (callback) => {
            timers.set(++nextTimerId, callback);

            return nextTimerId;
        },
        clearTimeout: (id) => timers.delete(id),
    });

    return {
        form,
        fields: Object.fromEntries(fields.map((field) => [field.name, field])),
        submissions,
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
    const {form, fields, submissions, flushTimers} = createFilterForm();
    fields.company_name.value = 'Harbour';
    fields.company_name.dispatch('input');

    form.requestSubmit();
    flushTimers();

    assert.equal(submissions.length, 1);
    assert.equal(submissions[0].company_name, 'Harbour');
});
