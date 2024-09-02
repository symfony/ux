<?php

declare(strict_types=1);

namespace src\Core;

use src\Enum\TurboAction;
use TurboStreamRendererInterface;

class TurboStreamRenderer implements TurboStreamRendererInterface
{
    /**
     * This is a hacky way to reuse the code of renderView form AbstractController - just pass the method as a callable to the renderer constructor.
     * @var callable $renderer
     */
    protected $renderer = null;

    public function __construct(callable $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @throws \InvalidRendererException
     */
    public function renderView(string $view, array $parameters = []): string
    {
        try {
            $renderer = $this->renderer;
            return $renderer($view, $parameters);
        }catch (\Throwable $e) {
            throw new \InvalidRendererException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function renderAsStreamView(string $target, TurboAction $action, string $view, array $parameters = []): string
    {
        $streams = (new TurboStreamCollection())->add($target, $action, $view, $parameters);

        return $this->renderTurboStreamsView($streams->all());
    }

    /**
     * @throws \InvalidRendererException
     */
    public function renderTurboStreamsView(array $streams): string
    {
        $response = "";
        foreach ($streams as $stream) {
            $content = $this->renderView($stream['view'], $stream['parameters']);
            $response .= $this->wrapWithTurboStream($stream['target'], $stream['action'], $content);
            $response .= "\n";
        }

        return $response;
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
