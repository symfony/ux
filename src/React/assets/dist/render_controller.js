import { Controller } from "@hotwired/stimulus";
import React from "react";
import { createRoot } from "react-dom/client";
class render_controller_default extends Controller {
  connect() {
    const props = this.propsValue ? this.propsValue : null;
    this.dispatchEvent("connect", { component: this.componentValue, props });
    if (!this.componentValue) {
      throw new Error("No component specified.");
    }
    const component = window.resolveReactComponent(this.componentValue);
    this._renderReactElement(React.createElement(component, props, null));
    this.dispatchEvent("mount", {
      componentName: this.componentValue,
      component,
      props
    });
  }
  disconnect() {
    if (this.permanentValue) {
      return;
    }
    this.element.root.unmount();
    this.dispatchEvent("unmount", {
      component: this.componentValue,
      props: this.propsValue ? this.propsValue : null
    });
  }
  _renderReactElement(reactElement) {
    const element = this.element;
    if (!element.root) {
      element.root = createRoot(this.element);
    }
    element.root.render(reactElement);
  }
  dispatchEvent(name, payload) {
    this.dispatch(name, { detail: payload, prefix: "react" });
  }
}
render_controller_default.values = {
  component: String,
  props: Object,
  permanent: { type: Boolean, default: false }
};
export {
  render_controller_default as default
};
