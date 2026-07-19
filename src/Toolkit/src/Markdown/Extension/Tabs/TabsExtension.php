<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Tabs;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Node\Tab;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Node\Tabs;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Parser\TabParser;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Parser\TabsParser;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Renderer\TabRenderer;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Renderer\TabsRenderer;

final class TabsExtension implements ExtensionInterface
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
        Assert::commonMarkAvailable();
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(TabsParser::createBlockStartParser(), 100)
            ->addBlockStartParser(TabParser::createBlockStartParser(), 90)
            ->addRenderer(Tabs::class, new TabsRenderer($this->twig))
            ->addRenderer(Tab::class, new TabRenderer())
        ;
    }
}
