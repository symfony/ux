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

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Subject\Exception\SubjectNotFoundException;

/**
 * Turns a disclose context into the subject object.
 *
 * The subject is an opaque object (an entity, a document, or any value the
 * resolver understands). Implement this interface to plug any data source.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
#[AutoconfigureTag('ux.disclose.subject_resolver')]
interface SubjectResolverInterface
{
    /**
     * Whether this resolver understands the given context.
     */
    public function supports(DiscloseContext $context): bool;

    /**
     * Resolves the subject addressed by the context.
     *
     * @throws SubjectNotFoundException when the subject cannot be resolved
     */
    public function resolve(DiscloseContext $context): object;
}
