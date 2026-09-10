<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Context\DiscloseContextSigner;

/**
 * Generates the HTTP endpoint URL for a disclose context.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class DiscloseUrlGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly DiscloseContextSigner $contextSigner,
    ) {}

    public function generate(DiscloseContext $context, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->urlGenerator->generate('ux_disclose', $this->contextSigner->sign($context), $referenceType);
    }
}
