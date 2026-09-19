<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Inspector\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Serves the bundled inspector module to the script tag injected in the page.
 *
 * The file path is fixed and takes no parameter, so the endpoint cannot be used
 * to read anything else from the bundle. It is registered as a service only when
 * the inspector is enabled, and revalidates through an ETag so a rebuilt module
 * is picked up on the next reload.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class InspectorController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $path = \dirname(__DIR__, 2).'/assets/dist/inspector.js';

        $response = new BinaryFileResponse($path, headers: [
            'Content-Type' => 'text/javascript; charset=UTF-8',
            'Cache-Control' => 'private, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ], public: false);

        // "no-cache" revalidates on every navigation, so the validator is computed
        // on every page load. xxh128 reads the same bytes as the default sha256 for
        // a fraction of the time, and an ETag needs no cryptographic strength.
        $response->setEtag(hash_file('xxh128', $path) ?: null);
        // Turns the response into a 304 when the browser already has this version.
        $response->isNotModified($request);

        return $response;
    }
}
