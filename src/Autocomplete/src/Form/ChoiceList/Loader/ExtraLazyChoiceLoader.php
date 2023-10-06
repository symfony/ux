<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Form\ChoiceList\Loader;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * Loads choices on demand only.
 */
class ExtraLazyChoiceLoader implements ChoiceLoaderInterface
{
    private ?ChoiceListInterface $choiceList = null;
    private array $choices = [];
    private bool $cached = false;

    public function __construct(
        private readonly ChoiceLoaderInterface $decorated,
    ) {
    }

    public function loadChoiceList(callable $value = null): ChoiceListInterface
    {
        if (null !== $this->choiceList && $this->cached) {
            return $this->choiceList;
        }

        $this->cached = true;

        return $this->choiceList = new ArrayChoiceList($this->choices, $value);
    }

    public function loadChoicesForValues(array $values, callable $value = null): array
    {
        if ($this->choices !== $choices = $this->decorated->loadChoicesForValues($values, $value)) {
            $this->cached = false;
        }

        return $this->choices = $choices;
    }

    public function loadValuesForChoices(array $choices, callable $value = null): array
    {
        $values = $this->decorated->loadValuesForChoices($choices, $value);

        if ([] === $values || [''] === $values) {
            $newChoices = [];
        } else {
            $newChoices = $choices;
        }

        if ($this->choices !== $newChoices) {
            $this->choices = $newChoices;
            $this->cached = false;
        }

        return $values;
    }
}
