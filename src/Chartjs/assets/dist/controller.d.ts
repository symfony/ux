import { Controller } from "@hotwired/stimulus";
export default class export_default extends Controller {
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
