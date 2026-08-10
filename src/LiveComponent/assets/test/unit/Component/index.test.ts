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
    });
});
