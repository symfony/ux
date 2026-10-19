<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Answers the requests to the historical locations.
 *
 * A Hotwire Native app intercepts these locations and never displays the response, but a browser can still reach them, from its history or a bookmark.
 *
 * @internal
 */
final class HistoricalLocationController
{
    public function recede(): Response
    {
        return $this->createResponse('Going back…');
    }

    public function refresh(): Response
    {
        return $this->createResponse('Refreshing…');
    }

    public function resume(): Response
    {
        return $this->createResponse('Staying put…');
    }

    private function createResponse(string $message): Response
    {
        return new Response(
            \sprintf('<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="robots" content="noindex"><title>%1$s</title></head><body><p>%1$s</p></body></html>', $message),
            Response::HTTP_OK,
            ['X-Robots-Tag' => 'noindex', 'Cache-Control' => 'no-store'],
        );
    }
}
