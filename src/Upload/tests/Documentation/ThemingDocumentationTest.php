<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Documentation;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class ThemingDocumentationTest extends TestCase
{
    public function testEveryDocumentedCssCustomPropertyIsConsumedByAPublishedStylesheet(): void
    {
        $packageDir = \dirname(__DIR__, 2);
        $documentation = file_get_contents($packageDir.'/doc/customizing-upload-field.rst');
        $stylesheets = implode("\n", array_map(
            static fn (string $path): string => (string) file_get_contents($path),
            glob($packageDir.'/assets/src/styles/*.css') ?: [],
        ));

        self::assertIsString($documentation);
        preg_match_all('/--ux-upload-[a-z0-9-]+/', $documentation, $matches);
        $documentedProperties = array_values(array_unique($matches[0]));

        self::assertNotEmpty($documentedProperties);
        foreach ($documentedProperties as $property) {
            self::assertStringContainsString($property, $stylesheets, $property);
        }
    }

    public function testFormThemeContainsThePublicBlockContract(): void
    {
        $packageDir = \dirname(__DIR__, 2);
        $formTheme = file_get_contents($packageDir.'/templates/form_theme.html.twig');
        self::assertIsString($formTheme);
        self::assertStringNotContainsString('<style', $formTheme);
        self::assertStringNotContainsString('|raw', $formTheme);
        self::assertStringContainsString("{{ block('attributes') }}", $formTheme);
        self::assertFileDoesNotExist($packageDir.'/templates/bootstrap_5.html.twig');
        self::assertFileDoesNotExist($packageDir.'/templates/tailwind_5.html.twig');
        self::assertDirectoryDoesNotExist($packageDir.'/templates/upload');
    }

    public function testEveryPublicRenderingBlockIsDefinedAndComposedByTwig(): void
    {
        $packageDir = \dirname(__DIR__, 2);
        $source = file_get_contents($packageDir.'/templates/form_theme.html.twig');
        self::assertIsString($source);

        $blocks = [
            'ux_upload_row',
            'ux_upload_widget',
            'ux_upload_picker',
            'ux_upload_item',
            'ux_upload_visual',
            'ux_upload_progress',
            'ux_upload_actions',
            'ux_upload_summary',
            'ux_upload_client_errors',
            'ux_upload_start',
        ];

        foreach ($blocks as $block) {
            self::assertStringContainsString('{% block '.$block, $source);
        }

        foreach (\array_slice($blocks, 2) as $block) {
            self::assertStringContainsString("block('{$block}')", $source);
        }

        $controller = file_get_contents($packageDir.'/assets/src/upload_controller.ts');
        self::assertIsString($controller);
        self::assertStringNotContainsString('document.createElement', $controller);
        self::assertStringNotContainsString('.innerHTML', $controller);
    }

    public function testPublishedStylesAreOptionalAssetMapperChoices(): void
    {
        $packageDir = \dirname(__DIR__, 2);
        $package = json_decode((string) file_get_contents($packageDir.'/assets/package.json'), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('./dist/compact.min.css', $package['exports']['./compact.css']);
        self::assertSame('./dist/dropzone.min.css', $package['exports']['./dropzone.css']);
        self::assertFalse($package['symfony']['controllers']['upload']['autoimport']['@symfony/ux-upload/dist/compact.min.css']);
        self::assertFalse($package['symfony']['controllers']['upload']['autoimport']['@symfony/ux-upload/dist/dropzone.min.css']);
        self::assertFileExists($packageDir.'/assets/dist/compact.min.css');
        self::assertFileExists($packageDir.'/assets/dist/dropzone.min.css');
    }

    public function testContinuousIntegrationRebuildsAndVerifiesPublishedAssets(): void
    {
        $rootDir = \dirname(__DIR__, 4);
        $workflow = file_get_contents($rootDir.'/.github/workflows/browser-tests.yml');

        self::assertIsString($workflow);
        self::assertStringContainsString('(cd src/Upload/assets && node ../../../bin/build_package.ts .)', $workflow);
        self::assertStringContainsString('git diff --exit-code -- src/Upload/assets/dist', $workflow);
    }
}
