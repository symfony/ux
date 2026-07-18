import { ComponentClass, FunctionComponent } from "react";
type Component = string | FunctionComponent<object> | ComponentClass<object, any>;
type GlobModule = {
  default: Component;
};
type GlobResult = Record<string, GlobModule | (() => Promise<GlobModule>)>;
declare global {
  function resolveReactComponent(name: string): Component;
  interface Window {
    resolveReactComponent(name: string): Component;
  }
}
declare function registerReactControllerComponents(context: __WebpackModuleApi.RequireContext | GlobResult): void;
export { registerReactControllerComponents };