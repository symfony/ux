import type { SvelteComponent } from 'svelte';
export interface ComponentCollection {
    [key: string]: SvelteComponent;
}
export declare const components: ComponentCollection;
