import { AbstractBlockController, type BlockInstance } from '@symfony/ux-editor/format/block';
import { resolveTool } from './tool-registry.js';

export default class EditorJSController extends AbstractBlockController {
    static values = { ...(AbstractBlockController as any).values };

    async createEditor(mount: HTMLElement, config: Record<string, unknown>): Promise<BlockInstance> {
        const { default: EditorJS } = await import('@editorjs/editorjs');

        const tools = config.tools as Record<string, { class: string; config?: any; inlineToolbar?: boolean; shortcut?: string }> | undefined;
        const resolvedTools: Record<string, any> = {};
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
        return editor as unknown as BlockInstance;
    }

    hotReloadable(): Set<string> {
        return new Set(['readOnly', 'placeholder']);
    }

    async applyConfig(diff: Record<string, unknown>, instance: any): Promise<void> {
        if ('readOnly' in diff && typeof instance.readOnly?.toggle === 'function') {
            await instance.readOnly.toggle(Boolean(diff.readOnly));
        }
    }

    async destroyEditor(instance: any): Promise<void> {
        if (typeof instance?.destroy === 'function') {
            await instance.destroy();
        }
    }
}
