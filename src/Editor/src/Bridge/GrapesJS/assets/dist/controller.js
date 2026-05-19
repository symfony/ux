import { AbstractPageBuilderController } from '@symfony/ux-editor/format/page';
function toJsonSafe(maybeCollection) {
    if (!maybeCollection)
        return [];
    if (Array.isArray(maybeCollection))
        return maybeCollection;
    const coll = maybeCollection;
    if (typeof coll.toJSON === 'function')
        return coll.toJSON();
    if (Array.isArray(coll.models))
        return coll.models;
    return [];
}
function readComponents(editor) {
    const getComponents = editor.getComponents;
    if (typeof getComponents === 'function') {
        return toJsonSafe(getComponents.call(editor));
    }
    return [];
}
function readAssets(editor) {
    const am = editor.AssetManager ?? editor.Assets;
    if (am && typeof am.getAll === 'function') {
        return toJsonSafe(am.getAll());
    }
    const getAssets = editor.getAssets;
    if (typeof getAssets === 'function') {
        return toJsonSafe(getAssets.call(editor));
    }
    return [];
}
export default class GrapesJSController extends AbstractPageBuilderController {
    static values = { ...AbstractPageBuilderController.values };
    async createEditor(mount, config) {
        const { default: grapesjs } = await import('grapesjs');
        const editor = grapesjs.init({ container: mount, ...config });
        ['component:add', 'component:remove', 'component:update', 'asset:add', 'asset:remove'].forEach(ev => editor.on(ev, () => this.syncInput()));
        const adapter = {
            getHtml: () => editor.getHtml(),
            getCss: () => editor.getCss(),
            getComponents: () => readComponents(editor),
            getAssets: () => readAssets(editor),
            destroy: () => editor.destroy(),
        };
        return adapter;
    }
    serialize(instance) {
        return {
            html: instance.getHtml(),
            css: instance.getCss(),
            components: instance.getComponents(),
            assets: instance.getAssets(),
        };
    }
    hotReloadable() {
        return new Set();
    }
    applyConfig(_diff, _instance) {
    }
    async destroyEditor(instance) {
        if (typeof instance?.destroy === 'function') {
            await instance.destroy();
        }
    }
}
