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

final class IndentField implements QuillBlockFieldInterface
{
    public const INDENT_FIELD_OPTION_MINUS = '-1';
    public const INDENT_FIELD_OPTION_PLUS = '+1';

    private string $option;

    public function __construct(string $option = self::INDENT_FIELD_OPTION_PLUS)
    {
        $this->option = $option;
    }

    public function getOption(): array
    {
        $array = [];
        $array['indent'] = $this->option;

        return $array;
    }
}
