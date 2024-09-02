<?php

declare(strict_types=1);

use src\Core\TurboStream;
use src\Enum\TurboAction;

interface TurboStreamRendererInterface
{
    function renderAsStreamView(string $target, TurboAction $action, string$view, array $parameters = []): string;

    /**
     * @param TurboStream[] $streams
     */
    function renderTurboStreamsView(array $streams): string;
}
