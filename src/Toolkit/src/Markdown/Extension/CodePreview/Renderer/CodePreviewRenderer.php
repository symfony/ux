<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\CodePreview\Renderer;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\Node\CodePreview;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;

final class CodePreviewRenderer implements NodeRendererInterface
{
    public function __construct(
        private \Twig\Environment $twig,
        private ?PreviewUrlGenerator $urlGenerator = null,
    ) {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof CodePreview) {
            throw new \InvalidArgumentException(\sprintf('Expected instance of "%s", got "%s"', CodePreview::class, $node::class));
        }

        $options = $node->getOptions();

        return $this->twig->render('@UXToolkit/markdown/code_preview.html.twig', [
            'previewUrl' => $this->urlGenerator?->generate($node->getCode(), $options),
            'code' => $node->getCode(),
        ]);
    }
}
