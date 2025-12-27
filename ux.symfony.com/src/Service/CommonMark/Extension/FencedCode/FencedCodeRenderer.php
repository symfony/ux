<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\FencedCode;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\UX\TwigComponent\ComponentRendererInterface;

final readonly class FencedCodeRenderer implements NodeRendererInterface
{
    public function __construct(
        private ComponentRendererInterface $componentRenderer,
    ) {
    }

    #[\Override]
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable|string|null
    {
        if (!$node instanceof FencedCode) {
            throw new \InvalidArgumentException('Block must be instance of '.FencedCode::class);
        }

        $code = $node->getLiteral();

        $infoWords = $node->getInfoWords();
        $language = $infoWords[0] ?? 'txt';

        $options = isset($infoWords[1]) && json_validate($infoWords[1]) ? json_decode($infoWords[1], true) : [];

        return $this->componentRenderer->createAndRender('CodeBlockInline', [
            'code' => $code,
            'language' => $language,
            'filename' => $options['filename'] ?? null,
        ]);
    }
}
