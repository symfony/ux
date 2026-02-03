import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        delayDuration: Number,
        // Using targets does not work if the elements are moved in the DOM (document.body.appendChild)
        // and using outlets does not work either if elements are children of the controller element.
        wrapperSelector: String,
        contentSelector: String,
        arrowSelector: String,
    };
    static targets = ['trigger'];

    connect() {
        this.initialized = false;
        this.wrapperElement = document.querySelector(this.wrapperSelectorValue);
        this.contentElement = document.querySelector(this.contentSelectorValue);
        this.arrowElement = document.querySelector(this.arrowSelectorValue);

        if (!this.wrapperElement || !this.contentElement || !this.arrowElement) {
            return;
        }

        this.side = this.wrapperElement.getAttribute('data-side') || 'top';
        this.sideOffset = parseInt(this.wrapperElement.getAttribute('data-side-offset'), 10) || 0;

        this.showTimeout = null;
        this.hideTimeout = null;

        document.body.appendChild(this.wrapperElement);
        this.initialized = true;
    }

    disconnect() {
        this.#clearTimeouts();

        if (this.wrapperElement && this.wrapperElement.parentNode === document.body) {
            this.element.appendChild(this.wrapperElement);
        }
    }

    show() {
        if (!this.initialized) {
            return;
        }

        this.#clearTimeouts();

        const delay = this.hasDelayDurationValue ? this.delayDurationValue : 0;

        this.showTimeout = setTimeout(() => {
            this.wrapperElement.setAttribute('open', '');
            this.contentElement.setAttribute('open', '');
            this.arrowElement.setAttribute('open', '');
            this.#positionElements();
            this.showTimeout = null;
        }, delay);
    }

    hide() {
        if (!this.initialized) {
            return;
        }

        this.#clearTimeouts();
        this.wrapperElement.removeAttribute('open');
        this.contentElement.removeAttribute('open');
        this.arrowElement.removeAttribute('open');
    }

    #clearTimeouts() {
        if (this.showTimeout) {
            clearTimeout(this.showTimeout);
            this.showTimeout = null;
        }
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }
    }

    #positionElements() {
        const triggerRect = this.triggerTarget.getBoundingClientRect();
        const contentRect = this.contentElement.getBoundingClientRect();
        const arrowRect = this.arrowElement.getBoundingClientRect();

        let wrapperLeft = 0;
        let wrapperTop = 0;
        let arrowLeft = null;
        let arrowTop = null;
        switch (this.side) {
            case 'left':
                wrapperLeft = triggerRect.left - contentRect.width - arrowRect.width / 2 - this.sideOffset;
                wrapperTop = triggerRect.top - contentRect.height / 2 + triggerRect.height / 2;
                arrowTop = contentRect.height / 2 - arrowRect.height / 2;
                break;
            case 'top':
                wrapperLeft = triggerRect.left - contentRect.width / 2 + triggerRect.width / 2;
                wrapperTop = triggerRect.top - contentRect.height - arrowRect.height / 2 - this.sideOffset;
                arrowLeft = contentRect.width / 2 - arrowRect.width / 2;
                break;
            case 'right':
                wrapperLeft = triggerRect.right + arrowRect.width / 2 + this.sideOffset;
                wrapperTop = triggerRect.top - contentRect.height / 2 + triggerRect.height / 2;
                arrowTop = contentRect.height / 2 - arrowRect.height / 2;
                break;
            case 'bottom':
                wrapperLeft = triggerRect.left - contentRect.width / 2 + triggerRect.width / 2;
                wrapperTop = triggerRect.bottom + arrowRect.height / 2 + this.sideOffset;
                arrowLeft = contentRect.width / 2 - arrowRect.width / 2;
                break;
        }

        this.wrapperElement.style.transform = `translate3d(${wrapperLeft}px, ${wrapperTop}px, 0)`;
        if (arrowLeft !== null) {
            this.arrowElement.style.left = `${arrowLeft}px`;
        }
        if (arrowTop !== null) {
            this.arrowElement.style.top = `${arrowTop}px`;
        }
    }
}
