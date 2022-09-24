import Component, {createComponent} from "../../src/Component";
import {BackendAction, BackendInterface} from "../../src/Backend";
import {
    DataModelElementResolver
} from "../../src/Component/ModelElementResolver";
import BackendRequest from "../../src/BackendRequest";
import { Response } from 'node-fetch';

describe('Component class', () => {
    describe('Proxy wrapper', () => {
        interface MockBackend extends BackendInterface {
            actions: BackendAction[],
        }

        const makeDummyComponent = (): { proxy: Component, backend: MockBackend } => {
            const backend: MockBackend = {
                actions: [],
                makeRequest(data: any, actions: BackendAction[], updatedModels: string[]): BackendRequest {
                    this.actions = actions;

                    return new BackendRequest(
                        // @ts-ignore Response doesn't quite match the underlying interface
                        new Promise((resolve) => resolve(new Response('<div data-live-data-value="{}"></div>'))),
                        [],
                        []
                    )
                }
            }

            return {
                proxy: createComponent(
                    document.createElement('div'),
                    {firstName: ''},
                    backend,
                    new DataModelElementResolver()
                ),
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
            expect(proxy.get('firstName')).toBe('Ryan');
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
    })
});
