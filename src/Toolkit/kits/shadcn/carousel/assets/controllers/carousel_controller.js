import { Controller } from '@hotwired/stimulus';
import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';

export default class extends Controller {
    static targets = ['viewport', 'previous', 'next'];

    static values = {
        orientation: { type: String, default: 'horizontal' },
        options: { type: Object, default: {} },
        autoplay: { type: Number, default: 0 },
    };

    #embla = null;
    #rtl = false;

    connect() {
        this.#rtl = 'rtl' === getComputedStyle(this.element).direction;

        // `axis` and `direction` are derived from the `orientation` prop and the rendered text
        // direction, which also drive the component's classes, so they always win over `options`.
        this.#embla = EmblaCarousel(
            this.viewportTarget,
            {
                ...this.optionsValue,
                axis: 'vertical' === this.orientationValue ? 'y' : 'x',
                direction: this.#rtl ? 'rtl' : 'ltr',
            },
            this.autoplayValue > 0
                ? [Autoplay({ delay: this.autoplayValue, stopOnMouseEnter: true, stopOnInteraction: false })]
                : []
        );

        this.#embla.on('select', this.#sync).on('reInit', this.#sync);
        this.#sync();
    }

    disconnect() {
        this.#embla?.destroy();
        this.#embla = null;
    }

    scrollPrev() {
        this.#embla?.scrollPrev();
    }

    scrollNext() {
        this.#embla?.scrollNext();
    }

    handleKeydown(event) {
        if (!this.#embla || event.target.closest('input, textarea, select, [contenteditable]')) {
            return;
        }

        let forward;
        if ('vertical' === this.orientationValue) {
            if ('ArrowUp' === event.key) {
                forward = false;
            } else if ('ArrowDown' === event.key) {
                forward = true;
            } else {
                return;
            }
        } else if ('ArrowLeft' === event.key) {
            forward = this.#rtl;
        } else if ('ArrowRight' === event.key) {
            forward = !this.#rtl;
        } else {
            return;
        }

        if (forward ? !this.#embla.canScrollNext() : !this.#embla.canScrollPrev()) {
            return;
        }

        event.preventDefault();

        if (forward) {
            this.scrollNext();
        } else {
            this.scrollPrev();
        }
    }

    #sync = () => {
        const embla = this.#embla;

        this.previousTargets.forEach((button) => {
            button.disabled = !embla.canScrollPrev();
        });
        this.nextTargets.forEach((button) => {
            button.disabled = !embla.canScrollNext();
        });

        this.dispatch('select', {
            detail: { index: embla.selectedScrollSnap(), count: embla.scrollSnapList().length },
        });
    };
}
