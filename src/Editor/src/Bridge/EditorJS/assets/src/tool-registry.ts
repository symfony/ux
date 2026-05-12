declare global {
    interface Window {
        UXEditorJSTools?: Record<string, any>;
    }
}

export function resolveTool(name: string): any {
    return (typeof window !== 'undefined' && window.UXEditorJSTools && window.UXEditorJSTools[name]) ?? undefined;
}
