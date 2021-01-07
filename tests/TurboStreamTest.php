<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
        $client->waitForElementToContain('body', 'This div replace the existing element with the DOM ID "form".');
        $this->assertSelectorTextContains('body', 'This div replace the existing element with the DOM ID "form".');

        $this->assertTrue(true);
    }
}
