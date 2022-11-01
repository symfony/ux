export default class {
    private hooks: Map<string, Array<(...args: any[]) => void>>;

    constructor() {
        this.hooks = new Map();
    }

    register(hookName: string, callback: () => void): void {
        const hooks = this.hooks.get(hookName) || [];
        hooks.push(callback);
        this.hooks.set(hookName, hooks);
    }

    unregister(hookName: string, callback: () => void): void {
        const hooks = this.hooks.get(hookName) || [];

        const index = hooks.indexOf(callback);
        if (index === -1) {
            return;
        }

        hooks.splice(index, 1);
        this.hooks.set(hookName, hooks);
    }

    triggerHook(hookName: string, ...args: any[]): void {
        const hooks = this.hooks.get(hookName) || [];
        hooks.forEach((callback) => {
            callback(...args);
        });
    }
}
