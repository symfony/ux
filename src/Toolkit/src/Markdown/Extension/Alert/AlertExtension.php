<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Alert;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Markdown\Extension\Alert\Node\Alert;
use Symfony\UX\Toolkit\Markdown\Extension\Alert\Parser\AlertParser;
use Symfony\UX\Toolkit\Markdown\Extension\Alert\Renderer\AlertRenderer;

final class AlertExtension implements ExtensionInterface
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
        Assert::commonMarkAvailable();
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(AlertParser::createBlockStartParser(), 100)
            ->addRenderer(Alert::class, new AlertRenderer($this->twig))
        ;
    }
}
