import { Controller } from '@hotwired/stimulus';

const SWIPE_THRESHOLD = 45;
const SWIPE_START_SLOP = 4;
// The 500ms transform transition plus slack, so a dropped `transitionend` still removes the node.
const EXIT_FALLBACK_MS = 600;

/**
 * Stacked toast notifications. The controller only publishes geometry, as custom properties
 * (`--toast-index`, `--toast-height`, `--toast-offset-y`, ...) and state attributes
 * (`data-expanded`, `data-behind`, `data-limited`, ...); the animations are pure CSS on top.
 *
 * @value  duration  Auto-dismiss delay in milliseconds for toasts that do not carry their own.
 * @value  limit     Maximum number of toasts kept on screen.
 * @target viewport  The region toasts are stacked into.
 * @target template  The markup cloned for every toast created from JavaScript.
 * @action add       Creates (or replaces) a toast from the `data-toast-*-param` attributes.
 * @action promise   Shows a loading toast, then replaces it with the success or error one.
 * @action close     Dismisses the toast the event originated from.
 * @action closeAll  Dismisses every toast at once.
 */
export default class extends Controller {
    static targets = ['viewport', 'template'];

    static values = {
        duration: { type: Number, default: 5000 },
        limit: { type: Number, default: 3 },
    };

    #timers = new Map(); // toast element → { id, remaining, start }
    #hydrated = new WeakSet();
    #swipeAborts = new Map(); // toast element → AbortController
    #sequence = 0;
    #expanded = false;
    #hovered = false;
    #focused = false;

    connect() {
        this.onToastEvent = (event) => this.#upsert(event.detail || {});
        window.addEventListener('toast', this.onToastEvent);

        // Tracked apart so neither cancels the other, and `focusout` checks `relatedTarget`:
        // tabbing between the action and close buttons stays inside the region.
        this.onPointerEnter = () => this.#track('hovered', true);
        this.onPointerLeave = () => this.#track('hovered', false);
        this.onFocusIn = () => this.#track('focused', true);
        this.onFocusOut = ({ relatedTarget }) => this.#track('focused', this.viewportTarget.contains(relatedTarget));
        this.viewportTarget.addEventListener('pointerenter', this.onPointerEnter);
        this.viewportTarget.addEventListener('pointerleave', this.onPointerLeave);
        this.viewportTarget.addEventListener('focusin', this.onFocusIn);
        this.viewportTarget.addEventListener('focusout', this.onFocusOut);

        for (const element of this.element.querySelectorAll('[data-slot="toast"]')) {
            if (!this.viewportTarget.contains(element)) {
                this.viewportTarget.appendChild(element);
            }
            this.#hydrate(element);
        }

        this.viewportObserver = new MutationObserver((records) => this.#absorb(records));
        this.viewportObserver.observe(this.viewportTarget, { childList: true });

        this.#layout();
    }

    disconnect() {
        window.removeEventListener('toast', this.onToastEvent);
        this.viewportTarget.removeEventListener('pointerenter', this.onPointerEnter);
        this.viewportTarget.removeEventListener('pointerleave', this.onPointerLeave);
        this.viewportTarget.removeEventListener('focusin', this.onFocusIn);
        this.viewportTarget.removeEventListener('focusout', this.onFocusOut);
        this.viewportObserver.disconnect();

        for (const timer of this.#timers.values()) {
            clearTimeout(timer.id);
        }
        this.#timers.clear();

        for (const abort of this.#swipeAborts.values()) {
            abort.abort();
        }
        this.#swipeAborts.clear();
    }

    /**
     * Wired as `data-action="click->toast#add"`, configured with `data-toast-<name>-param`
     * attributes: `type`, `title`, `description`, `duration`, `id` and `actionLabel`.
     */
    add({ params = {} } = {}) {
        this.#upsert(params);
    }

    /**
     * Wired as `data-action="click->toast#promise"`, configured with `data-toast-<name>-param`
     * attributes: `loading`, `success`, `error`, `delay` and `reject`.
     */
    promise({ params = {} } = {}) {
        const { loading = '', success = '', error = '', delay = 2000, reject = false } = params;
        const id = `toast-promise-${++this.#sequence}`;

        this.#upsert({ type: 'loading', title: loading, duration: 0, id });

        setTimeout(() => {
            this.#upsert(reject ? { type: 'error', title: error, id } : { type: 'success', title: success, id });
        }, Number(delay));
    }

    close({ target }) {
        const element = target.closest('[data-slot="toast"]');
        if (element) {
            this.#dismiss(element);
        }
    }

    closeAll() {
        for (const element of this.#items()) {
            this.#dismiss(element);
        }
    }

    #upsert({ type = 'default', title = '', description = '', duration, id = null, actionLabel = '' } = {}) {
        const toastId = String(id ?? '').trim() || `toast-${++this.#sequence}`;
        const existing = this.#items().find((element) => element.dataset.toastId === toastId);
        const element = existing ?? this.#clone();

        if (!element) {
            return;
        }

        const ms = Number(duration ?? this.durationValue);
        this.#fill(element, { type, title, description, actionLabel, toastId, ms });

        if (!existing) {
            this.viewportTarget.prepend(element);
            this.#hydrate(element);
        }

        this.#startTimer(element, ms);
        this.#layout();
    }

    #clone() {
        if (!this.hasTemplateTarget) {
            return null;
        }

        return this.templateTarget.content.firstElementChild?.cloneNode(true) ?? null;
    }

    #fill(element, { type, title, description, actionLabel, toastId, ms }) {
        element.dataset.type = type;
        element.dataset.toastId = toastId;
        element.dataset.toastDuration = String(ms);
        // The viewport is the live region, so a toast only carries the escalation. Cleared again
        // when a replaced toast leaves `error`, or a `promise` settling into `success` under the same
        // id would keep interrupting.
        if ('error' === type) {
            element.setAttribute('role', 'alert');
            element.setAttribute('aria-live', 'assertive');
        } else {
            element.removeAttribute('role');
            element.removeAttribute('aria-live');
        }

        // Every status icon ships in the markup; only the one matching the type stays visible,
        // so a toast replaced by `promise` can still switch from `loading` to `success`.
        for (const icon of element.querySelectorAll('[data-slot="toast-icon"]')) {
            icon.hidden = icon.dataset.type !== type;
        }

        this.#text(element, 'toast-title', title);
        this.#text(element, 'toast-description', description);

        const action = element.querySelector('[data-toast-part="action"]');
        if (action) {
            action.textContent = actionLabel;
            action.hidden = '' === actionLabel;
        }
    }

    #text(element, slot, value) {
        const node = element.querySelector(`[data-slot="${slot}"]`);
        if (node) {
            node.textContent = value;
            node.hidden = '' === value;
        }
    }

    #absorb(records) {
        const removed = new Set();
        let changed = false;

        for (const record of records) {
            for (const node of record.addedNodes) {
                if (Node.ELEMENT_NODE !== node.nodeType) {
                    continue;
                }

                const added = node.matches('[data-slot="toast"]')
                    ? [node]
                    : node.querySelectorAll('[data-slot="toast"]');
                for (const element of added) {
                    this.#hydrate(element);
                    changed = true;
                }
            }

            // Descendants too, as above: a toast can leave inside a subtree swapped as a whole, and
            // a removal nobody notices leaves the surviving stack on stale offsets.
            for (const node of record.removedNodes) {
                if (Node.ELEMENT_NODE !== node.nodeType) {
                    continue;
                }

                const gone = node.matches('[data-slot="toast"]')
                    ? [node]
                    : node.querySelectorAll('[data-slot="toast"]');
                for (const element of gone) {
                    removed.add(element);
                    changed = true;
                }
            }
        }

        // Records land once the DOM has settled, so a toast that was merely moved is back inside the
        // viewport by now: forgetting it here would clear its timer while `#hydrate()` skips it as
        // already hydrated, leaving a toast that never dismisses.
        for (const element of removed) {
            if (!this.viewportTarget.contains(element)) {
                this.#forget(element);
            }
        }

        if (removed.size > 0) {
            this.#refreshInteraction();
        }

        if (changed) {
            this.#layout();
        }
    }

    #hydrate(element) {
        if (this.#hydrated.has(element)) {
            return;
        }
        this.#hydrated.add(element);

        element.dataset.toastId ||= `toast-${++this.#sequence}`;
        this.#reset(element);
        this.#watchSwipe(element);
        this.#enter(element);
        this.#startTimer(element, Number(element.dataset.toastDuration ?? this.durationValue));
    }

    /**
     * The counterpart to `#hydrate()`. A toast can leave the viewport without going through
     * `#dismiss()` — a Turbo Stream `remove`, a morph, a subtree swap — and both maps are keyed by the
     * element, so without this the node stays reachable and its timer still fires, dispatching a
     * `toast:close` for a toast that is long gone. Dropping it from `#hydrated` is what lets a node
     * that comes back later animate in again rather than be skipped as already hydrated.
     */
    #forget(element) {
        this.#hydrated.delete(element);
        this.#clearTimer(element);
        this.#swipeAborts.get(element)?.abort();
        this.#swipeAborts.delete(element);
    }

    #reset(element) {
        element.style.setProperty('--toast-swipe-movement-x', '0px');
        element.style.setProperty('--toast-swipe-movement-y', '0px');
    }

    #enter(element) {
        element.dataset.startingStyle = '';
        // Two frames: the first commits the starting transform, the second gives it something
        // to animate from.
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                delete element.dataset.startingStyle;
            });
        });
    }

    #dismiss(element, direction = null) {
        if ('endingStyle' in element.dataset) {
            return;
        }

        this.#clearTimer(element);

        if (direction) {
            element.dataset.swipeDirection = direction;
        }
        element.dataset.endingStyle = '';

        let done = false;
        let fallbackId = null;
        const remove = () => {
            // `transitionend` and the fallback timer both land here; only the first one counts.
            if (done) {
                return;
            }
            done = true;
            clearTimeout(fallbackId);
            element.removeEventListener('transitionend', onTransitionEnd);

            const toastId = element.dataset.toastId;
            this.#forget(element);
            element.remove();

            this.dispatch('close', { detail: { id: toastId } });
            this.#layout();
        };

        const onTransitionEnd = ({ propertyName, target }) => {
            if ('transform' === propertyName && target === element) {
                remove();
            }
        };

        element.addEventListener('transitionend', onTransitionEnd);
        fallbackId = setTimeout(remove, EXIT_FALLBACK_MS);
    }

    #items() {
        return Array.from(this.viewportTarget.querySelectorAll('[data-slot="toast"]')).filter(
            (element) => !('endingStyle' in element.dataset)
        );
    }

    #layout() {
        const items = this.#items();
        const frontmostHeight = items.length > 0 ? this.#measure(items[0]) : 0;
        let offset = 0;

        items.forEach((element, index) => {
            const height = this.#measure(element);

            element.style.setProperty('--toast-index', String(index));
            element.style.setProperty('--toast-height', `${height}px`);
            element.style.setProperty('--toast-frontmost-height', `${frontmostHeight}px`);
            element.style.setProperty('--toast-offset-y', `${offset}px`);
            offset += height;

            this.#toggle(element, 'expanded', this.#expanded);
            this.#toggle(element, 'limited', index >= this.limitValue);

            const content = element.querySelector('[data-slot="toast-content"]');
            if (content) {
                this.#toggle(content, 'expanded', this.#expanded);
                this.#toggle(content, 'behind', !this.#expanded && index > 0);
            }
        });
    }

    // Natural height, read with the `--toast-height` constraint lifted. `offsetHeight` rather than
    // `getBoundingClientRect()`, which would fold in the `scale()` a stacked toast carries.
    #measure(element) {
        const previous = element.style.height;
        element.style.height = 'auto';
        const height = element.offsetHeight;
        element.style.height = previous;

        return height;
    }

    #toggle(element, name, on) {
        if (on) {
            element.dataset[name] = '';
        } else {
            delete element.dataset[name];
        }
    }

    #track(flag, on) {
        if ('hovered' === flag) {
            this.#hovered = on;
        } else {
            this.#focused = on;
        }

        this.#setExpanded(this.#hovered || this.#focused);
    }

    // `pointerleave` does not fire when the element under the pointer is removed, and Chrome and
    // Safari fire no `focusout` when the focused element is (Firefox does) — closing a toast from its
    // own close button is enough to strand `#focused`. Read both back from the DOM after a removal
    // instead of trusting the paired events, or the region stays expanded and every timer stays paused.
    // Hover is read off the toasts rather than the viewport, which is `pointer-events-none`: they are
    // what the `pointerenter` and `pointerleave` listeners bubble from in the first place.
    #refreshInteraction() {
        this.#hovered = null !== this.viewportTarget.querySelector('[data-slot="toast"]:hover');
        this.#focused = this.viewportTarget.contains(document.activeElement);
        this.#setExpanded(this.#hovered || this.#focused);
    }

    #setExpanded(expanded) {
        if (this.#expanded === expanded) {
            return;
        }

        this.#expanded = expanded;
        if (expanded) {
            this.#pauseTimers();
        } else {
            this.#resumeTimers();
        }
        this.#layout();
    }

    #startTimer(element, ms) {
        this.#clearTimer(element);

        if (!(ms > 0)) {
            return;
        }

        const timer = { id: null, remaining: ms, start: Date.now() };
        this.#timers.set(element, timer);

        if (!this.#expanded) {
            timer.id = setTimeout(() => this.#dismiss(element), ms);
        }
    }

    #clearTimer(element) {
        const timer = this.#timers.get(element);
        if (timer) {
            clearTimeout(timer.id);
            this.#timers.delete(element);
        }
    }

    #pauseTimers() {
        const now = Date.now();
        for (const timer of this.#timers.values()) {
            if (null !== timer.id) {
                clearTimeout(timer.id);
                timer.remaining = Math.max(0, timer.remaining - (now - timer.start));
                timer.id = null;
            }
        }
    }

    #resumeTimers() {
        const now = Date.now();
        for (const [element, timer] of this.#timers) {
            if (null === timer.id && timer.remaining > 0) {
                timer.start = now;
                timer.id = setTimeout(() => this.#dismiss(element), timer.remaining);
            }
        }
    }

    #watchSwipe(element) {
        element.addEventListener('pointerdown', (event) => this.#startSwipe(event, element));
    }

    #startSwipe(pointerDownEvent, element) {
        if (0 !== pointerDownEvent.button || 'endingStyle' in element.dataset) {
            return;
        }

        // A re-entrant pointerdown (or a missed pointerup) must not leak the previous listeners.
        this.#swipeAborts.get(element)?.abort();

        const abort = new AbortController();
        const { signal } = abort;
        this.#swipeAborts.set(element, abort);

        const startX = pointerDownEvent.clientX;
        const startY = pointerDownEvent.clientY;
        let dragging = false;

        const endSwipe = () => {
            abort.abort();
            this.#swipeAborts.delete(element);
        };

        element.addEventListener(
            'pointermove',
            (event) => {
                const dx = event.clientX - startX;
                const dy = event.clientY - startY;

                if (!dragging) {
                    // Capture only once past the slop: a capture held at pointerup retargets the
                    // click to the toast, swallowing the button the press started on.
                    if (Math.max(Math.abs(dx), Math.abs(dy)) < SWIPE_START_SLOP) {
                        return;
                    }

                    dragging = true;
                    element.setPointerCapture(event.pointerId);
                }

                element.style.setProperty('--toast-swipe-movement-x', `${dx}px`);
                element.style.setProperty('--toast-swipe-movement-y', `${dy}px`);
            },
            { signal }
        );

        element.addEventListener(
            'pointerup',
            (event) => {
                endSwipe();

                if (!dragging) {
                    return;
                }

                if (element.hasPointerCapture(event.pointerId)) {
                    element.releasePointerCapture(event.pointerId);
                }

                const direction = this.#swipeDirection(event.clientX - startX, event.clientY - startY);
                if (direction) {
                    this.#dismiss(element, direction);
                } else {
                    this.#reset(element);
                }
            },
            { signal }
        );

        element.addEventListener(
            'pointercancel',
            () => {
                endSwipe();
                this.#reset(element);
            },
            { signal }
        );
    }

    #swipeDirection(dx, dy) {
        if (Math.max(Math.abs(dx), Math.abs(dy)) < SWIPE_THRESHOLD) {
            return null;
        }

        if (Math.abs(dx) >= Math.abs(dy)) {
            return dx > 0 ? 'right' : 'left';
        }

        return dy > 0 ? 'down' : 'up';
    }
}

/**
 * Creates a toast from anywhere in the page. Mirrors the options of the `toast#add` action.
 */
export function toast(options) {
    window.dispatchEvent(new CustomEvent('toast', { detail: options }));
}

toast.success = (title, options = {}) => toast({ type: 'success', title, ...options });
toast.info = (title, options = {}) => toast({ type: 'info', title, ...options });
toast.warning = (title, options = {}) => toast({ type: 'warning', title, ...options });
toast.error = (title, options = {}) => toast({ type: 'error', title, ...options });
toast.loading = (title, options = {}) => toast({ type: 'loading', title, duration: 0, ...options });

/**
 * Shows a loading toast and replaces it, under the same id, once the promise settles.
 */
toast.promise = (promise, { loading = '', success = '', error = '', id = null } = {}) => {
    const toastId = id ?? `toast-promise-${Date.now()}`;
    toast({ type: 'loading', title: loading, duration: 0, id: toastId });

    return Promise.resolve(promise).then(
        (value) => {
            toast({
                type: 'success',
                title: 'function' === typeof success ? success(value) : success,
                id: toastId,
            });

            return value;
        },
        (reason) => {
            toast({
                type: 'error',
                title: 'function' === typeof error ? error(reason) : error,
                id: toastId,
            });

            throw reason;
        }
    );
};
