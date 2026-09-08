<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Form;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;

/**
 * Provides shared logic for WrappedEntityTypeAutocompleter and WrappedChoiceTypeAutocompleter.
 *
 * Classes using this trait must have $formType (string), $formFactory (FormFactoryInterface),
 * $form (?FormInterface), and $options (array) properties.
 *
 * @internal
 */
trait WrappedAutocompleterTrait
{
    public function setOptions(array $options): void
    {
        if (null !== $this->form) {
            throw new \LogicException('The options can only be set before the form is created.');
        }

        $this->options = $options;
    }

    public function reset(): void
    {
        unset($this->form);
        $this->form = null;
    }

    public function isGranted(Security $security): bool
    {
        $securityOption = $this->getForm()->getConfig()->getOption('security');

        if (false === $securityOption) {
            return true;
        }

        if (\is_string($securityOption)) {
            return $security->isGranted($securityOption, $this);
        }

        if (\is_callable($securityOption)) {
            return $securityOption($security);
        }

        throw new \InvalidArgumentException('Invalid passed to the "security" option: it must be the boolean false, a string role or a callable.');
    }

    private function getForm(): FormInterface
    {
        if (null === $this->form) {
            $this->form = $this->formFactory->create($this->formType, options: $this->options);
        }

        return $this->form;
    }
}
