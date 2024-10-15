<?php

declare(strict_types=1);


namespace src\Core;

use src\Enum\TurboAction;

class TurboStreamCollection
{

    /**
     * @param TurboStream[] $streams
     */
    public function __construct(private array $streams = [])
    {
    }

    public function add(string $target, TurboAction $action, $view, array $parameters = [])
    {
        return $this->addStream(new TurboStream($target, $action, $view, $parameters));
    }

    public function addStream(TurboStream $stream): self
    {
        $this->streams[] = $stream;

        return $this;
    }

    /**
     * @return TurboStream[]
     */
    public function all(): array
    {
        return $this->streams;
    }
}
