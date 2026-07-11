import { describe, it, expect, vi } from 'vitest';
import { Application } from '@hotwired/stimulus';

vi.mock('@editorjs/editorjs', () => {
    return {
        default: class FakeEditorJS {
            constructor(public opts: any) {}
            isReady: Promise<void> = Promise.resolve();
            async save(): Promise<any> {
                return { version: '2.30.0', blocks: [{ type: 'paragraph', data: { text: 'hi' } }] };
            }
            async destroy(): Promise<void> {}
        },
    };
});

function buildHost(wrapper: HTMLElement): { root: HTMLElement; input: HTMLTextAreaElement; mount: HTMLElement } {
    const root = document.createElement('div');
    root.setAttribute('data-controller', 'editorjs');
    root.setAttribute('data-editorjs-config-value', '{"defaultBlock":"paragraph"}');
    root.setAttribute('data-editorjs-format-value', 'blocks');
    root.setAttribute('data-editorjs-bridge-id-value', 'editorjs');
    const input = document.createElement('textarea');
    input.setAttribute('data-editorjs-target', 'input');
    const mount = document.createElement('div');
    mount.setAttribute('data-editorjs-target', 'mount');
    root.append(input, mount);
    wrapper.append(root);
    return { root, input, mount };
}

describe('EditorJSController', () => {
    it('mounts, dispatches connect, serialize returns save() payload', async () => {
        const { default: Controller } = await import('../src/controller.js');
        const wrapper = document.createElement('div');
        document.body.append(wrapper);
        const { root } = buildHost(wrapper);

        const events: string[] = [];
        ['ux:editor:pre-connect', 'ux:editor:connect'].forEach((n) => root.addEventListener(n, () => events.push(n)));

        const app = Application.start(wrapper);
        app.register('editorjs', Controller as any);
        await new Promise((r) => setTimeout(r, 10));

        expect(events).toEqual(['ux:editor:pre-connect', 'ux:editor:connect']);
        const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'editorjs');
        const out: any = await ctrl.serialize(ctrl.instance);
        expect(out.blocks[0].type).toBe('paragraph');
        app.stop();
        wrapper.remove();
    });
});
