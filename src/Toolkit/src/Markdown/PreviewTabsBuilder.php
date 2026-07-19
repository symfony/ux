<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\Node\CodePreview;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Node\Tab;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Node\Tabs;

/**
 * Builds the `Tabs(Preview: CodePreview, Code: FencedCode)` composition for a `{"preview": true}`
 * fenced-code block. The code is put into the nodes verbatim, so it is never re-parsed as markdown.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class PreviewTabsBuilder
{
    public static function build(string $code, CodeOptions $options = new CodeOptions(), string $language = 'twig'): Tabs
    {
        $tabs = new Tabs();

        $previewTab = new Tab('Preview');
        $previewTab->appendChild(new CodePreview($code, $options));
        $tabs->appendChild($previewTab);

        $codeTab = new Tab('Code');
        $fencedCode = new FencedCode(3, '`', 0);
        $codeTabInfoJson = $options->toCodeTabInfoJson();
        $fencedCode->setInfo(null === $codeTabInfoJson ? $language : $language.' '.$codeTabInfoJson);
        $fencedCode->setLiteral($code);
        $codeTab->appendChild($fencedCode);
        $tabs->appendChild($codeTab);

        return $tabs;
    }
}
