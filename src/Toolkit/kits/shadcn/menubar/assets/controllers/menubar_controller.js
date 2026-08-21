import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'trigger'];

    connect() {
        this._closeOnOutsideClick = this._closeOnOutsideClick.bind(this);
        this.closeAll();
    }

    disconnect() {
        document.removeEventListener('click', this._closeOnOutsideClick, true);
    }

    toggle(event) {
        const menu = this._menuFor(event.currentTarget);
        if (!menu) return;

        if (menu.dataset.state === 'open') {
            this.closeAll();
        } else {
            this._open(menu);
        }
    }

    // Once a menu is open, hovering another trigger switches to its menu; while closed, hovering does nothing.
    enter(event) {
        if (!this._hasOpenMenu()) return;

        const menu = this._menuFor(event.currentTarget);
        if (menu && menu.dataset.state !== 'open') {
            this._open(menu);
        }
    }

    closeAll() {
        this.menuTargets.forEach((menu) => (menu.dataset.state = 'closed'));
        this.triggerTargets.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));
        document.removeEventListener('click', this._closeOnOutsideClick, true);
    }

    _open(menu) {
        this.menuTargets.forEach((other) => (other.dataset.state = other === menu ? 'open' : 'closed'));
        this.triggerTargets.forEach((trigger) =>
            trigger.setAttribute('aria-expanded', menu.contains(trigger) ? 'true' : 'false')
        );
        document.addEventListener('click', this._closeOnOutsideClick, true);
    }

    _hasOpenMenu() {
        return this.menuTargets.some((menu) => menu.dataset.state === 'open');
    }

    _menuFor(trigger) {
        return this.menuTargets.find((menu) => menu.contains(trigger)) || null;
    }

    _closeOnOutsideClick(event) {
        if (!this.element.contains(event.target)) {
            this.closeAll();
        }
    }
}
