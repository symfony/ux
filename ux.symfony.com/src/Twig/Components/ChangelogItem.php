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

    public function getTitle(): string
    {
        if (!isset($this->item['name'])) {
            return $this->item['version'];
        }

        $name = $this->item['name'];
        if (false !== $pos = stripos($name, $version = $this->getVersion())) {
            return ltrim(mb_substr($name, $pos + \strlen($version)), ' -:');
        }

        return $name;
    }

    public function getVersion(): string
    {
        return ltrim($this->item['version'], 'v');
    }

    private function formatContent(string $content): string
    {
        // Replace "## " with "### "
        $content = preg_replace('/^## /m', '### ', $content);

        // Replace "https://github.com/symfony/ux/pull/x" with "#x"
        $content = preg_replace('#https://github.com/symfony/ux/pull/(\d+)/?#', '#$1', $content);

        // Replace "https://github.com/symfony/ux/compare/v2.14.1...v2.14.2" with a markdown link to the full changelog
        $content = preg_replace('#(https://github.com/symfony/ux/compare/(v(\d+\.\d+\.\d+))...(v(\d+\.\d+\.\d+)))#', '[$2 -> $4]($1)', $content);

        // Shorten "made their first contribution in" to "in"
        $content = preg_replace('/made their first contribution in/', 'in', $content);

        return $content;
    }
}
