<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Disclose;

use App\Entity\Client;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\DiscloserInterface;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class ClientDiscloser implements DiscloserInterface
{
    public function supports(object $subject): bool
    {
        return $subject instanceof Client;
    }

    public function isGranted(Security $security, object $subject, DiscloseContext $context): bool
    {
        return true;
    }

    public function disclose(object $subject, DiscloseContext $context): string
    {
        /** @var Client $subject */
        return match ($context->field) {
            'email' => $subject->email,
            'phone' => $subject->phone,
            default => throw new \InvalidArgumentException(sprintf('The field "%s" cannot be disclosed for a client.', $context->field)),
        };
    }
}
