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
 */
export default class extends Controller {
  initialize() {
    const topic = this.element.getAttribute("data-topic");
    if (!topic) {
      console.error(`The element must have a "data-topic" attribute.`);
      return;
    }

    const hub = this.element.getAttribute("data-hub");
    if (!hub) {
      console.error(`The element must have a "data-hub" attribute pointing to the Mercure hub.`);
      return;
    }

    const u = new URL(hub);
    u.searchParams.append("topic", topic);

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
