import { Controller } from '@hotwired/stimulus';
import { shallowReactive, watch, toRaw, createApp, defineComponent, h } from 'vue';

class default_1 extends Controller {
    propsValueChanged(newProps, oldProps) {
        if (oldProps) {
            let removedPropNames = Object.keys(oldProps);
            if (newProps) {
                removedPropNames = removedPropNames.filter((propName) => !Object.prototype.hasOwnProperty.call(newProps, propName));
            }
            removedPropNames.forEach((propName) => {
                delete this.props[propName];
            });
        }
        if (newProps) {
            Object.entries(newProps).forEach(([propName, propValue]) => {
                this.props[propName] = propValue;
            });
        }
    }
    initialize() {
        const props = this.hasPropsValue && this.propsValue ? this.propsValue : {};
        this.props = shallowReactive({ ...props });
        watch(this.props, (props) => {
            this.propsValue = toRaw(props);
        }, { flush: 'post' });
    }
    connect() {
        this.dispatchEvent('connect', { componentName: this.componentValue, props: this.props });
        const component = window.resolveVueComponent(this.componentValue);
        const wrappedComponent = this.wrapComponent(component);
        this.app = createApp(wrappedComponent);
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
    wrapComponent(component) {
        const { props } = this;
        return defineComponent(() => () => h(component, {
            ...props,
            ...Object.fromEntries(Object.keys(props).map((propName) => [
                `onUpdate:${propName}`,
                (value) => {
                    props[propName] = value;
                },
            ])),
        }));
    }
}
default_1.values = {
    component: String,
    props: Object,
};

export { default_1 as default };
