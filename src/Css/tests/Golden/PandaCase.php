<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Golden;

final class PandaCase
{
    /**
     * @param list<mixed>          $inputs
     * @param array<string, mixed> $expected
     */
    public function __construct(
        public readonly string $id,
        public readonly string $kind,
        public readonly string $config,
        public readonly array $inputs,
        public readonly array $expected,
        public readonly mixed $userConfig,
        public readonly ?array $configPatch,
        public readonly ?string $unsupported,
        public readonly bool $expectTypeError,
        public readonly bool $undefinedValues,
        public readonly string $sourceFile,
        public readonly ?int $sourceLine,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['kind'],
            $data['config'],
            $data['inputs'] ?? [],
            $data['expected'] ?? [],
            $data['userConfig'] ?? null,
            $data['configPatch'] ?? null,
            $data['unsupported'] ?? null,
            $data['expectTypeError'] ?? false,
            $data['undefinedValues'] ?? false,
            $data['source']['file'],
            $data['source']['line'] ?? null,
        );
    }

    public function matches(string $actual): bool
    {
        return $this->normalize($actual) === $this->normalize($this->expectedOutput());
    }

    /**
     * Panda re-parses the token CSS it writes several times, which leaves random indentation and no last semicolon before some closing braces.
     */
    public function normalize(string $css): string
    {
        return 'token-css' === $this->kind ? preg_replace('/;?\s*\}/', '}', $css) : $css;
    }

    public function expectedOutput(): string
    {
        return 'codegen-css' === $this->kind ? $this->expected['className'] : $this->expected['css'];
    }

    public function sourceLocation(): string
    {
        return $this->sourceFile.':'.($this->sourceLine ?? '?');
    }
}
