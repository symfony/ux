<?php

declare(strict_types=1);


namespace src\Core;

use src\Enum\TurboAction;

readonly class TurboStream
{
    public function __construct(
        public string $target,
        public TurboAction $action,
        public string $view,
        public array $parameters,
    ) {
    }
}
