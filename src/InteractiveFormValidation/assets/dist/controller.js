import { Controller } from '@hotwired/stimulus';

const invalidFormAlertEventName = 'interactive-form-validation-invalid-alert';
class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.executeBrowserStrategy = () => {
            alert(this.msgValue);
        };
        this.executeEmitStrategy = () => {
            document.dispatchEvent(new CustomEvent(invalidFormAlertEventName, { detail: this.msgValue }));
        };
    }
    connect() {
        super.connect();
        document.addEventListener(invalidFormAlertEventName, (ev) => {
            console.log(ev);
        });
        if (this.withAlertValue)
            this.resolveStrategy()();
        if (this.idValue) {
            const el = document.getElementById(this.idValue);
            if (el instanceof HTMLElement) {
                el.focus({ preventScroll: true });
                el.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }
    }
    resolveStrategy() {
        if (this.alertStrategyValue === 'browser_native') {
            return this.executeBrowserStrategy;
        }
        else {
            return this.executeEmitStrategy;
        }
    }
}
default_1.values = {
    msg: String,
    id: String,
    withAlert: Boolean,
    alertStrategy: String,
};

export { default_1 as default, invalidFormAlertEventName };
