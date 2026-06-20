<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Content\Converter;

use Symfony\UX\Editor\Content\EditorContentInterface;
use Symfony\UX\Editor\Exception\UnsupportedConversionException;

final class ContentConverterRegistry
{
    /**
     * @var array<string, ContentConverterInterface>
     */
    private array $byPair = [];

    /**
     * @param iterable<ContentConverterInterface> $converters
     */
    public function __construct(iterable $converters = [])
    {
        foreach ($converters as $c) {
            $this->byPair[$this->key($c->getFrom(), $c->getTo())] = $c;
        }
    }

    public function convert(EditorContentInterface $content, string $from, string $to): EditorContentInterface
    {
        if ($from === $to) {
            return $content;
        }

        $converter = $this->byPair[$this->key($from, $to)]
            ?? throw new UnsupportedConversionException(\sprintf('No converter registered for "%s" -> "%s"', $from, $to));

        return $converter->convert($content);
    }

    /**
     * @return list<array{from: string, to: string}>
     */
    public function pairs(): array
    {
        $out = [];
        foreach (array_keys($this->byPair) as $key) {
            [$from, $to] = explode('::', $key, 2);
            $out[] = ['from' => $from, 'to' => $to];
        }

        return $out;
    }

    private function key(string $from, string $to): string
    {
        return $from.'::'.$to;
    }
}
