import type { PluginInterface } from './PluginInterface';
import type Component from '../index';

export default class implements PluginInterface {
    private intersectionObserver: IntersectionObserver | null = null;

    attachToComponent(component: Component): void {
        if ('lazy' !== component.element.attributes.getNamedItem('loading')?.value) {
            return;
        }
        component.on('connect', () => {
            this.getObserver().observe(component.element);
        });
        component.on('disconnect', () => {
            this.intersectionObserver?.unobserve(component.element);
        });
    }

    private getObserver(): IntersectionObserver {
        if (!this.intersectionObserver) {
            this.intersectionObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.dispatchEvent(new CustomEvent('live:appear'));
                        observer.unobserve(entry.target);
                    }
                });
            });
        }

        return this.intersectionObserver;
    }
}
