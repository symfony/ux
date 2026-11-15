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

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A link, or a button submitting a form for another "method".
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class LinkActionType extends AbstractActionType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('href')
            ->setDefaults([
                'method' => 'GET',
                'csrf_token_id' => null,
            ])
            ->setAllowedTypes('href', ['string', \Closure::class])
            ->setAllowedTypes('method', 'string')
            ->setAllowedValues('method', static fn (string $method): bool => \in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true))
            ->setNormalizer('method', static fn (Options $options, string $method): string => strtoupper($method))
            ->setAllowedTypes('csrf_token_id', ['null', 'string', \Closure::class])
            ->setInfo('href', 'The URL, or a closure returning it')
            ->setInfo('csrf_token_id', 'The id of a CSRF token sent as "_token" by a non-GET action, or a closure returning it')
        ;
    }

    public function buildView(ActionView $view, Action $action, mixed $row, array $options): void
    {
        $view->vars['href'] = $options['href'] instanceof \Closure ? ($options['href'])($row) : $options['href'];
        $view->vars['method'] = $options['method'];
        $view->vars['csrf_token_id'] = $options['csrf_token_id'] instanceof \Closure ? ($options['csrf_token_id'])($row) : $options['csrf_token_id'];
    }
}
