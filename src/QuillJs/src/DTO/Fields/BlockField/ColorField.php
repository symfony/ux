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

final class ColorField implements QuillBlockFieldInterface
{
    /**
     * @var string[]
     */
    private array $options = [];

    public function __construct(string ...$options)
    {
        $this->options = $options;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getOption(): array
    {
        $array = [];
        $array['color'] = $this->options;

        return $array;
    }
}
