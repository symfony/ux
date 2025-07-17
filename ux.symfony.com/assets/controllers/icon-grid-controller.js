import {Controller} from '@hotwired/stimulus';
import {delegate} from 'tippy.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    connect() {
        this.element.addEventListener('click', this.click.bind(this), true);
        this.tippy = delegate(this.element, {
            target: '.IconCard',
            content: (reference) => '<button title="Copy Icon name" data-controller="clipboarder" data-action="clipboarder#copy"  data-clipboarder-target="button source">'
                + '<span>'
                + reference.title.split(':').join('</span>:<span>')
                + '</span>'
                + '</button>',
            arrow: true,
            theme: 'translucent',
            animation: 'scale',
            inertia: true,
            allowHTML: true,
            onShow: () => {
                if (document.body.dataset.iconSize !== 'small') {
                    return false;
                }
            },
            interactive: true,
            delay: [250, 0],
        });
    }

    disconnect() {
        this.tippy.unmount();
        this.tippy.destroy();
        this.element.removeEventListener('click', this.click.bind(this), true);
    }

    click(event) {
        const iconCard = event.target.closest('.IconCard');
        if (!iconCard) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();

        const customEvent = new CustomEvent('Icon:Clicked', { detail: { icon: iconCard.title }, bubbles: true });
        window.dispatchEvent(customEvent);
    }

}
