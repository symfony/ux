import Component from '../src/Component';
import ComponentRegistry from '../src/ComponentRegistry';
import BackendRequest from '../src/Backend/BackendRequest';
import { BackendInterface } from '../src/Backend/Backend';
import { Response } from 'node-fetch';
import { StandardElementDriver } from '../src/Component/ElementDriver';

const createComponent = (element: HTMLElement, name = 'foo-component'): Component => {
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
        name,
        {},
        [],
        () => [],
        null,
        null,
        backend,
        new StandardElementDriver(),
    );
};

describe('ComponentRegistry', () => {
    it('can add and retrieve components', async () => {
        const registry = new ComponentRegistry();

        const element1 = document.createElement('div');
        const component1 = createComponent(element1);
        const element2 = document.createElement('div');
        const component2 = createComponent(element2);

        registry.registerComponent(element1, component1);
        registry.registerComponent(element2, component2);

        const promise1 = registry.getComponent(element1);
        const promise2 = registry.getComponent(element2);
        await expect(promise1).resolves.toBe(component1);
        await expect(promise2).resolves.toBe(component2);
    });

    it('fails if component is not found soon', async () => {
        const registry = new ComponentRegistry();

        const element1 = document.createElement('div');
        const promise = registry.getComponent(element1);
        expect.assertions(1);
        await expect(promise).rejects.toEqual(new Error('Component not found for element <div></div>'));
    });

    it('can find components in the simple case', () => {
        const registry = new ComponentRegistry();

        const element1 = document.createElement('div');
        const component1 = createComponent(element1);
        const element2 = document.createElement('div');
        const component2 = createComponent(element2);

        registry.registerComponent(element1, component1);
        registry.registerComponent(element2, component2);

        const otherComponent = createComponent(document.createElement('div'));

        const components = registry.findComponents(otherComponent, false, null);
        expect(components).toEqual([component1, component2]);
    });

    it('can find components with only parents', () => {
        const registry = new ComponentRegistry();

        const element1 = document.createElement('div');
        const component1 = createComponent(element1);
        const element2 = document.createElement('div');
        const component2 = createComponent(element2);
        const element3 = document.createElement('div');
        const component3 = createComponent(element3);

        // put component 2 inside component 1
        element1.appendChild(element2);

        registry.registerComponent(element1, component1);
        registry.registerComponent(element2, component2);
        registry.registerComponent(element3, component3);

        const components = registry.findComponents(component2, true, null);
        expect(components).toEqual([component1]);
    });

    it('can find components by name', () => {
        const registry = new ComponentRegistry();

        const element1 = document.createElement('div');
        const component1 = createComponent(element1, 'component-type1');
        const element2 = document.createElement('div');
        const component2 = createComponent(element2, 'component-type1');
        const element3 = document.createElement('div');
        const component3 = createComponent(element3, 'component-type2');

        registry.registerComponent(element1, component1);
        registry.registerComponent(element2, component2);
        registry.registerComponent(element3, component3);

        const otherComponent = createComponent(document.createElement('div'));

        const components = registry.findComponents(otherComponent, false, 'component-type1');
        expect(components).toEqual([component1, component2]);
    });

    it('will find components including itself', () => {
        const registry = new ComponentRegistry();

        const element1 = document.createElement('div');
        const component1 = createComponent(element1, 'component-type1');
        const element2 = document.createElement('div');
        const component2 = createComponent(element2, 'component-type1');

        registry.registerComponent(element1, component1);
        registry.registerComponent(element2, component2);

        const components = registry.findComponents(component2, false, null);
        expect(components).toEqual([component1, component2]);
    });
});
