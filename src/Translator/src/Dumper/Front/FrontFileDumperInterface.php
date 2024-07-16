<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Dumper\Front;

use Symfony\Component\Translation\MessageCatalogueInterface;

interface FrontFileDumperInterface
{
    public function dump(MessageCatalogueInterface ...$catalogues): void;
    public function setDumpDir(string $dumpDir): static;
}
