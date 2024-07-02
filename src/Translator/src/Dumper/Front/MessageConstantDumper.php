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
use Symfony\UX\Translator\MessageParameters\Extractor\IntlMessageParametersExtractor;
use Symfony\UX\Translator\MessageParameters\Extractor\MessageParametersExtractor;
use Symfony\UX\Translator\MessageParameters\Printer\TypeScriptMessageParametersPrinter;

use function Symfony\Component\String\s;

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
class MessageConstantDumper extends AbstractFrontFileDumper implements FrontFileDumperInterface
{

    public function __construct(
        private MessageParametersExtractor $messageParametersExtractor,
        private IntlMessageParametersExtractor $intlMessageParametersExtractor,
        private TypeScriptMessageParametersPrinter $typeScriptMessageParametersPrinter,
        private Filesystem $filesystem,
    ) {
    }

    public function dump(MessageCatalogueInterface ...$catalogues): void
    {
        $this->filesystem->mkdir($this->dumpDir);
        $this->filesystem->remove($this->dumpDir.'/index.js');
        $this->filesystem->remove($this->dumpDir.'/index.d.ts');

        $translationsJs = '';
        $translationsTs = "import { Message, NoParametersType } from '@symfony/ux-translator';\n\n";

        foreach ($this->getTranslations(...$catalogues) as $translationId => $translationsByDomainAndLocale) {
            $constantName = $this->generateConstantName($translationId);

            $translationsJs .= \sprintf(
                "export const %s = %s;\n",
                $constantName,
                json_encode([
                    'id' => $translationId,
                    'translations' => $translationsByDomainAndLocale,
                ], \JSON_THROW_ON_ERROR),
            );
            $translationsTs .= \sprintf(
                "export declare const %s: %s;\n",
                $constantName,
                $this->getTranslationsTypeScriptTypeDefinition($translationsByDomainAndLocale)
            );
        }

        $this->filesystem->dumpFile($this->dumpDir.'/index.js', $translationsJs);
        $this->filesystem->dumpFile($this->dumpDir.'/index.d.ts', $translationsTs);
    }

    /**
     * @return array<MessageId, array<Domain, array<Locale, string>>>
     */
    private function getTranslations(MessageCatalogueInterface ...$catalogues): array
    {
        $translations = [];

        foreach ($catalogues as $catalogue) {
            $locale = $catalogue->getLocale();
            foreach ($catalogue->getDomains() as $domain) {
                foreach ($catalogue->all($domain) as $id => $message) {
                    $realDomain = $catalogue->has($id, $domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX)
                        ? $domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX
                        : $domain;

                    $translations[$id] ??= [];
                    $translations[$id][$realDomain] ??= [];
                    $translations[$id][$realDomain][$locale] = $message;
                }
            }
        }

        return $translations;
    }

    /**
     * @param array<Domain, array<Locale, string>> $translationsByDomainAndLocale
     *
     * @throws \Exception
     */
    private function getTranslationsTypeScriptTypeDefinition(array $translationsByDomainAndLocale): string
    {
        $parametersTypes = [];
        $locales = [];

        foreach ($translationsByDomainAndLocale as $domain => $translationsByLocale) {
            foreach ($translationsByLocale as $locale => $translation) {
                try {
                    $parameters = str_ends_with($domain, MessageCatalogueInterface::INTL_DOMAIN_SUFFIX)
                        ? $this->intlMessageParametersExtractor->extract($translation)
                        : $this->messageParametersExtractor->extract($translation);
                } catch (\Throwable $e) {
                    throw new \Exception(\sprintf('Error while extracting parameters from message "%s" in domain "%s" and locale "%s".', $translation, $domain, $locale), previous: $e);
                }

                $parametersTypes[$domain] = $this->typeScriptMessageParametersPrinter->print($parameters);

                $locales[] = $locale;
            }
        }

        return \sprintf(
            'Message<{ %s }, %s>',
            implode(', ', array_reduce(
                array_keys($parametersTypes),
                fn (array $carry, string $domain) => [
                    ...$carry,
                    \sprintf("'%s': { parameters: %s }", $domain, $parametersTypes[$domain]),
                ],
                [],
            )),
            implode('|', array_map(fn (string $locale) => "'$locale'", array_unique($locales))),
        );
    }

    private function generateConstantName(string $translationId): string
    {
        static $alreadyGenerated = [];

        $prefix = 0;
        do {
            $constantName = s($translationId)->ascii()->snake()->upper()->replaceMatches('/^(\d)/', '_$1')->toString().($prefix > 0 ? '_'.$prefix : '');
            ++$prefix;
        } while (\in_array($constantName, $alreadyGenerated, true));

        $alreadyGenerated[] = $constantName;

        return $constantName;
    }
}
