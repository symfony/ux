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

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the autocomplete URL resolution logic shared by BaseEntityAutocompleteType and AutocompleteChoiceType.
 *
 * @internal
 */
trait AutocompleteTypeTrait
{
    /**
     * Uses the provided URL, or auto-generates from the provided alias.
     *
     * @param class-string<AsAutocompleteField> $attributeClass
     */
    private function resolveAutocompleteUrl(FormBuilderInterface $builder, array $options, string $attributeClass): string
    {
        if ($options['autocomplete_url']) {
            return $options['autocomplete_url'];
        }

        $formType = $builder->getType()->getInnerType();
        $attribute = $attributeClass::getInstance($formType::class);

        if (!$attribute) {
            throw new \LogicException(\sprintf('You must either provide your own autocomplete_url, or add #[%s] attribute to "%s".', $attributeClass, $formType::class));
        }

        return $this->getUrlGenerator()->generate($attribute->getRoute(), [
            'alias' => $attribute->getAlias() ?: $attributeClass::shortName($formType::class),
        ]);
    }

    abstract private function getUrlGenerator(): UrlGeneratorInterface;
}
