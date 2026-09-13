import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../../public/assets/js/app.js', import.meta.url), 'utf8');

const clickBhcLink = (urls, blockedIndexes = []) => {
    const listeners = new Map();
    const fallback = {hidden: true};
    const openedTabs = [];
    const openCalls = [];
    const link = {
        dataset: {bhcTokenUrls: JSON.stringify(urls)},
        matches: () => false,
    };
    const event = {
        target: {closest: () => link},
        button: 0,
        defaultPrevented: false,
        preventDefault() { this.defaultPrevented = true; },
    };
    const document = {
        body: {classList: {contains: () => false}},
        querySelector: (selector) => selector === '[data-bhc-token-fallback]' ? fallback : null,
        querySelectorAll: () => [],
        addEventListener: (name, listener) => {
            listeners.set(name, [...(listeners.get(name) ?? []), listener]);
        },
    };
    const window = {
        matchMedia: () => ({matches: true, addEventListener() {}}),
        open: (...args) => {
            const index = openCalls.length;
            openCalls.push(args);

            if (blockedIndexes.includes(index)) {
                return null;
            }

            const tab = {opener: window, location: {href: 'about:blank'}};
            openedTabs.push(tab);

            return tab;
        },
    };

    runInNewContext(source, {document, window});
    listeners.get('click').forEach((listener) => listener(event));

    return {event, fallback, openedTabs, openCalls};
};

test('one matching token keeps the normal new-tab anchor behavior', () => {
    const result = clickBhcLink(['/tokens/1']);

    assert.equal(result.event.defaultPrevented, false);
    assert.equal(result.openCalls.length, 0);
    assert.equal(result.fallback.hidden, true);
});

test('multiple matching tokens each open in a separate tab without an opener', () => {
    const urls = ['/tokens/3', '/tokens/2', '/tokens/1'];
    const result = clickBhcLink(urls);

    assert.equal(result.event.defaultPrevented, true);
    assert.deepEqual(result.openCalls, [['', '_blank'], ['', '_blank'], ['', '_blank']]);
    assert.deepEqual(result.openedTabs.map((tab) => tab.location.href), urls);
    assert.ok(result.openedTabs.every((tab) => tab.opener === null));
    assert.equal(result.fallback.hidden, true);
});

test('blocked tabs reveal individual links while other tokens still open', () => {
    const result = clickBhcLink(['/tokens/3', '/tokens/2', '/tokens/1'], [1]);

    assert.deepEqual(result.openedTabs.map((tab) => tab.location.href), ['/tokens/3', '/tokens/1']);
    assert.equal(result.fallback.hidden, false);
});

test('blocking every tab still reveals the individual token links', () => {
    const result = clickBhcLink(['/tokens/2', '/tokens/1'], [0, 1]);

    assert.equal(result.openedTabs.length, 0);
    assert.equal(result.fallback.hidden, false);
});
