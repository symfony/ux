<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

final class CompileCommandTest extends WebTestCase
{
    public static function testTheFilesAreCompiledWithTheCommand(): void
    {
        // Given
        static::cleanup();
        $application = new Application(self::bootKernel());
        $command = $application->find('ux:native:dump');
        $commandTester = new CommandTester($command);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists(self::$kernel->getCacheDir().'/output/config/ios_v1.json');
        static::cleanup();
    }

    public static function testTheFilesAreCompiledFromAttribute(): void
    {
        // Given
        static::cleanup();
        $application = new Application(self::bootKernel());
        $command = $application->find('ux:native:dump');
        $commandTester = new CommandTester($command);

        // When
        $commandTester->execute([]);

        // Then
        $commandTester->assertCommandIsSuccessful();
        static::assertFileExists(self::$kernel->getCacheDir().'/output/config/android_v1.json');

        $content = file_get_contents(self::$kernel->getCacheDir().'/output/config/android_v1.json');
        static::assertStringContainsString('"use_local_db": false', $content);
        static::assertStringContainsString('/articles/.*', $content);
        static::cleanup();
    }

    public static function testTheFilesAreServedInDev(): void
    {
        // Given
        $client = self::createClient();
        static::cleanup();
        $application = new Application($client->getKernel());
        $command = $application->find('ux:native:dump');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // When
        $client->request(Request::METHOD_GET, '/config/ios_v1.json');

        // Then
        $response = $client->getResponse();
        static::assertResponseIsSuccessful();
        static::assertSame('application/json', $response->headers->get('Content-Type'));
        static::assertStringContainsString('"use_local_db":true', $response->getContent());
    }

    public static function testTheAttributeConfigurationsAreServedInDev(): void
    {
        // Given
        $client = self::createClient();
        static::cleanup();

        // When
        $client->request(Request::METHOD_GET, '/config/android_v1.json');

        // Then
        $response = $client->getResponse();
        static::assertResponseIsSuccessful();
        static::assertSame('application/json', $response->headers->get('Content-Type'));
        static::assertStringContainsString('"use_local_db":false', $response->getContent());
        static::assertStringContainsString('pull_to_refresh_enabled', $response->getContent());
    }

    public static function testTheCommandUsesUxNamespaceAndHasDescription(): void
    {
        $application = new Application(self::bootKernel());
        $command = $application->find('ux:native:dump');

        static::assertSame('ux:native:dump', $command->getName());
        static::assertSame('Dump UX Native configuration files', $command->getDescription());
    }

    private static function cleanup(): void
    {
        /** @var Filesystem $filesystem */
        $filesystem = self::getContainer()->get(Filesystem::class);
        $filesystem->remove(\sprintf('%s/output', self::$kernel->getCacheDir()));
    }
}
