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

use App\Service\CommonMark\Extension\FencedCode\FencedCodeRenderer;
use App\Service\CommonMark\Extension\Tabs\TabsExtension;
use App\Service\CommonMark\Extension\ToolkitPreview\ToolkitPreviewExtension;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableExtension;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\ComponentRendererInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsDecorator('twig.markdown.league_common_mark_converter_factory')]
final readonly class ConverterFactory
{
    public function __construct(
        private ComponentRendererInterface $componentRenderer,
        private UriSigner $uriSigner,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): CommonMarkConverter
    {
        $converter = new CommonMarkConverter([
            'default_attributes' => [
                Table::class => [
                    'class' => 'table table-hover',
                ],
            ],
            'mentions' => [
                'github_handle' => [
                    'prefix' => '@',
                    'pattern' => '[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)',
                    'generator' => 'https://github.com/%s',
                ],
                'github_issue' => [
                    'prefix' => '#',
                    'pattern' => '\d+',
                    'generator' => 'https://github.com/symfony/ux/issues/%d',
                ],
            ],
            'external_link' => [
                'internal_hosts' => ['/(^|\.)symfony\.com$/'],
            ],
            'table' => [
                'wrap' => [
                    'enabled' => true,
                    'tag' => 'div',
                    'attributes' => ['class' => 'table-responsive'],
                ],
            ],
        ]);

        $converter->getEnvironment()
            ->addExtension(new DefaultAttributesExtension())
            ->addExtension(new ExternalLinkExtension())
            ->addExtension(new MentionExtension())
            ->addExtension(new FrontMatterExtension())
            ->addExtension(new TableExtension())
            ->addExtension(new TabsExtension())
            ->addExtension(new ToolkitPreviewExtension($this->uriSigner, $this->urlGenerator))
            ->addRenderer(FencedCode::class, new FencedCodeRenderer($this->componentRenderer))
        ;

        return $converter;
    }
}
