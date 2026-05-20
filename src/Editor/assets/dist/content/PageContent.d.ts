import { EditorContent } from './EditorContent.js';
export interface PageBundle {
    html: string;
    css: string;
    assets: unknown[];
    components: unknown[];
}
export declare class PageContent extends EditorContent<PageBundle> {
    readonly html: string;
    readonly css: string;
    readonly assets: unknown[];
    readonly components: unknown[];
    constructor(html: string, css?: string, assets?: unknown[], components?: unknown[], metadata?: Record<string, unknown>);
    getRaw(): PageBundle;
    isEmpty(): boolean;
    static from(bundle: Partial<PageBundle>, metadata?: Record<string, unknown>): PageContent;
}
