import React from 'react';
import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    connect() {
        this._dispatchEvent('react:connect', { component: this.componentValue, props: this.propsValue });
        const component = window.resolveReactComponent(this.componentValue);
        this._renderReactElement(React.createElement(component, this.propsValue, null));
        this._dispatchEvent('react:mount', {
            componentName: this.componentValue,
            component: component,
            props: this.propsValue,
        });
    }
    disconnect() {
        this.element.unmount();
        this._dispatchEvent('react:unmount', { component: this.componentValue, props: this.propsValue });
    }
    _renderReactElement(reactElement) {
        if (parseInt(React.version) >= 18) {
            const root = require('react-dom/client').createRoot(this.element);
            root.render(reactElement);
            this.element.unmount = () => {
                root.unmount();
            };
            return;
        }
        const reactDom = require('react-dom');
        reactDom.render(reactElement, this.element);
        this.element.unmount = () => {
            reactDom.unmountComponentAtNode(this.element);
        };
    }
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
default_1.values = {
    component: String,
    props: Object,
};

export { default_1 as default };
