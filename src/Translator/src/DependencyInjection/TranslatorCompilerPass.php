<?php

namespace Symfony\UX\Translator\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Translation\TranslatorBagInterface;

class TranslatorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$this->hasValidTranslator($container)) {
            $container->removeDefinition('ux.translator.cache_warmer.translations_cache_warmer');
        }
    }

    private function hasValidTranslator(ContainerBuilder $containerBuilder): bool
    {
        if (!$containerBuilder->hasDefinition('translator')) {
            return false;
        }

        $translator = $containerBuilder->getDefinition('translator');
        if (!is_subclass_of($translator->getClass(), TranslatorBagInterface::class)) {
            return false;
        }
        return true;
    }
}