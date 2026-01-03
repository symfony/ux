<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\Tabs\Renderer;

use App\Service\CommonMark\Extension\Tabs\Node\Tab;
use App\Service\CommonMark\Extension\Tabs\Node\Tabs;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class TabsRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof Tabs) {
            throw new \InvalidArgumentException(\sprintf('Expected instance of "%s", got "%s"', Tabs::class, $node::class));
        }

        $tabs = [];
        foreach ($node->children() as $child) {
            if ($child instanceof Tab) {
                $tabs[] = [
                    'title' => $child->getTitle(),
                    'content' => $childRenderer->renderNodes($child->children()),
                ];
            }
        }

        if ([] === $tabs) {
            throw new \RuntimeException(\sprintf('The "%s" block must contain at least one "%s" block.', Tabs::class, Tab::class));
        }
        $activeTabId = null;
        $tabsControls = '';
        $tabsPanels = '';

        foreach ($tabs as $tab) {
            $tabId = hash('xxh3', $tab['title']);
            $activeTabId ??= $tabId;
            $isActive = $tabId === $activeTabId;

            $tabsControls .= \sprintf(
                '<button class="Wysiwyg_TabControl %s" data-action="tabs#show" data-tabs-target="control" data-tabs-tab-param="%s" role="tab" aria-selected="%s">%s</button>',
                $isActive ? 'active' : '',
                $tabId,
                $isActive ? 'true' : 'false',
                htmlspecialchars($tab['title'], \ENT_QUOTES, 'UTF-8')
            );

            $tabsPanels .= \sprintf(
                '<div class="Wysiwyg_TabPanel %s" data-tabs-target="tab" data-tab="%s" role="tabpanel">%s</div>',
                $isActive ? 'active' : '',
                $tabId,
                $tab['content']
            );
        }

        return <<<HTML
                <div class="Wysiwyg_Tabs" data-controller="tabs" data-tabs-tab-value="{$activeTabId}" data-tabs-active-class="active">
                    <nav class="Wysiwyg_TabHead" role="tablist" style="border-bottom: 1px solid var(--bs-border-color)">{$tabsControls}</nav>
                    <div class="Wysiwyg_TabBody">{$tabsPanels}</div>
                </div>
            HTML;
    }
}
