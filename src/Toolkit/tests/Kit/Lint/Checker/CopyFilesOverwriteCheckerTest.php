<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit\Lint\Checker;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Kit\Lint\Checker\CopyFilesOverwriteChecker;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class CopyFilesOverwriteCheckerTest extends TestCase
{
    use TestHelperTrait;

    public function testReportsTheFilesInstalledOverAComponent(): void
    {
        $factory = new KitFactory(new Filesystem(), new KitSynchronizer(new Filesystem(), new RecipeSynchronizer()));
        $kit = $factory->createKitFromAbsolutePath(self::getFixtureKitPath('lint-copy-files-overwrite'));

        $issues = iterator_to_array(new CopyFilesOverwriteChecker()->check($kit), false);

        // the "AppSidebar.html.twig" of both blocks is not reported
        $this->assertSame([
            'date-range-picker: templates/components/DatePicker.html.twig',
            'sidebar-01: templates/components/DatePicker.html.twig',
        ], array_map(static fn (LintIssue $issue): string => \sprintf('%s: %s', $issue->recipe, $issue->file), $issues));
        foreach ($issues as $issue) {
            $this->assertSame(LintSeverity::Error, $issue->severity);
            $this->assertSame('copy-files.overwrite', $issue->category);
            $this->assertSame('"%COMPONENTS_DIR%/DatePicker.html.twig" is also installed by the "date-picker" recipe: installing both overwrites one of them.', $issue->message);
        }
    }
}
