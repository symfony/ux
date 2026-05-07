import { describe, expect, it, beforeEach } from 'vitest';
import { Application } from '@hotwired/stimulus';
import { AbstractEditorController } from '../src/controller.js';

beforeEach(() => { document.body.innerHTML = ''; });

class DummyController extends AbstractEditorController<{ dom: HTMLElement; config: any }> {
  static values = { ...(AbstractEditorController as any).values };
  async createEditor(mount: HTMLElement, config: any) { return { dom: mount, config }; }
  serialize(i: { dom: HTMLElement }): string { return i.dom.textContent ?? ''; }
  async destroyEditor(_i: any): Promise<void> {}
  hotReloadable(): Set<string> { return new Set(); }
  applyConfig(_d: Record<string, unknown>, _i: any): void {}
}

describe('AbstractEditorController', () => {
  it('declares expected values + targets', () => {
    expect((DummyController as any).values).toHaveProperty('config');
    expect((DummyController as any).values).toHaveProperty('format');
    expect((DummyController as any).values).toHaveProperty('bridgeId');
    expect((DummyController as any).targets).toContain('input');
    expect((DummyController as any).targets).toContain('mount');
  });

  it('registers without throwing', () => {
    const scope = document.createElement('div');
    document.body.append(scope);
    const app = Application.start(scope);
    expect(() => app.register('dummy', DummyController as any)).not.toThrow();
    app.stop();
  });
});

// ── Task 54 helpers ──────────────────────────────────────────────────────────

function buildHost(parent: HTMLElement = document.body): HTMLElement {
  const root = document.createElement('div');
  root.setAttribute('data-controller', 'dummy');
  root.setAttribute('data-dummy-config-value', '{"foo":1}');
  root.setAttribute('data-dummy-format-value', 'html');
  root.setAttribute('data-dummy-bridge-id-value', 'fake');
  const input = document.createElement('textarea');
  input.setAttribute('data-dummy-target', 'input');
  const mount = document.createElement('div');
  mount.setAttribute('data-dummy-target', 'mount');
  mount.textContent = 'hi';
  root.append(input, mount);
  parent.append(root);
  return root;
}

function makeApp(): { app: Application; scope: HTMLElement } {
  const scope = document.createElement('div');
  document.body.append(scope);
  const app = Application.start(scope);
  return { app, scope };
}

it('connect dispatches pre-connect then connect, creates instance', async () => {
  const { app, scope } = makeApp();
  app.register('dummy', DummyController as any);
  const root = buildHost(scope);
  const events: string[] = [];
  ['ux:editor:pre-connect', 'ux:editor:connect'].forEach(n => root.addEventListener(n, () => events.push(n)));
  await new Promise(r => setTimeout(r, 0));
  expect(events).toEqual(['ux:editor:pre-connect', 'ux:editor:connect']);
  app.stop();
});

// ── Task 55 ──────────────────────────────────────────────────────────────────

it('syncInput writes serialized value + dispatches ux:editor:change', async () => {
  const { app, scope } = makeApp();
  app.register('dummy', DummyController as any);
  const root = buildHost(scope);
  const events: any[] = [];
  root.addEventListener('ux:editor:change', (e: any) => events.push(e.detail));
  await new Promise(r => setTimeout(r, 0));
  const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'dummy');
  ctrl.syncInput();
  expect((root.querySelector('textarea') as HTMLTextAreaElement).value).toBe('hi');
  expect(events[0]).toMatchObject({ value: 'hi', format: 'html' });
  app.stop();
});

// ── Task 56 ──────────────────────────────────────────────────────────────────

it('hot diff calls applyConfig, no remount', async () => {
  let applied: any = null;
  let created = 0;
  class HotDummy extends AbstractEditorController<{}> {
    static values = { ...(AbstractEditorController as any).values };
    async createEditor(): Promise<{}> { created++; return {}; }
    serialize(): string { return ''; }
    async destroyEditor(): Promise<void> {}
    hotReloadable(): Set<string> { return new Set(['readOnly']); }
    applyConfig(diff: Record<string, unknown>): void { applied = diff; }
  }
  const { app, scope } = makeApp();
  const root = document.createElement('div');
  root.setAttribute('data-controller', 'hot');
  root.setAttribute('data-hot-config-value', '{"readOnly":false}');
  root.setAttribute('data-hot-format-value', 'html');
  root.setAttribute('data-hot-bridge-id-value', 'fake');
  const input = document.createElement('textarea');
  input.setAttribute('data-hot-target', 'input');
  const mount = document.createElement('div');
  mount.setAttribute('data-hot-target', 'mount');
  root.append(input, mount);
  scope.append(root);

  app.register('hot', HotDummy as any);
  await new Promise(r => setTimeout(r, 0));
  const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'hot');
  await ctrl.configValueChanged({ readOnly: true }, { readOnly: false });
  expect(applied).toEqual({ readOnly: true });
  expect(created).toBe(1);
  app.stop();
});

it('non-hot diff destroys + recreates', async () => {
  let destroyed = 0, created = 0;
  class ColdDummy extends AbstractEditorController<{}> {
    static values = { ...(AbstractEditorController as any).values };
    async createEditor(): Promise<{}> { created++; return {}; }
    serialize(): string { return ''; }
    async destroyEditor(): Promise<void> { destroyed++; }
    hotReloadable(): Set<string> { return new Set(['readOnly']); }
    applyConfig(): void {}
  }
  const { app, scope } = makeApp();
  const root = document.createElement('div');
  root.setAttribute('data-controller', 'cold');
  root.setAttribute('data-cold-config-value', '{"toolbar":[]}');
  root.setAttribute('data-cold-format-value', 'html');
  root.setAttribute('data-cold-bridge-id-value', 'fake');
  const input = document.createElement('textarea');
  input.setAttribute('data-cold-target', 'input');
  const mount = document.createElement('div');
  mount.setAttribute('data-cold-target', 'mount');
  root.append(input, mount);
  scope.append(root);

  app.register('cold', ColdDummy as any);
  await new Promise(r => setTimeout(r, 0));
  const ctrl: any = (app as any).getControllerForElementAndIdentifier(root, 'cold');
  await ctrl.configValueChanged({ toolbar: ['bold'] }, { toolbar: [] });
  expect(destroyed).toBe(1);
  expect(created).toBe(2);
  app.stop();
});

// ── Task 57 ──────────────────────────────────────────────────────────────────

it('disconnect destroys + dispatches ux:editor:destroy', async () => {
  let destroyed = 0;
  class D extends AbstractEditorController<{}> {
    static values = { ...(AbstractEditorController as any).values };
    async createEditor(): Promise<{}> { return {}; }
    serialize(): string { return ''; }
    async destroyEditor(): Promise<void> { destroyed++; }
    hotReloadable(): Set<string> { return new Set(); }
    applyConfig(): void {}
  }
  const { app, scope } = makeApp();
  const root = document.createElement('div');
  root.setAttribute('data-controller', 'd');
  root.setAttribute('data-d-config-value', '{}');
  root.setAttribute('data-d-format-value', 'html');
  root.setAttribute('data-d-bridge-id-value', 'fake');
  const input = document.createElement('textarea');
  input.setAttribute('data-d-target', 'input');
  const mount = document.createElement('div');
  mount.setAttribute('data-d-target', 'mount');
  root.append(input, mount);
  scope.append(root);

  const events: string[] = [];
  root.addEventListener('ux:editor:destroy', () => events.push('destroy'));
  app.register('d', D as any);
  await new Promise(r => setTimeout(r, 0));
  root.remove();
  await new Promise(r => setTimeout(r, 0));
  expect(destroyed).toBe(1);
  expect(events).toEqual(['destroy']);
  app.stop();
});
