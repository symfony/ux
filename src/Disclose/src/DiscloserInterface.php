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

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\UX\Disclose\Context\DiscloseContext;

/**
 * Extracts the sensitive value of a subject and decides whether its
 * disclosure is allowed.
 *
 * Implement this interface to state your authorization policy and to map a
 * subject to the value to disclose. The subject is an opaque object: the
 * contract never depends on a specific data source.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
#[AutoconfigureTag('ux.disclose.discloser')]
interface DiscloserInterface
{
    /**
     * Whether this discloser handles the given subject.
     */
    public function supports(object $subject): bool;

    /**
     * Whether the current request is allowed to disclose the subject.
     *
     * Delegate to the Security component, for example
     * $security->isGranted('CLIENT_VIEW', $subject) or $security->isGranted('ROLE_USER').
     * The context carries the field to disclose and the application-specific
     * payload, so the policy can be field aware (for example a stricter
     * attribute for an email than for a phone number).
     *
     * This is enforced by the bundle before any value is returned. When the
     * SecurityBundle is not installed, no disclosure is allowed.
     */
    public function isGranted(Security $security, object $subject, DiscloseContext $context): bool;

    /**
     * Returns the sensitive value to disclose for the given subject.
     *
     * The context carries the field to read and the application-specific
     * payload built when the component was rendered.
     */
    public function disclose(object $subject, DiscloseContext $context): string;
}
