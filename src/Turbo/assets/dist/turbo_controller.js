import { Controller } from "@hotwired/stimulus";
import "@hotwired/turbo";

//#region src/turbo_controller.ts
/**
* Empty Stimulus controller only used for Symfony Flex wiring.
*
* @author Titouan Galopin <galopintitouan@gmail.com>
*/
var turbo_controller_default = class extends Controller {};

//#endregion
export { turbo_controller_default as default };