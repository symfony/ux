import Component, { proxifyComponent } from '../../src/Component';
import {BackendAction, BackendInterface} from '../../src/Backend/Backend';
import { StandardElementDriver } from '../../src/Component/ElementDriver';
import BackendRequest from '../../src/Backend/BackendRequest';
import { Response } from 'node-fetch';
import { waitFor } from '@testing-library/dom';
import BackendResponse from '../../src/Backend/BackendResponse';
import { dataToJsonAttribute } from '../tools';

class ComponentTest {
    component: Component;
    backend: BackendInterface;

    calledActions: BackendAction[] = [];
    mockedResponses: string[] = [];
    currentMockedResponse = 0;

    constructor(props: any) {
        // eslint-disable-next-line @typescript-eslint/no-this-alias
        const componentTest = this;
        this.backend = {
            makeRequest(props: any, actions: BackendAction[]): BackendRequest {
                componentTest.calledActions = actions;

                if (!componentTest.mockedResponses[componentTest.currentMockedResponse]) {
                    throw new Error(`No mocked response for request #${componentTest.currentMockedResponse}`);
                }
                const html = componentTest.mockedResponses[componentTest.currentMockedResponse];
                componentTest.currentMockedResponse++;

                return new BackendRequest(
                    // @ts-ignore Response doesn't quite match the underlying interface
                    new Promise((resolve) => resolve(new Response(html, {
                        headers: {
                            'Content-Type': 'application/vnd.live-component+html',
                        }
                    }))),
                    [],
                    [],
                );
            },
        };

        this.component = new Component(
            document.createElement('div'),
            'test-component',
            props,
            [],
            () => [],
            null,
            null,
            this.backend,
            new StandardElementDriver(),
        );
    }

    addMockResponse(html: string): void {
        this.mockedResponses.push(html);
    }

    getPendingResponseCount(): number {
        return this.mockedResponses.length - this.currentMockedResponse;
    }
}

let currentTest: ComponentTest|null = null;
const createTest = (props: any): ComponentTest => {
    return currentTest = new ComponentTest(props);
};

describe('Component class', () => {
    afterEach(() => {
        if (currentTest) {
            if (currentTest.getPendingResponseCount() > 0) {
                throw new Error(`Test finished with ${currentTest.getPendingResponseCount()} pending responses`);
            }
        }
        currentTest = null;
    });

    describe('set() method', () => {
        it('returns a Promise that eventually resolves', async () => {
            const test = createTest({
                firstName: '',
            });
            test.addMockResponse('<div data-live-props-value="{}"></div>');

            let backendResponse: BackendResponse|null = null;

            // set model but no re-render
            const promise = test.component.set('firstName', 'Ryan', false);
            // when this promise IS finally resolved, set the flag to true
            promise.then((response) => backendResponse = response);
            // even if we wait for a potential response to resolve, it won't resolve the promise yet
            await (new Promise(resolve => setTimeout(resolve, 10)));
            expect(backendResponse).toBeNull();

            // set model WITH re-render
            test.component.set('firstName', 'Kevin', true);
            // it's still not *instantly* resolved
            expect(backendResponse).toBeNull();
            await waitFor(() => expect(backendResponse).not.toBeNull());
            // @ts-ignore
            expect(await backendResponse?.getBody()).toEqual('<div data-live-props-value="{}"></div>');
        });

        it('triggers the model:set hook', async () => {
            const test = createTest({
                firstName: '',
            });

            let hookCalled = false;
            let actualModel: string|null = null;
            let actualValue: string|null = null;
            let actualComponent: Component|null = null;
            test.component.on('model:set', (model, value, theComponent) => {
                hookCalled = true;
                actualModel = model;
                actualValue = value;
                actualComponent = theComponent;
            });
            test.component.set('firstName', 'Ryan', false);
            expect(hookCalled).toBe(true);
            expect(actualModel).toBe('firstName');
            expect(actualValue).toBe('Ryan');
            expect(actualComponent).toBe(test.component);
        });
    });

    describe('render() method', () => {
        it('triggers model:set hook if a model changes on the server', async () => {
            const test = createTest({
                firstName: '',
                product: {
                    id: 5,
                    name: 'cool stuff',
                },
                lastName: '',
            });

            const newProps = {
                firstName: 'Ryan',
                lastName: 'Bond',
                product: {
                    id: 5,
                    name: 'purple stuff',
                },
            };
            test.addMockResponse(`<div data-controller="live" data-live-props-value="${dataToJsonAttribute(newProps)}"></div>`);

            const promise = test.component.render();

            // During the request, change lastName to make it a "dirty change"
            // The new value from the server is effectively ignored, and so no
            // model:set hook should be triggered
            test.component.set('lastName', 'dirty change', false);

            const hookModels: string[] = [];
            test.component.on('model:set', (model) => {
                hookModels.push(model);
            });

            await promise;

            expect(hookModels).toEqual(['firstName', 'product.name']);
        });
    });

    describe('Proxy wrapper', () => {
        const makeDummyComponent = (): { proxy: Component, test: ComponentTest } => {
            const test = createTest({
                firstName: '',
            });

            return {
                proxy: proxifyComponent(test.component),
                test,
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
            const { proxy, test } = makeDummyComponent();
            // @ts-ignore
            proxy.save({ foo: 'bar', secondArg: 'secondValue' });

            // ugly: the action delays for 0ms, so we just need a TINy
            // delay here before we start asserting
            await (new Promise(resolve => setTimeout(resolve, 5)));
            expect(test.calledActions).toHaveLength(1);
            expect(test.calledActions[0].name).toBe('save');
            expect(test.calledActions[0].args).toEqual({ foo: 'bar', secondArg: 'secondValue' });
        });
    });
});
