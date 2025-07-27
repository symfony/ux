import { Component } from 'vue';
import { ComponentCollection } from './components.js';

declare global {
    function resolveVueComponent(name: string): Component;
    interface Window {
        resolveVueComponent(name: string): Component;
    }
}
declare function registerVueControllerComponents(vueControllers?: ComponentCollection): void;

export { registerVueControllerComponents };
