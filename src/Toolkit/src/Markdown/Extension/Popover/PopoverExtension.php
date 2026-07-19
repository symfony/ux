<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Popover;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Markdown\Extension\Popover\Node\Popover;
use Symfony\UX\Toolkit\Markdown\Extension\Popover\Parser\PopoverParser;
use Symfony\UX\Toolkit\Markdown\Extension\Popover\Renderer\PopoverRenderer;

final class PopoverExtension implements ExtensionInterface
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
        Assert::commonMarkAvailable();
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addInlineParser(new PopoverParser())
            ->addRenderer(Popover::class, new PopoverRenderer($this->twig))
        ;
    }
}
