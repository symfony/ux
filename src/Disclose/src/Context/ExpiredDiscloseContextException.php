<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Context;

/**
 * Thrown when a signed disclose context is replayed past its expiry.
 *
 * Signed disclose URLs carry an expiry so a reference captured from a page,
 * a proxy log or a browser history cannot be replayed indefinitely. The
 * controller maps this to an HTTP 410 response.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class ExpiredDiscloseContextException extends \RuntimeException {}
