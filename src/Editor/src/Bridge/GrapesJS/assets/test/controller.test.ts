import { describe, it, expect, vi } from 'vitest';
import { Application } from '@hotwired/stimulus';

vi.mock('grapesjs', () => {
    return {
        default: {
            init(opts: any): any {
                return {
                    opts,
                    getHtml: () => '<h1>X</h1>',
                    getCss: () => 'h1{color:red}',
                    getComponents: () => ({ toJSON: () => [{ type: 'h1' }] }),
                    getAssets: () => ({ toJSON: () => [{ type: 'image', src: '/a.png' }] }),
                    destroy: () => {},
                    on: (_e: string, _fn: () => void) => {},
                };
            },
        },
    };
});

function buildHost(wrapper: HTMLElement): HTMLElement {
    const root = document.createElement('div');
    root.setAttribute('data-controller', 'grapesjs');
    root.setAttribute('data-grapesjs-config-value', '{"storageManager":{"type":"none"}}');
    root.setAttribute('data-grapesjs-format-value', 'page');
    root.setAttribute('data-grapesjs-bridge-id-value', 'grapesjs');
    const input = document.createElement('textarea');
    input.setAttribute('data-grapesjs-target', 'input');
    const mount = document.createElement('div');
    mount.setAttribute('data-grapesjs-target', 'mount');
    root.append(input, mount);
    wrapper.append(root);
    return root;
}

describe('GrapesJSController', () => {
    it('mounts and serialize returns bundle shape', async () => {
        const { default: Controller } = await import('../src/controller.js');
        const wrapper = document.createElement('div');
        document.body.append(wrapper);
        const root = buildHost(wrapper);
        const events: string[] = [];
        ['ux:editor:pre-connect', 'ux:editor:connect'].forEach((n) => root.addEventListener(n, () => events.push(n)));

        const app = Application.start(wrapper);
        app.register('grapesjs', Controller as any);
        await new Promise((r) => setTimeout(r, 10));

        expect(events).toEqual(['ux:editor:pre-connect', 'ux:editor:connect']);
        const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'grapesjs');
        const out: any = ctrl.serialize(ctrl.instance);
        expect(out.html).toBe('<h1>X</h1>');
        expect(out.css).toBe('h1{color:red}');
        expect(out.components[0].type).toBe('h1');
        expect(out.assets[0].src).toBe('/a.png');
        app.stop();
        wrapper.remove();
    });
});
