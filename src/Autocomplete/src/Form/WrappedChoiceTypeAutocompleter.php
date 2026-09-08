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

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\Autocomplete\AutocompleteResults;
use Symfony\UX\Autocomplete\OptionsAwareAutocompleterInterface;

/**
 * A choice auto-completer that wraps a form type to get its information.
 *
 * @internal
 */
final class WrappedChoiceTypeAutocompleter implements OptionsAwareAutocompleterInterface, ResetInterface
{
    use WrappedAutocompleterTrait;

    private ?FormInterface $form = null;
    private array $options = [];

    public function __construct(
        private string $formType,
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function fetchResults(string $query, int $page): AutocompleteResults
    {
        $form = $this->getForm();

        $choices = $this->getChoicesFromForm($form);

        // Filter choices by query (case-insensitive string matching on labels)
        if ('' !== $query) {
            $queryLower = mb_strtolower($query);
            $choices = array_filter($choices, static function (array $choice) use ($queryLower) {
                return str_contains(mb_strtolower($choice['text']), $queryLower);
            });
            $choices = array_values($choices);
        }

        $maxResults = $this->getMaxResults() ?? 10;
        $page = max(1, $page);
        $offset = ($page - 1) * $maxResults;

        $pagedChoices = \array_slice($choices, $offset, $maxResults);
        $hasNextPage = \count($choices) > ($offset + $maxResults);

        return new AutocompleteResults($pagedChoices, $hasNextPage);
    }

    /**
     * @return list<array{text: string, value: mixed}>
     */
    private function getChoicesFromForm(FormInterface $form): array
    {
        $choiceList = $form->getConfig()->getAttribute('choice_list');
        if (null === $choiceList) {
            return [];
        }

        $originalKeys = $choiceList->getOriginalKeys();
        $choices = [];
        foreach ($choiceList->getValues() as $value) {
            $choices[] = [
                'value' => $value,
                'text' => (string) ($originalKeys[$value] ?? $value),
            ];
        }

        return $choices;
    }

    private function getMaxResults(): ?int
    {
        return $this->getForm()->getConfig()->getOption('max_results');
    }
}
