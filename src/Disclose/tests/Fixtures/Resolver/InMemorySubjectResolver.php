<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Fixtures\Resolver;

use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Subject\Exception\SubjectNotFoundException;
use Symfony\UX\Disclose\Subject\SubjectResolverInterface;
use Symfony\UX\Disclose\Tests\Fixtures\Subject\FixtureData;

/**
 * An in-memory subject resolver, proving the resolution abstraction does not
 * depend on a data source.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class InMemorySubjectResolver implements SubjectResolverInterface
{
    public function supports(DiscloseContext $context): bool
    {
        return FixtureData::class === $context->class;
    }

    public function resolve(DiscloseContext $context): object
    {
        if ('999' === (string) $context->id) {
            throw new SubjectNotFoundException(\sprintf('The subject "%s::%s" cannot be found.', $context->class, $context->id));
        }

        return new FixtureData();
    }
}
