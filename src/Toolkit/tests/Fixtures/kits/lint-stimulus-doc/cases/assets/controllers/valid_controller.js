import { Controller } from '@hotwired/stimulus'

/**
 * @value  autoClose    Delay in milliseconds before auto-removal.
 * @value  orientation  The split direction, declared in object form.
 * @target timerbar     Countdown bar element.
 * @action close        Removes the element.
 */
export default class extends Controller {
    static values = { autoClose: Number, orientation: { type: String, default: 'horizontal' } }
    static targets = ['timerbar']

    close() {}
}
