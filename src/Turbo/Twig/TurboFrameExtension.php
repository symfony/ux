<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 * @experimental
 */
class TurboFrameExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('turbo_frame_start', [$this, 'startFrame'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('turbo_frame_end', [$this, 'endFrame'], ['is_safe' => ['html']]),
        ];
    }

    public function startFrame(Environment $env, string $id, array $attrs = []): string
    {
        $html = '<turbo-frame id="'.twig_escape_filter($env, $id, 'html_attr').'" ';

        foreach ($attrs as $name => $value) {
            $name = twig_escape_filter($env, $name, 'html_attr');
            $value = twig_escape_filter($env, $value, 'html_attr');

            if (true === $value) {
                $html .= $name.'="'.$name.'" ';
            } elseif (false !== $value) {
                $html .= $name.'="'.$value.'" ';
            }
        }

        return trim($html).'>';
    }

    public function endFrame(): string
    {
        return '</turbo-frame>';
    }
}
