<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\Tabs\Node;

use League\CommonMark\Node\Block\AbstractBlock;

final class Tab extends AbstractBlock
{
    public function __construct(
        private readonly string $title,
    ) {
        parent::__construct();
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
