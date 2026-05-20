export function setupAutosave(root, opts) {
    const debounceMs = opts.debounceMs ?? 800;
    let timer;
    let lastValue;
    const handler = (e) => {
        const ev = e;
        lastValue = ev.detail?.value;
        if (timer !== undefined)
            clearTimeout(timer);
        timer = setTimeout(() => {
            timer = undefined;
            opts.dispatch(opts.field, lastValue).catch(() => { });
        }, debounceMs);
    };
    root.addEventListener('ux:editor:change', handler);
    return () => {
        root.removeEventListener('ux:editor:change', handler);
        if (timer !== undefined)
            clearTimeout(timer);
    };
}
