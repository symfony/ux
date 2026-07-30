import { Controller } from '@hotwired/stimulus'

/**
 * @value     autoClose    Delay in milliseconds before auto-removal.
 * @value     orientation  The split direction, declared in object form.
 * @target    timerbar     Countdown bar element.
 * @css-class loading      Applied to the element while a request is in flight.
 * @outlet    flash        Flash controller notified when the element is removed.
 * @action    close        Removes the element.
 */
export default class extends Controller {
    static values = { autoClose: Number, orientation: { type: String, default: 'horizontal' } }
    static targets = ['timerbar']
    static classes = ['loading']
    static outlets = ['flash']

    close() {}
}
