import { defineAsyncComponent } from "vue";
function registerVueControllerComponents(context) {
	const loaders = {};
	if (typeof context === "function") context.keys().forEach((key) => {
		loaders[key] = () => context(key);
	});
	else {
		const keyMapping = normalizeGlobKeys(Object.keys(context));
		Object.entries(context).forEach(([key, value]) => {
			loaders[keyMapping[key]] = typeof value === "function" ? value : () => value;
		});
	}
	const vueControllers = {};
	function loadComponent(name) {
		const componentPath = `./${name}.vue`;
		if (!(componentPath in loaders)) {
			const possibleValues = Object.keys(loaders).map((key) => key.replace("./", "").replace(".vue", ""));
			throw new Error(`Vue controller "${name}" does not exist. Possible values: ${possibleValues.join(", ")}`);
		}
		if (typeof vueControllers[componentPath] === "undefined") {
			const module = loaders[componentPath]();
			if (module.default) vueControllers[componentPath] = module.default;
			else if (module instanceof Promise) vueControllers[componentPath] = defineAsyncComponent(() => new Promise((resolve, reject) => {
				module.then((resolvedModule) => {
					if (resolvedModule.default) resolve(resolvedModule.default);
					else reject(/* @__PURE__ */ new Error(`Cannot find default export in async Vue controller "${name}".`));
				}).catch(reject);
			}));
			else throw new Error(`Vue controller "${name}" does not exist.`);
		}
		return vueControllers[componentPath];
	}
	window.resolveVueComponent = (name) => {
		return loadComponent(name);
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
export { registerVueControllerComponents };
