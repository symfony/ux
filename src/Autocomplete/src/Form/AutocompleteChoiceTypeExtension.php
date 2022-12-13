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

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Initializes the autocomplete Stimulus controller for any fields with the "autocomplete" option.
 *
 * @internal
 */
final class AutocompleteChoiceTypeExtension extends AbstractTypeExtension
{
    public function __construct(private ?TranslatorInterface $translator = null)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            ChoiceType::class,
            TextType::class,
        ];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$options['autocomplete']) {
            $view->vars['uses_autocomplete'] = false;

            return;
        }

        $attr = $view->vars['attr'] ?? [];

        $controllerName = 'symfony--ux-autocomplete--autocomplete';
        $attr['data-controller'] = trim(($attr['data-controller'] ?? '').' '.$controllerName);

        $values = [];
        if ($options['autocomplete_url']) {
            $values['url'] = $options['autocomplete_url'];
        }

        if ($options['options_as_html']) {
            $values['options-as-html'] = '';
        }

        if ($options['allow_options_create']) {
            $values['allow-options-create'] = '';
        }

        if ($options['tom_select_options']) {
            $values['tom-select-options'] = json_encode($options['tom_select_options']);
        }

        if ($options['max_results']) {
            $values['max-results'] = $options['max_results'];
        }

        if ($options['min_characters']) {
            $values['min-characters'] = $options['min_characters'];
        }

        $values['no-results-found-text'] = $this->trans($options['no_results_found_text']);
        $values['no-more-results-text'] = $this->trans($options['no_more_results_text']);
        $values['preload'] = $options['preload'];

        foreach ($values as $name => $value) {
            $attr['data-'.$controllerName.'-'.$name.'-value'] = $value;
        }

        $view->vars['uses_autocomplete'] = true;
        $view->vars['attr'] = $attr;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete' => false,
            'autocomplete_url' => null,
            'tom_select_options' => [],
            'options_as_html' => false,
            'allow_options_create' => false,
            'no_results_found_text' => 'No results found',
            'no_more_results_text' => 'No more results',
            'min_characters' => 3,
            'max_results' => 10,
            'preload' => 'focus',
        ]);

        // if autocomplete_url is passed, then HTML options are already supported
        $resolver->setNormalizer('options_as_html', function (Options $options, $value) {
            return null === $options['autocomplete_url'] ? $value : false;
        });

        $resolver->setNormalizer('preload', function (Options $options, $value) {
            if (\is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            return $value;
        });
    }

    private function trans(string $message): string
    {
        return $this->translator ? $this->translator->trans($message, [], 'AutocompleteBundle') : $message;
    }
}
