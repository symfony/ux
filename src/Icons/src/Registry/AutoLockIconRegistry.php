<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Registry;

use Psr\Log\LoggerInterface;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\IconRegistryInterface;

/**
 * Persists "on demand" icons to the local icon directory as they are first rendered.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class AutoLockIconRegistry implements IconRegistryInterface
{
    public function __construct(
        private readonly IconRegistryInterface $inner,
        private readonly LocalSvgIconRegistry $localRegistry,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function get(string $name): Icon
    {
        $icon = $this->inner->get($name);

        try {
            $this->localRegistry->add(str_replace(':', '/', $name), $icon->toHtml());
        } catch (\Throwable $e) {
            $this->logger?->warning('Could not auto-lock icon "{name}".', ['name' => $name, 'exception' => $e]);
        }

        return $icon;
    }
}
