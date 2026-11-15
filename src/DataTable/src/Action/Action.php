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

/**
 * @author Simon Daigre <simon@daig.re>
 */
final class Action
{
    /**
     * @var list<string>
     */
    private readonly array $blockPrefixes;

    /**
     * @param non-empty-list<ActionTypeInterface> $types   from the root type to the action type
     * @param array<string, mixed>                $options
     */
    public function __construct(
        private readonly string $name,
        private readonly array $types,
        private readonly array $options,
    ) {
        $this->blockPrefixes = array_map(static fn (ActionTypeInterface $type): string => $type->getBlockPrefix(), $types);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param mixed                $row  the row of a row action, null otherwise
     * @param array<string, mixed> $vars
     */
    public function createView(mixed $row = null, array $vars = []): ActionView
    {
        $view = new ActionView($vars + ['id' => $this->name, 'translation_domain' => null, 'name' => $this->name, 'block_prefixes' => $this->blockPrefixes, 'row' => $row]);
        foreach ($this->types as $type) {
            $type->buildView($view, $this, $row, $this->options);
        }

        return $view;
    }
}
