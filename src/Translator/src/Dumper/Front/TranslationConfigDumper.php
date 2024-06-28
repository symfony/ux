<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Dumper\Front;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @final
 *
 * @experimental
 *
 * @phpstan-type Domain string
 * @phpstan-type Locale string
 * @phpstan-type MessageId string
 */
class TranslationConfigDumper extends AbstractFrontFileDumper implements FrontFileDumperInterface
{
    public function __construct(
        private Filesystem $filesystem,
    ) {
    }

    public function dump(MessageCatalogueInterface ...$catalogues): void
    {
        $this->filesystem->mkdir($this->dumpDir);
        $this->filesystem->remove($this->dumpDir.'/configuration.js');
        $this->filesystem->remove($this->dumpDir.'/configuration.d.ts');

        $this->filesystem->dumpFile($this->dumpDir.'/configuration.js',
            'export const localeFallbacks = '.
            json_encode($this->getLocaleFallbacks(...$catalogues), \JSON_THROW_ON_ERROR).
            ";\n",
        );
        $this->filesystem->dumpFile($this->dumpDir.'/configuration.d.ts', <<<'TS'
import { LocaleType } from '@symfony/ux-translator';

export declare const localeFallbacks: Record<LocaleType, LocaleType>;
TS
        );
    }

    private function getLocaleFallbacks(MessageCatalogueInterface ...$catalogues): array
    {
        $localesFallbacks = [];

        foreach ($catalogues as $catalogue) {
            $localesFallbacks[$catalogue->getLocale()] = $catalogue->getFallbackCatalogue()?->getLocale();
        }

        return $localesFallbacks;
    }
}
