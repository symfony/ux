import { SvelteComponent } from 'svelte';
import { ComponentCollection } from './components.js';

declare global {
    function resolveSvelteComponent(name: string): typeof SvelteComponent<any>;
    interface Window {
        resolveSvelteComponent(name: string): typeof SvelteComponent<any>;
    }
}
declare function registerSvelteControllerComponents(svelteComponents?: ComponentCollection): void;

export { registerSvelteControllerComponents };
