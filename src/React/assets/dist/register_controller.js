function registerReactControllerComponents(context) {
    const reactControllers = {};
    const importAllReactComponents = (r) => {
        r.keys().forEach((key) => (reactControllers[key] = r(key).default));
    };
    importAllReactComponents(context);
    window.resolveReactComponent = (name) => {
        const component = reactControllers[`./${name}.jsx`] || reactControllers[`./${name}.tsx`];
        if (typeof component === 'undefined') {
            const possibleValues = Object.keys(reactControllers).map((key) => key.replace('./', '').replace('.jsx', '').replace('.tsx', ''));
            throw new Error(`React controller "${name}" does not exist. Possible values: ${possibleValues.join(', ')}`);
        }
        return component;
    };
}

export { registerReactControllerComponents };
