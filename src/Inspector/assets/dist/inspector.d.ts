interface StimulusApplicationLike {
  getControllerForElementAndIdentifier(element: Element, identifier: string): object | null;
}
declare class UXInspector extends HTMLElement {
  #private;
  connectedCallback(): void;
  disconnectedCallback(): void;
  get isOpen(): boolean;
  getStatus(): Record<string, unknown>;
  setStimulusApplication(application: StimulusApplicationLike | null): void;
  open(): void;
  close(): void;
  toggle(): void;
  setPanelWidth(width: number): number;
  scan(): void;
  clear(): void;
  clearLog(): void;
  toggleLogPaused(): boolean;
  inspectElement(element: Element | null | undefined): void;
  toggleTargetMode(): boolean;
  toggleOverlay(): boolean;
}
declare function connectStimulus(application: StimulusApplicationLike | null): boolean;
export { UXInspector, connectStimulus };