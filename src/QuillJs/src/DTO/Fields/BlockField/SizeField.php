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

final class SizeField implements QuillBlockFieldInterface
{
    public const SIZE_FIELD_OPTION_SMALL = 'small';
    public const SIZE_FIELD_OPTION_NORMAL = false;
    public const SIZE_FIELD_OPTION_LARGE = 'large';
    public const SIZE_FIELD_OPTION_HUGE = 'huge';

    private array $options;

    public function __construct(string|bool ...$options)
    {
        $this->options = $options;
    }

    public function getOption(): array
    {
        $array = [];
        $array['size'] = $this->options;

        return $array;
    }
}
