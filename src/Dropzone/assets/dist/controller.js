// src/controller.ts
import { Controller } from "@hotwired/stimulus";
var controller_default = class extends Controller {
  constructor() {
    super(...arguments);
    this.files = /* @__PURE__ */ new Map();
  }
  initialize() {
    this.clear = this.clear.bind(this);
    this.onInputChange = this.onInputChange.bind(this);
    this.onDragEnter = this.onDragEnter.bind(this);
    this.onDragLeave = this.onDragLeave.bind(this);
  }
  connect() {
    this.clear();
    this.inputTarget.addEventListener("change", this.onInputChange);
    this.element.addEventListener("dragenter", this.onDragEnter);
    this.element.addEventListener("dragleave", this.onDragLeave);
    this.dispatchEvent("connect");
  }
  disconnect() {
    this.inputTarget.removeEventListener("change", this.onInputChange);
    this.element.removeEventListener("dragenter", this.onDragEnter);
    this.element.removeEventListener("dragleave", this.onDragLeave);
  }
  clear(event) {
    if (event?.params) {
      const filename = event.params.filename;
      if (filename && this.files.has(filename)) {
        this.files.delete(filename);
        this.updateFileInput();
        this.renderPreview();
      }
    }
    if (!this.inputTarget || !this.inputTarget.files || this.inputTarget?.files?.length === 0) {
      this.placeholderTarget.style.display = "block";
      if (!this.isMultiple) {
        this.inputTarget.style.display = "block";
      }
    }
    this.dispatchEvent("clear");
  }
  onInputChange() {
    const files = this.inputTarget.files;
    if (!files || files.length <= 0) {
      return;
    }
    if (!this.isMultiple && this.files.size > 0) {
      this.inputTarget.style.display = "none";
    }
    const selectedFiles = this.isMultiple ? Array.from(files) : Array.from(files).slice(0, 1);
    this.addFiles(selectedFiles);
    this.updateFileInput();
    this.renderPreview();
    this.dispatchEvent("change", files);
  }
  renderPreview() {
    this.clearPreviewContainer();
    for (const file of this.files.values()) {
      const preview = this.buildPreview(file);
      if (preview) {
        this.previewContainerTarget.appendChild(preview);
      }
    }
    if (this.previewTargets.length > 1) {
      this.placeholderTarget.style.display = "none";
      if (!this.isMultiple) {
        this.inputTarget.style.display = "none";
      } else {
        this.inputTarget.style.display = "block";
      }
    }
  }
  clearPreviewContainer() {
    const previews = this.previewTargets;
    previews.slice(1).forEach((el) => el.remove());
  }
  buildPreview(file, element) {
    if (!element) {
      element = this.previewContainerTarget.firstElementChild?.cloneNode(true);
    }
    element.style.display = "flex";
    const fileName = element.querySelector(".dropzone-preview-filename");
    if (fileName) {
      fileName.textContent = file.name;
    }
    const button = element.querySelector(".dropzone-preview-button");
    if (button) {
      button.setAttribute("data-symfony--ux-dropzone--dropzone-filename-param", file.name);
    }
    this._populateImagePreview(element, file);
    return element;
  }
  _populateImagePreview(element, file) {
    const image = element.querySelector(".dropzone-preview-image");
    if (image && this.isImage(file) && typeof FileReader !== "undefined") {
      const reader = new FileReader();
      reader.addEventListener("load", (event) => {
        image.querySelector(".dropzone-preview-image")?.remove();
        image.style.backgroundImage = `url('${event.target.result}')`;
        image.style.display = "block";
      });
      reader.readAsDataURL(file);
    }
  }
  onDragEnter() {
    this.inputTarget.style.display = "block";
  }
  onDragLeave(event) {
    event.preventDefault();
  }
  updateFileInput() {
    const dataTransfer = new DataTransfer();
    for (const file of this.files.values()) {
      dataTransfer.items.add(file);
    }
    this.inputTarget.files = dataTransfer.files;
  }
  addFiles(files) {
    for (const file of files) {
      this.files.set(file.name, file);
    }
  }
  isImage(file) {
    return typeof file.type !== "undefined" && file.type.indexOf("image") !== -1;
  }
  get isMultiple() {
    return this.inputTarget.multiple;
  }
  dispatchEvent(name, payload = {}) {
    this.dispatch(name, { detail: payload, prefix: "dropzone" });
  }
};
controller_default.targets = [
  "input",
  "placeholder",
  "preview",
  "previewClearButton",
  "previewFilename",
  "previewImage",
  "previewContainer"
];
export {
  controller_default as default
};
