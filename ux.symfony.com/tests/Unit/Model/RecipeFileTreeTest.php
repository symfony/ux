<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Model;

use App\Model\RecipeFileTree;
use PHPUnit\Framework\TestCase;

class RecipeFileTreeTest extends TestCase
{
    public function testItReturnsFileTree(): void
    {
        $fileTree = new RecipeFileTree();
        $files = $fileTree->getFiles();

        $this->assertArrayHasKey('assets', $files);
        $this->assertTrue($files['assets']['isDirectory']);
        $this->assertNotEmpty($files['assets']['files']);
        $this->assertArrayHasKey('assets/bootstrap.js', $files['assets']['files']);
        $this->assertSame('bootstrap.js', $files['assets']['files']['assets/bootstrap.js']['filename']);
        $this->assertArrayHasKey('assets/controllers', $files['assets']['files']);
        $this->assertArrayNotHasKey('assets/controllers/hello_controller.js', $files['assets']['files']);

        $this->assertArrayHasKey('package.json', $files);
        $this->assertFalse($files['package.json']['isDirectory']);
        $this->assertSame('package.json', $files['package.json']['filename']);
    }
}
