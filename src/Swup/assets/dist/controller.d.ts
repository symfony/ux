import { Controller } from "@hotwired/stimulus";

//#region src/controller.d.ts
declare class export_default extends Controller {
  readonly animateHistoryBrowsingValue: boolean;
  readonly hasAnimateHistoryBrowsingValue: boolean;
  readonly animationSelectorValue: string;
  readonly hasAnimationSelectorValue: boolean;
  readonly cacheValue: boolean;
  readonly hasCacheValue: boolean;
  readonly containersValue: string[];
  readonly mainElementValue: string;
  readonly hasMainElementValue: boolean;
  readonly linkSelectorValue: string;
  readonly hasLinkSelectorValue: boolean;
  readonly themeValue: string;
  readonly debugValue: boolean;
  static values: {
    animateHistoryBrowsing: BooleanConstructor;
    animationSelector: StringConstructor;
    cache: BooleanConstructor;
    containers: ArrayConstructor;
    linkSelector: StringConstructor;
    theme: StringConstructor;
    debug: BooleanConstructor;
    mainElement: StringConstructor;
  };
  connect(): void;
  private dispatchEvent;
}
//#endregion
export { export_default as default };