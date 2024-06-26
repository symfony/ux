import type { ComponentHookName, ComponentHookCallback } from './Component';
export default class {
    private hooks;
    register<T extends string | ComponentHookName = ComponentHookName>(hookName: T, callback: ComponentHookCallback<T>): void;
    unregister<T extends string | ComponentHookName = ComponentHookName>(hookName: T, callback: ComponentHookCallback<T>): void;
    triggerHook<T extends string | ComponentHookName = ComponentHookName>(hookName: T, ...args: Parameters<ComponentHookCallback<T>>): void;
}
