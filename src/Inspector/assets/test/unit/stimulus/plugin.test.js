import { renderStimulus } from '../../../src/stimulus/render';
import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { StimulusPlugin } from '../../../src/stimulus/plugin';

describe('StimulusPlugin', () => {
    let plugin;

    beforeEach(() => {
        plugin = new StimulusPlugin();
        document.body.innerHTML = '';
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    describe('canHandle()', () => {
        it('returns true for elements with data-controller', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            expect(plugin.canHandle(el)).toBe(true);
        });

        it('returns false for elements without data-controller', () => {
            const el = document.createElement('div');
            expect(plugin.canHandle(el)).toBe(false);
        });

        it('handles mixed LiveComponent and Stimulus controllers without duplicating live', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live foo-bar');

            expect(plugin.canHandle(el)).toBe(true);
            expect(plugin.parse(el).data.controllers).toEqual(['foo-bar']);
        });

        it('leaves a pure live controller to the LiveComponent plugin', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'live');

            expect(plugin.canHandle(el)).toBe(false);
        });
    });

    describe('parse()', () => {
        it('extracts controller names', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello world');
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.controllers).toEqual(['hello', 'world']);
        });

        it('extracts values from data-{controller}-{key}-value attributes', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'counter');
            el.setAttribute('data-counter-count-value', '42');
            el.setAttribute('data-counter-name-value', 'test');
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.values.counter).toEqual({ count: 42, name: 'test' });
        });

        it('keeps compound value and class names in their controller namespace', () => {
            const root = document.createElement('div');
            root.dataset.controller = 'demo admin--list';
            root.setAttribute('data-demo-page-size-value', '25');
            root.setAttribute('data-admin--list-page-size-value', '50');
            root.setAttribute('data-admin--list-loading-state-class', 'is-loading');
            root.setAttribute('data-unrelated-count-value', '3');

            const data = plugin.parse(root).data;

            expect(data.values).toEqual({ demo: { pageSize: 25 }, 'admin--list': { pageSize: 50 } });
            expect(data.classes).toEqual({ 'admin--list': { loadingState: 'is-loading' } });
        });

        it('matches camelCase runtime declarations to their dashed attributes', () => {
            class ListController {
                static values = { pageSize: { type: Number, default: 10 } };
                static classes = ['loadingState'];
            }
            plugin.setApplication({ getControllerForElementAndIdentifier: () => new ListController() });
            const root = document.createElement('div');
            root.dataset.controller = 'list';
            root.setAttribute('data-list-page-size-value', '25');
            root.setAttribute('data-list-loading-state-class', 'is-loading');

            const data = plugin.parse(root).data;

            expect(data.valueStates.list).toEqual([{ name: 'pageSize', value: 25, status: 'connected' }]);
            expect(data.classStates.list).toEqual([{ name: 'loadingState', value: 'is-loading', status: 'connected' }]);
        });

        it('redacts and bounds configured values before storing them', () => {
            const root = document.createElement('div');
            root.dataset.controller = 'demo';
            root.setAttribute('data-demo-password-value', 'private');
            root.setAttribute('data-demo-options-value', JSON.stringify({ token: 'private', title: 'x'.repeat(600) }));

            const data = plugin.parse(root).data;

            expect(data.values.demo).toEqual({
                password: '[redacted]',
                options: { token: '[redacted]', title: 'x'.repeat(500) },
            });
            expect(JSON.stringify(data.valueStates)).not.toContain('private');
        });

        it('resolves targets within scope', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'search');
            const input = document.createElement('input');
            input.setAttribute('data-search-target', 'input');
            el.appendChild(input);
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.targets.search.elements).toHaveLength(1);
            expect(result.data.targets.search.elements[0].name).toBe('input');
            expect(result.data.targets.search.elements[0].element).toBe(input);
        });

        it('resolves actions within scope', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'search');
            const btn = document.createElement('button');
            btn.setAttribute('data-action', 'click->search#perform');
            el.appendChild(btn);
            document.body.appendChild(el);

            const result = plugin.parse(el);
            expect(result.data.actions.search).toHaveLength(1);
            expect(result.data.actions.search[0].event).toBe('click');
            expect(result.data.actions.search[0].method).toBe('perform');
        });

        it('preserves action filters, scope, options and safe params', () => {
            const root = document.createElement('div');
            root.dataset.controller = 'search';
            const action = document.createElement('input');
            action.dataset.action = 'keydown.enter@window->search#perform:prevent:stop';
            action.setAttribute('data-search-page-param', '2');
            action.setAttribute('data-search-token-param', 'private');
            root.appendChild(action);
            document.body.appendChild(root);

            expect(plugin.parse(root).data.actions.search[0]).toMatchObject({
                event: 'keydown',
                scope: 'window',
                filters: ['enter'],
                options: ['prevent', 'stop'],
                params: { page: 2, token: '[redacted]' },
            });
        });

        it.each([
            ['button', 'click'],
            ['form', 'submit'],
            ['input', 'input'],
            ['textarea', 'input'],
            ['select', 'change'],
            ['details', 'toggle'],
            ['span', 'default'],
        ])('resolves the default action event for <%s> as %s', (tag, expectedEvent) => {
            const root = document.createElement('div');
            root.setAttribute('data-controller', 'search');
            const action = document.createElement(tag);
            action.setAttribute('data-action', 'search#perform');
            root.appendChild(action);
            document.body.appendChild(root);

            const result = plugin.parse(root);

            expect(result.data.actions.search[0]).toEqual({
                event: expectedEvent,
                method: 'perform',
                element: action,
                status: 'dom-only',
            });
        });

        it('merges connected runtime declarations with DOM findings', () => {
            class CounterController {
                static targets = ['count', 'missing'];
                static values = { step: Number, delay: { type: Number, default: 300 }, empty: Number };
                static classes = ['active', 'missing'];
                static outlets = ['display'];
                increment() {}
            }
            const instance = new CounterController();
            plugin.setApplication({
                getControllerForElementAndIdentifier: (_element, identifier) =>
                    identifier === 'counter' ? instance : null,
            });
            const root = document.createElement('div');
            root.dataset.controller = 'counter';
            root.dataset.counterStepValue = '2';
            root.dataset.counterActiveClass = 'is-active';
            root.dataset.counterDisplayOutlet = '#display';
            const count = document.createElement('span');
            count.dataset.counterTarget = 'count';
            const action = document.createElement('button');
            action.dataset.action = 'click->counter#increment click->counter#unknown';
            const display = document.createElement('div');
            display.id = 'display';
            display.dataset.controller = 'display';
            root.append(count, action);
            document.body.append(root, display);

            const data = plugin.parse(root).data;

            expect(data.runtimeAvailable).toBe(true);
            expect(data.connectedControllers).toEqual(['counter']);
            expect(data.valueStates.counter).toEqual([
                { name: 'step', value: 2, status: 'connected' },
                { name: 'delay', value: 300, status: 'default' },
                { name: 'empty', value: 0, status: 'default' },
            ]);
            expect(data.targets.counter.items).toEqual([
                { name: 'count', elements: [count], declared: true, status: 'connected' },
                { name: 'missing', elements: [], declared: true, status: 'missing' },
            ]);
            expect(data.classStates.counter).toEqual([
                { name: 'active', value: 'is-active', status: 'connected' },
                { name: 'missing', value: 'not configured', status: 'missing' },
            ]);
            expect(data.outlets.counter[0]).toMatchObject({
                name: 'display',
                selector: '#display',
                elements: [display],
                declared: true,
                status: 'connected',
            });
            expect(data.actions.counter.map((item) => item.status)).toEqual(['connected', 'missing-method']);
        });

        it('does not invoke static or value-default getters', () => {
            let reads = 0;
            class UnsafeController {
                static get targets() {
                    reads++;
                    return ['target'];
                }
                static values = {
                    get computed() {
                        reads++;
                        return String;
                    },
                    secret: {
                        type: String,
                        get default() {
                            reads++;
                            return 'private';
                        },
                    },
                };
            }
            const instance = new UnsafeController();
            Object.defineProperty(instance, 'constructor', {
                get() {
                    reads++;
                    return UnsafeController;
                },
            });
            plugin.setApplication({ getControllerForElementAndIdentifier: () => instance });
            const root = document.createElement('div');
            root.dataset.controller = 'unsafe';
            document.body.appendChild(root);

            plugin.parse(root);

            expect(reads).toBe(0);
        });

        it('resolves outlets by selector', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'search');
            el.setAttribute('data-search-results-outlet', '.results');
            document.body.appendChild(el);

            const target = document.createElement('div');
            target.className = 'results';
            document.body.appendChild(target);

            const result = plugin.parse(el);
            expect(result.data.outlets.search).toHaveLength(1);
            expect(result.data.outlets.search[0].name).toBe('results');
            expect(result.data.outlets.search[0].elements).toContain(target);
        });

        it('finds child controllers', () => {
            const parent = document.createElement('div');
            parent.setAttribute('data-controller', 'parent');
            const child = document.createElement('div');
            child.setAttribute('data-controller', 'child');
            parent.appendChild(child);
            document.body.appendChild(parent);

            const result = plugin.parse(parent);
            expect(result.data.children).toHaveLength(1);
            expect(result.data.children[0].controllers).toEqual(['child']);
        });

        it('finds parent controllers', () => {
            const grandparent = document.createElement('div');
            grandparent.setAttribute('data-controller', 'grandparent');
            const parent = document.createElement('div');
            parent.setAttribute('data-controller', 'parent');
            const child = document.createElement('div');
            child.setAttribute('data-controller', 'child');
            parent.appendChild(child);
            grandparent.appendChild(parent);
            document.body.appendChild(grandparent);

            const result = plugin.parse(child);
            expect(result.data.parents).toHaveLength(2);
            expect(result.data.parents[0].controllers).toEqual(['parent']);
            expect(result.data.parents[1].controllers).toEqual(['grandparent']);
        });

        it('respects scope boundaries (nested same-name controllers)', () => {
            const outer = document.createElement('div');
            outer.setAttribute('data-controller', 'tabs');
            const inner = document.createElement('div');
            inner.setAttribute('data-controller', 'tabs');
            const innerTarget = document.createElement('div');
            innerTarget.setAttribute('data-tabs-target', 'panel');
            inner.appendChild(innerTarget);
            outer.appendChild(inner);
            document.body.appendChild(outer);

            const result = plugin.parse(outer);
            // The inner target belongs to the inner controller, not outer
            expect(result.data.targets.tabs?.elements || []).toHaveLength(0);
        });
        it('extracts CSS classes from data-{controller}-{classname}-class attributes', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'tabs');
            el.setAttribute('data-tabs-active-class', 'bg-blue-500');
            el.setAttribute('data-tabs-inactive-class', 'bg-gray-200');
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.classes.tabs).toEqual({
                active: 'bg-blue-500',
                inactive: 'bg-gray-200',
            });
        });

        it('handles element with no values, targets, or actions gracefully', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'empty');
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.controllers).toEqual(['empty']);
            expect(result.data.values).toEqual({});
            expect(result.data.targets).toEqual({});
            expect(result.data.actions).toEqual({ empty: [] });
            expect(result.data.classes).toEqual({});
            expect(result.data.children).toEqual([]);
            expect(result.data.parents).toEqual([]);
        });

        it('handles multiple controllers with separate value namespaces', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'counter timer');
            el.setAttribute('data-counter-count-value', '10');
            el.setAttribute('data-timer-duration-value', '30');
            document.body.appendChild(el);
            const result = plugin.parse(el);
            expect(result.data.values.counter).toEqual({ count: 10 });
            expect(result.data.values.timer).toEqual({ duration: 30 });
        });
    });

    describe('canHandle()', () => {
        it('returns false for elements with empty data-controller', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', '');
            expect(plugin.canHandle(el)).toBe(false);
        });
    });

    describe('getDisplayName()', () => {
        it('returns single controller name', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            expect(plugin.getDisplayName(el)).toBe('hello');
        });

        it('returns "name (+N)" for multiple controllers', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello world foo');
            expect(plugin.getDisplayName(el)).toBe('hello (+2)');
        });

        it('returns controller name for single controller', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'dropdown');
            expect(plugin.getDisplayName(el)).toBe('dropdown');
        });
    });

    describe('renderCard()', () => {
        it('renders the expected data fields as a DocumentFragment', () => {
            const data = {
                data: {
                    controllers: ['test'],
                    values: { test: { a: 1 } },
                    classes: {},
                    targets: {},
                    actions: {},
                    outlets: {},
                    children: [],
                    parents: [],
                },
            };
            const frag = renderStimulus(data);
            expect(frag).toBeTruthy();
        });

        it('renders actions as readable event to method text without null', () => {
            const button = document.createElement('button');
            const data = {
                data: {
                    controllers: ['search'],
                    values: {},
                    classes: {},
                    targets: {},
                    outlets: {},
                    actions: { search: [{ event: 'click', method: 'perform', element: button }] },
                    children: [],
                    parents: [],
                },
            };

            const container = document.createElement('div');
            container.appendChild(renderStimulus(data));

            expect(container.textContent).toContain('perform()click');
            expect(container.textContent).not.toContain('null');
            expect(container.querySelector('.target-pill')).not.toBeNull();
        });

        it('organizes Stimulus contracts into visible non-empty groups', () => {
            const target = document.createElement('span');
            const action = document.createElement('button');
            const outlet = document.createElement('div');
            const data = {
                data: {
                    controllers: ['counter'],
                    values: { counter: { step: 1 } },
                    runtimeAvailable: false,
                    connectedControllers: [],
                    valueStates: { counter: [{ name: 'step', value: 1, status: 'configured' }] },
                    targets: { counter: { elements: [{ name: 'count', element: target }] } },
                    classStates: { counter: [{ name: 'active', value: 'is-active', status: 'configured' }] },
                    actions: {
                        counter: [
                            {
                                event: 'click',
                                method: 'increment',
                                element: action,
                                params: { amount: 2, options: { source: 'catalog' } },
                            },
                        ],
                    },
                    classes: { counter: { active: 'is-active' } },
                    outlets: { counter: [{ name: 'display', selector: '#display', elements: [outlet] }] },
                    children: [],
                    parents: [],
                },
            };
            data.data.targets.counter.items = [{ name: 'count', elements: [target], status: 'dom-only' }];

            const container = document.createElement('div');
            container.append(renderStimulus(data));

            expect(
                [...container.querySelectorAll('.groups > .group > .title > .name')].map((title) => title.textContent)
            ).toEqual(['Values', 'Classes', 'Actions', 'Targets']);
            expect(container.querySelector('.groups')).not.toBeNull();
            expect(container.querySelectorAll('dl.key-values')).toHaveLength(3);
            expect(container.querySelectorAll('dl.compound-field')).toHaveLength(1);
            expect(container.querySelector('[data-group="stimulus-actions"]').open).toBe(true);
            expect(container.querySelector('[data-group="stimulus-actions"] > .title > .badge')).toBeNull();
            expect(container.querySelector('[data-group="stimulus-targets"]').open).toBe(true);
            expect(container.querySelector('[data-tab]')).toBeNull();
            expect(container.querySelector('[data-field-key="step"] dt').textContent).toBe('step');
            expect(container.querySelector('[data-group="stimulus-classes"] [data-field-key="active"]')).not.toBeNull();
            expect(container.querySelector('[data-group="stimulus-outlets"]')).toBeNull();
            expect(container.querySelector('[data-field-key="element:count"] dt').textContent).toBe('count');
            expect(container.querySelector('[data-field-key="element:increment()"] dt').textContent).toBe(
                'increment()'
            );
            expect(container.querySelector('[data-field-key="element:increment()"] .value-note').textContent).toBe(
                'click'
            );
            expect(container.querySelectorAll('[data-group="stimulus-actions"] [data-element]')).toHaveLength(1);
            expect(
                container.querySelector(
                    '[data-group="stimulus-actions"] .compound-field [data-field-key="amount"] .value'
                ).textContent
            ).toBe('2');
            expect(
                container.querySelector(
                    '[data-group="stimulus-actions"] .compound-field [data-field-key="options"] .tree'
                )
            ).not.toBeNull();
            expect(container.querySelector('[data-field-key="element:display[1] outlet"]')).toBeNull();
            expect(container.textContent).not.toContain('Wiring');
        });

        it('keeps unresolved outlets in their own group without duplicating connected outlets', () => {
            const data = {
                data: {
                    controllers: ['counter'],
                    runtimeAvailable: true,
                    connectedControllers: ['counter'],
                    valueStates: {},
                    targets: {},
                    actions: {},
                    classStates: {},
                    outlets: { counter: [{ name: 'display', selector: '#missing', elements: [], status: 'missing' }] },
                    children: [],
                    parents: [],
                },
            };

            const container = document.createElement('div');
            container.append(renderStimulus(data));

            expect(container.querySelector('[data-group="stimulus-values"]')).toBeNull();
            expect(
                container.querySelector('[data-group="stimulus-outlets"] [data-field-key="display outlet"]')
            ).not.toBeNull();
            expect(container.querySelector('[data-group="stimulus-outlets"] > .title > .badge')).toBeNull();
        });

        it('omits a normal active runtime status from Values', () => {
            const container = document.createElement('div');
            container.append(
                renderStimulus({
                    data: {
                        controllers: ['counter'],
                        runtimeAvailable: true,
                        connectedControllers: ['counter'],
                        valueStates: { counter: [{ name: 'step', value: 1, status: 'connected' }] },
                        targets: {},
                        actions: {},
                        classStates: {},
                        outlets: {},
                    },
                })
            );

            expect(container.querySelector('[data-group="stimulus-context"]')).toBeNull();
            expect(container.querySelector('[data-group="stimulus-values"]')).not.toBeNull();
            expect(container.querySelector('[data-group="stimulus-values"] [data-field-key="status"]')).toBeNull();
        });

        it('omits a missing target instead of reserving empty space', () => {
            const container = document.createElement('div');
            container.append(
                renderStimulus({
                    data: {
                        controllers: ['counter'],
                        runtimeAvailable: true,
                        connectedControllers: ['counter'],
                        valueStates: {},
                        targets: { counter: { items: [{ name: 'optional', elements: [], status: 'missing' }] } },
                        actions: {},
                        classStates: {},
                        outlets: {},
                    },
                })
            );

            expect(container.querySelector('[data-field-key="optional"]')).toBeNull();
        });
    });
});
