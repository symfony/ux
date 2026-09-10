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
 * Thrown when a subject class is not managed by any data source.
 *
 * A mismatch between the rendered disclose context and the configured
 * resolvers is a configuration error.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class UnmanagedSubjectClassException extends \LogicException {}
