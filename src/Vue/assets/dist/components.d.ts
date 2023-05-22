import type { Component } from 'vue';
export interface ComponentCollection {
    [key: string]: Component;
}
export declare const components: ComponentCollection;
