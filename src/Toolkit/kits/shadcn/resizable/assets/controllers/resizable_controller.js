import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel', 'handle'];
    static values = { orientation: { type: String, default: 'horizontal' } };

    connect() {
        this.handleTargets.forEach((handle) => {
            handle.addEventListener('pointerdown', (event) => this._onPointerDown(event, handle));
            handle.addEventListener('keydown', (event) => this._onKeyDown(event, handle));
        });
    }

    _onPointerDown(event, handle) {
        if (event.button !== 0 && event.pointerType === 'mouse') return;
        event.preventDefault();
        handle.setPointerCapture?.(event.pointerId);

        const isVertical = this.orientationValue === 'vertical';
        const isRtl = !isVertical && getComputedStyle(this.element).direction === 'rtl';
        const siblings = this._neighborPanels(handle);
        if (!siblings) return;
        const { prev, next } = siblings;

        const startCoord = isVertical ? event.clientY : event.clientX;
        const prevStart = isVertical ? prev.getBoundingClientRect().height : prev.getBoundingClientRect().width;
        const nextStart = isVertical ? next.getBoundingClientRect().height : next.getBoundingClientRect().width;
        const total = prevStart + nextStart;
        const min = 20;

        const onMove = (e) => {
            const cur = isVertical ? e.clientY : e.clientX;
            let delta = cur - startCoord;
            if (isRtl) delta = -delta;
            let newPrev = Math.max(min, Math.min(total - min, prevStart + delta));
            let newNext = total - newPrev;
            prev.style.flex = `${newPrev} 1 0%`;
            next.style.flex = `${newNext} 1 0%`;
        };
        const onUp = () => {
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
        };
        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
    }

    _onKeyDown(event, handle) {
        const isVertical = this.orientationValue === 'vertical';
        const isRtl = !isVertical && getComputedStyle(this.element).direction === 'rtl';
        const step = event.shiftKey ? 40 : 8;

        let direction = 0;
        if (isVertical) {
            if (event.key === 'ArrowUp') direction = -1;
            else if (event.key === 'ArrowDown') direction = 1;
        } else {
            if (event.key === 'ArrowLeft') direction = isRtl ? 1 : -1;
            else if (event.key === 'ArrowRight') direction = isRtl ? -1 : 1;
        }
        if (direction === 0) return;
        event.preventDefault();

        const siblings = this._neighborPanels(handle);
        if (!siblings) return;
        const { prev, next } = siblings;

        const prevStart = isVertical ? prev.getBoundingClientRect().height : prev.getBoundingClientRect().width;
        const nextStart = isVertical ? next.getBoundingClientRect().height : next.getBoundingClientRect().width;
        const total = prevStart + nextStart;
        const min = 20;
        let newPrev = Math.max(min, Math.min(total - min, prevStart + direction * step));
        let newNext = total - newPrev;
        prev.style.flex = `${newPrev} 1 0%`;
        next.style.flex = `${newNext} 1 0%`;
    }

    _neighborPanels(handle) {
        const children = Array.from(this.element.children);
        const index = children.indexOf(handle);
        if (index <= 0 || index >= children.length - 1) return null;
        return { prev: children[index - 1], next: children[index + 1] };
    }
}
