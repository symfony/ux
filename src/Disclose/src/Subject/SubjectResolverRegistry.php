<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Subject;

use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Subject\Exception\UnmanagedSubjectClassException;

/**
 * Runs the subject resolvers in order and returns the first outcome.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class SubjectResolverRegistry
{
    /**
     * @param iterable<SubjectResolverInterface> $resolvers
     */
    public function __construct(private readonly iterable $resolvers) {}

    /**
     * @throws UnmanagedSubjectClassException when no resolver supports the context
     */
    public function resolve(DiscloseContext $context): object
    {
        foreach ($this->resolvers as $resolver) {
            if (!$resolver->supports($context)) {
                continue;
            }

            return $resolver->resolve($context);
        }

        throw new UnmanagedSubjectClassException(\sprintf('No subject resolver supports the context for class "%s".', $context->class));
    }
}
