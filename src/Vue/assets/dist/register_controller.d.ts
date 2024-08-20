import type { Component } from 'vue';
declare global {
    function resolveVueComponent(name: string): Component;
    interface Window {
        resolveVueComponent(name: string): Component;
    }
}
export declare function registerVueControllerComponents(context: __WebpackModuleApi.RequireContext): void;
