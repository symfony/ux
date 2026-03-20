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
            trigger.dataset.state = isActive ? 'active' : 'inactive';
            trigger.ariaSelected = isActive;
        });

        this.tabTargets.forEach((tab) => {
            tab.dataset.state = tab.dataset.tabId === this.activeTabValue ? 'active' : 'inactive';
        });
    }
}
