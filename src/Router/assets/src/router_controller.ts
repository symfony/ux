/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { ABSOLUTE_PATH, ABSOLUTE_URL, generate, NETWORK_PATH, RELATIVE_PATH } from './generator';
import type { RequestContext, Router, Routes } from './types';

export { InvalidParameterError, MissingMandatoryParametersError, RouteNotFoundError } from './errors';
export type * from './types';

export function createRouter<TRoutes extends Routes>({
    routes,
    context = {},
}: {
    routes: TRoutes;
    context?: Partial<RequestContext>;
}): Router<TRoutes> {
    const router = {
        path(name: string, parameters: Record<string, unknown> = {}, relative = false): string {
            return generate(routes, name, parameters, relative ? RELATIVE_PATH : ABSOLUTE_PATH, context);
        },
        url(name: string, parameters: Record<string, unknown> = {}, schemeRelative = false): string {
            return generate(routes, name, parameters, schemeRelative ? NETWORK_PATH : ABSOLUTE_URL, context);
        },
    };

    return router as unknown as Router<TRoutes>;
}
