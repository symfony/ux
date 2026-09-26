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

/**
 * Outside every prefix the root provider knows about, and carries no attribute:
 * the trail must still exist, and be empty.
 */
final class PlainController
{
    public function __invoke(): Response
    {
        return new Response('plain');
    }
}
