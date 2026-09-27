import '/turbo.js';
import { Application, Controller } from '/stimulus.js';

const application = Application.start();
application.register(
    'probe',
    class extends Controller {
        static values = { count: Number };
        increment() {
            this.countValue++;
            this.dispatch('changed', { detail: { count: this.countValue } });
        }
    }
);
window.Stimulus = application;
document.addEventListener('turbo:before-visit', (event) => {
    if (new URL(event.detail.url).pathname === '/cancel') event.preventDefault();
});
