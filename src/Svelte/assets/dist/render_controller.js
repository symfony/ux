import { Controller } from "@hotwired/stimulus";
class render_controller_default extends Controller {
  connect() {
    this.element.innerHTML = "";
    this.props = this.propsValue ?? void 0;
    this.intro = this.introValue ?? void 0;
    this.dispatchEvent("connect");
    const Component = window.resolveSvelteComponent(this.componentValue);
    this._destroyIfExists();
    this.app = new Component({
      target: this.element,
      props: this.props,
      intro: this.intro
    });
    this.element.root = this.app;
    this.dispatchEvent("mount", {
      component: Component
    });
  }
  disconnect() {
    this._destroyIfExists();
    this.dispatchEvent("unmount");
  }
  _destroyIfExists() {
    if (this.element.root !== void 0) {
      this.element.root.$destroy();
      delete this.element.root;
    }
  }
  dispatchEvent(name, payload = {}) {
    const detail = {
      componentName: this.componentValue,
      props: this.props,
      intro: this.intro,
      ...payload
    };
    this.dispatch(name, { detail, prefix: "svelte" });
  }
}
render_controller_default.values = {
  component: String,
  props: Object,
  intro: Boolean
};
export {
  render_controller_default as default
};
