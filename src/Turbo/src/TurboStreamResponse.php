<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo;

use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Turbo\Helper\TurboStream;

class TurboStreamResponse extends Response
{
    public function __construct(?string $content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);

        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', TurboBundle::STREAM_MEDIA_TYPE);
        }
    }

    /**
     * @return $this
     */
    public function append(string $target, string $html): static
    {
        $this->setContent($this->getContent().TurboStream::append($target, $html));

        return $this;
    }

    /**
     * @return $this
     */
    public function prepend(string $target, string $html): static
    {
        $this->setContent($this->getContent().TurboStream::prepend($target, $html));

        return $this;
    }

    /**
     * @return $this
     */
    public function replace(string $target, string $html, bool $morph = false): static
    {
        $this->setContent($this->getContent().TurboStream::replace($target, $html, $morph));

        return $this;
    }

    /**
     * @return $this
     */
    public function update(string $target, string $html, bool $morph = false): static
    {
        $this->setContent($this->getContent().TurboStream::update($target, $html, $morph));

        return $this;
    }

    /**
     * @return $this
     */
    public function remove(string $target): static
    {
        $this->setContent($this->getContent().TurboStream::remove($target));

        return $this;
    }

    /**
     * @return $this
     */
    public function before(string $target, string $html): static
    {
        $this->setContent($this->getContent().TurboStream::before($target, $html));

        return $this;
    }

    /**
     * @return $this
     */
    public function after(string $target, string $html): static
    {
        $this->setContent($this->getContent().TurboStream::after($target, $html));

        return $this;
    }

    /**
     * @return $this
     */
    public function refresh(?string $requestId = null): static
    {
        $this->setContent($this->getContent().TurboStream::refresh($requestId));

        return $this;
    }

    /**
     * Custom action and attributes.
     *
     * Set boolean attributes (e.g., `disabled`) by providing the attribute name as key with `null` as value.
     *
     * @param array<string, string|int|float|null> $attr
     *
     * @return $this
     */
    public function action(string $action, string $target, string $html, array $attr = []): static
    {
        $this->setContent($this->getContent().TurboStream::action($action, $target, $html, $attr));

        return $this;
    }
}
