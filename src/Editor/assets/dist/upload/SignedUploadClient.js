var SignedUploadClient = class {
	constructor(url, options) {
		this.url = url;
		this.options = options;
	}
	async upload(file, filename) {
		const fd = new FormData();
		fd.append("file", file, filename);
		fd.append("field", this.options.field);
		const res = await fetch(this.url, {
			method: "POST",
			body: fd
		});
		const text = await res.text();
		let payload = {};
		try {
			payload = text ? JSON.parse(text) : {};
		} catch {}
		if (!res.ok) {
			const err = new Error(payload.message ?? `Upload failed: ${res.status}`);
			err.status = res.status;
			err.code = payload.error ?? "unknown_error";
			throw err;
		}
		return payload;
	}
};
export { SignedUploadClient };
