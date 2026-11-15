<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

use Symfony\UX\DataTable\Action\ActionView;
use Symfony\UX\DataTable\Column\ColumnView;

/**
 * @author Simon Daigre <simon@daig.re>
 */
final class RowView
{
    /**
     * @param array<string, ColumnView> $cells
     * @param array<string, ActionView> $actions the visible row actions
     * @param array<string, mixed>      $vars
     */
    public function __construct(
        public array $cells = [],
        public array $actions = [],
        public array $vars = [],
    ) {
    }
}
