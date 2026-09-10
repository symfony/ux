<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Fixtures\Subject;

/**
 * A plain object standing for any subject a subject resolver may produce.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class FixtureData
{
    public function __construct(public readonly string $secret = 'the-secret') {}
}
