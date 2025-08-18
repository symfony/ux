import * as path from 'node:path';
import {
    Browser,
    BrowserTag,
    detectBrowserPlatform,
    install as installBrowser,
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
    'chrome@lowest': await installBrowser({
        ...installBrowserCommonOpts,
        browser: Browser.CHROME,
        // The lowest version where:
        // - Chrome and associated Chromedriver could easily be downloaded
        // - there is no compatibility issues like "WebDriver Bidi command \"session.subscribe\" failed with error"
        // - there is no timeout issues when requesting Vitest webserver
        // @see https://raw.githubusercontent.com/GoogleChromeLabs/chrome-for-testing/refs/heads/main/data/known-good-versions-with-downloads.json
        buildId: '130.0.6669.0',
    }),
    'chrome@latest': await installBrowser({
        ...installBrowserCommonOpts,
        browser: Browser.CHROME,
        buildId: await resolveBuildId(Browser.CHROME, platform, BrowserTag.STABLE),
    }),
    'firefox@lowest': await installBrowser({
        ...installBrowserCommonOpts,
        browser: Browser.FIREFOX,
        buildId: 'stable_128.0',
    }),
    'firefox@latest': await installBrowser({
        ...installBrowserCommonOpts,
        browser: Browser.FIREFOX,
        buildId: await resolveBuildId(Browser.FIREFOX, platform, BrowserTag.STABLE),
    }),
};

if (import.meta.main) {
    console.log('Browsers installed:', browsers);
}
