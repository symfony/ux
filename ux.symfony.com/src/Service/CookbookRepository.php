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
use Symfony\Component\Finder\Finder;

readonly class CookbookRepository
{
    public function __construct(
        private CookbookFactory $cookbookFactory,
        private string $cookbookPath,
    ) {
    }

    public function findOneByName(string $name): Cookbook
    {
        if (!file_exists($this->cookbookPath.'/'.$name.'.md')) {
            throw new \RuntimeException('No cookbook found');
        }

        $finder = new Finder();
        $finder->files()->in($this->cookbookPath)->name($name.'.md');

        $file = null;
        foreach ($finder as $fileL) {
            $file = $fileL;
        }

        return $this->cookbookFactory->buildFromFile($file);
    }

    /**
     * @return Cookbook[]
     */
    public function findAll(): array
    {
        $finder = new Finder();
        $finder->files()->in($this->cookbookPath)->name('*.md');

        if (!$finder->hasResults()) {
            throw new \RuntimeException('No cookbook found');
        }

        $cookbooks = [];
        foreach ($finder as $file) {
            $cookbooks[] = $this->cookbookFactory->buildFromFile($file);
        }

        return $cookbooks;
    }
}
