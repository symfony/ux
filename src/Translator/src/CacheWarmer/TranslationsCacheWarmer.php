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

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\UX\Translator\TranslationsDumper;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
class TranslationsCacheWarmer implements CacheWarmerInterface
{
    /**
     * @param list<string> $includedDomains
     * @param list<string> $excludedDomains
     * @param list<string> $keysPatterns
     */
    public function __construct(
        private TranslatorBagInterface $translatorBag,
        private TranslationsDumper $translationsDumper,
        private string $dumpDir,
        private bool $dumpTypeScript,
        private array $includedDomains,
        private array $excludedDomains,
        private array $keysPatterns,
        private string $defaultLocale,
    ) {
    }

    /**
     * Optional once the translations are dumped.
     *
     * The dump directory is an AssetMapper path, which must exist as soon as the container is built.
     */
    public function isOptional(): bool
    {
        return is_dir($this->dumpDir);
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        // getCatalogues() only returns the catalogues already loaded
        if ($this->translatorBag instanceof WarmableInterface) {
            $this->translatorBag->warmUp($cacheDir, $buildDir);
        }

        $this->translationsDumper->dump(
            $this->translatorBag->getCatalogues(),
            $this->dumpDir,
            $this->dumpTypeScript,
            $this->includedDomains,
            $this->excludedDomains,
            $this->keysPatterns,
            $this->defaultLocale,
        );

        // No need to preload anything
        return [];
    }
}
