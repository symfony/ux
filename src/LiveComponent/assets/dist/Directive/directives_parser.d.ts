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
export declare function parseDirectives(content: string | null): Directive[];
