<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\File\Doc;

final class DocTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $doc = new Doc(
            '# Basic Button

```twig
<twig:Button>
    Click me
</twig:Button>
```'
        );

        self::assertEquals('# Basic Button

```twig
<twig:Button>
    Click me
</twig:Button>
```', $doc->markdownContent);
    }
}
