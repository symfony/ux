function registerReactControllerComponents(context) {
    const reactControllers = {};
    const importAllReactComponents = (r) => {
        r.keys().forEach((key) => (reactControllers[key] = r(key).default));
    };
    importAllReactComponents(context);
    window.resolveReactComponent = (name) => {
        const component = reactControllers[`./${name}.jsx`] || reactControllers[`./${name}.tsx`];
        if (typeof component === 'undefined') {
            throw new Error('React controller "' + name + '" does not exist');
        }
        return component;
    };
}

export { registerReactControllerComponents };
