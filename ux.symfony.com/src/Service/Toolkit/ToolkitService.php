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
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Toolkit\Asset\Component;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Registry\RegistryFactory;
use function Symfony\Component\String\s;

class ToolkitService
{
    public function __construct(
        #[Autowire(service: 'ux_toolkit.registry.registry_factory')]
        private readonly RegistryFactory $registryFactory,
        private readonly UriSigner $uriSigner,
        private readonly UrlGeneratorInterface $urlGenerator
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

    public function renderComponentPreviewCodeTabs(ToolkitKitId $kitId, string $code, string $highlightedCode, string $height): string
    {
        $previewUrl = $this->urlGenerator->generate('app_toolkit_component_preview', ['kitId' => $kitId->value, 'code' => $code, 'height' => $height], UrlGeneratorInterface::ABSOLUTE_URL);
        $previewUrl = $this->uriSigner->sign($previewUrl);

        return self::generateTabs([
            'Preview' => <<<HTML
                <div class="Toolkit_Loader" style="height: {$height};">
                    <svg width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    <span>Loading...</span>
                </div>
                <iframe class="Toolkit_Preview loading" src="{$previewUrl}" style="height: {$height};" loading="lazy" onload="this.previousElementSibling.style.display = 'none'; this.classList.remove('loading')"></iframe>
            HTML,
            'Code' => $highlightedCode
        ]);
    }


    /**
     * @param non-empty-array<string, string> $tabs
     */
    private static function generateTabs(array $tabs): string
    {
        $activeTabId = null;
        $tabsControls = '';
        $tabsPanels = '';

        foreach ($tabs as $tabText => $tabContent) {
            $tabId = hash('xxh3', $tabText);
            $activeTabId ??= $tabId;
            $isActive = $activeTabId === $tabId;

            $tabsControls .= sprintf('<button class="Toolkit_TabControl" data-action="tabs#show" data-tabs-target="control" data-tabs-tab-param="%s" role="tab" aria-selected="%s">%s</button>', $tabId, $isActive ? 'true' : 'false', trim($tabText));
            $tabsPanels .= sprintf('<div class="Toolkit_TabPanel %s" data-tabs-target="tab" data-tab="%s" role="tabpanel">%s</div>', $isActive ? 'active' : '', $tabId, $tabContent);
        }

        return <<<HTML
<div class="Toolkit_Tabs" data-controller="tabs" data-tabs-tab-value="{$activeTabId}" data-tabs-active-class="active">
    <nav class="Toolkit_TabHead" role="tablist" style="border-bottom: 1px solid var(--bs-border-color)">{$tabsControls}</nav>
    <div class="Toolkit_TabBody">{$tabsPanels}</div>
</div>
HTML;
    }
}
