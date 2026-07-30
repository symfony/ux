import { Controller } from '@hotwired/stimulus';
import { computePosition, autoUpdate, offset, flip, shift, arrow } from '@floating-ui/dom';

let sequence = 0;

const OPPOSITE_SIDE = { top: 'bottom', right: 'left', bottom: 'top', left: 'right' };

/**
 * @value  content    The text shown inside the tooltip; falls back to the element's `title` when omitted. Any `title` is removed regardless, so the browser's native tooltip never competes with this one.
 * @value  placement  Preferred side of the trigger: `top` (default), `bottom`, `left` or `right`. Flips and shifts to stay in view.
 * @value  trigger    How the tooltip opens: `hover` (on hover and keyboard focus, the default), `click` (toggle on click), or `manual` (you drive `show`/`hide` yourself). Defaults to `hover`.
 * @value  autoHide   Delay in milliseconds after which a shown tooltip hides itself; when 0, it stays until `hide`. Defaults to 0.
 * @action show       Shows the tooltip, scheduling an automatic hide when `autoHide` is set.
 * @action hide       Hides the tooltip.
 * @action toggle     Shows the tooltip when hidden, hides it when shown.
 */
export default class extends Controller {
    static values = {
        content: String,
        placement: { type: String, default: 'top' },
        trigger: { type: String, default: 'hover' },
        autoHide: Number,
    };

    #tooltip;
    #timeout;
    #triggers; // AbortController for the auto-wired hover/click listeners
    #cleanup; // stops Floating UI's autoUpdate while the tooltip is hidden

    connect() {
        // Remove a tooltip a previous instance left in <body> (e.g. after a Turbo restore).
        const previous = this.element.getAttribute('aria-describedby');
        if (previous) {
            document.getElementById(previous)?.remove();
        }

        this.#tooltip = this.#build();
        // The tooltip lives in <body>, so associate it with the trigger by id.
        this.element.setAttribute('aria-describedby', this.#tooltip.id);
        this.#wire();
    }

    disconnect() {
        this.#triggers?.abort();
        this.#cleanup?.();
        this.#tooltip.remove();
    }

    show() {
        clearTimeout(this.#timeout);
        this.#tooltip.hidden = false;
        // Keep the tooltip anchored while visible: reposition on scroll, resize and layout shifts.
        this.#cleanup = autoUpdate(this.element, this.#tooltip, () => this.#position());

        if (this.autoHideValue) {
            this.#timeout = setTimeout(() => this.hide(), this.autoHideValue);
        }
    }

    hide() {
        clearTimeout(this.#timeout);
        this.#cleanup?.();
        this.#cleanup = null;
        this.#tooltip.hidden = true;
    }

    toggle() {
        if (this.#tooltip.hidden) {
            this.show();
            return;
        }

        this.hide();
    }

    #build() {
        const tooltip = document.createElement('span');
        tooltip.id = `tooltip-${++sequence}`;
        tooltip.className = 'tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.hidden = true;

        const arrowElement = document.createElement('span');
        arrowElement.className = 'tooltip-arrow';

        tooltip.append(this.#content(), arrowElement);
        document.body.appendChild(tooltip);

        return tooltip;
    }

    #position() {
        const arrowElement = this.#tooltip.querySelector('.tooltip-arrow');

        computePosition(this.element, this.#tooltip, {
            placement: this.placementValue,
            middleware: [offset(8), flip(), shift({ padding: 8 }), arrow({ element: arrowElement })],
        }).then(({ x, y, placement, middlewareData }) => {
            Object.assign(this.#tooltip.style, { left: `${x}px`, top: `${y}px` });

            const { x: arrowX, y: arrowY } = middlewareData.arrow;
            const staticSide = OPPOSITE_SIDE[placement.split('-')[0]];
            Object.assign(arrowElement.style, {
                left: null != arrowX ? `${arrowX}px` : '',
                top: null != arrowY ? `${arrowY}px` : '',
                right: '',
                bottom: '',
                [staticSide]: '-4px',
            });
        });
    }

    #content() {
        // Always remove a `title` (even when `content` is set) so the browser's native tooltip never
        // competes with ours; fall back to its text only when no `content` value is given.
        this.#stashTitle();

        if (this.hasContentValue) {
            return this.contentValue;
        }

        return this.element.getAttribute('data-tooltip-original-title') ?? '';
    }

    // Move a native `title` into a data attribute the first time we see it: the browser's own tooltip
    // stops showing, and the text survives a Turbo snapshot/restore (a removed `title` would not).
    #stashTitle() {
        const title = this.element.getAttribute('title');
        if (null === title) {
            return;
        }

        this.element.removeAttribute('title');
        this.element.setAttribute('data-tooltip-original-title', title);
    }

    // Standard hover (plus keyboard focus) is the default, so the common case needs no `data-action`.
    #wire() {
        if ('manual' === this.triggerValue) {
            return;
        }

        this.#triggers = new AbortController();
        const options = { signal: this.#triggers.signal };

        if ('click' === this.triggerValue) {
            this.element.addEventListener('click', () => this.toggle(), options);

            return;
        }

        this.element.addEventListener('mouseenter', () => this.show(), options);
        this.element.addEventListener('mouseleave', () => this.hide(), options);
        this.element.addEventListener('focusin', () => this.show(), options);
        this.element.addEventListener('focusout', () => this.hide(), options);
    }
}
