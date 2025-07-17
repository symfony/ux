/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Starts the Stimulus application and reads a map dump in the DOM to load controllers.
 *
 * Inspired by stimulus-loading.js from stimulus-rails.
 */
import { Application, type ControllerConstructor } from '@hotwired/stimulus';
import {
    type EagerControllersCollection,
    eagerControllers,
    isApplicationDebug,
    type LazyControllersCollection,
    lazyControllers,
} from './controllers.js';

const controllerAttribute = 'data-controller';

export const loadControllers = (
    application: Application,
    eagerControllers: EagerControllersCollection,
    lazyControllers: LazyControllersCollection
) => {
    // loop over the controllers map and require each controller
    for (const name in eagerControllers) {
        registerController(name, eagerControllers[name], application);
    }

    const lazyControllerHandler: StimulusLazyControllerHandler = new StimulusLazyControllerHandler(
        application,
        lazyControllers
    );
    lazyControllerHandler.start();
};

export const startStimulusApp = (): Application => {
    const application = Application.start();
    application.debug = isApplicationDebug;

    loadControllers(application, eagerControllers, lazyControllers);

    return application;
};

class StimulusLazyControllerHandler {
    private readonly application: Application;
    private readonly lazyControllers: LazyControllersCollection;

    constructor(application: Application, lazyControllers: LazyControllersCollection) {
        this.application = application;
        this.lazyControllers = lazyControllers;
    }

    start(): void {
        this.lazyLoadExistingControllers(document.documentElement);
        this.lazyLoadNewControllers(document.documentElement);
    }

    private lazyLoadExistingControllers(element: Element) {
        Array.from(element.querySelectorAll(`[${controllerAttribute}]`))
            .flatMap(extractControllerNamesFrom)
            .forEach((controllerName) => this.loadLazyController(controllerName));
    }

    private loadLazyController(name: string) {
        if (!this.lazyControllers[name]) {
            return;
        }

        // Delete the loader to avoid loading it twice
        const controllerLoader = this.lazyControllers[name];
        delete this.lazyControllers[name];

        if (!canRegisterController(name, this.application)) {
            return;
        }

        this.application.logDebugActivity(name, 'lazy:loading');

        controllerLoader()
            .then((controllerModule) => {
                this.application.logDebugActivity(name, 'lazy:loaded');
                registerController(name, controllerModule.default, this.application);
            })
            .catch((error) => {
                console.error(`Error loading controller "${name}":`, error);
            });
    }

    private lazyLoadNewControllers(element: Element) {
        if (Object.keys(this.lazyControllers).length === 0) {
            return;
        }
        new MutationObserver((mutationsList) => {
            for (const { attributeName, target, type } of mutationsList) {
                switch (type) {
                    case 'attributes': {
                        if (
                            attributeName === controllerAttribute &&
                            (target as Element).getAttribute(controllerAttribute)
                        ) {
                            extractControllerNamesFrom(target as Element).forEach((controllerName) =>
                                this.loadLazyController(controllerName)
                            );
                        }

                        break;
                    }

                    case 'childList': {
                        this.lazyLoadExistingControllers(target as Element);
                    }
                }
            }
        }).observe(element, {
            attributeFilter: [controllerAttribute],
            subtree: true,
            childList: true,
        });
    }
}

function registerController(name: string, controller: ControllerConstructor, application: Application) {
    if (canRegisterController(name, application)) {
        application.register(name, controller);
    }
}

function extractControllerNamesFrom(element: Element): string[] {
    const controllerNameValue = element.getAttribute(controllerAttribute);

    if (!controllerNameValue) {
        return [];
    }

    return controllerNameValue.split(/\s+/).filter((content) => content.length);
}

function canRegisterController(name: string, application: Application) {
    // @ts-ignore
    return !application.router.modulesByIdentifier.has(name);
}
