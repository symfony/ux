<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Twig;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\UX\Editor\Bridge\Format\Block\BlockRendererRegistry;
use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Content\EditorContentInterface;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Content\PageContent;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class EditorRenderExtension extends AbstractExtension
{
    public function __construct(
        private readonly BlockRendererRegistry $blockRenderers,
        private readonly ?HtmlSanitizerInterface $sanitizer = null,
        private readonly bool $debug = false,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('ux_editor_render', $this->render(...), ['is_safe' => ['html']])];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function render(?EditorContentInterface $content, array $options = []): string
    {
        if (null === $content || $content->isEmpty()) {
            return '';
        }

        return match (true) {
            $content instanceof HtmlContent => $this->renderHtml($content),
            $content instanceof BlockContent => $this->renderBlocks($content),
            $content instanceof PageContent => $this->renderPage($content, $options),
            default => '',
        };
    }

    private function renderHtml(HtmlContent $c): string
    {
        return $c->getSanitized($this->sanitizer);
    }

    private function renderBlocks(BlockContent $c): string
    {
        $out = '';
        foreach ($c->blocks as $block) {
            $type = (string) ($block['type'] ?? '');
            $r = $this->blockRenderers->get($type);
            if (null === $r) {
                $out .= $this->debug
                    ? sprintf('<div style="border:1px dashed red;padding:.5em;background:#fee">Missing block renderer for type "%s"</div>', htmlspecialchars($type))
                    : sprintf('<!-- ux-editor: missing renderer for "%s" -->', $type);
                continue;
            }
            $out .= $r->render($block['data'] ?? [], $block);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function renderPage(PageContent $c, array $options): string
    {
        $srcdoc = sprintf(
            '<!doctype html><html><head><style>%s</style></head><body>%s</body></html>',
            $c->css,
            $c->html,
        );

        return sprintf(
            '<iframe sandbox="allow-same-origin" srcdoc="%s"></iframe>',
            htmlspecialchars($srcdoc, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'),
        );
    }
}
