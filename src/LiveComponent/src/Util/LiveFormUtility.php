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

use Symfony\Component\Form\FormView;

/**
 * @internal
 */
final class LiveFormUtility
{
    /**
     * Removes the "paths" not present in the $data array.
     *
     * Given an array of paths - ['name', 'post.title', 'post.description'] -
     * and a $data array - ['name' => 'Ryan', 'post' => ['title' => 'Hi there!']] -
     * this removes any "paths" not present in the array.
     */
    public static function removePathsNotInData(array $paths, array $data): array
    {
        return array_values(array_filter($paths, static function ($path) use ($data) {
            $parts = explode('.', $path);
            while (\count($parts) > 0) {
                $part = $parts[0];

                if (!\is_array($data)) {
                    return false;
                }

                if (!\array_key_exists($part, $data)) {
                    return false;
                }

                // reset $parts and go to the next level
                unset($parts[0]);
                $parts = array_values($parts);
                $data = $data[$part];
            }

            // key was found at all levels
            return true;
        }));
    }

    public static function doesFormContainAnyErrors(FormView $formView): bool
    {
        if (($formView->vars['errors'] ?? null) && \count($formView->vars['errors']) > 0) {
            return true;
        }

        foreach ($formView->children as $childView) {
            if (self::doesFormContainAnyErrors($childView)) {
                return true;
            }
        }

        return false;
    }
}
