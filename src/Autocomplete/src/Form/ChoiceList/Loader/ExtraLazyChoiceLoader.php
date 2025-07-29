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
 *
 * @deprecated since Symfony UX 2.23 and will be removed in 3.0, use `Symfony\Component\Form\ChoiceList\Loader\LazyChoiceLoader` instead.
 */
class ExtraLazyChoiceLoader implements ChoiceLoaderInterface
{
    private ?ChoiceListInterface $choiceList = null;

    public function __construct(
        private readonly ChoiceLoaderInterface $decorated,
    ) {
    }

    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        return $this->choiceList ??= new ArrayChoiceList([], $value);
    }

    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        $choices = $this->decorated->loadChoicesForValues($values, $value);
        $this->choiceList = new ArrayChoiceList($choices, $value);

        return $choices;
    }

    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        $values = $this->decorated->loadValuesForChoices($choices, $value);
        $this->loadChoicesForValues($values, $value);

        return $values;
    }
}
