import { defineAsyncComponent } from 'vue';

function registerVueControllerComponents(context) {
    const vueControllers = context.keys().reduce((acc, key) => {
        acc[key] = undefined;
        return acc;
    }, {});
    function loadComponent(name) {
        const componentPath = `./${name}.vue`;
        if (!(componentPath in vueControllers)) {
            const possibleValues = Object.keys(vueControllers).map((key) => key.replace('./', '').replace('.vue', ''));
            throw new Error(`Vue controller "${name}" does not exist. Possible values: ${possibleValues.join(', ')}`);
        }
        if (typeof vueControllers[componentPath] === 'undefined') {
            const module = context(componentPath);
            if (module.default) {
                vueControllers[componentPath] = module.default;
            }
            else if (module instanceof Promise) {
                vueControllers[componentPath] = defineAsyncComponent(() => new Promise((resolve, reject) => {
                    module
                        .then((resolvedModule) => {
                        if (resolvedModule.default) {
                            resolve(resolvedModule.default);
                        }
                        else {
                            reject(new Error(`Cannot find default export in async Vue controller "${name}".`));
                        }
                    })
                        .catch(reject);
                }));
            }
            else {
                throw new Error(`Vue controller "${name}" does not exist.`);
            }
        }
        return vueControllers[componentPath];
    }
    window.resolveVueComponent = (name) => {
        return loadComponent(name);
    };
}

export { registerVueControllerComponents };
