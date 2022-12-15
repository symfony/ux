export default class {
    private hooks;
    constructor();
    register(hookName: string, callback: () => void): void;
    unregister(hookName: string, callback: () => void): void;
    triggerHook(hookName: string, ...args: any[]): void;
}
