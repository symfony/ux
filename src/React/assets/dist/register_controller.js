function registerReactControllerComponents(context) {
	const reactControllers = {};
	if (typeof context === "function") context.keys().forEach((key) => {
		reactControllers[key] = context(key).default;
	});
	else {
		const keyMapping = normalizeGlobKeys(Object.keys(context));
		Object.entries(context).forEach(([key, module]) => {
			if (typeof module === "function") throw new Error(`React controller "${key}" could not be registered from a lazy "import.meta.glob()". Enable the "eager" option, e.g. import.meta.glob('./react/controllers/**/*.{jsx,tsx}', { eager: true }).`);
			reactControllers[keyMapping[key]] = module.default;
		});
	}
	window.resolveReactComponent = (name) => {
		const component = reactControllers[`./${name}.jsx`] || reactControllers[`./${name}.tsx`];
		if (typeof component === "undefined") {
			const possibleValues = Object.keys(reactControllers).map((key) => key.replace("./", "").replace(".jsx", "").replace(".tsx", ""));
			if (possibleValues.includes(name)) throw new Error(`
                    React controller "${name}" could not be resolved. Ensure the module exports the controller as a default export.`);
			throw new Error(`React controller "${name}" does not exist. Possible values: ${possibleValues.join(", ")}`);
		}
		return component;
	};
}
function normalizeGlobKeys(keys) {
	if (keys.length === 0) return {};
	const segments = keys.map((key) => key.split("/"));
	let commonLength = Math.min(...segments.map((parts) => parts.length - 1));
	for (let i = 0; i < commonLength; i++) {
		const segment = segments[0][i];
		if (!segments.every((parts) => parts[i] === segment)) {
			commonLength = i;
			break;
		}
	}
	const mapping = {};
	keys.forEach((key, index) => {
		mapping[key] = `./${segments[index].slice(commonLength).join("/")}`;
	});
	return mapping;
}
export { registerReactControllerComponents };
