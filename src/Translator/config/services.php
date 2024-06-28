<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Translator\CacheWarmer\TranslationsCacheWarmer;
use Symfony\UX\Translator\Dumper\Front\DomainModuleDumper;
use Symfony\UX\Translator\Dumper\Front\TranslationConfigDumper;
use Symfony\UX\Translator\Dumper\Front\MessageConstantDumper;
use Symfony\UX\Translator\MessageParameters\Extractor\IntlMessageParametersExtractor;
use Symfony\UX\Translator\MessageParameters\Extractor\MessageParametersExtractor;
use Symfony\UX\Translator\MessageParameters\Printer\TypeScriptMessageParametersPrinter;
use Symfony\UX\Translator\TranslationsDumper;

/*
 * @author Hugo Alliaume <hugo@alliau.me>
 */
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('ux.translator.cache_warmer.translations_cache_warmer', TranslationsCacheWarmer::class)
            ->args([
                service('translator'),
                service('ux.translator.translations_dumper'),
            ])
            ->tag('kernel.cache_warmer')

        ->set('ux.translator.translations_dumper', TranslationsDumper::class)
            ->args([
                abstract_arg('Dump directory'),
            ])
        ->set('ux.translator.translations_dumper.message_constant', MessageConstantDumper::class)
            ->args([
                service('ux.translator.message_parameters.extractor.message_parameters_extractor'),
                service('ux.translator.message_parameters.extractor.intl_message_parameters_extractor'),
                service('ux.translator.message_parameters.printer.typescript_message_parameters_printer'),
                service('filesystem'),
            ])
            ->tag('ux.translator.front_translations_dumper')
        ->set('ux.translator.translations_dumper.domain_module', DomainModuleDumper::class)
            ->args([
                service('filesystem'),
            ])
            ->tag('ux.translator.front_translations_dumper')
        ->set('ux.translator.translations_dumper.configuration', TranslationConfigDumper::class)
            ->args([
                service('filesystem'),
            ])
            ->tag('ux.translator.front_translations_dumper')

        ->set('ux.translator.message_parameters.extractor.message_parameters_extractor', MessageParametersExtractor::class)

        ->set('ux.translator.message_parameters.extractor.intl_message_parameters_extractor', IntlMessageParametersExtractor::class)

        ->set('ux.translator.message_parameters.printer.typescript_message_parameters_printer', TypeScriptMessageParametersPrinter::class)
    ;
};
