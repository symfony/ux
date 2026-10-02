/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { StreamActions, visit } from '@hotwired/turbo';

/**
 * Redirects the browser to the URL given by the "url" attribute.
 *
 * Only the http and https schemes are accepted: navigating to a "javascript:"
 * URL would execute it, turning a user-controlled redirect target into XSS.
 */
StreamActions.redirect = function () {
    const url = this.getAttribute('url');
    if (null === url) {
        throw new Error('The "url" attribute is required on <turbo-stream action="redirect">.');
    }

    const target = new URL(url, document.baseURI);
    if ('http:' !== target.protocol && 'https:' !== target.protocol) {
        throw new Error(
            `The "url" attribute of <turbo-stream action="redirect"> must use the http or https scheme, "${target.protocol}" given.`
        );
    }

    if (target.origin !== window.location.origin) {
        window.location.assign(target.href);

        return;
    }

    visit(target.href, { action: this.hasAttribute('advance') ? 'advance' : 'replace' });
};
