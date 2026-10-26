import { Controller } from "@hotwired/stimulus";
var controller_default = class extends Controller {
	static targets = [
		"input",
		"placeholder",
		"preview",
		"previewClearButton",
		"previewFilename",
		"previewImage",
		"previewList",
		"previewItemTemplate"
	];
	static values = {
		multiple: Boolean,
		removeLabel: {
			type: String,
			default: "Remove"
		}
	};
	dataTransfer;
	previewItem = null;
	processingCount = 0;
	filled = false;
	dragging = false;
	queue = Promise.resolve();
	generation = 0;
	initialize() {
		this.clear = this.clear.bind(this);
		this.onInputChange = this.onInputChange.bind(this);
		this.onDragEnter = this.onDragEnter.bind(this);
		this.onDragLeave = this.onDragLeave.bind(this);
		this.onDrop = this.onDrop.bind(this);
		this.onMultipleChange = this.onMultipleChange.bind(this);
	}
	connect() {
		this.dragging = false;
		this.element.addEventListener("dragenter", this.onDragEnter);
		this.element.addEventListener("dragleave", this.onDragLeave);
		this.element.addEventListener("drop", this.onDrop);
		if (this.multipleValue) {
			this.connectMultiple();
			this.dispatchEvent("connect");
			return;
		}
		for (const target of [this.previewTarget, ...this.hasPreviewImageTarget ? [this.previewImageTarget] : []]) if (target.style.display === "none") target.style.removeProperty("display");
		this.clear();
		this.inputTarget.addEventListener("change", this.onInputChange);
		this.dispatchEvent("connect");
	}
	disconnect() {
		this.generation++;
		this.element.removeEventListener("dragenter", this.onDragEnter);
		this.element.removeEventListener("dragleave", this.onDragLeave);
		this.element.removeEventListener("drop", this.onDrop);
		if (this.multipleValue) {
			this.inputTarget.removeEventListener("change", this.onMultipleChange);
			return;
		}
		this.inputTarget.removeEventListener("change", this.onInputChange);
	}
	previewClearButtonTargetConnected(button) {
		if (!this.multipleValue) button.addEventListener("click", this.clear);
	}
	previewClearButtonTargetDisconnected(button) {
		button.removeEventListener("click", this.clear);
	}
	clear() {
		this.generation++;
		this.reset();
	}
	reset() {
		this.inputTarget.value = "";
		if (this.hasPreviewImageTarget) {
			this.previewImageTarget.hidden = true;
			this.previewImageTarget.style.removeProperty("background-image");
		}
		if (this.hasPreviewFilenameTarget) this.previewFilenameTarget.textContent = "";
		this.filled = false;
		this.updateState();
		this.dispatchEvent("clear");
	}
	onInputChange(event) {
		const files = Array.from(event.target.files ?? []);
		if (files.length === 0) return;
		this.preAdd(files, (added) => {
			const file = added[0];
			if (typeof file === "undefined") {
				this.reset();
				return;
			}
			if (this.inputTarget.files?.[0] !== file) {
				const dataTransfer = new DataTransfer();
				dataTransfer.items.add(file);
				this.inputTarget.files = dataTransfer.files;
			}
			this.showFile(file);
		});
	}
	_populateImagePreview(target, file) {
		this.readAsDataUrl(file, (url) => {
			target.hidden = false;
			target.style.backgroundImage = `url("${url}")`;
		});
	}
	onDragEnter() {
		this.dragging = true;
		this.updateState();
	}
	onDragLeave(event) {
		event.preventDefault();
		if (!this.element.contains(event.relatedTarget)) {
			this.dragging = false;
			this.updateState();
		}
	}
	onDrop() {
		setTimeout(() => {
			this.dragging = false;
			this.updateState();
		});
	}
	connectMultiple() {
		this.previewItem = null;
		if (this.hasPreviewItemTemplateTarget) {
			const elements = this.previewItemTemplateTarget.content.children;
			if (elements.length !== 1) throw new Error("The \"previewItemTemplate\" target must contain one root element.");
			this.previewItem = elements[0];
		}
		this.dataTransfer = new DataTransfer();
		for (const file of Array.from(this.inputTarget.files ?? [])) this.dataTransfer.items.add(file);
		this.inputTarget.addEventListener("change", this.onMultipleChange);
		this.renderList();
		this.updateState();
	}
	onMultipleChange() {
		const files = Array.from(this.inputTarget.files ?? []);
		this.inputTarget.files = this.dataTransfer.files;
		this.preAdd(files, (added) => {
			for (const file of added) if (!this.containsFile(file)) this.dataTransfer.items.add(file);
			this.syncMultiple();
		});
	}
	showFile(file) {
		if (this.hasPreviewFilenameTarget) this.previewFilenameTarget.textContent = file.name;
		if (this.hasPreviewImageTarget) {
			this.previewImageTarget.hidden = true;
			if (file.type && file.type.indexOf("image") !== -1) this._populateImagePreview(this.previewImageTarget, file);
		}
		this.filled = true;
		this.updateState();
		this.dispatchEvent("change", file);
	}
	preAdd(files, add) {
		let promise = null;
		let prevented = false;
		if (files.length > 0) {
			let dispatching = true;
			const event = this.dispatchEvent("pre-add", {
				files,
				waitUntil: (filesPromise) => {
					if (!dispatching || promise) throw new Error("waitUntil() can only be called once, from a \"dropzone:pre-add\" listener.");
					promise = Promise.resolve(filesPromise);
				}
			}, true);
			dispatching = false;
			prevented = event.defaultPrevented;
		}
		if (prevented && promise) {
			promise.catch((error) => console.error(error));
			promise = null;
		}
		const selected = prevented ? [] : files;
		if (!promise && this.processingCount === 0) {
			add(selected);
			return;
		}
		const generation = this.generation;
		this.processingCount++;
		this.updateState();
		const result = (promise ?? Promise.resolve(selected)).then((resolved) => {
			if (!Array.isArray(resolved) || !resolved.every((file) => file instanceof File)) throw new TypeError("The promise passed to waitUntil() must resolve to an array of files.");
			return resolved;
		}).catch((error) => {
			console.error(error);
			return files;
		});
		this.queue = this.queue.then(() => result).then((resolved) => {
			if (generation === this.generation) add(resolved);
		}).finally(() => {
			this.processingCount--;
			this.updateState();
		});
	}
	syncMultiple() {
		this.inputTarget.files = this.dataTransfer.files;
		this.renderList();
		this.updateState();
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
		if (this.previewItem) return this.buildListItemFromTemplate(this.previewItem, file, index);
		const item = document.createElement("li");
		item.className = "dropzone-preview-list-item";
		const image = document.createElement("div");
		image.className = "dropzone-preview-image";
		image.hidden = true;
		if (this.isImage(file)) this._populateImagePreview(image, file);
		const filename = document.createElement("span");
		filename.className = "dropzone-preview-filename";
		filename.textContent = file.name;
		const remove = document.createElement("button");
		remove.type = "button";
		remove.className = "dropzone-preview-list-remove";
		this.bindRemove(remove, file, index);
		item.append(image, filename, remove);
		return item;
	}
	buildListItemFromTemplate(template, file, index) {
		const item = template.cloneNode(true);
		const slots = (name) => {
			const selector = `[data-dropzone-slot="${name}"]`;
			return [...item.matches(selector) ? [item] : [], ...Array.from(item.querySelectorAll(selector))];
		};
		const images = slots("image");
		for (const image of images) image.hidden = true;
		if (images.length > 0 && this.isImage(file)) this.readAsDataUrl(file, (url) => {
			for (const image of images) {
				if (image instanceof HTMLImageElement) image.src = url;
				else image.style.backgroundImage = `url("${url}")`;
				image.hidden = false;
			}
		});
		for (const filename of slots("filename")) filename.textContent = file.name;
		for (const remove of slots("remove")) this.bindRemove(remove, file, index);
		return item;
	}
	isImage(file) {
		return !!file.type && file.type.indexOf("image") !== -1;
	}
	readAsDataUrl(file, onLoad) {
		if (typeof FileReader === "undefined") return;
		const reader = new FileReader();
		reader.addEventListener("load", () => onLoad(reader.result));
		reader.readAsDataURL(file);
	}
	bindRemove(element, file, index) {
		element.setAttribute("aria-label", `${this.removeLabelValue} ${file.name}`);
		element.addEventListener("click", () => this.removeFileAt(index, file));
	}
	updateState() {
		const filled = this.multipleValue ? this.dataTransfer.files.length > 0 : this.filled;
		let state = filled ? "filled" : "empty";
		if (this.dragging) state = "dragging";
		if (this.processingCount > 0) state = "processing";
		this.element.setAttribute("data-state", state);
		if (this.multipleValue) return;
		const showPreview = filled && !this.dragging;
		this.inputTarget.hidden = showPreview;
		this.placeholderTarget.hidden = showPreview;
		this.previewTarget.hidden = !showPreview;
	}
	dispatchEvent(name, payload = {}, cancelable = false) {
		return this.dispatch(name, {
			detail: payload,
			prefix: "dropzone",
			cancelable
		});
	}
};
export { controller_default as default };
