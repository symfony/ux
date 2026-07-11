<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\Format\Page;

use Symfony\UX\Editor\Content\PageContent;

final class PageAssetExtractor
{
    /**
     * @return list<string>
     */
    public function extractUrls(PageContent $page): array
    {
        $urls = [];
        foreach ($page->assets as $asset) {
            if (\is_array($asset) && isset($asset['url']) && \is_string($asset['url'])) {
                $urls[$asset['url']] = true;
            }
        }
        $this->walk($page->components, $urls);

        return array_keys($urls);
    }

    /**
     * @param array<mixed>        $nodes
     * @param array<string, bool> $urls
     */
    private function walk(array $nodes, array &$urls): void
    {
        foreach ($nodes as $n) {
            if (!\is_array($n)) {
                continue;
            }
            if (isset($n['src']) && \is_string($n['src'])) {
                $urls[$n['src']] = true;
            }
            if (isset($n['url']) && \is_string($n['url'])) {
                $urls[$n['url']] = true;
            }
            if (!empty($n['children']) && \is_array($n['children'])) {
                $this->walk($n['children'], $urls);
            }
        }
    }
}
