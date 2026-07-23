import { Controller } from '@hotwired/stimulus'

/**
 * @value autoClose delay in ms
 */
export default class extends Controller {
    static values = { autoClose: Number }
}
