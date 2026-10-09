import { createRouter } from '@symfony/ux-router';
import { routes } from '../var/routes/index.js';

/*
 * This file is part of the Symfony UX Router package.
 *
 * If folder "../var/routes" does not exist, or some routes are missing,
 * you must warmup your Symfony cache to refresh JavaScript routes.
 *
 * If you use TypeScript, you can rename this file to "router.ts" to take advantage of types checking.
 */

export const { path, url } = createRouter({ routes });
