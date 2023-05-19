// This file is dynamically rewritten by StimulusBundle + AssetMapper.
import { ControllerConstructor } from '@hotwired/stimulus';

export interface EagerControllersCollection {
    [key: string]: ControllerConstructor;
}
export interface LazyControllersCollection {
    [key: string]: () => Promise<{ default: ControllerConstructor }>;
}

export const eagerControllers: EagerControllersCollection = {};
export const lazyControllers: LazyControllersCollection = {};
export const isApplicationDebug = false;
