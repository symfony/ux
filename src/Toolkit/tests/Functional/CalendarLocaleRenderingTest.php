<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\Toolkit\Kit\KitFactory;

/**
 * The Shadcn calendar formats its labels server-side, while its Stimulus controller re-formats them
 * client-side with `Intl.DateTimeFormat`. Both sides must resolve the same locale, otherwise the
 * month caption flips language on the first interaction, so the template has to resolve the locale
 * and expose it through `data-calendar-locale-value`.
 */
class CalendarLocaleRenderingTest extends WebTestCase
{
    private string $initialDefaultLocale;

    protected function setUp(): void
    {
        $this->initialDefaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->initialDefaultLocale);

        parent::tearDown();
    }

    #[Group('skip-on-lowest')]
    public function testItFormatsWithTheRequestLocaleAndExposesItToTheController(): void
    {
        $renderedCode = $this->renderInRequestLocale('fr', '<twig:Calendar month="2026-09-01" today="2026-09-01" />');

        $this->assertStringContainsString('data-calendar-locale-value="fr"', $renderedCode);
        $this->assertStringContainsString('septembre 2026', $renderedCode);
    }

    #[Group('skip-on-lowest')]
    public function testTheLocalePropWinsOverTheRequestLocale(): void
    {
        $renderedCode = $this->renderInRequestLocale('fr', '<twig:Calendar month="2026-09-01" today="2026-09-01" locale="en" />');

        $this->assertStringContainsString('data-calendar-locale-value="en"', $renderedCode);
        $this->assertStringContainsString('September 2026', $renderedCode);
    }

    private function renderInRequestLocale(string $locale, string $code): string
    {
        $container = self::getContainer();

        /** @var RequestStack $requestStack */
        $requestStack = $container->get('request_stack');

        $request = Request::create('/');
        // Also syncs \Locale::getDefault(), the locale "format_date" falls back to.
        $request->setLocale($locale);
        $requestStack->push($request);

        try {
            /** @var KitFactory $kitFactory */
            $kitFactory = $container->get('ux_toolkit.kit.kit_factory');
            $kit = $kitFactory->createKitFromAbsolutePath(Path::join(__DIR__, '../../kits', 'shadcn'));

            /** @var KitContextRunner $kitContextRunner */
            $kitContextRunner = $container->get('ux_toolkit.kit.kit_context_runner');
            $template = $container->get('twig')->createTemplate($code);

            return $kitContextRunner->runForKit($kit, static fn () => $template->render());
        } finally {
            $requestStack->pop();
        }
    }
}
