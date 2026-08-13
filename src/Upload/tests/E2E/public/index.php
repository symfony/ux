<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * Front controller / PHP built-in server router for the E2E test kernel.
 *
 * Started by UploadE2ETest via `php -S 127.0.0.1:<port> <this file>`. It serves
 * two kinds of requests:
 *   - `/assets/*` static JavaScript (the built Stimulus controller and the
 *     @hotwired/stimulus ESM) so the real controller runs in the browser;
 *   - everything else is dispatched through the Symfony test kernel.
 *
 * The working directory (kernel cache/logs + upload storage) is passed via the
 * UX_UPLOAD_E2E_DIR environment variable so the test can isolate and clean it.
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Upload\Tests\E2E\TestKernel;

$packageDir = dirname(__DIR__, 3);

require $packageDir.'/vendor/autoload.php';

$uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$parsedPath = parse_url($uri, \PHP_URL_PATH);
$path = is_string($parsedPath) ? rawurldecode($parsedPath) : '/';

// Static asset serving: the browser needs the compiled controller and Stimulus.
$assets = [
    '/assets/stimulus.js' => $packageDir.'/assets/node_modules/@hotwired/stimulus/dist/stimulus.js',
];

if (isset($assets[$path]) || str_starts_with($path, '/assets/dist/')) {
    $file = $assets[$path] ?? $packageDir.'/assets/dist/'.basename($path);

    if (is_file($file) && (str_ends_with($file, '.js') || str_ends_with($file, '.css'))) {
        header('Content-Type: '.(str_ends_with($file, '.css') ? 'text/css' : 'text/javascript'));
        header('Cache-Control: no-store');
        readfile($file);

        return true;
    }

    http_response_code(404);

    return true;
}

$kernel = new TestKernel('test', true);

$request = Request::createFromGlobals();
if (str_starts_with($request->getPathInfo(), '/_upload')) {
    file_put_contents(
        (string) getenv('UX_UPLOAD_E2E_DIR').'/requests.log',
        $request->getMethod().' '.$request->getPathInfo()."\n",
        \FILE_APPEND | \LOCK_EX,
    );
}
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
