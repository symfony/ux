import { Controller } from "@hotwired/stimulus";

//#region src/controller.d.ts
declare class export_default extends Controller {
  readonly viewValue: any;
  static values: {
    view: ObjectConstructor;
  };
  private chart;
  connect(): void;
  disconnect(): void;
  viewValueChanged(): void;
  private dispatchEvent;
}
//#endregion
export { export_default as default };