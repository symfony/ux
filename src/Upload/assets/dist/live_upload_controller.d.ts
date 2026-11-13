import { Controller } from "@hotwired/stimulus";
export default class export_default extends Controller<HTMLElement> {
  static values: {
    property: StringConstructor;
    action: {
      type: StringConstructor;
      default: string;
    };
    event: {
      type: StringConstructor;
      default: string;
    };
  };
  propertyValue: string;
  actionValue: string;
  eventValue: string;
  private component?;
  private readonly onUploadComplete;
  connect(): Promise<void>;
  disconnect(): void;
  private apply;
}
