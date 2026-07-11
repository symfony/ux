interface AutosaveOptions {
  field: string;
  debounceMs?: number;
  dispatch: (field: string, content: unknown) => Promise<void>;
}
declare function setupAutosave(root: HTMLElement, opts: AutosaveOptions): () => void;
export { AutosaveOptions, setupAutosave };