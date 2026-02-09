import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item', 'trigger', 'content'];

    static values = {
        multiple: { type: Boolean, default: false },
        orientation: { type: String, default: 'vertical' },
    };

    /**
     * Toggle an accordion item when its trigger is clicked.
     * @param {Event} event
     */
    toggle(event) {
        const trigger = event.currentTarget;
        const item = trigger.closest('[data-accordion-target="item"]');

        if (!item || this.#isDisabled(item)) {
            return;
        }

        const isOpen = item.dataset.open === 'true';

        if (isOpen) {
            this.#closeItem(item);
        } else {
            this.#openItem(item);
        }
    }

    /**
     * Handle keyboard navigation for accessibility.
     * @param {KeyboardEvent} event
     */
    handleKeydown(event) {
        const trigger = event.currentTarget;
        const enabledTriggers = this.#getEnabledTriggers();
        const currentIndex = enabledTriggers.indexOf(trigger);

        if (currentIndex === -1) {
            return;
        }

        const isVertical = this.orientationValue === 'vertical';
        const prevKey = isVertical ? 'ArrowUp' : 'ArrowLeft';
        const nextKey = isVertical ? 'ArrowDown' : 'ArrowRight';

        let newIndex = null;

        switch (event.key) {
            case prevKey:
                event.preventDefault();
                newIndex = currentIndex > 0 ? currentIndex - 1 : enabledTriggers.length - 1;
                break;
            case nextKey:
                event.preventDefault();
                newIndex = currentIndex < enabledTriggers.length - 1 ? currentIndex + 1 : 0;
                break;
            case 'Home':
                event.preventDefault();
                newIndex = 0;
                break;
            case 'End':
                event.preventDefault();
                newIndex = enabledTriggers.length - 1;
                break;
        }

        if (newIndex !== null) {
            enabledTriggers[newIndex].focus();
        }
    }

    /**
     * Open an accordion item.
     * @param {HTMLElement} item
     */
    #openItem(item) {
        // If not multiple, close other open items first
        if (!this.multipleValue) {
            for (const otherItem of this.itemTargets) {
                if (otherItem !== item && otherItem.dataset.open === 'true') {
                    this.#closeItem(otherItem);
                }
            }
        }

        const trigger = item.querySelector('[data-accordion-target="trigger"]');
        const content = item.querySelector('[data-accordion-target="content"]');

        if (!trigger || !content) {
            return;
        }

        // Update item state
        item.dataset.open = 'true';
        delete item.dataset.closed;

        // Update trigger ARIA
        trigger.setAttribute('aria-expanded', 'true');

        // Remove hidden class and prepare for animation
        content.classList.remove('hidden');
        content.setAttribute('aria-hidden', 'false');
        content.dataset.open = 'true';
        delete content.dataset.closed;

        // Force reflow to ensure the transition starts from 0fr
        content.offsetHeight;

        // Apply open state with CSS Grid
        content.style.gridTemplateRows = '1fr';
    }

    /**
     * Close an accordion item.
     * @param {HTMLElement} item
     */
    #closeItem(item) {
        const trigger = item.querySelector('[data-accordion-target="trigger"]');
        const content = item.querySelector('[data-accordion-target="content"]');

        if (!trigger || !content) {
            return;
        }

        // Update item state
        delete item.dataset.open;
        item.dataset.closed = 'true';

        // Update trigger ARIA
        trigger.setAttribute('aria-expanded', 'false');

        // Update content state
        content.setAttribute('aria-hidden', 'true');
        delete content.dataset.open;
        content.dataset.closed = 'true';

        // Apply closed state with CSS Grid
        content.style.gridTemplateRows = '0fr';

        // Hide after transition completes
        const onTransitionEnd = (event) => {
            // Only react to grid-template-rows transition
            if (event.propertyName === 'grid-template-rows') {
                if (item.dataset.closed === 'true') {
                    content.classList.add('hidden');
                }
                content.removeEventListener('transitionend', onTransitionEnd);
            }
        };
        content.addEventListener('transitionend', onTransitionEnd);
    }

    /**
     * Check if an item is disabled.
     * @param {HTMLElement} item
     * @returns {boolean}
     */
    #isDisabled(item) {
        return item.hasAttribute('disabled');
    }

    /**
     * Get all enabled (non-disabled) triggers.
     * @returns {HTMLElement[]}
     */
    #getEnabledTriggers() {
        return this.triggerTargets.filter((trigger) => {
            const item = trigger.closest('[data-accordion-target="item"]');
            return item && !this.#isDisabled(item);
        });
    }
}
