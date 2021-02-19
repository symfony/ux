/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import '@symfony/stimulus-testing/setup';

// Tiny mock of EventSource for nodejs
if (typeof EventSource == 'undefined') {
    class EventSource {
        constructor(url) {
            this.url = url;
        }
    }

    window.EventSource = EventSource;
}
