import { Component } from "vue";
type GlobModule = {
  default: Component;
};
type GlobResult = Record<string, GlobModule | (() => Promise<GlobModule>)>;
declare global {
  function resolveVueComponent(name: string): Component;
  interface Window {
    resolveVueComponent(name: string): Component;
  }
}
declare function registerVueControllerComponents(context: __WebpackModuleApi.RequireContext | GlobResult): void;
export { registerVueControllerComponents };