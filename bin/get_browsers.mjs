import * as path from 'node:path';
import {
    Browser,
    BrowserTag,
    detectBrowserPlatform,
    install,
    resolveBuildId,
} from '@puppeteer/browsers';

const platform = detectBrowserPlatform();
const installBrowserCommonOpts = {
    platform,
    cacheDir: path.join(import.meta.dirname, '../browsers'),
    downloadProgressCallback: 'default',
};

// Lowest versions are computed from "defaults and fully supports es6-module" query,
// see https://browsersl.ist/#q=defaults+and+fully+supports+es6-module

export const browsers = {
    'chrome@lowest': await install({
        ...installBrowserCommonOpts,
        browser: Browser.CHROME,
        // The lowest version where:
        // - Chrome and associated Chromedriver could easily be downloaded
        // - there is no compatibility issues like "WebDriver Bidi command \"session.subscribe\" failed with error"
        // - there is no timeout issues when requesting Vitest webserver
        // @see https://raw.githubusercontent.com/GoogleChromeLabs/chrome-for-testing/refs/heads/main/data/known-good-versions-with-downloads.json
        buildId: '130.0.6669.0',
    }),

    'chrome@latest': await install({
       ...installBrowserCommonOpts,
       browser: Browser.CHROME,
       buildId: await resolveBuildId(Browser.CHROME, platform, BrowserTag.STABLE),
    }),

    // TODO: I don't find a way to install a specific Firefox version and make it usable
    // with Playwright. It's surely related to patch things (https://playwright.dev/docs/browsers#firefox),
    // but even a non-branded version like Nightly doesn't work.

    // 'firefox@lowest': await install({
    //     ...installBrowserCommonOpts,
    //     browser: Browser.FIREFOX,
    //     buildId: '128.0a1',
    //     baseUrl: 'https://ftp.mozilla.org/pub/firefox/nightly/2024/06/2024-06-01-09-33-40-mozilla-central'
    // }),
    //
    // 'firefox@latest': await install({
    //     ...installBrowserCommonOpts,
    //     browser: Browser.FIREFOX,
    //     buildId: await resolveBuildId(Browser.FIREFOX, platform, BrowserTag.NIGHTLY),
    // }),
};

if (import.meta.main) {
    console.log('Browsers installed:', browsers);
}
