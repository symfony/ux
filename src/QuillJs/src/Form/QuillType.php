<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\QuillJs\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\QuillJs\DTO\Options\DebugOption;
use Symfony\UX\QuillJs\DTO\Options\ThemeOption;

class QuillType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['quill_options'] = json_encode($options['quill_options']);
        $view->vars['attr']['quill_extra_options'] = json_encode($options['quill_extra_options']);
        $view->vars['attr']['sanitizer'] = $options['quill_extra_options']['sanitizer'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'sanitize_html' => false,
            'label' => false,
            'error_bubbling' => true,
            'quill_options' => ['bold', 'italic'],
            'quill_extra_options' => function (OptionsResolver $resolver) {
                $resolver
                    ->setDefault('upload_handler', function (OptionsResolver $spoolResolver): void {
                        $spoolResolver->setDefaults([
                            'type' => 'form',
                            'path' => null,
                        ]);
                        $spoolResolver->setAllowedTypes('type', ['string', 'null']);
                        $spoolResolver->setAllowedTypes('path', ['string', 'null']);
                        $spoolResolver->setAllowedValues('type', ['json', 'form', null]);
                    })
                ;
                $resolver
                    ->setDefault('debug', DebugOption::DEBUG_OPTION_ERROR)
                    ->setAllowedTypes('debug', 'string')
                    ->setAllowedValues('debug', [DebugOption::DEBUG_OPTION_ERROR, DebugOption::DEBUG_OPTION_WARNING, DebugOption::DEBUG_OPTION_LOG, DebugOption::DEBUG_OPTION_INFO])
                ;
                $resolver
                    ->setDefault('height', '200px')
                    ->setAllowedTypes('height', ['string', 'null'])
                    ->setAllowedValues('height', function (?string $value) {
                        if (null === $value) {
                            return true;
                        }

                        return preg_match('/(\d+)(px$|em$|ex$|%$)/', $value);
                    })
                ;
                $resolver
                    ->setDefault('theme', 'snow')
                    ->setAllowedTypes('theme', 'string')
                    ->setAllowedValues('theme', [ThemeOption::QUILL_THEME_SNOW, ThemeOption::QUILL_THEME_BUBBLE])
                ;
                $resolver
                    ->setDefault('placeholder', 'Quill editor')
                    ->setAllowedTypes('placeholder', 'string')
                ;
                $resolver
                    ->setDefault('sanitizer', null)
                    ->setAllowedTypes('sanitizer', ['string', 'null'])
                ;
            },
        ]);

        $resolver->setAllowedTypes('quill_options', ['array']);
    }

    public function getBlockPrefix(): string
    {
        return 'quill';
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}
