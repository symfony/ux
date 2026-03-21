<?php

use App\Kernel;

if (Kernel::VERSION_ID >= 80000) {
    return Symfony\Component\DependencyInjection\Loader\Configurator\App::config([
        'framework' => [
            'property_info' => [
                'with_constructor_extractor' => true,
            ]
        ]
    ]);
} else if (Kernel::VERSION_ID >= 70000) {
    return static function (Symfony\Config\FrameworkConfig $config) {
        $config->propertyInfo()->withConstructorExtractor(true);
    };
}
