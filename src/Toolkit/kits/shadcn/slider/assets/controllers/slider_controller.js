import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['track', 'range', 'thumb', 'input'];
    static values = {
        min: { type: Number, default: 0 },
        max: { type: Number, default: 100 },
        step: { type: Number, default: 1 },
        orientation: { type: String, default: 'horizontal' },
    };

    connect() {
        this._activeThumb = null;
        this._abortController = new AbortController();
        const { signal } = this._abortController;

        this.thumbTargets.forEach((thumb, index) => {
            thumb.addEventListener('pointerdown', (e) => this._onThumbDown(e, index), { signal });
            thumb.addEventListener('keydown', (e) => this._onThumbKey(e, index), { signal });
        });
        this.trackTarget.addEventListener('pointerdown', (e) => this._onTrackDown(e), { signal });

        this.update();
    }

    disconnect() {
        this._abortController?.abort();
        this._abortController = null;
        this._dragAbortController?.abort();
        this._dragAbortController = null;
        this._activeThumb = null;
    }

    _startDrag() {
        this._dragAbortController?.abort();
        this._dragAbortController = new AbortController();
        const { signal } = this._dragAbortController;
        window.addEventListener('pointermove', (e) => this._onPointerMove(e), { signal });
        window.addEventListener('pointerup', () => this._onPointerUp(), { signal });
    }

    _onThumbDown(event, index) {
        if (this._isDisabled()) return;
        event.preventDefault();
        this._activeThumb = index;
        this.thumbTargets[index].focus();
        this._startDrag();
    }

    _onTrackDown(event) {
        if (this._isDisabled()) return;
        const ratio = this._pointerRatio(event);
        const value = this._ratioToValue(ratio);
        const index = this._nearestThumbIndex(value);
        this._setThumbValue(index, value);
        this._activeThumb = index;
        this.thumbTargets[index].focus();
        this._startDrag();
    }

    _onPointerMove(event) {
        if (this._activeThumb === null) return;
        const ratio = this._pointerRatio(event);
        this._setThumbValue(this._activeThumb, this._ratioToValue(ratio));
    }

    _onPointerUp() {
        this._activeThumb = null;
        this._dragAbortController?.abort();
        this._dragAbortController = null;
    }

    _onThumbKey(event, index) {
        if (this._isDisabled()) return;
        const current = Number(this.inputTargets[index].value);
        const step = this.stepValue;
        let next = current;
        switch (event.key) {
            case 'ArrowLeft':
            case 'ArrowDown':
                next = current - step;
                break;
            case 'ArrowRight':
            case 'ArrowUp':
                next = current + step;
                break;
            case 'Home':
                next = this.minValue;
                break;
            case 'End':
                next = this.maxValue;
                break;
            case 'PageDown':
                next = current - step * 10;
                break;
            case 'PageUp':
                next = current + step * 10;
                break;
            default:
                return;
        }
        event.preventDefault();
        this._setThumbValue(index, next);
    }

    _pointerRatio(event) {
        const rect = this.trackTarget.getBoundingClientRect();
        const isVertical = this.orientationValue === 'vertical';
        const isRtl = getComputedStyle(this.element).direction === 'rtl';
        let ratio;
        if (isVertical) {
            ratio = 1 - (event.clientY - rect.top) / rect.height;
        } else {
            ratio = (event.clientX - rect.left) / rect.width;
            if (isRtl) ratio = 1 - ratio;
        }
        return Math.max(0, Math.min(1, ratio));
    }

    _ratioToValue(ratio) {
        const raw = this.minValue + ratio * (this.maxValue - this.minValue);
        const stepped = Math.round(raw / this.stepValue) * this.stepValue;
        return this._roundToStep(stepped);
    }

    _roundToStep(value) {
        const stepString = String(this.stepValue);
        const decimals = stepString.includes('.') ? stepString.split('.')[1].length : 0;
        return Number(value.toFixed(decimals));
    }

    _nearestThumbIndex(value) {
        const values = this.inputTargets.map((i) => Number(i.value));
        let bestIndex = 0;
        let bestDelta = Math.abs(values[0] - value);
        for (let i = 1; i < values.length; i++) {
            const d = Math.abs(values[i] - value);
            if (d < bestDelta) {
                bestDelta = d;
                bestIndex = i;
            }
        }
        return bestIndex;
    }

    _setThumbValue(index, value) {
        value = this._roundToStep(Math.max(this.minValue, Math.min(this.maxValue, value)));
        const values = this.inputTargets.map((i) => Number(i.value));
        if (index > 0) value = Math.max(value, values[index - 1]);
        if (index < values.length - 1) value = Math.min(value, values[index + 1]);
        if (values[index] === value) return;
        this.inputTargets[index].value = value;
        this.update();
        this.element.dispatchEvent(new Event('input', { bubbles: true }));
        this.element.dispatchEvent(new Event('change', { bubbles: true }));
    }

    update() {
        const values = this.inputTargets.map((i) => Number(i.value));
        const range = this.maxValue - this.minValue;
        const toPercent = (v) => (range > 0 ? ((v - this.minValue) / range) * 100 : 0);
        const percents = values.map(toPercent);

        const isVertical = this.orientationValue === 'vertical';
        const isRtl = getComputedStyle(this.element).direction === 'rtl';

        const prop = isVertical ? 'bottom' : isRtl ? 'right' : 'left';
        this.thumbTargets.forEach((thumb, i) => {
            thumb.style.left = thumb.style.right = thumb.style.bottom = '';
            thumb.style[prop] = `${percents[i]}%`;
            thumb.setAttribute('aria-valuenow', values[i]);
        });

        const lo = percents.length > 1 ? Math.min(...percents) : 0;
        const hi = Math.max(...percents);

        this.rangeTarget.style.top =
            this.rangeTarget.style.bottom =
            this.rangeTarget.style.left =
            this.rangeTarget.style.right =
                '';
        if (isVertical) {
            this.rangeTarget.style.bottom = `${lo}%`;
            this.rangeTarget.style.top = `${100 - hi}%`;
        } else if (isRtl) {
            this.rangeTarget.style.right = `${lo}%`;
            this.rangeTarget.style.left = `${100 - hi}%`;
        } else {
            this.rangeTarget.style.left = `${lo}%`;
            this.rangeTarget.style.right = `${100 - hi}%`;
        }
    }

    _isDisabled() {
        return this.element.getAttribute('aria-disabled') === 'true' || this.element.matches('[data-disabled]');
    }
}
