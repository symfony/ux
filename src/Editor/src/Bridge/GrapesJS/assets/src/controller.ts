import { AbstractPageBuilderController, type PageInstance } from '@symfony/ux-editor/format/page';

interface GrapesEditor {
    getHtml(): string;
    getCss(): string;
    destroy(): void;
    on(event: string, fn: () => void): void;
    // GrapesJS exposes additional managers as instance properties; treat as opaque and probe at runtime.
    [key: string]: unknown;
}

function toJsonSafe(maybeCollection: unknown): unknown[] {
    if (!maybeCollection) return [];
    if (Array.isArray(maybeCollection)) return maybeCollection;
    const coll = maybeCollection as { toJSON?: () => unknown[]; models?: unknown[] };
    if (typeof coll.toJSON === 'function') return coll.toJSON();
    if (Array.isArray(coll.models)) return coll.models;
    return [];
}

function readComponents(editor: GrapesEditor): unknown[] {
    const getComponents = (editor as any).getComponents;
    if (typeof getComponents === 'function') {
        return toJsonSafe(getComponents.call(editor));
    }
    return [];
}

function readAssets(editor: GrapesEditor): unknown[] {
    // GrapesJS 0.21 exposes AssetManager (or Assets) as an instance property; getAll() returns a Backbone Collection.
    const am = (editor as any).AssetManager ?? (editor as any).Assets;
    if (am && typeof am.getAll === 'function') {
        return toJsonSafe(am.getAll());
    }
    // Older versions exposed editor.getAssets() directly.
    const getAssets = (editor as any).getAssets;
    if (typeof getAssets === 'function') {
        return toJsonSafe(getAssets.call(editor));
    }
    return [];
}

export default class GrapesJSController extends AbstractPageBuilderController {
    static values = { ...(AbstractPageBuilderController as any).values };

    async createEditor(mount: HTMLElement, config: Record<string, unknown>): Promise<PageInstance> {
        const { default: grapesjs } = await import('grapesjs');
        const editor = grapesjs.init({
            container: mount,
            ...(config as Record<string, unknown>),
        }) as unknown as GrapesEditor;

        ['component:add', 'component:remove', 'component:update', 'asset:add', 'asset:remove'].forEach((ev) =>
            editor.on(ev, () => this.syncInput())
        );

        const adapter: PageInstance = {
            getHtml: () => editor.getHtml(),
            getCss: () => editor.getCss(),
            getComponents: () => readComponents(editor),
            getAssets: () => readAssets(editor),
            destroy: () => editor.destroy(),
        };
        return adapter;
    }

    serialize(instance: PageInstance): object {
        return {
            html: instance.getHtml(),
            css: instance.getCss(),
            components: instance.getComponents(),
            assets: instance.getAssets(),
        };
    }

    hotReloadable(): Set<string> {
        return new Set();
    }

    applyConfig(_diff: Record<string, unknown>, _instance: PageInstance): void {
        // No GrapesJS option safely hot-reloads; non-hot diff always triggers remount via base class.
    }

    async destroyEditor(instance: PageInstance): Promise<void> {
        if (typeof instance?.destroy === 'function') {
            await instance.destroy();
        }
    }
}
