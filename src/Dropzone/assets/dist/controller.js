import { Controller } from "@hotwired/stimulus";
var _Class = class extends Controller {
	initialize() {
		this.clear = this.clear.bind(this);
		this.onInputChange = this.onInputChange.bind(this);
		this.onDragEnter = this.onDragEnter.bind(this);
		this.onDragLeave = this.onDragLeave.bind(this);
		this.onMultipleChange = this.onMultipleChange.bind(this);
	}
	connect() {
		if (this.multipleValue) {
			this.connectMultiple();
			this.dispatchEvent("connect");
			return;
		}
		this.clear();
		this.previewClearButtonTarget.addEventListener("click", this.clear);
		this.inputTarget.addEventListener("change", this.onInputChange);
		this.element.addEventListener("dragenter", this.onDragEnter);
		this.element.addEventListener("dragleave", this.onDragLeave);
		this.dispatchEvent("connect");
	}
	disconnect() {
		if (this.multipleValue) {
			this.inputTarget.removeEventListener("change", this.onMultipleChange);
			return;
		}
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
		if (file.type && file.type.indexOf("image") !== -1) this._populateImagePreview(this.previewImageTarget, file);
		this.dispatchEvent("change", file);
	}
	_populateImagePreview(target, file) {
		if (typeof FileReader === "undefined") return;
		const reader = new FileReader();
		reader.addEventListener("load", (event) => {
			target.style.display = "block";
			target.style.backgroundImage = `url("${event.target.result}")`;
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
	connectMultiple() {
		this.dataTransfer = new DataTransfer();
		for (const file of Array.from(this.inputTarget.files ?? [])) this.dataTransfer.items.add(file);
		this.inputTarget.addEventListener("change", this.onMultipleChange);
		this.renderList();
	}
	onMultipleChange() {
		for (const file of Array.from(this.inputTarget.files ?? [])) if (!this.containsFile(file)) this.dataTransfer.items.add(file);
		this.syncMultiple();
	}
	syncMultiple() {
		this.inputTarget.files = this.dataTransfer.files;
		this.renderList();
		this.dispatchEvent("change", this.inputTarget.files);
	}
	removeFileAt(index, file) {
		this.dataTransfer.items.remove(index);
		this.syncMultiple();
		this.dispatchEvent("remove", file);
	}
	containsFile(file) {
		return Array.from(this.dataTransfer.files).some((existing) => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified);
	}
	renderList() {
		if (!this.hasPreviewListTarget) return;
		const items = Array.from(this.dataTransfer.files).map((file, index) => this.buildListItem(file, index));
		this.previewListTarget.replaceChildren(...items);
	}
	buildListItem(file, index) {
		const item = document.createElement("li");
		item.className = "dropzone-preview-list-item";
		const image = document.createElement("div");
		image.className = "dropzone-preview-image";
		image.style.display = "none";
		if (file.type && file.type.indexOf("image") !== -1) this._populateImagePreview(image, file);
		const filename = document.createElement("span");
		filename.className = "dropzone-preview-filename";
		filename.textContent = file.name;
		const remove = document.createElement("button");
		remove.type = "button";
		remove.className = "dropzone-preview-list-remove";
		remove.addEventListener("click", () => this.removeFileAt(index, file));
		item.append(image, filename, remove);
		return item;
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
	"previewImage",
	"previewList"
];
_Class.values = { multiple: Boolean };
export { _Class as default };
