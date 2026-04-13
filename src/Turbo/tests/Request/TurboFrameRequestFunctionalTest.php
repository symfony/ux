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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Sébastien JEAN <sebastien.jean76@gmail.com>
 */
class TurboFrameRequestFunctionalTest extends WebTestCase
{
    public function testIsNotTurboFrameRequestWithoutHeader()
    {
        $client = static::createClient();
        $client->request('GET', '/turboFrameRequest');

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode(['turbo_is_frame_request' => false, 'turbo_frame_request_id' => null]),
            (string) $response->getContent()
        );
    }

    public function testIsTurboFrameRequestWithHeader()
    {
        $client = static::createClient();
        $client->request('GET', '/turboFrameRequest', [], [], ['HTTP_Turbo-Frame' => 'my_frame']);

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode(['turbo_is_frame_request' => true, 'turbo_frame_request_id' => 'my_frame']),
            (string) $response->getContent()
        );
    }
}
