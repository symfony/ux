import { Controller } from '@hotwired/stimulus';
export class AbstractEditorController extends Controller {
    static values = {
        config: Object,
        format: String,
        bridgeId: String,
        uploadUrl: String,
    };
    static targets = ['input', 'mount'];
    instance;
    async connect() {
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
    syncInput() {
        if (this.instance === undefined)
            return;
        const value = this.serialize(this.instance);
        const finalize = (v) => {
            this.inputTarget.value = typeof v === 'string' ? v : JSON.stringify(v);
            this.element.dispatchEvent(new CustomEvent('ux:editor:change', {
                bubbles: true,
                detail: { value: v, format: this.formatValue, bridgeId: this.bridgeIdValue },
            }));
        };
        if (value && typeof value.then === 'function') {
            value.then(finalize);
        }
        else {
            finalize(value);
        }
    }
    async configValueChanged(newCfg, oldCfg) {
        if (!this.instance || oldCfg === undefined)
            return;
        const diff = this.diff(newCfg, oldCfg);
        if (Object.keys(diff).length === 0)
            return;
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
    async disconnect() {
        if (this.instance !== undefined) {
            await this.destroyEditor(this.instance);
            this.element.dispatchEvent(new CustomEvent('ux:editor:destroy', { bubbles: true, detail: { bridgeId: this.bridgeIdValue } }));
            this.instance = undefined;
        }
    }
    diff(a, b) {
        const out = {};
        const keys = new Set([...Object.keys(a), ...Object.keys(b)]);
        for (const k of keys) {
            if (JSON.stringify(a[k]) !== JSON.stringify(b[k])) {
                out[k] = a[k];
            }
        }
        return out;
    }
}
