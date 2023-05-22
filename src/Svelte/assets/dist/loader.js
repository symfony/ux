import { components } from './components.js';

function registerSvelteControllerComponents(svelteComponents = components) {
    window.resolveSvelteComponent = (name) => {
        const component = svelteComponents[name];
        if (typeof component === 'undefined') {
            const possibleValues = Object.keys(svelteComponents).length > 0 ? Object.keys(svelteComponents).join(', ') : 'none';
            throw new Error(`Svelte controller "${name}" does not exist. Possible values: ${possibleValues}`);
        }
        return component;
    };
}

export { registerSvelteControllerComponents };
