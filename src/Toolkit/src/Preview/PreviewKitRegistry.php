<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Preview;

use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitFactory;

/**
 * The kits the "ux_toolkit.preview.kits" option previews, by name, loaded on first use.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PreviewKitRegistry
{
    /**
     * @var array<string, Kit>
     */
    private array $kits = [];

    /**
     * @param array<string, string> $kitDirs kit name => absolute path of the kit directory
     */
    public function __construct(
        private readonly KitFactory $kitFactory,
        private readonly array $kitDirs,
    ) {
    }

    /**
     * @return array<string, Kit>
     */
    public function getKits(): array
    {
        $kits = [];
        foreach (array_keys($this->kitDirs) as $kitName) {
            $kits[$kitName] = $this->getKit($kitName);
        }

        return $kits;
    }

    public function getKit(string $kitName): ?Kit
    {
        if (!isset($this->kitDirs[$kitName])) {
            return null;
        }

        return $this->kits[$kitName] ??= $this->kitFactory->createKitFromAbsolutePath($this->kitDirs[$kitName]);
    }
}
