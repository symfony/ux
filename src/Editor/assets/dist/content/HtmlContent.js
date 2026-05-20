import { EditorContent, EditorContentFormat } from './EditorContent.js';
export class HtmlContent extends EditorContent {
    html;
    constructor(html, metadata = {}) {
        super(EditorContentFormat.Html, metadata);
        this.html = html;
    }
    getRaw() { return this.html; }
    isEmpty() { return this.html.replace(/<[^>]*>/g, '').trim() === ''; }
    static from(html, metadata = {}) {
        return new HtmlContent(html, metadata);
    }
}
