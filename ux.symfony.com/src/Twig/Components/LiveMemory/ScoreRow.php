<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\LiveMemory;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * @demo LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsTwigComponent('LiveMemory:ScoreRow')]
class ScoreRow
{
    public string $label;

    public string $value;

    public int $cols = 25;

    public int $wait = 0;

    public function mount(string|int|null $value = 0): void
    {
        $this->value = (string) $value;
    }

    #[ExposeInTemplate]
    public function getDots(): string
    {
        // LABEL .... XXX
        $charCount = \strlen($this->value) + \strlen($this->label);
        if ($this->cols <= $charCount) {
            return '';
        }

        return str_pad('', $this->cols - $charCount, '.');
    }
}
