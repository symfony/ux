<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Provider;

use Symfony\UX\Image\ImageTransformation;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
interface ProviderInterface
{
    /**
     * The DSN scheme this provider answers to, also the key used in the
     * component's "operations" map.
     */
    public function getName(): string;

    public function generateUrl(ImageTransformation $transformation): string;

    /**
     * @return list<string> operation names accepted in ImageTransformation::$operations
     */
    public function getSupportedOperations(): array;

    /**
     * @return list<string> output formats this provider can encode to
     */
    public function getSupportedFormats(): array;

    /**
     * Whether the provider picks the output format from the request itself.
     *
     * True lets the renderer emit a single <img>; false makes it emit a <picture>
     * with one <source type> per format.
     */
    public function supportsAutoFormat(): bool;
}
