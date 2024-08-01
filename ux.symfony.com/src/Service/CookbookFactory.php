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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CookbookFactory
{
    public function __construct(
        private readonly CookbookParser $cookbookParser,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildFromFile(\SplFileInfo $file): Cookbook
    {
        $content = $file->getContents();

        return new Cookbook(
            title: $this->cookbookParser->getTitle($content),
            description: $this->cookbookParser->getDescriptions($content),
            route: $this->urlGenerator->generate('app_cookbook_show', ['slug' => $file->getBasename('.md')]),
            image: $this->cookbookParser->getImage($content),
            content: $content,
            tags: $this->cookbookParser->getTags($content),
        );
    }
}
