/// <reference types="webpack-env" />
import type { SvelteComponent } from 'svelte';
declare global {
    function resolveSvelteComponent(name: string): typeof SvelteComponent<any>;
    interface Window {
        resolveSvelteComponent(name: string): typeof SvelteComponent<any>;
    }
}
export declare function registerSvelteControllerComponents(context: __WebpackModuleApi.RequireContext): void;
