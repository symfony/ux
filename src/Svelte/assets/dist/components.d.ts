import { SvelteComponent } from "svelte";
interface ComponentCollection {
  [key: string]: SvelteComponent;
}
declare const components: ComponentCollection;
export { ComponentCollection, components };