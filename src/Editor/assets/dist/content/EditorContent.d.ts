export declare enum EditorContentFormat {
    Html = "html",
    Blocks = "blocks",
    Page = "page"
}
export declare abstract class EditorContent<T = unknown> {
    readonly format: EditorContentFormat;
    readonly metadata: Record<string, unknown>;
    constructor(format: EditorContentFormat, metadata?: Record<string, unknown>);
    abstract getRaw(): T;
    abstract isEmpty(): boolean;
}
