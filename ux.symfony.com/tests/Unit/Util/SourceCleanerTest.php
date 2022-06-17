<?php

namespace App\Tests\Unit\Util;

use App\Util\SourceCleaner;
use PHPUnit\Framework\TestCase;

class SourceCleanerTest extends TestCase
{
    public function testItRemovesNamespaceAndPhpTag(): void
    {
        $source = <<<EOF
            <?php

            namespace App\Bar;

            class Foo
            {
            }
            EOF;

        $expected = <<<EOF
            class Foo
            {
            }
            EOF;

        $this->assertSame($expected, SourceCleaner::cleanupPhpFile($source));
    }

    public function testItRemovesClass(): void
    {
        $source = <<<EOF
            <?php

            namespace App\Bar;

            class Foo
            {
                public function foo()
                {
                    \$foo = '';
                    \$bar = function() use (\$foo) {};
                }
            }
            EOF;

        $expected = <<<EOF
            public function foo()
            {
                \$foo = '';
                \$bar = function() use (\$foo) {};
            }
            EOF;

        $this->assertSame($expected, SourceCleaner::cleanupPhpFile($source, removeClass: true));
    }
}
