<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests\Fixtures\Navigation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Native\Navigation\HistoricalLocation;
use Symfony\UX\Native\Navigation\NativeNavigator;

/**
 * @internal
 */
final class NoteController
{
    public function index(Request $request): Response
    {
        return new Response(implode("\n", $request->getSession()->getFlashBag()->get('success')));
    }

    public function new(Request $request, NativeNavigator $navigator): Response
    {
        $request->getSession()->getFlashBag()->add('success', 'Note created.');

        return $navigator->redirectOrToRoute(HistoricalLocation::Refresh, 'app_notes');
    }
}
