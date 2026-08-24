import { Controller } from '@hotwired/stimulus';

const STATE_EXPANDED = 'expanded';
const STATE_COLLAPSED = 'collapsed';

/**
 * Expand/collapse behavior for the `Sidebar` component: cookie persistence,
 * an off-canvas mode on mobile and a keyboard shortcut.
 *
 * Below the breakpoint the sidebar content is relocated into the `Sheet`
 * dialog (which brings the backdrop, slide-in, Escape and click-outside for
 * free) and moved back into the desktop layout above it.
 *
 * @value defaultOpen Initial open state when no cookie is present.
 * @value cookieName Cookie used to persist the sidebar state across reloads (empty disables persistence).
 * @value cookieMaxAge Cookie max-age, in seconds.
 * @value mobileBreakpoint Pixel width below which the sidebar behaves as an off-canvas sheet.
 * @value keyboardShortcut Single key combined with Ctrl/Cmd that toggles the sidebar (empty disables the shortcut).
 * @action toggle Toggle the sidebar between expanded and collapsed (or open/close the mobile sheet).
 * @action open Expand the sidebar (or open the mobile sheet).
 * @action close Collapse the sidebar (or close the mobile sheet).
 * @action handleKeydown Toggle the sidebar on the keyboard shortcut.
 */
export default class extends Controller {
    static values = {
        defaultOpen: { type: Boolean, default: true },
        cookieName: { type: String, default: 'sidebar:state' },
        cookieMaxAge: { type: Number, default: 60 * 60 * 24 * 7 },
        mobileBreakpoint: { type: Number, default: 768 },
        keyboardShortcut: { type: String, default: 'b' },
    };

    connect() {
        this._mobileQuery = window.matchMedia(`(max-width: ${this.mobileBreakpointValue - 1}px)`);
        this._shortcut = this.keyboardShortcutValue ? this.keyboardShortcutValue.toLowerCase() : '';
        this._sidebar = this.element.querySelector('[data-slot="sidebar"]');
        this._desktopSlot = this.element.querySelector('[data-slot="sidebar-inner"]');
        this._mobileSlot = this.element.querySelector('[data-slot="sidebar-mobile-inner"]');
        this._mobileDialog = this._mobileSlot?.closest('dialog') ?? null;

        const cookieValue = this._readCookie();
        if (STATE_EXPANDED === cookieValue || STATE_COLLAPSED === cookieValue) {
            this.element.dataset.state = cookieValue;
        } else {
            this.element.dataset.state = this.defaultOpenValue ? STATE_EXPANDED : STATE_COLLAPSED;
        }

        this._syncPortal();

        this._onMediaChange = () => {
            this._syncPortal();
            this._render();
        };
        this._mobileQuery.addEventListener('change', this._onMediaChange);

        this._onDialogClose = () => this._render();
        this._mobileDialog?.addEventListener('close', this._onDialogClose);

        this._render();
    }

    disconnect() {
        this._mobileQuery.removeEventListener('change', this._onMediaChange);
        this._mobileDialog?.removeEventListener('close', this._onDialogClose);
    }

    toggle(event) {
        event?.preventDefault?.();
        if (this._isOpen()) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        if (this._isMobile()) {
            this._mobileDialog?.showModal();
        } else {
            this._setState(STATE_EXPANDED);
        }
        this._render();
    }

    close() {
        if (this._isMobile()) {
            this._mobileDialog?.close();
        } else {
            this._setState(STATE_COLLAPSED);
        }
        this._render();
    }

    handleKeydown(event) {
        if (!this._shortcut || event.key.toLowerCase() !== this._shortcut) {
            return;
        }
        if (!event.ctrlKey && !event.metaKey) {
            return;
        }
        const tag = event.target?.tagName;
        if ('INPUT' === tag || 'TEXTAREA' === tag || event.target?.isContentEditable) {
            return;
        }
        event.preventDefault();
        this.toggle();
    }

    _isExpanded() {
        return STATE_EXPANDED === this.element.dataset.state;
    }

    _isOpen() {
        return this._isMobile() ? (this._mobileDialog?.open ?? false) : this._isExpanded();
    }

    _setState(state) {
        if (this.element.dataset.state === state) {
            return;
        }
        this.element.dataset.state = state;
        this._writeCookie(state);
    }

    _isMobile() {
        return this._mobileQuery.matches;
    }

    _syncPortal() {
        if (!this._desktopSlot || !this._mobileSlot) {
            return;
        }
        if (this._isMobile()) {
            this._moveChildren(this._desktopSlot, this._mobileSlot);
        } else {
            if (this._mobileDialog?.open) {
                this._mobileDialog.close();
            }
            this._moveChildren(this._mobileSlot, this._desktopSlot);
        }
    }

    _moveChildren(from, to) {
        while (from.firstChild) {
            to.appendChild(from.firstChild);
        }
    }

    _render() {
        const state = this.element.dataset.state;

        const sidebar = this._sidebar;
        if (sidebar) {
            sidebar.dataset.state = state;

            // Mirror shadcn React: data-collapsible is the active mode only when collapsed.
            const mode = sidebar.dataset.collapsibleMode || 'icon';
            sidebar.dataset.collapsible = STATE_COLLAPSED === state ? mode : '';
        }

        if (this.element.id) {
            const expanded = this._isOpen();
            const triggers = document.querySelectorAll(`[data-sidebar-target="${this.element.id}"]`);
            for (const trigger of triggers) {
                trigger.setAttribute('aria-expanded', String(expanded));
            }
        }
    }

    _readCookie() {
        if (!this.cookieNameValue || 'undefined' === typeof document) {
            return null;
        }
        const prefix = `${this.cookieNameValue}=`;
        const parts = document.cookie?.split('; ') ?? [];
        for (const part of parts) {
            if (part.startsWith(prefix)) {
                return decodeURIComponent(part.substring(prefix.length));
            }
        }

        return null;
    }

    _writeCookie(value) {
        if (!this.cookieNameValue || 'undefined' === typeof document) {
            return;
        }
        document.cookie = `${this.cookieNameValue}=${encodeURIComponent(value)}; path=/; max-age=${this.cookieMaxAgeValue}`;
    }
}
