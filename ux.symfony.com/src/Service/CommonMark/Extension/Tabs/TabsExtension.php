<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\Tabs;

use App\Service\CommonMark\Extension\Tabs\Node\Tab;
use App\Service\CommonMark\Extension\Tabs\Node\Tabs;
use App\Service\CommonMark\Extension\Tabs\Parser\TabParser;
use App\Service\CommonMark\Extension\Tabs\Parser\TabsParser;
use App\Service\CommonMark\Extension\Tabs\Renderer\TabRenderer;
use App\Service\CommonMark\Extension\Tabs\Renderer\TabsRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

final readonly class TabsExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(TabsParser::createBlockStartParser(), 100)
            ->addBlockStartParser(TabParser::createBlockStartParser(), 90)
            ->addRenderer(Tabs::class, new TabsRenderer())
            ->addRenderer(Tab::class, new TabRenderer())
        ;
    }
}
