import { Controller } from '@hotwired/stimulus';

/**
 * @value  autoClose  Delay in milliseconds before the widget closes.
 * @value  open       Whether the widget is open on initial render.
 * @target panel      The panel shown or hidden by the widget.
 * @action toggle     Toggles the widget open state.
 */
export default class extends Controller {
    static values = {
        autoClose: Number,
        open: { type: Boolean, default: false },
    };
    static targets = ['panel'];

    toggle() {}
}
