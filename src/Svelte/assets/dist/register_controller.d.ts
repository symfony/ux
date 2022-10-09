/// <reference types="webpack-env" />
import type { SvelteComponent } from 'svelte';
declare global {
    function resolveSvelteComponent(name: string): typeof SvelteComponent;
    interface Window {
        resolveSvelteComponent(name: string): typeof SvelteComponent;
    }
}
export declare function registerSvelteControllerComponents(context: __WebpackModuleApi.RequireContext): void;
