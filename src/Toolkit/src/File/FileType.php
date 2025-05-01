<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\File;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum FileType: string
{
    case Twig = 'twig';
    case StimulusController = 'stimulus_controller';

    public function getLabel(): string
    {
        return match ($this) {
            self::Twig => 'Twig',
            self::StimulusController => 'Stimulus Controller',
        };
    }
}
