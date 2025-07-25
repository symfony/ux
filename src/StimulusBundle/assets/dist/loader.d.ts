import { Application } from '@hotwired/stimulus';
import { EagerControllersCollection, LazyControllersCollection } from './controllers.js';

declare const loadControllers: (application: Application, eagerControllers: EagerControllersCollection, lazyControllers: LazyControllersCollection) => void;
declare const startStimulusApp: () => Application;

export { loadControllers, startStimulusApp };
