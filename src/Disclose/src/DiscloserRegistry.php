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

/**
 * Finds the discloser able to handle a resolved subject.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class DiscloserRegistry
{
    /**
     * @param iterable<DiscloserInterface> $disclosers
     */
    public function __construct(private readonly iterable $disclosers) {}

    public function getDiscloser(object $subject): DiscloserInterface
    {
        foreach ($this->disclosers as $discloser) {
            if ($discloser->supports($subject)) {
                return $discloser;
            }
        }

        throw new \LogicException(\sprintf('No discloser supports the subject of class "%s". Register a service implementing "%s".', $subject::class, DiscloserInterface::class));
    }
}
