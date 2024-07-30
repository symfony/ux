import Component, { proxifyComponent } from '../../src/Component';
import type {BackendAction, BackendInterface} from '../../src/Backend/Backend';
import BackendRequest from '../../src/Backend/BackendRequest';
import { Response } from 'node-fetch';
import { waitFor } from '@testing-library/dom';
import type BackendResponse from '../../src/Backend/BackendResponse';
import { noopElementDriver } from '../tools';

interface MockBackend extends BackendInterface {
    actions: BackendAction[],
}

const makeTestComponent = (): { component: Component, backend: MockBackend } => {
    const backend: MockBackend = {
        actions: [],
        makeRequest(data: any, actions: BackendAction[]): BackendRequest {
            this.actions = actions;

            return new BackendRequest(
                // @ts-ignore Response doesn't quite match the underlying interface
                new Promise((resolve) => resolve(new Response('<div data-live-props-value="{}"></div>'))),
                [],
                []
            )
        }
    }

    const component = new Component(
        document.createElement('div'),
        'test-component',
        { firstName: '', product: { name: '' } },
        [],
        null,
        backend,
        new noopElementDriver(),
    );

    return {
        component,
        backend
    }
}

describe('Component class', () => {
    describe('set() method', () => {
        it('returns a Promise that eventually resolves', async () => {
            const { component } = makeTestComponent();

            let backendResponse: BackendResponse|null = null;

            // set model but no re-render
            const promise = component.set('firstName', 'Ryan', false);
            // when this promise IS finally resolved, set the flag to true
            promise.then((response) => { backendResponse = response });
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
            expect(() => { component.set('notARealModel', 'Ryan', false) }).toThrow('Invalid model name "notARealModel"');
        });
    });

    describe('Proxy wrapper', () => {
        const makeDummyComponent = (): { proxy: Component, backend: MockBackend } => {
            const { backend, component} = makeTestComponent();
            return {
                proxy: proxifyComponent(component),
                backend
            }
        }

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
            await (new Promise(resolve => setTimeout(resolve, 5)));
            expect(backend.actions).toHaveLength(1);
            expect(backend.actions[0].name).toBe('save');
            expect(backend.actions[0].args).toEqual({ foo: 'bar', secondArg: 'secondValue' });
        });
    });
});
