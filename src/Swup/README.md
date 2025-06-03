# Symfony UX Swup

> [!WARNING]
> **Deprecated**: This package has been **deprecated** in 2.x and will be removed in the next major version.

We
To keep the same functionality in your Symfony application, follow these migration steps:

1. Install the `swup` library and its plugins:

```bash
# If using Symfony AssetMapper:
php bin/console importmap:require swup @swup/fade-theme @swup/slide-theme @swup/forms-plugin @swup/debug-plugin

# If using NPM (e.g.: with Webpack Encore):
npm install swup @swup/fade-theme @swup/slide-theme @swup/forms-plugin @swup/debug-plugin
```

2. Add the following code to your app:

<details><summary><code>assets/controllers/swup_controller.js</code></summary>

```javascript
import { Controller } from '@hotwired/stimulus';
import Swup from 'swup';
import SwupFadeTheme from '@swup/fade-theme';
import SwupSlideTheme from '@swup/slide-theme';
import SwupFormsPlugin from '@swup/forms-plugin';
import SwupDebugPlugin from '@swup/debug-plugin';

export default class extends Controller {
    static values = {
        containers: Array,
        mainElement: String,
        animateHistoryBrowsing: Boolean,
        animationSelector: String,
        cache: Boolean,
        linkSelector: String,
        theme: String,
        debug: Boolean,
    };

    connect() {
        const dataContainers = this.containersValue;
        const mainElement = this.mainElementValue || dataContainers[0] || '#swup';
        const allElements = [mainElement].concat(dataContainers);
        const containersList = allElements.filter((item, index) => {
            return allElements.indexOf(item) === index;
        });

        const options = {
            containers: containersList,
            plugins: [
                'slide' === this.themeValue
                    ? new SwupSlideTheme({ mainElement: mainElement })
                    : new SwupFadeTheme({ mainElement: mainElement }),
                new SwupFormsPlugin(),
            ],
        };

        if (this.hasMainElementValue) {
            options.mainElement = this.mainElementValue;
        }

        if (this.hasAnimateHistoryBrowsingValue) {
            options.animateHistoryBrowsing = this.animateHistoryBrowsingValue;
        }
        if (this.hasAnimationSelectorValue) {
            options.animationSelector = this.animationSelectorValue;
        }
        if (this.hasCacheValue) {
            options.cache = this.cacheValue;
        }
        if (this.hasLinkSelectorValue) {
            options.linkSelector = this.linkSelectorValue;
        }
        if (this.debugValue) {
            options.plugins.push(new SwupDebugPlugin());
        }

        this.dispatchEvent('pre-connect', { options });
        const swup = new Swup(options);
        this.dispatchEvent('connect', { swup, options });
    }

    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'swup' });
    }
}
```

</details>

3. Replace the `symfony--ux-swup` occurrences in your templates with `swup`, for example:

```diff
-<body {{ stimulus_controller('symfony/ux-swup/swup') }}>
+<body {{ stimulus_controller('swup') }}>
```

4. Remove `symfony/ux-swup` from your dependencies:

```bash
composer remove symfony/ux-swup
```

You're done!

---

Symfony UX Swup is a Symfony bundle integrating [Swup](https://swup.js.org/) in
Symfony applications. It is part of [the Symfony UX initiative](https://ux.symfony.com/).

Swup is a complete and easy to use page transition library for Web applications. It creates
a Single Page Application feel to Web applications without having to change anything on the server
and without bringing the complexity of a React/Vue/Angular application.

**This repository is a READ-ONLY sub-tree split**. See
https://github.com/symfony/ux to create issues or submit pull requests.

## Sponsor

The Symfony UX packages are [backed][1] by [Mercure.rocks][2].

Create real-time experiences in minutes! Mercure.rocks provides a realtime API service
that is tightly integrated with Symfony: create UIs that update in live with UX Turbo,
send notifications with the Notifier component, expose async APIs with API Platform and
create low level stuffs with the Mercure component. We maintain and scale the complex
infrastructure for you!

Help Symfony by [sponsoring][3] its development!

## Resources

-   [Documentation](https://symfony.com/bundles/ux-swup/current/index.html)
-   [Report issues](https://github.com/symfony/ux/issues) and
    [send Pull Requests](https://github.com/symfony/ux/pulls)
    in the [main Symfony UX repository](https://github.com/symfony/ux)

[1]: https://symfony.com/backers
[2]: https://mercure.rocks
[3]: https://symfony.com/sponsor
