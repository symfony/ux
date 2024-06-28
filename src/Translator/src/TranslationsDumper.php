<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\UX\Translator\Dumper\Front\AbstractFrontFileDumper;
use Symfony\UX\Translator\Dumper\Front\FrontFileDumperInterface;
use Symfony\UX\Translator\MessageParameters\Extractor\IntlMessageParametersExtractor;
use Symfony\UX\Translator\MessageParameters\Extractor\MessageParametersExtractor;
use Symfony\UX\Translator\MessageParameters\Printer\TypeScriptMessageParametersPrinter;

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
class TranslationsDumper extends AbstractFrontFileDumper
{
    private array $dumpers = [];

    public function __construct(
        string $dumpDir,
        private ?MessageParametersExtractor $messageParametersExtractor = null,
        private ?IntlMessageParametersExtractor $intlMessageParametersExtractor = null,
        private ?TypeScriptMessageParametersPrinter $typeScriptMessageParametersPrinter = null,
        private ?Filesystem $filesystem = null,
    ) {
        $this->setDumpDir($dumpDir);
        if (isset($messageParametersExtractor, $intlMessageParametersExtractor, $typeScriptMessageParametersPrinter, $filesystem)) {
            trigger_deprecation('symfony/ux-translator', '2.19', 'The "%s" class will not require the "%s", "%s", "%s" and "%s" arguments in version 3.0.', __CLASS__, MessageParametersExtractor::class, IntlMessageParametersExtractor::class, TypeScriptMessageParametersPrinter::class, Filesystem::class);
        }
    }

    public function addDumper(FrontFileDumperInterface $dumper): void
    {
        $this->dumpers[] = $dumper;
    }

    public function dump(MessageCatalogueInterface ...$catalogues): void
    {
        foreach ($this->dumpers as $dumper) {
            $dumper->dump(...$catalogues);
        }
    }
}
