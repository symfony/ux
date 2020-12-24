<?php
declare(strict_types=1);

namespace Symfony\UX\Turbo\Twig;

/**
 * @internal
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
trait Utils
{
    /**
     * Compatibility with the Broadcast mode (the "\" character isn't allowed in HTML IDs).
     */
    private function escapeId(string $id): string
    {
        return str_replace('\\', ':', $id);
    }
}