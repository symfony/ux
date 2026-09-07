<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Request;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\Turbo\TurboBundle;

/**
 * Tests the Turbo request listener.
 *
 * @author Alexander Hofbauer <a.hofbauer@fify.at>
 */
class RequestListenerTest extends WebTestCase
{
    public function testAddsTurboRequestFormat(): void
    {
        $client = static::createClient(server: [
            'HTTP_ACCEPT' => 'text/vnd.turbo-stream.html, text/html, application/xhtml+xml',
        ]);

        // simulate worker mode
        $client->disableReboot();

        // request twice to test if listener is always called
        $this->assertPreferredFormat();
        $this->assertPreferredFormat();
    }

    private function assertPreferredFormat(): void
    {
        /** @var KernelBrowser $client */
        $client = static::getClient();

        $client->request('POST', '/turboRequest');

        $response = $client->getResponse()->getContent();

        $expectedJson = json_encode([
            'preferred_format' => TurboBundle::STREAM_FORMAT,
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson ?: '', $response ?: '');
    }
}
