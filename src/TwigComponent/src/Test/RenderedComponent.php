<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Test;

use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RenderedComponent implements \Stringable
{
    /**
     * @internal
     */
    public function __construct(private string $html)
    {
    }

    public function crawler(): Crawler
    {
        if (!class_exists(Crawler::class)) {
            throw new \LogicException(\sprintf('"symfony/dom-crawler" is required to use "%s()" (install with "composer require symfony/dom-crawler").', __METHOD__));
        }

        return new Crawler($this->html);
    }

    public function toString(): string
    {
        return $this->html;
    }

    public function __toString(): string
    {
        return $this->html;
    }
}
