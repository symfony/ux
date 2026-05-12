declare global {
    interface Window {
        UXEditorJSTools?: Record<string, any>;
    }
}
export declare function resolveTool(name: string): any;
