import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/**
 * @value  animation     How long, in milliseconds, the items take to slide into their new place while sorting; 0 disables the animation.
 * @value  handle        CSS selector of the drag handle inside each item; when omitted, an item is dragged from anywhere.
 * @value  draggable     CSS selector of the items that can be dragged; when omitted, the `li` children of a list, or all the children of any other element.
 * @value  filter        CSS selector of the elements that do not start a drag when pressed, like the buttons inside the items.
 * @value  paramName     Name of the field that carries the new position of the moved item among the draggable ones, starting at 1; a value like `task[position]` nests it like a Symfony form field.
 * @value  csrfToken     CSRF token sent with the request as the `_token` parameter, e.g. from `csrf_token('reorder-tasks')`.
 */
export default class extends Controller {
    static values = {
        animation: { type: Number, default: 150 },
        handle: String,
        draggable: String,
        filter: String,
        paramName: { type: String, default: 'position' },
        csrfToken: String,
    };

    #sortable;
    #saves = Promise.resolve();

    connect() {
        this.#sortable = new Sortable(this.element, {
            animation: this.animationValue,
            handle: this.handleValue || undefined,
            filter: this.filterValue || undefined,
            // SortableJS only falls back to its default, depending on the element, when the option is missing.
            ...(this.draggableValue && { draggable: this.draggableValue }),
            // Fires only when an item lands at a new index, so dropping it back in place sends no request.
            onUpdate: (event) => {
                this.#saves = this.#saves.catch(() => {}).then(() => this.#save(event));
            },
        });
    }

    disconnect() {
        this.#sortable.destroy();
    }

    async #save({ item, newDraggableIndex }) {
        const url = item.dataset.sortableUpdateUrl;
        if (!url) {
            return;
        }

        const body = new FormData();
        body.append(this.paramNameValue, String(newDraggableIndex + 1));
        if (this.hasCsrfTokenValue) {
            body.append('_token', this.csrfTokenValue);
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: window.Turbo
                    ? 'text/vnd.turbo-stream.html, text/html, application/xhtml+xml'
                    : 'text/html, application/xhtml+xml',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
        });

        if (response.headers.get('Content-Type')?.startsWith('text/vnd.turbo-stream.html')) {
            window.Turbo?.renderStreamMessage(await response.text());
        }
    }
}
