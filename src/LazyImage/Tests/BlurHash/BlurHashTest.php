<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Symfony\UX\LazyImage;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LazyImage\BlurHash\BlurHashInterface;
use Tests\Symfony\UX\LazyImage\Kernel\TwigAppKernel;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class BlurHashTest extends TestCase
{
    public function testEncode()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var BlurHashInterface $blurHash */
        $blurHash = $container->get('test.lazy_image.blur_hash');

        $this->assertSame(
            'L54ec*~q_3?bofoffQWB9F9FD%IU',
            $blurHash->encode(__DIR__.'/../Fixtures/logo.png')
        );
    }

    public function testCreateDataUriThumbnail()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var BlurHashInterface $blurHash */
        $blurHash = $container->get('test.lazy_image.blur_hash');

        $this->assertSame(
            'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODAK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg8MEBcUGBgXFBYWGh0lHxobIxwWFiAsICMmJykqKRkfLTAtKDAlKCko/9sAQwEHBwcKCAoTCgoTKBoWGigoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo/8AAEQgAOgDqAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A8k8g00wH0rofsftTTZ+1AHOtAaieA10bWftUL2ftQBzbwGq0kBrppLT2qrLae1AHMSwGs+4tyc11ctr7VRntfagDj5oWQk44qGuluLQc8Vk3VngkrxQBQopWUqcGkoAKKKKACiiigAooqRIXbtQBHRVtLMnrUy2Q9KAM6itQWQ9KDZD0oAy6K0Gsh6VE9oR0oAqUVI8Lr2qMjHWgAooooAKKKKAPov7OPSmm3HpWhgU1gKAM1rcelQPbj0rUcCoJAKAMmSAelVJYB6VsSgVTlAoAxpoR6VRnhHpWzMBWfOBzQBi3EI5rLuYRg8Vu3GOayrnHNAHO3sA5IrPPBravMYNYz/eNACUUUUAFKqljgUlTWwGaALFvbjvV+KAelNgA4q7EBQAiQj0qVYR6VKgqZRQBX8kelBhHpVoCgigCk0I9KheEelaDAVC4FAGZLAKpT249K2JAKpzAUAY0iFTTKuXAHNU6ACiiigD6S84U1phWP9s96abz3oA1XmFQSTCs1rv3qB7v3oA0JZh61TllFUpbv3qpLd+9AFqaUVnzyiq812Oeaz7i7HPNAE1xKOeayrqYc81Dd3ygHJrGubxpCQvT1oAde3GThapUHmigAooooAKdG21s02igDUt5gQOavxSCueRyh4q3Dd44NAHQJJUyyVix3QPerC3I9aANTzKDIKzhcj1pDcj1oAvtJULyVSa5HrVeS7A70AXJZBVGeYDPNVpbvPSqruzHk0APmk3HioqKKACiiigD1X7WfWmm7PrVA000AXGuz61C92fWqrVA9AFiS8PrVSa896hlqlP3oAfcX+M81lXF+zkhfzqO8NVKAFd2c5Y5pKKKACiiigAooooAKKKKACiiigBQxHQ08TOO9R0UATfaHpDcOaiooAeZXPemEk9aKKACiiigAooooAKKKKAP/9k=',
            $blurHash->createDataUriThumbnail(__DIR__.'/../Fixtures/logo.png', 234, 58)
        );
    }
}
