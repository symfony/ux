<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Action;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\UX\DataTable\Humanizer;

/**
 * The root action type.
 *
 * The options accepting a closure call it with the row of a row action, null otherwise.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class ActionType extends AbstractActionType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'label' => null,
                'icon' => null,
                'attr' => [],
                'visible' => true,
                'confirmation' => null,
            ])
            ->setAllowedTypes('label', ['null', 'string', TranslatableInterface::class])
            ->setAllowedTypes('icon', ['null', 'string'])
            ->setAllowedTypes('attr', ['array', \Closure::class])
            ->setAllowedTypes('visible', ['bool', \Closure::class])
            ->setAllowedTypes('confirmation', ['null', 'bool', 'string', TranslatableInterface::class, \Closure::class])
            ->setInfo('label', 'The label; null to humanize the action name')
            ->setInfo('icon', 'The name of the icon, rendered when symfony/ux-icons is installed')
            ->setInfo('visible', 'Whether the action is shown, or a closure returning it')
            ->setInfo('confirmation', 'The message to confirm before acting, true for "Are you sure?", or a closure returning it')
        ;
    }

    public function buildView(ActionView $view, Action $action, mixed $row, array $options): void
    {
        $resolve = static fn (mixed $option): mixed => $option instanceof \Closure ? $option($row) : $option;
        $confirmation = $resolve($options['confirmation']);

        $view->vars['label'] = $options['label'] ?? Humanizer::humanize($action->getName());
        $view->vars['translatable_label'] = $view->vars['label'] instanceof TranslatableInterface;
        $view->vars['icon'] = $options['icon'];
        $view->vars['attr'] = $resolve($options['attr']);
        $view->vars['visible'] = $resolve($options['visible']);
        $view->vars['confirmation'] = true === $confirmation ? 'Are you sure?' : ($confirmation ?: null);
        // A translatable confirmation is always translated, in its own domain
        $view->vars['confirmation_translation_domain'] = match (true) {
            true === $confirmation => 'UXDataTableBundle',
            $confirmation instanceof TranslatableInterface => null,
            default => $view->vars['translation_domain'],
        };
    }

    public function getParent(): ?string
    {
        return null;
    }
}
