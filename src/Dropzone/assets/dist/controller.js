import { Controller } from "@hotwired/stimulus";
var controller_default = class extends Controller {
	static targets = [
		"input",
		"placeholder",
		"preview",
		"previewClearButton",
		"previewFilename",
		"previewImage",
		"previewList"
	];
	static values = {
		multiple: Boolean,
		maxFiles: Number,
		removeLabel: {
			type: String,
			default: "Remove"
		}
	};
	dataTransfer;
	shownFile = null;
	dropping = false;
	initialize() {
		this.clear = this.clear.bind(this);
		this.onInputChange = this.onInputChange.bind(this);
		this.onDragEnter = this.onDragEnter.bind(this);
		this.onDragLeave = this.onDragLeave.bind(this);
		this.onDrop = this.onDrop.bind(this);
		this.onMultipleChange = this.onMultipleChange.bind(this);
		this.onFilesDropped = this.onFilesDropped.bind(this);
	}
	connect() {
		this.element.addEventListener("drop", this.onFilesDropped);
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
		this.element.addEventListener("drop", this.onDrop);
		this.dispatchEvent("connect");
	}
	disconnect() {
		this.element.removeEventListener("drop", this.onFilesDropped);
		if (this.multipleValue) {
			this.inputTarget.removeEventListener("change", this.onMultipleChange);
			return;
		}
		this.previewClearButtonTarget.removeEventListener("click", this.clear);
		this.inputTarget.removeEventListener("change", this.onInputChange);
		this.element.removeEventListener("dragenter", this.onDragEnter);
		this.element.removeEventListener("dragleave", this.onDragLeave);
		this.element.removeEventListener("drop", this.onDrop);
	}
	clear() {
		this.inputTarget.value = "";
		this.inputTarget.style.display = "block";
		this.placeholderTarget.style.display = "block";
		this.previewTarget.style.display = "none";
		this.previewImageTarget.style.display = "none";
		this.previewImageTarget.style.backgroundImage = "none";
		this.previewFilenameTarget.textContent = "";
		this.shownFile = null;
		this.dispatchEvent("clear");
	}
	onInputChange(event) {
		const files = Array.from(event.target.files ?? []);
		if (files.length === 0) return;
		const [accepted, rejected] = this.checkAccept(files);
		const file = accepted[0];
		if (typeof file === "undefined") {
			this.setInputFiles(this.shownFile ? [this.shownFile] : []);
			this.reject(rejected, "accept");
			return;
		}
		if (files.length !== 1 || files[0] !== file) this.setInputFiles([file]);
		this.inputTarget.style.display = "none";
		this.placeholderTarget.style.display = "none";
		this.previewFilenameTarget.textContent = file.name;
		this.previewTarget.style.display = "flex";
		this.previewImageTarget.style.display = "none";
		if (file.type && file.type.indexOf("image") !== -1) this._populateImagePreview(this.previewImageTarget, file);
		this.shownFile = file;
		this.dispatchEvent("change", file);
		this.reject(rejected, "accept");
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
	onDrop() {
		setTimeout(() => {
			if (!this.inputTarget.files?.length) return;
			this.inputTarget.style.display = "none";
			this.placeholderTarget.style.display = "none";
			this.previewTarget.style.display = "flex";
		});
	}
	connectMultiple() {
		this.dataTransfer = new DataTransfer();
		for (const file of Array.from(this.inputTarget.files ?? [])) this.dataTransfer.items.add(file);
		this.inputTarget.addEventListener("change", this.onMultipleChange);
		this.renderList();
	}
	onMultipleChange() {
		const [accepted, rejected] = this.checkAccept(Array.from(this.inputTarget.files ?? []));
		const overflow = [];
		for (const file of accepted) {
			if (this.containsFile(file)) continue;
			if (this.maxFilesValue > 0 && this.dataTransfer.files.length >= this.maxFilesValue) overflow.push(file);
			else this.dataTransfer.items.add(file);
		}
		this.syncMultiple();
		this.reject(rejected, "accept");
		this.reject(overflow, "max_files");
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
	onFilesDropped() {
		this.dropping = true;
		setTimeout(() => {
			this.dropping = false;
		});
	}
	checkAccept(files) {
		if (!this.dropping) return [files, []];
		const tokens = this.inputTarget.accept.split(",").map((token) => token.trim().toLowerCase()).filter((token) => token !== "");
		if (tokens.length === 0 || tokens.some((token) => token === "*" || token === "*/*")) return [files, []];
		const accepted = [];
		const rejected = [];
		for (const file of files) {
			const name = file.name.toLowerCase();
			const type = file.type.toLowerCase();
			(tokens.some((token) => {
				if (token.startsWith(".")) return name.endsWith(token);
				if (type === "") return true;
				if (token.endsWith("/*")) return type.startsWith(token.slice(0, -1));
				return type === token;
			}) ? accepted : rejected).push(file);
		}
		return [accepted, rejected];
	}
	setInputFiles(files) {
		const dataTransfer = new DataTransfer();
		for (const file of files) dataTransfer.items.add(file);
		this.inputTarget.files = dataTransfer.files;
	}
	reject(files, reason) {
		if (files.length > 0) this.dispatchEvent("reject", {
			files,
			reason
		});
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
		remove.setAttribute("aria-label", `${this.removeLabelValue} ${file.name}`);
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
export { controller_default as default };
