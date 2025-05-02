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

use App\Enum\ToolkitKitId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\Toolkit\Asset\Component;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

class ToolkitService
{
    public function __construct(
        #[Autowire(service: 'ux_toolkit.registry.registry_factory')]
        private RegistryFactory $registryFactory,
    ) {
    }

    public function getKit(ToolkitKitId $kit): Kit
    {
        return $this->getKits()[$kit->value] ?? throw new \InvalidArgumentException(\sprintf('Kit "%s" not found', $kit->value));
    }

    /**
     * @return array<ToolkitKitId,Kit>
     */
    public function getKits(): array
    {
        static $kits = null;

        if (null === $kits) {
            $kits = [];
            foreach (ToolkitKitId::cases() as $kit) {
                $kits[$kit->value] = $this->registryFactory->getForKit($kit->value)->getKit($kit->value);
            }
        }

        return $kits;
    }

    /**
     * @return Component[]
     */
    public function getDocumentableComponents(Kit $kit): array
    {
        return array_filter($kit->getComponents(), fn (Component $component) => $component->doc);
    }
}
