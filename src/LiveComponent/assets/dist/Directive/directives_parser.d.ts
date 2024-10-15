export interface DirectiveModifier {
    name: string;
    value: string | null;
}
export interface Directive {
    action: string;
    args: string[];
    modifiers: DirectiveModifier[];
    getString: () => string;
}
export interface ElementDirectives {
    element: HTMLElement | SVGElement;
    directives: Directive[];
}
export declare function parseDirectives(content: string | null): Directive[];
