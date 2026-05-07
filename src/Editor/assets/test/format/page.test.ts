import { describe, it, expect } from 'vitest';
import { Application } from '@hotwired/stimulus';
import { AbstractPageBuilderController } from '../../src/format/page_builder_controller.js';

class Fake extends AbstractPageBuilderController {
  static values = { ...(AbstractPageBuilderController as any).values };
  async createEditor(): Promise<any> {
    return {
      getHtml: () => '<h1>X</h1>',
      getCss: () => 'h1{color:red}',
      getComponents: () => [{ type: 'h1' }],
      getAssets: () => [{ type: 'image', url: '/a.png' }],
    };
  }
  hotReloadable(): Set<string> { return new Set(); }
  applyConfig(): void {}
  async destroyEditor(): Promise<void> {}
}

function buildHost(wrapper: HTMLElement): HTMLElement {
  const root = document.createElement('div');
  root.setAttribute('data-controller', 'p');
  root.setAttribute('data-p-config-value', '{}');
  root.setAttribute('data-p-format-value', 'page');
  root.setAttribute('data-p-bridge-id-value', 'fp');
  const input = document.createElement('textarea');
  input.setAttribute('data-p-target', 'input');
  const mount = document.createElement('div');
  mount.setAttribute('data-p-target', 'mount');
  root.append(input, mount);
  wrapper.append(root);
  return root;
}

describe('AbstractPageBuilderController', () => {
  it('serialize returns bundle shape', async () => {
    const wrapper = document.createElement('div');
    document.body.append(wrapper);
    const root = buildHost(wrapper);
    const app = Application.start(wrapper);
    app.register('p', Fake as any);
    await new Promise(r => setTimeout(r, 0));
    const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'p');
    const out: any = ctrl.serialize(ctrl.instance);
    expect(out.html).toBe('<h1>X</h1>');
    expect(out.css).toBe('h1{color:red}');
    expect(out.components[0].type).toBe('h1');
    expect(out.assets[0].url).toBe('/a.png');
    app.stop();
    wrapper.remove();
  });
});
