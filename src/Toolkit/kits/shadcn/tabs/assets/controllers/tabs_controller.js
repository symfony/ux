import { Controller } from '@hotwired/stimulus';

/**
 * @value  activeTab  The `data-tab-id` of the currently selected tab.
 * @target trigger    A tab button that selects its panel and reflects the active state.
 * @target tab         A tab panel that is shown or hidden based on the active tab.
 * @action open        Activates the tab identified by the clicked trigger's `data-tab-id`.
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
            trigger.toggleAttribute('data-active', isActive);
            trigger.ariaSelected = isActive;
        });

        this.tabTargets.forEach((tab) => {
            const isActive = tab.dataset.tabId === this.activeTabValue;
            tab.toggleAttribute('data-active', isActive);
            tab.dataset.state = isActive ? 'active' : 'inactive';
        });
    }
}
