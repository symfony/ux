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
use Symfony\Component\Translation\Dumper\JsonFileDumper;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\UX\Translator\Dumper\ModuleDumper;

use function Symfony\Component\String\s;

/**
 * @final
 *
 * @experimental
 *
 * A dumper that generates a JavaScript module file for each domain/locale + a main module
 * per domain to easily import all it's translations at once.
 */
class DomainModuleDumper extends AbstractFrontFileDumper implements FrontFileDumperInterface
{
    /**
     * Pattern used to extract full domain name and locale from a file dumped by the ModuleDumper.
     * @see https://regex101.com/r/pMxiOm/1
     */
    private const TRANSLATIONS_FILENAME_PATTERN = '#\./translations/([a-zA-Z0-9\-+_]+)\.([a-zA-Z_]+)\.js$#';

    public function __construct(
        private Filesystem $filesystem,
    ) {
    }

    public function dump(MessageCatalogueInterface ...$catalogues): void
    {
        $this->filesystem->mkdir($this->dumpDir);
        $this->filesystem->remove($this->dumpDir.'/domains');
        $this->filesystem->mkdir($this->dumpDir.'/domains');
        $this->filesystem->mkdir($this->dumpDir.'/domains/translations');

        $fileDumper = new ModuleDumper(new JsonFileDumper());
        $domains = [];

        // Generate a module file for each domain and locale
        foreach ($catalogues as $catalogue) {
            $fileDumper->dump($catalogue, ['path' => $this->dumpDir.'/domains/translations', 'json_encoding' => \JSON_PRETTY_PRINT]);
            $domains = array_merge($domains, $catalogue->getDomains());
        }

        // Generate a module file for each domain, that exposes translations indexed by domain, then locale
        foreach (array_unique($domains) as $domain) {
            $this->dumpDomainModule($domain);
        }
    }

    private static function getModuleTemplate(): string
    {
        return <<<'JAVASCRIPT'
%s

export default {
%s
};
JAVASCRIPT;
    }

    /**
     * From a given domain name, looks for all previously dumped translations files that matches this domain (by locale + intl-icu).
     * Then, generates a module file that imports all these files, and exports an object with translations indexed
     * by domain (simple +intl-icu) then by locale.
     * Ex:
     * import messages_en from './translations/messages.en.js';
     * import messages_fr from './translations/messages.fr.js';
     * import messages_intl_icu_en from './translations/messages+intl-icu.en.js';
     * import messages_intl_icu_fr from './translations/messages+intl-icu.fr.js';
     * export default {
     *    'messages': {
     *       'en': messages_en,
     *       'fr': messages_fr,
     *    },
     *    'messages+intl-icu': {
     *      'en': messages_intl_icu_en,
     *      'fr': messages_intl_icu_fr,
     *    },
     * }
     */
    private function dumpDomainModule(string $domain): void
    {
        $relativeDomainFiles = $this->getTranslationsRelativePathsForDomain($domain);
        $importString = '';
        $translationsByDomain = [];
        foreach ($relativeDomainFiles as $file) {
            $matches = [];
            preg_match(self::TRANSLATIONS_FILENAME_PATTERN, $file, $matches);
            [,$fullDomain,$locale] = $matches;

            $variableName = s($fullDomain)->ascii()->snake()->append('_'.$locale)->toString();
            $importString .= \sprintf("import %s from '%s';\n", $variableName, $file);

            $translationsByDomain[$fullDomain] ??= '';
            $translationsByDomain[$fullDomain] .= \sprintf("         '%s': %s,\n", $locale, $variableName);
        }

        $dataString = '';
        foreach ($translationsByDomain as $fullDomain => $line) {
            $dataString .= \sprintf("    '%s': {\n%s    },\n", $fullDomain, $line);
        }

        $importString = rtrim($importString, "\n");
        $dataString = rtrim($dataString, "\n");

        $this->filesystem->dumpFile(
            $this->dumpDir.'/domains/'.$domain.'.js',
            \sprintf($this->getModuleTemplate(), $importString, $dataString)
        );
    }

    /**
     * @return list<string> List of relative paths to translation files for a given domain
     */
    private function getTranslationsRelativePathsForDomain(string $domain): array
    {
        $translationFiles = glob(
            $this->dumpDir.'/domains/translations/'.$domain.'{,+intl-icu}.*',
            \GLOB_NOSORT | \GLOB_BRACE
        );

        return array_map(fn ($path) => rtrim('./'.$this->filesystem->makePathRelative($path, $this->dumpDir.'/domains'), '/'), $translationFiles);
    }
}
