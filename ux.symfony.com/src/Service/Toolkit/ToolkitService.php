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
use App\Service\CommonMark\Extension\CodeBlockRenderer\CodeBlockRenderer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Toolkit\Installer\PoolResolver;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeType;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

class ToolkitService
{
    public function __construct(
        #[Autowire(service: 'ux_toolkit.registry.registry_factory')]
        private readonly RegistryFactory $registryFactory,
        private readonly UriSigner $uriSigner,
        private readonly UrlGeneratorInterface $urlGenerator,
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
     * @return Recipe[]
     */
    public function getDocumentableComponents(Kit $kit): array
    {
        return array_filter($kit->getRecipes(RecipeType::Component), fn (Recipe $recipe) => file_exists(Path::join($recipe->absolutePath, 'EXAMPLES.md')));
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
            'Code' => $highlightedCode,
        ]);
    }

    public function renderInstallationSteps(ToolkitKitId $kitId, Recipe $component): string
    {
        $kit = $this->getKit($kitId);
        $pool = (new PoolResolver())->resolveForRecipe($kit, $component);

        $manual = '<p>The UX Toolkit is not mandatory to install a component. You can install it manually by following the next steps:</p>';
        $manual .= '<ol style="display: grid; gap: 1rem;">';
        $manual .= '<li><strong>Copy the following file(s) into your Symfony app:</strong>';
        foreach ($pool->getFiles() as $recipeFullPath => $files) {
            foreach ($files as $file) {
                $manual .= \sprintf(
                    "<details><summary><code>%s</code></summary>\n%s\n</details>",
                    $file->sourceRelativePathName,
                    \sprintf("\n```%s\n%s\n```", pathinfo($file->sourceRelativePathName, \PATHINFO_EXTENSION), trim(file_get_contents(Path::join($recipeFullPath, $file->sourceRelativePathName))))
                );
            }
        }
        $manual .= '</li>';

        if ($phpPackageDependencies = $pool->getPhpPackageDependencies()) {
            $manual .= '<li><strong>If necessary, install the following Composer dependencies:</strong>';
            $manual .= CodeBlockRenderer::highlightCode('shell', '$ composer require '.implode(' ', $phpPackageDependencies), 'margin-bottom: 0');
            $manual .= '</li>';
        }

        $npmPackageDependencies = $pool->getNpmPackageDependencies();
        $importmapPackageDependencies = $pool->getImportmapPackageDependencies();

        if ($npmPackageDependencies && $importmapPackageDependencies) {
            $manual .= '<li><strong>If necessary, install the following front dependencies:</strong>';
            $manual .= CodeBlockRenderer::highlightCode(
                'shell',
                '# With npm/yarn/pnpm'.\PHP_EOL
                    .'$ npm install --save '.implode(' ', $npmPackageDependencies).\PHP_EOL
                    .'# With importmap (Symfony 6.3+)'.\PHP_EOL
                    .'$ php bin/console importmap:install '.implode(' ', $importmapPackageDependencies),
                'margin-bottom: 0'
            );
            $manual .= '</li>';
        } elseif ($npmPackageDependencies) {
            $manual .= '<li><strong>If necessary, install the following npm dependencies:</strong>';
            $manual .= CodeBlockRenderer::highlightCode('shell', '$ npm install --save '.implode(' ', $npmPackageDependencies), 'margin-bottom: 0');
            $manual .= '</li>';
        } elseif ($importmapPackageDependencies) {
            $manual .= '<li><strong>If necessary, install the following importmap dependencies:</strong>';
            $manual .= CodeBlockRenderer::highlightCode('shell', '$ php bin/console importmap:install '.implode(' ', $importmapPackageDependencies), 'margin-bottom: 0');
            $manual .= '</li>';
        }

        $manual .= '<li><strong>And the most important, enjoy!</strong></li>';
        $manual .= '</ol>';

        return self::generateTabs([
            'Automatic' => \sprintf(
                '<p>Ensure the Symfony UX Toolkit is installed in your Symfony app:</p>%s<p>Then, run the following command to install the component and its dependencies:</p>%s',
                CodeBlockRenderer::highlightCode('shell', '$ composer require --dev symfony/ux-toolkit'),
                CodeBlockRenderer::highlightCode('shell', "$ bin/console ux:install {$component->manifest->name} --kit {$kitId->value}"),
            ),
            'Manual' => $manual,
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

            $tabsControls .= \sprintf('<button class="Toolkit_TabControl" data-action="tabs#show" data-tabs-target="control" data-tabs-tab-param="%s" role="tab" aria-selected="%s">%s</button>', $tabId, $isActive ? 'true' : 'false', trim($tabText));
            $tabsPanels .= \sprintf('<div class="Toolkit_TabPanel %s" data-tabs-target="tab" data-tab="%s" role="tabpanel">%s</div>', $isActive ? 'active' : '', $tabId, $tabContent);
        }

        return <<<HTML
<div class="Toolkit_Tabs" data-controller="tabs" data-tabs-tab-value="{$activeTabId}" data-tabs-active-class="active">
    <nav class="Toolkit_TabHead" role="tablist" style="border-bottom: 1px solid var(--bs-border-color)">{$tabsControls}</nav>
    <div class="Toolkit_TabBody">{$tabsPanels}</div>
</div>
HTML;
    }
}
