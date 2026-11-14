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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

class DialogContentRenderingTest extends WebTestCase
{
    use TestHelperTrait;

    public static function provideRecipes(): iterable
    {
        yield 'dialog' => ['Dialog'];
        yield 'sheet' => ['Sheet'];
        yield 'drawer' => ['Drawer'];
    }

    #[Group('skip-on-lowest')]
    #[DataProvider('provideRecipes')]
    public function testItClosesOnAnyCloseRequestByDefault(string $recipe): void
    {
        $renderedCode = $this->render(\sprintf('<twig:%1$s id="demo"><twig:%1$s:Content>Content</twig:%1$s:Content></twig:%1$s>', $recipe));

        $this->assertSame(1, substr_count($renderedCode, 'closedby='));
        $this->assertStringContainsString('closedby="any"', $renderedCode);
    }

    #[Group('skip-on-lowest')]
    #[DataProvider('provideRecipes')]
    public function testItLetsTheClosedbyAttributeBeOverridden(string $recipe): void
    {
        $renderedCode = $this->render(\sprintf('<twig:%1$s id="demo"><twig:%1$s:Content closedby="closerequest">Content</twig:%1$s:Content></twig:%1$s>', $recipe));

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
