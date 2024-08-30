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
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;

/**
 * Initializes the autocomplete Stimulus controller for any fields with the "autocomplete" option.
 *
 * @internal
 */
final class AutocompleteChoiceTypeExtension extends AbstractTypeExtension
{
    public const CHECKSUM_KEY = '@checksum';

    public function __construct(
        private readonly ChecksumCalculator $checksumCalculator,
        private readonly ?TranslatorInterface $translator = null,
    ) {
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
        } elseif ($form->getConfig()->hasAttribute('autocomplete_url')) {
            $values['url'] = $form->getConfig()->getAttribute('autocomplete_url');
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

        if ($options['extra_options']) {
            $values['url'] = $this->getUrlWithExtraOptions($values['url'], $options['extra_options']);
        }

        $values['loading-more-text'] = $this->trans($options['loading_more_text']);
        $values['no-results-found-text'] = $this->trans($options['no_results_found_text']);
        $values['no-more-results-text'] = $this->trans($options['no_more_results_text']);
        $values['create-option-text'] = $this->trans($options['create_option_text']);
        $values['preload'] = $options['preload'];

        foreach ($values as $name => $value) {
            $attr['data-'.$controllerName.'-'.$name.'-value'] = $value;
        }

        $view->vars['uses_autocomplete'] = true;
        $view->vars['attr'] = $attr;
    }

    private function getUrlWithExtraOptions(string $url, array $extraOptions): string
    {
        $this->validateExtraOptions($extraOptions);

        $extraOptions[self::CHECKSUM_KEY] = $this->checksumCalculator->calculateForArray($extraOptions);
        $extraOptions = base64_encode(json_encode($extraOptions));

        return \sprintf(
            '%s%s%s',
            $url,
            $this->hasUrlParameters($url) ? '&' : '?',
            http_build_query(['extra_options' => $extraOptions]),
        );
    }

    private function hasUrlParameters(string $url): bool
    {
        $parsedUrl = parse_url($url);

        return isset($parsedUrl['query']);
    }

    private function validateExtraOptions(array $extraOptions): void
    {
        foreach ($extraOptions as $optionKey => $option) {
            if (!\is_scalar($option) && !\is_array($option) && null !== $option) {
                throw new \InvalidArgumentException(\sprintf('Extra option with key "%s" must be a scalar value, an array or null. Got "%s".', $optionKey, get_debug_type($option)));
            }

            if (\is_array($option)) {
                $this->validateExtraOptions($option);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete' => false,
            'autocomplete_url' => null,
            'tom_select_options' => [],
            'options_as_html' => false,
            'allow_options_create' => false,
            'loading_more_text' => 'Loading more results...',
            'no_results_found_text' => 'No results found',
            'no_more_results_text' => 'No more results',
            'create_option_text' => 'Add %placeholder%...',
            'min_characters' => null,
            'max_results' => 10,
            'preload' => 'focus',
            'extra_options' => [],
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
