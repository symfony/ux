import { defineConfig, devices } from '@playwright/test';

import { browsers } from './bin/get_browsers.mjs';

export default defineConfig({
    testMatch:[
        'src/**/assets/test/browser/**/*.{test,spec}.ts',
        'src/**/assets/test/**/*.browser.{test,spec}.ts',
    ],
    testIgnore: [
        // TODO: Temporary, as the Map tests require some TypeScript code to be compiled
        // and so fails (which is expected). To remove once the tests are real E2E.
        /Map/
    ],

    reporter: [
        ['list'],
        ['html', { open: process.env.CI ? 'never' : 'on-failure', outputFolder: '.playwright-report' }],
    ],

    use: {
        // Base URL to use in actions like `await page.goto('/')`.
        baseURL: 'http://localhost:9876',

        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        trace: 'retain-on-failure'
    },

    //webServer: {
    //    command: 'cd test_apps/e2e-app && symfony serve',
    //    url: 'http://localhost:9876',
    //    reuseExistingServer: !process.env.CI,
    //    stderr: 'pipe',
    //},

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
