import { components } from './components.js';

function registerReactControllerComponents(reactComponents = components) {
    window.resolveReactComponent = (name) => {
        const component = reactComponents[name];
        if (typeof component === 'undefined') {
            const possibleValues = Object.keys(reactComponents).length > 0 ? Object.keys(reactComponents).join(', ') : 'none';
            throw new Error(`React controller "${name}" does not exist. Possible values: ${possibleValues}`);
        }
        return component;
    };
}

export { registerReactControllerComponents };
