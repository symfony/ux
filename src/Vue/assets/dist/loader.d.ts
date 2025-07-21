import { ComponentCollection } from "./components.js";
import { Component } from "vue";

//#region src/loader.d.ts
declare global {
  function resolveVueComponent(name: string): Component;
  interface Window {
    resolveVueComponent(name: string): Component;
  }
}
declare function registerVueControllerComponents(vueControllers?: ComponentCollection): void;
//#endregion
export { registerVueControllerComponents };