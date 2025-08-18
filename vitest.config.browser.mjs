/// <reference types="@vitest/browser/providers/webdriverio" />
import { defineConfig } from 'vitest/config';
import path from 'node:path';
import { browsers } from './bin/get_browsers.mjs';

export default defineConfig({
    test: {
        include: [
            './test/browser/**/*.{test,spec}.ts',
            './test/**/*.browser.{test,spec}.ts',
        ],
        setupFiles: [path.join(__dirname, 'test', 'setup.js')],
        browser: {
            enabled: true,
            provider: 'webdriverio',
            headless: true,
            instances: [
                {
                    name: 'Chrome (lowest)',
                    browser: 'chrome',
                    capabilities: {
                        'goog:chromeOptions': {
                            binary: browsers['chrome@lowest'].executablePath,
                            args: ['--no-sandbox', '--disable-dev-shm-usage']
                        },
                    },
                },

                {
                    name: 'Chrome (latest)',
                    browser: 'chrome',
                    capabilities: {
                        'goog:chromeOptions': {
                            binary: browsers['chrome@latest'].executablePath,
                            args: ['--no-sandbox', '--disable-dev-shm-usage']
                        },
                    },
                },

                {
                    name: 'Firefox (lowest)',
                    browser: 'firefox',
                    capabilities: {
                        'moz:firefoxOptions': {
                            binary: browsers['firefox@lowest'].executablePath,
                        }
                    },
                },

                {
                    name: 'Firefox (latest)',
                    browser: 'firefox',
                    capabilities: {
                        'moz:firefoxOptions': {
                            binary: browsers['firefox@latest'].executablePath,
                        }
                    },
                },
            ],
        },
    },
});
