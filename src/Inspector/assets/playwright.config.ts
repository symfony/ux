import { defineConfig } from '@playwright/test';
import baseConfig from '../../../playwright.config.base';

export default defineConfig(baseConfig, {
    testDir: './test/browser',
    testMatch: '**/*.test.js',
    use: { baseURL: 'http://127.0.0.1:9878', viewport: { width: 1280, height: 900 } },
    webServer: { command: 'node test/browser/server.mjs', url: 'http://127.0.0.1:9878', reuseExistingServer: false },
});
