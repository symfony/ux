import { Controller } from '@hotwired/stimulus';
import { Dropdown } from 'flowbite';

export default class extends Controller {
    dropdown = null;
    static targets = ['trigger', 'content'];
    static values = {
        placement: String,
        triggerType: String,
        open: Boolean,
        delay: Number,
        offsetDistance: Number,
    };

    connect() {
        const options = {
            placement: this.placementValue,
            triggerType: this.triggerTypeValue,
            delay: this.delayValue,
            offsetDistance: this.offsetDistanceValue,
        };
        this.dropdown = new Dropdown(this.contentTarget, this.triggerTarget, options);
        this.dropdown.updateOnShow(() => {
            this.triggerTarget.setAttribute('aria-expanded', 'true');
        });
        this.dropdown.updateOnHide(() => {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
        });

        this._onTriggerKeydown = this._handleTriggerKeydown.bind(this);
        this._onContentKeydown = this._handleContentKeydown.bind(this);
        this.triggerTarget.addEventListener('keydown', this._onTriggerKeydown);
        this.contentTarget.addEventListener('keydown', this._onContentKeydown);

        if (this.openValue) {
            this.dropdown.show();
        }
    }

    disconnect() {
        this.triggerTarget.removeEventListener('keydown', this._onTriggerKeydown);
        this.contentTarget.removeEventListener('keydown', this._onContentKeydown);
        this.dropdown?.destroy();
        this.dropdown = null;
    }

    _getMenuItems() {
        // Get menuitems belonging to this menu level only (not nested submenu contents).
        // Items can be: li > a[role="menuitem"] (Item) or li > div[data-controller] > button[role="menuitem"] (SubTrigger)
        const items = [];
        for (const item of this.contentTarget.querySelectorAll('[role="menuitem"]')) {
            if (item.closest('[data-dropdown-target="content"]') === this.contentTarget) {
                items.push(item);
            }
        }
        return items;
    }

    _focusItem(index) {
        const items = this._getMenuItems();
        if (items.length === 0) return;
        const i = ((index % items.length) + items.length) % items.length;
        items[i].focus();
    }

    _focusFirstItem() {
        this._focusItem(0);
    }

    _focusLastItem() {
        this._focusItem(-1);
    }

    _show(focusFirst = true) {
        this.dropdown.show();
        if (focusFirst) {
            // Small delay to ensure the content is visible before focusing
            requestAnimationFrame(() => this._focusFirstItem());
        }
    }

    _hide() {
        this.dropdown.hide();
        this.triggerTarget.focus();
    }

    _handleTriggerKeydown(event) {
        // If this trigger is a SubTrigger (role="menuitem"), arrow keys should be
        // handled by the parent menu for navigation, not by this submenu's trigger.
        const isSubTrigger = this.triggerTarget.getAttribute('role') === 'menuitem';

        switch (event.key) {
            case 'ArrowDown':
                if (isSubTrigger) return;
                if (!this.dropdown.isVisible()) {
                    event.preventDefault();
                    this._show(true);
                }
                break;
            case 'ArrowUp':
                if (isSubTrigger) return;
                if (!this.dropdown.isVisible()) {
                    event.preventDefault();
                    this.dropdown.show();
                    requestAnimationFrame(() => this._focusLastItem());
                }
                break;
            case 'Enter':
            case ' ':
                if (!this.dropdown.isVisible()) {
                    event.preventDefault();
                    this._show(true);
                }
                break;
            case 'Escape':
                if (this.dropdown.isVisible()) {
                    event.preventDefault();
                    this._hide();
                }
                break;
        }
    }

    _handleContentKeydown(event) {
        // Only handle if the focused element belongs to this menu level
        const items = this._getMenuItems();
        const currentIndex = items.indexOf(document.activeElement);
        if (currentIndex === -1) return;

        // Stop propagation to prevent parent dropdown controllers from intercepting
        event.stopPropagation();

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this._focusItem(currentIndex + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this._focusItem(currentIndex - 1);
                break;
            case 'Home':
                event.preventDefault();
                this._focusFirstItem();
                break;
            case 'End':
                event.preventDefault();
                this._focusLastItem();
                break;
            case 'Escape':
                event.preventDefault();
                this._hide();
                break;
            case 'Tab':
                this._hide();
                break;
            case 'ArrowRight':
                // If focused on a SubTrigger, open the submenu
                if (document.activeElement.getAttribute('aria-haspopup') === 'menu') {
                    event.preventDefault();
                    document.activeElement.click();
                    // Focus first item in the submenu after it opens
                    requestAnimationFrame(() => {
                        const subContent = document.activeElement
                            ?.closest('[data-controller="dropdown"]')
                            ?.querySelector('[data-dropdown-target="content"]');
                        if (subContent) {
                            const firstItem = subContent.querySelector('[role="menuitem"]');
                            if (firstItem) firstItem.focus();
                        }
                    });
                }
                break;
            case 'ArrowLeft':
                // If inside a submenu, close it and return focus to the SubTrigger
                this._closeParentSubmenu(event);
                break;
        }
    }

    _closeParentSubmenu(event) {
        // Check if this controller is a submenu (nested inside another dropdown)
        const parentDropdown = this.element.closest('li[role="none"]')?.closest('[data-controller="dropdown"]');
        if (parentDropdown && parentDropdown !== this.element) {
            event.preventDefault();
            this._hide();
            // The _hide() already focuses our trigger, which is the SubTrigger in the parent menu
        }
    }
}
