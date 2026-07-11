import { describe, it, expect } from 'vitest';
import { Application } from '@hotwired/stimulus';
import { AbstractWysiwygController, type WysiwygInstance } from '../../src/format/wysiwyg_controller.js';

class Fake extends AbstractWysiwygController {
    static values = { ...(AbstractWysiwygController as any).values };
    async createEditor(): Promise<WysiwygInstance> {
        return { getHTML: () => '<p>x</p>' };
    }
    hotReloadable(): Set<string> {
        return new Set(['readOnly']);
    }
    applyConfig(): void {}
    async destroyEditor(): Promise<void> {}
}

function buildHost(wrapper: HTMLElement): HTMLElement {
    const root = document.createElement('div');
    root.setAttribute('data-controller', 'w');
    root.setAttribute('data-w-config-value', '{}');
    root.setAttribute('data-w-format-value', 'html');
    root.setAttribute('data-w-bridge-id-value', 'fake');
    const input = document.createElement('textarea');
    input.setAttribute('data-w-target', 'input');
    const mount = document.createElement('div');
    mount.setAttribute('data-w-target', 'mount');
    root.append(input, mount);
    wrapper.append(root);
    return root;
}

describe('AbstractWysiwygController', () => {
    it('serialize returns instance.getHTML()', async () => {
        const wrapper = document.createElement('div');
        document.body.append(wrapper);
        const root = buildHost(wrapper);
        const app = Application.start(wrapper);
        app.register('w', Fake as any);
        await new Promise((r) => setTimeout(r, 0));
        const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'w');
        expect(ctrl.serialize(ctrl.instance)).toBe('<p>x</p>');
        app.stop();
        wrapper.remove();
    });

    it('sets aria-multiline on mount target', async () => {
        const wrapper = document.createElement('div');
        document.body.append(wrapper);
        const root = buildHost(wrapper);
        const app = Application.start(wrapper);
        app.register('w', Fake as any);
        await new Promise((r) => setTimeout(r, 0));
        const mount = root.querySelector('[data-w-target="mount"]')!;
        expect(mount.getAttribute('aria-multiline')).toBe('true');
        app.stop();
        wrapper.remove();
    });
});
