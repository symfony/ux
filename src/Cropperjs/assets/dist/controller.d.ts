import { Controller } from "@hotwired/stimulus";

//#region src/controller.d.ts
declare class CropperController extends Controller {
  readonly publicUrlValue: string;
  readonly optionsValue: object;
  static values: {
    publicUrl: StringConstructor;
    options: ObjectConstructor;
  };
  connect(): void;
  private dispatchEvent;
}
//#endregion
export { CropperController as default };