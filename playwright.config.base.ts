/**
 * Playwright base configuration for UX packages, for browser testing.
 *
 * This file is not intended to be used directly, but to be used by UX packages themselves.
 * For example:
 * ```typescript
 * // src/Autocomplete/assets/playwright.config.ts
 *
 * import baseConfig from '../../../../../../playwright.config.base';
 *
 * export default baseConfig;
 * ```
 */

import { defineConfig, devices } from '@playwright/test';
import { browsers } from './bin/get_browsers.mjs';

export default defineConfig({
    testMatch: [
        '**/test/browser/**/*.{test,spec}.ts',
        '**/test/**/*.browser.{test,spec}.ts',
    ],

    reporter: [
        ['list'],
        ['html', { open: process.env.CI ? 'never' : 'on-failure', outputFolder: '.playwright-report' }],
    ],

    outputDir: '.playwright-output',

    use: {
        // Base URL to use in actions like `await page.goto('/')`.
        baseURL: 'http://localhost:9876',

        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        trace: 'retain-on-failure'
    },

    projects: [
        {
            name: 'chrome-lowest',
            use: {
                ...devices['Desktop Chrome'],
                channel: 'chrome',
                launchOptions: {
                    executablePath: browsers['chrome@lowest'].executablePath,
                }
            },
        },
        {
            name: 'chrome-latest',
            use: {
                ...devices['Desktop Chrome'],
                channel: 'chrome',
                launchOptions: {
                    executablePath: browsers['chrome@latest'].executablePath,
                }
            },
        },

        // TODO: Until we found a way to run a specific version of Firefox
        //{
        //    name: 'firefox-lowest',
        //    use: {
        //        ...devices['Desktop Firefox'],
        //        launchOptions: {
        //            executablePath: browsers['firefox@lowest'].executablePath,
        //        }
        //    },
        //},
        {
            name: 'firefox-latest',
            use: {
                ...devices['Desktop Firefox'],
            },
        }
    ],
});
