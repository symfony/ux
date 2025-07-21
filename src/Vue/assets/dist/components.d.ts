import { Component } from "vue";

//#region src/components.d.ts
interface ComponentCollection {
  [key: string]: Component;
}
declare const components: ComponentCollection;
//#endregion
export { ComponentCollection, components };