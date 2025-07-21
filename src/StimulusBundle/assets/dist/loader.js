import { Application } from "@hotwired/stimulus";
import { eagerControllers, isApplicationDebug, lazyControllers } from "./controllers.js";

//#region ../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/typeof.js
function _typeof(o) {
	"@babel/helpers - typeof";
	return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o$1) {
		return typeof o$1;
	} : function(o$1) {
		return o$1 && "function" == typeof Symbol && o$1.constructor === Symbol && o$1 !== Symbol.prototype ? "symbol" : typeof o$1;
	}, _typeof(o);
}

//#endregion
//#region ../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/toPrimitive.js
function toPrimitive(t, r) {
	if ("object" != _typeof(t) || !t) return t;
	var e = t[Symbol.toPrimitive];
	if (void 0 !== e) {
		var i = e.call(t, r || "default");
		if ("object" != _typeof(i)) return i;
		throw new TypeError("@@toPrimitive must return a primitive value.");
	}
	return ("string" === r ? String : Number)(t);
}

//#endregion
//#region ../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/toPropertyKey.js
function toPropertyKey(t) {
	var i = toPrimitive(t, "string");
	return "symbol" == _typeof(i) ? i : i + "";
}

//#endregion
//#region ../../../node_modules/.pnpm/@oxc-project+runtime@0.77.2/node_modules/@oxc-project/runtime/src/helpers/esm/defineProperty.js
function _defineProperty(e, r, t) {
	return (r = toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
		value: t,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[r] = t, e;
}

//#endregion
//#region src/loader.ts
const controllerAttribute = "data-controller";
const loadControllers = (application, eagerControllers$1, lazyControllers$1) => {
	for (const name in eagerControllers$1) registerController(name, eagerControllers$1[name], application);
	const lazyControllerHandler = new StimulusLazyControllerHandler(application, lazyControllers$1);
	lazyControllerHandler.start();
};
const startStimulusApp = () => {
	const application = Application.start();
	application.debug = isApplicationDebug;
	loadControllers(application, eagerControllers, lazyControllers);
	return application;
};
var StimulusLazyControllerHandler = class {
	constructor(application, lazyControllers$1) {
		_defineProperty(this, "application", void 0);
		_defineProperty(this, "lazyControllers", void 0);
		this.application = application;
		this.lazyControllers = lazyControllers$1;
	}
	start() {
		this.lazyLoadExistingControllers(document.documentElement);
		this.lazyLoadNewControllers(document.documentElement);
	}
	lazyLoadExistingControllers(element) {
		Array.from(element.querySelectorAll(`[${controllerAttribute}]`)).flatMap(extractControllerNamesFrom).forEach((controllerName) => this.loadLazyController(controllerName));
	}
	loadLazyController(name) {
		if (!this.lazyControllers[name]) return;
		const controllerLoader = this.lazyControllers[name];
		delete this.lazyControllers[name];
		if (!canRegisterController(name, this.application)) return;
		this.application.logDebugActivity(name, "lazy:loading");
		controllerLoader().then((controllerModule) => {
			this.application.logDebugActivity(name, "lazy:loaded");
			registerController(name, controllerModule.default, this.application);
		}).catch((error) => {
			console.error(`Error loading controller "${name}":`, error);
		});
	}
	lazyLoadNewControllers(element) {
		if (Object.keys(this.lazyControllers).length === 0) return;
		new MutationObserver((mutationsList) => {
			for (const { attributeName, target, type } of mutationsList) switch (type) {
				case "attributes": {
					if (attributeName === controllerAttribute && target.getAttribute(controllerAttribute)) extractControllerNamesFrom(target).forEach((controllerName) => this.loadLazyController(controllerName));
					break;
				}
				case "childList": this.lazyLoadExistingControllers(target);
			}
		}).observe(element, {
			attributeFilter: [controllerAttribute],
			subtree: true,
			childList: true
		});
	}
};
function registerController(name, controller, application) {
	if (canRegisterController(name, application)) application.register(name, controller);
}
function extractControllerNamesFrom(element) {
	const controllerNameValue = element.getAttribute(controllerAttribute);
	if (!controllerNameValue) return [];
	return controllerNameValue.split(/\s+/).filter((content) => content.length);
}
function canRegisterController(name, application) {
	return !application.router.modulesByIdentifier.has(name);
}

//#endregion
export { loadControllers, startStimulusApp };