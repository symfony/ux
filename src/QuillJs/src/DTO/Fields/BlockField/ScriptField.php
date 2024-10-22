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

final class ScriptField implements QuillBlockFieldInterface
{
    public const SCRIPT_FIELD_OPTION_SUB = 'sub';
    public const SCRIPT_FIELD_OPTION_SUPER = 'super';

    private string $option;

    public function __construct(string $option = self::SCRIPT_FIELD_OPTION_SUB)
    {
        $this->option = $option;
    }

    /**
     * @return array|mixed[]
     */
    public function getOption(): array
    {
        $array = [];
        $array['script'] = $this->option;

        return $array;
    }
}
