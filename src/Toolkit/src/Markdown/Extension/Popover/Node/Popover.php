<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Popover\Node;

use League\CommonMark\Node\Inline\AbstractInline;

final class Popover extends AbstractInline
{
    public function __construct(
        private readonly string $description,
    ) {
        parent::__construct();
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
