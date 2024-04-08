<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
final class LiveRequestDataParser
{
    /**
     * @return array{
     *     data: array,
     *     args: array,
     *     actions: array
     *     // has "fingerprint" and "tag" string key, keyed by component id
     *     children: array
     *     propsFromParent: array
     * }
     */
    public static function parseDataFor(Request $request): array
    {
        if (!$request->attributes->has('_live_request_data')) {
            if ($request->query->has('props')) {
                $liveRequestData = [
                    'props' => self::parseJsonFromQuery($request, 'props'),
                    'updated' => self::parseJsonFromQuery($request, 'updated'),
                    'args' => [],
                    'actions' => [],
                    'children' => self::parseJsonFromQuery($request, 'children'),
                    'propsFromParent' => self::parseJsonFromQuery($request, 'propsFromParent'),
                ];
            } else {
                try {
                    $requestData = json_decode($request->request->get('data'), true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new JsonException('Could not decode request body.', $e->getCode(), $e);
                }

                $liveRequestData = [
                    'props' => $requestData['props'] ?? [],
                    'updated' => $requestData['updated'] ?? [],
                    'args' => $requestData['args'] ?? [],
                    'actions' => $requestData['actions'] ?? [],
                    'children' => $requestData['children'] ?? [],
                    'propsFromParent' => $requestData['propsFromParent'] ?? [],
                ];
            }

            $request->attributes->set('_live_request_data', $liveRequestData);
        }

        return $request->attributes->get('_live_request_data');
    }

    private static function parseJsonFromQuery(Request $request, string $key): array
    {
        if (!$request->query->has($key)) {
            return [];
        }

        try {
            return json_decode($request->query->get($key), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new JsonException(sprintf('Invalid JSON on query string %s.', $key), 0, $exception);
        }
    }
}
