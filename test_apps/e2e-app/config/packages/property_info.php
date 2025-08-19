<?php

use App\Kernel;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config) {
    if (Kernel::VERSION_ID >= 70000) {
        $config->propertyInfo()->withConstructorExtractor(true);
    }
};
