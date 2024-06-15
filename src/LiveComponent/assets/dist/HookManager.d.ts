export default class {
    private hooks;
    register(hookName: string, callback: (...args: any[]) => void): void;
    unregister(hookName: string, callback: (...args: any[]) => void): void;
    triggerHook(hookName: string, ...args: any[]): void;
}
