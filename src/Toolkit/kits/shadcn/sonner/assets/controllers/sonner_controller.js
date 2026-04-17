import { Controller } from '@hotwired/stimulus';

// Literal class strings are required here so Tailwind JIT includes them in the CSS build.
const SONNER_BASE =
    'group flex w-full items-start gap-3 rounded-lg border bg-background p-4 text-foreground shadow-lg transition-[transform,opacity] duration-300 pointer-events-auto absolute left-0 right-0';

const SONNER_RICH_COLORS = {
    success:
        'bg-green-50 text-green-900 border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800',
    error: 'bg-red-50 text-red-900 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
    warning:
        'bg-amber-50 text-amber-900 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
    info: 'bg-blue-50 text-blue-900 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
};

const TYPE_ICONS = {
    success:
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    error: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    warning:
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
    info: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    loading:
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
};

const CLOSE_ICON =
    '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

export default class extends Controller {
    static values = {
        position: { type: String, default: 'top-right' },
        expand: { type: Boolean, default: false },
        richColors: { type: Boolean, default: false },
        closeButton: { type: Boolean, default: false },
        duration: { type: Number, default: 4000 },
        gap: { type: Number, default: 14 },
        visibleToasts: { type: Number, default: 3 },
        dir: { type: String, default: 'ltr' },
        theme: { type: String, default: 'system' },
    };

    static targets = ['list'];

    #swipeAborts = new WeakMap(); // li → AbortController

    connect() {
        this.toastSeq = 0;
        this.timers = new Map(); // li → { id, remaining, start }
        this.isHovered = false;

        this.onSonnerEvent = (e) => this.#createToast(e.detail || {});
        window.addEventListener('sonner', this.onSonnerEvent);

        this.#applyTheme();

        // Server-rendered toasts sit outside the <ol> in the DOM; move them in before hydrating
        for (const li of this.element.querySelectorAll('[data-slot="toast"]')) {
            this.listTarget.appendChild(li);
            this.#hydrateToast(li);
        }

        this.onMouseOver = (e) => {
            if (!this.isHovered && e.target.closest('[data-slot="toast"]')) {
                this.isHovered = true;
                this.#pauseAll();
                this.#layout();
            }
        };
        this.onMouseOut = (e) => {
            const related = e.relatedTarget;
            if (this.isHovered && (!related || !this.element.contains(related))) {
                this.isHovered = false;
                this.#resumeAll();
                this.#layout();
            }
        };

        this.listTarget.addEventListener('mouseover', this.onMouseOver);
        this.listTarget.addEventListener('mouseout', this.onMouseOut);

        this.#layout();
    }

    disconnect() {
        window.removeEventListener('sonner', this.onSonnerEvent);
        this.listTarget.removeEventListener('mouseover', this.onMouseOver);
        this.listTarget.removeEventListener('mouseout', this.onMouseOut);
        for (const timer of this.timers.values()) {
            clearTimeout(timer.id);
        }
        this.timers.clear();
    }

    // Called via data-action="click->sonner#fire" with data-sonner-*-param attributes.
    fire({
        params: {
            type = 'default',
            title = '',
            description = null,
            duration = null,
            actionLabel = null,
            actionUrl = null,
        } = {},
    }) {
        const action = actionLabel ? { label: actionLabel, ...(actionUrl && { url: actionUrl }) } : null;
        this.#createToast({ type, title, description, action, ...(duration !== null && { duration }) });
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    get #isTopPosition() {
        return this.positionValue.startsWith('top');
    }

    #applyTheme() {
        if (this.themeValue === 'dark') {
            this.element.dataset.theme = 'dark';
        } else if (this.themeValue === 'light') {
            this.element.dataset.theme = 'light';
        }
    }

    #createToast({
        type = 'default',
        title = '',
        description = null,
        duration,
        closeButton,
        id = null,
        action = null,
    } = {}) {
        const toastId = id ?? `toast-${++this.toastSeq}`;
        const toastMs = duration ?? this.durationValue;
        const showClose = closeButton ?? this.closeButtonValue;

        const existing = this.listTarget.querySelector(`[data-toast-id="${CSS.escape(toastId)}"]`);
        if (existing) this.#remove(existing);

        const li = document.createElement('li');
        li.dataset.slot = 'toast';
        li.dataset.type = type;
        li.dataset.state = 'closed';
        li.dataset.toastId = toastId;
        li.setAttribute('role', type === 'error' ? 'alert' : 'status');
        li.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        li.setAttribute('aria-atomic', 'true');

        li.className = SONNER_BASE;
        if (this.richColorsValue && SONNER_RICH_COLORS[type]) {
            li.className += ' ' + SONNER_RICH_COLORS[type];
        }

        if (TYPE_ICONS[type]) {
            const icon = document.createElement('div');
            icon.dataset.slot = 'toast-icon';
            icon.className = 'mt-0.5 shrink-0 self-start';
            icon.innerHTML = TYPE_ICONS[type]; // trusted constant SVG string
            li.appendChild(icon);
        }

        const content = document.createElement('div');
        content.dataset.slot = 'toast-content';
        content.className = 'flex-1 space-y-1';

        const titleEl = document.createElement('div');
        titleEl.dataset.slot = 'toast-title';
        titleEl.className = 'text-sm font-semibold leading-snug';
        titleEl.textContent = title;
        content.appendChild(titleEl);

        if (description) {
            const desc = document.createElement('div');
            desc.dataset.slot = 'toast-description';
            desc.className = 'text-sm text-muted-foreground';
            desc.textContent = description;
            content.appendChild(desc);
        }

        li.appendChild(content);

        if (action?.label) {
            const btn = action.url ? document.createElement('a') : document.createElement('button');

            if (action.url) btn.href = action.url;
            else btn.type = 'button';
            if (typeof action.onClick === 'function') {
                btn.addEventListener('click', action.onClick);
            }

            btn.dataset.slot = 'toast-action';
            btn.className =
                'ml-auto shrink-0 rounded-md border bg-transparent px-3 py-1 text-sm font-medium transition-colors hover:bg-secondary focus:outline-hidden focus:ring-2 focus:ring-ring focus:ring-offset-2';
            btn.textContent = action.label;
            li.appendChild(btn);
        }

        if (showClose) this.#appendCloseButton(li);

        this.listTarget.prepend(li);
        this.#activateToast(li, type, toastMs, true);
    }

    #hydrateToast(li) {
        const type = li.dataset.type ?? 'default';
        const ms = li.dataset.duration ? Number(li.dataset.duration) : this.durationValue;

        if (this.richColorsValue && SONNER_RICH_COLORS[type] && !li.dataset.richApplied) {
            li.className += ' ' + SONNER_RICH_COLORS[type];
            li.dataset.richApplied = '1';
        }

        const closeBtn = li.querySelector('[data-slot="toast-close"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.#dismiss(li));
        } else if (this.closeButtonValue) {
            this.#appendCloseButton(li);
        }

        this.#activateToast(li, type, ms, true);
    }

    #activateToast(li, type, ms, layoutOnOpen = false) {
        this.#anchor(li);
        li.addEventListener('pointerdown', (e) => this.#startSwipe(e, li));
        requestAnimationFrame(() => {
            li.dataset.state = 'open';
            if (layoutOnOpen) this.#layout();
        });
        if (type !== 'loading' && ms > 0) this.#startTimer(li, ms);
    }

    #appendCloseButton(li) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.dataset.slot = 'toast-close';
        btn.className =
            'absolute top-2 right-2 rounded-md p-1 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-muted';
        btn.setAttribute('aria-label', 'Close notification');
        btn.innerHTML = CLOSE_ICON;
        btn.addEventListener('click', () => this.#dismiss(li));
        li.appendChild(btn);
    }

    #anchor(li) {
        const isTop = this.#isTopPosition;
        li.style.position = 'absolute';
        li.style.left = '0';
        li.style.right = '0';
        li.style.opacity = '0';

        if (isTop) {
            li.style.top = '0';
            li.style.bottom = 'auto';
        } else {
            li.style.bottom = '0';
            li.style.top = 'auto';
        }
    }

    #dismiss(li) {
        if (li.dataset.state === 'closing') return;
        this.#clearTimer(li);

        li.dataset.state = 'closing';
        li.style.opacity = '0';

        const isTop = this.#isTopPosition;
        li.style.transform = isTop ? 'translateY(-16px) scale(0.95)' : 'translateY(16px) scale(0.95)';

        let done = false;
        let fallbackId = null;
        const cleanup = () => {
            if (done) return; // transitionend and the fallback timer both call this; run once
            done = true;
            if (fallbackId !== null) clearTimeout(fallbackId);
            li.removeEventListener('transitionend', onTransitionEnd);
            this.#remove(li);
            li.dispatchEvent(
                new CustomEvent('sonner:dismiss', {
                    bubbles: true,
                    detail: { id: li.dataset.toastId },
                })
            );
            this.#layout();
        };

        const onTransitionEnd = ({ propertyName }) => {
            if (propertyName === 'opacity') cleanup();
        };

        li.addEventListener('transitionend', onTransitionEnd);
        fallbackId = setTimeout(cleanup, 400); // fallback
    }

    #remove(li) {
        this.#clearTimer(li);
        this.#swipeAborts.get(li)?.abort();
        if (li.parentNode) li.remove();
    }

    #layout() {
        const openToasts = Array.from(this.listTarget.querySelectorAll('[data-slot="toast"][data-state="open"]'));

        const isTop = this.#isTopPosition;
        const gap = this.gapValue;
        const maxVisible = this.visibleToastsValue;
        const isExpanded = this.isHovered && this.expandValue;

        let runningOffset = 0;

        openToasts.forEach((li, idx) => {
            if (idx >= maxVisible) {
                li.style.opacity = '0';
                li.style.pointerEvents = 'none';
                li.style.zIndex = '0';
                return;
            }

            li.style.pointerEvents = isExpanded || idx === 0 ? 'auto' : 'none';
            li.style.zIndex = String(maxVisible - idx);
            li.style.transformOrigin = isTop ? 'top center' : 'bottom center';

            if (isExpanded) {
                const dir = isTop ? 1 : -1;
                li.style.transform = `translateY(${dir * runningOffset}px)`;
                li.style.opacity = '1';
                runningOffset += (li.offsetHeight || 56) + gap;
            } else {
                const offset = idx * gap;
                const scale = 1 - idx * 0.05;
                const opacity = Math.max(0, 1 - idx * 0.33);
                const dir = isTop ? 1 : -1;
                li.style.transform = `translateY(${dir * offset}px) scale(${scale})`;
                li.style.opacity = opacity;
            }
        });
    }

    #startTimer(li, ms) {
        const id = setTimeout(() => this.#dismiss(li), ms);
        this.timers.set(li, { id, remaining: ms, start: Date.now() });
    }

    #clearTimer(li) {
        const timer = this.timers.get(li);
        if (timer) {
            clearTimeout(timer.id);
            this.timers.delete(li);
        }
    }

    #pauseAll() {
        const now = Date.now();
        for (const timer of this.timers.values()) {
            if (timer.id !== null) {
                clearTimeout(timer.id);
                timer.remaining = Math.max(0, timer.remaining - (now - timer.start));
                timer.id = null;
            }
        }
    }

    #resumeAll() {
        const now = Date.now();
        for (const [li, timer] of this.timers) {
            if (timer.id === null && timer.remaining > 0) {
                timer.start = now;
                timer.id = setTimeout(() => this.#dismiss(li), timer.remaining);
            }
        }
    }

    #startSwipe(pointerDownEvent, li) {
        // Abort any in-flight swipe for this toast before starting a new one, so a
        // re-entrant pointerdown (or a missed pointerup) can't leak the old listeners.
        this.#swipeAborts.get(li)?.abort();

        const ac = new AbortController();
        const { signal } = ac;
        this.#swipeAborts.set(li, ac);

        const startX = pointerDownEvent.clientX;
        li.setPointerCapture(pointerDownEvent.pointerId);

        const endSwipe = () => {
            ac.abort();
            this.#swipeAborts.delete(li);
        };

        li.addEventListener(
            'pointermove',
            (e) => {
                const dx = e.clientX - startX;
                const rtl = this.dirValue === 'rtl';
                if ((!rtl && dx > 0) || (rtl && dx < 0)) {
                    li.style.transform = `translateX(${dx}px)`;
                    li.style.opacity = String(Math.max(0, 1 - Math.abs(dx) / 200));
                }
            },
            { signal }
        );

        li.addEventListener(
            'pointerup',
            (e) => {
                const dx = e.clientX - startX;
                endSwipe();
                if (Math.abs(dx) > 80) {
                    this.#dismiss(li);
                } else {
                    this.#layout();
                }
            },
            { signal }
        );

        li.addEventListener(
            'pointercancel',
            () => {
                endSwipe();
                this.#layout();
            },
            { signal }
        );
    }
}

export function toast(options) {
    window.dispatchEvent(new CustomEvent('sonner', { detail: options }));
}

toast.success = (title, opts = {}) => toast({ type: 'success', title, ...opts });
toast.error = (title, opts = {}) => toast({ type: 'error', title, ...opts });
toast.warning = (title, opts = {}) => toast({ type: 'warning', title, ...opts });
toast.info = (title, opts = {}) => toast({ type: 'info', title, ...opts });
toast.loading = (title, opts = {}) => toast({ type: 'loading', title, ...opts });
