import { Response } from 'node-fetch';
import { describe, expect, it } from 'vitest';
import BackendRequest from '../../../../src/Backend/BackendRequest';
import Component from '../../../../src/Component';
import LoadingPlugin from '../../../../src/Component/plugins/LoadingPlugin';
import { noopElementDriver } from '../../../tools';

const makeRequest = (): BackendRequest =>
    new BackendRequest(
        // @ts-expect-error Response doesn't quite match the underlying interface
        new Promise((resolve) => resolve(new Response(''))),
        [],
        []
    );

const makeComponent = (html: string): Component => {
    document.body.innerHTML = html;

    return new Component(
        document.body.firstElementChild as HTMLElement,
        'testing-component',
        {},
        [],
        null,
        { makeRequest },
        new noopElementDriver()
    );
};

const startLoading = (component: Component): void => {
    new LoadingPlugin().startLoading(component, component.element, makeRequest());
};

describe('LoadingPlugin', () => {
    it('rejects an unknown modifier and lists the available ones', () => {
        const component = makeComponent('<div data-controller="live"><span data-loading="nope(5)|show"></span></div>');

        expect(() => startLoading(component)).toThrow(
            'Unknown modifier "nope" used in data-loading="nope(5)|show". Available modifiers are: delay, action, model.'
        );
    });

    it('rejects an "action" modifier without a value', () => {
        const component = makeComponent('<div data-controller="live"><span data-loading="action|show"></span></div>');

        expect(() => startLoading(component)).toThrow(
            'The "action" in data-loading must have an action name - e.g. action(foo). It\'s missing for "action|show"'
        );
    });

    it('rejects a "model" modifier without a value', () => {
        const component = makeComponent('<div data-controller="live"><span data-loading="model|show"></span></div>');

        expect(() => startLoading(component)).toThrow(
            'The "model" in data-loading must have an action name - e.g. model(foo). It\'s missing for "model|show"'
        );
    });

    it('rejects an unknown loading action', () => {
        const component = makeComponent('<div data-controller="live"><span data-loading="doTheThing"></span></div>');

        expect(() => startLoading(component)).toThrow('Unknown data-loading action "doTheThing"');
    });

    it('re-reads the directives when the attribute changes', () => {
        const component = makeComponent(
            '<div data-controller="live"><span data-loading="addClass(busy)"></span></div>'
        );
        const element = component.element.querySelector('span') as HTMLElement;

        startLoading(component);
        expect(element.classList.contains('busy')).toBe(true);

        element.dataset.loading = 'addClass(working)';
        startLoading(component);

        expect(element.classList.contains('working')).toBe(true);
    });
});
