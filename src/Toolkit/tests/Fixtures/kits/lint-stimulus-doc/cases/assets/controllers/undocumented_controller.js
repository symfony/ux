import { Controller } from '@hotwired/stimulus'

// No doc tags at all: documentation is opt-in, so this controller must not be reported.
export default class extends Controller {
    static values = { delay: Number }
    static targets = ['panel']

    toggle() {}
}
