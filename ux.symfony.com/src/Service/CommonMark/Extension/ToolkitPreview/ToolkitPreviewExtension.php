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

namespace App\Service\CommonMark\Extension\ToolkitPreview;

use App\Service\CommonMark\Extension\ToolkitPreview\Node\ToolkitPreview;
use App\Service\CommonMark\Extension\ToolkitPreview\Parser\ToolkitPreviewParser;
use App\Service\CommonMark\Extension\ToolkitPreview\Renderer\ToolkitPreviewRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class ToolkitPreviewExtension implements ExtensionInterface
{
    public function __construct(
        private UriSigner $uriSigner,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(ToolkitPreviewParser::createBlockStartParser(), 100)
            ->addRenderer(ToolkitPreview::class, new ToolkitPreviewRenderer($this->uriSigner, $this->urlGenerator));
    }
}
