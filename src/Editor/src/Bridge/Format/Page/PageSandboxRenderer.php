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

final class PageSandboxRenderer
{
    public function __construct(private readonly string $sandbox = 'allow-same-origin')
    {
    }

    public function render(PageContent $page): string
    {
        if ($page->isEmpty()) {
            return '';
        }
        $srcdoc = \sprintf(
            '<!doctype html><html><head><style>%s</style></head><body>%s</body></html>',
            $page->css,
            $page->html,
        );

        return \sprintf(
            '<iframe sandbox="%s" srcdoc="%s"></iframe>',
            htmlspecialchars($this->sandbox, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'),
            htmlspecialchars($srcdoc, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'),
        );
    }
}
