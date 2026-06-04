<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit\Lint;

use Symfony\UX\Toolkit\Kit\Kit;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
interface KitCheckerInterface
{
    /**
     * @return iterable<LintIssue>
     */
    public function check(Kit $kit): iterable;
}
