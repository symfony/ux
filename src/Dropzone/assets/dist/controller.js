import { Controller } from "@hotwired/stimulus";
const fileKey = (file) => `${file.name}\0${file.size}\0${file.lastModified}`;
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
		maxFiles: Number,
		removeLabel: {
			type: String,
			default: "Remove"
		}
	};
	dataTransfer;
	previewItem = null;
	processingCount = 0;
	dragTargets = /* @__PURE__ */ new Set();
	queue = Promise.resolve();
	pendingSelections = /* @__PURE__ */ new Set();
	generation = 0;
	dropping = false;
	form = null;
	resetTimeout;
	dataUrls = /* @__PURE__ */ new WeakMap();
	previewedFiles = /* @__PURE__ */ new WeakMap();
	initialize() {
		this.clear = this.clear.bind(this);
		this.onInputChange = this.onInputChange.bind(this);
		this.onDragEnter = this.onDragEnter.bind(this);
		this.onDragLeave = this.onDragLeave.bind(this);
		this.onDrop = this.onDrop.bind(this);
		this.onFormReset = this.onFormReset.bind(this);
	}
	connect() {
		this.dragTargets.clear();
		this.dataTransfer = new DataTransfer();
		for (const file of Array.from(this.inputTarget.files ?? []).slice(0, this.multipleValue ? void 0 : 1)) this.dataTransfer.items.add(file);
		this.previewItem = null;
		if (this.hasPreviewItemTemplateTarget) {
			const elements = this.previewItemTemplateTarget.content.children;
			if (elements.length !== 1) throw new Error("The \"previewItemTemplate\" target must contain one root element.");
			this.previewItem = elements[0];
		}
		for (const target of [...this.hasPreviewTarget ? [this.previewTarget] : [], ...this.hasPreviewImageTarget ? [this.previewImageTarget] : []]) if (target.style.display === "none") target.style.removeProperty("display");
		this.element.addEventListener("dragenter", this.onDragEnter);
		this.element.addEventListener("dragleave", this.onDragLeave);
		this.element.addEventListener("drop", this.onDrop);
		this.inputTarget.addEventListener("change", this.onInputChange);
		this.form = this.inputTarget.form;
		this.form?.addEventListener("reset", this.onFormReset);
		this.render();
		this.dispatchEvent("connect");
	}
	disconnect() {
		this.generation++;
		this.pendingSelections.clear();
		clearTimeout(this.resetTimeout);
		this.element.removeEventListener("dragenter", this.onDragEnter);
		this.element.removeEventListener("dragleave", this.onDragLeave);
		this.element.removeEventListener("drop", this.onDrop);
		this.inputTarget.removeEventListener("change", this.onInputChange);
		this.form?.removeEventListener("reset", this.onFormReset);
		this.form = null;
	}
	previewClearButtonTargetConnected(button) {
		if (!this.multipleValue) button.addEventListener("click", this.clear);
	}
	previewClearButtonTargetDisconnected(button) {
		button.removeEventListener("click", this.clear);
	}
	clear() {
		this.generation++;
		this.pendingSelections.clear();
		const cleared = this.dataTransfer.files.length > 0;
		this.dataTransfer = new DataTransfer();
		this.syncInput();
		this.render();
		if (cleared) this.dispatchEvent("clear");
	}
	onInputChange() {
		const selected = Array.from(this.inputTarget.files ?? []);
		const [accepted, notAccepted] = this.checkAccept(selected);
		const [fitting, overflow] = this.checkMaxFiles(accepted, this.pendingSelections);
		this.syncInput();
		this.reject(notAccepted, "accept");
		this.reject(overflow, "max_files");
		if (fitting.length > 0) this.preAdd(fitting, (added) => this.add(added));
	}
	_populateImagePreview(target, file) {
		this.previewedFiles.set(target, file);
		this.readAsDataUrl(file, (url) => {
			if (this.previewedFiles.get(target) !== file) return;
			target.hidden = false;
			target.style.backgroundImage = `url("${url}")`;
		});
	}
	onDragEnter(event) {
		if (!this.isFileDrag(event)) return;
		this.dragTargets.add(event.target);
		this.updateState();
	}
	onDragLeave(event) {
		event.preventDefault();
		if (!this.isFileDrag(event)) return;
		this.dragTargets.delete(event.target);
		for (const target of this.dragTargets) if (!(target instanceof Node) || !this.element.contains(target)) this.dragTargets.delete(target);
		this.updateState();
	}
	onDrop() {
		this.dropping = true;
		setTimeout(() => {
			this.dropping = false;
			this.dragTargets.clear();
			this.updateState();
		});
	}
	onFormReset(event) {
		clearTimeout(this.resetTimeout);
		this.resetTimeout = setTimeout(() => {
			if (!event.defaultPrevented) this.clear();
		});
	}
	preAdd(files, add) {
		let promise = null;
		let dispatching = true;
		const event = this.dispatchEvent("pre-add", {
			files,
			waitUntil: (filesPromise) => {
				if (!dispatching || promise) throw new Error("waitUntil() can only be called once, from a \"dropzone:pre-add\" listener.");
				promise = Promise.resolve(filesPromise);
			}
		}, true);
		dispatching = false;
		if (event.defaultPrevented && promise) {
			promise.catch((error) => console.error(error));
			promise = null;
		}
		const selected = event.defaultPrevented ? [] : files;
		if (!promise && this.processingCount === 0) {
			add(selected);
			return;
		}
		const generation = this.generation;
		this.processingCount++;
		this.pendingSelections.add(selected);
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
			this.pendingSelections.delete(selected);
			this.processingCount--;
			this.updateState();
		});
	}
	add(added) {
		const [fitting, overflow] = this.checkMaxFiles(added, []);
		let changed = false;
		if (this.multipleValue) {
			const keys = new Set(Array.from(this.dataTransfer.files, fileKey));
			for (const file of fitting) if (!keys.has(fileKey(file))) {
				keys.add(fileKey(file));
				this.dataTransfer.items.add(file);
				changed = true;
			}
		} else if (fitting.length > 0) {
			this.dataTransfer = new DataTransfer();
			this.dataTransfer.items.add(fitting[0]);
			changed = true;
		}
		if (changed) {
			this.syncInput();
			this.render();
			this.dispatchEvent("change", this.multipleValue ? this.inputTarget.files : this.dataTransfer.files[0]);
		}
		this.reject(overflow, "max_files");
	}
	removeFileAt(index, file) {
		if (!this.multipleValue) {
			this.clear();
			return;
		}
		this.dataTransfer.items.remove(index);
		this.syncInput();
		this.render();
		this.dispatchEvent("change", this.inputTarget.files);
		this.dispatchEvent("remove", file);
		if (this.dataTransfer.files.length === 0) this.dispatchEvent("clear");
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
	checkMaxFiles(files, pendingSelections) {
		if (!this.multipleValue) return [files.slice(0, 1), files.slice(1)];
		if (!(this.maxFilesValue > 0)) return [files, []];
		const keys = new Set(Array.from(this.dataTransfer.files, fileKey));
		for (const selection of pendingSelections) for (const file of selection) keys.add(fileKey(file));
		let room = this.maxFilesValue - keys.size;
		const fitting = [];
		const overflow = [];
		for (const file of files) {
			const key = fileKey(file);
			if (keys.has(key)) fitting.push(file);
			else if (room > 0) {
				keys.add(key);
				fitting.push(file);
				room--;
			} else overflow.push(file);
		}
		return [fitting, overflow];
	}
	syncInput() {
		const dataTransfer = new DataTransfer();
		for (const file of Array.from(this.dataTransfer.files)) dataTransfer.items.add(file);
		this.inputTarget.files = dataTransfer.files;
	}
	reject(files, reason) {
		if (files.length > 0) this.dispatchEvent("reject", {
			files,
			reason
		});
	}
	render() {
		const files = Array.from(this.dataTransfer.files);
		if (this.multipleValue) {
			if (this.hasPreviewListTarget) this.previewListTarget.replaceChildren(...files.map((file, index) => this.buildListItem(file, index)));
		} else if (this.previewItem) this.previewTarget.replaceChildren(...files.map((file, index) => this.buildListItem(file, index)));
		else {
			const file = files[0];
			if (this.hasPreviewFilenameTarget) this.previewFilenameTarget.textContent = file?.name ?? "";
			if (this.hasPreviewImageTarget) {
				this.previewedFiles.delete(this.previewImageTarget);
				this.previewImageTarget.hidden = true;
				this.previewImageTarget.style.removeProperty("background-image");
				if (file && this.isImage(file)) this._populateImagePreview(this.previewImageTarget, file);
			}
		}
		this.updateState();
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
		let url = this.dataUrls.get(file);
		if (!url) {
			url = new Promise((resolve, reject) => {
				const reader = new FileReader();
				reader.addEventListener("load", () => resolve(reader.result));
				reader.addEventListener("error", () => reject(reader.error));
				reader.readAsDataURL(file);
			});
			this.dataUrls.set(file, url);
			url.catch(() => this.dataUrls.delete(file));
		}
		url.then(onLoad, () => {});
	}
	bindRemove(element, file, index) {
		element.setAttribute("aria-label", `${this.removeLabelValue} ${file.name}`);
		element.addEventListener("click", () => this.removeFileAt(index, file));
	}
	isFileDrag(event) {
		return Array.from(event.dataTransfer?.types ?? []).includes("Files");
	}
	updateState() {
		const filled = this.dataTransfer.files.length > 0;
		const dragging = this.dragTargets.size > 0;
		let state = filled ? "filled" : "empty";
		if (dragging) state = "dragging";
		if (this.processingCount > 0) state = "processing";
		this.element.setAttribute("data-state", state);
		if (this.multipleValue) return;
		const showPreview = filled && !dragging;
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
