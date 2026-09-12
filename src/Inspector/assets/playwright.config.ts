import { defineConfig } from '@playwright/test';
import { getBrowsers } from '../../../bin/get_browsers.mjs';

const browsers = await getBrowsers('145');

export default defineConfig({
    testDir: './test/browser',
    testMatch: '**/*.test.js',
    outputDir: 'playwright-output',
    reporter: 'list',
    workers: process.env.CI ? 2 : undefined,
    use: { baseURL: 'http://127.0.0.1:9877', viewport: { width: 1280, height: 900 }, trace: 'retain-on-failure' },
    webServer: { command: 'node test/browser/server.mjs', url: 'http://127.0.0.1:9877/a', reuseExistingServer: false },
    projects: [
        ...['lowest', 'latest'].map((version) => ({
            name: `chrome-${version}`,
            use: {
                browserName: 'chromium' as const,
                channel: 'chrome',
                launchOptions: { executablePath: browsers[`chrome@${version}`].executablePath },
            },
        })),
        {
            name: 'firefox-latest',
            use: {
                browserName: 'firefox',
                launchOptions: { executablePath: process.env.INSPECTOR_FIREFOX_EXECUTABLE },
            },
        },
    ],
});
