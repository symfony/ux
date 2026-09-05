<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Tests\Fixtures\TestKernel;
use Symfony\UX\Image\Twig\ImageExtension;
use Symfony\UX\Image\Twig\ImageRuntime;
use Twig\Environment;
use Twig\Error\RuntimeError;

final class ImageRuntimeTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testTheExtensionAndRuntimeAreRegistered()
    {
        self::bootKernel();

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        self::assertTrue($twig->hasExtension(ImageExtension::class));
        self::assertInstanceOf(ImageRuntime::class, $twig->getRuntime(ImageRuntime::class));
    }

    public function testItDoesNotRegisterAGlobalHtmlAttrTypeFilter()
    {
        // The layout style is built as an InlineStyle in PHP, so we don't need to publish twig/html-extra's filter into every app.
        self::bootKernel();

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        self::assertArrayNotHasKey('html_attr_type', $twig->getFilters());
    }

    public function testItRendersASingleImgForAnAutoFormatProvider()
    {
        $html = $this->renderFunction('/hero.jpg', 'Hero', ['width' => 800, 'height' => 450]);

        self::assertStringStartsWith('<img ', $html);
        self::assertStringContainsString('alt="Hero"', $html);
        self::assertStringContainsString('aspect-ratio: 800 / 450', $html);
    }

    public function testItRendersAPictureForAProviderWithoutAutoFormat()
    {
        $html = $this->renderFunction('/hero.jpg', '', ['width' => 800], autoFormat: false);

        self::assertStringStartsWith('<picture>', $html);
        self::assertStringContainsString('<source type="image/avif"', $html);
    }

    public function testAnExplicitFormatOptionIsAccepted()
    {
        $html = $this->renderFunction('/hero.jpg', '', ['width' => 800, 'format' => 'webp'], autoFormat: false);

        self::assertStringStartsWith('<img ', $html);
        self::assertStringContainsString('fm=webp', $html);
    }

    public function testAnUnknownOptionFailsClearlyInsteadOfARawPhpError()
    {
        try {
            $this->renderFunction('/hero.jpg', '', ['width' => 800, 'class' => 'rounded']);
            self::fail('Expected a RuntimeError to be thrown.');
        } catch (RuntimeError $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
            self::assertStringStartsWith('Unknown image option "class": expected one of "layout"', $e->getPrevious()->getMessage());
        }
    }

    public function testAnInvalidLayoutFailsClearly()
    {
        try {
            $this->renderFunction('/hero.jpg', '', ['layout' => 'not-a-layout']);
            self::fail('Expected a RuntimeError to be thrown.');
        } catch (RuntimeError $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
            self::assertSame('Invalid "layout" value "not-a-layout": expected one of "fixed", "constrained", "full-width".', $e->getPrevious()->getMessage());
        }
    }

    private function renderFunction(string $src, string $alt, array $options = [], bool $autoFormat = true): string
    {
        self::bootKernel(['environment' => $autoFormat ? 'test' : 'no_auto_format']);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        return trim($twig->createTemplate('{{ ux_image(src, alt, options) }}')->render([
            'src' => $src,
            'alt' => $alt,
            'options' => $options,
        ]));
    }
}
