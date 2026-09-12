import { renderLive } from '../../../src/live-component/render';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { LiveComponentPlugin } from '../../../src/live-component/plugin';

describe('LiveComponentPlugin', () => {
    let plugin;

    beforeEach(() => {
        plugin = new LiveComponentPlugin();
        document.body.innerHTML = '';
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    describe('canHandle()', () => {
        it('returns true for data-controller="live"', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            expect(plugin.canHandle(el)).toBe(true);
        });

        it('returns true for data-controller="live other-controller"', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live other-controller');
            expect(plugin.canHandle(el)).toBe(true);
        });

        it('returns false for data-controller="not-live"', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'not-live');
            expect(plugin.canHandle(el)).toBe(false);
        });
    });

    describe('parse()', () => {
        it('extracts component name from data-live-name-value', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.setAttribute('data-live-name-value', 'MyComponent');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.name).toBe('MyComponent');
            expect(result.data.runtime.status).toBe('detected');
        });

        it('reports connected only when a Live runtime component is present', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.__component = { on: vi.fn(), off: vi.fn() };
            document.body.appendChild(el);

            plugin.observe(el);
            expect(plugin.parse(el).data.runtime.status).toBe('connected');
        });

        it('extracts a safe URL from data-live-url-value', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.setAttribute('data-live-url-value', '/live/my-component?token=secret&view=full');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.url).toBe('/live/my-component?token=%5Bredacted%5D&view=full');
        });

        it('parses JSON props from data-live-props-value', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.setAttribute('data-live-props-value', '{"count":5,"label":"test"}');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.props).toEqual({ count: 5, label: 'test' });
        });

        it('handles malformed JSON in props gracefully', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.setAttribute('data-live-props-value', '{invalid json}');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.props).toEqual({ _raw: '{invalid json}' });
        });

        it('parses listeners from data-live-listeners-value', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.setAttribute('data-live-listeners-value', '[{"event":"postCreated","action":"refresh"}]');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.listeners).toHaveLength(1);
            expect(result.data.listeners[0].event).toBe('postCreated');
            expect(result.data.listeners[0].action).toBe('refresh');
        });

        it('detects polling from data-poll attribute', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.setAttribute('data-poll', 'delay(5000)|$render');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.polling).not.toBeNull();
            expect(result.data.polling.duration).toBe('5000ms');
        });

        it('extracts co-controllers (other controllers besides "live")', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live chart tooltip');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.otherControllers).toEqual(['chart', 'tooltip']);
        });

        it('resolves model bindings within scope', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            const input = document.createElement('input');
            input.setAttribute('data-model', 'username');
            el.appendChild(input);
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.models).toHaveLength(1);
            expect(result.data.models[0].name).toBe('username');
            expect(result.data.models[0].element).toBe(input);
        });

        it('parses model modifiers (e.g. on(change))', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            const input = document.createElement('input');
            input.setAttribute('data-model', 'on(change)|email');
            el.appendChild(input);
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.models[0].name).toBe('email');
            expect(result.data.models[0].modifiers).toEqual(['on(change)']);
        });

        it('excludes models in nested live components', () => {
            const parent = document.createElement('div');
            parent.setAttribute('data-controller', 'live');
            const child = document.createElement('div');
            child.setAttribute('data-controller', 'live');
            const input = document.createElement('input');
            input.setAttribute('data-model', 'nested-field');
            child.appendChild(input);
            parent.appendChild(child);
            document.body.appendChild(parent);

            const result = plugin.parse(parent);
            expect(result.data.models).toHaveLength(0);
        });

        it('resolves live actions within scope', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            const btn = document.createElement('button');
            btn.setAttribute('data-action', 'click->live#save');
            el.appendChild(btn);
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.actions).toHaveLength(1);
            expect(result.data.actions[0].event).toBe('click');
            expect(result.data.actions[0].method).toBe('save');
            expect(result.data.actions[0].element).toBe(btn);
        });

        it('resolves loading directives', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            const spinner = document.createElement('div');
            spinner.setAttribute('data-loading', 'show');
            el.appendChild(spinner);
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.loading).toHaveLength(1);
            expect(result.data.loading[0].action).toBe('show');
            expect(result.data.loading[0].element).toBe(spinner);
        });

        it('finds child live components', () => {
            const parent = document.createElement('div');
            parent.setAttribute('data-controller', 'live');
            parent.setAttribute('data-live-name-value', 'Parent');
            const child = document.createElement('div');
            child.setAttribute('data-controller', 'live');
            child.setAttribute('data-live-name-value', 'Child');
            parent.appendChild(child);
            document.body.appendChild(parent);

            const result = plugin.parse(parent);
            expect(result.data.children).toHaveLength(1);
            expect(result.data.children[0].name).toBe('Child');
            expect(result.data.children[0].element).toBe(child);
        });

        it('finds parent live components', () => {
            const parent = document.createElement('div');
            parent.setAttribute('data-controller', 'live');
            parent.setAttribute('data-live-name-value', 'Parent');
            const child = document.createElement('div');
            child.setAttribute('data-controller', 'live');
            child.setAttribute('data-live-name-value', 'Child');
            parent.appendChild(child);
            document.body.appendChild(parent);

            const result = plugin.parse(child);
            expect(result.data.parents).toHaveLength(1);
            expect(result.data.parents[0].name).toBe('Parent');
            expect(result.data.parents[0].element).toBe(parent);
        });
        it('extracts models with multiple modifiers', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            const input = document.createElement('input');
            input.setAttribute('data-model', 'norender|on(change)|email');
            el.appendChild(input);
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.models[0].name).toBe('email');
            expect(result.data.models[0].modifiers).toEqual(['norender', 'on(change)']);
        });

        it('resolves actions with data-action="live#method"', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            const btn = document.createElement('button');
            btn.setAttribute('data-action', 'live#doSomething');
            el.appendChild(btn);
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.actions).toHaveLength(1);
            expect(result.data.actions[0].method).toBe('doSomething');
        });

        it('resolves the modern live#action parameter', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            const btn = document.createElement('button');
            btn.setAttribute('data-action', 'live#action');
            btn.setAttribute('data-live-action-param', 'save');
            el.appendChild(btn);
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.actions[0].method).toBe('save');
        });

        it('detects data-poll without delay', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.setAttribute('data-poll', '');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.polling).not.toBeNull();
            expect(result.data.polling.duration).toBe('2000ms');
        });

        it('excludes actions in nested live components', () => {
            const parent = document.createElement('div');
            parent.setAttribute('data-controller', 'live');
            const child = document.createElement('div');
            child.setAttribute('data-controller', 'live');
            const btn = document.createElement('button');
            btn.setAttribute('data-action', 'click->live#nestedAction');
            child.appendChild(btn);
            parent.appendChild(child);
            document.body.appendChild(parent);

            const result = plugin.parse(parent);
            expect(result.data.actions).toHaveLength(0);
        });

        it('excludes loading directives in nested live components', () => {
            const parent = document.createElement('div');
            parent.setAttribute('data-controller', 'live');
            const child = document.createElement('div');
            child.setAttribute('data-controller', 'live');
            const spinner = document.createElement('div');
            spinner.setAttribute('data-loading', 'show');
            child.appendChild(spinner);
            parent.appendChild(child);
            document.body.appendChild(parent);

            const result = plugin.parse(parent);
            expect(result.data.loading).toHaveLength(0);
        });

        it('parses empty props as empty object', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.props).toEqual({});
        });

        it('reads safe model values and redacts sensitive models', () => {
            const el = document.createElement('div');
            el.dataset.controller = 'live';
            const title = document.createElement('input');
            title.dataset.model = 'on(change)|title';
            title.value = 'Hello';
            const password = document.createElement('input');
            password.type = 'password';
            password.dataset.model = 'password';
            password.value = 'private';
            el.append(title, password);
            document.body.appendChild(el);

            const models = plugin.parse(el).data.models;
            expect(models[0]).toMatchObject({ name: 'title', modifiers: ['on(change)'], value: 'Hello' });
            expect(models[1]).toMatchObject({ name: 'password', value: '[redacted]' });
        });

        it('extracts bounded and redacted Live action arguments', () => {
            const el = document.createElement('div');
            el.dataset.controller = 'live';
            const button = document.createElement('button');
            button.dataset.action = 'click->live#action';
            button.setAttribute('data-live-action-param', 'save');
            button.setAttribute('data-live-id-param', '42');
            button.setAttribute('data-live-csrf-param', 'private');
            el.appendChild(button);
            document.body.appendChild(el);

            expect(plugin.parse(el).data.actions[0]).toMatchObject({
                event: 'click',
                method: 'save',
                args: { id: 42, csrf: '[redacted]' },
            });
        });

        it('parses empty listeners as empty array', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.listeners).toEqual([]);
        });

        it('returns null polling when no data-poll attribute', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.polling).toBeNull();
        });
    });

    describe('canHandle()', () => {
        it('returns false for non-live controllers', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'stimulus chart');
            expect(plugin.canHandle(el)).toBe(false);
        });

        it('returns false for elements without data-controller', () => {
            const el = document.createElement('div');
            expect(plugin.canHandle(el)).toBe(false);
        });
    });

    describe('getDisplayName()', () => {
        it('returns the component name', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.setAttribute('data-live-name-value', 'SearchForm');
            expect(plugin.getDisplayName(el)).toBe('SearchForm');
        });

        it('returns "LiveComponent" when no name set', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            expect(plugin.getDisplayName(el)).toBe('LiveComponent');
        });
    });

    describe('renderCard()', () => {
        it('renders the expected data fields as a DocumentFragment', () => {
            const data = {
                data: {
                    name: 'test',
                    runtime: { status: 'detected' },
                    url: '/url',
                    props: { p: 1 },
                    propsFromParent: {},
                    models: [],
                    actions: [],
                    listeners: [],
                    loading: [],
                    children: [],
                    parents: [],
                    otherControllers: [],
                },
            };
            const frag = renderLive(data);
            expect(frag).toBeTruthy();
        });

        it('organizes LiveComponent contracts into visible non-empty groups', () => {
            const field = document.createElement('input');
            const button = document.createElement('button');
            const loading = document.createElement('span');
            const data = {
                data: {
                    name: 'Search',
                    url: '/_components/Search',
                    fingerprint: '1234567890abcdefghijkl',
                    props: { query: 'ux' },
                    propsFromParent: { page: 2 },
                    models: [{ name: 'query', modifiers: ['debounce(300)'], value: 'ux', element: field }],
                    actions: [{ event: 'click', method: 'save', args: { id: 4 }, element: button }],
                    listeners: [{ event: 'saved', action: 'refresh' }],
                    loading: [{ action: 'show', element: loading }],
                    polling: { duration: '5000ms' },
                    runtime: { status: 'idle', duration: 42, httpStatus: 200, error: null },
                    children: [],
                    parents: [],
                    otherControllers: [],
                },
            };

            const container = document.createElement('div');
            container.append(renderLive(data));

            expect(
                [...container.querySelectorAll('.groups > .group > .title > .name')].map((title) => title.textContent)
            ).toEqual(['Props', 'Models', 'Configuration', 'Actions', 'Listeners']);
            expect(container.querySelectorAll('dl.key-values')).toHaveLength(5);
            expect(container.querySelector('[data-group="livecomponent-props"]').textContent).not.toContain('checksum');
            expect(container.querySelector('[data-group="livecomponent-props"]').textContent).not.toContain(
                'query.value'
            );
            expect(container.querySelector('[data-field-key="query"]').textContent).toContain('ux');
            expect(container.querySelector('[data-field-key="element:query model"]').textContent).toContain(
                'debounce(300)'
            );
            expect(container.querySelector('[data-field-key="poll interval"]').textContent).toContain('5000ms');
            expect(container.querySelector('[data-group="livecomponent-actions"]').open).toBe(true);
            expect(container.querySelector('[data-group="livecomponent-actions"] > .title > .badge')).toBeNull();
            expect(
                container.querySelector('[data-group="livecomponent-listeners"] [data-field-key="saved"]').textContent
            ).toContain('refresh()');
            expect(container.querySelector('[data-field-key="status"]')).toBeNull();
            expect(container.querySelector('[data-group="livecomponent-request"]')).toBeNull();
            expect(container.querySelector('[data-tab]')).toBeNull();
            expect(container.textContent).not.toContain('Wiring');
        });

        it('shows only actionable runtime states', () => {
            const data = {
                data: {
                    name: 'Search',
                    url: '',
                    fingerprint: '',
                    props: {},
                    propsFromParent: {},
                    models: [],
                    actions: [],
                    listeners: [],
                    loading: [],
                    polling: null,
                    runtime: { status: 'updating', duration: null, httpStatus: null, error: null },
                    children: [],
                    parents: [],
                    otherControllers: [],
                },
            };

            const container = document.createElement('div');
            container.append(renderLive(data));

            expect(container.querySelector('[data-field-key="status"]').textContent).toContain('updating');
        });

        it('does not render a group for the transport URL alone', () => {
            const data = {
                data: {
                    name: 'Search',
                    url: '/_components/Search?token=secret',
                    fingerprint: '',
                    props: {},
                    propsFromParent: {},
                    models: [],
                    actions: [],
                    listeners: [],
                    loading: [],
                    polling: null,
                    runtime: { status: 'idle', duration: null, httpStatus: null, error: null },
                    children: [],
                    parents: [],
                    otherControllers: [],
                },
            };

            const container = document.createElement('div');
            container.append(renderLive(data));

            expect(container.textContent).not.toContain('/_components/Search');
            expect(container.querySelector('[data-group="livecomponent-configuration"]')).toBeNull();
        });

        it('keeps transport internals out of public Props', () => {
            const container = document.createElement('div');
            container.append(
                renderLive({
                    data: {
                        props: {
                            query: 'ux',
                            '@checksum': 'very-long-internal-checksum',
                            '@attributes': { id: 'generated' },
                        },
                        propsFromParent: {},
                        models: [],
                        actions: [],
                        listeners: [],
                        loading: [],
                        polling: null,
                        runtime: { status: 'idle', duration: null, httpStatus: null, error: null },
                        url: '',
                        fingerprint: '',
                        children: [],
                        parents: [],
                        otherControllers: [],
                    },
                })
            );

            const props = container.querySelector('[data-group="livecomponent-props"]');
            expect(props.textContent).toContain('queryux');
            expect(props.textContent).not.toContain('@checksum');
            expect(props.textContent).not.toContain('@attributes');
        });
    });

    describe('runtime lifecycle', () => {
        it('records safe request, model, render and response metadata', () => {
            const handlers = new Map();
            const component = {
                on: vi.fn((event, handler) => handlers.set(event, handler)),
                off: vi.fn(),
            };
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.__component = component;
            document.body.appendChild(el);
            const records = [];
            plugin.setEventRecorder((entry) => records.push(entry));

            plugin.observe(el);
            handlers.get('request:started')({
                actions: [{ name: 'save', args: { secret: 'not-recorded' } }],
                updated: { title: 'not-recorded' },
                files: { upload: {} },
                props: { token: 'not-recorded' },
            });
            handlers.get('model:set')('title', 'aaaaa');
            handlers.get('render:started')('<p>not recorded</p>');
            handlers.get('render:finished')();
            handlers.get('response:error')({ response: { status: 422 } });

            expect(records.map((record) => record.event)).toEqual([
                'live:request',
                'live:model:set',
                'live:render:started',
                'live:render:finished',
                'live:response:error',
            ]);
            expect(records[0].detail).toEqual({ actions: ['save'], models: ['title'], files: ['upload'] });
            expect(JSON.stringify(records)).not.toContain('not-recorded');
            expect(records[1].detail).toEqual({ model: 'title', value: 'aaaaa' });
            expect(records.at(-1).detail).toEqual({ status: 422 });
        });

        it('redacts values for sensitive model names', () => {
            const handlers = new Map();
            const component = { on: (event, handler) => handlers.set(event, handler), off: vi.fn() };
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.__component = component;
            const records = [];
            plugin.setEventRecorder((entry) => records.push(entry));

            plugin.observe(el);
            handlers.get('model:set')('password', 'private');

            expect(records[0].detail).toEqual({ model: 'password', value: '[redacted]' });
        });

        it('omits empty request parameters from activity details', () => {
            const handlers = new Map();
            const component = { on: (event, handler) => handlers.set(event, handler), off: vi.fn() };
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.__component = component;
            const records = [];
            plugin.setEventRecorder((entry) => records.push(entry));

            plugin.observe(el);
            handlers.get('request:started')({ actions: [], updated: {}, files: {} });

            expect(records[0].detail).toBeNull();
        });

        it('subscribes once and releases hooks on destroy', () => {
            const component = { on: vi.fn(), off: vi.fn() };
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.__component = component;
            plugin.setEventRecorder(vi.fn());

            plugin.observe(el);
            plugin.observe(el);
            expect(component.on).toHaveBeenCalledTimes(5);
            plugin.destroy();
            expect(component.off).toHaveBeenCalledTimes(5);
        });

        it('releases hooks when a detected element is removed', () => {
            const component = { on: vi.fn(), off: vi.fn() };
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');
            el.__component = component;
            plugin.setEventRecorder(vi.fn());
            plugin.observe(el);

            plugin.onElementRemoved(el);

            expect(component.off).toHaveBeenCalledTimes(5);
        });
    });
});
