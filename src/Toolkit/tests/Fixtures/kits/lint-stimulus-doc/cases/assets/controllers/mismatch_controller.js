import { Controller } from '@hotwired/stimulus'

/**
 * @value  ghost     Documented but not declared in the code.
 * @target timerbar  Countdown bar element.
 */
export default class extends Controller {
    static values = { autoClose: Number }
    static targets = ['timerbar']
}
