<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Dashboard;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
final class TableCell
{
    public bool $numeric = false;

    #[ExposeInTemplate]
    public function getClasses(): string
    {
        return $this->numeric ? 'text-end tabular-nums' : 'text-start';
    }
}
