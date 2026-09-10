<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Subject\Exception;

/**
 * Thrown when no subject resolver can resolve a disclose context.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class SubjectNotFoundException extends \RuntimeException {}
