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

class DaisyUiSelectRenderingTest extends WebTestCase
{
    use TestHelperTrait;

    #[Group('skip-on-lowest')]
    public function testItRendersANumericSizeAsTheNativeAttribute(): void
    {
        $renderedCode = $this->render('<twig:Select size="4" multiple><option>Crimson</option></twig:Select>');

        $this->assertMatchesRegularExpression('/<select\s+class="select" size="4" multiple=""\s*>/', $renderedCode);
    }

    #[Group('skip-on-lowest')]
    public function testItRendersADaisyUiSizeAsAClass(): void
    {
        $renderedCode = $this->render('<twig:Select size="lg"><option>Crimson</option></twig:Select>');

        $this->assertMatchesRegularExpression('/<select\s+class="select select-lg"\s*>/', $renderedCode);
        $this->assertStringNotContainsString('size=', $renderedCode);
    }

    private function render(string $code): string
    {
        $container = self::getContainer();

        /** @var KitFactory $kitFactory */
        $kitFactory = $container->get('ux_toolkit.kit.kit_factory');
        $kit = $kitFactory->createKitFromAbsolutePath(self::getLocalKitPath('daisyui-5'));

        /** @var KitContextRunner $kitContextRunner */
        $kitContextRunner = $container->get('ux_toolkit.kit.kit_context_runner');
        $template = $container->get('twig')->createTemplate($code);

        return $kitContextRunner->runForKit($kit, static fn () => $template->render());
    }
}
