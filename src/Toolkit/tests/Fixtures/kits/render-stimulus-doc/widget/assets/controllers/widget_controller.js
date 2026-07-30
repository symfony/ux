import { Controller } from '@hotwired/stimulus';

/**
 * @value     autoClose  Delay in milliseconds before the widget closes.
 * @value     open       Whether the widget is open on initial render.
 * @target    panel      The panel shown or hidden by the widget.
 * @css-class open       Applied to the widget while it is open.
 * @outlet    status     Linked status controller updated when the widget toggles.
 * @action    toggle     Toggles the widget open state.
 */
export default class extends Controller {
    static values = {
        autoClose: Number,
        open: { type: Boolean, default: false },
    };
    static targets = ['panel'];
    static classes = ['open'];
    static outlets = ['status'];

    toggle() {}
}
