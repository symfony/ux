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

final class HeaderField implements QuillBlockFieldInterface
{
    public const HEADER_OPTION_1 = 1;
    public const HEADER_OPTION_2 = 2;

    private int $options;

    public function __construct(?int $options = self::HEADER_OPTION_1)
    {
        $this->options = $options;
    }

    public function getOption(): array
    {
        $array = [];
        $array['header'] = $this->options;

        return $array;
    }
}
