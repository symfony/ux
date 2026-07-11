import { describe, it, expect } from 'vitest';
import { Application } from '@hotwired/stimulus';
import { AbstractWysiwygController } from '../../src/format/wysiwyg_controller.js';
import { AbstractBlockController } from '../../src/format/block_controller.js';
import { AbstractPageBuilderController } from '../../src/format/page_builder_controller.js';

async function lifecycle(cls: any, id: string, fmt: string): Promise<string[]> {
    const wrapper = document.createElement('div');
    document.body.append(wrapper);
    const root = document.createElement('div');
    root.setAttribute('data-controller', id);
    root.setAttribute(`data-${id}-config-value`, '{}');
    root.setAttribute(`data-${id}-format-value`, fmt);
    root.setAttribute(`data-${id}-bridge-id-value`, id);
    const input = document.createElement('textarea');
    input.setAttribute(`data-${id}-target`, 'input');
    const mount = document.createElement('div');
    mount.setAttribute(`data-${id}-target`, 'mount');
    root.append(input, mount);
    wrapper.append(root);

    const events: string[] = [];
    ['ux:editor:pre-connect', 'ux:editor:connect', 'ux:editor:destroy'].forEach((n) =>
        root.addEventListener(n, () => events.push(n))
    );
    const app = Application.start(wrapper);
    app.register(id, cls);
    await new Promise((r) => setTimeout(r, 0));
    root.remove();
    await new Promise((r) => setTimeout(r, 0));
    app.stop();
    wrapper.remove();
    return events;
}

describe('cross-format event consistency', () => {
    it('all Tier 1 controllers emit pre-connect, connect, destroy', async () => {
        class W extends AbstractWysiwygController {
            static values = { ...(AbstractWysiwygController as any).values };
            async createEditor(): Promise<any> {
                return { getHTML: () => '' };
            }
            hotReloadable() {
                return new Set<string>();
            }
            applyConfig() {}
            async destroyEditor() {}
        }
        class B extends AbstractBlockController {
            static values = { ...(AbstractBlockController as any).values };
            async createEditor(): Promise<any> {
                return { save: async () => ({ blocks: [] }) };
            }
            hotReloadable() {
                return new Set<string>();
            }
            applyConfig() {}
            async destroyEditor() {}
        }
        class P extends AbstractPageBuilderController {
            static values = { ...(AbstractPageBuilderController as any).values };
            async createEditor(): Promise<any> {
                return { getHtml: () => '', getCss: () => '', getComponents: () => [], getAssets: () => [] };
            }
            hotReloadable() {
                return new Set<string>();
            }
            applyConfig() {}
            async destroyEditor() {}
        }
        for (const [cls, id, fmt] of [
            [W, 'w1', 'html'],
            [B, 'b1', 'blocks'],
            [P, 'p1', 'page'],
        ] as const) {
            expect(await lifecycle(cls, id, fmt)).toEqual([
                'ux:editor:pre-connect',
                'ux:editor:connect',
                'ux:editor:destroy',
            ]);
        }
    });
});
