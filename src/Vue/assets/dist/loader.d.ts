import type { Component } from 'vue';
import { type ComponentCollection } from './components.js';
declare global {
    function resolveVueComponent(name: string): Component;
    interface Window {
        resolveVueComponent(name: string): Component;
    }
}
export declare function registerVueControllerComponents(vueControllers?: ComponentCollection): void;
