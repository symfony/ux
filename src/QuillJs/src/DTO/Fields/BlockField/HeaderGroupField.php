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

final class HeaderGroupField implements QuillBlockFieldInterface
{
    public const HEADER_OPTION_1 = 1;
    public const HEADER_OPTION_2 = 2;
    public const HEADER_OPTION_3 = 3;
    public const HEADER_OPTION_4 = 4;
    public const HEADER_OPTION_5 = 5;
    public const HEADER_OPTION_6 = 6;
    public const HEADER_OPTION_NORMAL = false;

    /**
     * @var int[]
     */
    private array $options;

    public function __construct(int ...$options)
    {
        $this->options = $options;
    }

    /**
     * @return array<string, array<int>>
     */
    public function getOption(): array
    {
        $array = [];
        $array['header'] = $this->options;

        return $array;
    }
}
