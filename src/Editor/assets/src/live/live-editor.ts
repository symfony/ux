export interface AutosaveOptions {
  field: string;
  debounceMs?: number;
  dispatch: (field: string, content: unknown) => Promise<void>;
}

export function setupAutosave(root: HTMLElement, opts: AutosaveOptions): () => void {
  const debounceMs = opts.debounceMs ?? 800;
  let timer: ReturnType<typeof setTimeout> | undefined;
  let lastValue: unknown;

  const handler = (e: Event) => {
    const ev = e as CustomEvent<{ value: unknown }>;
    lastValue = ev.detail?.value;
    if (timer !== undefined) clearTimeout(timer);
    timer = setTimeout(() => {
      timer = undefined;
      opts.dispatch(opts.field, lastValue).catch(() => { /* host surfaces errors */ });
    }, debounceMs);
  };

  root.addEventListener('ux:editor:change', handler);
  return () => {
    root.removeEventListener('ux:editor:change', handler);
    if (timer !== undefined) clearTimeout(timer);
  };
}
