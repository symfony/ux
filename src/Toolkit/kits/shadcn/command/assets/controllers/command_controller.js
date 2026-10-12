import { Controller } from '@hotwired/stimulus';

let instances = 0;

export default class extends Controller {
    static targets = ['input', 'list', 'item', 'group', 'separator', 'empty'];

    #prefix = `command-${++instances}`;
    #nextItemId = 0;
    #dialog = null;
    #scrollPending = false;

    // A command in a dialog starts over each time the dialog is closed.
    // The closed dialog is not rendered, so its list cannot scroll yet: that waits until the reopened dialog focuses the input.
    #reset = () => {
        for (const input of this.inputTargets) {
            input.value = '';
        }

        this.#scrollPending = true;
        this.filter();
    };

    #scrollToTop = () => {
        if (!this.#scrollPending) {
            return;
        }

        this.#scrollPending = false;
        for (const list of this.listTargets) {
            list.scrollTop = 0;
        }
    };

    connect() {
        this.#identify();
        this.filter();

        this.#dialog = this.element.closest('dialog');
        this.#dialog?.addEventListener('close', this.#reset);
        this.element.addEventListener('focusin', this.#scrollToTop);
    }

    disconnect() {
        this.#dialog?.removeEventListener('close', this.#reset);
        this.#dialog = null;
        this.element.removeEventListener('focusin', this.#scrollToTop);
    }

    // Items are addressed by `aria-activedescendant`, which needs an id.
    // The ids are generated here, so the markup needs no id prop and they stay unique when a page renders several commands.
    itemTargetConnected(item) {
        item.id ||= `${this.#prefix}-item-${this.#nextItemId++}`;
    }

    filter() {
        const query = (this.inputTargets[0]?.value ?? '').trim().toLowerCase();
        let matched = 0;

        for (const item of this.itemTargets) {
            const matches = '' === query || item.textContent.toLowerCase().includes(query);
            item.hidden = !matches;
            matched += matches ? 1 : 0;
        }

        for (const group of this.groupTargets) {
            group.hidden = !this.itemTargets.some((item) => !item.hidden && group.contains(item));
        }

        for (const separator of this.separatorTargets) {
            separator.hidden = '' !== query;
        }

        for (const empty of this.emptyTargets) {
            empty.hidden = matched > 0;
        }

        this.#select(this.#items()[0]);
    }

    highlight(event) {
        const item = event.currentTarget;
        if ('true' === item.dataset.disabled || item === this.#selected) {
            return;
        }

        this.#select(item, { scroll: false });
    }

    navigate(event) {
        if (event.defaultPrevented || event.isComposing || 229 === event.keyCode) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown':
                this.#move(event, 1);
                break;
            case 'ArrowUp':
                this.#move(event, -1);
                break;
            case 'n':
            case 'j':
                if (event.ctrlKey) {
                    this.#move(event, 1);
                }
                break;
            case 'p':
            case 'k':
                if (event.ctrlKey) {
                    this.#move(event, -1);
                }
                break;
            case 'Home':
                event.preventDefault();
                this.#select(this.#items()[0]);
                break;
            case 'End':
                event.preventDefault();
                this.#select(this.#items().at(-1));
                break;
            case 'Enter':
                event.preventDefault();
                this.#selected?.click();
                break;
        }
    }

    get #selected() {
        return this.itemTargets.find((item) => 'true' === item.dataset.selected) ?? null;
    }

    #items() {
        return this.itemTargets.filter((item) => !item.hidden && 'true' !== item.dataset.disabled);
    }

    // The Meta key jumps to the first or last item, the Alt key to the next or previous group.
    #move(event, step) {
        event.preventDefault();

        const items = this.#items();
        if (event.metaKey) {
            this.#select(step > 0 ? items.at(-1) : items[0]);

            return;
        }

        if (event.altKey) {
            const target = this.#itemInSiblingGroup(step);
            if (null !== target) {
                this.#select(target);

                return;
            }
        }

        const next = items[items.indexOf(this.#selected) + step];
        if (undefined !== next) {
            this.#select(next);
        }
    }

    #itemInSiblingGroup(step) {
        const groups = this.groupTargets.filter((group) => !group.hidden);
        const current = this.#selected?.closest('[data-slot="command-group"]');

        for (let index = groups.indexOf(current) + step; index >= 0 && index < groups.length; index += step) {
            const item = this.#items().find((candidate) => groups[index].contains(candidate));
            if (undefined !== item) {
                return item;
            }
        }

        return null;
    }

    #select(item, { scroll = true } = {}) {
        for (const other of this.itemTargets) {
            other.dataset.selected = String(other === item);
            other.setAttribute('aria-selected', String(other === item));
        }

        for (const input of this.inputTargets) {
            if (undefined === item) {
                input.removeAttribute('aria-activedescendant');
            } else {
                input.setAttribute('aria-activedescendant', item.id);
            }
        }

        if (scroll && undefined !== item) {
            this.#scrollIntoList(item);
        }
    }

    // Unlike `scrollIntoView()`, this only scrolls the list: the page must not jump to a command selecting its first item.
    #scrollIntoList(item) {
        const list = item.closest('[data-slot="command-list"]');
        if (null === list || 0 === list.offsetHeight) {
            return;
        }

        // The boxes are scaled while a dialog plays its opening transition: bring the offset back to the layout size.
        const listBox = list.getBoundingClientRect();
        const scale = listBox.height / list.offsetHeight;
        const top = list.scrollTop + (item.getBoundingClientRect().top - listBox.top) / scale - list.clientTop;
        const bottom = top + item.offsetHeight;
        if (top < list.scrollTop) {
            list.scrollTop = top;
        } else if (bottom > list.scrollTop + list.clientHeight) {
            list.scrollTop = bottom - list.clientHeight;
        }
    }

    #identify() {
        for (const list of this.listTargets) {
            list.id ||= `${this.#prefix}-list`;
        }

        if (this.hasListTarget) {
            for (const input of this.inputTargets) {
                input.setAttribute('aria-controls', this.listTarget.id);
            }
        }
    }
}
