import { Directive } from '../../Directive/directives_parser';
import BackendRequest from '../../Backend/BackendRequest';
import Component from '../../Component';
import { PluginInterface } from './PluginInterface';
interface ElementLoadingDirectives {
    element: HTMLElement | SVGElement;
    directives: Directive[];
}
export default class implements PluginInterface {
    attachToComponent(component: Component): void;
    startLoading(targetElement: HTMLElement | SVGElement, backendRequest: BackendRequest): void;
    finishLoading(targetElement: HTMLElement | SVGElement): void;
    private handleLoadingToggle;
    private handleLoadingDirective;
    getLoadingDirectives(element: HTMLElement | SVGElement): ElementLoadingDirectives[];
    private showElement;
    private hideElement;
    private addClass;
    private removeClass;
    private addAttributes;
    private removeAttributes;
}
export {};
