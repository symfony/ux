<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class TwigComponentTest extends TestCase
{
    private const CONFIG = [
        'tokens' => ['colors' => ['red' => '#f00', 'blue' => '#00f'], 'spacing' => ['md' => '1rem', 'lg' => '2rem']],
        'semantic_tokens' => ['colors' => ['primary' => '{colors.red}', 'fg' => '{colors.blue}']],
    ];

    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/ux_css_project_'.uniqid();
        $components = $this->projectDir.'/templates/components';
        new Filesystem()->mkdir($components);
        $button = '<button {{ attributes.defaults({ class: css({ p: \'md\', color: \'primary\' }) }) }}></button>';
        file_put_contents($components.'/Button.html.twig', $button);
        $badge = <<<'TWIG'
            {% props size = 'sm' %}
            {% set badge = cva(
                base: { color: 'primary' },
                variants: { size: { sm: { px: 'md' }, lg: { px: 'lg' } } },
                defaultVariants: { size: 'sm' },
            ) %}
            <span {{ attributes.defaults({ class: badge.apply({ size: size }) }) }}></span>
            TWIG;
        file_put_contents($components.'/Badge.html.twig', $badge);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->projectDir);
    }

    public function testTheCallerClassListWins(): void
    {
        $html = $this->render('<twig:Button class="{{ css({ p: \'lg\' }) }}" />');

        $this->assertStringContainsString('class="c_primary p_lg"', $html);
    }

    public function testTheDynamicPropSyntaxWorksToo(): void
    {
        $html = $this->render('<twig:Button :class="css({ p: \'lg\' })" />');

        $this->assertStringContainsString('class="c_primary p_lg"', $html);
    }

    public function testARuntimeValueTakesPartInTheMerge(): void
    {
        $html = $this->render('<twig:Button class="{{ css({ p: size }) }}" />', ['size' => 'lg']);

        $this->assertStringContainsString('class="c_primary p_lg"', $html);
    }

    public function testAPlainStringIsAppended(): void
    {
        $html = $this->render('<twig:Button class="card" />');

        $this->assertStringContainsString('class="p_md c_primary card"', $html);
    }

    public function testWithoutClassTheDefaultsApply(): void
    {
        $html = $this->render('<twig:Button />');

        $this->assertStringContainsString('class="p_md c_primary"', $html);
    }

    public function testACvaComponentRendersItsVariantClasses(): void
    {
        $html = $this->render('<twig:Badge size="lg" />');

        $this->assertStringContainsString('class="c_primary px_lg"', $html);
    }

    public function testACvaComponentWithoutPropsRendersItsDefaultVariant(): void
    {
        $html = $this->render('<twig:Badge />');

        $this->assertStringContainsString('class="c_primary px_md"', $html);
    }

    public function testTheCallerClassListWinsOverACvaVariant(): void
    {
        $html = $this->render('<twig:Badge size="lg" class="{{ css({ px: \'md\' }) }}" />');

        $this->assertStringContainsString('class="c_primary px_md"', $html);
    }

    private function render(string $template, array $context = []): string
    {
        $kernel = new TestKernel(self::CONFIG, 'test', true, $this->projectDir, null, true);
        $kernel->boot();
        $twig = $kernel->getContainer()->get('test.service_container')->get('twig');

        return $twig->createTemplate($template)->render($context);
    }
}
