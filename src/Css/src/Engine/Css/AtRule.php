<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine\Css;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class AtRule extends Container
{
    public string $afterName = ' ';

    /**
     * @param list<Node> $nodes
     */
    public function __construct(
        public string $name,
        public string $params = '',
        array $nodes = [],
    ) {
        parent::__construct($nodes);
    }

    public function toString(): string
    {
        $params = '' !== $this->params ? $this->afterName.$this->params : '';

        return '@'.$this->name.$params.' {'.$this->childrenToString().$this->after.'}';
    }

    public function toCompactString(): string
    {
        return '@'.$this->name.('' !== $this->params ? ' '.$this->params : '').'{'.$this->childrenToCompactString().'}';
    }
}
