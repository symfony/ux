export function resolveTool(name) {
    return (typeof window !== 'undefined' && window.UXEditorJSTools && window.UXEditorJSTools[name]) ?? undefined;
}
