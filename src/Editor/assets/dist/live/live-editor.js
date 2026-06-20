function setupAutosave(root, opts) {
	const debounceMs = opts.debounceMs ?? 800;
	let timer;
	let lastValue;
	const handler = (e) => {
		lastValue = e.detail?.value;
		if (timer !== void 0) clearTimeout(timer);
		timer = setTimeout(() => {
			timer = void 0;
			opts.dispatch(opts.field, lastValue).catch(() => {});
		}, debounceMs);
	};
	root.addEventListener("ux:editor:change", handler);
	return () => {
		root.removeEventListener("ux:editor:change", handler);
		if (timer !== void 0) clearTimeout(timer);
	};
}
export { setupAutosave };
