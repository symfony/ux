/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from "stimulus";
import { connectStreamSource, disconnectStreamSource } from "@hotwired/turbo";

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @todo Allow to use polyfills for URL and EventSource.
 */
export default class extends Controller {
  initialize() {
    if (!this.element.id) {
      console.error(`The element must have an "id" attribute.`);
    }

    this.hub = this.element.getAttribute("data-hub");
    if (!this.hub) {
      console.error(`The element must have a "data-hub" attribute pointing to the Mercure hub.`);
    }

    const u = new URL(this.hub);
    u.searchParams.append("topic", this.element.id);

    this.url = u.toString();
  }

  connect() {
    this.es = new EventSource(this.url);
    connectStreamSource(this.es);
  }

  disconnect() {
    this.es.close();
    disconnectStreamSource(this.es);
  }
}
