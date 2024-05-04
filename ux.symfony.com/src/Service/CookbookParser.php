<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Output\RenderedContentInterface;

class CookbookParser
{
    public function getTitle(string $content): string
    {
        return $this->getFrontMatterProperty('title', $content) ??
            throw new \RuntimeException('Title is required in a cookbook');
    }

    public function getDescriptions(string $content): string
    {
        return $this->getFrontMatterProperty('description', $content) ??
            throw new \RuntimeException('Description is required in a cookbook');
    }

    public function getImage(string $content): string
    {
        return $this->getFrontMatterProperty('image', $content) ??
            throw new \RuntimeException('Image is required in a cookbook');
    }

    public function getTags(string $content): array
    {
        return $this->getFrontMatterProperty('tags', $content) ??
            throw new \RuntimeException('Tags are required in a cookbook');
    }

    public function getContent(string $content): string
    {
        $result = $this->convert($content);

        return $result->getContent();
    }

    private function convert(string $content): RenderedContentInterface
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new FrontMatterExtension());

        $converter = new MarkdownConverter($environment);

        return $converter->convert($content);
    }

    private function getFrontMatterProperty(string $property, string $content)
    {
        $result = $this->convert($content);

        if (!$result instanceof RenderedContentWithFrontMatter) {
            throw new \RuntimeException('FrontMatter can\'t parse the cookbook');
        }

        return $result->getFrontMatter()[$property];
    }
}
