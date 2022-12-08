<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class BatchActionController
{
    public function __construct(private HttpKernelInterface $kernel)
    {
    }

    public function __invoke(Request $request, MountedComponent $_mounted_component, string $serviceId, array $actions): ?Response
    {
        foreach ($actions as $action) {
            $name = $action['name'] ?? throw new BadRequestHttpException('Invalid JSON');

            $subRequest = $request->duplicate(attributes: [
                '_controller' => [$serviceId, $name],
                '_component_action_args' => $action['args'] ?? [],
                '_mounted_component' => $_mounted_component,
                '_live_component' => $serviceId,
            ]);

            $response = $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);

            if ($response->isRedirection()) {
                return $response;
            }
        }

        return null;
    }
}
