<?php

declare(strict_types=1);

namespace src\Trait;

use src\Core\TurboStream;
use src\Core\TurboStreamCollection;
use src\Enum\TurboAction;
use TurboStreamRendererInterface;

// This needs Symfony\Component\HttpFoundation\Response so it won't work as part of the turbo bundle, unless there is another way to keep it decoupled.
// An alternative would be to have this as a separate bundle.


trait TurboRenderTrait
{
    abstract protected function getTurboStreamRenderer(): TurboStreamRendererInterface;

    protected function renderAsStreamView(string $target, TurboAction $action, $view, array $parameters = []): string
    {
        $streams = (new TurboStreamCollection())->add($target, $action, $view, $parameters);

        return $this->renderTurboStreamsView($streams->all());
    }

    /**
     * @param TurboStream[] $streams
     */
    protected function renderTurboStreamsView(array $streams): string
    {
        return $this->getTurboStreamRenderer()->renderTurboStreamsView($streams);
    }

    private function wrapWithTurboStream(string $target, TurboAction $action, string $content): string
    {
        return <<<HTML
        <turbo-stream action="$action->value" target="$target">
            <template>
                $content
            </template>
        </turbo-stream>
        HTML;
    }
}
