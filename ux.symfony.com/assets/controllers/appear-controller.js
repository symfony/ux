import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['loader'];

    loaderTargetConnected(element) {
        this.observer ??= new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.dispatchEvent(new CustomEvent('appear', {detail: {entry}}));
                }
            });
        });
        this.observer?.observe(element);
    }

    loaderTargetDisconnected(element) {
        this.observer?.unobserve(element);
    }
}
