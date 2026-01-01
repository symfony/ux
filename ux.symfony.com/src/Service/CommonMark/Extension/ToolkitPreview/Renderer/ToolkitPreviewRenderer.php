<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\ToolkitPreview\Renderer;

use App\Service\CommonMark\Extension\ToolkitPreview\Node\ToolkitPreview;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class ToolkitPreviewRenderer implements NodeRendererInterface
{
    public function __construct(
        private UriSigner $uriSigner,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof ToolkitPreview) {
            throw new \InvalidArgumentException(\sprintf('Expected instance of "%s", got "%s"', ToolkitPreview::class, $node::class));
        }

        $options = $node->getOptions();
        $height = $options['height'] ?? '150px';

        $previewUrl = $this->uriSigner->sign(
            $this->urlGenerator->generate(
                'app_toolkit_component_preview',
                ['kitId' => $node->getKitId()->value, 'code' => $node->getLiteral(), 'height' => $height],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );

        return <<<HTML
            <div class="Toolkit_Loader" style="height: {$height};">
                <svg width="18" height="18" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <span>Loading...</span>
            </div>
            <iframe class="Toolkit_Preview loading" src="{$previewUrl}" style="height: {$height};" loading="lazy" onload="this.previousElementSibling.style.display = 'none'; this.classList.remove('loading')"></iframe>
            HTML;
    }
}
