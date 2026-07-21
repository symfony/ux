import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tab', 'panel'];

    select(event) {
        const tabId = event.currentTarget.dataset.tabId;

        for (const tab of this.tabTargets) {
            tab.setAttribute('aria-selected', tab.dataset.tabId === tabId ? 'true' : 'false');
        }

        for (const panel of this.panelTargets) {
            panel.toggleAttribute('hidden', panel.dataset.tabId !== tabId);
        }
    }
}
