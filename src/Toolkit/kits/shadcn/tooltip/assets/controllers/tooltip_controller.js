import { Controller } from '@hotwired/stimulus';

// The native path needs both: a popover without anchor positioning would be centred by the browser.
const native = HTMLElement.prototype.hasOwnProperty('popover') && CSS.supports('position-area', 'block-end');
let anchors = 0;

// The side of the wrapper facing the trigger, which carries the gap between them on the native path.
const facingMargins = { top: 'marginBottom', bottom: 'marginTop', left: 'marginRight', right: 'marginLeft' };

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

    #anchor = `--tooltip-${++anchors}`;
    #anchoredTrigger = null;

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

        this.wrapperElement.addEventListener('beforetoggle', this.#onBeforeToggle);
        if (this.#anchored) {
            this.#connectNative();
        } else {
            document.body.appendChild(this.wrapperElement);
        }
        this.initialized = true;
    }

    disconnect() {
        this.hide();
        this.#clearTimeouts();
        this.#removeDismissListeners();
        this.wrapperElement?.removeEventListener('beforetoggle', this.#onBeforeToggle);
        this.#disconnectNative();

        if (this.wrapperElement && this.wrapperElement.parentNode === document.body) {
            this.element.appendChild(this.wrapperElement);
        }
    }

    wrapperTargetDisconnected(element) {
        // Moving the wrapper to `<body>` also disconnects it as a target: only a removed one is torn down.
        if (element === this.wrapperElement && !element.isConnected) {
            this.#clearTimeouts();
            element.removeEventListener('beforetoggle', this.#onBeforeToggle);
            this.#disconnectNative();
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
        if (!this.initialized || !this.wrapperElement.isConnected) {
            return;
        }

        this.#clearTimeouts();

        const delay = this.hasDelayDurationValue ? this.delayDurationValue : 0;

        this.showTimeout = setTimeout(() => {
            this.showTimeout = null;
            if (this.#anchored) {
                this.#connectNative();
            }
            this.#showPopover();
            this.wrapperElement.setAttribute('open', '');
            this.contentElement.setAttribute('open', '');
            this.arrowElement.setAttribute('open', '');
            this.#fitWidthToText();
            if (this.#anchored) {
                this.#placeNative();

                return;
            }
            this.#positionElements();
            // The tooltip is portaled to <body> and positioned absolutely, so it cannot follow
            // the trigger on scroll. Dismiss it instead (capture scrolls from any scroller).
            window.addEventListener('scroll', this.dismiss, true);
            window.addEventListener('resize', this.dismiss);
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
        this.#hidePopover();
    }

    get #anchored() {
        return native && this.hasTriggerTarget;
    }

    // Idempotent, as a Turbo morph strips what it sets.
    #connectNative() {
        this.#anchoredTrigger = this.triggerTarget;
        this.wrapperElement.dataset.anchored = '';
        this.wrapperElement.style.positionAnchor = this.#anchor;
        this.#anchoredTrigger.style.setProperty('anchor-name', this.#anchor);
    }

    #disconnectNative() {
        if (!native) {
            return;
        }
        if (this.wrapperElement) {
            delete this.wrapperElement.dataset.anchored;
            for (const property of ['position-anchor', 'translate', 'margin']) {
                this.wrapperElement.style.removeProperty(property);
            }
        }
        this.#anchoredTrigger?.style.removeProperty('anchor-name');
        this.#anchoredTrigger = null;
    }

    // Applies the gap and the arrow offset of the fallback placement to the placement of the browser.
    #placeNative() {
        const wrapper = this.wrapperElement;
        const arrowRect = this.arrowElement.getBoundingClientRect();
        const vertical = this.side === 'top' || this.side === 'bottom';
        if (this.side in facingMargins) {
            wrapper.style[facingMargins[this.side]] =
                `${(vertical ? arrowRect.height : arrowRect.width) / 2 + this.sideOffset}px`;
        }
        if (!vertical) {
            return;
        }

        // Shifted by the same amount, the wrapper stays in the viewport and the arrow on the centre of the trigger.
        wrapper.style.translate = '-50% 0';
        const box = wrapper.getBoundingClientRect();
        const margin = 16;
        const left = Math.max(margin, Math.min(box.left, document.documentElement.clientWidth - box.width - margin));
        const shift = left - box.left;
        wrapper.style.translate = `calc(-50% + ${shift}px) 0`;
        this.arrowElement.style.left = `${this.contentElement.offsetWidth / 2 - arrowRect.width / 2 - shift}px`;
    }

    #onBeforeToggle = (event) => {
        if ('closed' !== event.newState || !this.wrapperElement.hasAttribute('open')) {
            return;
        }
        this.#clearTimeouts();
        this.#removeDismissListeners();
        this.wrapperElement.removeAttribute('open');
        this.contentElement.removeAttribute('open');
        this.arrowElement.removeAttribute('open');
    };

    // In the top layer, the tooltip is displayed above a modal dialog that contains its trigger.
    // The wrapper keeps `display: block` when it is not open, so the opening and closing transitions still run.
    #showPopover() {
        if (this.#isPopover() && !this.wrapperElement.matches(':popover-open')) {
            this.wrapperElement.showPopover();
        }
    }

    #hidePopover() {
        if (this.#isPopover() && this.wrapperElement.matches(':popover-open')) {
            this.wrapperElement.hidePopover();
        }
    }

    // Browsers without the Popover API ignore the attribute: the tooltip then stays below a modal dialog.
    #isPopover() {
        return this.wrapperElement.hasAttribute('popover') && 'function' === typeof this.wrapperElement.showPopover;
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
