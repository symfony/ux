<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('ChangelogItem')]
class ChangelogItem
{
    public array $item;

    public bool $isOpen = false;

    public function getContent(): string
    {
        return $this->formatContent($this->item['body'] ?? '');
    }

    private function formatContent(string $content): string
    {
        // Replace "## " with "### "
        $content = preg_replace('/^## /m', '### ', $content);

        // Replace #1234 with a mardown link to the issue
        $content = preg_replace('/#(\d+)/', '[#$1](https://github.com/symfony/ux/issues/$1)', $content);

        // Replace "in https://github.com/symfony/ux/pull/1234" with a mardown link to the PR
        $content = preg_replace('#in https://github.com/symfony/ux/pull/(\d+)/?#', 'in [#$1](https://github.com/symfony/ux/issues/$1)', $content);

        // Replace "https://github.com/symfony/ux/compare/v2.14.1...v2.14.2" with a mardown link to the full changelog
        $content = preg_replace('#(https://github.com/symfony/ux/compare/(v(\d+\.\d+\.\d+))...(v(\d+\.\d+\.\d+)))#', '[$2 -> $4]($1)', $content);

        // Insert markdown link to the user's Github profile
        // ...in: "by @weaverryan in "
        $content = preg_replace('/by @([a-zA-Z0-9_]+) in /', 'by [@$1](https://github.com/$1) in ', $content);
        // ...in: "@weaverryan made their first "
        $content = preg_replace('/@([a-zA-Z0-9_]+) made their first /', '[@$1](https://github.com/$1) made their first ', $content);

        // Shorten "made their first contribution in" to "in"
        $content = preg_replace('/made their first contribution in/', 'in', $content);

        return $content;
    }
}
