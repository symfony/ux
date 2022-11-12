<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests;

use Symfony\Component\Panther\PantherTestCase;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TurboStreamTest extends PantherTestCase
{
    public function testStream(): void
    {
        ($client = self::createPantherClient())->request('GET', '/');

        $client->submitForm('Trigger Turbo Stream!');
        $this->assertSelectorWillContain('body', 'This div replaces the existing element with the DOM ID "form".');
    }
}
