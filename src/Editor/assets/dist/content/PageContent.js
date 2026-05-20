import { EditorContent, EditorContentFormat } from './EditorContent.js';
export class PageContent extends EditorContent {
    html;
    css;
    assets;
    components;
    constructor(html, css = '', assets = [], components = [], metadata = {}) {
        super(EditorContentFormat.Page, metadata);
        this.html = html;
        this.css = css;
        this.assets = assets;
        this.components = components;
    }
    getRaw() {
        return { html: this.html, css: this.css, assets: this.assets, components: this.components };
    }
    isEmpty() { return this.html === '' && this.components.length === 0; }
    static from(bundle, metadata = {}) {
        return new PageContent(bundle.html ?? '', bundle.css ?? '', bundle.assets ?? [], bundle.components ?? [], metadata);
    }
}
