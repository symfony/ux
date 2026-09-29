import { createRequire } from 'node:module';
import { defineConfig } from '@playwright/test';

const require = createRequire(import.meta.url);
const { version } = require('@playwright/test/package.json');
const isCI = !!process.env.CI;

const browserServer = [
    'docker run --rm --init -p 127.0.0.1:3000:3000',
    `mcr.microsoft.com/playwright:v${version}-noble`,
    `npx -y playwright@${version} run-server --port 3000 --host 0.0.0.0`,
].join(' ');

export default defineConfig({
    fullyParallel: true,
    forbidOnly: isCI,
    retries: isCI ? 1 : 0,
    workers: isCI ? '75%' : undefined,
    updateSnapshots: isCI ? 'none' : 'missing',

    reporter: [['list'], ['html', { open: isCI ? 'never' : 'on-failure', outputFolder: 'playwright-report' }]],

    outputDir: 'playwright-output',

    expect: {
        toHaveScreenshot: { animations: 'disabled', caret: 'hide' },
    },

    use: {
        baseURL: 'http://127.0.0.1:9889',
        viewport: { width: 800, height: 600 },
        deviceScaleFactor: 1,
        connectOptions: { wsEndpoint: 'ws://127.0.0.1:3000/', exposeNetwork: '<loopback>' },
        trace: 'retain-on-failure',
    },

    webServer: [
        {
            // The server logs every request: keep them in a file, uploaded by the CI when a test fails.
            command: 'mkdir -p var/log && symfony server:start --no-workers > var/log/server.log 2>&1',
            cwd: '../../../apps/toolkit',
            url: 'http://127.0.0.1:9889/',
            reuseExistingServer: true,
        },
        {
            command: browserServer,
            url: 'http://127.0.0.1:3000/',
            reuseExistingServer: true,
            timeout: 5 * 60_000,
        },
    ],

    projects: [
        {
            name: 'examples',
            testDir: 'test/browser',
            testMatch: ['examples.spec.ts', 'interactions.spec.ts'],
            snapshotPathTemplate: '{testDir}/../../../kits/{arg}{ext}',
        },
        {
            name: 'recipes',
            testDir: '../kits',
            testMatch: '*/*/tests/**/*.spec.ts',
            snapshotPathTemplate: '{testDir}/{arg}{ext}',
        },
    ],
});
