import { Controller } from '@hotwired/stimulus';

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
