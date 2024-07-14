import { Controller } from '@hotwired/stimulus';
import { createApp } from 'vue';

class default_1 extends Controller {
    connect() {
        this.props = this.propsValue ?? null;
        this.dispatchEvent('connect', { componentName: this.componentValue, props: this.props });
        const component = window.resolveVueComponent(this.componentValue);
        this.app = createApp(component, this.props);
        if (this.element.__vue_app__ !== undefined) {
            this.element.__vue_app__.unmount();
        }
        this.dispatchEvent('before-mount', {
            componentName: this.componentValue,
            component: component,
            props: this.props,
            app: this.app,
        });
        this.app.mount(this.element);
        this.dispatchEvent('mount', {
            componentName: this.componentValue,
            component: component,
            props: this.props,
        });
    }
    disconnect() {
        this.app.unmount();
        this.dispatchEvent('unmount', {
            componentName: this.componentValue,
            props: this.props,
        });
    }
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'vue' });
    }
}
default_1.values = {
    component: String,
    props: Object,
};

export { default_1 as default };
