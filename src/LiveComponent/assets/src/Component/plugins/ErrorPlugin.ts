import { type Directive, parseDirectives, type ElementDirectives } from '../../Directive/directives_parser';
import { elementBelongsToThisComponent } from '../../dom_utils';
import type { Component } from '../../live_controller';
import { combineSpacedArray } from '../../string_utils';
import type { PluginInterface } from './PluginInterface';

export default class ErrorPlugin implements PluginInterface {
    /**
     * The attribute name used to define the error directives.
     */
    static errorAttribute = 'data-error';

    /**
     * The attribute name used to define the error state of a component (used on the root element of the component).
     */
    static isErrorAttribute = 'data-live-is-error';

    /**
     * The supported actions for this plugin.
     */
    static supportedActions = {
        show: 'show',
        hide: 'hide',
        addClass: 'addClass',
        removeClass: 'removeClass',
        addAttribute: 'addAttribute',
        removeAttribute: 'removeAttribute',
    };

    attachToComponent(component: Component): void {
        component.on('response:error', () => {
            this.showErrors(component);
        });
        component.on('request:started', () => {
            this.hideErrors(component);
        });

        // Hide the elements if they aren't already
        // This should be done through CSS directly, but this is a fallback
        this.hideErrors(component);
    }

    /**
     * Shows the error state of the component.
     */
    showErrors(component: Component)
    {
        this.handleErrorToggle(component, true, component.element);
    }

    /**
     * Hides the error state of the component.
     */
    hideErrors(component: Component)
    {
        this.handleErrorToggle(component, false, component.element);
    }

    /**
     * Handles the error state of the component.
     */
    private handleErrorToggle(component: Component, isError: boolean, targetElement: HTMLElement|SVGElement) {
        this.getErrorDirectives(component, targetElement).forEach(({ element, directives }) => {
            // the component's root element will receive this attribute is it's in an error state
            if (isError) {
                this.addAttributes(element, [ErrorPlugin.isErrorAttribute]);
            } else {
                this.removeAttributes(element, [ErrorPlugin.isErrorAttribute]);
            }

            directives.forEach((directive) => {
                this.handleErrorDirective(element, isError, directive)
            });
        });
    }

    /**
     * Handles an error directive.
     */
    private handleErrorDirective(element: HTMLElement|SVGElement, isError: boolean, directive: Directive) {
        const finalAction = this.parseErrorAction(directive.action, isError);

        switch (finalAction) {
            case ErrorPlugin.supportedActions.show:
                this.showElement(element);

                break;

            case ErrorPlugin.supportedActions.hide:
                this.hideElement(element);

                break;

            case ErrorPlugin.supportedActions.addClass:
                this.addClass(element, directive.args);

                break;

            case ErrorPlugin.supportedActions.removeClass:
                this.removeClass(element, directive.args);

                break;

            case ErrorPlugin.supportedActions.addAttribute:
                this.addAttributes(element, directive.args);

                break;

            case ErrorPlugin.supportedActions.removeAttribute:
                this.removeAttributes(element, directive.args);

                break;

            default:
                throw new Error(`Unknown ${ErrorPlugin.errorAttribute} action "${finalAction}". Supported actions are: ${Object.values(`"${ErrorPlugin.supportedActions}"`).join(', ')}.`);
        }
    }

    /**
     * Returns an array of `ElementDirectives` for the given component and element.
     */
    private getErrorDirectives(component: Component, element: HTMLElement|SVGElement) {
        const errorDirectives: ElementDirectives[] = [];
        let matchingElements = [...Array.from(element.querySelectorAll(`[${ErrorPlugin.errorAttribute}]`))];

        // ignore elements which are inside a nested "live" component
        matchingElements = matchingElements.filter((elt) => elementBelongsToThisComponent(elt, component));

        // querySelectorAll doesn't include the element itself
        if (element.hasAttribute(ErrorPlugin.errorAttribute)) {
            matchingElements = [element, ...matchingElements];
        }

        matchingElements.forEach((element => {
            if (!(element instanceof HTMLElement) && !(element instanceof SVGElement)) {
                throw new Error(`Element "${element.nodeName}" is not supported for ${ErrorPlugin.errorAttribute} directives. Only HTMLElement and SVGElement are supported.`);
            }

            // use "show" if the attribute is empty
            const directives = parseDirectives(element.getAttribute(ErrorPlugin.errorAttribute) || 'show');

            directives.forEach((directive) => {
                if (directive.modifiers.length > 0) {
                    // TODO: Use the `modifier.toString()` method once it's implemented
                    throw new Error(`Modifiers are not supported for ${ErrorPlugin.errorAttribute} directives, but the following were found: "{${directive.modifiers.map((modifier) => `${modifier.name}: ${modifier.value}}`).join(', ')}" for element with tag "${element.nodeName}".`);
                }
            });

            errorDirectives.push({
                element,
                directives,
            });
        }));

        return errorDirectives;
    }

    /**
     * Parses the action and returns the opposite action if the element is not in an error state.
     */
    private parseErrorAction(action: string, isError: boolean) {
        switch (action) {
            case ErrorPlugin.supportedActions.show:
                return isError ? 'show' : 'hide';

            case ErrorPlugin.supportedActions.hide:
                return isError ? 'hide' : 'show';

            case ErrorPlugin.supportedActions.addClass:
                return isError ? 'addClass' : 'removeClass';

            case ErrorPlugin.supportedActions.removeClass:
                return isError ? 'removeClass' : 'addClass';

            case ErrorPlugin.supportedActions.addAttribute:
                return isError ? 'addAttribute' : 'removeAttribute';

            case ErrorPlugin.supportedActions.removeAttribute:
                return isError ? 'removeAttribute' : 'addAttribute';

            default:
                throw new Error(`Unknown ${ErrorPlugin.errorAttribute} action "${action}". Supported actions are: ${Object.values(`"${ErrorPlugin.supportedActions}"`).join(', ')}.`);

        }
    }

    // Utils
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
