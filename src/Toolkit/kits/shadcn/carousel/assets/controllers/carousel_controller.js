import { Controller } from '@hotwired/stimulus';

// Pointer travel, in pixels, before a press turns into a drag rather than a click.
const DRAG_THRESHOLD = 5;
// How far, in milliseconds, the release velocity is projected to pick the landing slide.
const DRAG_MOMENTUM = 120;

/**
 * Motion and swipe behavior for the `Carousel` component: a flex track translated with a CSS
 * transform, whose snap positions are measured from the rendered slides rather than configured,
 * so any `basis-*` utility on a `Carousel:Item` works out of the box.
 *
 * Positions are expressed along the logical axis (0 is the first slide, growing towards the last
 * one), so the same math drives horizontal, vertical and RTL carousels; only the sign of the
 * applied transform changes.
 */
export default class extends Controller {
    static targets = ['viewport', 'track', 'item', 'previous', 'next'];

    static values = {
        orientation: { type: String, default: 'horizontal' },
        align: { type: String, default: 'center' },
        loop: { type: Boolean, default: false },
        autoplay: { type: Number, default: 0 },
    };

    connect() {
        this._index = 0;
        this._position = 0;
        this._snaps = [0];
        this._maxPosition = 0;
        this._rtl = false;
        this._dragging = false;
        this._pointerId = null;
        this._autoplayPaused = false;
        this._autoplayTimer = null;

        this._abortController = new AbortController();
        const { signal } = this._abortController;

        this._resizeObserver = new ResizeObserver(() => this._onResize());
        this._resizeObserver.observe(this.viewportTarget);
        this._resizeObserver.observe(this.trackTarget);

        if (this.autoplayValue > 0) {
            this.element.addEventListener('mouseenter', () => this.pauseAutoplay(), { signal });
            this.element.addEventListener('mouseleave', () => this.resumeAutoplay(), { signal });
            this.element.addEventListener('focusin', () => this.pauseAutoplay(), { signal });
            this.element.addEventListener('focusout', () => this.resumeAutoplay(), { signal });
        }

        this.measure();
        this.select(0, false);
        this._restartAutoplay();
    }

    disconnect() {
        this._abortController?.abort();
        this._abortController = null;
        this._dragAbortController?.abort();
        this._dragAbortController = null;
        this._resizeObserver?.disconnect();
        this._resizeObserver = null;
        this._pointerId = null;
        this._stopAutoplay();
    }

    scrollPrev() {
        this.select(this._index - 1);
        this._restartAutoplay();
    }

    scrollNext() {
        this.select(this._index + 1);
        this._restartAutoplay();
    }

    select(index, animate = true) {
        const count = this._snaps.length;

        this._index = this.loopValue ? ((index % count) + count) % count : Math.min(Math.max(index, 0), count - 1);
        this._position = this._snaps[this._index];

        if (animate) {
            this._translate();
        } else {
            this._translateWithoutAnimation();
        }

        this._sync();
    }

    handleKeydown(event) {
        if (event.target.closest('input, textarea, select, [contenteditable]')) {
            return;
        }

        let forward;
        if (this._isHorizontal) {
            if ('ArrowLeft' === event.key) {
                forward = this._rtl;
            } else if ('ArrowRight' === event.key) {
                forward = !this._rtl;
            } else {
                return;
            }
        } else if ('ArrowUp' === event.key) {
            forward = false;
        } else if ('ArrowDown' === event.key) {
            forward = true;
        } else {
            return;
        }

        event.preventDefault();

        if (forward) {
            this.scrollNext();
        } else {
            this.scrollPrev();
        }
    }

    dragStart(event) {
        if (0 !== event.button || this._snaps.length < 2) {
            return;
        }

        this._dragAbortController?.abort();
        this._dragAbortController = new AbortController();
        const { signal } = this._dragAbortController;

        this._pointerId = event.pointerId;
        this._dragOrigin = this._pointerCoordinate(event);
        this._dragStartPosition = this._position;
        this._dragMoved = false;
        this._dragLastCoordinate = this._dragOrigin;
        this._dragLastTime = event.timeStamp;
        this._dragVelocity = 0;

        window.addEventListener('pointermove', (e) => this._onDragMove(e), { signal, passive: false });
        window.addEventListener('pointerup', (e) => this._onDragEnd(e), { signal });
        window.addEventListener('pointercancel', (e) => this._onDragEnd(e), { signal });

        this.pauseAutoplay();
    }

    pauseAutoplay() {
        this._autoplayPaused = true;
        this._stopAutoplay();
    }

    resumeAutoplay() {
        this._autoplayPaused = false;
        this._restartAutoplay();
    }

    /**
     * Offsets are taken from the track rather than the viewport so the negative margin carrying
     * the gutter is already accounted for, and the track's own size is what slides align against.
     */
    measure() {
        this._rtl = 'rtl' === getComputedStyle(this.element).direction;

        const horizontal = this._isHorizontal;
        const trackRect = this.trackTarget.getBoundingClientRect();
        const viewportSize = horizontal ? trackRect.width : trackRect.height;

        const slides = this.itemTargets.map((item) => {
            const rect = item.getBoundingClientRect();

            if (!horizontal) {
                return { start: rect.top - trackRect.top, size: rect.height };
            }

            return this._rtl
                ? { start: trackRect.right - rect.right, size: rect.width }
                : { start: rect.left - trackRect.left, size: rect.width };
        });

        const contentSize = slides.reduce((size, slide) => Math.max(size, slide.start + slide.size), 0);
        this._maxPosition = Math.max(0, contentSize - viewportSize);

        // Snaps clamped to the same position — the trailing slides of a multi-item carousel,
        // typically — collapse into a single one.
        const snaps = [];
        slides.forEach(({ start, size }) => {
            let target = start;
            if ('end' === this.alignValue) {
                target = start - (viewportSize - size);
            } else if ('start' !== this.alignValue) {
                target = start - (viewportSize - size) / 2;
            }

            target = Math.min(Math.max(target, 0), this._maxPosition);

            if (0 === snaps.length || Math.abs(snaps[snaps.length - 1] - target) > 1) {
                snaps.push(target);
            }
        });

        this._snaps = snaps.length > 0 ? snaps : [0];
        this._index = Math.min(this._index, this._snaps.length - 1);
    }

    get _isHorizontal() {
        return 'vertical' !== this.orientationValue;
    }

    /** The sign turning a logical position (or a pointer delta) into a physical one. */
    get _axisSign() {
        return this._isHorizontal && this._rtl ? 1 : -1;
    }

    _translate() {
        const offset = this._axisSign * this._position;

        this.trackTarget.style.transform = this._isHorizontal
            ? `translate3d(${offset}px, 0, 0)`
            : `translate3d(0, ${offset}px, 0)`;
    }

    _translateWithoutAnimation() {
        const track = this.trackTarget;

        track.style.transitionDuration = '0ms';
        this._translate();
        // Flush the transform so the transition restored below does not animate it.
        track.getBoundingClientRect();
        track.style.transitionDuration = '';
    }

    _sync() {
        const count = this._snaps.length;
        const canScrollPrev = this.loopValue ? count > 1 : this._index > 0;
        const canScrollNext = this.loopValue ? count > 1 : this._index < count - 1;

        this.previousTargets.forEach((button) => {
            button.disabled = !canScrollPrev;
        });
        this.nextTargets.forEach((button) => {
            button.disabled = !canScrollNext;
        });

        if (this._lastIndex !== this._index || this._lastCount !== count) {
            this._lastIndex = this._index;
            this._lastCount = count;
            this.dispatch('select', { detail: { index: this._index, count } });
        }
    }

    _onResize() {
        if (this._dragging) {
            return;
        }

        this.measure();
        this._position = this._snaps[this._index];
        this._translateWithoutAnimation();
        this._sync();
    }

    _onDragMove(event) {
        if (event.pointerId !== this._pointerId) {
            return;
        }

        const coordinate = this._pointerCoordinate(event);
        const delta = coordinate - this._dragOrigin;

        if (!this._dragMoved) {
            if (Math.abs(delta) < DRAG_THRESHOLD) {
                return;
            }

            this._dragMoved = true;
            this._dragging = true;
            this.trackTarget.style.transitionDuration = '0ms';
        }

        if (event.cancelable) {
            event.preventDefault();
        }

        let position = this._dragStartPosition + this._axisSign * delta;
        // Rubber band the drag past the first and last slide.
        if (position < 0) {
            position /= 3;
        } else if (position > this._maxPosition) {
            position = this._maxPosition + (position - this._maxPosition) / 3;
        }

        this._position = position;
        this._translate();

        const elapsed = event.timeStamp - this._dragLastTime;
        if (elapsed > 0) {
            this._dragVelocity = (coordinate - this._dragLastCoordinate) / elapsed;
            this._dragLastCoordinate = coordinate;
            this._dragLastTime = event.timeStamp;
        }
    }

    _onDragEnd(event) {
        if (event.pointerId !== this._pointerId) {
            return;
        }

        this._dragAbortController?.abort();
        this._dragAbortController = null;
        this._pointerId = null;

        if (!this._dragMoved) {
            this.resumeAutoplay();

            return;
        }

        this._dragMoved = false;
        this._dragging = false;
        this.trackTarget.style.transitionDuration = '';
        this._suppressNextClick();

        const projected = this._position + this._axisSign * this._dragVelocity * DRAG_MOMENTUM;
        let nearest = 0;
        let smallestDistance = Number.POSITIVE_INFINITY;
        this._snaps.forEach((snap, index) => {
            const distance = Math.abs(snap - projected);
            if (distance < smallestDistance) {
                smallestDistance = distance;
                nearest = index;
            }
        });

        this.select(nearest);
        this.resumeAutoplay();
    }

    /** Swallows the click a drag ends with, so dragging over a link or a button does not activate it. */
    _suppressNextClick() {
        const suppress = (event) => {
            event.preventDefault();
            event.stopPropagation();
        };

        this.viewportTarget.addEventListener('click', suppress, { capture: true });
        window.setTimeout(() => this.viewportTarget.removeEventListener('click', suppress, { capture: true }), 0);
    }

    _pointerCoordinate(event) {
        return this._isHorizontal ? event.clientX : event.clientY;
    }

    _restartAutoplay() {
        this._stopAutoplay();

        if (this.autoplayValue <= 0 || this._autoplayPaused) {
            return;
        }

        this._autoplayTimer = window.setInterval(() => {
            if (document.hidden) {
                return;
            }

            // Autoplay always rewinds to the first slide, even when the carousel does not loop.
            this.select(this._index >= this._snaps.length - 1 ? 0 : this._index + 1);
        }, this.autoplayValue);
    }

    _stopAutoplay() {
        if (this._autoplayTimer) {
            window.clearInterval(this._autoplayTimer);
            this._autoplayTimer = null;
        }
    }
}
