import { t as _defineProperty } from "./defineProperty-B6pPL0VL.js";
import { n as UploadSuspendedError, r as Uploader, t as UploadCancelledError } from "./uploader-CW2SxOPm.js";
import { Controller } from "@hotwired/stimulus";
const MAX_DIMENSION = 200;
const JPEG_QUALITY = .7;
var PreviewCache = class PreviewCache {
	constructor(cacheName = "ux-upload-previews") {
		_defineProperty(this, "cacheName", void 0);
		this.cacheName = cacheName;
	}
	async store(token, file) {
		if (!PreviewCache.isSupported() || !file.type.startsWith("image/")) return;
		try {
			const blob = await this.createThumbnail(file);
			if (!blob) return;
			const cache = await caches.open(this.cacheName);
			const response = new Response(blob, { headers: { "Content-Type": "image/jpeg" } });
			await cache.put(this.buildKey(token), response);
		} catch {}
	}
	async retrieve(token) {
		if (!PreviewCache.isSupported()) return null;
		try {
			const response = await (await caches.open(this.cacheName)).match(this.buildKey(token));
			if (!response) return null;
			const blob = await response.blob();
			return URL.createObjectURL(blob);
		} catch {
			return null;
		}
	}
	async remove(token) {
		if (!PreviewCache.isSupported()) return;
		try {
			await (await caches.open(this.cacheName)).delete(this.buildKey(token));
		} catch {}
	}
	async clear() {
		if (!PreviewCache.isSupported()) return;
		try {
			await caches.delete(this.cacheName);
		} catch {}
	}
	static isSupported() {
		return typeof caches !== "undefined" && typeof document !== "undefined" && typeof document.createElement === "function" && typeof HTMLCanvasElement !== "undefined";
	}
	buildKey(token) {
		return `/_ux-upload-preview/${encodeURIComponent(token)}`;
	}
	async createThumbnail(file) {
		const bitmap = await this.loadImageBitmap(file);
		if (!bitmap) return null;
		const { width, height } = this.fitDimensions(bitmap.width, bitmap.height);
		if (typeof OffscreenCanvas !== "undefined") return this.renderOffscreen(bitmap, width, height);
		return this.renderCanvas(bitmap, width, height);
	}
	async loadImageBitmap(file) {
		if (typeof createImageBitmap === "function") try {
			return await createImageBitmap(file);
		} catch {
			return null;
		}
		return null;
	}
	fitDimensions(originalWidth, originalHeight) {
		if (originalWidth <= MAX_DIMENSION && originalHeight <= MAX_DIMENSION) return {
			width: originalWidth,
			height: originalHeight
		};
		const ratio = Math.min(MAX_DIMENSION / originalWidth, MAX_DIMENSION / originalHeight);
		return {
			width: Math.round(originalWidth * ratio),
			height: Math.round(originalHeight * ratio)
		};
	}
	async renderOffscreen(bitmap, width, height) {
		const canvas = new OffscreenCanvas(width, height);
		const ctx = canvas.getContext("2d");
		if (!ctx) return null;
		ctx.drawImage(bitmap, 0, 0, width, height);
		return canvas.convertToBlob({
			type: "image/jpeg",
			quality: JPEG_QUALITY
		});
	}
	renderCanvas(bitmap, width, height) {
		const canvas = document.createElement("canvas");
		canvas.width = width;
		canvas.height = height;
		const ctx = canvas.getContext("2d");
		if (!ctx) return Promise.resolve(null);
		ctx.drawImage(bitmap, 0, 0, width, height);
		return new Promise((resolve) => {
			canvas.toBlob((blob) => resolve(blob), "image/jpeg", JPEG_QUALITY);
		});
	}
};
function getFileIconCategory(mimeType, filename) {
	const type = mimeType.toLowerCase();
	const ext = filename.split(".").pop()?.toLowerCase() ?? "";
	if (type === "application/pdf" || ext === "pdf") return "pdf";
	if (type === "application/vnd.ms-excel" || type.includes("spreadsheet") || [
		"xls",
		"xlsx",
		"ods",
		"csv"
	].includes(ext)) return "spreadsheet";
	if (type === "application/msword" || type.includes("wordprocessing") || type.startsWith("text/") || [
		"doc",
		"docx",
		"odt",
		"rtf"
	].includes(ext)) return "document";
	if (type === "application/zip" || type === "application/gzip" || type.startsWith("application/x-rar") || type.startsWith("application/x-7z") || [
		"zip",
		"rar",
		"7z",
		"tar",
		"gz",
		"bz2"
	].includes(ext)) return "archive";
	if (type.startsWith("video/") || [
		"mp4",
		"avi",
		"mov",
		"mkv",
		"webm"
	].includes(ext)) return "video";
	if (type.startsWith("audio/") || [
		"mp3",
		"wav",
		"ogg",
		"flac",
		"aac"
	].includes(ext)) return "audio";
	return "default";
}
const ANONYMOUS_NAMES = new Set([
	"image.png",
	"image.jpeg",
	"image.jpg"
]);
function extractFilesFromClipboard(event) {
	const data = event.clipboardData;
	if (!data) return [];
	if (data.files && data.files.length > 0) return Array.from(data.files).map(renameAnonymousFile);
	const files = [];
	for (const item of Array.from(data.items)) {
		if (item.kind !== "file") continue;
		const file = item.getAsFile();
		if (file) files.push(renameAnonymousFile(file));
	}
	return files;
}
function renameAnonymousFile(file) {
	if (file.name && !ANONYMOUS_NAMES.has(file.name.toLowerCase())) return file;
	const ext = extensionFromMime(file.type);
	const stamp = (/* @__PURE__ */ new Date()).toISOString().replace(/[:.]/g, "-");
	return new File([file], `pasted-${stamp}.${ext}`, {
		type: file.type,
		lastModified: file.lastModified
	});
}
function extensionFromMime(mime) {
	if (!mime) return "bin";
	return (mime.split("/")[1] ?? "").split("+")[0] || "bin";
}
function formatSize(bytes) {
	const units = [
		"B",
		"KB",
		"MB",
		"GB"
	];
	let size = bytes;
	let unitIndex = 0;
	while (size >= 1024 && unitIndex < units.length - 1) {
		size /= 1024;
		unitIndex++;
	}
	return `${size.toFixed(unitIndex > 0 ? 1 : 0)} ${units[unitIndex]}`;
}
function formatSpeed(bytesPerSecond) {
	return `${formatSize(bytesPerSecond)}/s`;
}
function formatEta(remainingMs) {
	const seconds = Math.ceil(remainingMs / 1e3);
	if (seconds < 1 || !isFinite(seconds)) return "";
	if (seconds < 60) return `${seconds}s`;
	const minutes = Math.floor(seconds / 60);
	const secs = seconds % 60;
	if (minutes < 60) return secs > 0 ? `${minutes}m ${secs}s` : `${minutes}m`;
	const hours = Math.floor(minutes / 60);
	const mins = minutes % 60;
	return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
}
const INTEGRITY_ALGORITHMS = [
	"sha256",
	"sha384",
	"sha512"
];
const formSubmissionGuards = /* @__PURE__ */ new WeakMap();
function toIntegrityAlgorithm(value) {
	return INTEGRITY_ALGORITHMS.includes(value) ? value : "sha256";
}
function connectFormSubmissionGuard(form) {
	const existing = formSubmissionGuards.get(form);
	if (existing) {
		existing.controllers++;
		return;
	}
	const guard = {
		controllers: 1,
		submitting: false,
		listener: (event) => {
			if (event.defaultPrevented) return;
			if (guard.submitting) {
				event.preventDefault();
				event.stopImmediatePropagation();
				return;
			}
			guard.submitting = true;
			form.classList.add("ux-upload-form--submitting");
			form.setAttribute("aria-busy", "true");
			queueMicrotask(() => {
				if (event.defaultPrevented) {
					guard.submitting = false;
					form.classList.remove("ux-upload-form--submitting");
					form.removeAttribute("aria-busy");
				}
			});
		}
	};
	formSubmissionGuards.set(form, guard);
	form.addEventListener("submit", guard.listener);
}
function disconnectFormSubmissionGuard(form) {
	const guard = formSubmissionGuards.get(form);
	if (!guard || --guard.controllers > 0) return;
	form.removeEventListener("submit", guard.listener);
	form.classList.remove("ux-upload-form--submitting");
	form.removeAttribute("aria-busy");
	formSubmissionGuards.delete(form);
}
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_defineProperty(this, "uploader", void 0);
		_defineProperty(this, "previewCache", void 0);
		_defineProperty(this, "cachedBlobUrls", /* @__PURE__ */ new Map());
		_defineProperty(this, "uploads", /* @__PURE__ */ new Map());
		_defineProperty(this, "fileCounter", 0);
		_defineProperty(this, "fileByFile", /* @__PURE__ */ new Map());
		_defineProperty(this, "dragCounter", 0);
		_defineProperty(this, "fileProgress", /* @__PURE__ */ new Map());
		_defineProperty(this, "lastSummaryText", null);
		_defineProperty(this, "form", null);
	}
	connect() {
		this.uploader = new Uploader({
			directUrl: this.directUrlValue || void 0,
			directUploadThreshold: this.chunkSizeValue,
			initUrl: this.initUrlValue,
			removeUrl: this.removeUrlValue || void 0,
			events: this.createEvents(),
			uploader: this.uploaderValue,
			csrfToken: this.csrfTokenValue || null,
			integrityAlgorithm: toIntegrityAlgorithm(this.integrityAlgorithmValue),
			policyToken: this.policyTokenValue || null,
			compression: this.compressionValue
		});
		this.previewCache = new PreviewCache();
		if (this.hasInputTarget && this.multipleValue) this.inputTarget.multiple = true;
		this.hydrate();
		this.syncRequired();
		this.form = this.element.closest("form");
		if (this.form) connectFormSubmissionGuard(this.form);
	}
	disconnect() {
		if (this.form) {
			disconnectFormSubmissionGuard(this.form);
			this.form = null;
		}
		for (const [fileId, upload] of this.uploads) {
			const status = this.findItem(fileId)?.dataset.status;
			if (status !== "uploading" && status !== "paused") continue;
			if (upload.uploadId) this.uploader.suspend(upload.uploadId);
			else this.uploader.cancelFile(upload.file);
		}
		for (const fileId of Array.from(this.cachedBlobUrls.keys())) this.revokeBlobUrls(fileId);
		this.uploads.clear();
		this.fileByFile.clear();
		this.fileProgress.clear();
	}
	hydrate() {
		if (!this.hasResultTarget || !this.resultTarget.value) return;
		try {
			const raw = JSON.parse(this.resultTarget.value);
			const entries = this.multipleValue ? Array.isArray(raw) ? raw : [] : raw && raw.token ? [raw] : [];
			for (const entry of entries) {
				if (!entry.token || !entry.meta) continue;
				const fileId = `file-${++this.fileCounter}`;
				const file = {
					name: entry.meta.filename,
					size: entry.meta.size,
					type: entry.meta.mimeType
				};
				const result = {
					token: entry.token,
					metadata: entry.meta
				};
				this.uploads.set(fileId, {
					file,
					result
				});
				this.createUploadItem(fileId, file);
				this.setStatus(fileId, "completed");
				if (this.showPreviewValue && entry.meta.mimeType?.startsWith("image/")) this.restoreImagePreview(fileId, entry.token, entry.meta.filename);
				else this.showFileTypeIcon(fileId, entry.meta.mimeType, entry.meta.filename);
			}
		} catch {}
		this.updateSummary();
	}
	selectFiles(event) {
		if (this.isDisabled()) return;
		const input = event.target;
		if (input.files) this.addFiles(Array.from(input.files));
		input.value = "";
	}
	browse(event) {
		if (this.isDisabled()) return;
		if (event?.target === this.inputTarget) return;
		if (this.hasInputTarget) this.inputTarget.click();
	}
	paste(event) {
		if (this.isDisabled()) return;
		const files = extractFilesFromClipboard(event);
		if (files.length === 0) return;
		event.preventDefault();
		this.addFiles(files);
	}
	keydown(event) {
		if (event.key === "Enter" || event.key === " ") {
			event.preventDefault();
			this.browse();
		}
	}
	drop(event) {
		event.preventDefault();
		this.dragCounter = 0;
		this.removeDropzoneActive();
		if (this.isDisabled()) return;
		if (event.dataTransfer?.files) this.addFiles(Array.from(event.dataTransfer.files));
	}
	dragover(event) {
		event.preventDefault();
		if (this.isDisabled()) return;
		if (this.dragCounter === 0) {
			this.dragCounter++;
			this.addDropzoneActive();
		}
	}
	dragleave(event) {
		event.preventDefault();
		if (this.isDisabled()) return;
		const dropzone = this.hasDropzoneTarget ? this.dropzoneTarget : null;
		if (dropzone && event.relatedTarget instanceof Node && dropzone.contains(event.relatedTarget)) return;
		this.dragCounter = 0;
		this.removeDropzoneActive();
	}
	cancel(event) {
		if (this.isDisabled()) return;
		this.clearErrors();
		const fileId = this.resolveFileId(event);
		if (!fileId) return;
		const upload = this.uploads.get(fileId);
		const item = this.findItem(fileId);
		if ((item?.dataset.status === "uploading" || item?.dataset.status === "paused") && upload?.uploadId) {
			this.uploader.cancel(upload.uploadId);
			this.setStatus(fileId, "cancelled");
			this.dispatch("cancel", { detail: { fileId } });
		} else if (item?.dataset.status === "uploading" && upload) {
			this.uploader.cancelFile(upload.file);
			this.setStatus(fileId, "cancelled");
			this.dispatch("cancel", { detail: { fileId } });
		} else if (item?.dataset.status === "pending") {
			this.dispatch("cancel", { detail: { fileId } });
			this.removeUpload(fileId);
		}
	}
	async remove(event) {
		if (this.isDisabled()) return;
		this.clearErrors();
		const fileId = this.resolveFileId(event);
		if (!fileId) return;
		const token = this.uploads.get(fileId)?.result?.token;
		if (token) try {
			await this.uploader.remove(token);
		} catch (error) {
			this.addError(error instanceof Error ? error.message : "Failed to remove upload");
			return;
		}
		this.removeUpload(fileId);
	}
	retry(event) {
		if (this.isDisabled()) return;
		this.clearErrors();
		const fileId = this.resolveFileId(event);
		if (!fileId) return;
		const upload = this.uploads.get(fileId);
		const item = this.findItem(fileId);
		if (upload && item?.dataset.status === "error") {
			this.dispatch("retry", { detail: { fileId } });
			this.startUpload(fileId, upload.file);
		}
	}
	pause(event) {
		if (this.isDisabled()) return;
		const fileId = this.resolveFileId(event);
		if (!fileId) return;
		const upload = this.uploads.get(fileId);
		if (this.findItem(fileId)?.dataset.status === "uploading" && upload?.uploadId) {
			this.uploader.pause(upload.uploadId);
			this.setStatus(fileId, "paused");
			this.dispatch("pause", { detail: { fileId } });
		}
	}
	resumeUpload(event) {
		if (this.isDisabled()) return;
		const fileId = this.resolveFileId(event);
		if (!fileId) return;
		const upload = this.uploads.get(fileId);
		if (this.findItem(fileId)?.dataset.status === "paused" && upload?.uploadId) {
			this.uploader.resume(upload.uploadId);
			this.setStatus(fileId, "uploading");
			this.dispatch("resume", { detail: { fileId } });
		}
	}
	startAll() {
		if (this.isDisabled()) return;
		for (const [fileId, upload] of this.uploads) if (this.findItem(fileId)?.dataset.status === "pending") this.startUpload(fileId, upload.file);
	}
	resolveFileId(event) {
		return event.currentTarget.closest("[data-ux-upload-item][data-file-id]")?.dataset.fileId ?? null;
	}
	createEvents() {
		return {
			onInit: (uploadId, file, resumable) => {
				const fileId = this.fileByFile.get(file);
				if (fileId) {
					this.fileByFile.delete(file);
					const upload = this.uploads.get(fileId);
					if (upload) {
						upload.uploadId = uploadId;
						upload.resumable = resumable;
					}
					const item = this.findItem(fileId);
					if (item) this.syncItemActions(item, item.dataset.status ?? "pending");
					this.dispatch("init", { detail: {
						uploadId,
						fileId,
						resumable
					} });
				}
			},
			onProgress: (uploadId, percent, _chunkIndex, speed) => {
				const fileId = this.findFileIdByUploadId(uploadId);
				if (fileId) this.updateProgress(fileId, percent, speed);
			},
			onDirectProgress: (file, percent, speed) => {
				const fileId = this.fileByFile.get(file);
				if (fileId) this.updateProgress(fileId, percent, speed);
			},
			onComplete: (uploadId, result) => {
				const fileId = this.findFileIdByUploadId(uploadId);
				if (fileId) this.completeUpload(fileId, result);
			},
			onError: (uploadId, error) => {
				const fileId = this.findFileIdByUploadId(uploadId);
				if (fileId) this.failUpload(fileId, error.message);
			},
			onChunkComplete: (uploadId, chunkIndex, totalChunks) => {
				this.dispatch("chunk", { detail: {
					uploadId,
					chunkIndex,
					totalChunks
				} });
			}
		};
	}
	addDropzoneActive() {
		if (this.hasDropzoneTarget && !this.isDisabled()) this.dropzoneTarget.classList.add("is-active");
	}
	removeDropzoneActive() {
		if (this.hasDropzoneTarget) this.dropzoneTarget.classList.remove("is-active");
	}
	isDisabled() {
		if (this.element.dataset.uxUploadDisabled === "true") return true;
		if (this.hasInputTarget && this.inputTarget.disabled) return true;
		const fieldset = this.element.closest("fieldset");
		return !!(fieldset && fieldset.hasAttribute("disabled"));
	}
	addFiles(files) {
		if (this.isDisabled()) return;
		this.clearErrors();
		if (this.maxFilesValue > 0) {
			const available = this.maxFilesValue - this.uploads.size;
			if (available <= 0) {
				this.addError(this.labelMaxFilesValue);
				return;
			}
			files = files.slice(0, available);
		}
		for (const file of files) {
			const error = this.validateFile(file);
			if (error) {
				this.addError(error);
				continue;
			}
			const fileId = `file-${++this.fileCounter}`;
			this.uploads.set(fileId, { file });
			this.createUploadItem(fileId, file);
			if (this.showPreviewValue && file.type.startsWith("image/")) {
				const thumbUrl = URL.createObjectURL(file);
				this.showImagePreview(fileId, thumbUrl, file.name);
				this.trackBlobUrl(fileId, thumbUrl);
			} else this.showFileTypeIcon(fileId, file.type, file.name);
			this.dispatch("add", { detail: {
				fileId,
				file: {
					name: file.name,
					size: file.size,
					type: file.type
				}
			} });
			if (this.autoUploadValue) this.startUpload(fileId, file);
		}
		this.updateSummary();
	}
	createUploadItem(fileId, file) {
		if (!this.hasListTarget || !this.hasTemplateTarget) return;
		const clone = this.templateTarget.content.cloneNode(true);
		const item = clone.firstElementChild;
		item.dataset.fileId = fileId;
		item.dataset.progress = "0";
		this.syncItemActions(item, "pending");
		const nameEl = item.querySelector("[data-ux-upload-name]");
		if (nameEl) nameEl.textContent = file.name;
		const sizeEl = item.querySelector("[data-ux-upload-size]");
		if (sizeEl) sizeEl.textContent = formatSize(file.size);
		const progressBar = item.querySelector("[data-ux-upload-progress]");
		if (progressBar) {
			progressBar.setAttribute("role", "progressbar");
			progressBar.setAttribute("aria-valuenow", "0");
			progressBar.setAttribute("aria-valuemin", "0");
			progressBar.setAttribute("aria-valuemax", "100");
		}
		this.listTarget.appendChild(clone);
	}
	updateItemDisplay(item, status, progress) {
		item.dataset.status = status;
		this.syncItemActions(item, status);
		const statusText = item.querySelector("[data-ux-upload-status]");
		if (statusText) switch (status) {
			case "uploading":
				statusText.textContent = `${progress ?? 0}%`;
				break;
			case "completed":
				statusText.textContent = this.labelCompleteValue;
				break;
			case "error": break;
			case "paused":
				statusText.textContent = this.labelPausedValue;
				break;
			case "cancelled":
				statusText.textContent = this.labelCancelledValue;
				break;
			default: statusText.textContent = this.labelPendingValue;
		}
	}
	syncItemActions(item, status) {
		const resumable = (item.dataset.fileId ? this.uploads.get(item.dataset.fileId) : void 0)?.resumable === true;
		const visibleActions = /* @__PURE__ */ new Set();
		if ("uploading" === status && resumable) visibleActions.add("pause");
		if ("paused" === status && resumable) visibleActions.add("resume");
		if ([
			"pending",
			"uploading",
			"paused"
		].includes(status)) visibleActions.add("cancel");
		if (["completed", "cancelled"].includes(status)) visibleActions.add("remove");
		if ("error" === status) visibleActions.add("retry");
		for (const action of item.querySelectorAll("[data-ux-upload-action]")) {
			action.hidden = !visibleActions.has(action.dataset.uxUploadAction ?? "");
			action.disabled = this.isDisabled();
		}
	}
	setStatus(fileId, status, error) {
		const item = this.findItem(fileId);
		if (!item) return;
		const fileName = this.uploads.get(fileId)?.file.name ?? "File";
		this.updateItemDisplay(item, status);
		if (error) {
			const statusText = item.querySelector("[data-ux-upload-status]");
			if (statusText) statusText.textContent = error;
		}
		this.announceStatus(fileName, status, error);
		this.updateSummary();
	}
	findItem(fileId) {
		return this.element.querySelector(`[data-ux-upload-item][data-file-id="${fileId}"]`);
	}
	restoreImagePreview(fileId, token, filename) {
		this.previewCache.retrieve(token).then((blobUrl) => {
			if (!blobUrl) return;
			if (!this.uploads.has(fileId)) {
				URL.revokeObjectURL(blobUrl);
				return;
			}
			this.trackBlobUrl(fileId, blobUrl);
			this.showImagePreview(fileId, blobUrl, filename);
			const item = this.findItem(fileId);
			if (item) {
				const percent = item.querySelector("[data-ux-upload-percent]");
				if (percent) percent.hidden = true;
			}
		});
	}
	validateFile(file) {
		if (this.maxSizeValue > 0 && file.size > this.maxSizeValue) return this.labelFileTooLargeValue.replace("%max_size%", formatSize(this.maxSizeValue));
		if (this.allowedTypesValue.length > 0) {
			if (!this.allowedTypesValue.some((type) => {
				if (type.endsWith("/*")) return file.type.startsWith(type.slice(0, -1));
				return file.type === type || file.name.endsWith(type);
			})) return this.labelFileTypeNotAllowedValue;
		}
		return null;
	}
	async startUpload(fileId, file) {
		const upload = this.uploads.get(fileId);
		if (upload) {
			delete upload.uploadId;
			delete upload.resumable;
		}
		this.setStatus(fileId, "uploading");
		this.dispatch("start", { detail: {
			fileId,
			file: {
				name: file.name,
				size: file.size,
				type: file.type
			}
		} });
		this.fileByFile.set(file, fileId);
		try {
			const result = await this.uploader.upload(file);
			const upload = this.uploads.get(fileId);
			if (upload) upload.result = result;
		} catch (error) {
			if (error instanceof UploadSuspendedError) return;
			if (error instanceof UploadCancelledError) this.setStatus(fileId, "cancelled");
			else {
				const message = error instanceof Error ? error.message : this.labelUploadFailedValue;
				this.failUpload(fileId, message);
			}
		}
	}
	updateProgress(fileId, percent, speed) {
		this.fileProgress.set(fileId, percent);
		this.renderProgress(fileId, percent, speed);
		this.dispatch("progress", { detail: {
			fileId,
			percent,
			speed
		} });
		this.updateSummary();
	}
	completeUpload(fileId, result) {
		const upload = this.uploads.get(fileId);
		if (upload) upload.result = result;
		this.fileProgress.set(fileId, 100);
		this.setStatus(fileId, "completed");
		const item = this.findItem(fileId);
		if (item) {
			item.dataset.progress = "100";
			this.renderProgress(fileId, 100);
		}
		this.updateResultInput();
		if (this.showPreviewValue && upload?.file.type.startsWith("image/")) {
			this.previewCache.store(result.token, upload.file);
			const completedItem = this.findItem(fileId);
			if (completedItem) {
				const percent = completedItem.querySelector("[data-ux-upload-percent]");
				if (percent) percent.hidden = true;
			}
		} else if (upload) this.showFileTypeIcon(fileId, upload.file.type, upload.file.name);
		this.dispatch("complete", { detail: {
			fileId,
			result
		} });
		this.updateSummary();
	}
	failUpload(fileId, error) {
		this.setStatus(fileId, "error", error);
		this.updateResultInput();
		this.dispatch("error", { detail: {
			fileId,
			error
		} });
	}
	trackBlobUrl(fileId, url) {
		const urls = this.cachedBlobUrls.get(fileId);
		if (urls) urls.push(url);
		else this.cachedBlobUrls.set(fileId, [url]);
	}
	revokeBlobUrls(fileId) {
		const urls = this.cachedBlobUrls.get(fileId);
		if (!urls) return;
		this.cachedBlobUrls.delete(fileId);
		for (const url of urls) URL.revokeObjectURL(url);
	}
	removeUpload(fileId) {
		this.uploads.delete(fileId);
		this.fileProgress.delete(fileId);
		this.revokeBlobUrls(fileId);
		const item = this.findItem(fileId);
		this.updateResultInput();
		this.dispatch("remove", { detail: { fileId } });
		this.updateSummary();
		if (item) {
			const supportsAnimation = typeof item.getAnimations === "function";
			const prefersReducedMotion = typeof window !== "undefined" && typeof window.matchMedia === "function" && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
			if (supportsAnimation && !prefersReducedMotion) {
				item.classList.add("is-removing");
				const onDone = () => {
					if (item.parentNode) item.remove();
				};
				item.addEventListener("animationend", onDone, { once: true });
				setTimeout(onDone, 400);
			} else item.remove();
		}
	}
	renderProgress(fileId, percent, speed) {
		const item = this.findItem(fileId);
		if (!item) return;
		const previousPercent = Number(item.dataset.progress ?? "0");
		const duration = this.getProgressAnimationDuration(previousPercent, percent);
		item.dataset.progress = percent.toString();
		item.style.setProperty("--ux-upload-progress-duration", `${duration}s`);
		const progressBar = item.querySelector("[data-ux-upload-progress-bar]");
		if (progressBar) progressBar.style.width = `${percent}%`;
		const progressContainer = item.querySelector("[data-ux-upload-progress]");
		if (progressContainer) progressContainer.setAttribute("aria-valuenow", percent.toString());
		const percentText = item.querySelector("[data-ux-upload-percent]");
		if (percentText) percentText.textContent = `${percent}%`;
		const statusText = item.querySelector("[data-ux-upload-status]");
		if (statusText) {
			let text = `${percent}%`;
			if (speed && percent < 100) {
				const speedStr = formatSpeed(speed.bytesPerSecond);
				const etaStr = formatEta(speed.remainingMs);
				text = `${percent}% \u00B7 ${speedStr}`;
				if (etaStr) text += ` \u00B7 ${etaStr}`;
			}
			statusText.textContent = text;
		}
	}
	updateResultInput() {
		if (!this.hasResultTarget) return;
		const entries = [];
		for (const [, upload] of this.uploads) if (upload.result) entries.push({
			token: upload.result.token,
			meta: upload.result.metadata
		});
		if (this.multipleValue) this.resultTarget.value = entries.length > 0 ? JSON.stringify(entries) : "[]";
		else this.resultTarget.value = entries.length > 0 ? JSON.stringify(entries[0]) : "";
		this.resultTarget.dispatchEvent(new Event("change", { bubbles: true }));
		this.syncRequired();
	}
	findFileIdByUploadId(uploadId) {
		for (const [fileId, upload] of this.uploads) if (upload.uploadId === uploadId) return fileId;
		return null;
	}
	addError(message) {
		if (this.hasErrorTarget) {
			if (this.hasErrorTemplateTarget) {
				const fragment = this.errorTemplateTarget.content.cloneNode(true);
				const item = fragment.firstElementChild;
				if (item) {
					item.textContent = message;
					this.errorTarget.appendChild(fragment);
				}
			} else this.errorTarget.textContent = message;
			this.errorTarget.hidden = false;
		}
		this.dispatch("validation-error", { detail: { message } });
	}
	clearErrors() {
		if (this.hasErrorTarget) {
			this.errorTarget.replaceChildren();
			this.errorTarget.hidden = true;
		}
	}
	syncRequired() {
		if (!this.hasInputTarget || !this.requiredValue) return;
		let hasTokens = false;
		if (this.hasResultTarget && this.resultTarget.value) try {
			const parsed = JSON.parse(this.resultTarget.value);
			if (Array.isArray(parsed)) hasTokens = parsed.some((entry) => !!entry.token);
			else if (parsed && parsed.token) hasTokens = true;
		} catch {
			hasTokens = false;
		}
		this.inputTarget.required = !hasTokens;
	}
	getProgressAnimationDuration(previous, next) {
		const delta = Math.abs(next - previous);
		const base = .4;
		const scaled = delta / 50;
		return Math.min(1.5, Math.max(base, scaled + base));
	}
	updateSummary() {
		this.syncDropzoneAvailability();
		this.syncStartAction();
		if (!this.hasSummaryTarget) return;
		const total = this.uploads.size;
		if (total <= 1) {
			if (this.hasSummaryTextTarget) this.summaryTextTarget.textContent = "";
			if (this.hasSummaryProgressTarget) this.summaryProgressTarget.hidden = true;
			this.summaryTarget.hidden = true;
			this.lastSummaryText = null;
			return;
		}
		let completed = 0;
		let uploading = 0;
		let failed = 0;
		let paused = 0;
		for (const [fileId] of this.uploads) {
			const status = this.findItem(fileId)?.dataset.status;
			if (status === "completed") completed++;
			else if (status === "uploading") uploading++;
			else if (status === "error") failed++;
			else if (status === "paused") paused++;
		}
		let text;
		if (completed === total) text = this.labelSummaryAllCompleteValue.replace("%count%", total.toString());
		else if (uploading > 0 && failed === 0) text = this.labelSummaryUploadingValue.replace("%completed%", completed.toString()).replace("%total%", total.toString());
		else if (failed > 0 && uploading === 0) text = this.labelSummaryPartialValue.replace("%completed%", completed.toString()).replace("%total%", total.toString()).replace("%failed%", failed.toString());
		else if (failed > 0 && uploading > 0) text = this.labelSummaryUploadingWithErrorsValue.replace("%completed%", completed.toString()).replace("%total%", total.toString()).replace("%failed%", failed.toString());
		else text = this.labelSummaryDefaultValue.replace("%completed%", completed.toString()).replace("%total%", total.toString());
		let batchPercent = 0;
		if (completed + uploading + paused > 0) {
			let totalPercent = 0;
			for (const [fileId] of this.uploads) {
				const status = this.findItem(fileId)?.dataset.status;
				if (status === "completed") totalPercent += 100;
				else if (status === "uploading" || status === "paused") totalPercent += this.fileProgress.get(fileId) ?? 0;
			}
			batchPercent = Math.round(totalPercent / total);
		}
		if (text !== this.lastSummaryText) {
			if (this.hasSummaryTextTarget) this.summaryTextTarget.textContent = text;
			else this.summaryTarget.textContent = text;
			this.lastSummaryText = text;
		}
		const showProgress = uploading > 0 || paused > 0;
		if (this.hasSummaryProgressTarget) {
			this.summaryProgressTarget.hidden = !showProgress;
			this.summaryProgressTarget.setAttribute("aria-valuenow", batchPercent.toString());
		}
		if (this.hasSummaryProgressBarTarget) this.summaryProgressBarTarget.style.width = `${batchPercent}%`;
		this.summaryTarget.hidden = false;
	}
	syncStartAction() {
		if (!this.hasStartTarget) return;
		const hasPendingUpload = Array.from(this.uploads).some(([fileId]) => {
			return this.findItem(fileId)?.dataset.status === "pending";
		});
		this.startTarget.hidden = !hasPendingUpload;
		this.startTarget.disabled = this.isDisabled();
	}
	syncDropzoneAvailability() {
		if (!this.hasDropzoneTarget || !this.showPreviewValue || this.maxFilesValue <= 0) return;
		this.dropzoneTarget.hidden = this.uploads.size >= this.maxFilesValue;
	}
	announceStatus(fileName, status, error) {
		if (!this.hasAnnounceTarget) return;
		let announcement;
		switch (status) {
			case "uploading":
				announcement = this.labelAnnounceStartedValue.replace("%filename%", fileName);
				break;
			case "completed":
				announcement = this.labelAnnounceCompleteValue.replace("%filename%", fileName);
				break;
			case "error":
				announcement = this.labelAnnounceFailedValue.replace("%filename%", fileName);
				if (error) announcement += ` - ${error}`;
				break;
			case "paused":
				announcement = this.labelAnnouncePausedValue.replace("%filename%", fileName);
				break;
			case "cancelled":
				announcement = this.labelAnnounceCancelledValue.replace("%filename%", fileName);
				break;
			default: return;
		}
		this.announceTarget.textContent = announcement;
	}
	showImagePreview(fileId, src, filename) {
		const item = this.findItem(fileId);
		if (!item) return;
		const preview = item.querySelector("[data-ux-upload-preview]");
		if (!preview) return;
		preview.src = src;
		preview.alt = filename;
		preview.hidden = false;
		item.querySelector("[data-ux-upload-file-icon]")?.setAttribute("hidden", "");
		item.dataset.preview = "image";
	}
	showFileTypeIcon(fileId, mimeType, filename) {
		const item = this.findItem(fileId);
		if (!item) return;
		const category = getFileIconCategory(mimeType, filename);
		item.dataset.fileType = category;
		item.querySelector("[data-ux-upload-file-icon]")?.removeAttribute("hidden");
		const preview = item.querySelector("[data-ux-upload-preview]");
		if (preview) {
			preview.hidden = true;
			preview.removeAttribute("src");
			preview.alt = "";
		}
	}
};
_defineProperty(_Class, "targets", [
	"input",
	"dropzone",
	"error",
	"errorTemplate",
	"list",
	"result",
	"template",
	"summary",
	"summaryText",
	"summaryProgress",
	"summaryProgressBar",
	"announce",
	"start"
]);
_defineProperty(_Class, "values", {
	directUrl: String,
	chunkSize: {
		type: Number,
		default: 0
	},
	initUrl: String,
	removeUrl: String,
	csrfToken: String,
	maxSize: {
		type: Number,
		default: 0
	},
	maxFiles: {
		type: Number,
		default: 0
	},
	allowedTypes: {
		type: Array,
		default: []
	},
	compression: {
		type: Boolean,
		default: false
	},
	multiple: {
		type: Boolean,
		default: false
	},
	required: {
		type: Boolean,
		default: false
	},
	autoUpload: {
		type: Boolean,
		default: true
	},
	showPreview: {
		type: Boolean,
		default: false
	},
	uploader: {
		type: String,
		default: "default"
	},
	integrityAlgorithm: {
		type: String,
		default: "sha256"
	},
	policyToken: String,
	labelPending: {
		type: String,
		default: "Pending"
	},
	labelComplete: {
		type: String,
		default: "Complete"
	},
	labelCancelled: {
		type: String,
		default: "Cancelled"
	},
	labelUploadFailed: {
		type: String,
		default: "Upload failed"
	},
	labelMaxFiles: {
		type: String,
		default: "Maximum number of files reached"
	},
	labelFileTooLarge: {
		type: String,
		default: "File too large (max %max_size%)"
	},
	labelFileTypeNotAllowed: {
		type: String,
		default: "File type not allowed"
	},
	labelSummaryAllComplete: {
		type: String,
		default: "%count% files uploaded"
	},
	labelSummaryUploading: {
		type: String,
		default: "Uploading… %completed% of %total% complete"
	},
	labelSummaryPartial: {
		type: String,
		default: "%completed% of %total% uploaded, %failed% failed"
	},
	labelSummaryUploadingWithErrors: {
		type: String,
		default: "Uploading… %completed% of %total% complete, %failed% failed"
	},
	labelSummaryDefault: {
		type: String,
		default: "%completed% of %total% files uploaded"
	},
	labelAnnounceStarted: {
		type: String,
		default: "%filename%: upload started"
	},
	labelAnnounceComplete: {
		type: String,
		default: "%filename%: upload complete"
	},
	labelAnnounceFailed: {
		type: String,
		default: "%filename%: upload failed"
	},
	labelAnnounceCancelled: {
		type: String,
		default: "%filename%: upload cancelled"
	},
	labelPaused: {
		type: String,
		default: "Paused"
	},
	labelAnnouncePaused: {
		type: String,
		default: "%filename%: upload paused"
	},
	labelAnnounceResumed: {
		type: String,
		default: "%filename%: upload resumed"
	}
});
export { extractFilesFromClipboard as n, renameAnonymousFile as r, _Class as t };
