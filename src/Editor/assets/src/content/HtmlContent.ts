import { EditorContent, EditorContentFormat } from './EditorContent.js';

export class HtmlContent extends EditorContent<string> {
    constructor(
        public readonly html: string,
        metadata: Record<string, unknown> = {}
    ) {
        super(EditorContentFormat.Html, metadata);
    }

    getRaw(): string {
        return this.html;
    }

    isEmpty(): boolean {
        return this.html.replace(/<[^>]*>/g, '').trim() === '';
    }

    static from(html: string, metadata: Record<string, unknown> = {}): HtmlContent {
        return new HtmlContent(html, metadata);
    }
}
