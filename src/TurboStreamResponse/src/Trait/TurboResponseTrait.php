<?php

declare(strict_types=1);

namespace src\Trait;

use src\Core\TurboStream;
use src\Enum\TurboAction;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Turbo\TurboBundle;

// This needs Symfony\Component\HttpFoundation\Response so it won't work as part of the turbo bundle, unless there is another way to keep it decoupled.
// An alternative would be to have this as a separate bundle.


trait TurboResponseTrait
{

    use TurboRenderTrait;

    protected function renderAsStream(string $target, TurboAction $action, $view, array $parameters = []): Response
    {
       return $this->renderTurboStreams([new TurboStream($target, $action, $view, $parameters)]);
    }

    /**
     * @param TurboStream[] $streams
     */
    protected function renderTurboStreams(array $streams): Response
    {
        $content = $this->renderTurboStreamsView($streams);
        $response ??= new Response();

        // I would like to keep this part of the standard AbstractController, but I don't really like the idea of duplication the code.
        // Any suggestions for a way to reuse it?

        foreach ($streams as $stream) {
            if (200 === $response->getStatusCode()) {
                $parameters = $stream->getParameters();
                foreach ($parameters as $v) {
                    if ($v instanceof FormInterface && $v->isSubmitted() && !$v->isValid()) {
                        $response->setStatusCode(422);
                        break 2;
                    }
                }
            }
        }

        $response->setContent($content);
        $response->headers->set('Content-Type', TurboBundle::STREAM_MEDIA_TYPE);

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
