import { Application } from '@hotwired/stimulus';
import { isApplicationDebug, eagerControllers, lazyControllers } from './controllers.js';

const controllerAttribute = 'data-controller';
const loadControllers = (application, eagerControllers, lazyControllers) => {
    for (const name in eagerControllers) {
        registerController(name, eagerControllers[name], application);
    }
    const lazyControllerHandler = new StimulusLazyControllerHandler(application, lazyControllers);
    lazyControllerHandler.start();
};
const startStimulusApp = () => {
    const application = Application.start();
    application.debug = isApplicationDebug;
    loadControllers(application, eagerControllers, lazyControllers);
    return application;
};
class StimulusLazyControllerHandler {
    constructor(application, lazyControllers) {
        this.application = application;
        this.lazyControllers = lazyControllers;
    }
    start() {
        this.lazyLoadExistingControllers(document.documentElement);
        this.lazyLoadNewControllers(document.documentElement);
    }
    lazyLoadExistingControllers(element) {
        Array.from(element.querySelectorAll(`[${controllerAttribute}]`))
            .flatMap(extractControllerNamesFrom)
            .forEach((controllerName) => this.loadLazyController(controllerName));
    }
    loadLazyController(name) {
        if (!this.lazyControllers[name]) {
            return;
        }
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
    lazyLoadNewControllers(element) {
        if (Object.keys(this.lazyControllers).length === 0) {
            return;
        }
        new MutationObserver((mutationsList) => {
            for (const { attributeName, target, type } of mutationsList) {
                switch (type) {
                    case 'attributes': {
                        if (attributeName === controllerAttribute &&
                            target.getAttribute(controllerAttribute)) {
                            extractControllerNamesFrom(target).forEach((controllerName) => this.loadLazyController(controllerName));
                        }
                        break;
                    }
                    case 'childList': {
                        this.lazyLoadExistingControllers(target);
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
function registerController(name, controller, application) {
    if (canRegisterController(name, application)) {
        application.register(name, controller);
    }
}
function extractControllerNamesFrom(element) {
    const controllerNameValue = element.getAttribute(controllerAttribute);
    if (!controllerNameValue) {
        return [];
    }
    return controllerNameValue.split(/\s+/).filter((content) => content.length);
}
function canRegisterController(name, application) {
    return !application.router.modulesByIdentifier.has(name);
}

export { loadControllers, startStimulusApp };
