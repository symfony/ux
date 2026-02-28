import { Component } from "vue";
interface ComponentCollection {
  [key: string]: Component;
}
declare const components: ComponentCollection;
export { ComponentCollection, components };