import { EditorContent } from './EditorContent.js';
export declare class HtmlContent extends EditorContent<string> {
    readonly html: string;
    constructor(html: string, metadata?: Record<string, unknown>);
    getRaw(): string;
    isEmpty(): boolean;
    static from(html: string, metadata?: Record<string, unknown>): HtmlContent;
}
