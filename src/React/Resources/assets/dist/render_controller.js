import React from 'react';
import require$$0 from 'react-dom';
import { Controller } from '@hotwired/stimulus';

var createRoot;

var m = require$$0;
if (process.env.NODE_ENV === 'production') {
  createRoot = m.createRoot;
} else {
  var i = m.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED;
  createRoot = function(c, o) {
    i.usingClientEntryPoint = true;
    try {
      return m.createRoot(c, o);
    } finally {
      i.usingClientEntryPoint = false;
    }
  };
}

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
        this.element.root.unmount();
        this._dispatchEvent('react:unmount', { component: this.componentValue, props: this.propsValue });
    }
    _renderReactElement(reactElement) {
        const root = createRoot(this.element);
        root.render(reactElement);
        this.element.root = root;
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
