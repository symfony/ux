/**
 * Minimal ambient types for the optional `@symfony/ux-live-component` peer.
 *
 * Declared locally so `tsc` can type-check the live-upload bridge without the
 * package installed (it is an optional peer dependency). Mirrors the public
 * JS API documented at symfony.com/bundles/ux-live-component.
 */
declare module '@symfony/ux-live-component' {
    export interface Component {
        action(name: string, args?: Record<string, unknown>, debounce?: number | boolean): Promise<unknown>;
        set(model: string, value: unknown, reRender?: boolean): Promise<unknown>;
        render(): Promise<unknown>;
    }

    export function getComponent(element: HTMLElement): Promise<Component>;
}
