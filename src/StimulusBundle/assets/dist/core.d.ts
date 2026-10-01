import { Application } from "@hotwired/stimulus";
import { EagerControllersCollection, LazyControllersCollection } from "./controllers.js";
declare const loadControllers: (application: Application, eagerControllers: EagerControllersCollection, lazyControllers: LazyControllersCollection) => void;
declare const startApplication: (eagerControllers: EagerControllersCollection, lazyControllers: LazyControllersCollection, isApplicationDebug: boolean) => Application;
export { loadControllers, startApplication };