<?php

namespace App\Service\CommonMark;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\ExtensionInterface;
use Tempest\Highlight\CommonMark\CodeBlockRenderer;
use Tempest\Highlight\CommonMark\InlineCodeBlockRenderer;
use Tempest\Highlight\Highlighter;

/**
 * TODO: remove this class when/if https://github.com/tempestphp/highlight/pull/102 is merged.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TempestHighlightExtension implements ExtensionInterface
{
    public function __construct(private Highlighter $highlighter)
    {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addRenderer(FencedCode::class, new CodeBlockRenderer($this->highlighter), 10)
            ->addRenderer(Code::class, new InlineCodeBlockRenderer($this->highlighter), 10)
        ;
    }
}
