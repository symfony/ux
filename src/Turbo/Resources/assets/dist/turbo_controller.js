import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

window.Turbo = Turbo;
class turbo_controller extends Controller {
}

export { turbo_controller as default };
