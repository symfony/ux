import { Controller } from "@hotwired/stimulus";

//#region src/controller.d.ts
declare class export_default extends Controller<HTMLInputElement> {
  readonly visibleLabelValue: string;
  readonly visibleIconValue: string;
  readonly hiddenLabelValue: string;
  readonly hiddenIconValue: string;
  readonly buttonClassesValue: Array<string>;
  static values: {
    visibleLabel: {
      type: StringConstructor;
      default: string;
    };
    visibleIcon: {
      type: StringConstructor;
      default: string;
    };
    hiddenLabel: {
      type: StringConstructor;
      default: string;
    };
    hiddenIcon: {
      type: StringConstructor;
      default: string;
    };
    buttonClasses: ArrayConstructor;
  };
  isDisplayed: boolean;
  visibleIcon: string;
  hiddenIcon: string;
  connect(): void;
  private createButton;
  toggle(event: any): void;
  private dispatchEvent;
}
//#endregion
export { export_default as default };