<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Dumper;

use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @author Maelan Le Borgne <maelan.leborgne@gmail.com>
 *
 * @final
 *
 * @experimental
 *
 * A dumper that generates JavaScript module files.
 */
class ModuleDumper extends FileDumper
{
    public function __construct(
        private FileDumper $inner,
    ) {
    }

    public function formatCatalogue(MessageCatalogue $messages, string $domain, array $options = []): string
    {
        return 'export default '.$this->inner->formatCatalogue($messages, $domain, $options).';';
    }

    protected function getExtension(): string
    {
        return 'js';
    }
}
