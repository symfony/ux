import type { SvelteComponent } from 'svelte';
import { type ComponentCollection } from './components.js';
declare global {
    function resolveSvelteComponent(name: string): typeof SvelteComponent<any>;
    interface Window {
        resolveSvelteComponent(name: string): typeof SvelteComponent<any>;
    }
}
export declare function registerSvelteControllerComponents(svelteComponents?: ComponentCollection): void;
