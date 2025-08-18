/**
 * Vitest base configuration for UX packages, for unit testing.
 *
 * This file is not intended to be used directly, but to be used by UX packages themselves.
 * For example:
 * ```typescript
 * // src/Autocomplete/assets/vitest.config.mjs
 *
 * import baseConfig from '../../../../../../vitest.config.base';
 *
 * export default baseConfig;
 * ```
 */

import { defineConfig } from 'vitest/config';
import path from 'node:path';

export default defineConfig({
    test: {
        include: [
            './test/unit/**/*.{test,spec}.(ts|tsx)',
            './test/**/*.unit.{test,spec}.(ts|tsx)',
        ],
        environment: 'jsdom',
        setupFiles: [path.join(import.meta.dirname, 'test', 'setup.js')],
    },
});
