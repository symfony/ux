<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Upload;

use Symfony\UX\Editor\Exception\Upload\UploadHandlerException;

final class UploadHandlerRegistry
{
    /**
     * @var array<string, EditorUploadHandlerInterface>
     */
    private array $handlers = [];

    /**
     * @param iterable<string, EditorUploadHandlerInterface> $handlers
     */
    public function __construct(iterable $handlers = [])
    {
        foreach ($handlers as $name => $h) {
            $this->handlers[$name] = $h;
        }
    }

    public function get(string $profile): EditorUploadHandlerInterface
    {
        return $this->handlers[$profile]
            ?? throw new UploadHandlerException(\sprintf('No upload handler for profile "%s". Registered: "%s"', $profile, '' !== ($list = implode(', ', array_keys($this->handlers))) ? $list : '(none)'));
    }

    /**
     * @return array<string, EditorUploadHandlerInterface>
     */
    public function all(): array
    {
        return $this->handlers;
    }
}
