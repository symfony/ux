function registerSvelteControllerComponents(context) {
    const svelteControllers = {};
    const importAllSvelteComponents = (r) => {
        r.keys().forEach((key) => {
            svelteControllers[key] = r(key).default;
        });
    };
    importAllSvelteComponents(context);
    window.resolveSvelteComponent = (name) => {
        const component = svelteControllers[`./${name}.svelte`];
        if (typeof component === 'undefined') {
            throw new Error(`Svelte controller "${name}" does not exist`);
        }
        return component;
    };
}

export { registerSvelteControllerComponents };
