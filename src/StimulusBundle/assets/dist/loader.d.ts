import { EagerControllersCollection, LazyControllersCollection } from "./controllers.js";
import { Application } from "@hotwired/stimulus";
declare const loadControllers: (application: Application, eagerControllers: EagerControllersCollection, lazyControllers: LazyControllersCollection) => void;
declare const startStimulusApp: () => Application;
export { loadControllers, startStimulusApp };