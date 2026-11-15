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

/**
 * A menu of "actions", created with DataTableBuilder::createAction(), hidden when none is visible.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class DropdownActionType extends AbstractActionType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('actions')
            ->setAllowedTypes('actions', Action::class.'[]')
        ;
    }

    public function buildView(ActionView $view, Action $action, mixed $row, array $options): void
    {
        /** @var array{actions: array<Action>, ...<string, mixed>} $options */
        /** @var string $id set by Action::createView() */
        $id = $view->vars['id'];
        $view->vars['actions'] = [];
        foreach ($options['actions'] as $child) {
            $childView = $child->createView($row, ['id' => $id.'-'.$child->getName(), 'translation_domain' => $view->vars['translation_domain']]);
            if ($childView->vars['visible']) {
                $view->vars['actions'][$child->getName()] = $childView;
            }
        }

        $view->vars['visible'] = $view->vars['visible'] && [] !== $view->vars['actions'];
    }
}
