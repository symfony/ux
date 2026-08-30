import { t as _defineProperty } from "./defineProperty-B6pPL0VL.js";
var ResumeStore = class {
	constructor() {
		_defineProperty(this, "database", "symfony-ux-upload");
		_defineProperty(this, "store", "resume");
	}
	async get(fingerprint) {
		const db = await this.open();
		if (!db) return null;
		try {
			return await new Promise((resolve) => {
				const request = db.transaction(this.store, "readonly").objectStore(this.store).get(fingerprint);
				request.onsuccess = () => {
					const record = request.result;
					resolve(record && record.expiresAt > Date.now() ? record : null);
				};
				request.onerror = () => resolve(null);
			});
		} finally {
			db.close();
		}
	}
	async put(record) {
		const db = await this.open();
		if (!db) return;
		try {
			await new Promise((resolve) => {
				const request = db.transaction(this.store, "readwrite").objectStore(this.store).put(record);
				request.onsuccess = () => resolve();
				request.onerror = () => resolve();
			});
		} finally {
			db.close();
		}
	}
	async delete(fingerprint) {
		const db = await this.open();
		if (!db) return;
		try {
			await new Promise((resolve) => {
				const request = db.transaction(this.store, "readwrite").objectStore(this.store).delete(fingerprint);
				request.onsuccess = () => resolve();
				request.onerror = () => resolve();
			});
		} finally {
			db.close();
		}
	}
	async open() {
		if (!("indexedDB" in globalThis)) return null;
		return new Promise((resolve) => {
			const request = indexedDB.open(this.database, 1);
			request.onupgradeneeded = () => request.result.createObjectStore(this.store, { keyPath: "fingerprint" });
			request.onsuccess = () => resolve(request.result);
			request.onerror = () => resolve(null);
		});
	}
};
var UploadCancelledError = class extends Error {
	constructor() {
		super("Upload cancelled");
		this.name = "UploadCancelledError";
	}
};
var UploadSuspendedError = class extends Error {
	constructor() {
		super("Upload suspended");
		this.name = "UploadSuspendedError";
	}
};
const WEB_CRYPTO_ALGORITHMS = {
	sha256: "SHA-256",
	sha384: "SHA-384",
	sha512: "SHA-512"
};
const MAX_BROWSER_FILE_HASH_SIZE = 64 * 1024 * 1024;
function isAbortError(error) {
	return (error instanceof DOMException || error instanceof Error) && error.name === "AbortError";
}
var Uploader = class {
	constructor(optionsOrInitUrl, events = {}, uploaderName = "default", csrfToken = null, integrityAlgorithm = "sha256", policyToken = null, compressionEnabled = false) {
		_defineProperty(this, "resumeStore", new ResumeStore());
		_defineProperty(this, "abortControllers", /* @__PURE__ */ new Map());
		_defineProperty(this, "uploadUrls", /* @__PURE__ */ new Map());
		_defineProperty(this, "uploadFingerprints", /* @__PURE__ */ new Map());
		_defineProperty(this, "abortReasons", /* @__PURE__ */ new Map());
		_defineProperty(this, "pauseFlags", /* @__PURE__ */ new Map());
		_defineProperty(this, "pauseResolvers", /* @__PURE__ */ new Map());
		_defineProperty(this, "speedSamples", /* @__PURE__ */ new Map());
		_defineProperty(this, "uploadFileSizes", /* @__PURE__ */ new Map());
		_defineProperty(this, "initUrl", void 0);
		_defineProperty(this, "directUrl", void 0);
		_defineProperty(this, "directUploadThreshold", void 0);
		_defineProperty(this, "removeUrl", void 0);
		_defineProperty(this, "events", void 0);
		_defineProperty(this, "uploaderName", void 0);
		_defineProperty(this, "csrfToken", void 0);
		_defineProperty(this, "integrityAlgorithm", void 0);
		_defineProperty(this, "policyToken", void 0);
		_defineProperty(this, "compressionEnabled", void 0);
		_defineProperty(this, "credentials", void 0);
		_defineProperty(this, "headers", void 0);
		_defineProperty(this, "fetcher", void 0);
		_defineProperty(this, "fileAbortControllers", /* @__PURE__ */ new WeakMap());
		_defineProperty(this, "xhrFactory", void 0);
		_defineProperty(this, "useXhrForDirect", void 0);
		const options = typeof optionsOrInitUrl === "string" ? {
			initUrl: optionsOrInitUrl,
			events,
			uploader: uploaderName,
			csrfToken,
			integrityAlgorithm,
			policyToken,
			compression: compressionEnabled
		} : optionsOrInitUrl;
		this.initUrl = options.initUrl;
		this.directUrl = options.directUrl;
		this.directUploadThreshold = options.directUploadThreshold ?? 0;
		this.removeUrl = options.removeUrl ?? options.initUrl.replace(/\/init$/, "/remove");
		this.events = options.events ?? {};
		this.uploaderName = options.uploader ?? "default";
		this.csrfToken = options.csrfToken ?? null;
		this.integrityAlgorithm = options.integrityAlgorithm ?? "sha256";
		this.policyToken = options.policyToken ?? null;
		this.compressionEnabled = options.compression ?? false;
		this.credentials = options.credentials;
		this.headers = options.headers ?? {};
		this.fetcher = options.fetch ?? ((input, init) => fetch(input, init));
		this.xhrFactory = options.xhr ?? (() => new XMLHttpRequest());
		this.useXhrForDirect = options.xhr !== void 0 || options.fetch === void 0 && options.credentials !== "omit";
	}
	async upload(file) {
		const abortController = new AbortController();
		const signal = abortController.signal;
		this.fileAbortControllers.set(file, abortController);
		try {
			const hash = await this.computeFileHash(file);
			this.throwIfAborted(signal);
			if (this.usesDirectUpload(file)) try {
				return await this.uploadDirect(file, signal, hash);
			} catch (error) {
				if (!(error instanceof DirectUploadFallbackError)) throw error;
			}
			const fingerprint = await this.fingerprint(file);
			const initResponse = await this.resumeUpload(fingerprint) ?? await this.initializeUpload(file, signal, hash);
			this.throwIfAborted(signal);
			const { uploadId, uploadUrl, config } = initResponse;
			config.compression = config.compression && this.compressionEnabled;
			if (initResponse.resumeToken) await this.resumeStore.put({
				fingerprint,
				token: initResponse.resumeToken,
				expiresAt: Date.now() + 1440 * 60 * 1e3
			});
			this.events.onInit?.(uploadId, file, true);
			this.abortControllers.set(uploadId, abortController);
			this.uploadUrls.set(uploadId, uploadUrl);
			this.uploadFingerprints.set(uploadId, fingerprint);
			this.speedSamples.set(uploadId, []);
			this.uploadFileSizes.set(uploadId, file.size);
			try {
				const existingChunks = await this.checkResume(uploadUrl);
				const chunksToUpload = [];
				for (let i = 0; i < config.totalChunks; i++) if (!existingChunks.includes(i)) chunksToUpload.push(i);
				await this.uploadChunks(file, uploadId, uploadUrl, chunksToUpload, config, signal, existingChunks.length);
				const result = await this.completeUpload(uploadUrl, uploadId, signal);
				await this.resumeStore.delete(fingerprint);
				this.events.onComplete?.(uploadId, result);
				return result;
			} catch (error) {
				if (isAbortError(error)) {
					const reason = this.abortReasons.get(uploadId);
					if (reason === "cancel") {
						await this.resumeStore.delete(fingerprint);
						throw new UploadCancelledError();
					}
					if (reason === "suspend") throw new UploadSuspendedError();
					throw new UploadCancelledError();
				}
				this.events.onError?.(uploadId, error);
				throw error;
			} finally {
				this.abortControllers.delete(uploadId);
				this.uploadUrls.delete(uploadId);
				this.uploadFingerprints.delete(uploadId);
				this.abortReasons.delete(uploadId);
				this.pauseFlags.delete(uploadId);
				this.pauseResolvers.delete(uploadId);
				this.speedSamples.delete(uploadId);
				this.uploadFileSizes.delete(uploadId);
			}
		} catch (error) {
			if (isAbortError(error)) throw new UploadCancelledError();
			throw error;
		} finally {
			this.fileAbortControllers.delete(file);
		}
	}
	usesDirectUpload(file) {
		return Boolean(this.directUrl) && this.directUploadThreshold > 0 && file.size <= this.directUploadThreshold;
	}
	cancelFile(file) {
		this.fileAbortControllers.get(file)?.abort();
	}
	cancel(uploadId) {
		this.abortReasons.set(uploadId, "cancel");
		const controller = this.abortControllers.get(uploadId);
		if (controller) {
			controller.abort();
			this.abortControllers.delete(uploadId);
		}
		this.resumeIfPaused(uploadId);
		const uploadUrl = this.uploadUrls.get(uploadId);
		if (uploadUrl) {
			this.request(uploadUrl, { method: "DELETE" }).catch(() => {});
			this.uploadUrls.delete(uploadId);
		}
		const fingerprint = this.uploadFingerprints.get(uploadId);
		if (fingerprint) this.resumeStore.delete(fingerprint).catch(() => {});
	}
	suspend(uploadId) {
		this.abortReasons.set(uploadId, "suspend");
		this.abortControllers.get(uploadId)?.abort();
		this.resumeIfPaused(uploadId);
	}
	async remove(token) {
		const response = await this.request(this.removeUrl, {
			method: "DELETE",
			headers: {
				"Content-Type": "application/json",
				...this.csrfToken ? { "X-CSRF-Token": this.csrfToken } : {}
			},
			body: JSON.stringify({
				token,
				...this.policyToken ? { policyToken: this.policyToken } : {}
			})
		});
		if (!response.ok) {
			let message = "Failed to remove upload";
			try {
				const error = await response.json();
				if (error.error) message = error.error;
			} catch {}
			throw new Error(message);
		}
	}
	pause(uploadId) {
		this.pauseFlags.set(uploadId, true);
	}
	resume(uploadId) {
		this.resumeIfPaused(uploadId);
	}
	isPaused(uploadId) {
		return this.pauseFlags.get(uploadId) === true;
	}
	async initializeUpload(file, signal, hash) {
		const response = await this.request(this.initUrl, {
			method: "POST",
			signal,
			headers: {
				"Content-Type": "application/json",
				...this.csrfToken ? { "X-CSRF-Token": this.csrfToken } : {}
			},
			body: JSON.stringify({
				filename: file.name,
				fileSize: file.size,
				mimeType: file.type || "application/octet-stream",
				...this.uploaderName !== "default" ? { uploader: this.uploaderName } : {},
				...hash ? { hash } : {},
				...hash ? { hashAlgorithm: this.integrityAlgorithm } : {},
				...this.policyToken ? { policyToken: this.policyToken } : {}
			})
		});
		if (!response.ok) {
			let message = "Failed to initialize upload";
			try {
				const error = await response.json();
				if (error.error) message = error.error;
			} catch {}
			throw new Error(message);
		}
		return response.json();
	}
	async uploadDirect(file, signal, hash) {
		const directUrl = this.directUrl;
		if (!directUrl) throw new DirectUploadFallbackError();
		const originalData = new Uint8Array(await file.arrayBuffer());
		const digest = await this.digestBuffer(originalData, "SHA-256");
		let transmittedData = originalData;
		let compressed = false;
		if (this.compressionEnabled && this.isCompressionSupported() && !this.isAlreadyCompressed(file.type)) try {
			transmittedData = await this.compress(originalData);
			compressed = true;
		} catch {
			transmittedData = originalData;
		}
		const body = new FormData();
		body.append("file", new Blob([transmittedData]), file.name);
		body.append("filename", file.name);
		body.append("fileSize", file.size.toString());
		body.append("mimeType", file.type || "application/octet-stream");
		body.append("digest", digest);
		if (compressed) body.append("contentEncoding", "gzip");
		if (this.uploaderName !== "default") body.append("uploader", this.uploaderName);
		if (hash) {
			body.append("hash", hash);
			body.append("hashAlgorithm", this.integrityAlgorithm);
		}
		if (this.policyToken) body.append("policyToken", this.policyToken);
		try {
			const data = this.useXhrForDirect ? await this.uploadDirectWithXhr(file, directUrl, body, signal) : await this.uploadDirectWithFetch(directUrl, body, signal);
			if (!data?.uploadId) throw new Error("Direct upload response is missing its upload ID");
			const result = {
				uploadId: data.uploadId,
				token: data.token,
				metadata: data.meta
			};
			this.events.onInit?.(data.uploadId, file, false);
			this.events.onProgress?.(data.uploadId, 100, 0);
			this.events.onComplete?.(data.uploadId, result);
			return result;
		} catch (error) {
			if (isAbortError(error)) throw new UploadCancelledError();
			throw error;
		}
	}
	uploadDirectWithXhr(file, directUrl, body, signal) {
		return new Promise((resolve, reject) => {
			const xhr = this.xhrFactory();
			const startedAt = performance.now();
			const rejectNetworkError = () => reject(/* @__PURE__ */ new TypeError("Direct upload network request failed"));
			const rejectAbort = () => reject(new UploadCancelledError());
			xhr.open("POST", directUrl);
			xhr.withCredentials = this.credentials === "include";
			for (const [name, value] of Object.entries(this.headers)) xhr.setRequestHeader(name, value);
			if (this.csrfToken) xhr.setRequestHeader("X-CSRF-Token", this.csrfToken);
			xhr.upload.addEventListener("progress", (event) => {
				if (!event.lengthComputable || event.total <= 0) return;
				const elapsedMs = Math.max(performance.now() - startedAt, 1);
				const bytesPerSecond = Math.round(event.loaded * 1e3 / elapsedMs);
				const percent = Math.min(Math.round(event.loaded / event.total * 100), 100);
				const remainingMs = bytesPerSecond > 0 ? Math.round((event.total - event.loaded) / bytesPerSecond * 1e3) : 0;
				this.events.onDirectProgress?.(file, percent, {
					bytesPerSecond,
					remainingMs
				});
			});
			xhr.addEventListener("load", () => {
				if (413 === xhr.status) {
					reject(new DirectUploadFallbackError());
					return;
				}
				const data = this.parseDirectResponse(xhr.responseText);
				if (xhr.status < 200 || xhr.status >= 300) {
					reject(new Error(this.readDirectError(data)));
					return;
				}
				resolve(data);
			});
			xhr.addEventListener("error", rejectNetworkError);
			xhr.addEventListener("abort", rejectAbort);
			if (signal.aborted) {
				rejectAbort();
				return;
			}
			signal.addEventListener("abort", () => xhr.abort(), { once: true });
			xhr.send(body);
		});
	}
	async uploadDirectWithFetch(directUrl, body, signal) {
		const response = await this.request(directUrl, {
			method: "POST",
			body,
			signal
		});
		if (413 === response.status) throw new DirectUploadFallbackError();
		let data;
		try {
			data = await response.json();
		} catch {
			data = null;
		}
		if (!response.ok) throw new Error(this.readDirectError(data));
		return data;
	}
	parseDirectResponse(responseText) {
		try {
			return JSON.parse(responseText);
		} catch {
			return null;
		}
	}
	readDirectError(data) {
		if (data && typeof data === "object" && "error" in data && typeof data.error === "string") return data.error;
		return "Direct upload failed";
	}
	async resumeUpload(fingerprint) {
		const record = await this.resumeStore.get(fingerprint);
		if (!record) return null;
		try {
			const response = await this.request(this.initUrl.replace(/\/init$/, "/resume"), {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({
					resumeToken: record.token,
					...this.policyToken ? { policyToken: this.policyToken } : {}
				})
			});
			if (response.ok) return response.json();
		} catch {}
		await this.resumeStore.delete(fingerprint);
		return null;
	}
	async fingerprint(file) {
		const sampleSize = Math.min(64 * 1024, file.size);
		const first = new Uint8Array(await file.slice(0, sampleSize).arrayBuffer());
		const last = new Uint8Array(await file.slice(Math.max(0, file.size - sampleSize)).arrayBuffer());
		const sample = new Uint8Array(first.length + last.length);
		sample.set(first);
		sample.set(last, first.length);
		const policyFingerprint = this.policyToken ? await this.digestBuffer(new TextEncoder().encode(this.policyToken), "SHA-256") : "none";
		const scope = new TextEncoder().encode(`${this.initUrl}\0${this.uploaderName}\0${policyFingerprint}\0${this.integrityAlgorithm}`);
		return `v2:${await this.digestBuffer(scope, "SHA-256")}:${file.name}:${file.size}:${file.lastModified}:${await this.digestBuffer(sample, "SHA-256")}`;
	}
	async checkResume(uploadUrl) {
		try {
			const response = await this.request(uploadUrl, { method: "GET" });
			if (response.ok) return (await response.json()).progress?.chunkIndices || [];
		} catch {}
		return [];
	}
	async uploadChunks(file, uploadId, uploadUrl, chunks, config, signal, existingCount) {
		const { chunkSize, parallel, compression, totalChunks } = config;
		if (!Array.isArray(chunks)) chunks = [];
		let completedCount = existingCount;
		for (let i = 0; i < chunks.length; i += parallel) {
			await this.waitIfPaused(uploadId);
			this.throwIfAborted(signal);
			const batch = chunks.slice(i, i + parallel);
			await Promise.all(batch.map(async (chunkIndex) => {
				await this.uploadChunk(file, uploadId, uploadUrl, chunkIndex, chunkSize, compression, totalChunks, signal);
				completedCount++;
				const percent = Math.min(Math.round(completedCount / totalChunks * 100), 100);
				const chunkBytes = Math.min(chunkSize, file.size - chunkIndex * chunkSize);
				const speed = this.recordSpeedSample(uploadId, chunkBytes, percent);
				this.events.onProgress?.(uploadId, percent, chunkIndex, speed);
				this.events.onChunkComplete?.(uploadId, chunkIndex, totalChunks);
			}));
		}
	}
	async uploadChunk(file, uploadId, uploadUrl, chunkIndex, chunkSize, compression, totalChunks, signal, attempt = 0) {
		const maxRetries = 3;
		const start = chunkIndex * chunkSize;
		const end = Math.min(start + chunkSize, file.size);
		let data = await file.slice(start, end).arrayBuffer();
		const chunkDigest = await this.digestBuffer(data, "SHA-256");
		const headers = {
			"Content-Type": "application/octet-stream",
			"X-Chunk-Index": chunkIndex.toString(),
			"X-Chunk-Digest": chunkDigest
		};
		if (compression && this.isCompressionSupported() && !this.isAlreadyCompressed(file.type)) try {
			data = await this.compress(new Uint8Array(data));
			headers["Content-Encoding"] = "gzip";
		} catch {}
		try {
			const response = await this.request(uploadUrl, {
				method: "PUT",
				headers,
				body: data,
				signal
			});
			if (!response.ok) {
				let message = response.statusText;
				try {
					const body = await response.json();
					if (body.error) message = body.error;
				} catch {}
				throw new ChunkUploadError(`Chunk upload failed: ${message}`, response.status);
			}
		} catch (error) {
			if (error instanceof DOMException && error.name === "AbortError") throw error;
			if (!(error instanceof ChunkUploadError && error.status >= 400 && error.status < 500 && error.status !== 408 && error.status !== 429) && attempt < maxRetries) {
				const delay = Math.pow(2, attempt) * 1e3;
				await this.sleep(delay, signal);
				return this.uploadChunk(file, uploadId, uploadUrl, chunkIndex, chunkSize, compression, totalChunks, signal, attempt + 1);
			}
			throw error;
		}
	}
	async completeUpload(uploadUrl, uploadId, signal) {
		this.throwIfAborted(signal);
		const response = await this.request(uploadUrl, {
			method: "POST",
			signal,
			headers: {
				"Content-Type": "application/json",
				...this.csrfToken ? { "X-CSRF-Token": this.csrfToken } : {}
			}
		});
		if (!response.ok) {
			let message = "Failed to complete upload";
			try {
				const error = await response.json();
				if (error.error) message = error.error;
			} catch {}
			throw new Error(message);
		}
		const data = await response.json();
		this.throwIfAborted(signal);
		return {
			uploadId: data.uploadId ?? uploadId,
			token: data.token,
			metadata: data.meta
		};
	}
	request(input, init = {}) {
		const method = (init.method ?? "GET").toUpperCase();
		const csrfHeaders = {};
		if (this.csrfToken && method !== "GET" && method !== "HEAD") csrfHeaders["X-CSRF-Token"] = this.csrfToken;
		return this.fetcher(input, {
			...init,
			...this.credentials ? { credentials: this.credentials } : {},
			headers: {
				...this.headers,
				...csrfHeaders,
				...init.headers
			}
		});
	}
	isCompressionSupported() {
		return typeof CompressionStream !== "undefined";
	}
	isAlreadyCompressed(mimeType) {
		return [
			"image/jpeg",
			"image/png",
			"image/gif",
			"image/webp",
			"video/",
			"audio/",
			"application/zip",
			"application/gzip",
			"application/x-rar",
			"application/x-7z"
		].some((type) => mimeType.startsWith(type) || mimeType === type);
	}
	async compress(data) {
		const reader = new ReadableStream({ start(controller) {
			controller.enqueue(data);
			controller.close();
		} }).pipeThrough(new CompressionStream("gzip")).getReader();
		const chunks = [];
		while (true) {
			const { done, value } = await reader.read();
			if (done) break;
			chunks.push(value);
		}
		const totalLength = chunks.reduce((acc, chunk) => acc + chunk.length, 0);
		const result = new Uint8Array(totalLength);
		let offset = 0;
		for (const chunk of chunks) {
			result.set(chunk, offset);
			offset += chunk.length;
		}
		return result;
	}
	recordSpeedSample(uploadId, chunkBytes, percent) {
		const samples = this.speedSamples.get(uploadId);
		if (!samples) return void 0;
		const now = Date.now();
		samples.push({
			time: now,
			bytes: chunkBytes
		});
		const cutoff = now - 1e4;
		while (samples.length > 1 && samples[0].time < cutoff) samples.shift();
		if (samples.length < 2) return void 0;
		const elapsed = (samples[samples.length - 1].time - samples[0].time) / 1e3;
		if (elapsed <= 0) return void 0;
		const bytesPerSecond = samples.reduce((sum, s) => sum + s.bytes, 0) / elapsed;
		if (bytesPerSecond <= 0) return void 0;
		return {
			bytesPerSecond,
			remainingMs: (this.uploadFileSizes.get(uploadId) ?? 0) * (1 - percent / 100) / bytesPerSecond * 1e3
		};
	}
	async computeFileHash(file) {
		if (file.size > MAX_BROWSER_FILE_HASH_SIZE) return;
		if (typeof crypto === "undefined" || !crypto.subtle) return;
		try {
			const buffer = await file.arrayBuffer();
			const hashBuffer = await crypto.subtle.digest(WEB_CRYPTO_ALGORITHMS[this.integrityAlgorithm], buffer);
			return Array.from(new Uint8Array(hashBuffer)).map((b) => b.toString(16).padStart(2, "0")).join("");
		} catch {
			return;
		}
	}
	async digestBuffer(data, algorithm) {
		const bytes = data instanceof Uint8Array ? data : new Uint8Array(data);
		const digest = await crypto.subtle.digest(algorithm, bytes);
		return Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, "0")).join("");
	}
	sleep(ms, signal) {
		return new Promise((resolve, reject) => {
			if (signal.aborted) {
				reject(new DOMException("Upload cancelled", "AbortError"));
				return;
			}
			const onAbort = () => {
				clearTimeout(timer);
				reject(new DOMException("Upload cancelled", "AbortError"));
			};
			const timer = setTimeout(() => {
				signal.removeEventListener("abort", onAbort);
				resolve();
			}, ms);
			signal.addEventListener("abort", onAbort, { once: true });
		});
	}
	throwIfAborted(signal) {
		if (signal.aborted) throw new DOMException("Upload cancelled", "AbortError");
	}
	waitIfPaused(uploadId) {
		if (!this.pauseFlags.get(uploadId)) return Promise.resolve();
		return new Promise((resolve) => {
			this.pauseResolvers.set(uploadId, resolve);
		});
	}
	resumeIfPaused(uploadId) {
		this.pauseFlags.delete(uploadId);
		const resolver = this.pauseResolvers.get(uploadId);
		if (resolver) {
			this.pauseResolvers.delete(uploadId);
			resolver();
		}
	}
};
var DirectUploadFallbackError = class extends Error {};
var ChunkUploadError = class extends Error {
	constructor(message, status) {
		super(message);
		this.status = status;
	}
};
export { UploadSuspendedError as n, Uploader as r, UploadCancelledError as t };
