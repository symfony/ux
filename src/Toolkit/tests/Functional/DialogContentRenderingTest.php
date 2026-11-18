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

class DialogContentRenderingTest extends WebTestCase
{
    use TestHelperTrait;

    #[Group('skip-on-lowest')]
    public function testItClosesOnAnyCloseRequestByDefault(): void
    {
        $renderedCode = $this->render('<twig:Dialog id="demo"><twig:Dialog:Content>Content</twig:Dialog:Content></twig:Dialog>');

        $this->assertSame(1, substr_count($renderedCode, 'closedby='));
        $this->assertStringContainsString('closedby="any"', $renderedCode);
    }

    #[Group('skip-on-lowest')]
    public function testItLetsTheClosedbyAttributeBeOverridden(): void
    {
        $renderedCode = $this->render('<twig:Dialog id="demo"><twig:Dialog:Content closedby="closerequest">Content</twig:Dialog:Content></twig:Dialog>');

        $this->assertSame(1, substr_count($renderedCode, 'closedby='));
        $this->assertStringContainsString('closedby="closerequest"', $renderedCode);
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
