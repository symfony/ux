import {
    type Directive,
    type DirectiveModifier,
    parseDirectives
} from '../../Directive/directives_parser';
import { elementBelongsToThisComponent } from '../../dom_utils';
import { combineSpacedArray } from '../../string_utils';
import type BackendRequest from '../../Backend/BackendRequest';
import type Component from '../../Component';
import type { PluginInterface } from './PluginInterface';

interface ElementLoadingDirectives {
    element: HTMLElement|SVGElement,
    directives: Directive[]
}

export default class implements PluginInterface {
    attachToComponent(component: Component): void {
        component.on('loading.state:started', (element: HTMLElement, request: BackendRequest) => {
            this.startLoading(component, element, request);
        });
        component.on('loading.state:finished', (element: HTMLElement) => {
            this.finishLoading(component, element);
        });
        // hide "loading" elements to begin with
        // This is done with CSS, but only for the most basic cases
        this.finishLoading(component, component.element);
    }

    startLoading(component: Component, targetElement: HTMLElement|SVGElement, backendRequest: BackendRequest): void {
        this.handleLoadingToggle(component, true, targetElement, backendRequest);
    }

    finishLoading(component: Component, targetElement: HTMLElement|SVGElement): void {
        this.handleLoadingToggle(component,false, targetElement, null);
    }

    private handleLoadingToggle(component: Component, isLoading: boolean, targetElement: HTMLElement|SVGElement, backendRequest: BackendRequest|null) {
        if (isLoading) {
            this.addAttributes(targetElement, ['busy']);
        } else {
            this.removeAttributes(targetElement, ['busy']);
        }

        this.getLoadingDirectives(component, targetElement).forEach(({ element, directives }) => {
            // so we can track, at any point, if an element is in a "loading" state
            if (isLoading) {
                this.addAttributes(element, ['data-live-is-loading']);
            } else {
                this.removeAttributes(element, ['data-live-is-loading']);
            }

            directives.forEach((directive) => {
                this.handleLoadingDirective(element, isLoading, directive, backendRequest)
            });
        });
    }

    private handleLoadingDirective(element: HTMLElement|SVGElement, isLoading: boolean, directive: Directive, backendRequest: BackendRequest|null) {
        const finalAction = parseLoadingAction(directive.action, isLoading);

        const targetedActions: string[] = [];
        const targetedModels: string[] = [];
        let delay = 0;

        const validModifiers: Map<string, (modifier: DirectiveModifier) => void> = new Map();
        validModifiers.set('delay', (modifier: DirectiveModifier) => {
            if (!isLoading) {
                return;
            }
            delay = modifier.value ? Number.parseInt(modifier.value) : 200;
        });
        validModifiers.set('action', (modifier: DirectiveModifier) => {
            if (!modifier.value) {
                throw new Error(`The "action" in data-loading must have an action name - e.g. action(foo). It's missing for "${directive.getString()}"`);
            }
            targetedActions.push(modifier.value);
        });
        validModifiers.set('model', (modifier: DirectiveModifier) => {
            if (!modifier.value) {
                throw new Error(`The "model" in data-loading must have an action name - e.g. model(foo). It's missing for "${directive.getString()}"`);
            }
            targetedModels.push(modifier.value);
        });

        directive.modifiers.forEach((modifier) => {
            if (validModifiers.has(modifier.name)) {
                // variable is entirely to make ts happy
                const callable = validModifiers.get(modifier.name) ?? (() => {});
                callable(modifier);

                return;
            }

            throw new Error(`Unknown modifier "${modifier.name}" used in data-loading="${directive.getString()}". Available modifiers are: ${Array.from(validModifiers.keys()).join(', ')}.`)
        });

        // if loading is being activated + action modifier, only apply if the action is on the request
        if (isLoading && targetedActions.length > 0 && backendRequest && !backendRequest.containsOneOfActions(targetedActions)) {
            return;
        }

        // if loading is being activated + model modifier, only apply if the model is modified
        if (isLoading && targetedModels.length > 0 && backendRequest && !backendRequest.areAnyModelsUpdated(targetedModels)) {
            return;
        }

        let loadingDirective: (() => void);

        switch (finalAction) {
            case 'show':
                loadingDirective = () => this.showElement(element);
                break;

            case 'hide':
                loadingDirective = () => this.hideElement(element);
                break;

            case 'addClass':
                loadingDirective = () => this.addClass(element, directive.args);
                break;

            case 'removeClass':
                loadingDirective = () => this.removeClass(element, directive.args);
                break;

            case 'addAttribute':
                loadingDirective = () => this.addAttributes(element, directive.args);
                break;

            case 'removeAttribute':
                loadingDirective = () => this.removeAttributes(element, directive.args);
                break;

            default:
                throw new Error(`Unknown data-loading action "${finalAction}"`);
        }

        if (delay) {
            window.setTimeout(() => {
                if (backendRequest && !backendRequest.isResolved) {
                    loadingDirective();
                }
            }, delay);

            return;
        }

        loadingDirective();
    }

    getLoadingDirectives(component: Component, element: HTMLElement|SVGElement) {
        const loadingDirectives: ElementLoadingDirectives[] = [];
        let matchingElements = [...Array.from(element.querySelectorAll('[data-loading]'))];

        // ignore elements which are inside a nested "live" component
        matchingElements = matchingElements.filter((elt) => elementBelongsToThisComponent(elt, component));

        // querySelectorAll doesn't include the element itself
        if (element.hasAttribute('data-loading')) {
            matchingElements = [element, ...matchingElements];
        }

        matchingElements.forEach((element => {
            if (!(element instanceof HTMLElement) && !(element instanceof SVGElement)) {
                throw new Error('Invalid Element Type');
            }

            // use "show" if the attribute is empty
            const directives = parseDirectives(element.dataset.loading || 'show');

            loadingDirectives.push({
                element,
                directives,
            });
        }));

        return loadingDirectives;
    }

    private showElement(element: HTMLElement|SVGElement) {
        element.style.display = 'revert';
    }

    private hideElement(element: HTMLElement|SVGElement) {
        element.style.display = 'none';
    }

    private addClass(element: HTMLElement|SVGElement, classes: string[]) {
        element.classList.add(...combineSpacedArray(classes));
    }

    private removeClass(element: HTMLElement|SVGElement, classes: string[]) {
        element.classList.remove(...combineSpacedArray(classes));
        if (element.classList.length === 0) {
            // remove empty class="" to avoid morphdom "diff" problem
            element.removeAttribute('class');
        }
    }

    private addAttributes(element: Element, attributes: string[]) {
        attributes.forEach((attribute) => {
            element.setAttribute(attribute, '');
        })
    }

    private removeAttributes(element: Element, attributes: string[]) {
        attributes.forEach((attribute) => {
            element.removeAttribute(attribute);
        })
    }
}

const parseLoadingAction = (action: string, isLoading: boolean) => {
    switch (action) {
        case 'show':
            return isLoading ? 'show' : 'hide';
        case 'hide':
            return isLoading ? 'hide' : 'show';
        case 'addClass':
            return isLoading ? 'addClass' : 'removeClass';
        case 'removeClass':
            return isLoading ? 'removeClass' : 'addClass';
        case 'addAttribute':
            return isLoading ? 'addAttribute' : 'removeAttribute';
        case 'removeAttribute':
            return isLoading ? 'removeAttribute' : 'addAttribute';
    }

    throw new Error(`Unknown data-loading action "${action}"`);
}

