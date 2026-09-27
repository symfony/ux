<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

final class InvalidParentController
{
    #[Breadcrumb(label: 'first', parent: [self::class, 'second'])]
    public function first(): Response
    {
        return new Response();
    }

    #[Breadcrumb(label: 'second', parent: [self::class, 'first'])]
    public function second(): Response
    {
        return new Response();
    }

    #[Breadcrumb(label: 'self', parent: [self::class, 'self'])]
    public function self(): Response
    {
        return new Response();
    }

    #[Breadcrumb(label: 'unknown', parent: 'no_such_route')]
    public function unknownRoute(): Response
    {
        return new Response();
    }

    #[Breadcrumb(label: 'missing', parent: [self::class, 'missing'])]
    public function missingMethod(): Response
    {
        return new Response();
    }

    #[Breadcrumb(label: 'string action', parent: ProductIndexController::class.'::__invoke')]
    public function stringAction(): Response
    {
        return new Response();
    }

    #[Breadcrumb(label: 'malformed', parent: [self::class])]
    public function malformedAction(): Response
    {
        return new Response();
    }

    // Only the topmost crumb of a level may name the parent.
    #[Breadcrumb(label: 'top')]
    #[Breadcrumb(label: 'misplaced', parent: ProductIndexController::class)]
    public function misplaced(): Response
    {
        return new Response();
    }
}
