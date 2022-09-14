function registerVueControllerComponents(contexts) {
    const vueControllers = {};
    const importAllVueComponents = (r) => {
        r.keys().forEach((key) => (vueControllers[key] = r(key).default));
    };

    [].concat(contexts).forEach((context) => importAllVueComponents(context));

    window.resolveVueComponent = (name) => {
        const component = Object.values(
            Object.fromEntries(
                Object.entries(vueControllers).filter(([key]) => key.endsWith(`${name}.vue`)))
        )[0];

        if (typeof component === 'undefined') {
            throw new Error('Vue controller "' + name + '" does not exist');
        }
        return component;
    };
}

export { registerVueControllerComponents };
