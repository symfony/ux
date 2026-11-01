<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\LiveComponent\EventListener\LiveComponentSubscriber;

/**
 * Skips the CSRF validation of the forms submitted during a live component request.
 *
 * The live component endpoints are already protected against CSRF by requiring
 * the X-Requested-With header, while the stateless CSRF protection cannot be met
 * there: live requests never double-submit a token. The token is still rendered,
 * so that a regular submit of the form remains protected.
 *
 * @internal
 */
final class LiveRequestCsrfTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->addNormalizer('csrf_token_manager', function (Options $options, ?CsrfTokenManagerInterface $csrfTokenManager): ?CsrfTokenManagerInterface {
            if (null === $csrfTokenManager || !$this->requestStack->getMainRequest()?->attributes->get(LiveComponentSubscriber::LIVE_REQUEST_ATTRIBUTE)) {
                return $csrfTokenManager;
            }

            return new LiveRequestCsrfTokenManager($csrfTokenManager);
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
