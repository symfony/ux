<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Dropzone\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class DropzoneType extends AbstractType
{
    private const CONTROLLER = 'symfony--ux-dropzone--dropzone';

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['remove_label'] = $options['remove_label'];
        $view->vars['max_files'] = $options['max_files'];
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['accept_message'] = $options['accept_message'];
        $view->vars['max_files_message'] = $options['max_files_message'];

        $view->vars['container_attr'] = [
            'class' => $options['multiple'] ? 'dropzone-container dropzone-container-multiple' : 'dropzone-container',
            'data-controller' => trim(($view->vars['attr']['data-controller'] ?? '').' '.self::CONTROLLER),
            'data-'.self::CONTROLLER.'-remove-label-value' => $options['remove_label'],
        ];
        if ($options['multiple']) {
            $view->vars['container_attr']['data-'.self::CONTROLLER.'-multiple-value'] = 'true';
            if (null !== $options['max_files']) {
                $view->vars['container_attr']['data-'.self::CONTROLLER.'-max-files-value'] = (string) $options['max_files'];
            }
        }

        // Also in "attr", where themes copied from older versions read it
        unset($view->vars['attr']['placeholder']);
        if (false !== $options['placeholder']) {
            $view->vars['attr']['placeholder'] = $options['placeholder'];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // The default text used to be the default of the "attr" option, which a custom "attr" replaces
            'placeholder' => static fn (Options $options) => [] === $options['attr'] ? 'Drag and drop or browse' : ($options['attr']['placeholder'] ?? false),
            'remove_label' => 'Remove',
            'max_files' => null,
            'accept_message' => 'File type not accepted: %files%',
            'max_files_message' => 'Too many files, not added: %files%',
        ]);
        $resolver->setAllowedTypes('placeholder', ['string', 'bool', TranslatableInterface::class]);
        $resolver->setAllowedValues('placeholder', static fn (string|bool|TranslatableInterface $placeholder): bool => true !== $placeholder);
        $resolver->setAllowedTypes('remove_label', 'string');
        foreach (['accept_message', 'max_files_message'] as $option) {
            $resolver->setAllowedTypes($option, ['string', 'bool', TranslatableInterface::class]);
            $resolver->setAllowedValues($option, static fn (string|bool|TranslatableInterface $message): bool => true !== $message);
        }
        $resolver->addNormalizer('attr', static function (Options $options, array $attr): array {
            if (isset($attr['placeholder'])) {
                trigger_deprecation('symfony/ux-dropzone', '3.7', 'Passing the placeholder in the "attr" option of "%s" is deprecated, use the "placeholder" option instead.', self::class);
            }

            return $attr;
        });
        $resolver->setAllowedTypes('max_files', ['null', 'int']);
        $resolver->setAllowedValues('max_files', static fn (?int $maxFiles): bool => null === $maxFiles || $maxFiles > 0);
        $resolver->setNormalizer('max_files', static function (Options $options, ?int $maxFiles): ?int {
            if (null !== $maxFiles && !$options['multiple']) {
                throw new InvalidOptionsException('The "max_files" option requires the "multiple" option.');
            }

            return $maxFiles;
        });

        // The "constraints" option only exists when the validator extension is registered
        if ($resolver->isDefined('constraints')) {
            $resolver->addNormalizer('constraints', static fn (Options $options, array $constraints): array => null === $options['max_files'] ? $constraints : [...$constraints, new Count(max: $options['max_files'])]);
        }
    }

    public function getParent(): string
    {
        return FileType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'dropzone';
    }
}
