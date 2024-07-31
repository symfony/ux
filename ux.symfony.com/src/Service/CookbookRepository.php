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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

readonly class CookbookRepository
{
    public function __construct(
        private CookbookFactory $cookbookFactory,
        #[Autowire('%kernel.project_dir%/cookbook')] private string $cookbookDirectory,
    ) {
    }

    public function findOneBySlug(string $slug): ?Cookbook
    {
        if (!preg_match('/^[a-z]+(-[a-z0-9]+)*$/', $slug)) {
            throw new \InvalidArgumentException(\sprintf('Invalid slug provided: "%s".', $slug));
        }

        if (!file_exists($filename = $this->getCookbookFilename($slug))) {
            return null;
        }

        return $this->cookbookFactory->createFromFile($filename);
    }

    /**
     * @return list<Cookbook>
     */
    public function findAll(): array
    {
        $files = (new Finder())
            ->files()
            ->in($this->cookbookDirectory)
            ->name('*.md');

        $cookbooks = [];
        foreach ($files as $file) {
            $cookbooks[] = $this->cookbookFactory->createFromFile($file);
        }

        return $cookbooks;
    }

    private function getCookbookFilename(string $slug): string
    {
        return $this->cookbookDirectory.'/'.str_replace('-', '_', $slug).'.md';
    }
}
