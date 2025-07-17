import { Application } from '@hotwired/stimulus';
import { type EagerControllersCollection, type LazyControllersCollection } from './controllers.js';
export declare const loadControllers: (application: Application, eagerControllers: EagerControllersCollection, lazyControllers: LazyControllersCollection) => void;
export declare const startStimulusApp: () => Application;
