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

/**
 * An action, as passed to the theme.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class ActionView
{
    /**
     * @param array<string, mixed> $vars
     */
    public function __construct(
        public array $vars = [],
    ) {
    }
}
