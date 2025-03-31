<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Toolkit;

use App\Enum\ToolkitKit;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\Toolkit\Component\Component;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

class ToolkitService
{
    public function __construct(
        #[Autowire(service: 'ux_toolkit.registry.factory')]
        private RegistryFactory $registryFactory,
    ) {
    }

    /**
     * @return Component[]
     */
    public function getDocumentableComponents(ToolkitKit $kit): array
    {
        return array_filter($this->getKit($kit)->getComponents(), fn (Component $component) => $component->doc);
    }

    public function getComponent(ToolkitKit $kit, string $component): ?Component
    {
        return $this->getKit($kit)->getComponent($component);
    }

    public function getKit(ToolkitKit $kit): Kit
    {
        return $this->getKits()[$kit->value] ?? throw new \InvalidArgumentException(\sprintf('Kit "%s" not found', $kit->value));
    }

    /**
     * @return array<ToolkitKit,Kit>
     */
    public function getKits(): array
    {
        static $kits = null;

        if (null === $kits) {
            $kits = [];
            foreach (ToolkitKit::cases() as $kit) {
                $kits[$kit->value] = $this->registryFactory->getForKit($kit->value)->getKit($kit->value);
            }
        }

        return $kits;
    }
}
