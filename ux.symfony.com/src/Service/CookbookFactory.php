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

use App\Model\Cookbook;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParserInterface;

final class CookbookFactory
{
    private FrontMatterParserInterface $frontMatterParser;

    public function __construct()
    {
        $this->frontMatterParser = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
    }

    public function createFromFile(string $file): Cookbook
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(\sprintf('File "%s" not found.', $file));
        }

        $content = file_get_contents($file);

        if (!\is_array($frontMatter = $this->frontMatterParser->parse($content)->getFrontMatter())) {
            throw new \RuntimeException(\sprintf('Cookbook file "%s" does not contains Front Matter data.', $file));
        }

        if (!isset($frontMatter['title']) || !\is_string($frontMatter['title'])) {
            throw new \RuntimeException('Missing title in Front Matter.');
        }
        if (!isset($frontMatter['description']) || !\is_string($frontMatter['description'])) {
            throw new \RuntimeException('Missing description in Front Matter.');
        }
        if (!isset($frontMatter['image']) || !\is_string($frontMatter['image'])) {
            throw new \RuntimeException('Missing image in Front Matter.');
        }
        if (!isset($frontMatter['tags']) || !\is_array($frontMatter['tags'])) {
            throw new \RuntimeException('Missing tags in Front Matter.');
        }

        return new Cookbook(
            title: $frontMatter['title'],
            slug: str_replace('_', '-', basename($file, '.md')),
            image: $frontMatter['image'],
            description: $frontMatter['description'],
            content: $content,
            tags: $frontMatter['tags'],
        );
    }
}
