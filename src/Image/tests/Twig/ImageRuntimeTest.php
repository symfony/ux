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

    public function testUxImageRendersAnImgEvenWhenTheProviderDoesNotNegotiateTheFormat()
    {
        $html = $this->renderFunction('/hero.jpg', '', ['width' => 800], autoFormat: false);

        self::assertStringStartsWith('<img ', $html);
        self::assertStringNotContainsString('<picture>', $html);
    }

    public function testUxPictureRendersAPictureEvenWhenTheProviderNegotiatesTheFormat()
    {
        $html = $this->renderPictureFunction('/hero.jpg', 'Hero', ['width' => 800], ['class' => 'rounded']);

        self::assertStringStartsWith('<picture><source type="image/avif"', $html);
        self::assertStringContainsString('<source type="image/webp"', $html);
        self::assertStringContainsString('alt="Hero"', $html);
        self::assertStringContainsString('class="rounded"', $html);
        self::assertStringEndsWith('</picture>', $html);
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

    public function testCallerAttributesAreRendered()
    {
        $html = $this->renderFunction('/hero.jpg', '', ['width' => 800], attributes: ['class' => 'rounded', 'data-test' => '1']);

        self::assertStringContainsString('class="rounded"', $html);
        self::assertStringContainsString('data-test="1"', $html);
    }

    public function testACallerAttributeWinsOverTheGeneratedDefault()
    {
        $html = $this->renderFunction('/hero.jpg', '', ['width' => 800], attributes: ['loading' => 'eager']);

        self::assertStringContainsString('loading="eager"', $html);
        self::assertStringNotContainsString('loading="lazy"', $html);
    }

    public function testACallerStyleMergesIntoTheLayoutStyle()
    {
        $html = $this->renderFunction('/hero.jpg', '', ['width' => 800, 'height' => 450], attributes: ['style' => ['border-radius' => '8px']]);

        self::assertStringContainsString('aspect-ratio: 800 / 450', $html);
        self::assertStringContainsString('border-radius: 8px', $html);
    }

    public function testACallerStringStyleMergesIntoTheLayoutStyle()
    {
        $html = $this->renderFunction('/hero.jpg', '', ['width' => 800, 'height' => 450], attributes: ['style' => 'border-radius: 8px']);

        self::assertStringContainsString('aspect-ratio: 800 / 450', $html);
        self::assertStringContainsString('border-radius: 8px', $html);
    }

    public function testACallerSizesOverridesEverySourceAndTheImgInThePictureBranch()
    {
        $html = $this->renderPictureFunction('/hero.jpg', '', ['width' => 800], ['sizes' => '50vw']);

        self::assertSame(4, substr_count($html, 'sizes="50vw"'));
        self::assertStringNotContainsString('100vw', $html);
    }

    public function testUxImageUrlRendersOneEscapedUrl()
    {
        $url = $this->renderUrlFunction('og.jpg', ['width' => 1200, 'height' => 630]);

        self::assertSame('/og.jpg?w=1200&amp;fm=&amp;h=630&amp;fit=cover', $url);
    }

    public function testUxImageUrlTakesAFitByName()
    {
        $url = $this->renderUrlFunction('og.jpg', ['width' => 400, 'height' => 400, 'fit' => 'contain']);

        self::assertStringContainsString('fit=contain', $url);
    }

    public function testUxImageUrlRejectsAnOptionItDoesNotKnow()
    {
        try {
            $this->renderUrlFunction('og.jpg', ['layout' => 'fixed']);
            self::fail('Expected a RuntimeError to be thrown.');
        } catch (RuntimeError $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
            self::assertSame('Unknown image URL option "layout": expected one of "width", "height", "fit", "format", "quality", "operations".', $e->getPrevious()->getMessage());
        }
    }

    public function testAnOperationsKeyNamingNoInstalledProviderIsRejected()
    {
        try {
            $this->renderUrlFunction('og.jpg', ['operations' => ['cloudfare' => ['gravity' => 'auto']]]);
            self::fail('Expected a RuntimeError to be thrown.');
        } catch (RuntimeError $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
            self::assertSame('The "operations" option has a "cloudfare" key, which is not an installed image provider (installed: "fake", "null").', $e->getPrevious()->getMessage());
        }
    }

    public function testTheRendererAlsoRejectsAnOperationsKeyNamingNoInstalledProvider()
    {
        try {
            $this->renderFunction('/hero.jpg', 'Hero', ['width' => 400, 'operations' => ['cloudfare' => ['gravity' => 'auto']]]);
            self::fail('Expected a RuntimeError to be thrown.');
        } catch (RuntimeError $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
            self::assertStringStartsWith('The "operations" option has a "cloudfare" key', $e->getPrevious()->getMessage());
        }
    }

    private function renderPictureFunction(string $src, string $alt, array $options = [], array $attributes = []): string
    {
        self::bootKernel();

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        return trim($twig->createTemplate('{{ ux_picture(src, alt, options, attributes) }}')->render([
            'src' => $src,
            'alt' => $alt,
            'options' => $options,
            'attributes' => $attributes,
        ]));
    }

    private function renderUrlFunction(string $src, array $options = []): string
    {
        self::bootKernel();

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        return $twig->createTemplate('{{ ux_image_url(src, options) }}')->render(['src' => $src, 'options' => $options]);
    }

    private function renderFunction(string $src, string $alt, array $options = [], bool $autoFormat = true, array $attributes = []): string
    {
        self::bootKernel(['environment' => $autoFormat ? 'test' : 'no_auto_format']);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        return trim($twig->createTemplate('{{ ux_image(src, alt, options, attributes) }}')->render([
            'src' => $src,
            'alt' => $alt,
            'options' => $options,
            'attributes' => $attributes,
        ]));
    }
}
