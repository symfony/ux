import { Controller } from '@hotwired/stimulus';

// Two scroll positions within this distance are treated as equal, to absorb zoom and HiDPI rounding.
const SCROLL_POSITION_EPSILON = 0.5;
// How long `data-autoscrolling` stays set after a programmatic scroll to the end.
const AUTOSCROLLING_CLEAR_DELAY = 180;

const FOLLOWING_END = 'following-end';
const FREE_SCROLLING = 'free-scrolling';
const ANCHORED = 'anchored';
const SETTLING_JUMP = 'settling-jump';

/**
 * Scroll behavior of a chat transcript: it opens at a chosen position, settles a new turn near the top,
 * follows a streamed reply only while the reader stays at the end, and keeps the reader's place when
 * older rows are added above.
 *
 * @value  autoScroll              Whether the viewport follows new content while it is at the end of the transcript.
 * @value  defaultScrollPosition   Where the transcript opens on the first render: `start`, `end` or `last-anchor`.
 * @value  scrollEdgeThreshold     Distance in pixels from an edge that still counts as being at that edge.
 * @value  scrollPreviousItemPeek  Pixels of the previous item kept visible above a newly anchored item.
 * @value  scrollMargin            Margin in pixels kept on the aligned edge when scrolling to an item.
 * @target root                    The frame, which reflects the scroll state like the viewport.
 * @target viewport                The scrollable element.
 * @target content                 The element holding the rows of the transcript.
 * @target spacer                  The empty row that gives a newly anchored turn room to settle near the top.
 * @target button                  A button scrolling to one edge, active only while there is content in its direction.
 * @target anchorLink              A control calling `scrollToMessage`, marked with `aria-current` while its row is the current turn.
 * @action scrollToStart           Scrolls to the start of the transcript.
 * @action scrollToEnd             Scrolls to the end of the transcript, and follows new content again when `autoScroll` is on.
 * @action scrollToMessage         Scrolls to the row whose `messageId` is the `id` param, aligned with the `align` param.
 */
export default class extends Controller {
    static targets = ['root', 'viewport', 'content', 'spacer', 'button', 'anchorLink'];
    static values = {
        autoScroll: Boolean,
        defaultScrollPosition: { type: String, default: 'end' },
        scrollEdgeThreshold: { type: Number, default: 8 },
        scrollPreviousItemPeek: { type: Number, default: 64 },
        scrollMargin: { type: Number, default: 0 },
    };

    #mode;
    #anchoredItem = null;
    #handledAnchors = new WeakSet();
    #itemCount = 0;
    #firstItem = null;
    #lastScrollTop = 0;
    #spacerHeight = 0;
    #autoscrolling = false;
    #autoscrollingTimeout = null;
    #defaultPositionApplied = false;
    #pendingScrollToMessage = null;
    #prependAnchor = null;
    #stateFrame = null;
    #resizeFrame = null;
    #currentAnchorFrame = null;
    #contentObserver = null;
    #resizeObserver = null;

    initialize() {
        this.#mode = this.autoScrollValue ? FOLLOWING_END : FREE_SCROLLING;
    }

    connect() {
        this.#contentObserver = new MutationObserver(() => this.#handleContentChange());
        this.#contentObserver.observe(this.contentTarget, { childList: true });

        // Coalesced on a frame: the resize handler changes the spacer inside an observed element,
        // which would otherwise report "ResizeObserver loop completed with undelivered notifications".
        this.#resizeObserver = new ResizeObserver(() => {
            cancelAnimationFrame(this.#resizeFrame);
            this.#resizeFrame = requestAnimationFrame(() => this.#handleResize());
        });
        this.#resizeObserver.observe(this.viewportTarget);
        this.#resizeObserver.observe(this.contentTarget);

        this.#handleContentChange();
        if (0 === this.#itemCount) {
            this.#clearPendingScroll();
        }
    }

    disconnect() {
        this.#contentObserver.disconnect();
        this.#resizeObserver.disconnect();
        cancelAnimationFrame(this.#stateFrame);
        cancelAnimationFrame(this.#resizeFrame);
        cancelAnimationFrame(this.#currentAnchorFrame);
        clearTimeout(this.#autoscrollingTimeout);
        this.#stateFrame = null;
        this.#resizeFrame = null;
        this.#currentAnchorFrame = null;
        this.#autoscrollingTimeout = null;
        this.#autoscrolling = false;
    }

    anchorLinkTargetConnected() {
        this.#scheduleCurrentAnchorSync();
    }

    scrollToStart(event) {
        this.#blurButton(event);
        this.#scrollToStart(event?.params?.behavior ?? 'auto');
    }

    scrollToEnd(event) {
        this.#blurButton(event);
        this.#scrollToEnd(event?.params?.behavior ?? 'auto');
    }

    scrollToMessage({ params: { id, align = 'start', behavior = 'auto' } = {} } = {}) {
        if (undefined === id) {
            return;
        }

        const messageId = String(id);
        const item = this.#findItem(messageId);
        if (!item) {
            // Rows that are not rendered yet, like a transcript loaded after the page, get the jump once they arrive.
            if (0 === this.#itemCount) {
                this.#pendingScrollToMessage = { messageId, align, behavior };
                this.#markDefaultPositionApplied();
            }

            return;
        }

        this.#markDefaultPositionApplied();
        this.#scrollToElement(item, { align, behavior });
    }

    handleScroll() {
        this.#commitScrollState();
        this.#scheduleCurrentAnchorSync();
        this.#capturePrependAnchor();
    }

    // A deliberate gesture releases the follow, the turn anchoring and a jump in flight, so the reader is never fought.
    handleUserScroll() {
        if (FREE_SCROLLING !== this.#mode) {
            this.#anchoredItem = null;
            this.#mode = FREE_SCROLLING;
        }
    }

    #handleContentChange() {
        this.#keepSpacerLast();

        const items = this.#items();
        const previousItemCount = this.#itemCount;
        const previousFirstItem = this.#firstItem;
        this.#itemCount = items.length;
        this.#firstItem = items[0] ?? null;

        this.#reconcileScrollPosition(items, previousItemCount, previousFirstItem);
        this.#capturePrependAnchor();
    }

    // The order of the cases matters: first content, prepended rows, appended rows, then updated rows.
    #reconcileScrollPosition(items, previousItemCount, previousFirstItem) {
        if (this.#flushPendingScrollToMessage()) {
            return;
        }

        if (0 === previousItemCount) {
            if (this.#applyDefaultScrollPosition()) {
                return;
            }

            if (items.length > 0 && this.autoScrollValue) {
                this.#scrollToEnd('auto');

                return;
            }

            this.#commitScrollState();
            this.#scheduleCurrentAnchorSync();

            return;
        }

        if (this.#preservesScrollOnPrepend() && previousFirstItem && items.indexOf(previousFirstItem) > 0) {
            // Rows added above are not new turns: the reader stays on the row they were looking at.
            this.#restorePrependAnchor();

            return;
        }

        if (items.length > previousItemCount) {
            const newAnchors = items.slice(previousItemCount).filter(isScrollAnchor);
            if (newAnchors.length > 0) {
                // A batch of turns arriving while the reader follows the end keeps following it,
                // instead of jumping back to the first turn of the batch.
                if (newAnchors.length > 1 && this.autoScrollValue && FOLLOWING_END === this.#mode) {
                    this.#scrollToEnd('auto');

                    return;
                }

                this.#anchorTurn(newAnchors[0]);

                return;
            }
        }

        if (items.length === previousItemCount) {
            // A row that becomes an anchor in place, like a reply turned into a new turn, settles like an appended one.
            const anchor = items.find((item) => isScrollAnchor(item) && !this.#handledAnchors.has(item));
            if (anchor) {
                this.#anchorTurn(anchor);

                return;
            }
        }

        if (FOLLOWING_END === this.#mode && this.autoScrollValue) {
            this.#scrollToEnd('auto');

            return;
        }

        this.#commitScrollState();
        this.#scheduleCurrentAnchorSync();
    }

    #handleResize() {
        if (this.#applyDefaultScrollPosition()) {
            return;
        }

        if (FOLLOWING_END === this.#mode && this.autoScrollValue) {
            this.#scrollToEnd('auto');

            return;
        }

        // Hold the anchored turn in place while the content below it changes, like a streamed reply.
        const previousSpacerHeight = this.#spacerHeight;
        if (this.#reanchorTurn()) {
            // Once the reply has used up the spacer, it fills the viewport and the reader is at the live edge.
            // Waiting for that transition keeps a turn taller than the viewport, placed without spacer, held.
            if (this.autoScrollValue && previousSpacerHeight > 0 && 0 === this.#spacerHeight) {
                this.#scrollToEnd('auto');
            }

            return;
        }

        this.#scheduleStateCommit();
        this.#scheduleCurrentAnchorSync();
    }

    #applyDefaultScrollPosition() {
        // A transcript in a hidden panel has no height yet: its position is applied once it is shown.
        if (this.#defaultPositionApplied || 0 === this.#itemCount || 0 === this.viewportTarget.clientHeight) {
            return false;
        }

        if ('start' === this.defaultScrollPositionValue) {
            this.#scrollToStart('auto');
        } else if ('last-anchor' === this.defaultScrollPositionValue) {
            this.#scrollToLastAnchor();
        } else {
            this.#scrollToEnd('auto');
        }

        this.#markDefaultPositionApplied();

        return true;
    }

    #scrollToLastAnchor() {
        const anchor = this.#items().findLast(isScrollAnchor);
        if (!anchor) {
            this.#scrollToEnd('auto');

            return;
        }

        // A last turn that fits below its anchor opens at the end, which shows it whole without a gap below.
        const lastTurnHeight = this.#contentBottom() - elementScrollTop(anchor, this.viewportTarget);
        if (lastTurnHeight <= this.viewportTarget.clientHeight) {
            this.#scrollToEnd('auto');

            return;
        }

        this.#scrollToElement(anchor, { align: 'start' }, { keepPreviousPeek: true });
    }

    #flushPendingScrollToMessage() {
        if (!this.#pendingScrollToMessage) {
            return false;
        }

        const { messageId, align, behavior } = this.#pendingScrollToMessage;
        const item = this.#findItem(messageId);
        if (!item) {
            return false;
        }

        this.#pendingScrollToMessage = null;
        this.#scrollToElement(item, { align, behavior });

        return true;
    }

    #anchorTurn(item) {
        this.#scrollToElement(item, { align: 'start' }, { keepPreviousPeek: true });
        this.#handledAnchors.add(item);
    }

    #reanchorTurn() {
        if (ANCHORED !== this.#mode || !this.#anchoredItem?.isConnected) {
            return false;
        }

        // Placing the turn again resizes the spacer for the new content height.
        this.#scrollToElement(this.#anchoredItem, { align: 'start' }, { keepPreviousPeek: true });

        return true;
    }

    #scrollToStart(behavior) {
        this.#setSpacerHeight(0);
        this.#anchoredItem = null;
        this.#mode = FREE_SCROLLING;
        this.#scrollToPosition(0, { behavior });
        this.#scheduleCurrentAnchorSync();
    }

    #scrollToEnd(behavior) {
        const viewport = this.viewportTarget;

        this.#setSpacerHeight(0);
        this.#anchoredItem = null;
        this.#mode = this.autoScrollValue ? FOLLOWING_END : FREE_SCROLLING;
        this.#scrollToPosition(Math.max(0, viewport.scrollHeight - viewport.clientHeight), {
            behavior,
            autoscrolling: true,
        });
        this.#scheduleCurrentAnchorSync();
    }

    #scrollToElement(element, { align = 'start', behavior = 'auto' } = {}, { keepPreviousPeek = false } = {}) {
        const viewport = this.viewportTarget;
        const scrollMargin = this.scrollMarginValue + (keepPreviousPeek ? this.scrollPreviousItemPeekValue : 0);
        const scrollTop = this.#alignedScrollTop(element, align, scrollMargin);

        // The spacer lengthens the transcript so a turn near the end can still settle near the top.
        this.#setSpacerHeight(scrollTop + viewport.clientHeight - this.#contentBottom());
        // Rows prepended before this scroll settles keep the target in place, instead of the row seen before.
        this.#prependAnchor = { element, viewportTop: elementViewportTop(element, viewport) };
        this.#mode = keepPreviousPeek ? ANCHORED : SETTLING_JUMP;
        this.#anchoredItem = keepPreviousPeek ? element : null;

        this.#scrollToPosition(scrollTop, { behavior });
        this.#scheduleCurrentAnchorSync();
    }

    #scrollToPosition(scrollTop, { behavior = 'auto', autoscrolling = false } = {}) {
        const viewport = this.viewportTarget;
        const top = Math.max(0, scrollTop);

        if (Math.abs(viewport.scrollTop - top) <= SCROLL_POSITION_EPSILON) {
            viewport.scrollTop = top;
            this.#commitScrollState();

            return;
        }

        if (autoscrolling) {
            this.#setAutoscrolling(true);
        }

        viewport.scrollTo({ top, behavior });
        this.#scheduleStateCommit();
    }

    #alignedScrollTop(element, align, scrollMargin) {
        const viewport = this.viewportTarget;
        const top = elementScrollTop(element, viewport);
        const height = element.getBoundingClientRect().height;
        const padding = blockPadding(this.contentTarget);

        if ('center' === align) {
            const insetHeight = Math.max(0, viewport.clientHeight - padding.start - padding.end);

            return top - padding.start - (insetHeight - height) / 2 - scrollMargin;
        }

        if ('end' === align) {
            return top - viewport.clientHeight + height + padding.end + scrollMargin;
        }

        if ('nearest' === align) {
            const visibleTop = viewport.scrollTop + padding.start;
            const visibleBottom = viewport.scrollTop + viewport.clientHeight - padding.end;

            if (top >= visibleTop && top + height <= visibleBottom) {
                return viewport.scrollTop;
            }

            if (top >= visibleTop) {
                return top + height - viewport.clientHeight + padding.end + scrollMargin;
            }
        }

        return top - padding.start - scrollMargin;
    }

    #setSpacerHeight(height) {
        const nextHeight = Math.max(0, Math.ceil(height));
        if (nextHeight === this.#spacerHeight) {
            return;
        }

        this.#spacerHeight = nextHeight;
        this.spacerTarget.hidden = 0 === nextHeight;
        this.spacerTarget.style.height = `${nextHeight}px`;
        // The spacer is not a row: it cancels the gap the content puts before it.
        this.spacerTarget.style.marginTop = nextHeight > 0 ? `${-flexGap(this.contentTarget)}px` : '';
    }

    #setAutoscrolling(autoscrolling) {
        clearTimeout(this.#autoscrollingTimeout);
        this.#autoscrollingTimeout = null;

        if (autoscrolling !== this.#autoscrolling) {
            this.#autoscrolling = autoscrolling;
            this.#commitScrollState();
        }

        if (autoscrolling) {
            this.#autoscrollingTimeout = setTimeout(() => {
                this.#autoscrollingTimeout = null;
                this.#autoscrolling = false;
                this.#commitScrollState();
            }, AUTOSCROLLING_CLEAR_DELAY);
        }
    }

    #scheduleStateCommit() {
        if (null !== this.#stateFrame) {
            return;
        }

        this.#stateFrame = requestAnimationFrame(() => {
            this.#stateFrame = null;
            this.#commitScrollState();
        });
    }

    #commitScrollState() {
        const viewport = this.viewportTarget;
        const scrollable = {
            start: viewport.scrollTop > this.scrollEdgeThresholdValue,
            end: this.#contentBottom() - viewport.scrollTop - viewport.clientHeight > this.scrollEdgeThresholdValue,
        };

        this.#reconcileFollowMode(scrollable);

        // While following, the scroller is already closing the gap a streamed chunk opened:
        // reporting it would make the scroll button flicker once per chunk.
        this.#writeScrollState(FOLLOWING_END === this.#mode ? { ...scrollable, end: false } : scrollable);
    }

    // Following starts at the end and stops on any scroll up, a scrollbar drag included. It does not start while a
    // turn is anchored: the spacer makes that turn read as "at the end", and the first streamed chunk would then pull
    // the reader off it. Content growing below the live edge is not a scroll up, and does not stop the following.
    #reconcileFollowMode(scrollable) {
        const scrollTop = this.viewportTarget.scrollTop;
        const scrolledUp = scrollTop < this.#lastScrollTop - SCROLL_POSITION_EPSILON;
        this.#lastScrollTop = scrollTop;

        if (this.autoScrollValue && !scrollable.end && SETTLING_JUMP !== this.#mode && ANCHORED !== this.#mode) {
            this.#mode = FOLLOWING_END;
        } else if (FOLLOWING_END === this.#mode && scrollable.end && scrolledUp && !this.#autoscrolling) {
            this.#mode = FREE_SCROLLING;
        }
    }

    #writeScrollState({ start, end }) {
        const scrollable = [start && 'start', end && 'end'].filter(Boolean).join(' ');

        for (const element of [this.element, ...this.rootTargets, this.viewportTarget]) {
            if (scrollable) {
                element.setAttribute('data-scrollable', scrollable);
            } else {
                element.removeAttribute('data-scrollable');
            }
            element.toggleAttribute('data-autoscrolling', this.#autoscrolling);
        }

        for (const button of this.buttonTargets) {
            const active = 'start' === button.dataset.direction ? start : end;

            button.dataset.active = active ? 'true' : 'false';
            button.inert = !active;
            if (active) {
                button.removeAttribute('tabindex');
            } else {
                button.tabIndex = -1;
            }
        }
    }

    // The current turn is the last anchor that reached the reading line, where anchored turns settle. It stays
    // current once its top scrolls out of view, and a turn only peeking above the line is not current yet.
    #scheduleCurrentAnchorSync() {
        if (!this.hasAnchorLinkTarget || null !== this.#currentAnchorFrame) {
            return;
        }

        this.#currentAnchorFrame = requestAnimationFrame(() => {
            this.#currentAnchorFrame = null;

            const readingLine =
                this.viewportTarget.getBoundingClientRect().top +
                this.scrollMarginValue +
                this.scrollPreviousItemPeekValue;
            const current = this.#items()
                .filter((item) => isScrollAnchor(item) && item.dataset.messageId)
                .findLast((item) => item.getBoundingClientRect().top <= readingLine + SCROLL_POSITION_EPSILON);
            const currentId = current?.dataset.messageId ?? null;

            for (const link of this.anchorLinkTargets) {
                const isCurrent = null !== currentId && String(link.dataset.messageScrollerIdParam) === currentId;

                link.setAttribute('aria-current', isCurrent ? 'location' : 'false');
            }
        });
    }

    #capturePrependAnchor() {
        const viewportRect = this.viewportTarget.getBoundingClientRect();
        const item = this.#items().find((candidate) => {
            if (!candidate.dataset.messageId) {
                return false;
            }

            const rect = candidate.getBoundingClientRect();

            return rect.bottom > viewportRect.top && rect.top < viewportRect.bottom;
        });

        this.#prependAnchor = item
            ? { element: item, viewportTop: elementViewportTop(item, this.viewportTarget) }
            : null;
    }

    // Compares the row with its place in the viewport, so this does nothing where the browser's own scroll
    // anchoring already kept it there, and only corrects the scroll where it did not.
    #restorePrependAnchor() {
        const anchor = this.#prependAnchor;
        if (!anchor?.element.isConnected) {
            return;
        }

        const delta = elementViewportTop(anchor.element, this.viewportTarget) - anchor.viewportTop;
        if (Math.abs(delta) <= SCROLL_POSITION_EPSILON) {
            return;
        }

        this.viewportTarget.scrollTop += delta;
        this.#scheduleStateCommit();
        this.#scheduleCurrentAnchorSync();
    }

    #contentBottom() {
        const viewport = this.viewportTarget;
        const padding = blockPadding(this.contentTarget);
        const viewportTop = viewport.getBoundingClientRect().top;
        let bottom = padding.start + padding.end;

        for (const item of this.#items()) {
            bottom = Math.max(
                bottom,
                item.getBoundingClientRect().bottom - viewportTop + viewport.scrollTop + padding.end
            );
        }

        return bottom;
    }

    // Rows added with a plain append, like a Turbo Stream, land after the spacer, which must stay the last child.
    #keepSpacerLast() {
        if (this.contentTarget.lastElementChild === this.spacerTarget) {
            return;
        }

        this.contentTarget.append(this.spacerTarget);
        this.#contentObserver.takeRecords();
    }

    #items() {
        return Array.from(this.contentTarget.children).filter((child) => child !== this.spacerTarget);
    }

    #findItem(messageId) {
        return this.#items().find((item) => item.dataset.messageId === messageId) ?? null;
    }

    #preservesScrollOnPrepend() {
        return 'false' !== this.viewportTarget.dataset.preserveScrollOnPrepend;
    }

    #markDefaultPositionApplied() {
        this.#defaultPositionApplied = true;
        this.#clearPendingScroll();
    }

    #clearPendingScroll() {
        for (const element of [...this.rootTargets, this.viewportTarget]) {
            element.removeAttribute('data-pending-scroll');
        }
    }

    // The button turns inert once the viewport reaches its edge, so it must not keep the focus.
    #blurButton(event) {
        if (this.buttonTargets.includes(event?.currentTarget)) {
            event.currentTarget.blur();
        }
    }
}

function isScrollAnchor(item) {
    return 'true' === item.dataset.scrollAnchor;
}

function elementScrollTop(element, viewport) {
    return elementViewportTop(element, viewport) + viewport.scrollTop;
}

function elementViewportTop(element, viewport) {
    return element.getBoundingClientRect().top - viewport.getBoundingClientRect().top;
}

function blockPadding(element) {
    const style = getComputedStyle(element);

    return { start: parseFloat(style.paddingBlockStart) || 0, end: parseFloat(style.paddingBlockEnd) || 0 };
}

function flexGap(element) {
    return parseFloat(getComputedStyle(element).rowGap) || 0;
}
