import { waitFor } from '@testing-library/dom';
import { Response } from 'node-fetch';
import { describe, expect, it, vi } from 'vitest';
import type { BackendAction, BackendInterface } from '../../../src/Backend/Backend';
import BackendRequest from '../../../src/Backend/BackendRequest';
import type BackendResponse from '../../../src/Backend/BackendResponse';
import Component, { proxifyComponent } from '../../../src/Component';
import { noopElementDriver } from '../../tools';

interface MockBackend extends BackendInterface {
    actions: BackendAction[];
}

const makeTestComponent = (): { component: Component; backend: MockBackend } => {
    const backend: MockBackend = {
        actions: [],
        makeRequest(_data: any, actions: BackendAction[]): BackendRequest {
            this.actions = actions;

            return new BackendRequest(
                // @ts-expect-error Response doesn't quite match the underlying interface
                new Promise((resolve) => resolve(new Response('<div data-live-props-value="{}"></div>'))),
                [],
                []
            );
        },
    };

    const component = new Component(
        document.createElement('div'),
        'test-component',
        { firstName: '', product: { name: '' } },
        [],
        null,
        backend,
        new noopElementDriver()
    );

    return {
        component,
        backend,
    };
};

describe('Component class', () => {
    describe('set() method', () => {
        it('returns a Promise that eventually resolves', async () => {
            const { component } = makeTestComponent();

            let backendResponse: BackendResponse | null = null;

            // set model but no re-render
            const promise = component.set('firstName', 'Ryan', false);
            // when this promise IS finally resolved, set the flag to true
            promise.then((response) => {
                backendResponse = response;
            });
            // it should not have happened yet
            expect(backendResponse).toBeNull();

            // set model WITH re-render
            component.set('firstName', 'Kevin', true);
            // it's still not *instantly* resolve - it'll
            expect(backendResponse).toBeNull();
            await waitFor(() => expect(backendResponse).not.toBeNull());
            // @ts-expect-error
            expect(await backendResponse?.getBody()).toEqual('<div data-live-props-value="{}"></div>');
        });

        it('errors when an invalid model is passed', async () => {
            const { component } = makeTestComponent();

            // setting nested - totally ok
            component.set('product.name', 'Ryan', false);
            expect(() => {
                component.set('notARealModel', 'Ryan', false);
            }).toThrow('Invalid model name "notARealModel"');
        });
    });

    describe('file download handling', () => {
        const HTML = '<div data-controller="live" data-live-props-value="{}">rendered</div>';

        // the noop driver throws on every method: rendering needs one that answers
        class renderingDriver extends noopElementDriver {
            constructor(private props: any = {}) {
                super();
            }
            getComponentProps(): any {
                return this.props;
            }
            getEventsToEmit(): Array<any> {
                return [];
            }
            getBrowserEventsToDispatch(): Array<any> {
                return [];
            }
        }

        const makeComponent = (
            file: string | null,
            serverProps: any = {},
            extraHeaders: Record<string, string> = {}
        ): Component => {
            const encoder = new TextEncoder();
            const htmlBytes = encoder.encode(HTML);
            const headers: Record<string, string> = {
                'Content-Type': 'application/vnd.live-component+html',
                ...extraHeaders,
            };
            let body: Uint8Array = htmlBytes;

            if (null !== file) {
                const fileBytes = encoder.encode(file);
                body = new Uint8Array(htmlBytes.length + fileBytes.length);
                body.set(htmlBytes, 0);
                body.set(fileBytes, htmlBytes.length);

                headers['X-Live-Html-Length'] = String(htmlBytes.length);
                headers['X-Live-Download-Filename'] = 'r%C3%A9sum%C3%A9.csv';
                headers['X-Live-Download-Type'] = 'text/csv';
            }

            const backend: MockBackend = {
                actions: [],
                makeRequest(_data: any, actions: BackendAction[]): BackendRequest {
                    this.actions = actions;

                    return new BackendRequest(
                        new Promise((resolve) =>
                            resolve(
                                // @ts-expect-error Response doesn't quite match the underlying interface
                                new Response(body, { headers })
                            )
                        ),
                        [],
                        []
                    );
                },
            };

            return new Component(
                document.createElement('div'),
                'test-component',
                { firstName: '', product: { name: '' } },
                [],
                null,
                backend,
                new renderingDriver(serverProps)
            );
        };

        const withMockedObjectUrl = async (fn: () => Promise<void>): Promise<void> => {
            const createObjectURL = URL.createObjectURL;
            const revokeObjectURL = URL.revokeObjectURL;
            URL.createObjectURL = vi.fn(() => 'blob:mock-url');
            URL.revokeObjectURL = vi.fn();
            try {
                await fn();
            } finally {
                URL.createObjectURL = createObjectURL;
                URL.revokeObjectURL = revokeObjectURL;
            }
        };

        it('triggers a browser download after the component rendered', async () => {
            await withMockedObjectUrl(async () => {
                const component = makeComponent('a,b,c');
                const appendChild = vi.spyOn(document.body, 'appendChild');

                try {
                    await component.set('firstName', 'Kevin', true);

                    const link = appendChild.mock.calls[0][0] as HTMLAnchorElement;
                    expect(link.tagName).toBe('A');
                    expect(link.href).toBe('blob:mock-url');
                    expect(link.download).toBe('résumé.csv');
                } finally {
                    appendChild.mockRestore();
                }
            });
        });

        it('keeps the state the action changed on the server', async () => {
            // the whole point: the file rides along, so the render still happens
            await withMockedObjectUrl(async () => {
                const component = makeComponent('a,b,c', { firstName: 'Kevin', downloadCount: 3 });

                await component.set('firstName', 'Ryan', true);

                expect(component.getData('downloadCount')).toBe(3);
                expect(component.getData('firstName')).toBe('Kevin');
            });
        });

        it('renders the HTML part and not the file bytes', async () => {
            await withMockedObjectUrl(async () => {
                const component = makeComponent('a,b,c');

                await component.set('firstName', 'Kevin', true);

                expect(component.element.textContent).toBe('rendered');
                expect(component.element.textContent).not.toContain('a,b,c');
            });
        });

        it('revokes the object URL once the download has been engaged', async () => {
            await withMockedObjectUrl(async () => {
                const component = makeComponent('a,b,c');

                await component.set('firstName', 'Kevin', true);

                expect(URL.revokeObjectURL).not.toHaveBeenCalled();
                await new Promise((resolve) => setTimeout(resolve, 100));
                expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:mock-url');
            });
        });

        it('still applies the props when triggering the download throws', async () => {
            // URL.createObjectURL can fail for real (memory pressure). The render must not
            // be lost with it: the action's state change is already on the page.
            const createObjectURL = URL.createObjectURL;
            const revokeObjectURL = URL.revokeObjectURL;
            URL.createObjectURL = vi.fn(() => {
                throw new Error('createObjectURL failed');
            });
            URL.revokeObjectURL = vi.fn();

            try {
                const component = makeComponent('a,b,c', { firstName: 'Kevin', downloadCount: 7 });

                await component.set('firstName', 'Ryan', true);

                expect(component.getData('downloadCount')).toBe(7);
                expect(component.getData('firstName')).toBe('Kevin');
            } finally {
                URL.createObjectURL = createObjectURL;
                URL.revokeObjectURL = revokeObjectURL;
            }
        });

        it('follows a download URL instead of building a blob', async () => {
            await withMockedObjectUrl(async () => {
                const component = makeComponent(null, {}, { 'X-Live-Download-Url': '/exports/report.csv' });
                const appendChild = vi.spyOn(document.body, 'appendChild');

                try {
                    await component.set('firstName', 'Kevin', true);

                    const link = appendChild.mock.calls[0][0] as HTMLAnchorElement;
                    expect(link.href).toContain('/exports/report.csv');
                    // no blob involved: the browser fetches it itself
                    expect(URL.createObjectURL).not.toHaveBeenCalled();
                } finally {
                    appendChild.mockRestore();
                }
            });
        });

        it('creates no link when the response carries no file', async () => {
            await withMockedObjectUrl(async () => {
                const component = makeComponent(null);
                const appendChild = vi.spyOn(document.body, 'appendChild');

                try {
                    await component.set('firstName', 'Kevin', true);

                    expect(appendChild).not.toHaveBeenCalled();
                    expect(URL.createObjectURL).not.toHaveBeenCalled();
                } finally {
                    appendChild.mockRestore();
                }
            });
        });
    });

    describe('component removal', () => {
        // the noop driver throws on every method: a request needs one that answers
        class renderingDriver extends noopElementDriver {
            constructor(
                private eventsToEmit: Array<any> = [],
                private browserEventsToDispatch: Array<any> = []
            ) {
                super();
            }

            getComponentProps(): any {
                return {};
            }
            getEventsToEmit(): Array<any> {
                return this.eventsToEmit;
            }
            getBrowserEventsToDispatch(): Array<any> {
                return this.browserEventsToDispatch;
            }
        }

        /**
         * A removal carries one final LiveComponent render. X-Live-Remove tells the client
         * to process it and then take the component off the page.
         */
        const makeRemovableComponent = (
            headers: Record<string, string> = {
                'Content-Type': 'application/vnd.live-component+html',
                'X-Live-Remove': '1',
            },
            body = '<div data-controller="live" data-live-props-value="{}">rendered</div>',
            eventsToEmit: Array<any> = [],
            browserEventsToDispatch: Array<any> = []
        ): Component => {
            const backend: MockBackend = {
                actions: [],
                makeRequest(_data: any, actions: BackendAction[]): BackendRequest {
                    this.actions = actions;

                    return new BackendRequest(
                        new Promise((resolve) =>
                            resolve(
                                // @ts-expect-error Response doesn't quite match the underlying interface
                                new Response(body, { status: 200, headers })
                            )
                        ),
                        [],
                        []
                    );
                },
            };

            const element = document.createElement('div');
            document.body.appendChild(element);

            return new Component(
                element,
                'test-component',
                { firstName: '' },
                [],
                null,
                backend,
                new renderingDriver(eventsToEmit, browserEventsToDispatch)
            );
        };

        /** The element is only dropped a frame later, once its animations have settled. */
        const nextFrame = (): Promise<void> => new Promise((resolve) => requestAnimationFrame(() => resolve()));

        it('takes the element off the page', async () => {
            const component = makeRemovableComponent();

            expect(component.element.isConnected).toBe(true);

            await component.set('firstName', 'Kevin', true);
            await nextFrame();
            await nextFrame();

            expect(component.element.isConnected).toBe(false);
        });

        it('marks the element as leaving, so the page can animate it out', async () => {
            const component = makeRemovableComponent();

            await component.set('firstName', 'Kevin', true);

            // still there, but no longer a live component
            expect(component.element.isConnected).toBe(true);
            expect(component.element.hasAttribute('data-live-removing')).toBe(true);
        });

        it('strips the props, so nothing can re-hydrate the element', async () => {
            const component = makeRemovableComponent();
            component.element.setAttribute('data-live-props-value', '{"firstName":"Kevin"}');
            component.element.setAttribute('data-live-url-value', '/_components/foo');

            await component.set('firstName', 'Kevin', true);

            expect(component.element.hasAttribute('data-live-props-value')).toBe(false);
            expect(component.element.hasAttribute('data-live-url-value')).toBe(false);
        });

        it('never talks to the server again, even while it is still on the page', async () => {
            const component = makeRemovableComponent();
            const makeRequest = vi.spyOn(component.backend, 'makeRequest');

            await component.set('firstName', 'Kevin', true);
            makeRequest.mockClear();

            // render() reaches the request funnel synchronously, unlike the debounced
            // action() path: the element keeps its listeners until it goes, so a click
            // must not reach a component that is already gone
            component.render();

            expect(makeRequest).not.toHaveBeenCalled();
        });

        it('would otherwise have talked to the server', async () => {
            // the counter-proof: without a removal, the very same call does reach the backend
            const component = makeRemovableComponent(
                { 'Content-Type': 'application/vnd.live-component+html' },
                '<div data-controller="live" data-live-props-value="{}">rendered</div>'
            );
            const makeRequest = vi.spyOn(component.backend, 'makeRequest');

            await component.set('firstName', 'Kevin', true);
            makeRequest.mockClear();

            component.render();

            expect(makeRequest).toHaveBeenCalled();
        });

        it('processes the final render before removing the component', async () => {
            const component = makeRemovableComponent();
            const renderStarted = vi.fn();
            component.on('render:started', renderStarted);

            await component.set('firstName', 'Kevin', true);

            expect(renderStarted).toHaveBeenCalledOnce();
            expect(component.element).toHaveTextContent('rendered');
        });

        it('emits LiveComponent events before disconnecting', async () => {
            const component = makeRemovableComponent(undefined, undefined, [
                { event: 'componentRemoved', data: { id: 42 }, target: null, componentName: null },
            ]);
            const listener = new Component(
                document.createElement('div'),
                'listener-component',
                {},
                [{ event: 'componentRemoved', action: 'refresh' }],
                null,
                component.backend,
                new renderingDriver()
            );
            const action = vi.spyOn(listener, 'action');
            component.connect();
            listener.connect();

            try {
                await component.set('firstName', 'Kevin', true);

                expect(action).toHaveBeenCalledWith('refresh', { id: 42 }, 1);
            } finally {
                listener.disconnect();
            }
        });

        it('dispatches browser events before disconnecting', async () => {
            const component = makeRemovableComponent(
                undefined,
                undefined,
                [],
                [{ event: 'component:removed', payload: { id: 42 } }]
            );
            const received: Array<any> = [];
            const makeRequest = vi.spyOn(component.backend, 'makeRequest');
            let disconnected = false;
            component.on('disconnect', () => {
                disconnected = true;
            });
            component.element.addEventListener('component:removed', (event) => {
                received.push({ detail: (event as CustomEvent).detail, disconnected });
                component.render();
            });

            await component.set('firstName', 'Kevin', true);

            expect(received).toEqual([{ detail: { id: 42 }, disconnected: false }]);
            expect(makeRequest).toHaveBeenCalledOnce();
            expect(disconnected).toBe(true);
        });

        it('is not mistaken for a response the client cannot use', async () => {
            const component = makeRemovableComponent();
            const responseError = vi.fn();
            component.on('response:error', responseError);

            await component.set('firstName', 'Kevin', true);

            expect(responseError).not.toHaveBeenCalled();
        });

        it('leaves the element alone without the header', async () => {
            const component = makeRemovableComponent(
                { 'Content-Type': 'application/vnd.live-component+html' },
                '<div data-controller="live" data-live-props-value="{}">rendered</div>'
            );

            await component.set('firstName', 'Kevin', true);

            expect(component.element.isConnected).toBe(true);
        });
    });

    describe('Proxy wrapper', () => {
        const makeDummyComponent = (): { proxy: Component; backend: MockBackend } => {
            const { backend, component } = makeTestComponent();
            return {
                proxy: proxifyComponent(component),
                backend,
            };
        };

        it('forwards real property gets', () => {
            const { proxy } = makeDummyComponent();
            expect(proxy.element).toBeInstanceOf(HTMLDivElement);
        });

        it('forwards real method calls', () => {
            const { proxy } = makeDummyComponent();
            proxy.set('firstName', 'Ryan');
            expect(proxy.valueStore.get('firstName')).toBe('Ryan');
        });

        it('forwards real property sets', () => {
            const { proxy } = makeDummyComponent();
            proxy.defaultDebounce = 123;
            expect(proxy.defaultDebounce).toBe(123);
        });

        it('calls get() on the component', () => {
            const { proxy } = makeDummyComponent();
            proxy.set('firstName', 'Ryan');
            // @ts-expect-error
            expect(proxy.firstName).toBe('Ryan');
        });

        it('calls set() on the component', () => {
            const { proxy } = makeDummyComponent();
            // @ts-expect-error
            proxy.firstName = 'Ryan';
            expect(proxy.getData('firstName')).toBe('Ryan');
        });

        it('calls an action on a component', async () => {
            const { proxy, backend } = makeDummyComponent();
            // @ts-expect-error
            proxy.save({ foo: 'bar', secondArg: 'secondValue' });

            // ugly: the action delays for 0ms, so we just need a TINy
            // delay here before we start asserting
            await new Promise((resolve) => setTimeout(resolve, 5));
            expect(backend.actions).toHaveLength(1);
            expect(backend.actions[0].name).toBe('save');
            expect(backend.actions[0].args).toEqual({ foo: 'bar', secondArg: 'secondValue' });
        });

        it('does not turn the toJSON protocol probe of JSON.stringify() into an action', async () => {
            const { proxy, backend } = makeDummyComponent();

            // @ts-expect-error
            expect(proxy.toJSON).toBeUndefined();

            try {
                JSON.stringify(proxy);
            } catch {
                // the component graph is circular, so stringify throws exactly like
                // it does on the unproxied component; all that matters here is that
                // the "toJSON" probe did not queue a server action
            }

            await new Promise((resolve) => setTimeout(resolve, 5));
            expect(backend.actions).toHaveLength(0);
        });

        it('does not turn the then protocol probe of promise assimilation into an action', async () => {
            const { proxy, backend } = makeDummyComponent();

            // if "then" returned a callable, the proxy would be treated as a
            // thenable and this promise would never resolve to the proxy itself
            const resolved = await Promise.resolve(proxy);

            expect(resolved).toBe(proxy);
            await new Promise((resolve) => setTimeout(resolve, 5));
            expect(backend.actions).toHaveLength(0);
        });

        it('still exposes models named like protocol probes', () => {
            const { backend } = makeTestComponent();
            const component = new Component(
                document.createElement('div'),
                'test-component',
                { toJSON: 'model value', then: 'other value' },
                [],
                null,
                backend,
                new noopElementDriver()
            );
            const proxy = proxifyComponent(component);

            // @ts-expect-error
            expect(proxy.toJSON).toBe('model value');
            // @ts-expect-error
            expect(proxy.then).toBe('other value');
        });
    });
});
