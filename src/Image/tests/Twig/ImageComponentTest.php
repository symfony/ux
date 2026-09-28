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
use Twig\Environment;
use Twig\Error\RuntimeError;

final class ImageComponentTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testItRendersASingleImgForAnAutoFormatProvider(): void
    {
        $html = $this->renderComponent(['src' => '/hero.jpg', 'alt' => 'Hero', 'width' => 800, 'height' => 450]);

        self::assertStringStartsWith('<img ', $html);
        self::assertStringContainsString('alt="Hero"', $html);
        self::assertStringContainsString('aspect-ratio: 800 / 450', $html);
    }

    public function testItRendersAnImgEvenWhenTheProviderDoesNotNegotiateTheFormat(): void
    {
        $html = $this->renderComponent(['src' => '/hero.jpg', 'alt' => '', 'width' => 800], autoFormat: false);

        self::assertStringStartsWith('<img ', $html);
        self::assertStringNotContainsString('<picture>', $html);
    }

    public function testThePictureComponentRendersAPictureEvenWhenTheProviderNegotiatesTheFormat(): void
    {
        $html = $this->renderComponent(['src' => '/hero.jpg', 'alt' => '', 'width' => 800], component: 'ux:picture');

        self::assertStringStartsWith('<picture>', $html);
        self::assertStringContainsString('<source type="image/avif"', $html);
        self::assertStringContainsString('<source type="image/webp"', $html);
        self::assertStringContainsString('<source type="image/jpeg"', $html);
        self::assertStringEndsWith('</picture>', $html);
    }

    public function testAnExplicitFormatPropRendersASingleImgInsteadOfAPicture(): void
    {
        $html = $this->renderComponent(['src' => '/hero.jpg', 'alt' => '', 'width' => 800, 'format' => 'webp'], autoFormat: false);

        self::assertStringStartsWith('<img ', $html);
        self::assertStringNotContainsString('<source', $html);
        self::assertStringNotContainsString('format="webp"', $html);
        self::assertStringContainsString('fm=webp', $html);
    }

    public function testCallerAttributesAreRendered(): void
    {
        $html = $this->renderComponent(['src' => '/hero.jpg', 'alt' => '', 'width' => 800, 'class' => 'rounded', 'data-test' => '1']);

        self::assertStringContainsString('class="rounded"', $html);
        self::assertStringContainsString('data-test="1"', $html);
    }

    public function testACallerStyleMergesIntoTheLayoutStyle(): void
    {
        $html = $this->renderComponent(['src' => '/hero.jpg', 'alt' => '', 'width' => 800, 'height' => 450, 'style' => ['border-radius' => '8px']]);

        self::assertStringContainsString('aspect-ratio: 800 / 450', $html);
        self::assertStringContainsString('border-radius: 8px', $html);
    }

    public function testACallerStringStyleMergesIntoTheLayoutStyle(): void
    {
        $html = $this->renderComponent(['src' => '/hero.jpg', 'alt' => '', 'width' => 800, 'height' => 450, 'style' => 'border-radius: 8px']);

        self::assertStringContainsString('aspect-ratio: 800 / 450', $html);
        self::assertStringContainsString('border-radius: 8px', $html);
    }

    public function testACallerSizesOverridesEverySourceAndTheImgInThePictureBranch(): void
    {
        $html = $this->renderComponent(['src' => '/hero.jpg', 'alt' => '', 'width' => 800, 'sizes' => '50vw'], component: 'ux:picture');

        self::assertSame(4, substr_count($html, 'sizes="50vw"'));
        self::assertStringNotContainsString('100vw', $html);
    }

    public function testAnInvalidLayoutFailsClearly(): void
    {
        try {
            $this->renderComponent(['src' => '/hero.jpg', 'alt' => '', 'width' => 800, 'layout' => 'not-a-layout']);
            self::fail('Expected a RuntimeError to be thrown.');
        } catch (RuntimeError $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
            self::assertSame('Invalid "layout" value "not-a-layout": expected one of "fixed", "constrained", "full-width".', $e->getPrevious()->getMessage());
        }
    }

    private function renderComponent(array $props, bool $autoFormat = true, string $component = 'ux:image'): string
    {
        self::bootKernel(['environment' => $autoFormat ? 'test' : 'no_auto_format']);

        /** @var Environment $twig */
        $twig = self::getContainer()->get('twig');

        return trim($twig->createTemplate('{{ component(name, props) }}')->render(['name' => $component, 'props' => $props]));
    }
}
