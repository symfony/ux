import type { ComponentHookName, ComponentHookCallback } from './Component';

export default class {
    private hooks: Map<ComponentHookName | string, Array<(...args: any[]) => void>> = new Map();

    register<T extends string | ComponentHookName = ComponentHookName>(
        hookName: T,
        callback: ComponentHookCallback<T>
    ): void {
        const hooks = this.hooks.get(hookName) || [];
        hooks.push(callback);
        this.hooks.set(hookName, hooks);
    }

    unregister<T extends string | ComponentHookName = ComponentHookName>(
        hookName: T,
        callback: ComponentHookCallback<T>
    ): void {
        const hooks = this.hooks.get(hookName) || [];
        const index = hooks.indexOf(callback);
        if (index === -1) {
            return;
        }

        hooks.splice(index, 1);
        this.hooks.set(hookName, hooks);
    }

    triggerHook<T extends string | ComponentHookName = ComponentHookName>(
        hookName: T,
        ...args: Parameters<ComponentHookCallback<T>>
    ): void {
        const hooks = this.hooks.get(hookName) || [];
        hooks.forEach((callback) => callback(...args));
    }
}
