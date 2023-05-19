import { Application } from '@hotwired/stimulus';
import { EagerControllersCollection, LazyControllersCollection } from './controllers.js';
export declare const loadControllers: (application: Application, eagerControllers: EagerControllersCollection, lazyControllers: LazyControllersCollection) => void;
export declare const startStimulusApp: () => Application;
