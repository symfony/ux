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

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Subject\Exception\SubjectNotFoundException;
use Symfony\UX\Disclose\Subject\Exception\UnmanagedSubjectClassException;

/**
 * Resolves disclose contexts against any object manager registry compatible
 * with doctrine/persistence (Doctrine ORM, MongoDB ODM, or another source).
 *
 * This resolver is only registered when the matching package is installed. It
 * is an example implementation: the resolve contract itself stays
 * data-source agnostic.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DoctrinePersistenceSubjectResolver implements SubjectResolverInterface
{
    public function __construct(private readonly ?ManagerRegistry $doctrineRegistry = null) {}

    public function supports(DiscloseContext $context): bool
    {
        return null !== $this->objectManagerFor($context);
    }

    public function resolve(DiscloseContext $context): object
    {
        $objectManager = $this->objectManagerFor($context);

        if (null === $objectManager) {
            throw new UnmanagedSubjectClassException(\sprintf('Class "%s" is not managed by any doctrine/persistence object manager.', $context->class));
        }

        if (null === $subject = $objectManager->find($context->class, $context->id)) {
            throw new SubjectNotFoundException(\sprintf('The subject "%s::%s" cannot be found.', $context->class, \is_array($context->id) ? json_encode($context->id) : $context->id));
        }

        return $subject;
    }

    private function objectManagerFor(DiscloseContext $context): ?ObjectManager
    {
        return $this->doctrineRegistry?->getManagerForClass($context->class);
    }
}
