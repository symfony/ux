<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;
use Symfony\UX\Breadcrumb\Tests\Fixtures\CountingTranslator;
use Symfony\UX\Breadcrumb\Tests\Fixtures\RouteName;
use Symfony\UX\Breadcrumb\Tests\Fixtures\TestKernel;
use Symfony\UX\Breadcrumb\Twig\BreadcrumbExtension;
use Twig\Attribute\AsTwigFunction;

#[CoversClass(BreadcrumbExtension::class)]
final class BreadcrumbExtensionTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testItDeclaresBothTwigFunctions(): void
    {
        $declared = [];
        foreach (new \ReflectionClass(BreadcrumbExtension::class)->getMethods() as $method) {
            foreach ($method->getAttributes(AsTwigFunction::class) as $attribute) {
                $function = $attribute->newInstance();
                $declared[$function->name] = [$method->getName(), $function->isSafe];
            }
        }

        self::assertSame([
            'ux_breadcrumb_items' => ['getBreadcrumb', null],
            'ux_breadcrumb' => ['renderBreadcrumb', ['html']],
        ], $declared);
    }

    public function testOneCrumbAndFourCallsCostTwoTranslations(): void
    {
        $extension = $this->extensionFor($this->trailWithOneCrumb());

        $extension->getBreadcrumb();
        $extension->getBreadcrumb();
        $extension->getBreadcrumb(true);
        $extension->getBreadcrumb(true);

        self::assertSame(2, $this->translator()->calls, 'Once per reference type, however often a template asks.');
    }

    public function testARepeatedCallReturnsEqualItems(): void
    {
        $extension = $this->extensionFor($this->trailWithOneCrumb());

        self::assertEquals($extension->getBreadcrumb(), $extension->getBreadcrumb());
    }

    public function testRelativeAndAbsoluteAreMemoizedSeparately(): void
    {
        $extension = $this->extensionFor($this->trailWithOneCrumb());

        $relative = $extension->getBreadcrumb();
        $absolute = $extension->getBreadcrumb(true);

        self::assertNull($relative[0]->url, 'The only crumb is the current page.');
        self::assertStringStartsWith('http', (string) $absolute[0]->url);
    }

    public function testTheMemoIsPerLocaleAsWellAsPerReferenceType(): void
    {
        $extension = $this->extensionFor($this->trailWithOneCrumb());
        $translator = $this->translator();

        $english = $extension->getBreadcrumb()[0]->label;

        $translator->setLocale('fr');
        $french = $extension->getBreadcrumb()[0]->label;

        self::assertSame('product.index.breadcrumb@en', $english);
        self::assertSame('product.index.breadcrumb@fr', $french, 'A locale switch must not be served the memoized items.');
        self::assertSame(2, $translator->calls);
    }

    public function testAnEmptyTrailNeverReachesTheResolver(): void
    {
        $extension = $this->extensionFor(new BreadcrumbTrail('product_index'));

        self::assertSame([], $extension->getBreadcrumb());
        self::assertSame([], $extension->getBreadcrumb(true));
        self::assertSame(0, $this->translator()->calls);
    }

    public function testNoTrailOnTheRequestReturnsNothing(): void
    {
        $extension = $this->extensionFor(null);

        self::assertSame([], $extension->getBreadcrumb());
        self::assertSame(0, $this->translator()->calls);
    }

    public function testNoRequestAtAllReturnsNothing(): void
    {
        self::bootKernel(['environment' => 'counting_translator']);

        self::assertSame([], $this->extension()->getBreadcrumb());
    }

    public function testATrailMutatedAfterAReadIsResolvedAgain(): void
    {
        $trail = $this->trailWithOneCrumb();
        $extension = $this->extensionFor($trail);

        $before = $extension->getBreadcrumb();
        $trail->append(new Breadcrumb(label: 'Appended later', translationDomain: false));
        $after = $extension->getBreadcrumb();

        self::assertCount(1, $before);
        self::assertCount(2, $after, 'A crumb appended after a read must not be swallowed by the memo.');
        self::assertSame('Appended later', $after[1]->label);
    }

    private function trailWithOneCrumb(): BreadcrumbTrail
    {
        $trail = new BreadcrumbTrail('product_index');
        $trail->append(new Breadcrumb(
            label: 'product.index.breadcrumb',
            route: RouteName::ProductIndex->value,
        ));

        return $trail;
    }

    private function extensionFor(?BreadcrumbTrail $trail): BreadcrumbExtension
    {
        self::bootKernel(['environment' => 'counting_translator']);

        $request = Request::create('/products');
        if (null !== $trail) {
            $request->attributes->set(BreadcrumbTrail::ATTRIBUTE, $trail);
        }

        $requestStack = self::getContainer()->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $requestStack->push($request);

        return $this->extension();
    }

    private function extension(): BreadcrumbExtension
    {
        $extension = self::getContainer()->get('ux_breadcrumb.twig.extension');
        self::assertInstanceOf(BreadcrumbExtension::class, $extension);

        return $extension;
    }

    private function translator(): CountingTranslator
    {
        $translator = self::getContainer()->get('translator');
        self::assertInstanceOf(CountingTranslator::class, $translator);

        return $translator;
    }
}
