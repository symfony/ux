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
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

class MessageScrollerButtonRenderingTest extends WebTestCase
{
    use TestHelperTrait;

    #[Group('skip-on-lowest')]
    public function testItRendersAnArrowAndALabelByDefault(): void
    {
        $renderedCode = $this->render('<twig:MessageScroller:Button direction="start" />');

        $this->assertStringContainsString('<svg', $renderedCode);
        $this->assertStringContainsString('<span class="sr-only">Scroll to start</span>', $renderedCode);
    }

    #[Group('skip-on-lowest')]
    public function testItRendersTheContentItIsGivenAsHtml(): void
    {
        $renderedCode = $this->render('<twig:MessageScroller:Button><span class="label">Latest</span></twig:MessageScroller:Button>');

        $this->assertStringContainsString('<span class="label">Latest</span>', $renderedCode);
        $this->assertStringNotContainsString('Scroll to end', $renderedCode);
    }

    private function render(string $code): string
    {
        $container = self::getContainer();

        /** @var KitFactory $kitFactory */
        $kitFactory = $container->get('ux_toolkit.kit.kit_factory');
        $kit = $kitFactory->createKitFromAbsolutePath(self::getLocalKitPath('shadcn'));

        /** @var KitContextRunner $kitContextRunner */
        $kitContextRunner = $container->get('ux_toolkit.kit.kit_context_runner');
        $template = $container->get('twig')->createTemplate($code);

        return $kitContextRunner->runForKit($kit, static fn () => $template->render());
    }
}
