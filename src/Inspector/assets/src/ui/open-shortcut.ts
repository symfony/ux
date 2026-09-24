/** Open with "ux" without intercepting typing, composition, or modified shortcuts. */
export function bindOpenShortcut(open: () => void, signal: AbortSignal): void {
    let prefix = false;
    document.addEventListener(
        'keydown',
        (event) => {
            const editing = event
                .composedPath()
                .some(
                    (target) =>
                        target instanceof HTMLElement &&
                        (target.matches('input, textarea, select') ||
                            target.isContentEditable ||
                            target.closest('[contenteditable]:not([contenteditable="false"])'))
                );
            if (editing || event.isComposing || event.repeat || event.altKey || event.ctrlKey || event.metaKey) {
                prefix = false;
                return;
            }
            const key = event.key.toLowerCase();
            if (prefix && key === 'x') open();
            prefix = key === 'u';
        },
        { signal }
    );
}
