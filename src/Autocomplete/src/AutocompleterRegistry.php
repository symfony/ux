<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete;

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class AutocompleterRegistry
{
    public function __construct(
        private ServiceLocator $autocompletersLocator,
    ) {
    }

    public function getAutocompleter(string $alias): ?EntityAutocompleterInterface
    {
        return $this->autocompletersLocator->has($alias) ? $this->autocompletersLocator->get($alias) : null;
    }

    /**
     * @return list<string>
     */
    public function getAutocompleterNames(): array
    {
        return array_keys($this->autocompletersLocator->getProvidedServices());
    }
}
