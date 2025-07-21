import { Component } from "vue";

//#region src/register_controller.d.ts
declare global {
  function resolveVueComponent(name: string): Component;
  interface Window {
    resolveVueComponent(name: string): Component;
  }
}
declare function registerVueControllerComponents(context: __WebpackModuleApi.RequireContext): void;
//#endregion
export { registerVueControllerComponents };