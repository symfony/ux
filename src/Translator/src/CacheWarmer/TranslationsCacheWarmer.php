<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\CacheWarmer;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\UX\Translator\TranslationsDumper;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @experimental
 */
class TranslationsCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private ?TranslatorBagInterface $translatorBag,
        private TranslationsDumper $translationsDumper,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        if (null === $this->translatorBag) {
            $this->logger?->warning('Translator bag not available');
            return [];
        }
        $this->translationsDumper->dump(
            ...$this->translatorBag->getCatalogues()
        );

        // No need to preload anything
        return [];
    }
}
