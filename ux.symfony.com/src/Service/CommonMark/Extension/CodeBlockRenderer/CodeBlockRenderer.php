<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\CodeBlockRenderer;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tempest\Highlight\Highlighter;
use Tempest\Highlight\WebTheme;

final readonly class CodeBlockRenderer implements NodeRendererInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UriSigner $uriSigner,
    ) {
    }

    #[\Override]
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string|null
    {
        if (!$node instanceof FencedCode) {
            throw new \InvalidArgumentException('Block must be instance of '.FencedCode::class);
        }

        $infoWords = $node->getInfoWords();
        $language = $infoWords[0] ?? 'txt';
        $options = isset($infoWords[1]) && json_validate($infoWords[1]) ? json_decode($infoWords[1], true) : [];
        $preview = $options['preview'] ?? false;
        $kit = $options['kit'] ?? null;
        $height = $options['height'] ?? '150px';

        $code = $node->getLiteral();

        $output = $this->highlightCode($code, $language);

        if ($preview && $kit) {
            $previewUrl = $this->uriSigner->sign($this->urlGenerator->generate('app_toolkit_component_preview', [
                'toolkitKit' => $kit,
                'code' => $code,
                'height' => $height,
            ], UrlGeneratorInterface::ABSOLUTE_URL));

            $output = <<<HTML
<div class="CodePreview_Tabs" data-controller="tabs" data-tabs-tab-value="preview" data-tabs-active-class="active">
    <nav class="CodePreview_TabHead" role="tablist" style="border-bottom: 1px solid var(--bs-border-color)">
        <button class="CodePreview_TabControl" data-action="tabs#show" data-tabs-target="control" data-tabs-tab-param="preview" role="tab" aria-selected="true">Preview</button>
        <button class="CodePreview_TabControl" data-action="tabs#show" data-tabs-target="control" data-tabs-tab-param="code" role="tab" aria-selected="false">Code</button>
    </nav>
    <div class="CodePreview_TabBody">
        <div class="CodePreview_TabPanel active" data-tabs-target="tab" data-tab="preview" role="tabpanel">
            <div class="CodePreview_Loader" style="height: {$height};">
                <svg width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <span>Loading...</span>
            </div>
            <iframe class="CodePreview_Preview loading" src="{$previewUrl}" style="height: {$height};" loading="lazy" onload="this.previousElementSibling.style.display = 'none'; this.classList.remove('loading')"></iframe>
        </div>
        <div class="CodePreview_TabPanel" data-tabs-target="tab" data-tab="code" role="tabpanel">{$output}</div>
    </div>
</div>
HTML;
        }

        return $output;
    }

    private function highlightCode(string $code, string $language): string
    {
        $highlighter = new Highlighter();

        $theme = $highlighter->getTheme();
        $parsed = $highlighter->parse($code, $language);
        $output = $theme instanceof WebTheme
            ? $theme->preBefore($highlighter).$parsed.$theme->preAfter($highlighter)
            : '<pre data-lang="'.$language.'" class="notranslate">'.$parsed.'</pre>';

        return <<<HTML
            <div class="Terminal terminal-code" style="margin-bottom: 1rem;">
                <div class="Terminal_body">
                    <div class="Terminal_content" style="max-height: 450px;">
                        {$output}
                    </div>
                </div>
            </div>
        HTML;
    }
}
