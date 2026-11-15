<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Action;

use Symfony\UX\DataTable\TypeInterface;

/**
 * @author Simon Daigre <simon@daig.re>
 */
interface ActionTypeInterface extends TypeInterface
{
    /**
     * @param mixed                $row     the row of a row action, null otherwise
     * @param array<string, mixed> $options
     */
    public function buildView(ActionView $view, Action $action, mixed $row, array $options): void;
}
