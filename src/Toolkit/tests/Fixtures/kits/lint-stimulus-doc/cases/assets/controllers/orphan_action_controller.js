import { Controller } from '@hotwired/stimulus'

/**
 * @action save Saves the thing.
 */
export default class extends Controller {
    connect() {
        // A bare call-site, not a definition: the documented `save` action is never defined.
        save();
    }
}
