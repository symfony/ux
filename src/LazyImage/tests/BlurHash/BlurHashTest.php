<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LazyImage\Tests\BlurHash;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\UX\LazyImage\BlurHash\BlurHash;
use Symfony\UX\LazyImage\BlurHash\BlurHashInterface;
use Symfony\UX\LazyImage\Tests\Fixtures\BlurHash\LoggedFetchImageContent;
use Symfony\UX\LazyImage\Tests\Kernel\TwigAppKernel;

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
            BlurHash::intervention3() ? 'LnMtaO9FD%IU%MRjayRj~qIUM{of' : 'L54ec*~q_3?bofoffQWB9F9FD%IU',
            $blurHash->encode(__DIR__.'/../Fixtures/logo.png')
        );
    }

    public function testWithCustomGetImageContent(): void
    {
        $kernel = new class('test', true) extends TwigAppKernel {
            public function registerContainerConfiguration(LoaderInterface $loader)
            {
                parent::registerContainerConfiguration($loader);

                $loader->load(static function (ContainerBuilder $container) {
                    $container->loadFromExtension('lazy_image', [
                        'fetch_image_content' => 'logged_get_image_content',
                    ]);

                    $container
                        ->setDefinition('logged_get_image_content', new Definition(LoggedFetchImageContent::class))
                        ->setPublic('true')
                    ;
                });
            }
        };

        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var BlurHashInterface $blurHash */
        $blurHash = $container->get('test.lazy_image.blur_hash');

        $loggedGetImageContent = $container->get('logged_get_image_content');
        $this->assertInstanceOf(LoggedFetchImageContent::class, $loggedGetImageContent);
        $this->assertEmpty($loggedGetImageContent->logs);

        $this->assertSame(
            BlurHash::intervention3() ? 'LnMtaO9FD%IU%MRjayRj~qIUM{of' : 'L54ec*~q_3?bofoffQWB9F9FD%IU',
            $blurHash->encode(__DIR__.'/../Fixtures/logo.png')
        );

        $this->assertCount(1, $loggedGetImageContent->logs);
        $this->assertSame(__DIR__.'/../Fixtures/logo.png', $loggedGetImageContent->logs[0]);
    }

    public function testEnsureCacheIsNotUsedWhenNotConfigured()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var BlurHashInterface $blurHash */
        $blurHash = $container->get('test.lazy_image.blur_hash');

        $this->assertInstanceOf(BlurHash::class, $blurHash);
        $this->assertNull($this->extractCache($blurHash));
    }

    public function testEnsureCacheIsUsedWhenConfigured()
    {
        $kernel = new class('test', true) extends TwigAppKernel {
            public function registerContainerConfiguration(LoaderInterface $loader)
            {
                parent::registerContainerConfiguration($loader);

                $loader->load(static function (ContainerBuilder $container) {
                    $container->loadFromExtension('framework', [
                        'cache' => [
                            'pools' => [
                                'cache.lazy_image' => [
                                    'adapter' => 'cache.adapter.array',
                                ],
                            ],
                        ],
                    ]);

                    $container->loadFromExtension('lazy_image', [
                        'cache' => 'cache.lazy_image',
                    ]);

                    $container->setAlias('test.cache.lazy_image', 'cache.lazy_image')->setPublic(true);
                });
            }
        };

        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var BlurHashInterface $blurHash */
        $blurHash = $container->get('test.lazy_image.blur_hash');

        $this->assertInstanceOf(BlurHash::class, $blurHash);
        $this->assertInstanceOf(CacheInterface::class, $this->extractCache($blurHash));
    }

    public function testCreateDataUriThumbnail()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var BlurHashInterface $blurHash */
        $blurHash = $container->get('test.lazy_image.blur_hash');

        $this->assertInstanceOf(BlurHash::class, $blurHash);
        $this->assertNull($this->extractCache($blurHash));

        $this->assertSame(
            BlurHash::intervention3() ? 'data:image/jpeg;base64,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' : 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODAK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg8MEBcUGBgXFBYWGh0lHxobIxwWFiAsICMmJykqKRkfLTAtKDAlKCko/9sAQwEHBwcKCAoTCgoTKBoWGigoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo/8AAEQgAOgDqAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A8k8g00wH0rofsftTTZ+1AHOtAaieA10bWftUL2ftQBzbwGq0kBrppLT2qrLae1AHMSwGs+4tyc11ctr7VRntfagDj5oWQk44qGuluLQc8Vk3VngkrxQBQopWUqcGkoAKKKKACiiigAooqRIXbtQBHRVtLMnrUy2Q9KAM6itQWQ9KDZD0oAy6K0Gsh6VE9oR0oAqUVI8Lr2qMjHWgAooooAKKKKAPov7OPSmm3HpWhgU1gKAM1rcelQPbj0rUcCoJAKAMmSAelVJYB6VsSgVTlAoAxpoR6VRnhHpWzMBWfOBzQBi3EI5rLuYRg8Vu3GOayrnHNAHO3sA5IrPPBravMYNYz/eNACUUUUAFKqljgUlTWwGaALFvbjvV+KAelNgA4q7EBQAiQj0qVYR6VKgqZRQBX8kelBhHpVoCgigCk0I9KheEelaDAVC4FAGZLAKpT249K2JAKpzAUAY0iFTTKuXAHNU6ACiiigD6S84U1phWP9s96abz3oA1XmFQSTCs1rv3qB7v3oA0JZh61TllFUpbv3qpLd+9AFqaUVnzyiq812Oeaz7i7HPNAE1xKOeayrqYc81Dd3ygHJrGubxpCQvT1oAde3GThapUHmigAooooAKdG21s02igDUt5gQOavxSCueRyh4q3Dd44NAHQJJUyyVix3QPerC3I9aANTzKDIKzhcj1pDcj1oAvtJULyVSa5HrVeS7A70AXJZBVGeYDPNVpbvPSqruzHk0APmk3HioqKKACiiigD1X7WfWmm7PrVA000AXGuz61C92fWqrVA9AFiS8PrVSa896hlqlP3oAfcX+M81lXF+zkhfzqO8NVKAFd2c5Y5pKKKACiiigAooooAKKKKACiiigBQxHQ08TOO9R0UATfaHpDcOaiooAeZXPemEk9aKKACiiigAooooAKKKKAP/9k=',
            $blurHash->createDataUriThumbnail(__DIR__.'/../Fixtures/logo.png', 234, 58)
        );
    }

    public function testCreateDataUriThumbnailWithCache()
    {
        $kernel = new class('test', true) extends TwigAppKernel {
            public function registerContainerConfiguration(LoaderInterface $loader)
            {
                parent::registerContainerConfiguration($loader);

                $loader->load(static function (ContainerBuilder $container) {
                    $container->loadFromExtension('framework', [
                        'cache' => [
                            'pools' => [
                                'cache.lazy_image' => [
                                    'adapter' => 'cache.adapter.array',
                                ],
                            ],
                        ],
                    ]);

                    $container->loadFromExtension('lazy_image', [
                        'cache' => 'cache.lazy_image',
                    ]);

                    $container->setAlias('test.cache.lazy_image', 'cache.lazy_image')->setPublic(true);
                });
            }
        };

        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var BlurHashInterface $blurHash */
        $blurHash = $container->get('test.lazy_image.blur_hash');

        $this->assertInstanceOf(BlurHash::class, $blurHash);
        $this->assertInstanceOf(ArrayAdapter::class, $cache = $this->extractCache($blurHash));

        $this->assertEmpty($cache->getValues());

        $this->assertSame(
            BlurHash::intervention3() ? 'data:image/jpeg;base64,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' : 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODAK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg8MEBcUGBgXFBYWGh0lHxobIxwWFiAsICMmJykqKRkfLTAtKDAlKCko/9sAQwEHBwcKCAoTCgoTKBoWGigoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo/8AAEQgAOgDqAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A8k8g00wH0rofsftTTZ+1AHOtAaieA10bWftUL2ftQBzbwGq0kBrppLT2qrLae1AHMSwGs+4tyc11ctr7VRntfagDj5oWQk44qGuluLQc8Vk3VngkrxQBQopWUqcGkoAKKKKACiiigAooqRIXbtQBHRVtLMnrUy2Q9KAM6itQWQ9KDZD0oAy6K0Gsh6VE9oR0oAqUVI8Lr2qMjHWgAooooAKKKKAPov7OPSmm3HpWhgU1gKAM1rcelQPbj0rUcCoJAKAMmSAelVJYB6VsSgVTlAoAxpoR6VRnhHpWzMBWfOBzQBi3EI5rLuYRg8Vu3GOayrnHNAHO3sA5IrPPBravMYNYz/eNACUUUUAFKqljgUlTWwGaALFvbjvV+KAelNgA4q7EBQAiQj0qVYR6VKgqZRQBX8kelBhHpVoCgigCk0I9KheEelaDAVC4FAGZLAKpT249K2JAKpzAUAY0iFTTKuXAHNU6ACiiigD6S84U1phWP9s96abz3oA1XmFQSTCs1rv3qB7v3oA0JZh61TllFUpbv3qpLd+9AFqaUVnzyiq812Oeaz7i7HPNAE1xKOeayrqYc81Dd3ygHJrGubxpCQvT1oAde3GThapUHmigAooooAKdG21s02igDUt5gQOavxSCueRyh4q3Dd44NAHQJJUyyVix3QPerC3I9aANTzKDIKzhcj1pDcj1oAvtJULyVSa5HrVeS7A70AXJZBVGeYDPNVpbvPSqruzHk0APmk3HioqKKACiiigD1X7WfWmm7PrVA000AXGuz61C92fWqrVA9AFiS8PrVSa896hlqlP3oAfcX+M81lXF+zkhfzqO8NVKAFd2c5Y5pKKKACiiigAooooAKKKKACiiigBQxHQ08TOO9R0UATfaHpDcOaiooAeZXPemEk9aKKACiiigAooooAKKKKAP/9k=',
            $blurHash->createDataUriThumbnail(__DIR__.'/../Fixtures/logo.png', 234, 58)
        );

        $this->assertNotEmpty($cache->getValues());

        $this->assertSame(
            BlurHash::intervention3() ? 'data:image/jpeg;base64,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' : 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODAK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg8MEBcUGBgXFBYWGh0lHxobIxwWFiAsICMmJykqKRkfLTAtKDAlKCko/9sAQwEHBwcKCAoTCgoTKBoWGigoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo/8AAEQgAOgDqAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A8k8g00wH0rofsftTTZ+1AHOtAaieA10bWftUL2ftQBzbwGq0kBrppLT2qrLae1AHMSwGs+4tyc11ctr7VRntfagDj5oWQk44qGuluLQc8Vk3VngkrxQBQopWUqcGkoAKKKKACiiigAooqRIXbtQBHRVtLMnrUy2Q9KAM6itQWQ9KDZD0oAy6K0Gsh6VE9oR0oAqUVI8Lr2qMjHWgAooooAKKKKAPov7OPSmm3HpWhgU1gKAM1rcelQPbj0rUcCoJAKAMmSAelVJYB6VsSgVTlAoAxpoR6VRnhHpWzMBWfOBzQBi3EI5rLuYRg8Vu3GOayrnHNAHO3sA5IrPPBravMYNYz/eNACUUUUAFKqljgUlTWwGaALFvbjvV+KAelNgA4q7EBQAiQj0qVYR6VKgqZRQBX8kelBhHpVoCgigCk0I9KheEelaDAVC4FAGZLAKpT249K2JAKpzAUAY0iFTTKuXAHNU6ACiiigD6S84U1phWP9s96abz3oA1XmFQSTCs1rv3qB7v3oA0JZh61TllFUpbv3qpLd+9AFqaUVnzyiq812Oeaz7i7HPNAE1xKOeayrqYc81Dd3ygHJrGubxpCQvT1oAde3GThapUHmigAooooAKdG21s02igDUt5gQOavxSCueRyh4q3Dd44NAHQJJUyyVix3QPerC3I9aANTzKDIKzhcj1pDcj1oAvtJULyVSa5HrVeS7A70AXJZBVGeYDPNVpbvPSqruzHk0APmk3HioqKKACiiigD1X7WfWmm7PrVA000AXGuz61C92fWqrVA9AFiS8PrVSa896hlqlP3oAfcX+M81lXF+zkhfzqO8NVKAFd2c5Y5pKKKACiiigAooooAKKKKACiiigBQxHQ08TOO9R0UATfaHpDcOaiooAeZXPemEk9aKKACiiigAooooAKKKKAP/9k=',
            $blurHash->createDataUriThumbnail(__DIR__.'/../Fixtures/logo.png', 234, 58)
        );

        $this->assertNotEmpty($cache->getValues());
    }

    public function testTwigExtension()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $twig = $kernel->getContainer()->get('test.service_container')->get('twig');
        $output = $twig->createTemplate(<<<TWIG
            {{ blur_hash(file) }}
            {{ data_uri_thumbnail(file, 234, 58) }}
            TWIG)->render(['file' => __DIR__.'/../Fixtures/logo.png']);

        if (BlurHash::intervention3()) {
            $expected = <<<OUTPUT
            LnMtaO9FD%IU%MRjayRj~qIUM{of
            data:image/jpeg;base64,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
            OUTPUT;
        } else {
            $expected = <<<OUTPUT
            L54ec*~q_3?bofoffQWB9F9FD%IU
            data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODAK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg8MEBcUGBgXFBYWGh0lHxobIxwWFiAsICMmJykqKRkfLTAtKDAlKCko/9sAQwEHBwcKCAoTCgoTKBoWGigoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo/8AAEQgAOgDqAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A8k8g00wH0rofsftTTZ+1AHOtAaieA10bWftUL2ftQBzbwGq0kBrppLT2qrLae1AHMSwGs+4tyc11ctr7VRntfagDj5oWQk44qGuluLQc8Vk3VngkrxQBQopWUqcGkoAKKKKACiiigAooqRIXbtQBHRVtLMnrUy2Q9KAM6itQWQ9KDZD0oAy6K0Gsh6VE9oR0oAqUVI8Lr2qMjHWgAooooAKKKKAPov7OPSmm3HpWhgU1gKAM1rcelQPbj0rUcCoJAKAMmSAelVJYB6VsSgVTlAoAxpoR6VRnhHpWzMBWfOBzQBi3EI5rLuYRg8Vu3GOayrnHNAHO3sA5IrPPBravMYNYz/eNACUUUUAFKqljgUlTWwGaALFvbjvV+KAelNgA4q7EBQAiQj0qVYR6VKgqZRQBX8kelBhHpVoCgigCk0I9KheEelaDAVC4FAGZLAKpT249K2JAKpzAUAY0iFTTKuXAHNU6ACiiigD6S84U1phWP9s96abz3oA1XmFQSTCs1rv3qB7v3oA0JZh61TllFUpbv3qpLd+9AFqaUVnzyiq812Oeaz7i7HPNAE1xKOeayrqYc81Dd3ygHJrGubxpCQvT1oAde3GThapUHmigAooooAKdG21s02igDUt5gQOavxSCueRyh4q3Dd44NAHQJJUyyVix3QPerC3I9aANTzKDIKzhcj1pDcj1oAvtJULyVSa5HrVeS7A70AXJZBVGeYDPNVpbvPSqruzHk0APmk3HioqKKACiiigD1X7WfWmm7PrVA000AXGuz61C92fWqrVA9AFiS8PrVSa896hlqlP3oAfcX+M81lXF+zkhfzqO8NVKAFd2c5Y5pKKKACiiigAooooAKKKKACiiigBQxHQ08TOO9R0UATfaHpDcOaiooAeZXPemEk9aKKACiiigAooooAKKKKAP/9k=
            OUTPUT;
        }

        $this->assertSame($expected, $output);

        $kernel->shutdown();
    }

    private function extractCache(BlurHash $blurHash): ?CacheInterface
    {
        return \Closure::bind(fn () => $this->cache, $blurHash, BlurHash::class)();
    }
}
