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
		removeLabel: {
			type: String,
			default: "Remove"
		}
	};
	dataTransfer;
	processingCount = 0;
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
		if (this.processingCount === 0) this.element.removeAttribute("data-state");
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
		this.generation++;
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
		this.generation++;
		this.reset();
	}
	reset() {
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
			if (!this.inputTarget.files?.length || this.processingCount > 0) return;
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
		const files = Array.from(this.inputTarget.files ?? []);
		this.inputTarget.files = this.dataTransfer.files;
		this.preAdd(files, (added) => {
			for (const file of added) if (!this.containsFile(file)) this.dataTransfer.items.add(file);
			this.syncMultiple();
		});
	}
	showFile(file) {
		this.inputTarget.style.display = "none";
		this.placeholderTarget.style.display = "none";
		this.previewFilenameTarget.textContent = file.name;
		this.previewTarget.style.display = "flex";
		this.previewImageTarget.style.display = "none";
		if (file.type && file.type.indexOf("image") !== -1) this._populateImagePreview(this.previewImageTarget, file);
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
		this.element.setAttribute("data-state", "processing");
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
			if (this.processingCount === 0) this.element.removeAttribute("data-state");
		});
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
		remove.setAttribute("aria-label", `${this.removeLabelValue} ${file.name}`);
		remove.addEventListener("click", () => this.removeFileAt(index, file));
		item.append(image, filename, remove);
		return item;
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
