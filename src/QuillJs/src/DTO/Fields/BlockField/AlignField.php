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

final class AlignField implements QuillBlockFieldInterface
{
    public const ALIGN_FIELD_OPTION_LEFT = false;
    public const ALIGN_FIELD_OPTION_CENTER = 'center';
    public const ALIGN_FIELD_OPTION_RIGHT = 'right';
    public const ALIGN_FIELD_OPTION_JUSTIFY = 'justify';

    private array $options;

    public function __construct(string|bool ...$options)
    {
        $this->options = $options;
    }

    public function getOption(): array
    {
        $array = [];
        $array['align'] = $this->options;

        return $array;
    }
}
