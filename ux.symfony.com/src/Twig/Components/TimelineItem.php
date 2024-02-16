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

#[AsTwigComponent('TimelineItem')]
class TimelineItem
{
    public array $item;

    public function getContent(): string
    {
        return $this->formatContent($this->item['body'] ?? '');
    }

    private function formatContent(string $content): string
    {
        // Replace "### " with "## "
        $content = preg_replace('/^## /m', '### ', $content);

        // Replace #1234 with a mardown link to the issue
        $content = preg_replace('/#(\d+)#/', '[#$1](https://github.com/symfony/ux/issues/$1)', $content);

        // Replace "in https://github.com/symfony/ux/pull/1234" with a mardown link to the PR
        $content = preg_replace('#in https://github.com/symfony/ux/pull/(\d+)/?#', 'in [#$1](https://github.com/symfony/ux/issues/$1)', $content);

        // Replace "https://github.com/symfony/ux/compare/v2.14.1...v2.14.2" with a mardown link to the full changelog
        $content = preg_replace('#https://github.com/symfony/ux/compare/(v[^.]+)...(v[^.]+)#', '[$2...$3]($1)', $content);

        // Replace "@daFish in " with a markdown link to the user profile
        $content = preg_replace('/@([a-zA-Z0-9_]+) in /', '[@$1](https://github.com/$1) in ', $content);

        // Replace "https://github.com/symfony/ux/compare/v2.14.1...v2.14.2" with a mardown link to the full changelog
        $content = str_replace('https://github.com/symfony/ux/compare/', 'ZZ', $content);
        $content = preg_replace('/ZZv(\d+\.\d+\.\d+)...v(\d+\.\d+\.\d+)/', '[$2...$1](https://github.com/symfony/ux/compare/$1..$2)', $content);

        return $content;
    }
}
