<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Component;

/**
 * The API reference extracted from a Stimulus controller: its values, targets and actions.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusControllerDoc
{
    /**
     * @param list<StimulusControllerValue>  $values
     * @param list<StimulusControllerTarget> $targets
     * @param list<StimulusControllerAction> $actions
     */
    public function __construct(
        public readonly array $values = [],
        public readonly array $targets = [],
        public readonly array $actions = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->values && [] === $this->targets && [] === $this->actions;
    }
}
