export default class {
    private hooks;
    register(hookName: string, callback: () => void): void;
    unregister(hookName: string, callback: () => void): void;
    triggerHook(hookName: string, ...args: any[]): void;
}
