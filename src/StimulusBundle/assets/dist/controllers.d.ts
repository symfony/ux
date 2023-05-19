import { ControllerConstructor } from '@hotwired/stimulus';
export interface EagerControllersCollection {
    [key: string]: ControllerConstructor;
}
export interface LazyControllersCollection {
    [key: string]: () => Promise<{
        default: ControllerConstructor;
    }>;
}
export declare const eagerControllers: EagerControllersCollection;
export declare const lazyControllers: LazyControllersCollection;
export declare const isApplicationDebug = false;
