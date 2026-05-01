import { EditorContent, EditorContentFormat } from './EditorContent.js';

export interface PageBundle {
  html: string;
  css: string;
  assets: unknown[];
  components: unknown[];
}

export class PageContent extends EditorContent<PageBundle> {
  constructor(
    public readonly html: string,
    public readonly css: string = '',
    public readonly assets: unknown[] = [],
    public readonly components: unknown[] = [],
    metadata: Record<string, unknown> = {},
  ) {
    super(EditorContentFormat.Page, metadata);
  }

  getRaw(): PageBundle {
    return { html: this.html, css: this.css, assets: this.assets, components: this.components };
  }

  isEmpty(): boolean { return this.html === '' && this.components.length === 0; }

  static from(bundle: Partial<PageBundle>, metadata: Record<string, unknown> = {}): PageContent {
    return new PageContent(bundle.html ?? '', bundle.css ?? '', bundle.assets ?? [], bundle.components ?? [], metadata);
  }
}
