<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Symfony\UX\TwigComponent\CVA;
use Twig\DeprecatedCallableInfo;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [ComponentRuntime::class, 'render'], ['is_safe' => ['all']]),
            new TwigFunction('cva', [$this, 'cva'], [
                ...(class_exists(DeprecatedCallableInfo::class)
                    ? ['deprecation_info' => new DeprecatedCallableInfo('symfony/ux-twig-component', '2.20', 'html_cva', 'twig/html-extra')]
                    : ['deprecated' => '2.20', 'deprecating_package' => 'symfony/ux-twig-component', 'alternative' => 'html_cva']),
            ]),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            new ComponentTokenParser(),
            new PropsTokenParser(),
        ];
    }

    /**
     * Create a CVA instance.
     *
     * base some base class you want to have in every matching recipes
     * variants your recipes class
     * compoundVariants compounds allow you to add extra class when multiple variation are matching in the same time
     * defaultVariants allow you to add a default class when no recipe is matching
     *
     * @see https://symfony.com/bundles/ux-twig-component/current/index.html#component-with-complex-variants-cva
     *
     * @param array{
     *   base: string|string[]|null,
     *   variants: array<string, array<string, string|string[]>>,
     *   compoundVariants: list<array<string, string|string[]>>,
     *   defaultVariants: array<string, string>,
     * } $cva
     */
    public function cva(array $cva): CVA
    {
        trigger_deprecation('symfony/ux-twig-component', '2.20', 'Twig Function "cva" is deprecated; use "html_cva" from the "twig/html-extra" package (available since version 3.12) instead.');

        return new CVA(
            $cva['base'] ?? '',
            $cva['variants'] ?? [],
            $cva['compoundVariants'] ?? [],
            $cva['defaultVariants'] ?? [],
        );
    }
}
