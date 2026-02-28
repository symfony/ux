import { ControllerConstructor } from "@hotwired/stimulus";
interface EagerControllersCollection {
  [key: string]: ControllerConstructor;
}
interface LazyControllersCollection {
  [key: string]: () => Promise<{
    default: ControllerConstructor;
  }>;
}
declare const eagerControllers: EagerControllersCollection;
declare const lazyControllers: LazyControllersCollection;
declare const isApplicationDebug = false;
export { EagerControllersCollection, LazyControllersCollection, eagerControllers, isApplicationDebug, lazyControllers };