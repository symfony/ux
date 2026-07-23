import { Controller } from '@hotwired/stimulus';

/**
 * @value  activeTab  The `data-tab-id` of the currently active tab.
 * @target trigger    The clickable elements that switch tabs and reflect their selected state.
 * @target tab        The tab panels that are shown or hidden based on the active tab.
 * @action open       Activates the tab identified by the clicked trigger.
 */
export default class extends Controller {
    static targets = ['trigger', 'tab'];
    static values = { activeTab: String };

    open(e) {
        this.activeTabValue = e.currentTarget.dataset.tabId;
    }

    activeTabValueChanged() {
        this.triggerTargets.forEach((trigger) => {
            const isActive = trigger.dataset.tabId === this.activeTabValue;
            trigger.dataset.state = isActive ? 'active' : 'inactive';
            trigger.ariaSelected = isActive;
        });

        this.tabTargets.forEach((tab) => {
            tab.dataset.state = tab.dataset.tabId === this.activeTabValue ? 'active' : 'inactive';
        });
    }
}
