<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait InteractsWithTwigComponents
{
    protected function mountTwigComponent(string $name, array $data = []): object
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('The "%s" trait can only be used on "%s" classes.', __TRAIT__, KernelTestCase::class));
        }

        return static::getContainer()->get('ux.twig_component.component_factory')->create($name, $data)->getComponent();
    }

    /**
     * @param array<string,string> $blocks
     */
    protected function renderTwigComponent(string $name, array $data = [], ?string $content = null, array $blocks = []): RenderedComponent
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException(\sprintf('The "%s" trait can only be used on "%s" classes.', __TRAIT__, KernelTestCase::class));
        }

        $blocks = array_filter(array_merge($blocks, ['content' => $content]));

        if (!$blocks) {
            return new RenderedComponent(
                self::getContainer()->get('twig')
                ->createTemplate('{{ component(name, data) }}')
                ->render([
                    'name' => $name,
                    'data' => $data,
                ])
            );
        }

        $template = \sprintf('{%% component "%s" with data %%}', addslashes($name));

        foreach ($blocks as $blockName => $blockContent) {
            // Inline the content as a Twig string literal instead of passing it
            // through a context variable: a variable would be filtered out when
            // the component template restricts the scope with `{% with ... only %}`.
            $template .= \sprintf(
                '{%% block %s %%}{{ \'%s\'|raw }}{%% endblock %%}',
                $blockName,
                strtr((string) $blockContent, ['\\' => '\\\\', '\'' => '\\\'']),
            );
        }

        $template .= '{% endcomponent %}';

        return new RenderedComponent(
            self::getContainer()->get('twig')
            ->createTemplate($template)
            ->render([
                'data' => $data,
            ])
        );
    }
}
