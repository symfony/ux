<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Validation;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\RuntimeException;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class Normalizer
{
    public function __construct(private readonly DtcgValidator $validator)
    {
    }

    /**
     * @param 'tokens'|'resolver'|null $kind
     * @param bool                     $partial see DtcgValidator::validateFile()
     */
    public function normalize(string $json, string $source = 'stdin', string $basePath = '', ?string $kind = null, bool $partial = false): string
    {
        $this->validator->validateJson($json, $source, $basePath, $kind, $partial);

        $document = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);

        return json_encode(
            $document,
            \JSON_PRETTY_PRINT | \JSON_PRESERVE_ZERO_FRACTION | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
        )."\n";
    }

    /** @param bool $partial see DtcgValidator::validateFile() */
    public function normalizeFile(string $path, bool $partial = false): string
    {
        if (!is_file($path)) {
            throw new RuntimeException(\sprintf('Design token document not found: "%s".', $path));
        }
        try {
            $json = new Filesystem()->readFile($path);
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException(\sprintf('Could not read design token document: "%s".', $path), previous: $e);
        }
        $kind = match (true) {
            str_ends_with($path, '.resolver.json') => 'resolver',
            str_ends_with($path, '.tokens.json'), str_ends_with($path, '.tokens') => 'tokens',
            default => throw new InvalidArgumentException(\sprintf('Expected a .tokens.json, .tokens, or .resolver.json file, got "%s".', $path)),
        };

        return $this->normalize($json, $path, \dirname($path), $kind, $partial);
    }
}
