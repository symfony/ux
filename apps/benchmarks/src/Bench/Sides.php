<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class Sides
{
    /**
     * @param list<string> $refs
     *
     * @return list<string>
     */
    public static function labels(array $refs, string $workingTreeLabel): array
    {
        $refsByDirectory = [];
        foreach ($refs as $ref) {
            if ($ref === $workingTreeLabel) {
                throw new \InvalidArgumentException(\sprintf('The ref "%s" is the working tree.', $ref));
            }

            $directory = AgainstInstaller::directoryName($ref);
            $previous = $refsByDirectory[$directory] ?? null;
            if ($ref === $previous) {
                throw new \InvalidArgumentException(\sprintf('The ref "%s" is given twice.', $ref));
            }

            if (null !== $previous) {
                throw new \InvalidArgumentException(\sprintf('The refs "%s" and "%s" share the directory "%s".', $previous, $ref, $directory));
            }

            $refsByDirectory[$directory] = $ref;
        }

        return [...$refs, $workingTreeLabel];
    }
}
