import type { Component } from '../../live_controller';
import type { PluginInterface } from './PluginInterface';
export default class ErrorPlugin implements PluginInterface {
    static errorAttribute: string;
    static isErrorAttribute: string;
    static supportedActions: {
        show: string;
        hide: string;
        addClass: string;
        removeClass: string;
        addAttribute: string;
        removeAttribute: string;
    };
    attachToComponent(component: Component): void;
    showErrors(component: Component): void;
    hideErrors(component: Component): void;
    private handleErrorToggle;
    private handleErrorDirective;
    private getErrorDirectives;
    private parseErrorAction;
    private showElement;
    private hideElement;
    private addClass;
    private removeClass;
    private addAttributes;
    private removeAttributes;
}
