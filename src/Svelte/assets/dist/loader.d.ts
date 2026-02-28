import { ComponentCollection } from "./components.js";
import { SvelteComponent } from "svelte";
declare global {
  function resolveSvelteComponent(name: string): typeof SvelteComponent<any>;
  interface Window {
    resolveSvelteComponent(name: string): typeof SvelteComponent<any>;
  }
}
declare function registerSvelteControllerComponents(svelteComponents?: ComponentCollection): void;
export { registerSvelteControllerComponents };