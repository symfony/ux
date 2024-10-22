import { type ElementDirectives } from '../../Directive/directives_parser';
import type BackendRequest from '../../Backend/BackendRequest';
import type Component from '../../Component';
import type { PluginInterface } from './PluginInterface';
export default class implements PluginInterface {
    attachToComponent(component: Component): void;
    startLoading(component: Component, targetElement: HTMLElement | SVGElement, backendRequest: BackendRequest): void;
    finishLoading(component: Component, targetElement: HTMLElement | SVGElement): void;
    private handleLoadingToggle;
    private handleLoadingDirective;
    getLoadingDirectives(component: Component, element: HTMLElement | SVGElement): ElementDirectives[];
    private showElement;
    private hideElement;
    private addClass;
    private removeClass;
    private addAttributes;
    private removeAttributes;
}
