<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\CodePreview;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\Node\CodePreview;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\Renderer\CodePreviewRenderer;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;

/**
 * Renders {@see CodePreview} nodes. Pass a {@see PreviewUrlGenerator} to enable live previews;
 * without one, previews degrade to a static, escaped code block.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class CodePreviewExtension implements ExtensionInterface
{
    public function __construct(
        private \Twig\Environment $twig,
        private ?PreviewUrlGenerator $urlGenerator = null,
    ) {
        Assert::commonMarkAvailable();
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(CodePreview::class, new CodePreviewRenderer($this->twig, $this->urlGenerator));
    }
}
