<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Importer;

use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;

/**
 * Converts a document of another format into DTCG, for ux:design-tokens:import.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface ImporterInterface
{
    /**
     * A value DTCG cannot hold is left out, and logged when the importer is LoggerAwareInterface.
     *
     * @param array<string, mixed> $context options of this import, which a format reads when it understands them
     *
     * @return array<string, mixed> a DTCG token document
     *
     * @throws InvalidArgumentException when the contents cannot be read as this format
     */
    public function import(string $contents, array $context = []): array;
}
