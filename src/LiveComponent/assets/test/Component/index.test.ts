import { waitFor } from '@testing-library/dom';
import { Response } from 'node-fetch';
import { describe, expect, it } from 'vitest';
import type { BackendAction, BackendInterface } from '../../src/Backend/Backend';
import BackendRequest from '../../src/Backend/BackendRequest';
import BackendResponse from '../../src/Backend/BackendResponse';
import Component, { proxifyComponent } from '../../src/Component';
import { noopElementDriver } from '../tools';

interface MockBackend extends BackendInterface {
    actions: BackendAction[];
}

const makeTestComponent = (): { component: Component; backend: MockBackend } => {
    const backend: MockBackend = {
        actions: [],
        makeRequest(_data: any, actions: BackendAction[]): BackendRequest {
            this.actions = actions;

            return new BackendRequest(
                // @ts-ignore Response doesn't quite match the underlying interface
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
            // @ts-ignore
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
            // @ts-ignore
            expect(proxy.firstName).toBe('Ryan');
        });

        it('calls set() on the component', () => {
            const { proxy } = makeDummyComponent();
            // @ts-ignore
            proxy.firstName = 'Ryan';
            expect(proxy.getData('firstName')).toBe('Ryan');
        });

        it('calls an action on a component', async () => {
            const { proxy, backend } = makeDummyComponent();
            // @ts-ignore
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

describe('BackendResponse.checkResponseType', () => {
    it('should detect valid JSON response', async () => {
        const jsonData = JSON.stringify({ message: 'hello' });
        const response = new Response(jsonData, {
            headers: {
                'Content-Type': 'application/json',
            },
        });

        const backendResponse = new BackendResponse(response);
        const result = await backendResponse.checkResponseType();

        expect(result.type).toBe('json');
        expect(result.body).toBe(jsonData);
    });

    it('should detect valid HTML response with correct Content-Type', async () => {
        const htmlContent = '<div>Live component</div>';
        const response = new Response(htmlContent, {
            headers: {
                'Content-Type': 'application/vnd.live-component+html',
            },
        });

        const backendResponse = new BackendResponse(response);
        const result = await backendResponse.checkResponseType();

        expect(result.type).toBe('html');
        expect(result.body).toBe(htmlContent);
    });

    it('should detect valid HTML response with X-Live-Redirect header', async () => {
        const htmlContent = '<div>Redirected HTML</div>';
        const response = new Response(htmlContent, {
            headers: {
                'X-Live-Redirect': '/some/path',
            },
        });

        const backendResponse = new BackendResponse(response);
        const result = await backendResponse.checkResponseType();

        expect(result.type).toBe('html');
        expect(result.body).toBe(htmlContent);
    });

    it('should detect invalid response (not JSON or HTML)', async () => {
        const plainText = 'Just a plain response';
        const response = new Response(plainText, {
            headers: {
                'Content-Type': 'text/plain',
            },
        });

        const backendResponse = new BackendResponse(response);
        const result = await backendResponse.checkResponseType();

        expect(result.type).toBe('invalid');
        expect(result.body).toBe(plainText);
    });

    it('should detect broken JSON as invalid', async () => {
        const brokenJson = '{"invalidJson": ';
        const response = new Response(brokenJson, {
            headers: {
                'Content-Type': 'application/json',
            },
        });

        const backendResponse = new BackendResponse(response);
        const result = await backendResponse.checkResponseType();

        expect(result.type).toBe('invalid');
        expect(result.body).toBe(brokenJson.trim());
    });

    it('should detect invalid response with empty body', async () => {
        const emptyBody = '';
        const response = new Response(emptyBody, {
            headers: {
                'Content-Type': 'application/vnd.live-component+html',
            },
        });

        const backendResponse = new BackendResponse(response);
        const result = await backendResponse.checkResponseType();

        expect(result.type).toBe('invalid');
        expect(result.body).toBe(emptyBody);
    });

    it('should detect invalid response with no Content-Type header', async () => {
        const bodyContent = '<div>Some HTML</div>';
        const response = new Response(bodyContent, {
            headers: {
                // no Content-Type header
            },
        });

        const backendResponse = new BackendResponse(response);
        const result = await backendResponse.checkResponseType();

        expect(result.type).toBe('invalid');
        expect(result.body).toBe(bodyContent);
    });

    it('should detect invalid response with empty body and no headers', async () => {
        const emptyBody = '';
        const response = new Response(emptyBody);

        const backendResponse = new BackendResponse(response);
        const result = await backendResponse.checkResponseType();

        expect(result.type).toBe('invalid');
        expect(result.body).toBe(emptyBody);
    });
});
