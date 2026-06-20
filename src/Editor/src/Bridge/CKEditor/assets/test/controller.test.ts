import { describe, it, expect, vi } from 'vitest';
import { Application } from '@hotwired/stimulus';

vi.mock('@ckeditor/ckeditor5-build-classic', () => {
    return {
        default: class FakeClassicEditor {
            static async create(_element: HTMLElement, opts: any): Promise<any> {
                return new FakeClassicEditor(opts);
            }
            constructor(public opts: any) {
                this.isReadOnly = false;
            }
            isReadOnly: boolean;
            getData(): string {
                return '<p>x</p>';
            }
            setData(_data: string): void {}
            enableReadOnlyMode(_id: string): void {
                this.isReadOnly = true;
            }
            disableReadOnlyMode(_id: string): void {
                this.isReadOnly = false;
            }
            destroy(): Promise<void> {
                return Promise.resolve();
            }
        },
    };
});

function buildHost(wrapper: HTMLElement): HTMLElement {
    const root = document.createElement('div');
    root.setAttribute('data-controller', 'ckeditor');
    root.setAttribute('data-ckeditor-config-value', '{"placeholder":"Write…"}');
    root.setAttribute('data-ckeditor-format-value', 'html');
    root.setAttribute('data-ckeditor-bridge-id-value', 'ckeditor');
    const input = document.createElement('textarea');
    input.setAttribute('data-ckeditor-target', 'input');
    const mount = document.createElement('div');
    mount.setAttribute('data-ckeditor-target', 'mount');
    root.append(input, mount);
    wrapper.append(root);
    return root;
}

describe('CKEditorController', () => {
    it('mounts and serialize returns getData()', async () => {
        const { default: Controller } = await import('../src/controller.js');
        const wrapper = document.createElement('div');
        document.body.append(wrapper);
        const root = buildHost(wrapper);
        const events: string[] = [];
        ['ux:editor:pre-connect', 'ux:editor:connect'].forEach((n) => root.addEventListener(n, () => events.push(n)));

        const app = Application.start(wrapper);
        app.register('ckeditor', Controller as any);
        await new Promise((r) => setTimeout(r, 10));

        expect(events).toEqual(['ux:editor:pre-connect', 'ux:editor:connect']);
        const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'ckeditor');
        expect(ctrl.serialize(ctrl.instance)).toBe('<p>x</p>');
        app.stop();
        wrapper.remove();
    });

    it('hot-applies readOnly', async () => {
        const { default: Controller } = await import('../src/controller.js');
        const wrapper = document.createElement('div');
        document.body.append(wrapper);
        const root = buildHost(wrapper);
        const app = Application.start(wrapper);
        app.register('ckeditor', Controller as any);
        await new Promise((r) => setTimeout(r, 10));
        const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'ckeditor');
        expect(ctrl.hotReloadable().has('readOnly')).toBe(true);
        expect(ctrl.instance.isReadOnly).toBe(false);
        await ctrl.applyConfig({ readOnly: true }, ctrl.instance);
        expect(ctrl.instance.isReadOnly).toBe(true);
        await ctrl.applyConfig({ readOnly: false }, ctrl.instance);
        expect(ctrl.instance.isReadOnly).toBe(false);
        app.stop();
        wrapper.remove();
    });
});
