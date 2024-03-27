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

    private array $options;

    public function __construct(string ...$options)
    {
        $this->options = $options;
    }

    public function getOption(): array
    {
        $array = [];
        foreach ($this->options as $option) {
            $array[] = ['list' => $option];
        }

        return $array;
    }
}
