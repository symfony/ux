/**
 * Root Playwright configuration for the UX Editor demo + Live cross-cutting specs.
 *
 * This file orchestrates all editor-related Playwright projects against the
 * ux.symfony.com demo server running on http://127.0.0.1:8765.
 *
 * Note: This config does NOT extend playwright.config.base because that base
 * config eagerly downloads browser binaries via get_browsers.mjs (network calls
 * at import time). Individual bridge e2e dirs can extend the base when needed.
 *
 * Projects:
 *  - live      → cross-cutting live specs (hot-reload, autosave)
 *  - editorjs  → EditorJS bridge e2e (tests added separately)
 *  - ckeditor  → CKEditor bridge e2e (tests added separately)
 *  - grapesjs  → GrapesJS bridge e2e (tests added separately)
 */

import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testMatch: ['**/*.{test,spec}.ts'],

    reporter: [
        ['list'],
        ['html', { open: process.env.CI ? 'never' : 'on-failure', outputFolder: '.playwright-report' }],
    ],

    outputDir: '.playwright-output',

    webServer: {
        command: 'cd ux.symfony.com && symfony server:start --no-tls --port=8765',
        url: 'http://127.0.0.1:8765',
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },

    use: {
        baseURL: 'http://127.0.0.1:8765',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        trace: 'retain-on-failure',
    },

    projects: [
        {
            name: 'live',
            testDir: './src/Editor/tests/e2e/live',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'editorjs',
            testDir: './src/Editor/src/Bridge/EditorJS/tests/e2e',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'ckeditor',
            testDir: './src/Editor/src/Bridge/CKEditor/tests/e2e',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'grapesjs',
            testDir: './src/Editor/src/Bridge/GrapesJS/tests/e2e',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
