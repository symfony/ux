import { Controller } from "@hotwired/stimulus";
declare class export_default extends Controller {
  static targets: string[];
  static values: {
    url: StringConstructor;
    mask: {
      type: StringConstructor;
      default: string;
    };
    renderHtml: {
      type: BooleanConstructor;
      default: boolean;
    };
    toggle: {
      type: BooleanConstructor;
      default: boolean;
    };
    revealLabel: {
      type: StringConstructor;
      default: string;
    };
    hideLabel: {
      type: StringConstructor;
      default: string;
    };
    loadingLabel: {
      type: StringConstructor;
      default: string;
    };
    errorLabel: {
      type: StringConstructor;
      default: string;
    };
    rateLimitedLabel: {
      type: StringConstructor;
      default: string;
    };
  };
  readonly urlValue: string;
  readonly maskValue: string;
  readonly renderHtmlValue: boolean;
  readonly toggleValue: boolean;
  readonly revealLabelValue: string;
  readonly hideLabelValue: string;
  readonly loadingLabelValue: string;
  readonly errorLabelValue: string;
  readonly rateLimitedLabelValue: string;
  readonly buttonTarget: HTMLButtonElement;
  readonly hasButtonTarget: boolean;
  readonly contentTarget: HTMLElement;
  readonly hasContentTarget: boolean;
  readonly valueTarget: HTMLElement;
  readonly hasValueTarget: boolean;
  readonly hideButtonTarget: HTMLButtonElement;
  readonly hasHideButtonTarget: boolean;
  readonly errorTarget: HTMLElement;
  readonly hasErrorTarget: boolean;
  private inFlight;
  private cachedValue;
  private revealed;
  private originalButtonHtml;
  private originalValueHtml;
  connect(): void;
  private resetTrigger;
  reveal(): Promise<void>;
  hide(): void;
  toggle(): void;
  private displayValue;
  private clearError;
  private showError;
}
export { export_default as default };