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
    private const ASSET_MAPPER = 'Symfony\\Component\\AssetMapper\\';

    public function testBundleBootsWithoutConsole(): void
    {
        $result = $this->bootInIsolation([self::CONSOLE], [
            'has' => ['ux_upload.command.cleanup'],
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        self::assertFalse($result['has']['ux_upload.command.cleanup'] ?? true);
    }

    public function testBundleBootsWithNoOptionalPackages(): void
    {
        $result = $this->bootInIsolation([
            self::FLYSYSTEM,
            self::CONSOLE,
            self::SECURITY_CSRF,
            self::MESSENGER,
            self::VALIDATOR,
            self::TRANSLATION,
            self::ASSET_MAPPER,
        ], [
            'action' => 'create_form',
            'has' => ['ux_upload.command.cleanup'],
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        self::assertFalse($result['has']['ux_upload.command.cleanup'] ?? true);
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
