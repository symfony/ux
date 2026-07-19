<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Alert\Node;

use League\CommonMark\Node\Block\AbstractBlock;

final class Alert extends AbstractBlock
{
    public function __construct(
        private readonly string $type,
    ) {
        parent::__construct();
    }

    public function getType(): string
    {
        return $this->type;
    }
}
