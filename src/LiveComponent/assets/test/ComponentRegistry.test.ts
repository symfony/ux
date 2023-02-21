import Component from '../src/Component';
import ComponentRegistry from '../src/ComponentRegistry';
import BackendRequest from '../src/BackendRequest';
import { BackendInterface } from '../src/Backend';
import { Response } from 'node-fetch';
import { StandardElementDriver } from '../src/Component/ElementDriver';

const createComponent = (element: HTMLElement): Component => {
    const backend: BackendInterface = {
        makeRequest(): BackendRequest {
            return new BackendRequest(
                // @ts-ignore Response doesn't quite match the underlying interface
                new Promise((resolve) => resolve(new Response(''))),
                [],
                []
            )
        }
    }

    return new Component(
        element,
        {},
        null,
        null,
        backend,
        new StandardElementDriver(),
    );
};

describe('ComponentRegistry', () => {
    it('can add and retrieve components', async () => {
        const element1 = document.createElement('div');
        const component1 = createComponent(element1);
        const element2 = document.createElement('div');
        const component2 = createComponent(element2);

        ComponentRegistry.registerComponent(element1, component1);
        ComponentRegistry.registerComponent(element2, component2);

        const promise1 = ComponentRegistry.getComponent(element1);
        const promise2 = ComponentRegistry.getComponent(element2);
        await expect(promise1).resolves.toBe(component1);
        await expect(promise2).resolves.toBe(component2);
    });

    it('fails if component is not found soon', async () => {
        const element1 = document.createElement('div');
        const promise = ComponentRegistry.getComponent(element1);
        expect.assertions(1);
        await expect(promise).rejects.toEqual(new Error('Component not found for element <div></div>'));
    });
});
