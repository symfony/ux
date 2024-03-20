import {Controller} from '@hotwired/stimulus';
import {getComponent} from '@symfony/ux-live-component';

export default class extends Controller {
    static targets = ["tab", "control"]
    static values = {tab: String}
    static classes = [ "active" ]

    initialize() {
        this.showTab(this.tabValue);
    }

    show({ params: { tab }}) {
        this.tabValue = tab;
    }

    showTab(tab) {
        const tabTarget = this.getTabTarget(tab);
        tabTarget.classList.add(this.activeClass);

        const controlTarget = this.getControlTarget(tab);
        controlTarget.classList.add(this.activeClass);
    }

    hideTab(tab) {
        const tabTarget = this.getTabTarget(tab);
        tabTarget.classList.remove(this.activeClass);

        const controlTarget = this.getControlTarget(tab);
        controlTarget.classList.remove(this.activeClass);
    }

    tabValueChanged(value, previousValue) {
        if (previousValue) {
            this.hideTab(previousValue);
        }

        this.showTab(value);
    }

    getControlTarget(tab) {
        return this.controlTargets.find((elt) => elt.dataset.tabsTabParam === tab);
    }

    getTabTarget(tab) {
        return this.tabTargets.find((elt) => elt.dataset.tab === tab);
    }
}
