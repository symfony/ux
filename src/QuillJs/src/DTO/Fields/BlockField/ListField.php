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

final class ListField implements QuillBlockFieldInterface
{
    public const LIST_FIELD_OPTION_ORDERED = 'ordered';
    public const LIST_FIELD_OPTION_BULLET = 'bullet';
    public const LIST_FIELD_OPTION_CHECK = 'check';

    private string $option;

    public function __construct(string $option = self::LIST_FIELD_OPTION_ORDERED)
    {
        $this->option = $option;
    }

    /**
     * @return string[]
     */
    public function getOption(): array
    {
        $array = [];
        $array['list'] = $this->option;

        return $array;
    }
}
