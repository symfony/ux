import { Controller } from '@hotwired/stimulus';

export abstract class AbstractEditorController<T = any> extends Controller<HTMLElement> {
  static values = {
    config: Object,
    format: String,
    bridgeId: String,
    uploadUrl: String,
  };
  static targets = ['input', 'mount'];

  declare readonly configValue: Record<string, unknown>;
  declare readonly formatValue: string;
  declare readonly bridgeIdValue: string;
  declare readonly uploadUrlValue: string;
  declare readonly inputTarget: HTMLInputElement | HTMLTextAreaElement;
  declare readonly mountTarget: HTMLElement;

  protected instance?: T;

  abstract createEditor(mount: HTMLElement, config: Record<string, unknown>): Promise<T>;
  abstract serialize(instance: T): string | object | Promise<string | object>;
  abstract destroyEditor(instance: T): Promise<void> | void;
  abstract hotReloadable(): Set<string>;
  abstract applyConfig(diff: Record<string, unknown>, instance: T): Promise<void> | void;

  async connect(): Promise<void> {
    this.element.dispatchEvent(new CustomEvent('ux:editor:pre-connect', {
      bubbles: true,
      detail: { bridgeId: this.bridgeIdValue, format: this.formatValue, config: this.configValue },
    }));
    this.instance = await this.createEditor(this.mountTarget, this.configValue);
    this.element.dispatchEvent(new CustomEvent('ux:editor:connect', {
      bubbles: true,
      detail: { bridgeId: this.bridgeIdValue, instance: this.instance },
    }));
  }

  syncInput(): void {
    if (this.instance === undefined) return;
    const value = this.serialize(this.instance);
    const finalize = (v: string | object) => {
      this.inputTarget.value = typeof v === 'string' ? v : JSON.stringify(v);
      this.element.dispatchEvent(new CustomEvent('ux:editor:change', {
        bubbles: true,
        detail: { value: v, format: this.formatValue, bridgeId: this.bridgeIdValue },
      }));
    };
    if (value && typeof (value as any).then === 'function') {
      (value as Promise<string | object>).then(finalize);
    } else {
      finalize(value as string | object);
    }
  }

  async configValueChanged(newCfg: Record<string, unknown>, oldCfg?: Record<string, unknown>): Promise<void> {
    if (!this.instance || oldCfg === undefined) return;
    const diff = this.diff(newCfg, oldCfg);
    if (Object.keys(diff).length === 0) return;

    const hot = this.hotReloadable();
    const allHot = Object.keys(diff).every(k => hot.has(k));
    if (allHot) {
      await this.applyConfig(diff, this.instance);
      return;
    }
    await this.destroyEditor(this.instance);
    this.element.dispatchEvent(new CustomEvent('ux:editor:remount', {
      bubbles: true,
      detail: { reason: 'non-hot-keys', diff },
    }));
    this.instance = await this.createEditor(this.mountTarget, newCfg);
  }

  async disconnect(): Promise<void> {
    if (this.instance !== undefined) {
      await this.destroyEditor(this.instance);
      this.element.dispatchEvent(new CustomEvent('ux:editor:destroy', { bubbles: true, detail: { bridgeId: this.bridgeIdValue } }));
      this.instance = undefined;
    }
  }

  protected diff(a: Record<string, unknown>, b: Record<string, unknown>): Record<string, unknown> {
    const out: Record<string, unknown> = {};
    const keys = new Set([...Object.keys(a), ...Object.keys(b)]);
    for (const k of keys) {
      if (JSON.stringify(a[k]) !== JSON.stringify(b[k])) {
        out[k] = a[k];
      }
    }
    return out;
  }
}
