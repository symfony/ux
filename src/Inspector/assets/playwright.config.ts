import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './test/browser',
    testMatch: '**/*.test.js',
    outputDir: 'playwright-output',
    reporter: 'list',
    use: { baseURL: 'http://127.0.0.1:9877', viewport: { width: 1280, height: 900 }, trace: 'retain-on-failure' },
    webServer: { command: 'node test/browser/server.mjs', url: 'http://127.0.0.1:9877/a', reuseExistingServer: false },
    projects: [
        { name: 'chrome', use: { browserName: 'chromium', channel: 'chrome' } },
        {
            name: 'firefox',
            use: {
                browserName: 'firefox',
                launchOptions: { executablePath: process.env.INSPECTOR_FIREFOX_EXECUTABLE },
            },
        },
    ],
});
