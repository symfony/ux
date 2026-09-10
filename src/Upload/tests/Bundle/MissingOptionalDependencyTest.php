<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Bundle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Tests\Isolation\IsolatedBootTrait;
use Symfony\UX\Upload\UXUploadBundle;

/**
 * Verifies UXUploadBundle installs and boots when its optional packages are
 * absent (league/flysystem, symfony/console, symfony/security-csrf,
 * symfony/messenger, symfony/translation). Each case boots the bundle in a child process with the
 * relevant package hidden from the autoloader -- see {@see IsolatedBootTrait}
 * for why in-process isolation is not possible here.
 */
#[CoversClass(UXUploadBundle::class)]
#[Group('isolation')]
final class MissingOptionalDependencyTest extends TestCase
{
    use IsolatedBootTrait;

    private const FLYSYSTEM = 'League\\Flysystem\\';
    private const CONSOLE = 'Symfony\\Component\\Console\\';
    private const SECURITY_CSRF = 'Symfony\\Component\\Security\\Csrf\\';
    private const MESSENGER = 'Symfony\\Component\\Messenger\\';
    private const VALIDATOR = 'Symfony\\Component\\Validator\\';
    private const TRANSLATION = 'Symfony\\Component\\Translation\\';

    public function testLoadExtensionFlysystemThrowsWhenLibraryMissing()
    {
        $result = $this->bootInIsolation([self::FLYSYSTEM], [
            'ux_upload' => ['storage' => 'flysystem'],
        ]);

        self::assertSame('error', $result['status']);
        self::assertSame(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class, $result['class'] ?? null);
        self::assertStringContainsString('league/flysystem is not installed', $result['message'] ?? '');
    }

    public function testBundleBootsWithoutConsole()
    {
        $result = $this->bootInIsolation([self::CONSOLE], [
            'has' => ['ux_upload.command.cleanup'],
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        // The cleanup command is gated on symfony/console, so it must not be
        // registered when the package is absent -- the running counterpart to
        // UXUploadBundleExtensionTest::testCleanupCommandIsRegisteredWhenConsoleInstalled.
        self::assertFalse($result['has']['ux_upload.command.cleanup'] ?? true);
    }

    public function testBundleBootsWithoutSecurityCsrf()
    {
        $result = $this->bootInIsolation([self::SECURITY_CSRF], [
            'action' => 'create_form',
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        // Without symfony/security-csrf the form is built with a null token
        // manager, so no CSRF token is emitted -- the counterpart to
        // UXUploadBundleExtensionTest::testCsrfTokenIsGeneratedAndValidatedThroughContainer.
        self::assertNull($result['csrf_token'] ?? null);
    }

    public function testBundleBootsWithoutValidator()
    {
        // Validator is optional: applications may use standard constraints on the
        // form field, but the core upload pipeline does not reference the component.
        $result = $this->bootInIsolation([self::VALIDATOR], [
            'action' => 'run_upload',
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        self::assertSame('note.txt', $result['upload']['filename'] ?? null);
    }

    public function testBundleBootsWithoutTranslation()
    {
        $result = $this->bootInIsolation([self::TRANSLATION], [
            'action' => 'create_form',
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
    }

    public function testBundleBootsWithNoOptionalPackages()
    {
        $result = $this->bootInIsolation([
            self::FLYSYSTEM,
            self::CONSOLE,
            self::SECURITY_CSRF,
            self::MESSENGER,
            self::TRANSLATION,
        ], [
            'action' => 'create_form',
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        self::assertNull($result['csrf_token'] ?? null);
    }

    /**
     * @param array{status: string, class?: string, message?: string, csrf_token?: string|null} $result
     */
    private function explain(array $result): string
    {
        if ('ok' === $result['status']) {
            return '';
        }

        return \sprintf('isolated boot failed: %s: %s', $result['class'] ?? '?', $result['message'] ?? '');
    }
}
