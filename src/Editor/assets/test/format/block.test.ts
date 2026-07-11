import { describe, it, expect } from 'vitest';
import { Application } from '@hotwired/stimulus';
import { AbstractBlockController } from '../../src/format/block_controller.js';

class Fake extends AbstractBlockController {
    static values = { ...(AbstractBlockController as any).values };
    async createEditor(): Promise<any> {
        return { save: async () => ({ version: '1.0', blocks: [{ type: 'p', data: { text: 'hi' } }] }) };
    }
    hotReloadable(): Set<string> {
        return new Set();
    }
    applyConfig(): void {}
    async destroyEditor(): Promise<void> {}
}

function buildHost(wrapper: HTMLElement): HTMLElement {
    const root = document.createElement('div');
    root.setAttribute('data-controller', 'b');
    root.setAttribute('data-b-config-value', '{}');
    root.setAttribute('data-b-format-value', 'blocks');
    root.setAttribute('data-b-bridge-id-value', 'fb');
    const input = document.createElement('textarea');
    input.setAttribute('data-b-target', 'input');
    const mount = document.createElement('div');
    mount.setAttribute('data-b-target', 'mount');
    root.append(input, mount);
    wrapper.append(root);
    return root;
}

describe('AbstractBlockController', () => {
    it('serialize awaits editor.save()', async () => {
        const wrapper = document.createElement('div');
        document.body.append(wrapper);
        const root = buildHost(wrapper);
        const app = Application.start(wrapper);
        app.register('b', Fake as any);
        await new Promise((r) => setTimeout(r, 0));
        const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'b');
        const out: any = await ctrl.serialize(ctrl.instance);
        expect(out.blocks[0].type).toBe('p');
        app.stop();
        wrapper.remove();
    });
});
