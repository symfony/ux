function registerVueControllerComponents(context) {
    const vueControllers = {};
    const importAllVueComponents = (r) => {
        r.keys().forEach((key) => (vueControllers[key] = r(key).default));
    };
    importAllVueComponents(context);
    window.resolveVueComponent = (name) => {
        const component = vueControllers[`./${name}.vue`];
        if (typeof component === 'undefined') {
            throw new Error(`Vue controller "${name}" does not exist`);
        }
        return component;
    };
}

export { registerVueControllerComponents };
