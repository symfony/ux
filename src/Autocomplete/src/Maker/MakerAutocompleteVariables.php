<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Maker;

use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;

/**
 * @internal
 */
class MakerAutocompleteVariables
{
    public function __construct(
        public UseStatementGenerator $useStatements,
        public ClassNameDetails $entityClassDetails,
        public ?ClassNameDetails $repositoryClassDetails = null,
    ) {
    }
}
