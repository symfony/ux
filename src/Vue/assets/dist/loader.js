import { components } from './components.js';

function registerVueControllerComponents(vueControllers = components) {
    function loadComponent(name) {
        const component = vueControllers[name];
        if (typeof component === 'undefined') {
            const possibleValues = Object.keys(vueControllers).length > 0 ? Object.keys(vueControllers).join(', ') : 'none';
            throw new Error(`Vue controller "${name}" does not exist. Possible values: ${possibleValues}`);
        }
        return component;
    }
    window.resolveVueComponent = (name) => {
        return loadComponent(name);
    };
}

export { registerVueControllerComponents };
