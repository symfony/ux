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
    static targets = ['trigger', 'wrapper'];

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
        this.dismiss = this.hide.bind(this);

        document.body.appendChild(this.wrapperElement);
        this.initialized = true;
    }

    disconnect() {
        this.#clearTimeouts();
        this.#removeDismissListeners();

        if (this.wrapperElement && this.wrapperElement.parentNode === document.body) {
            this.element.appendChild(this.wrapperElement);
        }
    }

    wrapperTargetConnected(element) {
        // This case appear when live component rerender.
        // Because original wrapper is moved on body, the Smart rerender algorithm recreate a new wrapper.
        // The same wrapper comes back when the controller reconnects, as disconnect() moves it back.
        if (this.wrapperElement && element !== this.wrapperElement) {
            this.wrapperElement.remove();
            this.connect();
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
            this.#fitWidthToText();
            this.#positionElements();
            // The tooltip is portaled to <body> and positioned absolutely, so it cannot follow
            // the trigger on scroll. Dismiss it instead (capture scrolls from any scroller).
            window.addEventListener('scroll', this.dismiss, true);
            window.addEventListener('resize', this.dismiss);
            this.showTimeout = null;
        }, delay);
    }

    hide() {
        if (!this.initialized) {
            return;
        }

        this.#clearTimeouts();
        this.#removeDismissListeners();
        this.wrapperElement.removeAttribute('open');
        this.contentElement.removeAttribute('open');
        this.arrowElement.removeAttribute('open');
    }

    // A shrink-to-fit box that wraps its text keeps its max width, leaving a gap after the longest line.
    // Size the content to that line instead.
    #fitWidthToText() {
        const content = this.contentElement;
        content.style.width = '';
        const range = document.createRange();
        let left = Infinity;
        let right = -Infinity;
        for (const node of content.childNodes) {
            if (node === this.arrowElement) {
                continue;
            }
            range.selectNodeContents(node);
            for (const rect of range.getClientRects()) {
                left = Math.min(left, rect.left);
                right = Math.max(right, rect.right);
            }
        }
        if (right <= left) {
            return;
        }
        // The rects are shrunk by the opening animation (scale-95), scale them back to the layout size.
        const scale = content.getBoundingClientRect().width / content.offsetWidth;
        const style = getComputedStyle(content);
        const padding = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight);
        // The extra pixel keeps a sub-pixel rounding from wrapping the last word.
        content.style.width = `${Math.ceil((right - left) / scale + padding) + 1}px`;
    }

    #removeDismissListeners() {
        window.removeEventListener('scroll', this.dismiss, true);
        window.removeEventListener('resize', this.dismiss);
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
        // Unlike getBoundingClientRect(), offsetWidth/offsetHeight ignore the opening animation (scale-95).
        const contentRect = { width: this.contentElement.offsetWidth, height: this.contentElement.offsetHeight };
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

        // Keep a top or bottom tooltip inside the viewport, and move the arrow so it still points at the trigger.
        if (arrowLeft !== null) {
            const margin = 16;
            const maxLeft = document.documentElement.clientWidth - contentRect.width - margin;
            const clampedLeft = Math.max(margin, Math.min(wrapperLeft, maxLeft));
            arrowLeft += wrapperLeft - clampedLeft;
            wrapperLeft = clampedLeft;
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
