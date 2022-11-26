// Controller used to check the actual controller was properly booted
import {Application, Controller} from '@hotwired/stimulus';
import SvelteController from '../../src/render_controller';
import MyComponent from '../fixtures/MyComponent.svelte';

class CheckController extends Controller {
    connect() {
        this.element.addEventListener('svelte:connect', () => {
            this.element.classList.add('connected');
        });

        this.element.addEventListener('svelte:mount', () => {
            this.element.classList.add('mounted');
        });
    }
}

export const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('svelte', SvelteController);
};

(window as any).resolveSvelteComponent = () => {
    return MyComponent;
};