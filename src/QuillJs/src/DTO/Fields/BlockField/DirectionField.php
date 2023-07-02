<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\QuillJs\DTO\Fields\BlockField;

use Symfony\UX\QuillJs\DTO\Fields\Interfaces\QuillBlockFieldInterface;

final class DirectionField implements QuillBlockFieldInterface
{
    public const DIRECTION_FIELD_OPTION_RTL = 'rtl';

    private string $options;

    public function __construct(?string $option = self::DIRECTION_FIELD_OPTION_RTL)
    {
        $this->options = $option;
    }

    public function getOption(): array
    {
        $array = [];
        $array['direction'] = $this->options;

        return $array;
    }
}
