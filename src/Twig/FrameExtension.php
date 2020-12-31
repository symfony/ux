<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\Turbo\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig helpers to generated the Turbo Frame elements.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class FrameExtension extends AbstractExtension
{
    public function getFunctions(): iterable
    {
        // TODO: remove this one? Is it really useful?
        yield new TwigFunction('render_turbo_frame', function (string $id, string $src, array $attrs = []): string {
            return $this->turboFrameStart($id, $attrs + ['src' => $src]).$this->turboFrameEnd();
        }, ['is_safe' => ['html']]);
        yield new TwigFunction('turbo_frame_start', [$this, 'turboFrameStart'], ['is_safe' => ['html']]);
        yield new TwigFunction('turbo_frame_end', [$this, 'turboFrameEnd'], ['is_safe' => ['html']]);
    }

    public function turboFrameStart(string $id, array $attrs = []): string
    {
        $a = [];
        foreach ($attrs + ['id' => $id] as $k => $v) {
            $a[] = sprintf('%s="%s"', htmlspecialchars($k, ENT_QUOTES), htmlspecialchars($v, ENT_QUOTES));
        }

        return sprintf('<turbo-frame %s>', implode(' ', $a));
    }

    public function turboFrameEnd(): string
    {
        return '</turbo-frame>';
    }
}
