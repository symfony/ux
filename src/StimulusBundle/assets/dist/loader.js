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
        this.queryControllerNamesWithin(element).forEach((controllerName) => this.loadLazyController(controllerName));
    }
    async loadLazyController(name) {
        if (canRegisterController(name, this.application)) {
            if (this.lazyControllers[name] === undefined) {
                return;
            }
            const controllerModule = await this.lazyControllers[name]();
            registerController(name, controllerModule.default, this.application);
        }
    }
    lazyLoadNewControllers(element) {
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
    queryControllerNamesWithin(element) {
        return Array.from(element.querySelectorAll(`[${controllerAttribute}]`))
            .map(extractControllerNamesFrom)
            .flat();
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
