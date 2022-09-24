export default class {
    private hooks: Map<string, Array<(...args: any[]) => void>>;

    constructor() {
        this.hooks = new Map();
    }

    /**
     * Add a named hook to the component. Available hooks are:
     *
     *      * request.rendered
     */
    register(hookName: string, callback: () => void): void {
        const hooks = this.hooks.get(hookName) || [];
        hooks.push(callback);
        this.hooks.set(hookName, hooks);
    }

    triggerHook(hookName: string, ...args: any[]): void {
        const hooks = this.hooks.get(hookName) || [];
        hooks.forEach((callback) => {
            callback(...args);
        });
    }
}
