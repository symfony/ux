<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CodeRenderer implements NodeRendererInterface
{
    /**
     * @param FencedCode $node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string|\Stringable|null
    {
        $codeAttr = ['data-code-highlighter-target' => 'codeBlock'];

        if ($lang = $node->getInfo()) {
            $codeAttr['class'] = 'language-'.$lang;
        }

        return new HtmlElement(
            'div',
            ['class' => 'Terminal_body mb-3'],
            new HtmlElement(
                'div',
                ['class' => 'Terminal_content'],
                new HtmlElement(
                    'pre',
                    ['data-controller' => 'code-highlighter'],
                    new HtmlElement('code', $codeAttr, Xml::escape($node->getLiteral()))
                )
            )
        );
    }
}
