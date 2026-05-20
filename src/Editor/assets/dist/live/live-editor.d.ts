export interface AutosaveOptions {
    field: string;
    debounceMs?: number;
    dispatch: (field: string, content: unknown) => Promise<void>;
}
export declare function setupAutosave(root: HTMLElement, opts: AutosaveOptions): () => void;
