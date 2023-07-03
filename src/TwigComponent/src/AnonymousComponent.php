<?php

namespace Symfony\UX\TwigComponent;

class AnonymousComponent
{
    private array $props = [];

    public function setProps(array $props)
    {
        $this->props = $props;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function mount(array $props = [])
    {
        $this->setProps($props);
    }
}
