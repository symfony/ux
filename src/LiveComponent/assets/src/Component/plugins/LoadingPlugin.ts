import {
    Directive,
    DirectiveModifier,
    parseDirectives
} from '../../directives_parser';
import { combineSpacedArray}  from '../../string_utils';
import BackendRequest from '../../BackendRequest';
import Component from '../../Component';
import { PluginInterface } from './PluginInterface';

interface ElementLoadingDirectives {
    element: HTMLElement|SVGElement,
    directives: Directive[]
}

export default class implements PluginInterface {
    attachToComponent(component: Component): void {
        component.on('loading.state:started', (element: HTMLElement, request: BackendRequest) => {
            this.startLoading(element, request);
        });
        component.on('loading.state:finished', (element: HTMLElement) => {
            this.finishLoading(element);
        });
        // hide "loading" elements to begin with
        // This is done with CSS, but only for the most basic cases
        this.finishLoading(component.element);
    }

    startLoading(targetElement: HTMLElement|SVGElement, backendRequest: BackendRequest): void {
        this.handleLoadingToggle(true, targetElement, backendRequest);
    }

    finishLoading(targetElement: HTMLElement|SVGElement): void {
        this.handleLoadingToggle(false, targetElement, null);
    }

    private handleLoadingToggle(isLoading: boolean, targetElement: HTMLElement|SVGElement, backendRequest: BackendRequest|null) {
        if (isLoading) {
            this.addAttributes(targetElement, ['busy']);
        } else {
            this.removeAttributes(targetElement, ['busy']);
        }

        this.getLoadingDirectives(targetElement).forEach(({ element, directives }) => {
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
            // if loading has *stopped*, the delay modifier has no effect
            if (!isLoading) {
                return;
            }

            delay = modifier.value ? parseInt(modifier.value) : 200;
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
                loadingDirective = () => {
                    this.showElement(element)
                };
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

    getLoadingDirectives(element: HTMLElement|SVGElement) {
        const loadingDirectives: ElementLoadingDirectives[] = [];

        element.querySelectorAll('[data-loading]').forEach((element => {
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
        element.style.display = 'inline-block';
    }

    private hideElement(element: HTMLElement|SVGElement) {
        element.style.display = 'none';
    }

    private addClass(element: HTMLElement|SVGElement, classes: string[]) {
        element.classList.add(...combineSpacedArray(classes));
    }

    private removeClass(element: HTMLElement|SVGElement, classes: string[]) {
        element.classList.remove(...combineSpacedArray(classes));

        // remove empty class="" to avoid morphdom "diff" problem
        if (element.classList.length === 0) {
            this.removeAttributes(element, ['class']);
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

const parseLoadingAction = function(action: string, isLoading: boolean) {
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

