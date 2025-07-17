<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

/**
 * Parses the "data-model" format.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class ModelBindingParser
{
    /**
     * @return array<array{child: string, parent: string}>
     */
    public function parse(string $modelString): array
    {
        $bindingStrings = explode(' ', trim($modelString));

        $bindings = [];
        foreach ($bindingStrings as $bindingString) {
            $bindingString = trim($bindingString);
            if ($bindingString) {
                $bindings[] = $this->parseBinding($bindingString);
            }
        }

        return $bindings;
    }

    /**
     * @return <array{parent: string, child: string}>
     */
    private function parseBinding(string $bindingString): array
    {
        $parts = explode(':', $bindingString);

        return match (\count($parts)) {
            1 => ['child' => 'value', 'parent' => $parts[0]],
            2 => ['child' => $parts[1], 'parent' => $parts[0]],
            default => throw new \InvalidArgumentException(\sprintf('Invalid value "%s" given for "data-model". Format should be "childValue:parentValue" or just "parentValue" to use "value" for the child.', $bindingString)),
        };
    }
}
