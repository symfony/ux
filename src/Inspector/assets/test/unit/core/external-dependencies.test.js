import { afterEach, describe, expect, it, vi } from 'vitest';
import { ComponentDetector } from '../../../src/core/component-detector';
import { StateManager } from '../../../src/core/state-manager';
import { PluginRegistry } from '../../../src/core/plugin-registry';
import { StimulusPlugin } from '../../../src/stimulus/plugin';
import { TurboPlugin } from '../../../src/turbo/plugin';

let detector;
afterEach(() => {
    detector?.destroy();
    document.body.replaceChildren();
    vi.restoreAllMocks();
});
function detect(html, plugins = [new StimulusPlugin(), new TurboPlugin()]) {
    document.body.innerHTML = html;
    const state = new StateManager();
    detector = new ComponentDetector(new PluginRegistry(plugins), state, document.createElement('ux-inspector'));
    detector.scan();
    detector.observe();
    return state;
}
const outlets = (state, owner) => state.get(owner).get('stimulus').data.outlets.owner[0].elements;

describe('External DOM dependencies', () => {
    it('removes an outlet match when its target no longer matches the selector', async () => {
        const state = detect(
            '<div id="owner" data-controller="owner" data-owner-item-outlet=".item"></div><div id="item" class="item"></div>'
        );
        const owner = document.getElementById('owner');
        const item = document.getElementById('item');
        expect(outlets(state, owner)).toEqual([item]);
        item.className = 'other';
        await vi.waitFor(() => expect(outlets(state, owner)).toEqual([]));
    });

    it('resolves dependencies on arbitrary attributes of a sibling', async () => {
        const state = detect(
            '<div id="owner" data-controller="owner" data-owner-item-outlet="[aria-expanded=true] + .item"></div><div id="gate" aria-expanded="false"></div><div id="item" class="item"></div>'
        );
        const owner = document.getElementById('owner');
        const item = document.getElementById('item');
        document.getElementById('gate').setAttribute('aria-expanded', 'true');
        await vi.waitFor(() => expect(outlets(state, owner)).toEqual([item]));
        document.getElementById('gate').setAttribute('aria-expanded', 'false');
        await vi.waitFor(() => expect(outlets(state, owner)).toEqual([]));
    });

    it('refreshes both frames when nested external links and forms are added and retargeted', async () => {
        const state = detect('<turbo-frame id="first"></turbo-frame><turbo-frame id="second"></turbo-frame>');
        const first = document.getElementById('first');
        const second = document.getElementById('second');
        const wrapper = document.createElement('div');
        wrapper.innerHTML =
            '<a href="/one" data-turbo-frame="first">Link</a><form action="/save" data-turbo-frame="first"></form>';
        document.body.append(wrapper);
        const link = wrapper.firstElementChild;
        const form = wrapper.lastElementChild;
        const data = (frame) => state.get(frame).get('turbo').data;
        await vi.waitFor(() => expect(data(first).linksToFrame.map((link) => link.element)).toEqual([link]));
        expect(data(first).formsInFrame[0].element).toBe(form);
        link.dataset.turboFrame = 'second';
        form.dataset.turboFrame = 'second';
        await vi.waitFor(() => {
            expect(data(first).linksToFrame).toEqual([]);
            expect(data(first).formsInFrame).toEqual([]);
            expect(data(second).linksToFrame[0].element).toBe(link);
            expect(data(second).formsInFrame[0].element).toBe(form);
        });
    });

    it('queries a shared selector once per mutation batch and does not reparse unchanged owners', async () => {
        const plugin = new StimulusPlugin();
        const state = detect(
            '<div id="target"></div>' +
                Array.from(
                    { length: 20 },
                    (_, i) => `<div id="owner-${i}" data-controller="owner" data-owner-item-outlet=".item"></div>`
                ).join(''),
            [plugin]
        );
        const target = document.getElementById('target');
        const query = vi.spyOn(document, 'querySelectorAll');
        const parse = vi.spyOn(plugin, 'parse');
        target.className = 'item';
        await vi.waitFor(() => expect(outlets(state, document.getElementById('owner-19'))).toEqual([target]));
        expect(query.mock.calls.filter(([selector]) => selector === '.item')).toHaveLength(1);
        parse.mockClear();
        query.mockClear();
        const updated = vi.fn();
        state.addEventListener('page-updated', updated);
        for (let i = 0; i < 50; i++) target.setAttribute('aria-label', String(i));
        await vi.waitFor(() => expect(query.mock.calls.filter(([selector]) => selector === '.item')).toHaveLength(1));
        expect(updated).not.toHaveBeenCalled();
        expect(query.mock.calls.filter(([selector]) => selector === '.item')).toHaveLength(1);
        expect(parse).not.toHaveBeenCalled();
    });
});

it('keeps the runtime-aware outlet filter when rechecking a selector', async () => {
    const controller = { constructor: { outlets: ['item'] } };
    const plugin = new StimulusPlugin({
        getControllerForElementAndIdentifier: (_element, name) => (name === 'owner' ? controller : null),
    });
    const state = detect(
        '<div id="owner" data-controller="owner" data-owner-item-outlet=".item"></div><div id="item" class="item"></div>',
        [plugin]
    );
    const owner = document.getElementById('owner');
    const item = document.getElementById('item');
    expect(outlets(state, owner)).toEqual([]);
    item.dataset.controller = 'item';
    await vi.waitFor(() => expect(outlets(state, owner)).toEqual([item]));
    item.removeAttribute('data-controller');
    await vi.waitFor(() => expect(outlets(state, owner)).toEqual([]));
});

it('rechecks external selectors for character-data mutations', async () => {
    const state = detect(
        '<div id="owner" data-controller="owner" data-owner-item-outlet=".item:empty"></div><div id="item" class="item">occupied</div>'
    );
    const owner = document.getElementById('owner');
    const item = document.getElementById('item');
    const query = vi.spyOn(document, 'querySelectorAll');
    item.firstChild.data = 'changed';
    await vi.waitFor(() => expect(query.mock.calls.filter(([selector]) => selector === '.item:empty')).toHaveLength(1));
    expect(outlets(state, owner)).toEqual([]);
});
