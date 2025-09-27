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

use App\Enum\ToolkitKitId;
use App\Service\Toolkit\ToolkitService;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Tempest\Highlight\Highlighter;
use Tempest\Highlight\Injection;
use Tempest\Highlight\Language;
use Tempest\Highlight\Languages\Base\Injections\DeletionInjection;
use Tempest\Highlight\Languages\Twig\TwigLanguage;
use Tempest\Highlight\WebTheme;

final readonly class CodeBlockRenderer implements NodeRendererInterface
{
    public function __construct(
        private ToolkitService $toolkitService,
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
        $kitId = ToolkitKitId::tryFrom($options['kit'] ?? null);
        $preview = $options['preview'] ?? false;

        $output = $this->highlightCode($language, $code = $node->getLiteral());

        if ($kitId && $preview) {
            $output = $this->toolkitService->renderComponentPreviewCodeTabs($kitId, $code, $output, $options['height'] ?? '150px');
        }

        return $output;
    }

    public static function highlightCode(string $language, string $code, string $style = 'margin-bottom: 1rem'): string
    {
        $highlighter = new Highlighter();

        // https://github.com/tempestphp/highlight/issues/182
        if ('twig' === $language) {
            $language = new class extends TwigLanguage {
                public function getInjections(): array
                {
                    return array_filter(parent::getInjections(), static function (Injection $injection) {
                        return !$injection instanceof DeletionInjection;
                    });
                }
            };
        }

        $theme = $highlighter->getTheme();
        $parsed = $highlighter->parse($code, $language);
        $output = $theme instanceof WebTheme
            ? $theme->preBefore($highlighter).$parsed.$theme->preAfter($highlighter)
            : '<pre data-lang="'.($language instanceof Language ? $language->getName() : $language).'" class="notranslate">'.$parsed.'</pre>';

        return <<<HTML
            <div class="Terminal terminal-code" style="$style">
                <div class="Terminal_body">
                    <div class="Terminal_content" style="max-height: 450px;">{$output}</div>
                </div>
            </div>
        HTML;
    }
}
