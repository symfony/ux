import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    async initialize() {
        this.component = await getComponent(this.element);
        this.component.on('render:finished', (component) => {
            if (this.element.dataset.open) {
                this.element.showModal();
                this.element.open = true
            }
        });
    }

    connect() {
        window.addEventListener('Icon:Clicked', this.onIconClick.bind(this));
        this.element.addEventListener('click', this.onClick.bind(this));
    }

    disconnect() {
        this.element.removeEventListener('click', this.onClick.bind(this));
        window.removeEventListener('Icon:Clicked', this.onIconClick.bind(this));
    }

    show() {
        if (!this.element.open) {
            this.element.showModal();
            this.element.open = true;
        }
    }

    close() {
        if (this.element.open) {
            this.element.dataset.open = false;
            this.element.close();
            this.element.open = false;

            const input = this.element.querySelector('input');
            input.value = '';
        }
    }

    onIconClick(event) {
        event.preventDefault();
        event.stopPropagation();
        const input = this.element.querySelector('input');
        input.value = event.detail.icon;
        input.dispatchEvent(new Event('change', {bubbles: true}));
        this.show();
    }

    onClick(event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        const dialogDimensions = this.element.getBoundingClientRect()
        if (
            event.clientX < dialogDimensions.left ||
            event.clientX > dialogDimensions.right ||
            event.clientY < dialogDimensions.top ||
            event.clientY > dialogDimensions.bottom
        ) {
            this.close()
        }
    }
}
