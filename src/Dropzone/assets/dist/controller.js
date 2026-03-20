import { Controller } from "@hotwired/stimulus";
var _Class = class extends Controller {
	initialize() {
		this.clear = this.clear.bind(this);
		this.onInputChange = this.onInputChange.bind(this);
		this.onDragEnter = this.onDragEnter.bind(this);
		this.onDragLeave = this.onDragLeave.bind(this);
	}
	connect() {
		this.clear();
		this.previewClearButtonTarget.addEventListener("click", this.clear);
		this.inputTarget.addEventListener("change", this.onInputChange);
		this.element.addEventListener("dragenter", this.onDragEnter);
		this.element.addEventListener("dragleave", this.onDragLeave);
		this.dispatchEvent("connect");
	}
	disconnect() {
		this.previewClearButtonTarget.removeEventListener("click", this.clear);
		this.inputTarget.removeEventListener("change", this.onInputChange);
		this.element.removeEventListener("dragenter", this.onDragEnter);
		this.element.removeEventListener("dragleave", this.onDragLeave);
	}
	clear() {
		this.inputTarget.value = "";
		this.inputTarget.style.display = "block";
		this.placeholderTarget.style.display = "block";
		this.previewTarget.style.display = "none";
		this.previewImageTarget.style.display = "none";
		this.previewImageTarget.style.backgroundImage = "none";
		this.previewFilenameTarget.textContent = "";
		this.dispatchEvent("clear");
	}
	onInputChange(event) {
		const file = event.target.files[0];
		if (typeof file === "undefined") return;
		this.inputTarget.style.display = "none";
		this.placeholderTarget.style.display = "none";
		this.previewFilenameTarget.textContent = file.name;
		this.previewTarget.style.display = "flex";
		this.previewImageTarget.style.display = "none";
		if (file.type && file.type.indexOf("image") !== -1) this._populateImagePreview(file);
		this.dispatchEvent("change", file);
	}
	_populateImagePreview(file) {
		if (typeof FileReader === "undefined") return;
		const reader = new FileReader();
		reader.addEventListener("load", (event) => {
			this.previewImageTarget.style.display = "block";
			this.previewImageTarget.style.backgroundImage = `url("${event.target.result}")`;
		});
		reader.readAsDataURL(file);
	}
	onDragEnter() {
		this.inputTarget.style.display = "block";
		this.placeholderTarget.style.display = "block";
		this.previewTarget.style.display = "none";
	}
	onDragLeave(event) {
		event.preventDefault();
		if (!this.element.contains(event.relatedTarget)) {
			this.inputTarget.style.display = "none";
			this.placeholderTarget.style.display = "none";
			this.previewTarget.style.display = "block";
		}
	}
	dispatchEvent(name, payload = {}) {
		this.dispatch(name, {
			detail: payload,
			prefix: "dropzone"
		});
	}
};
_Class.targets = [
	"input",
	"placeholder",
	"preview",
	"previewClearButton",
	"previewFilename",
	"previewImage"
];
export { _Class as default };
