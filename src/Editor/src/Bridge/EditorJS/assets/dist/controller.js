import { AbstractBlockController } from '@symfony/ux-editor/format/block';
import { resolveTool } from './tool-registry.js';
export default class EditorJSController extends AbstractBlockController {
    static values = { ...AbstractBlockController.values };
    async createEditor(mount, config) {
        const { default: EditorJS } = await import('@editorjs/editorjs');
        const tools = config.tools;
        const resolvedTools = {};
        if (tools) {
            for (const [name, spec] of Object.entries(tools)) {
                const klass = resolveTool(spec.class);
                if (!klass) {
                    console.warn(`[ux-editor-editorjs] Tool class "${spec.class}" not registered on window.UXEditorJSTools — skipping`);
                    continue;
                }
                resolvedTools[name] = { class: klass, config: spec.config ?? {}, inlineToolbar: spec.inlineToolbar ?? true, shortcut: spec.shortcut };
            }
        }
        const editor = new EditorJS({
            holder: mount,
            ...config,
            tools: resolvedTools,
        });
        await editor.isReady;
        return editor;
    }
    hotReloadable() {
        return new Set(['readOnly', 'placeholder']);
    }
    async applyConfig(diff, instance) {
        if ('readOnly' in diff && typeof instance.readOnly?.toggle === 'function') {
            await instance.readOnly.toggle(Boolean(diff.readOnly));
        }
    }
    async destroyEditor(instance) {
        if (typeof instance?.destroy === 'function') {
            await instance.destroy();
        }
    }
}
