<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Resolver;

use Symfony\UX\DesignTokens\Exception\RuntimeException;

/**
 * Loads a DTCG document from a URI, with its references left in place.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface DocumentLoaderInterface
{
    /**
     * @param string $uri absolute file path or URL of a DTCG document
     *
     * @return array<array-key, mixed> the decoded document, with its references left in place
     *
     * @throws RuntimeException when the URI cannot be read, or does not decode to a JSON object
     */
    public function load(string $uri): array;
}
