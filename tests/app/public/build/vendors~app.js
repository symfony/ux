(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["vendors~app"],{

/***/ "./node_modules/@stimulus/core/dist/action.js":
/*!****************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/action.js ***!
  \****************************************************/
/*! exports provided: Action, getDefaultEventNameForElement */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Action", function() { return Action; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getDefaultEventNameForElement", function() { return getDefaultEventNameForElement; });
/* harmony import */ var _action_descriptor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./action_descriptor */ "./node_modules/@stimulus/core/dist/action_descriptor.js");

var Action = /** @class */ (function () {
    function Action(element, index, descriptor) {
        this.element = element;
        this.index = index;
        this.eventTarget = descriptor.eventTarget || element;
        this.eventName = descriptor.eventName || getDefaultEventNameForElement(element) || error("missing event name");
        this.eventOptions = descriptor.eventOptions || {};
        this.identifier = descriptor.identifier || error("missing identifier");
        this.methodName = descriptor.methodName || error("missing method name");
    }
    Action.forToken = function (token) {
        return new this(token.element, token.index, Object(_action_descriptor__WEBPACK_IMPORTED_MODULE_0__["parseActionDescriptorString"])(token.content));
    };
    Action.prototype.toString = function () {
        var eventNameSuffix = this.eventTargetName ? "@" + this.eventTargetName : "";
        return "" + this.eventName + eventNameSuffix + "->" + this.identifier + "#" + this.methodName;
    };
    Object.defineProperty(Action.prototype, "eventTargetName", {
        get: function () {
            return Object(_action_descriptor__WEBPACK_IMPORTED_MODULE_0__["stringifyEventTarget"])(this.eventTarget);
        },
        enumerable: false,
        configurable: true
    });
    return Action;
}());

var defaultEventNames = {
    "a": function (e) { return "click"; },
    "button": function (e) { return "click"; },
    "form": function (e) { return "submit"; },
    "input": function (e) { return e.getAttribute("type") == "submit" ? "click" : "input"; },
    "select": function (e) { return "change"; },
    "textarea": function (e) { return "input"; }
};
function getDefaultEventNameForElement(element) {
    var tagName = element.tagName.toLowerCase();
    if (tagName in defaultEventNames) {
        return defaultEventNames[tagName](element);
    }
}
function error(message) {
    throw new Error(message);
}
//# sourceMappingURL=action.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/action_descriptor.js":
/*!***************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/action_descriptor.js ***!
  \***************************************************************/
/*! exports provided: parseActionDescriptorString, stringifyEventTarget */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "parseActionDescriptorString", function() { return parseActionDescriptorString; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "stringifyEventTarget", function() { return stringifyEventTarget; });
// capture nos.:            12   23 4               43   1 5   56 7      768 9  98
var descriptorPattern = /^((.+?)(@(window|document))?->)?(.+?)(#([^:]+?))(:(.+))?$/;
function parseActionDescriptorString(descriptorString) {
    var source = descriptorString.trim();
    var matches = source.match(descriptorPattern) || [];
    return {
        eventTarget: parseEventTarget(matches[4]),
        eventName: matches[2],
        eventOptions: matches[9] ? parseEventOptions(matches[9]) : {},
        identifier: matches[5],
        methodName: matches[7]
    };
}
function parseEventTarget(eventTargetName) {
    if (eventTargetName == "window") {
        return window;
    }
    else if (eventTargetName == "document") {
        return document;
    }
}
function parseEventOptions(eventOptions) {
    return eventOptions.split(":").reduce(function (options, token) {
        var _a;
        return Object.assign(options, (_a = {}, _a[token.replace(/^!/, "")] = !/^!/.test(token), _a));
    }, {});
}
function stringifyEventTarget(eventTarget) {
    if (eventTarget == window) {
        return "window";
    }
    else if (eventTarget == document) {
        return "document";
    }
}
//# sourceMappingURL=action_descriptor.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/application.js":
/*!*********************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/application.js ***!
  \*********************************************************/
/*! exports provided: Application */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Application", function() { return Application; });
/* harmony import */ var _dispatcher__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./dispatcher */ "./node_modules/@stimulus/core/dist/dispatcher.js");
/* harmony import */ var _router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./router */ "./node_modules/@stimulus/core/dist/router.js");
/* harmony import */ var _schema__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./schema */ "./node_modules/@stimulus/core/dist/schema.js");
var __awaiter = (undefined && undefined.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (undefined && undefined.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __spreadArrays = (undefined && undefined.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};



var Application = /** @class */ (function () {
    function Application(element, schema) {
        if (element === void 0) { element = document.documentElement; }
        if (schema === void 0) { schema = _schema__WEBPACK_IMPORTED_MODULE_2__["defaultSchema"]; }
        this.logger = console;
        this.element = element;
        this.schema = schema;
        this.dispatcher = new _dispatcher__WEBPACK_IMPORTED_MODULE_0__["Dispatcher"](this);
        this.router = new _router__WEBPACK_IMPORTED_MODULE_1__["Router"](this);
    }
    Application.start = function (element, schema) {
        var application = new Application(element, schema);
        application.start();
        return application;
    };
    Application.prototype.start = function () {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, domReady()];
                    case 1:
                        _a.sent();
                        this.dispatcher.start();
                        this.router.start();
                        return [2 /*return*/];
                }
            });
        });
    };
    Application.prototype.stop = function () {
        this.dispatcher.stop();
        this.router.stop();
    };
    Application.prototype.register = function (identifier, controllerConstructor) {
        this.load({ identifier: identifier, controllerConstructor: controllerConstructor });
    };
    Application.prototype.load = function (head) {
        var _this = this;
        var rest = [];
        for (var _i = 1; _i < arguments.length; _i++) {
            rest[_i - 1] = arguments[_i];
        }
        var definitions = Array.isArray(head) ? head : __spreadArrays([head], rest);
        definitions.forEach(function (definition) { return _this.router.loadDefinition(definition); });
    };
    Application.prototype.unload = function (head) {
        var _this = this;
        var rest = [];
        for (var _i = 1; _i < arguments.length; _i++) {
            rest[_i - 1] = arguments[_i];
        }
        var identifiers = Array.isArray(head) ? head : __spreadArrays([head], rest);
        identifiers.forEach(function (identifier) { return _this.router.unloadIdentifier(identifier); });
    };
    Object.defineProperty(Application.prototype, "controllers", {
        // Controllers
        get: function () {
            return this.router.contexts.map(function (context) { return context.controller; });
        },
        enumerable: false,
        configurable: true
    });
    Application.prototype.getControllerForElementAndIdentifier = function (element, identifier) {
        var context = this.router.getContextForElementAndIdentifier(element, identifier);
        return context ? context.controller : null;
    };
    // Error handling
    Application.prototype.handleError = function (error, message, detail) {
        this.logger.error("%s\n\n%o\n\n%o", message, error, detail);
    };
    return Application;
}());

function domReady() {
    return new Promise(function (resolve) {
        if (document.readyState == "loading") {
            document.addEventListener("DOMContentLoaded", resolve);
        }
        else {
            resolve();
        }
    });
}
//# sourceMappingURL=application.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/binding.js":
/*!*****************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/binding.js ***!
  \*****************************************************/
/*! exports provided: Binding */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Binding", function() { return Binding; });
var Binding = /** @class */ (function () {
    function Binding(context, action) {
        this.context = context;
        this.action = action;
    }
    Object.defineProperty(Binding.prototype, "index", {
        get: function () {
            return this.action.index;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "eventTarget", {
        get: function () {
            return this.action.eventTarget;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "eventOptions", {
        get: function () {
            return this.action.eventOptions;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "identifier", {
        get: function () {
            return this.context.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Binding.prototype.handleEvent = function (event) {
        if (this.willBeInvokedByEvent(event)) {
            this.invokeWithEvent(event);
        }
    };
    Object.defineProperty(Binding.prototype, "eventName", {
        get: function () {
            return this.action.eventName;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "method", {
        get: function () {
            var method = this.controller[this.methodName];
            if (typeof method == "function") {
                return method;
            }
            throw new Error("Action \"" + this.action + "\" references undefined method \"" + this.methodName + "\"");
        },
        enumerable: false,
        configurable: true
    });
    Binding.prototype.invokeWithEvent = function (event) {
        try {
            this.method.call(this.controller, event);
        }
        catch (error) {
            var _a = this, identifier = _a.identifier, controller = _a.controller, element = _a.element, index = _a.index;
            var detail = { identifier: identifier, controller: controller, element: element, index: index, event: event };
            this.context.handleError(error, "invoking action \"" + this.action + "\"", detail);
        }
    };
    Binding.prototype.willBeInvokedByEvent = function (event) {
        var eventTarget = event.target;
        if (this.element === eventTarget) {
            return true;
        }
        else if (eventTarget instanceof Element && this.element.contains(eventTarget)) {
            return this.scope.containsElement(eventTarget);
        }
        else {
            return this.scope.containsElement(this.action.element);
        }
    };
    Object.defineProperty(Binding.prototype, "controller", {
        get: function () {
            return this.context.controller;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "methodName", {
        get: function () {
            return this.action.methodName;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "scope", {
        get: function () {
            return this.context.scope;
        },
        enumerable: false,
        configurable: true
    });
    return Binding;
}());

//# sourceMappingURL=binding.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/binding_observer.js":
/*!**************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/binding_observer.js ***!
  \**************************************************************/
/*! exports provided: BindingObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BindingObserver", function() { return BindingObserver; });
/* harmony import */ var _action__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./action */ "./node_modules/@stimulus/core/dist/action.js");
/* harmony import */ var _binding__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./binding */ "./node_modules/@stimulus/core/dist/binding.js");
/* harmony import */ var _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @stimulus/mutation-observers */ "./node_modules/@stimulus/mutation-observers/dist/index.js");



var BindingObserver = /** @class */ (function () {
    function BindingObserver(context, delegate) {
        this.context = context;
        this.delegate = delegate;
        this.bindingsByAction = new Map;
    }
    BindingObserver.prototype.start = function () {
        if (!this.valueListObserver) {
            this.valueListObserver = new _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_2__["ValueListObserver"](this.element, this.actionAttribute, this);
            this.valueListObserver.start();
        }
    };
    BindingObserver.prototype.stop = function () {
        if (this.valueListObserver) {
            this.valueListObserver.stop();
            delete this.valueListObserver;
            this.disconnectAllActions();
        }
    };
    Object.defineProperty(BindingObserver.prototype, "element", {
        get: function () {
            return this.context.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(BindingObserver.prototype, "identifier", {
        get: function () {
            return this.context.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(BindingObserver.prototype, "actionAttribute", {
        get: function () {
            return this.schema.actionAttribute;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(BindingObserver.prototype, "schema", {
        get: function () {
            return this.context.schema;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(BindingObserver.prototype, "bindings", {
        get: function () {
            return Array.from(this.bindingsByAction.values());
        },
        enumerable: false,
        configurable: true
    });
    BindingObserver.prototype.connectAction = function (action) {
        var binding = new _binding__WEBPACK_IMPORTED_MODULE_1__["Binding"](this.context, action);
        this.bindingsByAction.set(action, binding);
        this.delegate.bindingConnected(binding);
    };
    BindingObserver.prototype.disconnectAction = function (action) {
        var binding = this.bindingsByAction.get(action);
        if (binding) {
            this.bindingsByAction.delete(action);
            this.delegate.bindingDisconnected(binding);
        }
    };
    BindingObserver.prototype.disconnectAllActions = function () {
        var _this = this;
        this.bindings.forEach(function (binding) { return _this.delegate.bindingDisconnected(binding); });
        this.bindingsByAction.clear();
    };
    // Value observer delegate
    BindingObserver.prototype.parseValueForToken = function (token) {
        var action = _action__WEBPACK_IMPORTED_MODULE_0__["Action"].forToken(token);
        if (action.identifier == this.identifier) {
            return action;
        }
    };
    BindingObserver.prototype.elementMatchedValue = function (element, action) {
        this.connectAction(action);
    };
    BindingObserver.prototype.elementUnmatchedValue = function (element, action) {
        this.disconnectAction(action);
    };
    return BindingObserver;
}());

//# sourceMappingURL=binding_observer.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/blessing.js":
/*!******************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/blessing.js ***!
  \******************************************************/
/*! exports provided: bless */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "bless", function() { return bless; });
/* harmony import */ var _inheritable_statics__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./inheritable_statics */ "./node_modules/@stimulus/core/dist/inheritable_statics.js");
var __extends = (undefined && undefined.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __spreadArrays = (undefined && undefined.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};

/** @hidden */
function bless(constructor) {
    return shadow(constructor, getBlessedProperties(constructor));
}
function shadow(constructor, properties) {
    var shadowConstructor = extend(constructor);
    var shadowProperties = getShadowProperties(constructor.prototype, properties);
    Object.defineProperties(shadowConstructor.prototype, shadowProperties);
    return shadowConstructor;
}
function getBlessedProperties(constructor) {
    var blessings = Object(_inheritable_statics__WEBPACK_IMPORTED_MODULE_0__["readInheritableStaticArrayValues"])(constructor, "blessings");
    return blessings.reduce(function (blessedProperties, blessing) {
        var properties = blessing(constructor);
        for (var key in properties) {
            var descriptor = blessedProperties[key] || {};
            blessedProperties[key] = Object.assign(descriptor, properties[key]);
        }
        return blessedProperties;
    }, {});
}
function getShadowProperties(prototype, properties) {
    return getOwnKeys(properties).reduce(function (shadowProperties, key) {
        var _a;
        var descriptor = getShadowedDescriptor(prototype, properties, key);
        if (descriptor) {
            Object.assign(shadowProperties, (_a = {}, _a[key] = descriptor, _a));
        }
        return shadowProperties;
    }, {});
}
function getShadowedDescriptor(prototype, properties, key) {
    var shadowingDescriptor = Object.getOwnPropertyDescriptor(prototype, key);
    var shadowedByValue = shadowingDescriptor && "value" in shadowingDescriptor;
    if (!shadowedByValue) {
        var descriptor = Object.getOwnPropertyDescriptor(properties, key).value;
        if (shadowingDescriptor) {
            descriptor.get = shadowingDescriptor.get || descriptor.get;
            descriptor.set = shadowingDescriptor.set || descriptor.set;
        }
        return descriptor;
    }
}
var getOwnKeys = (function () {
    if (typeof Object.getOwnPropertySymbols == "function") {
        return function (object) { return __spreadArrays(Object.getOwnPropertyNames(object), Object.getOwnPropertySymbols(object)); };
    }
    else {
        return Object.getOwnPropertyNames;
    }
})();
var extend = (function () {
    function extendWithReflect(constructor) {
        function extended() {
            var _newTarget = this && this instanceof extended ? this.constructor : void 0;
            return Reflect.construct(constructor, arguments, _newTarget);
        }
        extended.prototype = Object.create(constructor.prototype, {
            constructor: { value: extended }
        });
        Reflect.setPrototypeOf(extended, constructor);
        return extended;
    }
    function testReflectExtension() {
        var a = function () { this.a.call(this); };
        var b = extendWithReflect(a);
        b.prototype.a = function () { };
        return new b;
    }
    try {
        testReflectExtension();
        return extendWithReflect;
    }
    catch (error) {
        return function (constructor) { return /** @class */ (function (_super) {
            __extends(extended, _super);
            function extended() {
                return _super !== null && _super.apply(this, arguments) || this;
            }
            return extended;
        }(constructor)); };
    }
})();
//# sourceMappingURL=blessing.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/class_map.js":
/*!*******************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/class_map.js ***!
  \*******************************************************/
/*! exports provided: ClassMap */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ClassMap", function() { return ClassMap; });
var ClassMap = /** @class */ (function () {
    function ClassMap(scope) {
        this.scope = scope;
    }
    ClassMap.prototype.has = function (name) {
        return this.data.has(this.getDataKey(name));
    };
    ClassMap.prototype.get = function (name) {
        return this.data.get(this.getDataKey(name));
    };
    ClassMap.prototype.getAttributeName = function (name) {
        return this.data.getAttributeNameForKey(this.getDataKey(name));
    };
    ClassMap.prototype.getDataKey = function (name) {
        return name + "-class";
    };
    Object.defineProperty(ClassMap.prototype, "data", {
        get: function () {
            return this.scope.data;
        },
        enumerable: false,
        configurable: true
    });
    return ClassMap;
}());

//# sourceMappingURL=class_map.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/class_properties.js":
/*!**************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/class_properties.js ***!
  \**************************************************************/
/*! exports provided: ClassPropertiesBlessing */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ClassPropertiesBlessing", function() { return ClassPropertiesBlessing; });
/* harmony import */ var _inheritable_statics__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./inheritable_statics */ "./node_modules/@stimulus/core/dist/inheritable_statics.js");
/* harmony import */ var _string_helpers__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./string_helpers */ "./node_modules/@stimulus/core/dist/string_helpers.js");


/** @hidden */
function ClassPropertiesBlessing(constructor) {
    var classes = Object(_inheritable_statics__WEBPACK_IMPORTED_MODULE_0__["readInheritableStaticArrayValues"])(constructor, "classes");
    return classes.reduce(function (properties, classDefinition) {
        return Object.assign(properties, propertiesForClassDefinition(classDefinition));
    }, {});
}
function propertiesForClassDefinition(key) {
    var _a;
    var name = key + "Class";
    return _a = {},
        _a[name] = {
            get: function () {
                var classes = this.classes;
                if (classes.has(key)) {
                    return classes.get(key);
                }
                else {
                    var attribute = classes.getAttributeName(key);
                    throw new Error("Missing attribute \"" + attribute + "\"");
                }
            }
        },
        _a["has" + Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["capitalize"])(name)] = {
            get: function () {
                return this.classes.has(key);
            }
        },
        _a;
}
//# sourceMappingURL=class_properties.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/context.js":
/*!*****************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/context.js ***!
  \*****************************************************/
/*! exports provided: Context */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Context", function() { return Context; });
/* harmony import */ var _binding_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./binding_observer */ "./node_modules/@stimulus/core/dist/binding_observer.js");
/* harmony import */ var _value_observer__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./value_observer */ "./node_modules/@stimulus/core/dist/value_observer.js");


var Context = /** @class */ (function () {
    function Context(module, scope) {
        this.module = module;
        this.scope = scope;
        this.controller = new module.controllerConstructor(this);
        this.bindingObserver = new _binding_observer__WEBPACK_IMPORTED_MODULE_0__["BindingObserver"](this, this.dispatcher);
        this.valueObserver = new _value_observer__WEBPACK_IMPORTED_MODULE_1__["ValueObserver"](this, this.controller);
        try {
            this.controller.initialize();
        }
        catch (error) {
            this.handleError(error, "initializing controller");
        }
    }
    Context.prototype.connect = function () {
        this.bindingObserver.start();
        this.valueObserver.start();
        try {
            this.controller.connect();
        }
        catch (error) {
            this.handleError(error, "connecting controller");
        }
    };
    Context.prototype.disconnect = function () {
        try {
            this.controller.disconnect();
        }
        catch (error) {
            this.handleError(error, "disconnecting controller");
        }
        this.valueObserver.stop();
        this.bindingObserver.stop();
    };
    Object.defineProperty(Context.prototype, "application", {
        get: function () {
            return this.module.application;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "identifier", {
        get: function () {
            return this.module.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "schema", {
        get: function () {
            return this.application.schema;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "dispatcher", {
        get: function () {
            return this.application.dispatcher;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "parentElement", {
        get: function () {
            return this.element.parentElement;
        },
        enumerable: false,
        configurable: true
    });
    // Error handling
    Context.prototype.handleError = function (error, message, detail) {
        if (detail === void 0) { detail = {}; }
        var _a = this, identifier = _a.identifier, controller = _a.controller, element = _a.element;
        detail = Object.assign({ identifier: identifier, controller: controller, element: element }, detail);
        this.application.handleError(error, "Error " + message, detail);
    };
    return Context;
}());

//# sourceMappingURL=context.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/controller.js":
/*!********************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/controller.js ***!
  \********************************************************/
/*! exports provided: Controller */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Controller", function() { return Controller; });
/* harmony import */ var _class_properties__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./class_properties */ "./node_modules/@stimulus/core/dist/class_properties.js");
/* harmony import */ var _target_properties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./target_properties */ "./node_modules/@stimulus/core/dist/target_properties.js");
/* harmony import */ var _value_properties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./value_properties */ "./node_modules/@stimulus/core/dist/value_properties.js");



var Controller = /** @class */ (function () {
    function Controller(context) {
        this.context = context;
    }
    Object.defineProperty(Controller.prototype, "application", {
        get: function () {
            return this.context.application;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "scope", {
        get: function () {
            return this.context.scope;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "identifier", {
        get: function () {
            return this.scope.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "targets", {
        get: function () {
            return this.scope.targets;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "classes", {
        get: function () {
            return this.scope.classes;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "data", {
        get: function () {
            return this.scope.data;
        },
        enumerable: false,
        configurable: true
    });
    Controller.prototype.initialize = function () {
        // Override in your subclass to set up initial controller state
    };
    Controller.prototype.connect = function () {
        // Override in your subclass to respond when the controller is connected to the DOM
    };
    Controller.prototype.disconnect = function () {
        // Override in your subclass to respond when the controller is disconnected from the DOM
    };
    Controller.blessings = [_class_properties__WEBPACK_IMPORTED_MODULE_0__["ClassPropertiesBlessing"], _target_properties__WEBPACK_IMPORTED_MODULE_1__["TargetPropertiesBlessing"], _value_properties__WEBPACK_IMPORTED_MODULE_2__["ValuePropertiesBlessing"]];
    Controller.targets = [];
    Controller.values = {};
    return Controller;
}());

//# sourceMappingURL=controller.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/data_map.js":
/*!******************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/data_map.js ***!
  \******************************************************/
/*! exports provided: DataMap */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DataMap", function() { return DataMap; });
/* harmony import */ var _string_helpers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string_helpers */ "./node_modules/@stimulus/core/dist/string_helpers.js");

var DataMap = /** @class */ (function () {
    function DataMap(scope) {
        this.scope = scope;
    }
    Object.defineProperty(DataMap.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(DataMap.prototype, "identifier", {
        get: function () {
            return this.scope.identifier;
        },
        enumerable: false,
        configurable: true
    });
    DataMap.prototype.get = function (key) {
        var name = this.getAttributeNameForKey(key);
        return this.element.getAttribute(name);
    };
    DataMap.prototype.set = function (key, value) {
        var name = this.getAttributeNameForKey(key);
        this.element.setAttribute(name, value);
        return this.get(key);
    };
    DataMap.prototype.has = function (key) {
        var name = this.getAttributeNameForKey(key);
        return this.element.hasAttribute(name);
    };
    DataMap.prototype.delete = function (key) {
        if (this.has(key)) {
            var name_1 = this.getAttributeNameForKey(key);
            this.element.removeAttribute(name_1);
            return true;
        }
        else {
            return false;
        }
    };
    DataMap.prototype.getAttributeNameForKey = function (key) {
        return "data-" + this.identifier + "-" + Object(_string_helpers__WEBPACK_IMPORTED_MODULE_0__["dasherize"])(key);
    };
    return DataMap;
}());

//# sourceMappingURL=data_map.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/definition.js":
/*!********************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/definition.js ***!
  \********************************************************/
/*! exports provided: blessDefinition */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "blessDefinition", function() { return blessDefinition; });
/* harmony import */ var _blessing__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./blessing */ "./node_modules/@stimulus/core/dist/blessing.js");

/** @hidden */
function blessDefinition(definition) {
    return {
        identifier: definition.identifier,
        controllerConstructor: Object(_blessing__WEBPACK_IMPORTED_MODULE_0__["bless"])(definition.controllerConstructor)
    };
}
//# sourceMappingURL=definition.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/dispatcher.js":
/*!********************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/dispatcher.js ***!
  \********************************************************/
/*! exports provided: Dispatcher */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Dispatcher", function() { return Dispatcher; });
/* harmony import */ var _event_listener__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./event_listener */ "./node_modules/@stimulus/core/dist/event_listener.js");

var Dispatcher = /** @class */ (function () {
    function Dispatcher(application) {
        this.application = application;
        this.eventListenerMaps = new Map;
        this.started = false;
    }
    Dispatcher.prototype.start = function () {
        if (!this.started) {
            this.started = true;
            this.eventListeners.forEach(function (eventListener) { return eventListener.connect(); });
        }
    };
    Dispatcher.prototype.stop = function () {
        if (this.started) {
            this.started = false;
            this.eventListeners.forEach(function (eventListener) { return eventListener.disconnect(); });
        }
    };
    Object.defineProperty(Dispatcher.prototype, "eventListeners", {
        get: function () {
            return Array.from(this.eventListenerMaps.values())
                .reduce(function (listeners, map) { return listeners.concat(Array.from(map.values())); }, []);
        },
        enumerable: false,
        configurable: true
    });
    // Binding observer delegate
    /** @hidden */
    Dispatcher.prototype.bindingConnected = function (binding) {
        this.fetchEventListenerForBinding(binding).bindingConnected(binding);
    };
    /** @hidden */
    Dispatcher.prototype.bindingDisconnected = function (binding) {
        this.fetchEventListenerForBinding(binding).bindingDisconnected(binding);
    };
    // Error handling
    Dispatcher.prototype.handleError = function (error, message, detail) {
        if (detail === void 0) { detail = {}; }
        this.application.handleError(error, "Error " + message, detail);
    };
    Dispatcher.prototype.fetchEventListenerForBinding = function (binding) {
        var eventTarget = binding.eventTarget, eventName = binding.eventName, eventOptions = binding.eventOptions;
        return this.fetchEventListener(eventTarget, eventName, eventOptions);
    };
    Dispatcher.prototype.fetchEventListener = function (eventTarget, eventName, eventOptions) {
        var eventListenerMap = this.fetchEventListenerMapForEventTarget(eventTarget);
        var cacheKey = this.cacheKey(eventName, eventOptions);
        var eventListener = eventListenerMap.get(cacheKey);
        if (!eventListener) {
            eventListener = this.createEventListener(eventTarget, eventName, eventOptions);
            eventListenerMap.set(cacheKey, eventListener);
        }
        return eventListener;
    };
    Dispatcher.prototype.createEventListener = function (eventTarget, eventName, eventOptions) {
        var eventListener = new _event_listener__WEBPACK_IMPORTED_MODULE_0__["EventListener"](eventTarget, eventName, eventOptions);
        if (this.started) {
            eventListener.connect();
        }
        return eventListener;
    };
    Dispatcher.prototype.fetchEventListenerMapForEventTarget = function (eventTarget) {
        var eventListenerMap = this.eventListenerMaps.get(eventTarget);
        if (!eventListenerMap) {
            eventListenerMap = new Map;
            this.eventListenerMaps.set(eventTarget, eventListenerMap);
        }
        return eventListenerMap;
    };
    Dispatcher.prototype.cacheKey = function (eventName, eventOptions) {
        var parts = [eventName];
        Object.keys(eventOptions).sort().forEach(function (key) {
            parts.push("" + (eventOptions[key] ? "" : "!") + key);
        });
        return parts.join(":");
    };
    return Dispatcher;
}());

//# sourceMappingURL=dispatcher.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/event_listener.js":
/*!************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/event_listener.js ***!
  \************************************************************/
/*! exports provided: EventListener */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "EventListener", function() { return EventListener; });
var EventListener = /** @class */ (function () {
    function EventListener(eventTarget, eventName, eventOptions) {
        this.eventTarget = eventTarget;
        this.eventName = eventName;
        this.eventOptions = eventOptions;
        this.unorderedBindings = new Set();
    }
    EventListener.prototype.connect = function () {
        this.eventTarget.addEventListener(this.eventName, this, this.eventOptions);
    };
    EventListener.prototype.disconnect = function () {
        this.eventTarget.removeEventListener(this.eventName, this, this.eventOptions);
    };
    // Binding observer delegate
    /** @hidden */
    EventListener.prototype.bindingConnected = function (binding) {
        this.unorderedBindings.add(binding);
    };
    /** @hidden */
    EventListener.prototype.bindingDisconnected = function (binding) {
        this.unorderedBindings.delete(binding);
    };
    EventListener.prototype.handleEvent = function (event) {
        var extendedEvent = extendEvent(event);
        for (var _i = 0, _a = this.bindings; _i < _a.length; _i++) {
            var binding = _a[_i];
            if (extendedEvent.immediatePropagationStopped) {
                break;
            }
            else {
                binding.handleEvent(extendedEvent);
            }
        }
    };
    Object.defineProperty(EventListener.prototype, "bindings", {
        get: function () {
            return Array.from(this.unorderedBindings).sort(function (left, right) {
                var leftIndex = left.index, rightIndex = right.index;
                return leftIndex < rightIndex ? -1 : leftIndex > rightIndex ? 1 : 0;
            });
        },
        enumerable: false,
        configurable: true
    });
    return EventListener;
}());

function extendEvent(event) {
    if ("immediatePropagationStopped" in event) {
        return event;
    }
    else {
        var stopImmediatePropagation_1 = event.stopImmediatePropagation;
        return Object.assign(event, {
            immediatePropagationStopped: false,
            stopImmediatePropagation: function () {
                this.immediatePropagationStopped = true;
                stopImmediatePropagation_1.call(this);
            }
        });
    }
}
//# sourceMappingURL=event_listener.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/guide.js":
/*!***************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/guide.js ***!
  \***************************************************/
/*! exports provided: Guide */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Guide", function() { return Guide; });
var Guide = /** @class */ (function () {
    function Guide(logger) {
        this.warnedKeysByObject = new WeakMap;
        this.logger = logger;
    }
    Guide.prototype.warn = function (object, key, message) {
        var warnedKeys = this.warnedKeysByObject.get(object);
        if (!warnedKeys) {
            warnedKeys = new Set;
            this.warnedKeysByObject.set(object, warnedKeys);
        }
        if (!warnedKeys.has(key)) {
            warnedKeys.add(key);
            this.logger.warn(message, object);
        }
    };
    return Guide;
}());

//# sourceMappingURL=guide.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/index.js":
/*!***************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/index.js ***!
  \***************************************************/
/*! exports provided: Application, Context, Controller, defaultSchema */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _application__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./application */ "./node_modules/@stimulus/core/dist/application.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Application", function() { return _application__WEBPACK_IMPORTED_MODULE_0__["Application"]; });

/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./context */ "./node_modules/@stimulus/core/dist/context.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Context", function() { return _context__WEBPACK_IMPORTED_MODULE_1__["Context"]; });

/* harmony import */ var _controller__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./controller */ "./node_modules/@stimulus/core/dist/controller.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Controller", function() { return _controller__WEBPACK_IMPORTED_MODULE_2__["Controller"]; });

/* harmony import */ var _schema__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./schema */ "./node_modules/@stimulus/core/dist/schema.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "defaultSchema", function() { return _schema__WEBPACK_IMPORTED_MODULE_3__["defaultSchema"]; });





//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/inheritable_statics.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/inheritable_statics.js ***!
  \*****************************************************************/
/*! exports provided: readInheritableStaticArrayValues, readInheritableStaticObjectPairs */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "readInheritableStaticArrayValues", function() { return readInheritableStaticArrayValues; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "readInheritableStaticObjectPairs", function() { return readInheritableStaticObjectPairs; });
function readInheritableStaticArrayValues(constructor, propertyName) {
    var ancestors = getAncestorsForConstructor(constructor);
    return Array.from(ancestors.reduce(function (values, constructor) {
        getOwnStaticArrayValues(constructor, propertyName).forEach(function (name) { return values.add(name); });
        return values;
    }, new Set));
}
function readInheritableStaticObjectPairs(constructor, propertyName) {
    var ancestors = getAncestorsForConstructor(constructor);
    return ancestors.reduce(function (pairs, constructor) {
        pairs.push.apply(pairs, getOwnStaticObjectPairs(constructor, propertyName));
        return pairs;
    }, []);
}
function getAncestorsForConstructor(constructor) {
    var ancestors = [];
    while (constructor) {
        ancestors.push(constructor);
        constructor = Object.getPrototypeOf(constructor);
    }
    return ancestors.reverse();
}
function getOwnStaticArrayValues(constructor, propertyName) {
    var definition = constructor[propertyName];
    return Array.isArray(definition) ? definition : [];
}
function getOwnStaticObjectPairs(constructor, propertyName) {
    var definition = constructor[propertyName];
    return definition ? Object.keys(definition).map(function (key) { return [key, definition[key]]; }) : [];
}
//# sourceMappingURL=inheritable_statics.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/module.js":
/*!****************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/module.js ***!
  \****************************************************/
/*! exports provided: Module */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Module", function() { return Module; });
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./context */ "./node_modules/@stimulus/core/dist/context.js");
/* harmony import */ var _definition__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./definition */ "./node_modules/@stimulus/core/dist/definition.js");


var Module = /** @class */ (function () {
    function Module(application, definition) {
        this.application = application;
        this.definition = Object(_definition__WEBPACK_IMPORTED_MODULE_1__["blessDefinition"])(definition);
        this.contextsByScope = new WeakMap;
        this.connectedContexts = new Set;
    }
    Object.defineProperty(Module.prototype, "identifier", {
        get: function () {
            return this.definition.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Module.prototype, "controllerConstructor", {
        get: function () {
            return this.definition.controllerConstructor;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Module.prototype, "contexts", {
        get: function () {
            return Array.from(this.connectedContexts);
        },
        enumerable: false,
        configurable: true
    });
    Module.prototype.connectContextForScope = function (scope) {
        var context = this.fetchContextForScope(scope);
        this.connectedContexts.add(context);
        context.connect();
    };
    Module.prototype.disconnectContextForScope = function (scope) {
        var context = this.contextsByScope.get(scope);
        if (context) {
            this.connectedContexts.delete(context);
            context.disconnect();
        }
    };
    Module.prototype.fetchContextForScope = function (scope) {
        var context = this.contextsByScope.get(scope);
        if (!context) {
            context = new _context__WEBPACK_IMPORTED_MODULE_0__["Context"](this, scope);
            this.contextsByScope.set(scope, context);
        }
        return context;
    };
    return Module;
}());

//# sourceMappingURL=module.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/router.js":
/*!****************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/router.js ***!
  \****************************************************/
/*! exports provided: Router */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Router", function() { return Router; });
/* harmony import */ var _module__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./module */ "./node_modules/@stimulus/core/dist/module.js");
/* harmony import */ var _stimulus_multimap__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @stimulus/multimap */ "./node_modules/@stimulus/multimap/dist/index.js");
/* harmony import */ var _scope__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./scope */ "./node_modules/@stimulus/core/dist/scope.js");
/* harmony import */ var _scope_observer__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./scope_observer */ "./node_modules/@stimulus/core/dist/scope_observer.js");




var Router = /** @class */ (function () {
    function Router(application) {
        this.application = application;
        this.scopeObserver = new _scope_observer__WEBPACK_IMPORTED_MODULE_3__["ScopeObserver"](this.element, this.schema, this);
        this.scopesByIdentifier = new _stimulus_multimap__WEBPACK_IMPORTED_MODULE_1__["Multimap"];
        this.modulesByIdentifier = new Map;
    }
    Object.defineProperty(Router.prototype, "element", {
        get: function () {
            return this.application.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "schema", {
        get: function () {
            return this.application.schema;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "logger", {
        get: function () {
            return this.application.logger;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "controllerAttribute", {
        get: function () {
            return this.schema.controllerAttribute;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "modules", {
        get: function () {
            return Array.from(this.modulesByIdentifier.values());
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "contexts", {
        get: function () {
            return this.modules.reduce(function (contexts, module) { return contexts.concat(module.contexts); }, []);
        },
        enumerable: false,
        configurable: true
    });
    Router.prototype.start = function () {
        this.scopeObserver.start();
    };
    Router.prototype.stop = function () {
        this.scopeObserver.stop();
    };
    Router.prototype.loadDefinition = function (definition) {
        this.unloadIdentifier(definition.identifier);
        var module = new _module__WEBPACK_IMPORTED_MODULE_0__["Module"](this.application, definition);
        this.connectModule(module);
    };
    Router.prototype.unloadIdentifier = function (identifier) {
        var module = this.modulesByIdentifier.get(identifier);
        if (module) {
            this.disconnectModule(module);
        }
    };
    Router.prototype.getContextForElementAndIdentifier = function (element, identifier) {
        var module = this.modulesByIdentifier.get(identifier);
        if (module) {
            return module.contexts.find(function (context) { return context.element == element; });
        }
    };
    // Error handler delegate
    /** @hidden */
    Router.prototype.handleError = function (error, message, detail) {
        this.application.handleError(error, message, detail);
    };
    // Scope observer delegate
    /** @hidden */
    Router.prototype.createScopeForElementAndIdentifier = function (element, identifier) {
        return new _scope__WEBPACK_IMPORTED_MODULE_2__["Scope"](this.schema, element, identifier, this.logger);
    };
    /** @hidden */
    Router.prototype.scopeConnected = function (scope) {
        this.scopesByIdentifier.add(scope.identifier, scope);
        var module = this.modulesByIdentifier.get(scope.identifier);
        if (module) {
            module.connectContextForScope(scope);
        }
    };
    /** @hidden */
    Router.prototype.scopeDisconnected = function (scope) {
        this.scopesByIdentifier.delete(scope.identifier, scope);
        var module = this.modulesByIdentifier.get(scope.identifier);
        if (module) {
            module.disconnectContextForScope(scope);
        }
    };
    // Modules
    Router.prototype.connectModule = function (module) {
        this.modulesByIdentifier.set(module.identifier, module);
        var scopes = this.scopesByIdentifier.getValuesForKey(module.identifier);
        scopes.forEach(function (scope) { return module.connectContextForScope(scope); });
    };
    Router.prototype.disconnectModule = function (module) {
        this.modulesByIdentifier.delete(module.identifier);
        var scopes = this.scopesByIdentifier.getValuesForKey(module.identifier);
        scopes.forEach(function (scope) { return module.disconnectContextForScope(scope); });
    };
    return Router;
}());

//# sourceMappingURL=router.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/schema.js":
/*!****************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/schema.js ***!
  \****************************************************/
/*! exports provided: defaultSchema */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "defaultSchema", function() { return defaultSchema; });
var defaultSchema = {
    controllerAttribute: "data-controller",
    actionAttribute: "data-action",
    targetAttribute: "data-target"
};
//# sourceMappingURL=schema.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/scope.js":
/*!***************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/scope.js ***!
  \***************************************************/
/*! exports provided: Scope */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Scope", function() { return Scope; });
/* harmony import */ var _class_map__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./class_map */ "./node_modules/@stimulus/core/dist/class_map.js");
/* harmony import */ var _data_map__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./data_map */ "./node_modules/@stimulus/core/dist/data_map.js");
/* harmony import */ var _guide__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./guide */ "./node_modules/@stimulus/core/dist/guide.js");
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./selectors */ "./node_modules/@stimulus/core/dist/selectors.js");
/* harmony import */ var _target_set__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./target_set */ "./node_modules/@stimulus/core/dist/target_set.js");
var __spreadArrays = (undefined && undefined.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};





var Scope = /** @class */ (function () {
    function Scope(schema, element, identifier, logger) {
        var _this = this;
        this.targets = new _target_set__WEBPACK_IMPORTED_MODULE_4__["TargetSet"](this);
        this.classes = new _class_map__WEBPACK_IMPORTED_MODULE_0__["ClassMap"](this);
        this.data = new _data_map__WEBPACK_IMPORTED_MODULE_1__["DataMap"](this);
        this.containsElement = function (element) {
            return element.closest(_this.controllerSelector) === _this.element;
        };
        this.schema = schema;
        this.element = element;
        this.identifier = identifier;
        this.guide = new _guide__WEBPACK_IMPORTED_MODULE_2__["Guide"](logger);
    }
    Scope.prototype.findElement = function (selector) {
        return this.element.matches(selector)
            ? this.element
            : this.queryElements(selector).find(this.containsElement);
    };
    Scope.prototype.findAllElements = function (selector) {
        return __spreadArrays(this.element.matches(selector) ? [this.element] : [], this.queryElements(selector).filter(this.containsElement));
    };
    Scope.prototype.queryElements = function (selector) {
        return Array.from(this.element.querySelectorAll(selector));
    };
    Object.defineProperty(Scope.prototype, "controllerSelector", {
        get: function () {
            return Object(_selectors__WEBPACK_IMPORTED_MODULE_3__["attributeValueContainsToken"])(this.schema.controllerAttribute, this.identifier);
        },
        enumerable: false,
        configurable: true
    });
    return Scope;
}());

//# sourceMappingURL=scope.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/scope_observer.js":
/*!************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/scope_observer.js ***!
  \************************************************************/
/*! exports provided: ScopeObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ScopeObserver", function() { return ScopeObserver; });
/* harmony import */ var _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @stimulus/mutation-observers */ "./node_modules/@stimulus/mutation-observers/dist/index.js");

var ScopeObserver = /** @class */ (function () {
    function ScopeObserver(element, schema, delegate) {
        this.element = element;
        this.schema = schema;
        this.delegate = delegate;
        this.valueListObserver = new _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_0__["ValueListObserver"](this.element, this.controllerAttribute, this);
        this.scopesByIdentifierByElement = new WeakMap;
        this.scopeReferenceCounts = new WeakMap;
    }
    ScopeObserver.prototype.start = function () {
        this.valueListObserver.start();
    };
    ScopeObserver.prototype.stop = function () {
        this.valueListObserver.stop();
    };
    Object.defineProperty(ScopeObserver.prototype, "controllerAttribute", {
        get: function () {
            return this.schema.controllerAttribute;
        },
        enumerable: false,
        configurable: true
    });
    // Value observer delegate
    /** @hidden */
    ScopeObserver.prototype.parseValueForToken = function (token) {
        var element = token.element, identifier = token.content;
        var scopesByIdentifier = this.fetchScopesByIdentifierForElement(element);
        var scope = scopesByIdentifier.get(identifier);
        if (!scope) {
            scope = this.delegate.createScopeForElementAndIdentifier(element, identifier);
            scopesByIdentifier.set(identifier, scope);
        }
        return scope;
    };
    /** @hidden */
    ScopeObserver.prototype.elementMatchedValue = function (element, value) {
        var referenceCount = (this.scopeReferenceCounts.get(value) || 0) + 1;
        this.scopeReferenceCounts.set(value, referenceCount);
        if (referenceCount == 1) {
            this.delegate.scopeConnected(value);
        }
    };
    /** @hidden */
    ScopeObserver.prototype.elementUnmatchedValue = function (element, value) {
        var referenceCount = this.scopeReferenceCounts.get(value);
        if (referenceCount) {
            this.scopeReferenceCounts.set(value, referenceCount - 1);
            if (referenceCount == 1) {
                this.delegate.scopeDisconnected(value);
            }
        }
    };
    ScopeObserver.prototype.fetchScopesByIdentifierForElement = function (element) {
        var scopesByIdentifier = this.scopesByIdentifierByElement.get(element);
        if (!scopesByIdentifier) {
            scopesByIdentifier = new Map;
            this.scopesByIdentifierByElement.set(element, scopesByIdentifier);
        }
        return scopesByIdentifier;
    };
    return ScopeObserver;
}());

//# sourceMappingURL=scope_observer.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/selectors.js":
/*!*******************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/selectors.js ***!
  \*******************************************************/
/*! exports provided: attributeValueContainsToken */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "attributeValueContainsToken", function() { return attributeValueContainsToken; });
/** @hidden */
function attributeValueContainsToken(attributeName, token) {
    return "[" + attributeName + "~=\"" + token + "\"]";
}
//# sourceMappingURL=selectors.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/string_helpers.js":
/*!************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/string_helpers.js ***!
  \************************************************************/
/*! exports provided: camelize, capitalize, dasherize */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "camelize", function() { return camelize; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "capitalize", function() { return capitalize; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "dasherize", function() { return dasherize; });
function camelize(value) {
    return value.replace(/(?:[_-])([a-z0-9])/g, function (_, char) { return char.toUpperCase(); });
}
function capitalize(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}
function dasherize(value) {
    return value.replace(/([A-Z])/g, function (_, char) { return "-" + char.toLowerCase(); });
}
//# sourceMappingURL=string_helpers.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/target_properties.js":
/*!***************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/target_properties.js ***!
  \***************************************************************/
/*! exports provided: TargetPropertiesBlessing */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "TargetPropertiesBlessing", function() { return TargetPropertiesBlessing; });
/* harmony import */ var _inheritable_statics__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./inheritable_statics */ "./node_modules/@stimulus/core/dist/inheritable_statics.js");
/* harmony import */ var _string_helpers__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./string_helpers */ "./node_modules/@stimulus/core/dist/string_helpers.js");


/** @hidden */
function TargetPropertiesBlessing(constructor) {
    var targets = Object(_inheritable_statics__WEBPACK_IMPORTED_MODULE_0__["readInheritableStaticArrayValues"])(constructor, "targets");
    return targets.reduce(function (properties, targetDefinition) {
        return Object.assign(properties, propertiesForTargetDefinition(targetDefinition));
    }, {});
}
function propertiesForTargetDefinition(name) {
    var _a;
    return _a = {},
        _a[name + "Target"] = {
            get: function () {
                var target = this.targets.find(name);
                if (target) {
                    return target;
                }
                else {
                    throw new Error("Missing target element \"" + this.identifier + "." + name + "\"");
                }
            }
        },
        _a[name + "Targets"] = {
            get: function () {
                return this.targets.findAll(name);
            }
        },
        _a["has" + Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["capitalize"])(name) + "Target"] = {
            get: function () {
                return this.targets.has(name);
            }
        },
        _a;
}
//# sourceMappingURL=target_properties.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/target_set.js":
/*!********************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/target_set.js ***!
  \********************************************************/
/*! exports provided: TargetSet */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "TargetSet", function() { return TargetSet; });
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./selectors */ "./node_modules/@stimulus/core/dist/selectors.js");
var __spreadArrays = (undefined && undefined.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};

var TargetSet = /** @class */ (function () {
    function TargetSet(scope) {
        this.scope = scope;
    }
    Object.defineProperty(TargetSet.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(TargetSet.prototype, "identifier", {
        get: function () {
            return this.scope.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(TargetSet.prototype, "schema", {
        get: function () {
            return this.scope.schema;
        },
        enumerable: false,
        configurable: true
    });
    TargetSet.prototype.has = function (targetName) {
        return this.find(targetName) != null;
    };
    TargetSet.prototype.find = function () {
        var _this = this;
        var targetNames = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            targetNames[_i] = arguments[_i];
        }
        return targetNames.reduce(function (target, targetName) {
            return target
                || _this.findTarget(targetName)
                || _this.findLegacyTarget(targetName);
        }, undefined);
    };
    TargetSet.prototype.findAll = function () {
        var _this = this;
        var targetNames = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            targetNames[_i] = arguments[_i];
        }
        return targetNames.reduce(function (targets, targetName) { return __spreadArrays(targets, _this.findAllTargets(targetName), _this.findAllLegacyTargets(targetName)); }, []);
    };
    TargetSet.prototype.findTarget = function (targetName) {
        var selector = this.getSelectorForTargetName(targetName);
        return this.scope.findElement(selector);
    };
    TargetSet.prototype.findAllTargets = function (targetName) {
        var selector = this.getSelectorForTargetName(targetName);
        return this.scope.findAllElements(selector);
    };
    TargetSet.prototype.getSelectorForTargetName = function (targetName) {
        var attributeName = "data-" + this.identifier + "-target";
        return Object(_selectors__WEBPACK_IMPORTED_MODULE_0__["attributeValueContainsToken"])(attributeName, targetName);
    };
    TargetSet.prototype.findLegacyTarget = function (targetName) {
        var selector = this.getLegacySelectorForTargetName(targetName);
        return this.deprecate(this.scope.findElement(selector), targetName);
    };
    TargetSet.prototype.findAllLegacyTargets = function (targetName) {
        var _this = this;
        var selector = this.getLegacySelectorForTargetName(targetName);
        return this.scope.findAllElements(selector).map(function (element) { return _this.deprecate(element, targetName); });
    };
    TargetSet.prototype.getLegacySelectorForTargetName = function (targetName) {
        var targetDescriptor = this.identifier + "." + targetName;
        return Object(_selectors__WEBPACK_IMPORTED_MODULE_0__["attributeValueContainsToken"])(this.schema.targetAttribute, targetDescriptor);
    };
    TargetSet.prototype.deprecate = function (element, targetName) {
        if (element) {
            var identifier = this.identifier;
            var attributeName = this.schema.targetAttribute;
            this.guide.warn(element, "target:" + targetName, "Please replace " + attributeName + "=\"" + identifier + "." + targetName + "\" with data-" + identifier + "-target=\"" + targetName + "\". " +
                ("The " + attributeName + " attribute is deprecated and will be removed in a future version of Stimulus."));
        }
        return element;
    };
    Object.defineProperty(TargetSet.prototype, "guide", {
        get: function () {
            return this.scope.guide;
        },
        enumerable: false,
        configurable: true
    });
    return TargetSet;
}());

//# sourceMappingURL=target_set.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/value_observer.js":
/*!************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/value_observer.js ***!
  \************************************************************/
/*! exports provided: ValueObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ValueObserver", function() { return ValueObserver; });
/* harmony import */ var _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @stimulus/mutation-observers */ "./node_modules/@stimulus/mutation-observers/dist/index.js");

var ValueObserver = /** @class */ (function () {
    function ValueObserver(context, receiver) {
        this.context = context;
        this.receiver = receiver;
        this.stringMapObserver = new _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_0__["StringMapObserver"](this.element, this);
        this.valueDescriptorMap = this.controller.valueDescriptorMap;
        this.invokeChangedCallbacksForDefaultValues();
    }
    ValueObserver.prototype.start = function () {
        this.stringMapObserver.start();
    };
    ValueObserver.prototype.stop = function () {
        this.stringMapObserver.stop();
    };
    Object.defineProperty(ValueObserver.prototype, "element", {
        get: function () {
            return this.context.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(ValueObserver.prototype, "controller", {
        get: function () {
            return this.context.controller;
        },
        enumerable: false,
        configurable: true
    });
    // String map observer delegate
    ValueObserver.prototype.getStringMapKeyForAttribute = function (attributeName) {
        if (attributeName in this.valueDescriptorMap) {
            return this.valueDescriptorMap[attributeName].name;
        }
    };
    ValueObserver.prototype.stringMapValueChanged = function (attributeValue, name) {
        this.invokeChangedCallbackForValue(name);
    };
    ValueObserver.prototype.invokeChangedCallbacksForDefaultValues = function () {
        for (var _i = 0, _a = this.valueDescriptors; _i < _a.length; _i++) {
            var _b = _a[_i], key = _b.key, name_1 = _b.name, defaultValue = _b.defaultValue;
            if (defaultValue != undefined && !this.controller.data.has(key)) {
                this.invokeChangedCallbackForValue(name_1);
            }
        }
    };
    ValueObserver.prototype.invokeChangedCallbackForValue = function (name) {
        var methodName = name + "Changed";
        var method = this.receiver[methodName];
        if (typeof method == "function") {
            var value = this.receiver[name];
            method.call(this.receiver, value);
        }
    };
    Object.defineProperty(ValueObserver.prototype, "valueDescriptors", {
        get: function () {
            var valueDescriptorMap = this.valueDescriptorMap;
            return Object.keys(valueDescriptorMap).map(function (key) { return valueDescriptorMap[key]; });
        },
        enumerable: false,
        configurable: true
    });
    return ValueObserver;
}());

//# sourceMappingURL=value_observer.js.map

/***/ }),

/***/ "./node_modules/@stimulus/core/dist/value_properties.js":
/*!**************************************************************!*\
  !*** ./node_modules/@stimulus/core/dist/value_properties.js ***!
  \**************************************************************/
/*! exports provided: ValuePropertiesBlessing, propertiesForValueDefinitionPair */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ValuePropertiesBlessing", function() { return ValuePropertiesBlessing; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "propertiesForValueDefinitionPair", function() { return propertiesForValueDefinitionPair; });
/* harmony import */ var _inheritable_statics__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./inheritable_statics */ "./node_modules/@stimulus/core/dist/inheritable_statics.js");
/* harmony import */ var _string_helpers__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./string_helpers */ "./node_modules/@stimulus/core/dist/string_helpers.js");


/** @hidden */
function ValuePropertiesBlessing(constructor) {
    var valueDefinitionPairs = Object(_inheritable_statics__WEBPACK_IMPORTED_MODULE_0__["readInheritableStaticObjectPairs"])(constructor, "values");
    var propertyDescriptorMap = {
        valueDescriptorMap: {
            get: function () {
                var _this = this;
                return valueDefinitionPairs.reduce(function (result, valueDefinitionPair) {
                    var _a;
                    var valueDescriptor = parseValueDefinitionPair(valueDefinitionPair);
                    var attributeName = _this.data.getAttributeNameForKey(valueDescriptor.key);
                    return Object.assign(result, (_a = {}, _a[attributeName] = valueDescriptor, _a));
                }, {});
            }
        }
    };
    return valueDefinitionPairs.reduce(function (properties, valueDefinitionPair) {
        return Object.assign(properties, propertiesForValueDefinitionPair(valueDefinitionPair));
    }, propertyDescriptorMap);
}
/** @hidden */
function propertiesForValueDefinitionPair(valueDefinitionPair) {
    var _a;
    var definition = parseValueDefinitionPair(valueDefinitionPair);
    var type = definition.type, key = definition.key, name = definition.name;
    var read = readers[type], write = writers[type] || writers.default;
    return _a = {},
        _a[name] = {
            get: function () {
                var value = this.data.get(key);
                if (value !== null) {
                    return read(value);
                }
                else {
                    return definition.defaultValue;
                }
            },
            set: function (value) {
                if (value === undefined) {
                    this.data.delete(key);
                }
                else {
                    this.data.set(key, write(value));
                }
            }
        },
        _a["has" + Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["capitalize"])(name)] = {
            get: function () {
                return this.data.has(key);
            }
        },
        _a;
}
function parseValueDefinitionPair(_a) {
    var token = _a[0], typeConstant = _a[1];
    var type = parseValueTypeConstant(typeConstant);
    return valueDescriptorForTokenAndType(token, type);
}
function parseValueTypeConstant(typeConstant) {
    switch (typeConstant) {
        case Array: return "array";
        case Boolean: return "boolean";
        case Number: return "number";
        case Object: return "object";
        case String: return "string";
    }
    throw new Error("Unknown value type constant \"" + typeConstant + "\"");
}
function valueDescriptorForTokenAndType(token, type) {
    var key = Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["dasherize"])(token) + "-value";
    return {
        type: type,
        key: key,
        name: Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["camelize"])(key),
        get defaultValue() { return defaultValuesByType[type]; }
    };
}
var defaultValuesByType = {
    get array() { return []; },
    boolean: false,
    number: 0,
    get object() { return {}; },
    string: ""
};
var readers = {
    array: function (value) {
        var array = JSON.parse(value);
        if (!Array.isArray(array)) {
            throw new TypeError("Expected array");
        }
        return array;
    },
    boolean: function (value) {
        return !(value == "0" || value == "false");
    },
    number: function (value) {
        return parseFloat(value);
    },
    object: function (value) {
        var object = JSON.parse(value);
        if (object === null || typeof object != "object" || Array.isArray(object)) {
            throw new TypeError("Expected object");
        }
        return object;
    },
    string: function (value) {
        return value;
    }
};
var writers = {
    default: writeString,
    array: writeJSON,
    object: writeJSON
};
function writeJSON(value) {
    return JSON.stringify(value);
}
function writeString(value) {
    return "" + value;
}
//# sourceMappingURL=value_properties.js.map

/***/ }),

/***/ "./node_modules/@stimulus/multimap/dist/index.js":
/*!*******************************************************!*\
  !*** ./node_modules/@stimulus/multimap/dist/index.js ***!
  \*******************************************************/
/*! exports provided: IndexedMultimap, Multimap, add, del, fetch, prune */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _indexed_multimap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./indexed_multimap */ "./node_modules/@stimulus/multimap/dist/indexed_multimap.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "IndexedMultimap", function() { return _indexed_multimap__WEBPACK_IMPORTED_MODULE_0__["IndexedMultimap"]; });

/* harmony import */ var _multimap__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./multimap */ "./node_modules/@stimulus/multimap/dist/multimap.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Multimap", function() { return _multimap__WEBPACK_IMPORTED_MODULE_1__["Multimap"]; });

/* harmony import */ var _set_operations__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./set_operations */ "./node_modules/@stimulus/multimap/dist/set_operations.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "add", function() { return _set_operations__WEBPACK_IMPORTED_MODULE_2__["add"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "del", function() { return _set_operations__WEBPACK_IMPORTED_MODULE_2__["del"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "fetch", function() { return _set_operations__WEBPACK_IMPORTED_MODULE_2__["fetch"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "prune", function() { return _set_operations__WEBPACK_IMPORTED_MODULE_2__["prune"]; });




//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@stimulus/multimap/dist/indexed_multimap.js":
/*!******************************************************************!*\
  !*** ./node_modules/@stimulus/multimap/dist/indexed_multimap.js ***!
  \******************************************************************/
/*! exports provided: IndexedMultimap */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "IndexedMultimap", function() { return IndexedMultimap; });
/* harmony import */ var _multimap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./multimap */ "./node_modules/@stimulus/multimap/dist/multimap.js");
/* harmony import */ var _set_operations__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./set_operations */ "./node_modules/@stimulus/multimap/dist/set_operations.js");
var __extends = (undefined && undefined.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();


var IndexedMultimap = /** @class */ (function (_super) {
    __extends(IndexedMultimap, _super);
    function IndexedMultimap() {
        var _this = _super.call(this) || this;
        _this.keysByValue = new Map;
        return _this;
    }
    Object.defineProperty(IndexedMultimap.prototype, "values", {
        get: function () {
            return Array.from(this.keysByValue.keys());
        },
        enumerable: false,
        configurable: true
    });
    IndexedMultimap.prototype.add = function (key, value) {
        _super.prototype.add.call(this, key, value);
        Object(_set_operations__WEBPACK_IMPORTED_MODULE_1__["add"])(this.keysByValue, value, key);
    };
    IndexedMultimap.prototype.delete = function (key, value) {
        _super.prototype.delete.call(this, key, value);
        Object(_set_operations__WEBPACK_IMPORTED_MODULE_1__["del"])(this.keysByValue, value, key);
    };
    IndexedMultimap.prototype.hasValue = function (value) {
        return this.keysByValue.has(value);
    };
    IndexedMultimap.prototype.getKeysForValue = function (value) {
        var set = this.keysByValue.get(value);
        return set ? Array.from(set) : [];
    };
    return IndexedMultimap;
}(_multimap__WEBPACK_IMPORTED_MODULE_0__["Multimap"]));

//# sourceMappingURL=indexed_multimap.js.map

/***/ }),

/***/ "./node_modules/@stimulus/multimap/dist/multimap.js":
/*!**********************************************************!*\
  !*** ./node_modules/@stimulus/multimap/dist/multimap.js ***!
  \**********************************************************/
/*! exports provided: Multimap */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Multimap", function() { return Multimap; });
/* harmony import */ var _set_operations__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./set_operations */ "./node_modules/@stimulus/multimap/dist/set_operations.js");

var Multimap = /** @class */ (function () {
    function Multimap() {
        this.valuesByKey = new Map();
    }
    Object.defineProperty(Multimap.prototype, "values", {
        get: function () {
            var sets = Array.from(this.valuesByKey.values());
            return sets.reduce(function (values, set) { return values.concat(Array.from(set)); }, []);
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Multimap.prototype, "size", {
        get: function () {
            var sets = Array.from(this.valuesByKey.values());
            return sets.reduce(function (size, set) { return size + set.size; }, 0);
        },
        enumerable: false,
        configurable: true
    });
    Multimap.prototype.add = function (key, value) {
        Object(_set_operations__WEBPACK_IMPORTED_MODULE_0__["add"])(this.valuesByKey, key, value);
    };
    Multimap.prototype.delete = function (key, value) {
        Object(_set_operations__WEBPACK_IMPORTED_MODULE_0__["del"])(this.valuesByKey, key, value);
    };
    Multimap.prototype.has = function (key, value) {
        var values = this.valuesByKey.get(key);
        return values != null && values.has(value);
    };
    Multimap.prototype.hasKey = function (key) {
        return this.valuesByKey.has(key);
    };
    Multimap.prototype.hasValue = function (value) {
        var sets = Array.from(this.valuesByKey.values());
        return sets.some(function (set) { return set.has(value); });
    };
    Multimap.prototype.getValuesForKey = function (key) {
        var values = this.valuesByKey.get(key);
        return values ? Array.from(values) : [];
    };
    Multimap.prototype.getKeysForValue = function (value) {
        return Array.from(this.valuesByKey)
            .filter(function (_a) {
            var key = _a[0], values = _a[1];
            return values.has(value);
        })
            .map(function (_a) {
            var key = _a[0], values = _a[1];
            return key;
        });
    };
    return Multimap;
}());

//# sourceMappingURL=multimap.js.map

/***/ }),

/***/ "./node_modules/@stimulus/multimap/dist/set_operations.js":
/*!****************************************************************!*\
  !*** ./node_modules/@stimulus/multimap/dist/set_operations.js ***!
  \****************************************************************/
/*! exports provided: add, del, fetch, prune */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "add", function() { return add; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "del", function() { return del; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "fetch", function() { return fetch; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "prune", function() { return prune; });
function add(map, key, value) {
    fetch(map, key).add(value);
}
function del(map, key, value) {
    fetch(map, key).delete(value);
    prune(map, key);
}
function fetch(map, key) {
    var values = map.get(key);
    if (!values) {
        values = new Set();
        map.set(key, values);
    }
    return values;
}
function prune(map, key) {
    var values = map.get(key);
    if (values != null && values.size == 0) {
        map.delete(key);
    }
}
//# sourceMappingURL=set_operations.js.map

/***/ }),

/***/ "./node_modules/@stimulus/mutation-observers/dist/attribute_observer.js":
/*!******************************************************************************!*\
  !*** ./node_modules/@stimulus/mutation-observers/dist/attribute_observer.js ***!
  \******************************************************************************/
/*! exports provided: AttributeObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "AttributeObserver", function() { return AttributeObserver; });
/* harmony import */ var _element_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./element_observer */ "./node_modules/@stimulus/mutation-observers/dist/element_observer.js");

var AttributeObserver = /** @class */ (function () {
    function AttributeObserver(element, attributeName, delegate) {
        this.attributeName = attributeName;
        this.delegate = delegate;
        this.elementObserver = new _element_observer__WEBPACK_IMPORTED_MODULE_0__["ElementObserver"](element, this);
    }
    Object.defineProperty(AttributeObserver.prototype, "element", {
        get: function () {
            return this.elementObserver.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(AttributeObserver.prototype, "selector", {
        get: function () {
            return "[" + this.attributeName + "]";
        },
        enumerable: false,
        configurable: true
    });
    AttributeObserver.prototype.start = function () {
        this.elementObserver.start();
    };
    AttributeObserver.prototype.stop = function () {
        this.elementObserver.stop();
    };
    AttributeObserver.prototype.refresh = function () {
        this.elementObserver.refresh();
    };
    Object.defineProperty(AttributeObserver.prototype, "started", {
        get: function () {
            return this.elementObserver.started;
        },
        enumerable: false,
        configurable: true
    });
    // Element observer delegate
    AttributeObserver.prototype.matchElement = function (element) {
        return element.hasAttribute(this.attributeName);
    };
    AttributeObserver.prototype.matchElementsInTree = function (tree) {
        var match = this.matchElement(tree) ? [tree] : [];
        var matches = Array.from(tree.querySelectorAll(this.selector));
        return match.concat(matches);
    };
    AttributeObserver.prototype.elementMatched = function (element) {
        if (this.delegate.elementMatchedAttribute) {
            this.delegate.elementMatchedAttribute(element, this.attributeName);
        }
    };
    AttributeObserver.prototype.elementUnmatched = function (element) {
        if (this.delegate.elementUnmatchedAttribute) {
            this.delegate.elementUnmatchedAttribute(element, this.attributeName);
        }
    };
    AttributeObserver.prototype.elementAttributeChanged = function (element, attributeName) {
        if (this.delegate.elementAttributeValueChanged && this.attributeName == attributeName) {
            this.delegate.elementAttributeValueChanged(element, attributeName);
        }
    };
    return AttributeObserver;
}());

//# sourceMappingURL=attribute_observer.js.map

/***/ }),

/***/ "./node_modules/@stimulus/mutation-observers/dist/element_observer.js":
/*!****************************************************************************!*\
  !*** ./node_modules/@stimulus/mutation-observers/dist/element_observer.js ***!
  \****************************************************************************/
/*! exports provided: ElementObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ElementObserver", function() { return ElementObserver; });
var ElementObserver = /** @class */ (function () {
    function ElementObserver(element, delegate) {
        var _this = this;
        this.element = element;
        this.started = false;
        this.delegate = delegate;
        this.elements = new Set;
        this.mutationObserver = new MutationObserver(function (mutations) { return _this.processMutations(mutations); });
    }
    ElementObserver.prototype.start = function () {
        if (!this.started) {
            this.started = true;
            this.mutationObserver.observe(this.element, { attributes: true, childList: true, subtree: true });
            this.refresh();
        }
    };
    ElementObserver.prototype.stop = function () {
        if (this.started) {
            this.mutationObserver.takeRecords();
            this.mutationObserver.disconnect();
            this.started = false;
        }
    };
    ElementObserver.prototype.refresh = function () {
        if (this.started) {
            var matches = new Set(this.matchElementsInTree());
            for (var _i = 0, _a = Array.from(this.elements); _i < _a.length; _i++) {
                var element = _a[_i];
                if (!matches.has(element)) {
                    this.removeElement(element);
                }
            }
            for (var _b = 0, _c = Array.from(matches); _b < _c.length; _b++) {
                var element = _c[_b];
                this.addElement(element);
            }
        }
    };
    // Mutation record processing
    ElementObserver.prototype.processMutations = function (mutations) {
        if (this.started) {
            for (var _i = 0, mutations_1 = mutations; _i < mutations_1.length; _i++) {
                var mutation = mutations_1[_i];
                this.processMutation(mutation);
            }
        }
    };
    ElementObserver.prototype.processMutation = function (mutation) {
        if (mutation.type == "attributes") {
            this.processAttributeChange(mutation.target, mutation.attributeName);
        }
        else if (mutation.type == "childList") {
            this.processRemovedNodes(mutation.removedNodes);
            this.processAddedNodes(mutation.addedNodes);
        }
    };
    ElementObserver.prototype.processAttributeChange = function (node, attributeName) {
        var element = node;
        if (this.elements.has(element)) {
            if (this.delegate.elementAttributeChanged && this.matchElement(element)) {
                this.delegate.elementAttributeChanged(element, attributeName);
            }
            else {
                this.removeElement(element);
            }
        }
        else if (this.matchElement(element)) {
            this.addElement(element);
        }
    };
    ElementObserver.prototype.processRemovedNodes = function (nodes) {
        for (var _i = 0, _a = Array.from(nodes); _i < _a.length; _i++) {
            var node = _a[_i];
            var element = this.elementFromNode(node);
            if (element) {
                this.processTree(element, this.removeElement);
            }
        }
    };
    ElementObserver.prototype.processAddedNodes = function (nodes) {
        for (var _i = 0, _a = Array.from(nodes); _i < _a.length; _i++) {
            var node = _a[_i];
            var element = this.elementFromNode(node);
            if (element && this.elementIsActive(element)) {
                this.processTree(element, this.addElement);
            }
        }
    };
    // Element matching
    ElementObserver.prototype.matchElement = function (element) {
        return this.delegate.matchElement(element);
    };
    ElementObserver.prototype.matchElementsInTree = function (tree) {
        if (tree === void 0) { tree = this.element; }
        return this.delegate.matchElementsInTree(tree);
    };
    ElementObserver.prototype.processTree = function (tree, processor) {
        for (var _i = 0, _a = this.matchElementsInTree(tree); _i < _a.length; _i++) {
            var element = _a[_i];
            processor.call(this, element);
        }
    };
    ElementObserver.prototype.elementFromNode = function (node) {
        if (node.nodeType == Node.ELEMENT_NODE) {
            return node;
        }
    };
    ElementObserver.prototype.elementIsActive = function (element) {
        if (element.isConnected != this.element.isConnected) {
            return false;
        }
        else {
            return this.element.contains(element);
        }
    };
    // Element tracking
    ElementObserver.prototype.addElement = function (element) {
        if (!this.elements.has(element)) {
            if (this.elementIsActive(element)) {
                this.elements.add(element);
                if (this.delegate.elementMatched) {
                    this.delegate.elementMatched(element);
                }
            }
        }
    };
    ElementObserver.prototype.removeElement = function (element) {
        if (this.elements.has(element)) {
            this.elements.delete(element);
            if (this.delegate.elementUnmatched) {
                this.delegate.elementUnmatched(element);
            }
        }
    };
    return ElementObserver;
}());

//# sourceMappingURL=element_observer.js.map

/***/ }),

/***/ "./node_modules/@stimulus/mutation-observers/dist/index.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@stimulus/mutation-observers/dist/index.js ***!
  \*****************************************************************/
/*! exports provided: AttributeObserver, ElementObserver, StringMapObserver, TokenListObserver, ValueListObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _attribute_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./attribute_observer */ "./node_modules/@stimulus/mutation-observers/dist/attribute_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "AttributeObserver", function() { return _attribute_observer__WEBPACK_IMPORTED_MODULE_0__["AttributeObserver"]; });

/* harmony import */ var _element_observer__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./element_observer */ "./node_modules/@stimulus/mutation-observers/dist/element_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "ElementObserver", function() { return _element_observer__WEBPACK_IMPORTED_MODULE_1__["ElementObserver"]; });

/* harmony import */ var _string_map_observer__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./string_map_observer */ "./node_modules/@stimulus/mutation-observers/dist/string_map_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "StringMapObserver", function() { return _string_map_observer__WEBPACK_IMPORTED_MODULE_2__["StringMapObserver"]; });

/* harmony import */ var _token_list_observer__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./token_list_observer */ "./node_modules/@stimulus/mutation-observers/dist/token_list_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "TokenListObserver", function() { return _token_list_observer__WEBPACK_IMPORTED_MODULE_3__["TokenListObserver"]; });

/* harmony import */ var _value_list_observer__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./value_list_observer */ "./node_modules/@stimulus/mutation-observers/dist/value_list_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "ValueListObserver", function() { return _value_list_observer__WEBPACK_IMPORTED_MODULE_4__["ValueListObserver"]; });






//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@stimulus/mutation-observers/dist/string_map_observer.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@stimulus/mutation-observers/dist/string_map_observer.js ***!
  \*******************************************************************************/
/*! exports provided: StringMapObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "StringMapObserver", function() { return StringMapObserver; });
var StringMapObserver = /** @class */ (function () {
    function StringMapObserver(element, delegate) {
        var _this = this;
        this.element = element;
        this.delegate = delegate;
        this.started = false;
        this.stringMap = new Map;
        this.mutationObserver = new MutationObserver(function (mutations) { return _this.processMutations(mutations); });
    }
    StringMapObserver.prototype.start = function () {
        if (!this.started) {
            this.started = true;
            this.mutationObserver.observe(this.element, { attributes: true });
            this.refresh();
        }
    };
    StringMapObserver.prototype.stop = function () {
        if (this.started) {
            this.mutationObserver.takeRecords();
            this.mutationObserver.disconnect();
            this.started = false;
        }
    };
    StringMapObserver.prototype.refresh = function () {
        if (this.started) {
            for (var _i = 0, _a = this.knownAttributeNames; _i < _a.length; _i++) {
                var attributeName = _a[_i];
                this.refreshAttribute(attributeName);
            }
        }
    };
    // Mutation record processing
    StringMapObserver.prototype.processMutations = function (mutations) {
        if (this.started) {
            for (var _i = 0, mutations_1 = mutations; _i < mutations_1.length; _i++) {
                var mutation = mutations_1[_i];
                this.processMutation(mutation);
            }
        }
    };
    StringMapObserver.prototype.processMutation = function (mutation) {
        var attributeName = mutation.attributeName;
        if (attributeName) {
            this.refreshAttribute(attributeName);
        }
    };
    // State tracking
    StringMapObserver.prototype.refreshAttribute = function (attributeName) {
        var key = this.delegate.getStringMapKeyForAttribute(attributeName);
        if (key != null) {
            if (!this.stringMap.has(attributeName)) {
                this.stringMapKeyAdded(key, attributeName);
            }
            var value = this.element.getAttribute(attributeName);
            if (this.stringMap.get(attributeName) != value) {
                this.stringMapValueChanged(value, key);
            }
            if (value == null) {
                this.stringMap.delete(attributeName);
                this.stringMapKeyRemoved(key, attributeName);
            }
            else {
                this.stringMap.set(attributeName, value);
            }
        }
    };
    StringMapObserver.prototype.stringMapKeyAdded = function (key, attributeName) {
        if (this.delegate.stringMapKeyAdded) {
            this.delegate.stringMapKeyAdded(key, attributeName);
        }
    };
    StringMapObserver.prototype.stringMapValueChanged = function (value, key) {
        if (this.delegate.stringMapValueChanged) {
            this.delegate.stringMapValueChanged(value, key);
        }
    };
    StringMapObserver.prototype.stringMapKeyRemoved = function (key, attributeName) {
        if (this.delegate.stringMapKeyRemoved) {
            this.delegate.stringMapKeyRemoved(key, attributeName);
        }
    };
    Object.defineProperty(StringMapObserver.prototype, "knownAttributeNames", {
        get: function () {
            return Array.from(new Set(this.currentAttributeNames.concat(this.recordedAttributeNames)));
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(StringMapObserver.prototype, "currentAttributeNames", {
        get: function () {
            return Array.from(this.element.attributes).map(function (attribute) { return attribute.name; });
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(StringMapObserver.prototype, "recordedAttributeNames", {
        get: function () {
            return Array.from(this.stringMap.keys());
        },
        enumerable: false,
        configurable: true
    });
    return StringMapObserver;
}());

//# sourceMappingURL=string_map_observer.js.map

/***/ }),

/***/ "./node_modules/@stimulus/mutation-observers/dist/token_list_observer.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@stimulus/mutation-observers/dist/token_list_observer.js ***!
  \*******************************************************************************/
/*! exports provided: TokenListObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "TokenListObserver", function() { return TokenListObserver; });
/* harmony import */ var _attribute_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./attribute_observer */ "./node_modules/@stimulus/mutation-observers/dist/attribute_observer.js");
/* harmony import */ var _stimulus_multimap__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @stimulus/multimap */ "./node_modules/@stimulus/multimap/dist/index.js");


var TokenListObserver = /** @class */ (function () {
    function TokenListObserver(element, attributeName, delegate) {
        this.attributeObserver = new _attribute_observer__WEBPACK_IMPORTED_MODULE_0__["AttributeObserver"](element, attributeName, this);
        this.delegate = delegate;
        this.tokensByElement = new _stimulus_multimap__WEBPACK_IMPORTED_MODULE_1__["Multimap"];
    }
    Object.defineProperty(TokenListObserver.prototype, "started", {
        get: function () {
            return this.attributeObserver.started;
        },
        enumerable: false,
        configurable: true
    });
    TokenListObserver.prototype.start = function () {
        this.attributeObserver.start();
    };
    TokenListObserver.prototype.stop = function () {
        this.attributeObserver.stop();
    };
    TokenListObserver.prototype.refresh = function () {
        this.attributeObserver.refresh();
    };
    Object.defineProperty(TokenListObserver.prototype, "element", {
        get: function () {
            return this.attributeObserver.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(TokenListObserver.prototype, "attributeName", {
        get: function () {
            return this.attributeObserver.attributeName;
        },
        enumerable: false,
        configurable: true
    });
    // Attribute observer delegate
    TokenListObserver.prototype.elementMatchedAttribute = function (element) {
        this.tokensMatched(this.readTokensForElement(element));
    };
    TokenListObserver.prototype.elementAttributeValueChanged = function (element) {
        var _a = this.refreshTokensForElement(element), unmatchedTokens = _a[0], matchedTokens = _a[1];
        this.tokensUnmatched(unmatchedTokens);
        this.tokensMatched(matchedTokens);
    };
    TokenListObserver.prototype.elementUnmatchedAttribute = function (element) {
        this.tokensUnmatched(this.tokensByElement.getValuesForKey(element));
    };
    TokenListObserver.prototype.tokensMatched = function (tokens) {
        var _this = this;
        tokens.forEach(function (token) { return _this.tokenMatched(token); });
    };
    TokenListObserver.prototype.tokensUnmatched = function (tokens) {
        var _this = this;
        tokens.forEach(function (token) { return _this.tokenUnmatched(token); });
    };
    TokenListObserver.prototype.tokenMatched = function (token) {
        this.delegate.tokenMatched(token);
        this.tokensByElement.add(token.element, token);
    };
    TokenListObserver.prototype.tokenUnmatched = function (token) {
        this.delegate.tokenUnmatched(token);
        this.tokensByElement.delete(token.element, token);
    };
    TokenListObserver.prototype.refreshTokensForElement = function (element) {
        var previousTokens = this.tokensByElement.getValuesForKey(element);
        var currentTokens = this.readTokensForElement(element);
        var firstDifferingIndex = zip(previousTokens, currentTokens)
            .findIndex(function (_a) {
            var previousToken = _a[0], currentToken = _a[1];
            return !tokensAreEqual(previousToken, currentToken);
        });
        if (firstDifferingIndex == -1) {
            return [[], []];
        }
        else {
            return [previousTokens.slice(firstDifferingIndex), currentTokens.slice(firstDifferingIndex)];
        }
    };
    TokenListObserver.prototype.readTokensForElement = function (element) {
        var attributeName = this.attributeName;
        var tokenString = element.getAttribute(attributeName) || "";
        return parseTokenString(tokenString, element, attributeName);
    };
    return TokenListObserver;
}());

function parseTokenString(tokenString, element, attributeName) {
    return tokenString.trim().split(/\s+/).filter(function (content) { return content.length; })
        .map(function (content, index) { return ({ element: element, attributeName: attributeName, content: content, index: index }); });
}
function zip(left, right) {
    var length = Math.max(left.length, right.length);
    return Array.from({ length: length }, function (_, index) { return [left[index], right[index]]; });
}
function tokensAreEqual(left, right) {
    return left && right && left.index == right.index && left.content == right.content;
}
//# sourceMappingURL=token_list_observer.js.map

/***/ }),

/***/ "./node_modules/@stimulus/mutation-observers/dist/value_list_observer.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@stimulus/mutation-observers/dist/value_list_observer.js ***!
  \*******************************************************************************/
/*! exports provided: ValueListObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ValueListObserver", function() { return ValueListObserver; });
/* harmony import */ var _token_list_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./token_list_observer */ "./node_modules/@stimulus/mutation-observers/dist/token_list_observer.js");

var ValueListObserver = /** @class */ (function () {
    function ValueListObserver(element, attributeName, delegate) {
        this.tokenListObserver = new _token_list_observer__WEBPACK_IMPORTED_MODULE_0__["TokenListObserver"](element, attributeName, this);
        this.delegate = delegate;
        this.parseResultsByToken = new WeakMap;
        this.valuesByTokenByElement = new WeakMap;
    }
    Object.defineProperty(ValueListObserver.prototype, "started", {
        get: function () {
            return this.tokenListObserver.started;
        },
        enumerable: false,
        configurable: true
    });
    ValueListObserver.prototype.start = function () {
        this.tokenListObserver.start();
    };
    ValueListObserver.prototype.stop = function () {
        this.tokenListObserver.stop();
    };
    ValueListObserver.prototype.refresh = function () {
        this.tokenListObserver.refresh();
    };
    Object.defineProperty(ValueListObserver.prototype, "element", {
        get: function () {
            return this.tokenListObserver.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(ValueListObserver.prototype, "attributeName", {
        get: function () {
            return this.tokenListObserver.attributeName;
        },
        enumerable: false,
        configurable: true
    });
    ValueListObserver.prototype.tokenMatched = function (token) {
        var element = token.element;
        var value = this.fetchParseResultForToken(token).value;
        if (value) {
            this.fetchValuesByTokenForElement(element).set(token, value);
            this.delegate.elementMatchedValue(element, value);
        }
    };
    ValueListObserver.prototype.tokenUnmatched = function (token) {
        var element = token.element;
        var value = this.fetchParseResultForToken(token).value;
        if (value) {
            this.fetchValuesByTokenForElement(element).delete(token);
            this.delegate.elementUnmatchedValue(element, value);
        }
    };
    ValueListObserver.prototype.fetchParseResultForToken = function (token) {
        var parseResult = this.parseResultsByToken.get(token);
        if (!parseResult) {
            parseResult = this.parseToken(token);
            this.parseResultsByToken.set(token, parseResult);
        }
        return parseResult;
    };
    ValueListObserver.prototype.fetchValuesByTokenForElement = function (element) {
        var valuesByToken = this.valuesByTokenByElement.get(element);
        if (!valuesByToken) {
            valuesByToken = new Map;
            this.valuesByTokenByElement.set(element, valuesByToken);
        }
        return valuesByToken;
    };
    ValueListObserver.prototype.parseToken = function (token) {
        try {
            var value = this.delegate.parseValueForToken(token);
            return { value: value };
        }
        catch (error) {
            return { error: error };
        }
    };
    return ValueListObserver;
}());

//# sourceMappingURL=value_list_observer.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js":
/*!**********************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js ***!
  \**********************************************************************************************/
/*! exports provided: clearCache, connectStreamSource, disconnectStreamSource, navigator, registerAdapter, renderStreamMessage, setProgressBarDelay, start, visit */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "clearCache", function() { return clearCache; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "connectStreamSource", function() { return connectStreamSource; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "disconnectStreamSource", function() { return disconnectStreamSource; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "navigator", function() { return navigator; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "registerAdapter", function() { return registerAdapter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "renderStreamMessage", function() { return renderStreamMessage; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "setProgressBarDelay", function() { return setProgressBarDelay; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "start", function() { return start; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "visit", function() { return visit; });
/*
Turbo 7.0.0-beta.1
Copyright © 2020 Basecamp, LLC
 */
(function () {
    if (window.Reflect === undefined || window.customElements === undefined ||
        window.customElements.polyfillWrapFlushCallback) {
        return;
    }
    const BuiltInHTMLElement = HTMLElement;
    const wrapperForTheName = {
        'HTMLElement': function HTMLElement() {
            return Reflect.construct(BuiltInHTMLElement, [], this.constructor);
        }
    };
    window.HTMLElement =
        wrapperForTheName['HTMLElement'];
    HTMLElement.prototype = BuiltInHTMLElement.prototype;
    HTMLElement.prototype.constructor = HTMLElement;
    Object.setPrototypeOf(HTMLElement, BuiltInHTMLElement);
})();

const submittersByForm = new WeakMap;
function findSubmitterFromClickTarget(target) {
    const element = target instanceof Element ? target : target instanceof Node ? target.parentElement : null;
    const candidate = element ? element.closest("input, button") : null;
    return (candidate === null || candidate === void 0 ? void 0 : candidate.getAttribute("type")) == "submit" ? candidate : null;
}
function clickCaptured(event) {
    const submitter = findSubmitterFromClickTarget(event.target);
    if (submitter && submitter.form) {
        submittersByForm.set(submitter.form, submitter);
    }
}
(function () {
    if ("SubmitEvent" in window)
        return;
    addEventListener("click", clickCaptured, true);
    Object.defineProperty(Event.prototype, "submitter", {
        get() {
            if (this.type == "submit" && this.target instanceof HTMLFormElement) {
                return submittersByForm.get(this.target);
            }
        }
    });
})();

class Location {
    constructor(url) {
        const linkWithAnchor = document.createElement("a");
        linkWithAnchor.href = url;
        this.absoluteURL = linkWithAnchor.href;
        const anchorLength = linkWithAnchor.hash.length;
        if (anchorLength < 2) {
            this.requestURL = this.absoluteURL;
        }
        else {
            this.requestURL = this.absoluteURL.slice(0, -anchorLength);
            this.anchor = linkWithAnchor.hash.slice(1);
        }
    }
    static get currentLocation() {
        return this.wrap(window.location.toString());
    }
    static wrap(locatable) {
        if (typeof locatable == "string") {
            return new this(locatable);
        }
        else if (locatable != null) {
            return locatable;
        }
    }
    getOrigin() {
        return this.absoluteURL.split("/", 3).join("/");
    }
    getPath() {
        return (this.requestURL.match(/\/\/[^/]*(\/[^?;]*)/) || [])[1] || "/";
    }
    getPathComponents() {
        return this.getPath().split("/").slice(1);
    }
    getLastPathComponent() {
        return this.getPathComponents().slice(-1)[0];
    }
    getExtension() {
        return (this.getLastPathComponent().match(/\.[^.]*$/) || [])[0] || "";
    }
    isHTML() {
        return !!this.getExtension().match(/^(?:|\.(?:htm|html|xhtml))$/);
    }
    isPrefixedBy(location) {
        const prefixURL = getPrefixURL(location);
        return this.isEqualTo(location) || stringStartsWith(this.absoluteURL, prefixURL);
    }
    isEqualTo(location) {
        return location && this.absoluteURL === location.absoluteURL;
    }
    toCacheKey() {
        return this.requestURL;
    }
    toJSON() {
        return this.absoluteURL;
    }
    toString() {
        return this.absoluteURL;
    }
    valueOf() {
        return this.absoluteURL;
    }
}
function getPrefixURL(location) {
    return addTrailingSlash(location.getOrigin() + location.getPath());
}
function addTrailingSlash(url) {
    return stringEndsWith(url, "/") ? url : url + "/";
}
function stringStartsWith(string, prefix) {
    return string.slice(0, prefix.length) === prefix;
}
function stringEndsWith(string, suffix) {
    return string.slice(-suffix.length) === suffix;
}

class FetchResponse {
    constructor(response) {
        this.response = response;
    }
    get succeeded() {
        return this.response.ok;
    }
    get failed() {
        return !this.succeeded;
    }
    get redirected() {
        return this.response.redirected;
    }
    get location() {
        return Location.wrap(this.response.url);
    }
    get isHTML() {
        return this.contentType && this.contentType.match(/^text\/html|^application\/xhtml\+xml/);
    }
    get statusCode() {
        return this.response.status;
    }
    get contentType() {
        return this.header("Content-Type");
    }
    get responseText() {
        return this.response.text();
    }
    get responseHTML() {
        if (this.isHTML) {
            return this.response.text();
        }
        else {
            return Promise.resolve(undefined);
        }
    }
    header(name) {
        return this.response.headers.get(name);
    }
}

function dispatch(eventName, { target, cancelable, detail } = {}) {
    const event = new CustomEvent(eventName, { cancelable, bubbles: true, detail });
    void (target || document.documentElement).dispatchEvent(event);
    return event;
}
function nextAnimationFrame() {
    return new Promise(resolve => requestAnimationFrame(() => resolve()));
}
function nextMicrotask() {
    return Promise.resolve();
}
function unindent(strings, ...values) {
    const lines = interpolate(strings, values).replace(/^\n/, "").split("\n");
    const match = lines[0].match(/^\s+/);
    const indent = match ? match[0].length : 0;
    return lines.map(line => line.slice(indent)).join("\n");
}
function interpolate(strings, values) {
    return strings.reduce((result, string, i) => {
        const value = values[i] == undefined ? "" : values[i];
        return result + string + value;
    }, "");
}
function uuid() {
    return Array.apply(null, { length: 36 }).map((_, i) => {
        if (i == 8 || i == 13 || i == 18 || i == 23) {
            return "-";
        }
        else if (i == 14) {
            return "4";
        }
        else if (i == 19) {
            return (Math.floor(Math.random() * 4) + 8).toString(16);
        }
        else {
            return Math.floor(Math.random() * 15).toString(16);
        }
    }).join("");
}

var FetchMethod;
(function (FetchMethod) {
    FetchMethod[FetchMethod["get"] = 0] = "get";
    FetchMethod[FetchMethod["post"] = 1] = "post";
    FetchMethod[FetchMethod["put"] = 2] = "put";
    FetchMethod[FetchMethod["patch"] = 3] = "patch";
    FetchMethod[FetchMethod["delete"] = 4] = "delete";
})(FetchMethod || (FetchMethod = {}));
function fetchMethodFromString(method) {
    switch (method.toLowerCase()) {
        case "get": return FetchMethod.get;
        case "post": return FetchMethod.post;
        case "put": return FetchMethod.put;
        case "patch": return FetchMethod.patch;
        case "delete": return FetchMethod.delete;
    }
}
class FetchRequest {
    constructor(delegate, method, location, body) {
        this.abortController = new AbortController;
        this.delegate = delegate;
        this.method = method;
        this.location = location;
        this.body = body;
    }
    get url() {
        const url = this.location.absoluteURL;
        const query = this.params.toString();
        if (this.isIdempotent && query.length) {
            return [url, query].join(url.includes("?") ? "&" : "?");
        }
        else {
            return url;
        }
    }
    get params() {
        return this.entries.reduce((params, [name, value]) => {
            params.append(name, value.toString());
            return params;
        }, new URLSearchParams);
    }
    get entries() {
        return this.body ? Array.from(this.body.entries()) : [];
    }
    cancel() {
        this.abortController.abort();
    }
    async perform() {
        const { fetchOptions } = this;
        dispatch("turbo:before-fetch-request", { detail: { fetchOptions } });
        try {
            this.delegate.requestStarted(this);
            const response = await fetch(this.url, fetchOptions);
            return await this.receive(response);
        }
        catch (error) {
            this.delegate.requestErrored(this, error);
            throw error;
        }
        finally {
            this.delegate.requestFinished(this);
        }
    }
    async receive(response) {
        const fetchResponse = new FetchResponse(response);
        const event = dispatch("turbo:before-fetch-response", { cancelable: true, detail: { fetchResponse } });
        if (event.defaultPrevented) {
            this.delegate.requestPreventedHandlingResponse(this, fetchResponse);
        }
        else if (fetchResponse.succeeded) {
            this.delegate.requestSucceededWithResponse(this, fetchResponse);
        }
        else {
            this.delegate.requestFailedWithResponse(this, fetchResponse);
        }
        return fetchResponse;
    }
    get fetchOptions() {
        return {
            method: FetchMethod[this.method].toUpperCase(),
            credentials: "same-origin",
            headers: this.headers,
            redirect: "follow",
            body: this.isIdempotent ? undefined : this.body,
            signal: this.abortSignal
        };
    }
    get isIdempotent() {
        return this.method == FetchMethod.get;
    }
    get headers() {
        return Object.assign({ "Accept": "text/html, application/xhtml+xml" }, this.additionalHeaders);
    }
    get additionalHeaders() {
        if (typeof this.delegate.additionalHeadersForRequest == "function") {
            return this.delegate.additionalHeadersForRequest(this);
        }
        else {
            return {};
        }
    }
    get abortSignal() {
        return this.abortController.signal;
    }
}

class FormInterceptor {
    constructor(delegate, element) {
        this.submitBubbled = ((event) => {
            if (event.target instanceof HTMLFormElement) {
                const form = event.target;
                const submitter = event.submitter || undefined;
                if (this.delegate.shouldInterceptFormSubmission(form, submitter)) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.delegate.formSubmissionIntercepted(form, submitter);
                }
            }
        });
        this.delegate = delegate;
        this.element = element;
    }
    start() {
        this.element.addEventListener("submit", this.submitBubbled);
    }
    stop() {
        this.element.removeEventListener("submit", this.submitBubbled);
    }
}

var FormSubmissionState;
(function (FormSubmissionState) {
    FormSubmissionState[FormSubmissionState["initialized"] = 0] = "initialized";
    FormSubmissionState[FormSubmissionState["requesting"] = 1] = "requesting";
    FormSubmissionState[FormSubmissionState["waiting"] = 2] = "waiting";
    FormSubmissionState[FormSubmissionState["receiving"] = 3] = "receiving";
    FormSubmissionState[FormSubmissionState["stopping"] = 4] = "stopping";
    FormSubmissionState[FormSubmissionState["stopped"] = 5] = "stopped";
})(FormSubmissionState || (FormSubmissionState = {}));
class FormSubmission {
    constructor(delegate, formElement, submitter, mustRedirect = false) {
        this.state = FormSubmissionState.initialized;
        this.delegate = delegate;
        this.formElement = formElement;
        this.formData = buildFormData(formElement, submitter);
        this.submitter = submitter;
        this.fetchRequest = new FetchRequest(this, this.method, this.location, this.formData);
        this.mustRedirect = mustRedirect;
    }
    get method() {
        var _a;
        const method = ((_a = this.submitter) === null || _a === void 0 ? void 0 : _a.getAttribute("formmethod")) || this.formElement.method;
        return fetchMethodFromString(method.toLowerCase()) || FetchMethod.get;
    }
    get action() {
        var _a;
        return ((_a = this.submitter) === null || _a === void 0 ? void 0 : _a.getAttribute("formaction")) || this.formElement.action;
    }
    get location() {
        return Location.wrap(this.action);
    }
    async start() {
        const { initialized, requesting } = FormSubmissionState;
        if (this.state == initialized) {
            this.state = requesting;
            return this.fetchRequest.perform();
        }
    }
    stop() {
        const { stopping, stopped } = FormSubmissionState;
        if (this.state != stopping && this.state != stopped) {
            this.state = stopping;
            this.fetchRequest.cancel();
            return true;
        }
    }
    additionalHeadersForRequest(request) {
        const headers = {};
        if (this.method != FetchMethod.get) {
            const token = getCookieValue(getMetaContent("csrf-param")) || getMetaContent("csrf-token");
            if (token) {
                headers["X-CSRF-Token"] = token;
            }
        }
        return headers;
    }
    requestStarted(request) {
        this.state = FormSubmissionState.waiting;
        dispatch("turbo:submit-start", { target: this.formElement, detail: { formSubmission: this } });
        this.delegate.formSubmissionStarted(this);
    }
    requestPreventedHandlingResponse(request, response) {
        this.result = { success: response.succeeded, fetchResponse: response };
    }
    requestSucceededWithResponse(request, response) {
        if (this.requestMustRedirect(request) && !response.redirected) {
            const error = new Error("Form responses must redirect to another location");
            this.delegate.formSubmissionErrored(this, error);
        }
        else {
            this.state = FormSubmissionState.receiving;
            this.result = { success: true, fetchResponse: response };
            this.delegate.formSubmissionSucceededWithResponse(this, response);
        }
    }
    requestFailedWithResponse(request, response) {
        this.result = { success: false, fetchResponse: response };
        this.delegate.formSubmissionFailedWithResponse(this, response);
    }
    requestErrored(request, error) {
        this.result = { success: false, error };
        this.delegate.formSubmissionErrored(this, error);
    }
    requestFinished(request) {
        this.state = FormSubmissionState.stopped;
        dispatch("turbo:submit-end", { target: this.formElement, detail: Object.assign({ formSubmission: this }, this.result) });
        this.delegate.formSubmissionFinished(this);
    }
    requestMustRedirect(request) {
        return !request.isIdempotent && this.mustRedirect;
    }
}
function buildFormData(formElement, submitter) {
    const formData = new FormData(formElement);
    const name = submitter === null || submitter === void 0 ? void 0 : submitter.getAttribute("name");
    const value = submitter === null || submitter === void 0 ? void 0 : submitter.getAttribute("value");
    if (name && formData.get(name) != value) {
        formData.append(name, value || "");
    }
    return formData;
}
function getCookieValue(cookieName) {
    if (cookieName != null) {
        const cookies = document.cookie ? document.cookie.split("; ") : [];
        const cookie = cookies.find((cookie) => cookie.startsWith(cookieName));
        if (cookie) {
            const value = cookie.split("=").slice(1).join("=");
            return value ? decodeURIComponent(value) : undefined;
        }
    }
}
function getMetaContent(name) {
    const element = document.querySelector(`meta[name="${name}"]`);
    return element && element.content;
}

class LinkInterceptor {
    constructor(delegate, element) {
        this.clickBubbled = (event) => {
            if (this.respondsToEventTarget(event.target)) {
                this.clickEvent = event;
            }
            else {
                delete this.clickEvent;
            }
        };
        this.linkClicked = ((event) => {
            if (this.clickEvent && this.respondsToEventTarget(event.target) && event.target instanceof Element) {
                if (this.delegate.shouldInterceptLinkClick(event.target, event.detail.url)) {
                    this.clickEvent.preventDefault();
                    event.preventDefault();
                    this.delegate.linkClickIntercepted(event.target, event.detail.url);
                }
            }
            delete this.clickEvent;
        });
        this.willVisit = () => {
            delete this.clickEvent;
        };
        this.delegate = delegate;
        this.element = element;
    }
    start() {
        this.element.addEventListener("click", this.clickBubbled);
        document.addEventListener("turbo:click", this.linkClicked);
        document.addEventListener("turbo:before-visit", this.willVisit);
    }
    stop() {
        this.element.removeEventListener("click", this.clickBubbled);
        document.removeEventListener("turbo:click", this.linkClicked);
        document.removeEventListener("turbo:before-visit", this.willVisit);
    }
    respondsToEventTarget(target) {
        const element = target instanceof Element
            ? target
            : target instanceof Node
                ? target.parentElement
                : null;
        return element && element.closest("turbo-frame, html") == this.element;
    }
}

class FrameController {
    constructor(element) {
        this.resolveVisitPromise = () => { };
        this.element = element;
        this.linkInterceptor = new LinkInterceptor(this, this.element);
        this.formInterceptor = new FormInterceptor(this, this.element);
    }
    connect() {
        this.linkInterceptor.start();
        this.formInterceptor.start();
    }
    disconnect() {
        this.linkInterceptor.stop();
        this.formInterceptor.stop();
    }
    shouldInterceptLinkClick(element, url) {
        return this.shouldInterceptNavigation(element);
    }
    linkClickIntercepted(element, url) {
        this.navigateFrame(element, url);
    }
    shouldInterceptFormSubmission(element) {
        return this.shouldInterceptNavigation(element);
    }
    formSubmissionIntercepted(element, submitter) {
        if (this.formSubmission) {
            this.formSubmission.stop();
        }
        this.formSubmission = new FormSubmission(this, element, submitter);
        if (this.formSubmission.fetchRequest.isIdempotent) {
            this.navigateFrame(element, this.formSubmission.fetchRequest.url);
        }
        else {
            this.formSubmission.start();
        }
    }
    async visit(url) {
        const location = Location.wrap(url);
        const request = new FetchRequest(this, FetchMethod.get, location);
        return new Promise(resolve => {
            this.resolveVisitPromise = () => {
                this.resolveVisitPromise = () => { };
                resolve();
            };
            request.perform();
        });
    }
    additionalHeadersForRequest(request) {
        return { "Turbo-Frame": this.id };
    }
    requestStarted(request) {
        this.element.setAttribute("busy", "");
    }
    requestPreventedHandlingResponse(request, response) {
        this.resolveVisitPromise();
    }
    async requestSucceededWithResponse(request, response) {
        await this.loadResponse(response);
        this.resolveVisitPromise();
    }
    requestFailedWithResponse(request, response) {
        console.error(response);
        this.resolveVisitPromise();
    }
    requestErrored(request, error) {
        console.error(error);
        this.resolveVisitPromise();
    }
    requestFinished(request) {
        this.element.removeAttribute("busy");
    }
    formSubmissionStarted(formSubmission) {
    }
    formSubmissionSucceededWithResponse(formSubmission, response) {
        const frame = this.findFrameElement(formSubmission.formElement);
        frame.controller.loadResponse(response);
    }
    formSubmissionFailedWithResponse(formSubmission, fetchResponse) {
    }
    formSubmissionErrored(formSubmission, error) {
    }
    formSubmissionFinished(formSubmission) {
    }
    navigateFrame(element, url) {
        const frame = this.findFrameElement(element);
        frame.src = url;
    }
    findFrameElement(element) {
        var _a;
        const id = element.getAttribute("data-turbo-frame");
        return (_a = getFrameElementById(id)) !== null && _a !== void 0 ? _a : this.element;
    }
    async loadResponse(response) {
        const fragment = fragmentFromHTML(await response.responseHTML);
        const element = await this.extractForeignFrameElement(fragment);
        if (element) {
            await nextAnimationFrame();
            this.loadFrameElement(element);
            this.scrollFrameIntoView(element);
            await nextAnimationFrame();
            this.focusFirstAutofocusableElement();
        }
    }
    async extractForeignFrameElement(container) {
        let element;
        const id = CSS.escape(this.id);
        if (element = activateElement(container.querySelector(`turbo-frame#${id}`))) {
            return element;
        }
        if (element = activateElement(container.querySelector(`turbo-frame[src][recurse~=${id}]`))) {
            await element.loaded;
            return await this.extractForeignFrameElement(element);
        }
    }
    loadFrameElement(frameElement) {
        var _a;
        const destinationRange = document.createRange();
        destinationRange.selectNodeContents(this.element);
        destinationRange.deleteContents();
        const sourceRange = (_a = frameElement.ownerDocument) === null || _a === void 0 ? void 0 : _a.createRange();
        if (sourceRange) {
            sourceRange.selectNodeContents(frameElement);
            this.element.appendChild(sourceRange.extractContents());
        }
    }
    focusFirstAutofocusableElement() {
        const element = this.firstAutofocusableElement;
        if (element) {
            element.focus();
            return true;
        }
        return false;
    }
    scrollFrameIntoView(frame) {
        if (this.element.autoscroll || frame.autoscroll) {
            const element = this.element.firstElementChild;
            const block = readScrollLogicalPosition(this.element.getAttribute("data-autoscroll-block"), "end");
            if (element) {
                element.scrollIntoView({ block });
                return true;
            }
        }
        return false;
    }
    shouldInterceptNavigation(element) {
        const id = element.getAttribute("data-turbo-frame") || this.element.getAttribute("target");
        if (!this.enabled || id == "_top") {
            return false;
        }
        if (id) {
            const frameElement = getFrameElementById(id);
            if (frameElement) {
                return !frameElement.disabled;
            }
        }
        return true;
    }
    get firstAutofocusableElement() {
        const element = this.element.querySelector("[autofocus]");
        return element instanceof HTMLElement ? element : null;
    }
    get id() {
        return this.element.id;
    }
    get enabled() {
        return !this.element.disabled;
    }
}
function getFrameElementById(id) {
    if (id != null) {
        const element = document.getElementById(id);
        if (element instanceof FrameElement) {
            return element;
        }
    }
}
function readScrollLogicalPosition(value, defaultValue) {
    if (value == "end" || value == "start" || value == "center" || value == "nearest") {
        return value;
    }
    else {
        return defaultValue;
    }
}
function fragmentFromHTML(html = "") {
    const foreignDocument = document.implementation.createHTMLDocument();
    return foreignDocument.createRange().createContextualFragment(html);
}
function activateElement(element) {
    if (element && element.ownerDocument !== document) {
        element = document.importNode(element, true);
    }
    if (element instanceof FrameElement) {
        return element;
    }
}

class FrameElement extends HTMLElement {
    constructor() {
        super();
        this.controller = new FrameController(this);
    }
    static get observedAttributes() {
        return ["src"];
    }
    connectedCallback() {
        this.controller.connect();
    }
    disconnectedCallback() {
        this.controller.disconnect();
    }
    attributeChangedCallback() {
        if (this.src && this.isActive) {
            const value = this.controller.visit(this.src);
            Object.defineProperty(this, "loaded", { value, configurable: true });
        }
    }
    formSubmissionIntercepted(element, submitter) {
        this.controller.formSubmissionIntercepted(element, submitter);
    }
    get src() {
        return this.getAttribute("src");
    }
    set src(value) {
        if (value) {
            this.setAttribute("src", value);
        }
        else {
            this.removeAttribute("src");
        }
    }
    get loaded() {
        return Promise.resolve(undefined);
    }
    get disabled() {
        return this.hasAttribute("disabled");
    }
    set disabled(value) {
        if (value) {
            this.setAttribute("disabled", "");
        }
        else {
            this.removeAttribute("disabled");
        }
    }
    get autoscroll() {
        return this.hasAttribute("autoscroll");
    }
    set autoscroll(value) {
        if (value) {
            this.setAttribute("autoscroll", "");
        }
        else {
            this.removeAttribute("autoscroll");
        }
    }
    get isActive() {
        return this.ownerDocument === document && !this.isPreview;
    }
    get isPreview() {
        var _a, _b;
        return (_b = (_a = this.ownerDocument) === null || _a === void 0 ? void 0 : _a.documentElement) === null || _b === void 0 ? void 0 : _b.hasAttribute("data-turbo-preview");
    }
}
customElements.define("turbo-frame", FrameElement);

const StreamActions = {
    append() {
        var _a;
        (_a = this.targetElement) === null || _a === void 0 ? void 0 : _a.append(this.templateContent);
    },
    prepend() {
        var _a;
        (_a = this.targetElement) === null || _a === void 0 ? void 0 : _a.prepend(this.templateContent);
    },
    remove() {
        var _a;
        (_a = this.targetElement) === null || _a === void 0 ? void 0 : _a.remove();
    },
    replace() {
        var _a;
        (_a = this.targetElement) === null || _a === void 0 ? void 0 : _a.replaceWith(this.templateContent);
    },
    update() {
        if (this.targetElement) {
            this.targetElement.innerHTML = "";
            this.targetElement.append(this.templateContent);
        }
    }
};

class StreamElement extends HTMLElement {
    async connectedCallback() {
        try {
            await this.render();
        }
        catch (error) {
            console.error(error);
        }
        finally {
            this.disconnect();
        }
    }
    async render() {
        var _a;
        return (_a = this.renderPromise) !== null && _a !== void 0 ? _a : (this.renderPromise = (async () => {
            if (this.dispatchEvent(this.beforeRenderEvent)) {
                await nextAnimationFrame();
                this.performAction();
            }
        })());
    }
    disconnect() {
        try {
            this.remove();
        }
        catch (_a) { }
    }
    get performAction() {
        if (this.action) {
            const actionFunction = StreamActions[this.action];
            if (actionFunction) {
                return actionFunction;
            }
            this.raise("unknown action");
        }
        this.raise("action attribute is missing");
    }
    get targetElement() {
        var _a;
        if (this.target) {
            return (_a = this.ownerDocument) === null || _a === void 0 ? void 0 : _a.getElementById(this.target);
        }
        this.raise("target attribute is missing");
    }
    get templateContent() {
        return this.templateElement.content;
    }
    get templateElement() {
        if (this.firstElementChild instanceof HTMLTemplateElement) {
            return this.firstElementChild;
        }
        this.raise("first child element must be a <template> element");
    }
    get action() {
        return this.getAttribute("action");
    }
    get target() {
        return this.getAttribute("target");
    }
    raise(message) {
        throw new Error(`${this.description}: ${message}`);
    }
    get description() {
        var _a, _b;
        return (_b = ((_a = this.outerHTML.match(/<[^>]+>/)) !== null && _a !== void 0 ? _a : [])[0]) !== null && _b !== void 0 ? _b : "<turbo-stream>";
    }
    get beforeRenderEvent() {
        return new CustomEvent("turbo:before-stream-render", { bubbles: true, cancelable: true });
    }
}
customElements.define("turbo-stream", StreamElement);

(() => {
    let element = document.currentScript;
    if (!element)
        return;
    if (element.hasAttribute("data-turbo-suppress-warning"))
        return;
    while (element = element.parentElement) {
        if (element == document.body) {
            return console.warn(unindent `
        You are loading Turbo from a <script> element inside the <body> element. This is probably not what you meant to do!

        Load your application’s JavaScript bundle inside the <head> element instead. <script> elements in <body> are evaluated with each page change.

        For more information, see: https://turbo.hotwire.dev/handbook/building#working-with-script-elements

        ——
        Suppress this warning by adding a "data-turbo-suppress-warning" attribute to: %s
      `, element.outerHTML);
        }
    }
})();

class ProgressBar {
    constructor() {
        this.hiding = false;
        this.value = 0;
        this.visible = false;
        this.trickle = () => {
            this.setValue(this.value + Math.random() / 100);
        };
        this.stylesheetElement = this.createStylesheetElement();
        this.progressElement = this.createProgressElement();
        this.installStylesheetElement();
        this.setValue(0);
    }
    static get defaultCSS() {
        return unindent `
      .turbo-progress-bar {
        position: fixed;
        display: block;
        top: 0;
        left: 0;
        height: 3px;
        background: #0076ff;
        z-index: 9999;
        transition:
          width ${ProgressBar.animationDuration}ms ease-out,
          opacity ${ProgressBar.animationDuration / 2}ms ${ProgressBar.animationDuration / 2}ms ease-in;
        transform: translate3d(0, 0, 0);
      }
    `;
    }
    show() {
        if (!this.visible) {
            this.visible = true;
            this.installProgressElement();
            this.startTrickling();
        }
    }
    hide() {
        if (this.visible && !this.hiding) {
            this.hiding = true;
            this.fadeProgressElement(() => {
                this.uninstallProgressElement();
                this.stopTrickling();
                this.visible = false;
                this.hiding = false;
            });
        }
    }
    setValue(value) {
        this.value = value;
        this.refresh();
    }
    installStylesheetElement() {
        document.head.insertBefore(this.stylesheetElement, document.head.firstChild);
    }
    installProgressElement() {
        this.progressElement.style.width = "0";
        this.progressElement.style.opacity = "1";
        document.documentElement.insertBefore(this.progressElement, document.body);
        this.refresh();
    }
    fadeProgressElement(callback) {
        this.progressElement.style.opacity = "0";
        setTimeout(callback, ProgressBar.animationDuration * 1.5);
    }
    uninstallProgressElement() {
        if (this.progressElement.parentNode) {
            document.documentElement.removeChild(this.progressElement);
        }
    }
    startTrickling() {
        if (!this.trickleInterval) {
            this.trickleInterval = window.setInterval(this.trickle, ProgressBar.animationDuration);
        }
    }
    stopTrickling() {
        window.clearInterval(this.trickleInterval);
        delete this.trickleInterval;
    }
    refresh() {
        requestAnimationFrame(() => {
            this.progressElement.style.width = `${10 + (this.value * 90)}%`;
        });
    }
    createStylesheetElement() {
        const element = document.createElement("style");
        element.type = "text/css";
        element.textContent = ProgressBar.defaultCSS;
        return element;
    }
    createProgressElement() {
        const element = document.createElement("div");
        element.className = "turbo-progress-bar";
        return element;
    }
}
ProgressBar.animationDuration = 300;

class HeadDetails {
    constructor(children) {
        this.detailsByOuterHTML = children.reduce((result, element) => {
            const { outerHTML } = element;
            const details = outerHTML in result
                ? result[outerHTML]
                : {
                    type: elementType(element),
                    tracked: elementIsTracked(element),
                    elements: []
                };
            return Object.assign(Object.assign({}, result), { [outerHTML]: Object.assign(Object.assign({}, details), { elements: [...details.elements, element] }) });
        }, {});
    }
    static fromHeadElement(headElement) {
        const children = headElement ? [...headElement.children] : [];
        return new this(children);
    }
    getTrackedElementSignature() {
        return Object.keys(this.detailsByOuterHTML)
            .filter(outerHTML => this.detailsByOuterHTML[outerHTML].tracked)
            .join("");
    }
    getScriptElementsNotInDetails(headDetails) {
        return this.getElementsMatchingTypeNotInDetails("script", headDetails);
    }
    getStylesheetElementsNotInDetails(headDetails) {
        return this.getElementsMatchingTypeNotInDetails("stylesheet", headDetails);
    }
    getElementsMatchingTypeNotInDetails(matchedType, headDetails) {
        return Object.keys(this.detailsByOuterHTML)
            .filter(outerHTML => !(outerHTML in headDetails.detailsByOuterHTML))
            .map(outerHTML => this.detailsByOuterHTML[outerHTML])
            .filter(({ type }) => type == matchedType)
            .map(({ elements: [element] }) => element);
    }
    getProvisionalElements() {
        return Object.keys(this.detailsByOuterHTML).reduce((result, outerHTML) => {
            const { type, tracked, elements } = this.detailsByOuterHTML[outerHTML];
            if (type == null && !tracked) {
                return [...result, ...elements];
            }
            else if (elements.length > 1) {
                return [...result, ...elements.slice(1)];
            }
            else {
                return result;
            }
        }, []);
    }
    getMetaValue(name) {
        const element = this.findMetaElementByName(name);
        return element
            ? element.getAttribute("content")
            : null;
    }
    findMetaElementByName(name) {
        return Object.keys(this.detailsByOuterHTML).reduce((result, outerHTML) => {
            const { elements: [element] } = this.detailsByOuterHTML[outerHTML];
            return elementIsMetaElementWithName(element, name) ? element : result;
        }, undefined);
    }
}
function elementType(element) {
    if (elementIsScript(element)) {
        return "script";
    }
    else if (elementIsStylesheet(element)) {
        return "stylesheet";
    }
}
function elementIsTracked(element) {
    return element.getAttribute("data-turbo-track") == "reload";
}
function elementIsScript(element) {
    const tagName = element.tagName.toLowerCase();
    return tagName == "script";
}
function elementIsStylesheet(element) {
    const tagName = element.tagName.toLowerCase();
    return tagName == "style" || (tagName == "link" && element.getAttribute("rel") == "stylesheet");
}
function elementIsMetaElementWithName(element, name) {
    const tagName = element.tagName.toLowerCase();
    return tagName == "meta" && element.getAttribute("name") == name;
}

class Snapshot {
    constructor(headDetails, bodyElement) {
        this.headDetails = headDetails;
        this.bodyElement = bodyElement;
    }
    static wrap(value) {
        if (value instanceof this) {
            return value;
        }
        else if (typeof value == "string") {
            return this.fromHTMLString(value);
        }
        else {
            return this.fromHTMLElement(value);
        }
    }
    static fromHTMLString(html) {
        const { documentElement } = new DOMParser().parseFromString(html, "text/html");
        return this.fromHTMLElement(documentElement);
    }
    static fromHTMLElement(htmlElement) {
        const headElement = htmlElement.querySelector("head");
        const bodyElement = htmlElement.querySelector("body") || document.createElement("body");
        const headDetails = HeadDetails.fromHeadElement(headElement);
        return new this(headDetails, bodyElement);
    }
    clone() {
        const { bodyElement } = Snapshot.fromHTMLString(this.bodyElement.outerHTML);
        return new Snapshot(this.headDetails, bodyElement);
    }
    getRootLocation() {
        const root = this.getSetting("root", "/");
        return new Location(root);
    }
    getCacheControlValue() {
        return this.getSetting("cache-control");
    }
    getElementForAnchor(anchor) {
        try {
            return this.bodyElement.querySelector(`[id='${anchor}'], a[name='${anchor}']`);
        }
        catch (_a) {
            return null;
        }
    }
    getPermanentElements() {
        return [...this.bodyElement.querySelectorAll("[id][data-turbo-permanent]")];
    }
    getPermanentElementById(id) {
        return this.bodyElement.querySelector(`#${id}[data-turbo-permanent]`);
    }
    getPermanentElementsPresentInSnapshot(snapshot) {
        return this.getPermanentElements().filter(({ id }) => snapshot.getPermanentElementById(id));
    }
    findFirstAutofocusableElement() {
        return this.bodyElement.querySelector("[autofocus]");
    }
    hasAnchor(anchor) {
        return this.getElementForAnchor(anchor) != null;
    }
    isPreviewable() {
        return this.getCacheControlValue() != "no-preview";
    }
    isCacheable() {
        return this.getCacheControlValue() != "no-cache";
    }
    isVisitable() {
        return this.getSetting("visit-control") != "reload";
    }
    getSetting(name, defaultValue) {
        const value = this.headDetails.getMetaValue(`turbo-${name}`);
        return value == null ? defaultValue : value;
    }
}

var TimingMetric;
(function (TimingMetric) {
    TimingMetric["visitStart"] = "visitStart";
    TimingMetric["requestStart"] = "requestStart";
    TimingMetric["requestEnd"] = "requestEnd";
    TimingMetric["visitEnd"] = "visitEnd";
})(TimingMetric || (TimingMetric = {}));
var VisitState;
(function (VisitState) {
    VisitState["initialized"] = "initialized";
    VisitState["started"] = "started";
    VisitState["canceled"] = "canceled";
    VisitState["failed"] = "failed";
    VisitState["completed"] = "completed";
})(VisitState || (VisitState = {}));
const defaultOptions = {
    action: "advance",
    historyChanged: false
};
var SystemStatusCode;
(function (SystemStatusCode) {
    SystemStatusCode[SystemStatusCode["networkFailure"] = 0] = "networkFailure";
    SystemStatusCode[SystemStatusCode["timeoutFailure"] = -1] = "timeoutFailure";
    SystemStatusCode[SystemStatusCode["contentTypeMismatch"] = -2] = "contentTypeMismatch";
})(SystemStatusCode || (SystemStatusCode = {}));
class Visit {
    constructor(delegate, location, restorationIdentifier, options = {}) {
        this.identifier = uuid();
        this.timingMetrics = {};
        this.followedRedirect = false;
        this.historyChanged = false;
        this.scrolled = false;
        this.snapshotCached = false;
        this.state = VisitState.initialized;
        this.performScroll = () => {
            if (!this.scrolled) {
                if (this.action == "restore") {
                    this.scrollToRestoredPosition() || this.scrollToTop();
                }
                else {
                    this.scrollToAnchor() || this.scrollToTop();
                }
                this.scrolled = true;
            }
        };
        this.delegate = delegate;
        this.location = location;
        this.restorationIdentifier = restorationIdentifier || uuid();
        const { action, historyChanged, referrer, snapshotHTML, response } = Object.assign(Object.assign({}, defaultOptions), options);
        this.action = action;
        this.historyChanged = historyChanged;
        this.referrer = referrer;
        this.snapshotHTML = snapshotHTML;
        this.response = response;
    }
    get adapter() {
        return this.delegate.adapter;
    }
    get view() {
        return this.delegate.view;
    }
    get history() {
        return this.delegate.history;
    }
    get restorationData() {
        return this.history.getRestorationDataForIdentifier(this.restorationIdentifier);
    }
    start() {
        if (this.state == VisitState.initialized) {
            this.recordTimingMetric(TimingMetric.visitStart);
            this.state = VisitState.started;
            this.adapter.visitStarted(this);
            this.delegate.visitStarted(this);
        }
    }
    cancel() {
        if (this.state == VisitState.started) {
            if (this.request) {
                this.request.cancel();
            }
            this.cancelRender();
            this.state = VisitState.canceled;
        }
    }
    complete() {
        if (this.state == VisitState.started) {
            this.recordTimingMetric(TimingMetric.visitEnd);
            this.state = VisitState.completed;
            this.adapter.visitCompleted(this);
            this.delegate.visitCompleted(this);
        }
    }
    fail() {
        if (this.state == VisitState.started) {
            this.state = VisitState.failed;
            this.adapter.visitFailed(this);
        }
    }
    changeHistory() {
        if (!this.historyChanged) {
            const actionForHistory = this.location.isEqualTo(this.referrer) ? "replace" : this.action;
            const method = this.getHistoryMethodForAction(actionForHistory);
            this.history.update(method, this.location, this.restorationIdentifier);
            this.historyChanged = true;
        }
    }
    issueRequest() {
        if (this.hasPreloadedResponse()) {
            this.simulateRequest();
        }
        else if (this.shouldIssueRequest() && !this.request) {
            this.request = new FetchRequest(this, FetchMethod.get, this.location);
            this.request.perform();
        }
    }
    simulateRequest() {
        if (this.response) {
            this.startRequest();
            this.recordResponse();
            this.finishRequest();
        }
    }
    startRequest() {
        this.recordTimingMetric(TimingMetric.requestStart);
        this.adapter.visitRequestStarted(this);
    }
    recordResponse(response = this.response) {
        this.response = response;
        if (response) {
            const { statusCode } = response;
            if (isSuccessful(statusCode)) {
                this.adapter.visitRequestCompleted(this);
            }
            else {
                this.adapter.visitRequestFailedWithStatusCode(this, statusCode);
            }
        }
    }
    finishRequest() {
        this.recordTimingMetric(TimingMetric.requestEnd);
        this.adapter.visitRequestFinished(this);
    }
    loadResponse() {
        if (this.response) {
            const { statusCode, responseHTML } = this.response;
            this.render(() => {
                this.cacheSnapshot();
                if (isSuccessful(statusCode) && responseHTML != null) {
                    this.view.render({ snapshot: Snapshot.fromHTMLString(responseHTML) }, this.performScroll);
                    this.adapter.visitRendered(this);
                    this.complete();
                }
                else {
                    this.view.render({ error: responseHTML }, this.performScroll);
                    this.adapter.visitRendered(this);
                    this.fail();
                }
            });
        }
    }
    getCachedSnapshot() {
        const snapshot = this.view.getCachedSnapshotForLocation(this.location) || this.getPreloadedSnapshot();
        if (snapshot && (!this.location.anchor || snapshot.hasAnchor(this.location.anchor))) {
            if (this.action == "restore" || snapshot.isPreviewable()) {
                return snapshot;
            }
        }
    }
    getPreloadedSnapshot() {
        if (this.snapshotHTML) {
            return Snapshot.wrap(this.snapshotHTML);
        }
    }
    hasCachedSnapshot() {
        return this.getCachedSnapshot() != null;
    }
    loadCachedSnapshot() {
        const snapshot = this.getCachedSnapshot();
        if (snapshot) {
            const isPreview = this.shouldIssueRequest();
            this.render(() => {
                this.cacheSnapshot();
                this.view.render({ snapshot, isPreview }, this.performScroll);
                this.adapter.visitRendered(this);
                if (!isPreview) {
                    this.complete();
                }
            });
        }
    }
    followRedirect() {
        if (this.redirectedToLocation && !this.followedRedirect) {
            this.location = this.redirectedToLocation;
            this.history.replace(this.redirectedToLocation, this.restorationIdentifier);
            this.followedRedirect = true;
        }
    }
    requestStarted() {
        this.startRequest();
    }
    requestPreventedHandlingResponse(request, response) {
    }
    async requestSucceededWithResponse(request, response) {
        const responseHTML = await response.responseHTML;
        if (responseHTML == undefined) {
            this.recordResponse({ statusCode: SystemStatusCode.contentTypeMismatch });
        }
        else {
            this.redirectedToLocation = response.redirected ? response.location : undefined;
            this.recordResponse({ statusCode: response.statusCode, responseHTML });
        }
    }
    async requestFailedWithResponse(request, response) {
        const responseHTML = await response.responseHTML;
        if (responseHTML == undefined) {
            this.recordResponse({ statusCode: SystemStatusCode.contentTypeMismatch });
        }
        else {
            this.recordResponse({ statusCode: response.statusCode, responseHTML });
        }
    }
    requestErrored(request, error) {
        this.recordResponse({ statusCode: SystemStatusCode.networkFailure });
    }
    requestFinished() {
        this.finishRequest();
    }
    scrollToRestoredPosition() {
        const { scrollPosition } = this.restorationData;
        if (scrollPosition) {
            this.view.scrollToPosition(scrollPosition);
            return true;
        }
    }
    scrollToAnchor() {
        if (this.location.anchor != null) {
            this.view.scrollToAnchor(this.location.anchor);
            return true;
        }
    }
    scrollToTop() {
        this.view.scrollToPosition({ x: 0, y: 0 });
    }
    recordTimingMetric(metric) {
        this.timingMetrics[metric] = new Date().getTime();
    }
    getTimingMetrics() {
        return Object.assign({}, this.timingMetrics);
    }
    getHistoryMethodForAction(action) {
        switch (action) {
            case "replace": return history.replaceState;
            case "advance":
            case "restore": return history.pushState;
        }
    }
    hasPreloadedResponse() {
        return typeof this.response == "object";
    }
    shouldIssueRequest() {
        return this.action == "restore"
            ? !this.hasCachedSnapshot()
            : true;
    }
    cacheSnapshot() {
        if (!this.snapshotCached) {
            this.view.cacheSnapshot();
            this.snapshotCached = true;
        }
    }
    render(callback) {
        this.cancelRender();
        this.frame = requestAnimationFrame(() => {
            delete this.frame;
            callback.call(this);
        });
    }
    cancelRender() {
        if (this.frame) {
            cancelAnimationFrame(this.frame);
            delete this.frame;
        }
    }
}
function isSuccessful(statusCode) {
    return statusCode >= 200 && statusCode < 300;
}

class BrowserAdapter {
    constructor(session) {
        this.progressBar = new ProgressBar;
        this.showProgressBar = () => {
            this.progressBar.show();
        };
        this.session = session;
    }
    visitProposedToLocation(location, options) {
        this.navigator.startVisit(location, uuid(), options);
    }
    visitStarted(visit) {
        visit.issueRequest();
        visit.changeHistory();
        visit.loadCachedSnapshot();
    }
    visitRequestStarted(visit) {
        this.progressBar.setValue(0);
        if (visit.hasCachedSnapshot() || visit.action != "restore") {
            this.showProgressBarAfterDelay();
        }
        else {
            this.showProgressBar();
        }
    }
    visitRequestCompleted(visit) {
        visit.loadResponse();
    }
    visitRequestFailedWithStatusCode(visit, statusCode) {
        switch (statusCode) {
            case SystemStatusCode.networkFailure:
            case SystemStatusCode.timeoutFailure:
            case SystemStatusCode.contentTypeMismatch:
                return this.reload();
            default:
                return visit.loadResponse();
        }
    }
    visitRequestFinished(visit) {
        this.progressBar.setValue(1);
        this.hideProgressBar();
    }
    visitCompleted(visit) {
        visit.followRedirect();
    }
    pageInvalidated() {
        this.reload();
    }
    visitFailed(visit) {
    }
    visitRendered(visit) {
    }
    showProgressBarAfterDelay() {
        this.progressBarTimeout = window.setTimeout(this.showProgressBar, this.session.progressBarDelay);
    }
    hideProgressBar() {
        this.progressBar.hide();
        if (this.progressBarTimeout != null) {
            window.clearTimeout(this.progressBarTimeout);
            delete this.progressBarTimeout;
        }
    }
    reload() {
        window.location.reload();
    }
    get navigator() {
        return this.session.navigator;
    }
}

class FormSubmitObserver {
    constructor(delegate) {
        this.started = false;
        this.submitCaptured = () => {
            removeEventListener("submit", this.submitBubbled, false);
            addEventListener("submit", this.submitBubbled, false);
        };
        this.submitBubbled = ((event) => {
            if (!event.defaultPrevented) {
                const form = event.target instanceof HTMLFormElement ? event.target : undefined;
                const submitter = event.submitter || undefined;
                if (form) {
                    if (this.delegate.willSubmitForm(form, submitter)) {
                        event.preventDefault();
                        this.delegate.formSubmitted(form, submitter);
                    }
                }
            }
        });
        this.delegate = delegate;
    }
    start() {
        if (!this.started) {
            addEventListener("submit", this.submitCaptured, true);
            this.started = true;
        }
    }
    stop() {
        if (this.started) {
            removeEventListener("submit", this.submitCaptured, true);
            this.started = false;
        }
    }
}

class FrameRedirector {
    constructor(element) {
        this.element = element;
        this.linkInterceptor = new LinkInterceptor(this, element);
        this.formInterceptor = new FormInterceptor(this, element);
    }
    start() {
        this.linkInterceptor.start();
        this.formInterceptor.start();
    }
    stop() {
        this.linkInterceptor.stop();
        this.formInterceptor.stop();
    }
    shouldInterceptLinkClick(element, url) {
        return this.shouldRedirect(element);
    }
    linkClickIntercepted(element, url) {
        const frame = this.findFrameElement(element);
        if (frame) {
            frame.src = url;
        }
    }
    shouldInterceptFormSubmission(element, submitter) {
        return this.shouldRedirect(element, submitter);
    }
    formSubmissionIntercepted(element, submitter) {
        const frame = this.findFrameElement(element);
        if (frame) {
            frame.formSubmissionIntercepted(element, submitter);
        }
    }
    shouldRedirect(element, submitter) {
        const frame = this.findFrameElement(element);
        return frame ? frame != element.closest("turbo-frame") : false;
    }
    findFrameElement(element) {
        const id = element.getAttribute("data-turbo-frame");
        if (id && id != "_top") {
            const frame = this.element.querySelector(`#${id}:not([disabled])`);
            if (frame instanceof FrameElement) {
                return frame;
            }
        }
    }
}

class History {
    constructor(delegate) {
        this.restorationIdentifier = uuid();
        this.restorationData = {};
        this.started = false;
        this.pageLoaded = false;
        this.onPopState = (event) => {
            if (this.shouldHandlePopState()) {
                const { turbo } = event.state || {};
                if (turbo) {
                    const location = Location.currentLocation;
                    this.location = location;
                    const { restorationIdentifier } = turbo;
                    this.restorationIdentifier = restorationIdentifier;
                    this.delegate.historyPoppedToLocationWithRestorationIdentifier(location, restorationIdentifier);
                }
            }
        };
        this.onPageLoad = async (event) => {
            await nextMicrotask();
            this.pageLoaded = true;
        };
        this.delegate = delegate;
    }
    start() {
        if (!this.started) {
            this.previousScrollRestoration = history.scrollRestoration;
            history.scrollRestoration = "manual";
            addEventListener("popstate", this.onPopState, false);
            addEventListener("load", this.onPageLoad, false);
            this.started = true;
            this.replace(Location.currentLocation);
        }
    }
    stop() {
        var _a;
        if (this.started) {
            history.scrollRestoration = (_a = this.previousScrollRestoration) !== null && _a !== void 0 ? _a : "auto";
            removeEventListener("popstate", this.onPopState, false);
            removeEventListener("load", this.onPageLoad, false);
            this.started = false;
        }
    }
    push(location, restorationIdentifier) {
        this.update(history.pushState, location, restorationIdentifier);
    }
    replace(location, restorationIdentifier) {
        this.update(history.replaceState, location, restorationIdentifier);
    }
    update(method, location, restorationIdentifier = uuid()) {
        const state = { turbo: { restorationIdentifier } };
        method.call(history, state, "", location.absoluteURL);
        this.location = location;
        this.restorationIdentifier = restorationIdentifier;
    }
    getRestorationDataForIdentifier(restorationIdentifier) {
        return this.restorationData[restorationIdentifier] || {};
    }
    updateRestorationData(additionalData) {
        const { restorationIdentifier } = this;
        const restorationData = this.restorationData[restorationIdentifier];
        this.restorationData[restorationIdentifier] = Object.assign(Object.assign({}, restorationData), additionalData);
    }
    shouldHandlePopState() {
        return this.pageIsLoaded();
    }
    pageIsLoaded() {
        return this.pageLoaded || document.readyState == "complete";
    }
}

class LinkClickObserver {
    constructor(delegate) {
        this.started = false;
        this.clickCaptured = () => {
            removeEventListener("click", this.clickBubbled, false);
            addEventListener("click", this.clickBubbled, false);
        };
        this.clickBubbled = (event) => {
            if (this.clickEventIsSignificant(event)) {
                const link = this.findLinkFromClickTarget(event.target);
                if (link) {
                    const location = this.getLocationForLink(link);
                    if (this.delegate.willFollowLinkToLocation(link, location)) {
                        event.preventDefault();
                        this.delegate.followedLinkToLocation(link, location);
                    }
                }
            }
        };
        this.delegate = delegate;
    }
    start() {
        if (!this.started) {
            addEventListener("click", this.clickCaptured, true);
            this.started = true;
        }
    }
    stop() {
        if (this.started) {
            removeEventListener("click", this.clickCaptured, true);
            this.started = false;
        }
    }
    clickEventIsSignificant(event) {
        return !((event.target && event.target.isContentEditable)
            || event.defaultPrevented
            || event.which > 1
            || event.altKey
            || event.ctrlKey
            || event.metaKey
            || event.shiftKey);
    }
    findLinkFromClickTarget(target) {
        if (target instanceof Element) {
            return target.closest("a[href]:not([target^=_]):not([download])");
        }
    }
    getLocationForLink(link) {
        return new Location(link.getAttribute("href") || "");
    }
}

class Navigator {
    constructor(delegate) {
        this.delegate = delegate;
    }
    proposeVisit(location, options = {}) {
        if (this.delegate.allowsVisitingLocation(location)) {
            this.delegate.visitProposedToLocation(location, options);
        }
    }
    startVisit(location, restorationIdentifier, options = {}) {
        this.stop();
        this.currentVisit = new Visit(this, Location.wrap(location), restorationIdentifier, Object.assign({ referrer: this.location }, options));
        this.currentVisit.start();
    }
    submitForm(form, submitter) {
        this.stop();
        this.formSubmission = new FormSubmission(this, form, submitter, true);
        this.formSubmission.start();
    }
    stop() {
        if (this.formSubmission) {
            this.formSubmission.stop();
            delete this.formSubmission;
        }
        if (this.currentVisit) {
            this.currentVisit.cancel();
            delete this.currentVisit;
        }
    }
    get adapter() {
        return this.delegate.adapter;
    }
    get view() {
        return this.delegate.view;
    }
    get history() {
        return this.delegate.history;
    }
    formSubmissionStarted(formSubmission) {
    }
    async formSubmissionSucceededWithResponse(formSubmission, fetchResponse) {
        console.log("Form submission succeeded", formSubmission);
        if (formSubmission == this.formSubmission) {
            const responseHTML = await fetchResponse.responseHTML;
            if (responseHTML) {
                if (formSubmission.method != FetchMethod.get) {
                    console.log("Clearing snapshot cache after successful form submission");
                    this.view.clearSnapshotCache();
                }
                const { statusCode } = fetchResponse;
                const visitOptions = { response: { statusCode, responseHTML } };
                console.log("Visiting", fetchResponse.location, visitOptions);
                this.proposeVisit(fetchResponse.location, visitOptions);
            }
        }
    }
    formSubmissionFailedWithResponse(formSubmission, fetchResponse) {
        console.error("Form submission failed", formSubmission, fetchResponse);
    }
    formSubmissionErrored(formSubmission, error) {
        console.error("Form submission failed", formSubmission, error);
    }
    formSubmissionFinished(formSubmission) {
    }
    visitStarted(visit) {
        this.delegate.visitStarted(visit);
    }
    visitCompleted(visit) {
        this.delegate.visitCompleted(visit);
    }
    get location() {
        return this.history.location;
    }
    get restorationIdentifier() {
        return this.history.restorationIdentifier;
    }
}

var PageStage;
(function (PageStage) {
    PageStage[PageStage["initial"] = 0] = "initial";
    PageStage[PageStage["loading"] = 1] = "loading";
    PageStage[PageStage["interactive"] = 2] = "interactive";
    PageStage[PageStage["complete"] = 3] = "complete";
    PageStage[PageStage["invalidated"] = 4] = "invalidated";
})(PageStage || (PageStage = {}));
class PageObserver {
    constructor(delegate) {
        this.stage = PageStage.initial;
        this.started = false;
        this.interpretReadyState = () => {
            const { readyState } = this;
            if (readyState == "interactive") {
                this.pageIsInteractive();
            }
            else if (readyState == "complete") {
                this.pageIsComplete();
            }
        };
        this.delegate = delegate;
    }
    start() {
        if (!this.started) {
            if (this.stage == PageStage.initial) {
                this.stage = PageStage.loading;
            }
            document.addEventListener("readystatechange", this.interpretReadyState, false);
            this.started = true;
        }
    }
    stop() {
        if (this.started) {
            document.removeEventListener("readystatechange", this.interpretReadyState, false);
            this.started = false;
        }
    }
    invalidate() {
        if (this.stage != PageStage.invalidated) {
            this.stage = PageStage.invalidated;
            this.delegate.pageInvalidated();
        }
    }
    pageIsInteractive() {
        if (this.stage == PageStage.loading) {
            this.stage = PageStage.interactive;
            this.delegate.pageBecameInteractive();
        }
    }
    pageIsComplete() {
        this.pageIsInteractive();
        if (this.stage == PageStage.interactive) {
            this.stage = PageStage.complete;
            this.delegate.pageLoaded();
        }
    }
    get readyState() {
        return document.readyState;
    }
}

class ScrollObserver {
    constructor(delegate) {
        this.started = false;
        this.onScroll = () => {
            this.updatePosition({ x: window.pageXOffset, y: window.pageYOffset });
        };
        this.delegate = delegate;
    }
    start() {
        if (!this.started) {
            addEventListener("scroll", this.onScroll, false);
            this.onScroll();
            this.started = true;
        }
    }
    stop() {
        if (this.started) {
            removeEventListener("scroll", this.onScroll, false);
            this.started = false;
        }
    }
    updatePosition(position) {
        this.delegate.scrollPositionChanged(position);
    }
}

class StreamMessage {
    constructor(html) {
        this.templateElement = document.createElement("template");
        this.templateElement.innerHTML = html;
    }
    static wrap(message) {
        if (typeof message == "string") {
            return new this(message);
        }
        else {
            return message;
        }
    }
    get fragment() {
        const fragment = document.createDocumentFragment();
        for (const element of this.foreignElements) {
            fragment.appendChild(document.importNode(element, true));
        }
        return fragment;
    }
    get foreignElements() {
        return this.templateChildren.reduce((streamElements, child) => {
            if (child.tagName.toLowerCase() == "turbo-stream") {
                return [...streamElements, child];
            }
            else {
                return streamElements;
            }
        }, []);
    }
    get templateChildren() {
        return Array.from(this.templateElement.content.children);
    }
}

class StreamObserver {
    constructor(delegate) {
        this.sources = new Set;
        this.started = false;
        this.prepareFetchRequest = ((event) => {
            var _a;
            const fetchOptions = (_a = event.detail) === null || _a === void 0 ? void 0 : _a.fetchOptions;
            if (fetchOptions) {
                const { headers } = fetchOptions;
                headers.Accept = ["text/html; turbo-stream", headers.Accept].join(", ");
            }
        });
        this.inspectFetchResponse = ((event) => {
            const response = fetchResponseFromEvent(event);
            if (response && fetchResponseIsStream(response)) {
                event.preventDefault();
                this.receiveMessageResponse(response);
            }
        });
        this.receiveMessageEvent = (event) => {
            if (this.started && typeof event.data == "string") {
                this.receiveMessageHTML(event.data);
            }
        };
        this.delegate = delegate;
    }
    start() {
        if (!this.started) {
            this.started = true;
            addEventListener("turbo:before-fetch-request", this.prepareFetchRequest, true);
            addEventListener("turbo:before-fetch-response", this.inspectFetchResponse, false);
        }
    }
    stop() {
        if (this.started) {
            this.started = false;
            removeEventListener("turbo:before-fetch-request", this.prepareFetchRequest, true);
            removeEventListener("turbo:before-fetch-response", this.inspectFetchResponse, false);
        }
    }
    connectStreamSource(source) {
        if (!this.streamSourceIsConnected(source)) {
            this.sources.add(source);
            source.addEventListener("message", this.receiveMessageEvent, false);
        }
    }
    disconnectStreamSource(source) {
        if (this.streamSourceIsConnected(source)) {
            this.sources.delete(source);
            source.removeEventListener("message", this.receiveMessageEvent, false);
        }
    }
    streamSourceIsConnected(source) {
        return this.sources.has(source);
    }
    async receiveMessageResponse(response) {
        const html = await response.responseHTML;
        if (html) {
            this.receiveMessageHTML(html);
        }
    }
    receiveMessageHTML(html) {
        this.delegate.receivedMessageFromStream(new StreamMessage(html));
    }
}
function fetchResponseFromEvent(event) {
    var _a;
    const fetchResponse = (_a = event.detail) === null || _a === void 0 ? void 0 : _a.fetchResponse;
    if (fetchResponse instanceof FetchResponse) {
        return fetchResponse;
    }
}
function fetchResponseIsStream(response) {
    var _a;
    const contentType = (_a = response.contentType) !== null && _a !== void 0 ? _a : "";
    return /text\/html;.*\bturbo-stream\b/.test(contentType);
}

function isAction(action) {
    return action == "advance" || action == "replace" || action == "restore";
}

class Renderer {
    renderView(callback) {
        this.delegate.viewWillRender(this.newBody);
        callback();
        this.delegate.viewRendered(this.newBody);
    }
    invalidateView() {
        this.delegate.viewInvalidated();
    }
    createScriptElement(element) {
        if (element.getAttribute("data-turbo-eval") == "false") {
            return element;
        }
        else {
            const createdScriptElement = document.createElement("script");
            createdScriptElement.textContent = element.textContent;
            createdScriptElement.async = false;
            copyElementAttributes(createdScriptElement, element);
            return createdScriptElement;
        }
    }
}
function copyElementAttributes(destinationElement, sourceElement) {
    for (const { name, value } of [...sourceElement.attributes]) {
        destinationElement.setAttribute(name, value);
    }
}

class ErrorRenderer extends Renderer {
    constructor(delegate, html) {
        super();
        this.delegate = delegate;
        this.htmlElement = (() => {
            const htmlElement = document.createElement("html");
            htmlElement.innerHTML = html;
            return htmlElement;
        })();
        this.newHead = this.htmlElement.querySelector("head") || document.createElement("head");
        this.newBody = this.htmlElement.querySelector("body") || document.createElement("body");
    }
    static render(delegate, callback, html) {
        return new this(delegate, html).render(callback);
    }
    render(callback) {
        this.renderView(() => {
            this.replaceHeadAndBody();
            this.activateBodyScriptElements();
            callback();
        });
    }
    replaceHeadAndBody() {
        const { documentElement, head, body } = document;
        documentElement.replaceChild(this.newHead, head);
        documentElement.replaceChild(this.newBody, body);
    }
    activateBodyScriptElements() {
        for (const replaceableElement of this.getScriptElements()) {
            const parentNode = replaceableElement.parentNode;
            if (parentNode) {
                const element = this.createScriptElement(replaceableElement);
                parentNode.replaceChild(element, replaceableElement);
            }
        }
    }
    getScriptElements() {
        return [...document.documentElement.querySelectorAll("script")];
    }
}

class SnapshotCache {
    constructor(size) {
        this.keys = [];
        this.snapshots = {};
        this.size = size;
    }
    has(location) {
        return location.toCacheKey() in this.snapshots;
    }
    get(location) {
        if (this.has(location)) {
            const snapshot = this.read(location);
            this.touch(location);
            return snapshot;
        }
    }
    put(location, snapshot) {
        this.write(location, snapshot);
        this.touch(location);
        return snapshot;
    }
    clear() {
        this.snapshots = {};
    }
    read(location) {
        return this.snapshots[location.toCacheKey()];
    }
    write(location, snapshot) {
        this.snapshots[location.toCacheKey()] = snapshot;
    }
    touch(location) {
        const key = location.toCacheKey();
        const index = this.keys.indexOf(key);
        if (index > -1)
            this.keys.splice(index, 1);
        this.keys.unshift(key);
        this.trim();
    }
    trim() {
        for (const key of this.keys.splice(this.size)) {
            delete this.snapshots[key];
        }
    }
}

class SnapshotRenderer extends Renderer {
    constructor(delegate, currentSnapshot, newSnapshot, isPreview) {
        super();
        this.delegate = delegate;
        this.currentSnapshot = currentSnapshot;
        this.currentHeadDetails = currentSnapshot.headDetails;
        this.newSnapshot = newSnapshot;
        this.newHeadDetails = newSnapshot.headDetails;
        this.newBody = newSnapshot.bodyElement;
        this.isPreview = isPreview;
    }
    static render(delegate, callback, currentSnapshot, newSnapshot, isPreview) {
        return new this(delegate, currentSnapshot, newSnapshot, isPreview).render(callback);
    }
    render(callback) {
        if (this.shouldRender()) {
            this.mergeHead();
            this.renderView(() => {
                this.replaceBody();
                if (!this.isPreview) {
                    this.focusFirstAutofocusableElement();
                }
                callback();
            });
        }
        else {
            this.invalidateView();
        }
    }
    mergeHead() {
        this.copyNewHeadStylesheetElements();
        this.copyNewHeadScriptElements();
        this.removeCurrentHeadProvisionalElements();
        this.copyNewHeadProvisionalElements();
    }
    replaceBody() {
        const placeholders = this.relocateCurrentBodyPermanentElements();
        this.activateNewBody();
        this.assignNewBody();
        this.replacePlaceholderElementsWithClonedPermanentElements(placeholders);
    }
    shouldRender() {
        return this.newSnapshot.isVisitable() && this.trackedElementsAreIdentical();
    }
    trackedElementsAreIdentical() {
        return this.currentHeadDetails.getTrackedElementSignature() == this.newHeadDetails.getTrackedElementSignature();
    }
    copyNewHeadStylesheetElements() {
        for (const element of this.getNewHeadStylesheetElements()) {
            document.head.appendChild(element);
        }
    }
    copyNewHeadScriptElements() {
        for (const element of this.getNewHeadScriptElements()) {
            document.head.appendChild(this.createScriptElement(element));
        }
    }
    removeCurrentHeadProvisionalElements() {
        for (const element of this.getCurrentHeadProvisionalElements()) {
            document.head.removeChild(element);
        }
    }
    copyNewHeadProvisionalElements() {
        for (const element of this.getNewHeadProvisionalElements()) {
            document.head.appendChild(element);
        }
    }
    relocateCurrentBodyPermanentElements() {
        return this.getCurrentBodyPermanentElements().reduce((placeholders, permanentElement) => {
            const newElement = this.newSnapshot.getPermanentElementById(permanentElement.id);
            if (newElement) {
                const placeholder = createPlaceholderForPermanentElement(permanentElement);
                replaceElementWithElement(permanentElement, placeholder.element);
                replaceElementWithElement(newElement, permanentElement);
                return [...placeholders, placeholder];
            }
            else {
                return placeholders;
            }
        }, []);
    }
    replacePlaceholderElementsWithClonedPermanentElements(placeholders) {
        for (const { element, permanentElement } of placeholders) {
            const clonedElement = permanentElement.cloneNode(true);
            replaceElementWithElement(element, clonedElement);
        }
    }
    activateNewBody() {
        document.adoptNode(this.newBody);
        this.activateNewBodyScriptElements();
    }
    activateNewBodyScriptElements() {
        for (const inertScriptElement of this.getNewBodyScriptElements()) {
            const activatedScriptElement = this.createScriptElement(inertScriptElement);
            replaceElementWithElement(inertScriptElement, activatedScriptElement);
        }
    }
    assignNewBody() {
        if (document.body) {
            replaceElementWithElement(document.body, this.newBody);
        }
        else {
            document.documentElement.appendChild(this.newBody);
        }
    }
    focusFirstAutofocusableElement() {
        const element = this.newSnapshot.findFirstAutofocusableElement();
        if (elementIsFocusable(element)) {
            element.focus();
        }
    }
    getNewHeadStylesheetElements() {
        return this.newHeadDetails.getStylesheetElementsNotInDetails(this.currentHeadDetails);
    }
    getNewHeadScriptElements() {
        return this.newHeadDetails.getScriptElementsNotInDetails(this.currentHeadDetails);
    }
    getCurrentHeadProvisionalElements() {
        return this.currentHeadDetails.getProvisionalElements();
    }
    getNewHeadProvisionalElements() {
        return this.newHeadDetails.getProvisionalElements();
    }
    getCurrentBodyPermanentElements() {
        return this.currentSnapshot.getPermanentElementsPresentInSnapshot(this.newSnapshot);
    }
    getNewBodyScriptElements() {
        return [...this.newBody.querySelectorAll("script")];
    }
}
function createPlaceholderForPermanentElement(permanentElement) {
    const element = document.createElement("meta");
    element.setAttribute("name", "turbo-permanent-placeholder");
    element.setAttribute("content", permanentElement.id);
    return { element, permanentElement };
}
function replaceElementWithElement(fromElement, toElement) {
    const parentElement = fromElement.parentElement;
    if (parentElement) {
        return parentElement.replaceChild(toElement, fromElement);
    }
}
function elementIsFocusable(element) {
    return element && typeof element.focus == "function";
}

class View {
    constructor(delegate) {
        this.htmlElement = document.documentElement;
        this.snapshotCache = new SnapshotCache(10);
        this.delegate = delegate;
    }
    getRootLocation() {
        return this.getSnapshot().getRootLocation();
    }
    getElementForAnchor(anchor) {
        return this.getSnapshot().getElementForAnchor(anchor);
    }
    getSnapshot() {
        return Snapshot.fromHTMLElement(this.htmlElement);
    }
    clearSnapshotCache() {
        this.snapshotCache.clear();
    }
    shouldCacheSnapshot() {
        return this.getSnapshot().isCacheable();
    }
    async cacheSnapshot() {
        if (this.shouldCacheSnapshot()) {
            this.delegate.viewWillCacheSnapshot();
            const snapshot = this.getSnapshot();
            const location = this.lastRenderedLocation || Location.currentLocation;
            await nextMicrotask();
            this.snapshotCache.put(location, snapshot.clone());
        }
    }
    getCachedSnapshotForLocation(location) {
        return this.snapshotCache.get(location);
    }
    render({ snapshot, error, isPreview }, callback) {
        this.markAsPreview(isPreview);
        if (snapshot) {
            this.renderSnapshot(snapshot, isPreview, callback);
        }
        else {
            this.renderError(error, callback);
        }
    }
    scrollToAnchor(anchor) {
        const element = this.getElementForAnchor(anchor);
        if (element) {
            this.scrollToElement(element);
        }
        else {
            this.scrollToPosition({ x: 0, y: 0 });
        }
    }
    scrollToElement(element) {
        element.scrollIntoView();
    }
    scrollToPosition({ x, y }) {
        window.scrollTo(x, y);
    }
    markAsPreview(isPreview) {
        if (isPreview) {
            this.htmlElement.setAttribute("data-turbo-preview", "");
        }
        else {
            this.htmlElement.removeAttribute("data-turbo-preview");
        }
    }
    renderSnapshot(snapshot, isPreview, callback) {
        SnapshotRenderer.render(this.delegate, callback, this.getSnapshot(), snapshot, isPreview || false);
    }
    renderError(error, callback) {
        ErrorRenderer.render(this.delegate, callback, error || "");
    }
}

class Session {
    constructor() {
        this.navigator = new Navigator(this);
        this.history = new History(this);
        this.view = new View(this);
        this.adapter = new BrowserAdapter(this);
        this.pageObserver = new PageObserver(this);
        this.linkClickObserver = new LinkClickObserver(this);
        this.formSubmitObserver = new FormSubmitObserver(this);
        this.scrollObserver = new ScrollObserver(this);
        this.streamObserver = new StreamObserver(this);
        this.frameRedirector = new FrameRedirector(document.documentElement);
        this.enabled = true;
        this.progressBarDelay = 500;
        this.started = false;
    }
    start() {
        if (!this.started) {
            this.pageObserver.start();
            this.linkClickObserver.start();
            this.formSubmitObserver.start();
            this.scrollObserver.start();
            this.streamObserver.start();
            this.frameRedirector.start();
            this.history.start();
            this.started = true;
            this.enabled = true;
        }
    }
    disable() {
        this.enabled = false;
    }
    stop() {
        if (this.started) {
            this.pageObserver.stop();
            this.linkClickObserver.stop();
            this.formSubmitObserver.stop();
            this.scrollObserver.stop();
            this.streamObserver.stop();
            this.frameRedirector.stop();
            this.history.stop();
            this.started = false;
        }
    }
    registerAdapter(adapter) {
        this.adapter = adapter;
    }
    visit(location, options = {}) {
        this.navigator.proposeVisit(Location.wrap(location), options);
    }
    connectStreamSource(source) {
        this.streamObserver.connectStreamSource(source);
    }
    disconnectStreamSource(source) {
        this.streamObserver.disconnectStreamSource(source);
    }
    renderStreamMessage(message) {
        document.documentElement.appendChild(StreamMessage.wrap(message).fragment);
    }
    clearCache() {
        this.view.clearSnapshotCache();
    }
    setProgressBarDelay(delay) {
        this.progressBarDelay = delay;
    }
    get location() {
        return this.history.location;
    }
    get restorationIdentifier() {
        return this.history.restorationIdentifier;
    }
    historyPoppedToLocationWithRestorationIdentifier(location) {
        if (this.enabled) {
            this.navigator.proposeVisit(location, { action: "restore", historyChanged: true });
        }
        else {
            this.adapter.pageInvalidated();
        }
    }
    scrollPositionChanged(position) {
        this.history.updateRestorationData({ scrollPosition: position });
    }
    willFollowLinkToLocation(link, location) {
        return this.linkIsVisitable(link)
            && this.locationIsVisitable(location)
            && this.applicationAllowsFollowingLinkToLocation(link, location);
    }
    followedLinkToLocation(link, location) {
        const action = this.getActionForLink(link);
        this.visit(location, { action });
    }
    allowsVisitingLocation(location) {
        return this.applicationAllowsVisitingLocation(location);
    }
    visitProposedToLocation(location, options) {
        this.adapter.visitProposedToLocation(location, options);
    }
    visitStarted(visit) {
        this.notifyApplicationAfterVisitingLocation(visit.location);
    }
    visitCompleted(visit) {
        this.notifyApplicationAfterPageLoad(visit.getTimingMetrics());
    }
    willSubmitForm(form, submitter) {
        return true;
    }
    formSubmitted(form, submitter) {
        this.navigator.submitForm(form, submitter);
    }
    pageBecameInteractive() {
        this.view.lastRenderedLocation = this.location;
        this.notifyApplicationAfterPageLoad();
    }
    pageLoaded() {
    }
    pageInvalidated() {
        this.adapter.pageInvalidated();
    }
    receivedMessageFromStream(message) {
        this.renderStreamMessage(message);
    }
    viewWillRender(newBody) {
        this.notifyApplicationBeforeRender(newBody);
    }
    viewRendered() {
        this.view.lastRenderedLocation = this.history.location;
        this.notifyApplicationAfterRender();
    }
    viewInvalidated() {
        this.pageObserver.invalidate();
    }
    viewWillCacheSnapshot() {
        this.notifyApplicationBeforeCachingSnapshot();
    }
    applicationAllowsFollowingLinkToLocation(link, location) {
        const event = this.notifyApplicationAfterClickingLinkToLocation(link, location);
        return !event.defaultPrevented;
    }
    applicationAllowsVisitingLocation(location) {
        const event = this.notifyApplicationBeforeVisitingLocation(location);
        return !event.defaultPrevented;
    }
    notifyApplicationAfterClickingLinkToLocation(link, location) {
        return dispatch("turbo:click", { target: link, detail: { url: location.absoluteURL }, cancelable: true });
    }
    notifyApplicationBeforeVisitingLocation(location) {
        return dispatch("turbo:before-visit", { detail: { url: location.absoluteURL }, cancelable: true });
    }
    notifyApplicationAfterVisitingLocation(location) {
        return dispatch("turbo:visit", { detail: { url: location.absoluteURL } });
    }
    notifyApplicationBeforeCachingSnapshot() {
        return dispatch("turbo:before-cache");
    }
    notifyApplicationBeforeRender(newBody) {
        return dispatch("turbo:before-render", { detail: { newBody } });
    }
    notifyApplicationAfterRender() {
        return dispatch("turbo:render");
    }
    notifyApplicationAfterPageLoad(timing = {}) {
        return dispatch("turbo:load", { detail: { url: this.location.absoluteURL, timing } });
    }
    getActionForLink(link) {
        const action = link.getAttribute("data-turbo-action");
        return isAction(action) ? action : "advance";
    }
    linkIsVisitable(link) {
        const container = link.closest("[data-turbo]");
        if (container) {
            return container.getAttribute("data-turbo") != "false";
        }
        else {
            return true;
        }
    }
    locationIsVisitable(location) {
        return location.isPrefixedBy(this.view.getRootLocation()) && location.isHTML();
    }
}

const session = new Session;
const { navigator } = session;
function start() {
    session.start();
}
function registerAdapter(adapter) {
    session.registerAdapter(adapter);
}
function visit(location, options) {
    session.visit(location, options);
}
function connectStreamSource(source) {
    session.connectStreamSource(source);
}
function disconnectStreamSource(source) {
    session.disconnectStreamSource(source);
}
function renderStreamMessage(message) {
    session.renderStreamMessage(message);
}
function clearCache() {
    session.clearCache();
}
function setProgressBarDelay(delay) {
    session.setProgressBarDelay(delay);
}

start();


//# sourceMappingURL=turbo.es2017-esm.js.map


/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/action.js":
/*!***********************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/action.js ***!
  \***********************************************************************************/
/*! exports provided: Action, getDefaultEventNameForElement */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Action", function() { return Action; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getDefaultEventNameForElement", function() { return getDefaultEventNameForElement; });
/* harmony import */ var _action_descriptor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./action_descriptor */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/action_descriptor.js");

var Action = /** @class */ (function () {
    function Action(element, index, descriptor) {
        this.element = element;
        this.index = index;
        this.eventTarget = descriptor.eventTarget || element;
        this.eventName = descriptor.eventName || getDefaultEventNameForElement(element) || error("missing event name");
        this.eventOptions = descriptor.eventOptions || {};
        this.identifier = descriptor.identifier || error("missing identifier");
        this.methodName = descriptor.methodName || error("missing method name");
    }
    Action.forToken = function (token) {
        return new this(token.element, token.index, Object(_action_descriptor__WEBPACK_IMPORTED_MODULE_0__["parseActionDescriptorString"])(token.content));
    };
    Action.prototype.toString = function () {
        var eventNameSuffix = this.eventTargetName ? "@" + this.eventTargetName : "";
        return "" + this.eventName + eventNameSuffix + "->" + this.identifier + "#" + this.methodName;
    };
    Object.defineProperty(Action.prototype, "eventTargetName", {
        get: function () {
            return Object(_action_descriptor__WEBPACK_IMPORTED_MODULE_0__["stringifyEventTarget"])(this.eventTarget);
        },
        enumerable: false,
        configurable: true
    });
    return Action;
}());

var defaultEventNames = {
    "a": function (e) { return "click"; },
    "button": function (e) { return "click"; },
    "form": function (e) { return "submit"; },
    "input": function (e) { return e.getAttribute("type") == "submit" ? "click" : "input"; },
    "select": function (e) { return "change"; },
    "textarea": function (e) { return "input"; }
};
function getDefaultEventNameForElement(element) {
    var tagName = element.tagName.toLowerCase();
    if (tagName in defaultEventNames) {
        return defaultEventNames[tagName](element);
    }
}
function error(message) {
    throw new Error(message);
}
//# sourceMappingURL=action.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/action_descriptor.js":
/*!**********************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/action_descriptor.js ***!
  \**********************************************************************************************/
/*! exports provided: parseActionDescriptorString, stringifyEventTarget */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "parseActionDescriptorString", function() { return parseActionDescriptorString; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "stringifyEventTarget", function() { return stringifyEventTarget; });
// capture nos.:            12   23 4               43   1 5   56 7      768 9  98
var descriptorPattern = /^((.+?)(@(window|document))?->)?(.+?)(#([^:]+?))(:(.+))?$/;
function parseActionDescriptorString(descriptorString) {
    var source = descriptorString.trim();
    var matches = source.match(descriptorPattern) || [];
    return {
        eventTarget: parseEventTarget(matches[4]),
        eventName: matches[2],
        eventOptions: matches[9] ? parseEventOptions(matches[9]) : {},
        identifier: matches[5],
        methodName: matches[7]
    };
}
function parseEventTarget(eventTargetName) {
    if (eventTargetName == "window") {
        return window;
    }
    else if (eventTargetName == "document") {
        return document;
    }
}
function parseEventOptions(eventOptions) {
    return eventOptions.split(":").reduce(function (options, token) {
        var _a;
        return Object.assign(options, (_a = {}, _a[token.replace(/^!/, "")] = !/^!/.test(token), _a));
    }, {});
}
function stringifyEventTarget(eventTarget) {
    if (eventTarget == window) {
        return "window";
    }
    else if (eventTarget == document) {
        return "document";
    }
}
//# sourceMappingURL=action_descriptor.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/application.js":
/*!****************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/application.js ***!
  \****************************************************************************************/
/*! exports provided: Application */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Application", function() { return Application; });
/* harmony import */ var _dispatcher__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./dispatcher */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/dispatcher.js");
/* harmony import */ var _router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./router */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/router.js");
/* harmony import */ var _schema__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./schema */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/schema.js");
var __awaiter = (undefined && undefined.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (undefined && undefined.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var __spreadArrays = (undefined && undefined.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};



var Application = /** @class */ (function () {
    function Application(element, schema) {
        if (element === void 0) { element = document.documentElement; }
        if (schema === void 0) { schema = _schema__WEBPACK_IMPORTED_MODULE_2__["defaultSchema"]; }
        this.logger = console;
        this.element = element;
        this.schema = schema;
        this.dispatcher = new _dispatcher__WEBPACK_IMPORTED_MODULE_0__["Dispatcher"](this);
        this.router = new _router__WEBPACK_IMPORTED_MODULE_1__["Router"](this);
    }
    Application.start = function (element, schema) {
        var application = new Application(element, schema);
        application.start();
        return application;
    };
    Application.prototype.start = function () {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, domReady()];
                    case 1:
                        _a.sent();
                        this.dispatcher.start();
                        this.router.start();
                        return [2 /*return*/];
                }
            });
        });
    };
    Application.prototype.stop = function () {
        this.dispatcher.stop();
        this.router.stop();
    };
    Application.prototype.register = function (identifier, controllerConstructor) {
        this.load({ identifier: identifier, controllerConstructor: controllerConstructor });
    };
    Application.prototype.load = function (head) {
        var _this = this;
        var rest = [];
        for (var _i = 1; _i < arguments.length; _i++) {
            rest[_i - 1] = arguments[_i];
        }
        var definitions = Array.isArray(head) ? head : __spreadArrays([head], rest);
        definitions.forEach(function (definition) { return _this.router.loadDefinition(definition); });
    };
    Application.prototype.unload = function (head) {
        var _this = this;
        var rest = [];
        for (var _i = 1; _i < arguments.length; _i++) {
            rest[_i - 1] = arguments[_i];
        }
        var identifiers = Array.isArray(head) ? head : __spreadArrays([head], rest);
        identifiers.forEach(function (identifier) { return _this.router.unloadIdentifier(identifier); });
    };
    Object.defineProperty(Application.prototype, "controllers", {
        // Controllers
        get: function () {
            return this.router.contexts.map(function (context) { return context.controller; });
        },
        enumerable: false,
        configurable: true
    });
    Application.prototype.getControllerForElementAndIdentifier = function (element, identifier) {
        var context = this.router.getContextForElementAndIdentifier(element, identifier);
        return context ? context.controller : null;
    };
    // Error handling
    Application.prototype.handleError = function (error, message, detail) {
        this.logger.error("%s\n\n%o\n\n%o", message, error, detail);
    };
    return Application;
}());

function domReady() {
    return new Promise(function (resolve) {
        if (document.readyState == "loading") {
            document.addEventListener("DOMContentLoaded", resolve);
        }
        else {
            resolve();
        }
    });
}
//# sourceMappingURL=application.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/binding.js":
/*!************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/binding.js ***!
  \************************************************************************************/
/*! exports provided: Binding */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Binding", function() { return Binding; });
var Binding = /** @class */ (function () {
    function Binding(context, action) {
        this.context = context;
        this.action = action;
    }
    Object.defineProperty(Binding.prototype, "index", {
        get: function () {
            return this.action.index;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "eventTarget", {
        get: function () {
            return this.action.eventTarget;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "eventOptions", {
        get: function () {
            return this.action.eventOptions;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "identifier", {
        get: function () {
            return this.context.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Binding.prototype.handleEvent = function (event) {
        if (this.willBeInvokedByEvent(event)) {
            this.invokeWithEvent(event);
        }
    };
    Object.defineProperty(Binding.prototype, "eventName", {
        get: function () {
            return this.action.eventName;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "method", {
        get: function () {
            var method = this.controller[this.methodName];
            if (typeof method == "function") {
                return method;
            }
            throw new Error("Action \"" + this.action + "\" references undefined method \"" + this.methodName + "\"");
        },
        enumerable: false,
        configurable: true
    });
    Binding.prototype.invokeWithEvent = function (event) {
        try {
            this.method.call(this.controller, event);
        }
        catch (error) {
            var _a = this, identifier = _a.identifier, controller = _a.controller, element = _a.element, index = _a.index;
            var detail = { identifier: identifier, controller: controller, element: element, index: index, event: event };
            this.context.handleError(error, "invoking action \"" + this.action + "\"", detail);
        }
    };
    Binding.prototype.willBeInvokedByEvent = function (event) {
        var eventTarget = event.target;
        if (this.element === eventTarget) {
            return true;
        }
        else if (eventTarget instanceof Element && this.element.contains(eventTarget)) {
            return this.scope.containsElement(eventTarget);
        }
        else {
            return this.scope.containsElement(this.action.element);
        }
    };
    Object.defineProperty(Binding.prototype, "controller", {
        get: function () {
            return this.context.controller;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "methodName", {
        get: function () {
            return this.action.methodName;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Binding.prototype, "scope", {
        get: function () {
            return this.context.scope;
        },
        enumerable: false,
        configurable: true
    });
    return Binding;
}());

//# sourceMappingURL=binding.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/binding_observer.js":
/*!*********************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/binding_observer.js ***!
  \*********************************************************************************************/
/*! exports provided: BindingObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "BindingObserver", function() { return BindingObserver; });
/* harmony import */ var _action__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./action */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/action.js");
/* harmony import */ var _binding__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./binding */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/binding.js");
/* harmony import */ var _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @stimulus/mutation-observers */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/index.js");



var BindingObserver = /** @class */ (function () {
    function BindingObserver(context, delegate) {
        this.context = context;
        this.delegate = delegate;
        this.bindingsByAction = new Map;
    }
    BindingObserver.prototype.start = function () {
        if (!this.valueListObserver) {
            this.valueListObserver = new _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_2__["ValueListObserver"](this.element, this.actionAttribute, this);
            this.valueListObserver.start();
        }
    };
    BindingObserver.prototype.stop = function () {
        if (this.valueListObserver) {
            this.valueListObserver.stop();
            delete this.valueListObserver;
            this.disconnectAllActions();
        }
    };
    Object.defineProperty(BindingObserver.prototype, "element", {
        get: function () {
            return this.context.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(BindingObserver.prototype, "identifier", {
        get: function () {
            return this.context.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(BindingObserver.prototype, "actionAttribute", {
        get: function () {
            return this.schema.actionAttribute;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(BindingObserver.prototype, "schema", {
        get: function () {
            return this.context.schema;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(BindingObserver.prototype, "bindings", {
        get: function () {
            return Array.from(this.bindingsByAction.values());
        },
        enumerable: false,
        configurable: true
    });
    BindingObserver.prototype.connectAction = function (action) {
        var binding = new _binding__WEBPACK_IMPORTED_MODULE_1__["Binding"](this.context, action);
        this.bindingsByAction.set(action, binding);
        this.delegate.bindingConnected(binding);
    };
    BindingObserver.prototype.disconnectAction = function (action) {
        var binding = this.bindingsByAction.get(action);
        if (binding) {
            this.bindingsByAction.delete(action);
            this.delegate.bindingDisconnected(binding);
        }
    };
    BindingObserver.prototype.disconnectAllActions = function () {
        var _this = this;
        this.bindings.forEach(function (binding) { return _this.delegate.bindingDisconnected(binding); });
        this.bindingsByAction.clear();
    };
    // Value observer delegate
    BindingObserver.prototype.parseValueForToken = function (token) {
        var action = _action__WEBPACK_IMPORTED_MODULE_0__["Action"].forToken(token);
        if (action.identifier == this.identifier) {
            return action;
        }
    };
    BindingObserver.prototype.elementMatchedValue = function (element, action) {
        this.connectAction(action);
    };
    BindingObserver.prototype.elementUnmatchedValue = function (element, action) {
        this.disconnectAction(action);
    };
    return BindingObserver;
}());

//# sourceMappingURL=binding_observer.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/blessing.js":
/*!*************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/blessing.js ***!
  \*************************************************************************************/
/*! exports provided: bless */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "bless", function() { return bless; });
/* harmony import */ var _inheritable_statics__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./inheritable_statics */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/inheritable_statics.js");
var __extends = (undefined && undefined.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __spreadArrays = (undefined && undefined.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};

/** @hidden */
function bless(constructor) {
    return shadow(constructor, getBlessedProperties(constructor));
}
function shadow(constructor, properties) {
    var shadowConstructor = extend(constructor);
    var shadowProperties = getShadowProperties(constructor.prototype, properties);
    Object.defineProperties(shadowConstructor.prototype, shadowProperties);
    return shadowConstructor;
}
function getBlessedProperties(constructor) {
    var blessings = Object(_inheritable_statics__WEBPACK_IMPORTED_MODULE_0__["readInheritableStaticArrayValues"])(constructor, "blessings");
    return blessings.reduce(function (blessedProperties, blessing) {
        var properties = blessing(constructor);
        for (var key in properties) {
            var descriptor = blessedProperties[key] || {};
            blessedProperties[key] = Object.assign(descriptor, properties[key]);
        }
        return blessedProperties;
    }, {});
}
function getShadowProperties(prototype, properties) {
    return getOwnKeys(properties).reduce(function (shadowProperties, key) {
        var _a;
        var descriptor = getShadowedDescriptor(prototype, properties, key);
        if (descriptor) {
            Object.assign(shadowProperties, (_a = {}, _a[key] = descriptor, _a));
        }
        return shadowProperties;
    }, {});
}
function getShadowedDescriptor(prototype, properties, key) {
    var shadowingDescriptor = Object.getOwnPropertyDescriptor(prototype, key);
    var shadowedByValue = shadowingDescriptor && "value" in shadowingDescriptor;
    if (!shadowedByValue) {
        var descriptor = Object.getOwnPropertyDescriptor(properties, key).value;
        if (shadowingDescriptor) {
            descriptor.get = shadowingDescriptor.get || descriptor.get;
            descriptor.set = shadowingDescriptor.set || descriptor.set;
        }
        return descriptor;
    }
}
var getOwnKeys = (function () {
    if (typeof Object.getOwnPropertySymbols == "function") {
        return function (object) { return __spreadArrays(Object.getOwnPropertyNames(object), Object.getOwnPropertySymbols(object)); };
    }
    else {
        return Object.getOwnPropertyNames;
    }
})();
var extend = (function () {
    function extendWithReflect(constructor) {
        function extended() {
            var _newTarget = this && this instanceof extended ? this.constructor : void 0;
            return Reflect.construct(constructor, arguments, _newTarget);
        }
        extended.prototype = Object.create(constructor.prototype, {
            constructor: { value: extended }
        });
        Reflect.setPrototypeOf(extended, constructor);
        return extended;
    }
    function testReflectExtension() {
        var a = function () { this.a.call(this); };
        var b = extendWithReflect(a);
        b.prototype.a = function () { };
        return new b;
    }
    try {
        testReflectExtension();
        return extendWithReflect;
    }
    catch (error) {
        return function (constructor) { return /** @class */ (function (_super) {
            __extends(extended, _super);
            function extended() {
                return _super !== null && _super.apply(this, arguments) || this;
            }
            return extended;
        }(constructor)); };
    }
})();
//# sourceMappingURL=blessing.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/class_map.js":
/*!**************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/class_map.js ***!
  \**************************************************************************************/
/*! exports provided: ClassMap */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ClassMap", function() { return ClassMap; });
var ClassMap = /** @class */ (function () {
    function ClassMap(scope) {
        this.scope = scope;
    }
    ClassMap.prototype.has = function (name) {
        return this.data.has(this.getDataKey(name));
    };
    ClassMap.prototype.get = function (name) {
        return this.data.get(this.getDataKey(name));
    };
    ClassMap.prototype.getAttributeName = function (name) {
        return this.data.getAttributeNameForKey(this.getDataKey(name));
    };
    ClassMap.prototype.getDataKey = function (name) {
        return name + "-class";
    };
    Object.defineProperty(ClassMap.prototype, "data", {
        get: function () {
            return this.scope.data;
        },
        enumerable: false,
        configurable: true
    });
    return ClassMap;
}());

//# sourceMappingURL=class_map.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/class_properties.js":
/*!*********************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/class_properties.js ***!
  \*********************************************************************************************/
/*! exports provided: ClassPropertiesBlessing */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ClassPropertiesBlessing", function() { return ClassPropertiesBlessing; });
/* harmony import */ var _inheritable_statics__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./inheritable_statics */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/inheritable_statics.js");
/* harmony import */ var _string_helpers__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./string_helpers */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/string_helpers.js");


/** @hidden */
function ClassPropertiesBlessing(constructor) {
    var classes = Object(_inheritable_statics__WEBPACK_IMPORTED_MODULE_0__["readInheritableStaticArrayValues"])(constructor, "classes");
    return classes.reduce(function (properties, classDefinition) {
        return Object.assign(properties, propertiesForClassDefinition(classDefinition));
    }, {});
}
function propertiesForClassDefinition(key) {
    var _a;
    var name = key + "Class";
    return _a = {},
        _a[name] = {
            get: function () {
                var classes = this.classes;
                if (classes.has(key)) {
                    return classes.get(key);
                }
                else {
                    var attribute = classes.getAttributeName(key);
                    throw new Error("Missing attribute \"" + attribute + "\"");
                }
            }
        },
        _a["has" + Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["capitalize"])(name)] = {
            get: function () {
                return this.classes.has(key);
            }
        },
        _a;
}
//# sourceMappingURL=class_properties.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/context.js":
/*!************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/context.js ***!
  \************************************************************************************/
/*! exports provided: Context */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Context", function() { return Context; });
/* harmony import */ var _binding_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./binding_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/binding_observer.js");
/* harmony import */ var _value_observer__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./value_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/value_observer.js");


var Context = /** @class */ (function () {
    function Context(module, scope) {
        this.module = module;
        this.scope = scope;
        this.controller = new module.controllerConstructor(this);
        this.bindingObserver = new _binding_observer__WEBPACK_IMPORTED_MODULE_0__["BindingObserver"](this, this.dispatcher);
        this.valueObserver = new _value_observer__WEBPACK_IMPORTED_MODULE_1__["ValueObserver"](this, this.controller);
        try {
            this.controller.initialize();
        }
        catch (error) {
            this.handleError(error, "initializing controller");
        }
    }
    Context.prototype.connect = function () {
        this.bindingObserver.start();
        this.valueObserver.start();
        try {
            this.controller.connect();
        }
        catch (error) {
            this.handleError(error, "connecting controller");
        }
    };
    Context.prototype.disconnect = function () {
        try {
            this.controller.disconnect();
        }
        catch (error) {
            this.handleError(error, "disconnecting controller");
        }
        this.valueObserver.stop();
        this.bindingObserver.stop();
    };
    Object.defineProperty(Context.prototype, "application", {
        get: function () {
            return this.module.application;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "identifier", {
        get: function () {
            return this.module.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "schema", {
        get: function () {
            return this.application.schema;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "dispatcher", {
        get: function () {
            return this.application.dispatcher;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Context.prototype, "parentElement", {
        get: function () {
            return this.element.parentElement;
        },
        enumerable: false,
        configurable: true
    });
    // Error handling
    Context.prototype.handleError = function (error, message, detail) {
        if (detail === void 0) { detail = {}; }
        var _a = this, identifier = _a.identifier, controller = _a.controller, element = _a.element;
        detail = Object.assign({ identifier: identifier, controller: controller, element: element }, detail);
        this.application.handleError(error, "Error " + message, detail);
    };
    return Context;
}());

//# sourceMappingURL=context.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/controller.js":
/*!***************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/controller.js ***!
  \***************************************************************************************/
/*! exports provided: Controller */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Controller", function() { return Controller; });
/* harmony import */ var _class_properties__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./class_properties */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/class_properties.js");
/* harmony import */ var _target_properties__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./target_properties */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/target_properties.js");
/* harmony import */ var _value_properties__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./value_properties */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/value_properties.js");



var Controller = /** @class */ (function () {
    function Controller(context) {
        this.context = context;
    }
    Object.defineProperty(Controller.prototype, "application", {
        get: function () {
            return this.context.application;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "scope", {
        get: function () {
            return this.context.scope;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "identifier", {
        get: function () {
            return this.scope.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "targets", {
        get: function () {
            return this.scope.targets;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "classes", {
        get: function () {
            return this.scope.classes;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Controller.prototype, "data", {
        get: function () {
            return this.scope.data;
        },
        enumerable: false,
        configurable: true
    });
    Controller.prototype.initialize = function () {
        // Override in your subclass to set up initial controller state
    };
    Controller.prototype.connect = function () {
        // Override in your subclass to respond when the controller is connected to the DOM
    };
    Controller.prototype.disconnect = function () {
        // Override in your subclass to respond when the controller is disconnected from the DOM
    };
    Controller.blessings = [_class_properties__WEBPACK_IMPORTED_MODULE_0__["ClassPropertiesBlessing"], _target_properties__WEBPACK_IMPORTED_MODULE_1__["TargetPropertiesBlessing"], _value_properties__WEBPACK_IMPORTED_MODULE_2__["ValuePropertiesBlessing"]];
    Controller.targets = [];
    Controller.values = {};
    return Controller;
}());

//# sourceMappingURL=controller.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/data_map.js":
/*!*************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/data_map.js ***!
  \*************************************************************************************/
/*! exports provided: DataMap */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DataMap", function() { return DataMap; });
/* harmony import */ var _string_helpers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string_helpers */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/string_helpers.js");

var DataMap = /** @class */ (function () {
    function DataMap(scope) {
        this.scope = scope;
    }
    Object.defineProperty(DataMap.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(DataMap.prototype, "identifier", {
        get: function () {
            return this.scope.identifier;
        },
        enumerable: false,
        configurable: true
    });
    DataMap.prototype.get = function (key) {
        var name = this.getAttributeNameForKey(key);
        return this.element.getAttribute(name);
    };
    DataMap.prototype.set = function (key, value) {
        var name = this.getAttributeNameForKey(key);
        this.element.setAttribute(name, value);
        return this.get(key);
    };
    DataMap.prototype.has = function (key) {
        var name = this.getAttributeNameForKey(key);
        return this.element.hasAttribute(name);
    };
    DataMap.prototype.delete = function (key) {
        if (this.has(key)) {
            var name_1 = this.getAttributeNameForKey(key);
            this.element.removeAttribute(name_1);
            return true;
        }
        else {
            return false;
        }
    };
    DataMap.prototype.getAttributeNameForKey = function (key) {
        return "data-" + this.identifier + "-" + Object(_string_helpers__WEBPACK_IMPORTED_MODULE_0__["dasherize"])(key);
    };
    return DataMap;
}());

//# sourceMappingURL=data_map.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/definition.js":
/*!***************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/definition.js ***!
  \***************************************************************************************/
/*! exports provided: blessDefinition */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "blessDefinition", function() { return blessDefinition; });
/* harmony import */ var _blessing__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./blessing */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/blessing.js");

/** @hidden */
function blessDefinition(definition) {
    return {
        identifier: definition.identifier,
        controllerConstructor: Object(_blessing__WEBPACK_IMPORTED_MODULE_0__["bless"])(definition.controllerConstructor)
    };
}
//# sourceMappingURL=definition.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/dispatcher.js":
/*!***************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/dispatcher.js ***!
  \***************************************************************************************/
/*! exports provided: Dispatcher */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Dispatcher", function() { return Dispatcher; });
/* harmony import */ var _event_listener__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./event_listener */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/event_listener.js");

var Dispatcher = /** @class */ (function () {
    function Dispatcher(application) {
        this.application = application;
        this.eventListenerMaps = new Map;
        this.started = false;
    }
    Dispatcher.prototype.start = function () {
        if (!this.started) {
            this.started = true;
            this.eventListeners.forEach(function (eventListener) { return eventListener.connect(); });
        }
    };
    Dispatcher.prototype.stop = function () {
        if (this.started) {
            this.started = false;
            this.eventListeners.forEach(function (eventListener) { return eventListener.disconnect(); });
        }
    };
    Object.defineProperty(Dispatcher.prototype, "eventListeners", {
        get: function () {
            return Array.from(this.eventListenerMaps.values())
                .reduce(function (listeners, map) { return listeners.concat(Array.from(map.values())); }, []);
        },
        enumerable: false,
        configurable: true
    });
    // Binding observer delegate
    /** @hidden */
    Dispatcher.prototype.bindingConnected = function (binding) {
        this.fetchEventListenerForBinding(binding).bindingConnected(binding);
    };
    /** @hidden */
    Dispatcher.prototype.bindingDisconnected = function (binding) {
        this.fetchEventListenerForBinding(binding).bindingDisconnected(binding);
    };
    // Error handling
    Dispatcher.prototype.handleError = function (error, message, detail) {
        if (detail === void 0) { detail = {}; }
        this.application.handleError(error, "Error " + message, detail);
    };
    Dispatcher.prototype.fetchEventListenerForBinding = function (binding) {
        var eventTarget = binding.eventTarget, eventName = binding.eventName, eventOptions = binding.eventOptions;
        return this.fetchEventListener(eventTarget, eventName, eventOptions);
    };
    Dispatcher.prototype.fetchEventListener = function (eventTarget, eventName, eventOptions) {
        var eventListenerMap = this.fetchEventListenerMapForEventTarget(eventTarget);
        var cacheKey = this.cacheKey(eventName, eventOptions);
        var eventListener = eventListenerMap.get(cacheKey);
        if (!eventListener) {
            eventListener = this.createEventListener(eventTarget, eventName, eventOptions);
            eventListenerMap.set(cacheKey, eventListener);
        }
        return eventListener;
    };
    Dispatcher.prototype.createEventListener = function (eventTarget, eventName, eventOptions) {
        var eventListener = new _event_listener__WEBPACK_IMPORTED_MODULE_0__["EventListener"](eventTarget, eventName, eventOptions);
        if (this.started) {
            eventListener.connect();
        }
        return eventListener;
    };
    Dispatcher.prototype.fetchEventListenerMapForEventTarget = function (eventTarget) {
        var eventListenerMap = this.eventListenerMaps.get(eventTarget);
        if (!eventListenerMap) {
            eventListenerMap = new Map;
            this.eventListenerMaps.set(eventTarget, eventListenerMap);
        }
        return eventListenerMap;
    };
    Dispatcher.prototype.cacheKey = function (eventName, eventOptions) {
        var parts = [eventName];
        Object.keys(eventOptions).sort().forEach(function (key) {
            parts.push("" + (eventOptions[key] ? "" : "!") + key);
        });
        return parts.join(":");
    };
    return Dispatcher;
}());

//# sourceMappingURL=dispatcher.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/event_listener.js":
/*!*******************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/event_listener.js ***!
  \*******************************************************************************************/
/*! exports provided: EventListener */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "EventListener", function() { return EventListener; });
var EventListener = /** @class */ (function () {
    function EventListener(eventTarget, eventName, eventOptions) {
        this.eventTarget = eventTarget;
        this.eventName = eventName;
        this.eventOptions = eventOptions;
        this.unorderedBindings = new Set();
    }
    EventListener.prototype.connect = function () {
        this.eventTarget.addEventListener(this.eventName, this, this.eventOptions);
    };
    EventListener.prototype.disconnect = function () {
        this.eventTarget.removeEventListener(this.eventName, this, this.eventOptions);
    };
    // Binding observer delegate
    /** @hidden */
    EventListener.prototype.bindingConnected = function (binding) {
        this.unorderedBindings.add(binding);
    };
    /** @hidden */
    EventListener.prototype.bindingDisconnected = function (binding) {
        this.unorderedBindings.delete(binding);
    };
    EventListener.prototype.handleEvent = function (event) {
        var extendedEvent = extendEvent(event);
        for (var _i = 0, _a = this.bindings; _i < _a.length; _i++) {
            var binding = _a[_i];
            if (extendedEvent.immediatePropagationStopped) {
                break;
            }
            else {
                binding.handleEvent(extendedEvent);
            }
        }
    };
    Object.defineProperty(EventListener.prototype, "bindings", {
        get: function () {
            return Array.from(this.unorderedBindings).sort(function (left, right) {
                var leftIndex = left.index, rightIndex = right.index;
                return leftIndex < rightIndex ? -1 : leftIndex > rightIndex ? 1 : 0;
            });
        },
        enumerable: false,
        configurable: true
    });
    return EventListener;
}());

function extendEvent(event) {
    if ("immediatePropagationStopped" in event) {
        return event;
    }
    else {
        var stopImmediatePropagation_1 = event.stopImmediatePropagation;
        return Object.assign(event, {
            immediatePropagationStopped: false,
            stopImmediatePropagation: function () {
                this.immediatePropagationStopped = true;
                stopImmediatePropagation_1.call(this);
            }
        });
    }
}
//# sourceMappingURL=event_listener.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/guide.js":
/*!**********************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/guide.js ***!
  \**********************************************************************************/
/*! exports provided: Guide */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Guide", function() { return Guide; });
var Guide = /** @class */ (function () {
    function Guide(logger) {
        this.warnedKeysByObject = new WeakMap;
        this.logger = logger;
    }
    Guide.prototype.warn = function (object, key, message) {
        var warnedKeys = this.warnedKeysByObject.get(object);
        if (!warnedKeys) {
            warnedKeys = new Set;
            this.warnedKeysByObject.set(object, warnedKeys);
        }
        if (!warnedKeys.has(key)) {
            warnedKeys.add(key);
            this.logger.warn(message, object);
        }
    };
    return Guide;
}());

//# sourceMappingURL=guide.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/index.js":
/*!**********************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/index.js ***!
  \**********************************************************************************/
/*! exports provided: Application, Context, Controller, defaultSchema */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _application__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./application */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/application.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Application", function() { return _application__WEBPACK_IMPORTED_MODULE_0__["Application"]; });

/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./context */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/context.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Context", function() { return _context__WEBPACK_IMPORTED_MODULE_1__["Context"]; });

/* harmony import */ var _controller__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./controller */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/controller.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Controller", function() { return _controller__WEBPACK_IMPORTED_MODULE_2__["Controller"]; });

/* harmony import */ var _schema__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./schema */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/schema.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "defaultSchema", function() { return _schema__WEBPACK_IMPORTED_MODULE_3__["defaultSchema"]; });





//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/inheritable_statics.js":
/*!************************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/inheritable_statics.js ***!
  \************************************************************************************************/
/*! exports provided: readInheritableStaticArrayValues, readInheritableStaticObjectPairs */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "readInheritableStaticArrayValues", function() { return readInheritableStaticArrayValues; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "readInheritableStaticObjectPairs", function() { return readInheritableStaticObjectPairs; });
function readInheritableStaticArrayValues(constructor, propertyName) {
    var ancestors = getAncestorsForConstructor(constructor);
    return Array.from(ancestors.reduce(function (values, constructor) {
        getOwnStaticArrayValues(constructor, propertyName).forEach(function (name) { return values.add(name); });
        return values;
    }, new Set));
}
function readInheritableStaticObjectPairs(constructor, propertyName) {
    var ancestors = getAncestorsForConstructor(constructor);
    return ancestors.reduce(function (pairs, constructor) {
        pairs.push.apply(pairs, getOwnStaticObjectPairs(constructor, propertyName));
        return pairs;
    }, []);
}
function getAncestorsForConstructor(constructor) {
    var ancestors = [];
    while (constructor) {
        ancestors.push(constructor);
        constructor = Object.getPrototypeOf(constructor);
    }
    return ancestors.reverse();
}
function getOwnStaticArrayValues(constructor, propertyName) {
    var definition = constructor[propertyName];
    return Array.isArray(definition) ? definition : [];
}
function getOwnStaticObjectPairs(constructor, propertyName) {
    var definition = constructor[propertyName];
    return definition ? Object.keys(definition).map(function (key) { return [key, definition[key]]; }) : [];
}
//# sourceMappingURL=inheritable_statics.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/module.js":
/*!***********************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/module.js ***!
  \***********************************************************************************/
/*! exports provided: Module */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Module", function() { return Module; });
/* harmony import */ var _context__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./context */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/context.js");
/* harmony import */ var _definition__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./definition */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/definition.js");


var Module = /** @class */ (function () {
    function Module(application, definition) {
        this.application = application;
        this.definition = Object(_definition__WEBPACK_IMPORTED_MODULE_1__["blessDefinition"])(definition);
        this.contextsByScope = new WeakMap;
        this.connectedContexts = new Set;
    }
    Object.defineProperty(Module.prototype, "identifier", {
        get: function () {
            return this.definition.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Module.prototype, "controllerConstructor", {
        get: function () {
            return this.definition.controllerConstructor;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Module.prototype, "contexts", {
        get: function () {
            return Array.from(this.connectedContexts);
        },
        enumerable: false,
        configurable: true
    });
    Module.prototype.connectContextForScope = function (scope) {
        var context = this.fetchContextForScope(scope);
        this.connectedContexts.add(context);
        context.connect();
    };
    Module.prototype.disconnectContextForScope = function (scope) {
        var context = this.contextsByScope.get(scope);
        if (context) {
            this.connectedContexts.delete(context);
            context.disconnect();
        }
    };
    Module.prototype.fetchContextForScope = function (scope) {
        var context = this.contextsByScope.get(scope);
        if (!context) {
            context = new _context__WEBPACK_IMPORTED_MODULE_0__["Context"](this, scope);
            this.contextsByScope.set(scope, context);
        }
        return context;
    };
    return Module;
}());

//# sourceMappingURL=module.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/router.js":
/*!***********************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/router.js ***!
  \***********************************************************************************/
/*! exports provided: Router */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Router", function() { return Router; });
/* harmony import */ var _module__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./module */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/module.js");
/* harmony import */ var _stimulus_multimap__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @stimulus/multimap */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/index.js");
/* harmony import */ var _scope__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./scope */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/scope.js");
/* harmony import */ var _scope_observer__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./scope_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/scope_observer.js");




var Router = /** @class */ (function () {
    function Router(application) {
        this.application = application;
        this.scopeObserver = new _scope_observer__WEBPACK_IMPORTED_MODULE_3__["ScopeObserver"](this.element, this.schema, this);
        this.scopesByIdentifier = new _stimulus_multimap__WEBPACK_IMPORTED_MODULE_1__["Multimap"];
        this.modulesByIdentifier = new Map;
    }
    Object.defineProperty(Router.prototype, "element", {
        get: function () {
            return this.application.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "schema", {
        get: function () {
            return this.application.schema;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "logger", {
        get: function () {
            return this.application.logger;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "controllerAttribute", {
        get: function () {
            return this.schema.controllerAttribute;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "modules", {
        get: function () {
            return Array.from(this.modulesByIdentifier.values());
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Router.prototype, "contexts", {
        get: function () {
            return this.modules.reduce(function (contexts, module) { return contexts.concat(module.contexts); }, []);
        },
        enumerable: false,
        configurable: true
    });
    Router.prototype.start = function () {
        this.scopeObserver.start();
    };
    Router.prototype.stop = function () {
        this.scopeObserver.stop();
    };
    Router.prototype.loadDefinition = function (definition) {
        this.unloadIdentifier(definition.identifier);
        var module = new _module__WEBPACK_IMPORTED_MODULE_0__["Module"](this.application, definition);
        this.connectModule(module);
    };
    Router.prototype.unloadIdentifier = function (identifier) {
        var module = this.modulesByIdentifier.get(identifier);
        if (module) {
            this.disconnectModule(module);
        }
    };
    Router.prototype.getContextForElementAndIdentifier = function (element, identifier) {
        var module = this.modulesByIdentifier.get(identifier);
        if (module) {
            return module.contexts.find(function (context) { return context.element == element; });
        }
    };
    // Error handler delegate
    /** @hidden */
    Router.prototype.handleError = function (error, message, detail) {
        this.application.handleError(error, message, detail);
    };
    // Scope observer delegate
    /** @hidden */
    Router.prototype.createScopeForElementAndIdentifier = function (element, identifier) {
        return new _scope__WEBPACK_IMPORTED_MODULE_2__["Scope"](this.schema, element, identifier, this.logger);
    };
    /** @hidden */
    Router.prototype.scopeConnected = function (scope) {
        this.scopesByIdentifier.add(scope.identifier, scope);
        var module = this.modulesByIdentifier.get(scope.identifier);
        if (module) {
            module.connectContextForScope(scope);
        }
    };
    /** @hidden */
    Router.prototype.scopeDisconnected = function (scope) {
        this.scopesByIdentifier.delete(scope.identifier, scope);
        var module = this.modulesByIdentifier.get(scope.identifier);
        if (module) {
            module.disconnectContextForScope(scope);
        }
    };
    // Modules
    Router.prototype.connectModule = function (module) {
        this.modulesByIdentifier.set(module.identifier, module);
        var scopes = this.scopesByIdentifier.getValuesForKey(module.identifier);
        scopes.forEach(function (scope) { return module.connectContextForScope(scope); });
    };
    Router.prototype.disconnectModule = function (module) {
        this.modulesByIdentifier.delete(module.identifier);
        var scopes = this.scopesByIdentifier.getValuesForKey(module.identifier);
        scopes.forEach(function (scope) { return module.disconnectContextForScope(scope); });
    };
    return Router;
}());

//# sourceMappingURL=router.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/schema.js":
/*!***********************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/schema.js ***!
  \***********************************************************************************/
/*! exports provided: defaultSchema */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "defaultSchema", function() { return defaultSchema; });
var defaultSchema = {
    controllerAttribute: "data-controller",
    actionAttribute: "data-action",
    targetAttribute: "data-target"
};
//# sourceMappingURL=schema.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/scope.js":
/*!**********************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/scope.js ***!
  \**********************************************************************************/
/*! exports provided: Scope */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Scope", function() { return Scope; });
/* harmony import */ var _class_map__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./class_map */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/class_map.js");
/* harmony import */ var _data_map__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./data_map */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/data_map.js");
/* harmony import */ var _guide__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./guide */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/guide.js");
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./selectors */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/selectors.js");
/* harmony import */ var _target_set__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./target_set */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/target_set.js");
var __spreadArrays = (undefined && undefined.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};





var Scope = /** @class */ (function () {
    function Scope(schema, element, identifier, logger) {
        var _this = this;
        this.targets = new _target_set__WEBPACK_IMPORTED_MODULE_4__["TargetSet"](this);
        this.classes = new _class_map__WEBPACK_IMPORTED_MODULE_0__["ClassMap"](this);
        this.data = new _data_map__WEBPACK_IMPORTED_MODULE_1__["DataMap"](this);
        this.containsElement = function (element) {
            return element.closest(_this.controllerSelector) === _this.element;
        };
        this.schema = schema;
        this.element = element;
        this.identifier = identifier;
        this.guide = new _guide__WEBPACK_IMPORTED_MODULE_2__["Guide"](logger);
    }
    Scope.prototype.findElement = function (selector) {
        return this.element.matches(selector)
            ? this.element
            : this.queryElements(selector).find(this.containsElement);
    };
    Scope.prototype.findAllElements = function (selector) {
        return __spreadArrays(this.element.matches(selector) ? [this.element] : [], this.queryElements(selector).filter(this.containsElement));
    };
    Scope.prototype.queryElements = function (selector) {
        return Array.from(this.element.querySelectorAll(selector));
    };
    Object.defineProperty(Scope.prototype, "controllerSelector", {
        get: function () {
            return Object(_selectors__WEBPACK_IMPORTED_MODULE_3__["attributeValueContainsToken"])(this.schema.controllerAttribute, this.identifier);
        },
        enumerable: false,
        configurable: true
    });
    return Scope;
}());

//# sourceMappingURL=scope.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/scope_observer.js":
/*!*******************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/scope_observer.js ***!
  \*******************************************************************************************/
/*! exports provided: ScopeObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ScopeObserver", function() { return ScopeObserver; });
/* harmony import */ var _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @stimulus/mutation-observers */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/index.js");

var ScopeObserver = /** @class */ (function () {
    function ScopeObserver(element, schema, delegate) {
        this.element = element;
        this.schema = schema;
        this.delegate = delegate;
        this.valueListObserver = new _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_0__["ValueListObserver"](this.element, this.controllerAttribute, this);
        this.scopesByIdentifierByElement = new WeakMap;
        this.scopeReferenceCounts = new WeakMap;
    }
    ScopeObserver.prototype.start = function () {
        this.valueListObserver.start();
    };
    ScopeObserver.prototype.stop = function () {
        this.valueListObserver.stop();
    };
    Object.defineProperty(ScopeObserver.prototype, "controllerAttribute", {
        get: function () {
            return this.schema.controllerAttribute;
        },
        enumerable: false,
        configurable: true
    });
    // Value observer delegate
    /** @hidden */
    ScopeObserver.prototype.parseValueForToken = function (token) {
        var element = token.element, identifier = token.content;
        var scopesByIdentifier = this.fetchScopesByIdentifierForElement(element);
        var scope = scopesByIdentifier.get(identifier);
        if (!scope) {
            scope = this.delegate.createScopeForElementAndIdentifier(element, identifier);
            scopesByIdentifier.set(identifier, scope);
        }
        return scope;
    };
    /** @hidden */
    ScopeObserver.prototype.elementMatchedValue = function (element, value) {
        var referenceCount = (this.scopeReferenceCounts.get(value) || 0) + 1;
        this.scopeReferenceCounts.set(value, referenceCount);
        if (referenceCount == 1) {
            this.delegate.scopeConnected(value);
        }
    };
    /** @hidden */
    ScopeObserver.prototype.elementUnmatchedValue = function (element, value) {
        var referenceCount = this.scopeReferenceCounts.get(value);
        if (referenceCount) {
            this.scopeReferenceCounts.set(value, referenceCount - 1);
            if (referenceCount == 1) {
                this.delegate.scopeDisconnected(value);
            }
        }
    };
    ScopeObserver.prototype.fetchScopesByIdentifierForElement = function (element) {
        var scopesByIdentifier = this.scopesByIdentifierByElement.get(element);
        if (!scopesByIdentifier) {
            scopesByIdentifier = new Map;
            this.scopesByIdentifierByElement.set(element, scopesByIdentifier);
        }
        return scopesByIdentifier;
    };
    return ScopeObserver;
}());

//# sourceMappingURL=scope_observer.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/selectors.js":
/*!**************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/selectors.js ***!
  \**************************************************************************************/
/*! exports provided: attributeValueContainsToken */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "attributeValueContainsToken", function() { return attributeValueContainsToken; });
/** @hidden */
function attributeValueContainsToken(attributeName, token) {
    return "[" + attributeName + "~=\"" + token + "\"]";
}
//# sourceMappingURL=selectors.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/string_helpers.js":
/*!*******************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/string_helpers.js ***!
  \*******************************************************************************************/
/*! exports provided: camelize, capitalize, dasherize */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "camelize", function() { return camelize; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "capitalize", function() { return capitalize; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "dasherize", function() { return dasherize; });
function camelize(value) {
    return value.replace(/(?:[_-])([a-z0-9])/g, function (_, char) { return char.toUpperCase(); });
}
function capitalize(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}
function dasherize(value) {
    return value.replace(/([A-Z])/g, function (_, char) { return "-" + char.toLowerCase(); });
}
//# sourceMappingURL=string_helpers.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/target_properties.js":
/*!**********************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/target_properties.js ***!
  \**********************************************************************************************/
/*! exports provided: TargetPropertiesBlessing */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "TargetPropertiesBlessing", function() { return TargetPropertiesBlessing; });
/* harmony import */ var _inheritable_statics__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./inheritable_statics */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/inheritable_statics.js");
/* harmony import */ var _string_helpers__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./string_helpers */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/string_helpers.js");


/** @hidden */
function TargetPropertiesBlessing(constructor) {
    var targets = Object(_inheritable_statics__WEBPACK_IMPORTED_MODULE_0__["readInheritableStaticArrayValues"])(constructor, "targets");
    return targets.reduce(function (properties, targetDefinition) {
        return Object.assign(properties, propertiesForTargetDefinition(targetDefinition));
    }, {});
}
function propertiesForTargetDefinition(name) {
    var _a;
    return _a = {},
        _a[name + "Target"] = {
            get: function () {
                var target = this.targets.find(name);
                if (target) {
                    return target;
                }
                else {
                    throw new Error("Missing target element \"" + this.identifier + "." + name + "\"");
                }
            }
        },
        _a[name + "Targets"] = {
            get: function () {
                return this.targets.findAll(name);
            }
        },
        _a["has" + Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["capitalize"])(name) + "Target"] = {
            get: function () {
                return this.targets.has(name);
            }
        },
        _a;
}
//# sourceMappingURL=target_properties.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/target_set.js":
/*!***************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/target_set.js ***!
  \***************************************************************************************/
/*! exports provided: TargetSet */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "TargetSet", function() { return TargetSet; });
/* harmony import */ var _selectors__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./selectors */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/selectors.js");
var __spreadArrays = (undefined && undefined.__spreadArrays) || function () {
    for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
    for (var r = Array(s), k = 0, i = 0; i < il; i++)
        for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
            r[k] = a[j];
    return r;
};

var TargetSet = /** @class */ (function () {
    function TargetSet(scope) {
        this.scope = scope;
    }
    Object.defineProperty(TargetSet.prototype, "element", {
        get: function () {
            return this.scope.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(TargetSet.prototype, "identifier", {
        get: function () {
            return this.scope.identifier;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(TargetSet.prototype, "schema", {
        get: function () {
            return this.scope.schema;
        },
        enumerable: false,
        configurable: true
    });
    TargetSet.prototype.has = function (targetName) {
        return this.find(targetName) != null;
    };
    TargetSet.prototype.find = function () {
        var _this = this;
        var targetNames = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            targetNames[_i] = arguments[_i];
        }
        return targetNames.reduce(function (target, targetName) {
            return target
                || _this.findTarget(targetName)
                || _this.findLegacyTarget(targetName);
        }, undefined);
    };
    TargetSet.prototype.findAll = function () {
        var _this = this;
        var targetNames = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            targetNames[_i] = arguments[_i];
        }
        return targetNames.reduce(function (targets, targetName) { return __spreadArrays(targets, _this.findAllTargets(targetName), _this.findAllLegacyTargets(targetName)); }, []);
    };
    TargetSet.prototype.findTarget = function (targetName) {
        var selector = this.getSelectorForTargetName(targetName);
        return this.scope.findElement(selector);
    };
    TargetSet.prototype.findAllTargets = function (targetName) {
        var selector = this.getSelectorForTargetName(targetName);
        return this.scope.findAllElements(selector);
    };
    TargetSet.prototype.getSelectorForTargetName = function (targetName) {
        var attributeName = "data-" + this.identifier + "-target";
        return Object(_selectors__WEBPACK_IMPORTED_MODULE_0__["attributeValueContainsToken"])(attributeName, targetName);
    };
    TargetSet.prototype.findLegacyTarget = function (targetName) {
        var selector = this.getLegacySelectorForTargetName(targetName);
        return this.deprecate(this.scope.findElement(selector), targetName);
    };
    TargetSet.prototype.findAllLegacyTargets = function (targetName) {
        var _this = this;
        var selector = this.getLegacySelectorForTargetName(targetName);
        return this.scope.findAllElements(selector).map(function (element) { return _this.deprecate(element, targetName); });
    };
    TargetSet.prototype.getLegacySelectorForTargetName = function (targetName) {
        var targetDescriptor = this.identifier + "." + targetName;
        return Object(_selectors__WEBPACK_IMPORTED_MODULE_0__["attributeValueContainsToken"])(this.schema.targetAttribute, targetDescriptor);
    };
    TargetSet.prototype.deprecate = function (element, targetName) {
        if (element) {
            var identifier = this.identifier;
            var attributeName = this.schema.targetAttribute;
            this.guide.warn(element, "target:" + targetName, "Please replace " + attributeName + "=\"" + identifier + "." + targetName + "\" with data-" + identifier + "-target=\"" + targetName + "\". " +
                ("The " + attributeName + " attribute is deprecated and will be removed in a future version of Stimulus."));
        }
        return element;
    };
    Object.defineProperty(TargetSet.prototype, "guide", {
        get: function () {
            return this.scope.guide;
        },
        enumerable: false,
        configurable: true
    });
    return TargetSet;
}());

//# sourceMappingURL=target_set.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/value_observer.js":
/*!*******************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/value_observer.js ***!
  \*******************************************************************************************/
/*! exports provided: ValueObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ValueObserver", function() { return ValueObserver; });
/* harmony import */ var _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @stimulus/mutation-observers */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/index.js");

var ValueObserver = /** @class */ (function () {
    function ValueObserver(context, receiver) {
        this.context = context;
        this.receiver = receiver;
        this.stringMapObserver = new _stimulus_mutation_observers__WEBPACK_IMPORTED_MODULE_0__["StringMapObserver"](this.element, this);
        this.valueDescriptorMap = this.controller.valueDescriptorMap;
        this.invokeChangedCallbacksForDefaultValues();
    }
    ValueObserver.prototype.start = function () {
        this.stringMapObserver.start();
    };
    ValueObserver.prototype.stop = function () {
        this.stringMapObserver.stop();
    };
    Object.defineProperty(ValueObserver.prototype, "element", {
        get: function () {
            return this.context.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(ValueObserver.prototype, "controller", {
        get: function () {
            return this.context.controller;
        },
        enumerable: false,
        configurable: true
    });
    // String map observer delegate
    ValueObserver.prototype.getStringMapKeyForAttribute = function (attributeName) {
        if (attributeName in this.valueDescriptorMap) {
            return this.valueDescriptorMap[attributeName].name;
        }
    };
    ValueObserver.prototype.stringMapValueChanged = function (attributeValue, name) {
        this.invokeChangedCallbackForValue(name);
    };
    ValueObserver.prototype.invokeChangedCallbacksForDefaultValues = function () {
        for (var _i = 0, _a = this.valueDescriptors; _i < _a.length; _i++) {
            var _b = _a[_i], key = _b.key, name_1 = _b.name, defaultValue = _b.defaultValue;
            if (defaultValue != undefined && !this.controller.data.has(key)) {
                this.invokeChangedCallbackForValue(name_1);
            }
        }
    };
    ValueObserver.prototype.invokeChangedCallbackForValue = function (name) {
        var methodName = name + "Changed";
        var method = this.receiver[methodName];
        if (typeof method == "function") {
            var value = this.receiver[name];
            method.call(this.receiver, value);
        }
    };
    Object.defineProperty(ValueObserver.prototype, "valueDescriptors", {
        get: function () {
            var valueDescriptorMap = this.valueDescriptorMap;
            return Object.keys(valueDescriptorMap).map(function (key) { return valueDescriptorMap[key]; });
        },
        enumerable: false,
        configurable: true
    });
    return ValueObserver;
}());

//# sourceMappingURL=value_observer.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/value_properties.js":
/*!*********************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/value_properties.js ***!
  \*********************************************************************************************/
/*! exports provided: ValuePropertiesBlessing, propertiesForValueDefinitionPair */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ValuePropertiesBlessing", function() { return ValuePropertiesBlessing; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "propertiesForValueDefinitionPair", function() { return propertiesForValueDefinitionPair; });
/* harmony import */ var _inheritable_statics__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./inheritable_statics */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/inheritable_statics.js");
/* harmony import */ var _string_helpers__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./string_helpers */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/string_helpers.js");


/** @hidden */
function ValuePropertiesBlessing(constructor) {
    var valueDefinitionPairs = Object(_inheritable_statics__WEBPACK_IMPORTED_MODULE_0__["readInheritableStaticObjectPairs"])(constructor, "values");
    var propertyDescriptorMap = {
        valueDescriptorMap: {
            get: function () {
                var _this = this;
                return valueDefinitionPairs.reduce(function (result, valueDefinitionPair) {
                    var _a;
                    var valueDescriptor = parseValueDefinitionPair(valueDefinitionPair);
                    var attributeName = _this.data.getAttributeNameForKey(valueDescriptor.key);
                    return Object.assign(result, (_a = {}, _a[attributeName] = valueDescriptor, _a));
                }, {});
            }
        }
    };
    return valueDefinitionPairs.reduce(function (properties, valueDefinitionPair) {
        return Object.assign(properties, propertiesForValueDefinitionPair(valueDefinitionPair));
    }, propertyDescriptorMap);
}
/** @hidden */
function propertiesForValueDefinitionPair(valueDefinitionPair) {
    var _a;
    var definition = parseValueDefinitionPair(valueDefinitionPair);
    var type = definition.type, key = definition.key, name = definition.name;
    var read = readers[type], write = writers[type] || writers.default;
    return _a = {},
        _a[name] = {
            get: function () {
                var value = this.data.get(key);
                if (value !== null) {
                    return read(value);
                }
                else {
                    return definition.defaultValue;
                }
            },
            set: function (value) {
                if (value === undefined) {
                    this.data.delete(key);
                }
                else {
                    this.data.set(key, write(value));
                }
            }
        },
        _a["has" + Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["capitalize"])(name)] = {
            get: function () {
                return this.data.has(key);
            }
        },
        _a;
}
function parseValueDefinitionPair(_a) {
    var token = _a[0], typeConstant = _a[1];
    var type = parseValueTypeConstant(typeConstant);
    return valueDescriptorForTokenAndType(token, type);
}
function parseValueTypeConstant(typeConstant) {
    switch (typeConstant) {
        case Array: return "array";
        case Boolean: return "boolean";
        case Number: return "number";
        case Object: return "object";
        case String: return "string";
    }
    throw new Error("Unknown value type constant \"" + typeConstant + "\"");
}
function valueDescriptorForTokenAndType(token, type) {
    var key = Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["dasherize"])(token) + "-value";
    return {
        type: type,
        key: key,
        name: Object(_string_helpers__WEBPACK_IMPORTED_MODULE_1__["camelize"])(key),
        get defaultValue() { return defaultValuesByType[type]; }
    };
}
var defaultValuesByType = {
    get array() { return []; },
    boolean: false,
    number: 0,
    get object() { return {}; },
    string: ""
};
var readers = {
    array: function (value) {
        var array = JSON.parse(value);
        if (!Array.isArray(array)) {
            throw new TypeError("Expected array");
        }
        return array;
    },
    boolean: function (value) {
        return !(value == "0" || value == "false");
    },
    number: function (value) {
        return parseFloat(value);
    },
    object: function (value) {
        var object = JSON.parse(value);
        if (object === null || typeof object != "object" || Array.isArray(object)) {
            throw new TypeError("Expected object");
        }
        return object;
    },
    string: function (value) {
        return value;
    }
};
var writers = {
    default: writeString,
    array: writeJSON,
    object: writeJSON
};
function writeJSON(value) {
    return JSON.stringify(value);
}
function writeString(value) {
    return "" + value;
}
//# sourceMappingURL=value_properties.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/index.js":
/*!**************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/index.js ***!
  \**************************************************************************************/
/*! exports provided: IndexedMultimap, Multimap, add, del, fetch, prune */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _indexed_multimap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./indexed_multimap */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/indexed_multimap.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "IndexedMultimap", function() { return _indexed_multimap__WEBPACK_IMPORTED_MODULE_0__["IndexedMultimap"]; });

/* harmony import */ var _multimap__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./multimap */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/multimap.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Multimap", function() { return _multimap__WEBPACK_IMPORTED_MODULE_1__["Multimap"]; });

/* harmony import */ var _set_operations__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./set_operations */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/set_operations.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "add", function() { return _set_operations__WEBPACK_IMPORTED_MODULE_2__["add"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "del", function() { return _set_operations__WEBPACK_IMPORTED_MODULE_2__["del"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "fetch", function() { return _set_operations__WEBPACK_IMPORTED_MODULE_2__["fetch"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "prune", function() { return _set_operations__WEBPACK_IMPORTED_MODULE_2__["prune"]; });




//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/indexed_multimap.js":
/*!*************************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/indexed_multimap.js ***!
  \*************************************************************************************************/
/*! exports provided: IndexedMultimap */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "IndexedMultimap", function() { return IndexedMultimap; });
/* harmony import */ var _multimap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./multimap */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/multimap.js");
/* harmony import */ var _set_operations__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./set_operations */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/set_operations.js");
var __extends = (undefined && undefined.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();


var IndexedMultimap = /** @class */ (function (_super) {
    __extends(IndexedMultimap, _super);
    function IndexedMultimap() {
        var _this = _super.call(this) || this;
        _this.keysByValue = new Map;
        return _this;
    }
    Object.defineProperty(IndexedMultimap.prototype, "values", {
        get: function () {
            return Array.from(this.keysByValue.keys());
        },
        enumerable: false,
        configurable: true
    });
    IndexedMultimap.prototype.add = function (key, value) {
        _super.prototype.add.call(this, key, value);
        Object(_set_operations__WEBPACK_IMPORTED_MODULE_1__["add"])(this.keysByValue, value, key);
    };
    IndexedMultimap.prototype.delete = function (key, value) {
        _super.prototype.delete.call(this, key, value);
        Object(_set_operations__WEBPACK_IMPORTED_MODULE_1__["del"])(this.keysByValue, value, key);
    };
    IndexedMultimap.prototype.hasValue = function (value) {
        return this.keysByValue.has(value);
    };
    IndexedMultimap.prototype.getKeysForValue = function (value) {
        var set = this.keysByValue.get(value);
        return set ? Array.from(set) : [];
    };
    return IndexedMultimap;
}(_multimap__WEBPACK_IMPORTED_MODULE_0__["Multimap"]));

//# sourceMappingURL=indexed_multimap.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/multimap.js":
/*!*****************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/multimap.js ***!
  \*****************************************************************************************/
/*! exports provided: Multimap */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Multimap", function() { return Multimap; });
/* harmony import */ var _set_operations__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./set_operations */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/set_operations.js");

var Multimap = /** @class */ (function () {
    function Multimap() {
        this.valuesByKey = new Map();
    }
    Object.defineProperty(Multimap.prototype, "values", {
        get: function () {
            var sets = Array.from(this.valuesByKey.values());
            return sets.reduce(function (values, set) { return values.concat(Array.from(set)); }, []);
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Multimap.prototype, "size", {
        get: function () {
            var sets = Array.from(this.valuesByKey.values());
            return sets.reduce(function (size, set) { return size + set.size; }, 0);
        },
        enumerable: false,
        configurable: true
    });
    Multimap.prototype.add = function (key, value) {
        Object(_set_operations__WEBPACK_IMPORTED_MODULE_0__["add"])(this.valuesByKey, key, value);
    };
    Multimap.prototype.delete = function (key, value) {
        Object(_set_operations__WEBPACK_IMPORTED_MODULE_0__["del"])(this.valuesByKey, key, value);
    };
    Multimap.prototype.has = function (key, value) {
        var values = this.valuesByKey.get(key);
        return values != null && values.has(value);
    };
    Multimap.prototype.hasKey = function (key) {
        return this.valuesByKey.has(key);
    };
    Multimap.prototype.hasValue = function (value) {
        var sets = Array.from(this.valuesByKey.values());
        return sets.some(function (set) { return set.has(value); });
    };
    Multimap.prototype.getValuesForKey = function (key) {
        var values = this.valuesByKey.get(key);
        return values ? Array.from(values) : [];
    };
    Multimap.prototype.getKeysForValue = function (value) {
        return Array.from(this.valuesByKey)
            .filter(function (_a) {
            var key = _a[0], values = _a[1];
            return values.has(value);
        })
            .map(function (_a) {
            var key = _a[0], values = _a[1];
            return key;
        });
    };
    return Multimap;
}());

//# sourceMappingURL=multimap.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/set_operations.js":
/*!***********************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/set_operations.js ***!
  \***********************************************************************************************/
/*! exports provided: add, del, fetch, prune */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "add", function() { return add; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "del", function() { return del; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "fetch", function() { return fetch; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "prune", function() { return prune; });
function add(map, key, value) {
    fetch(map, key).add(value);
}
function del(map, key, value) {
    fetch(map, key).delete(value);
    prune(map, key);
}
function fetch(map, key) {
    var values = map.get(key);
    if (!values) {
        values = new Set();
        map.set(key, values);
    }
    return values;
}
function prune(map, key) {
    var values = map.get(key);
    if (values != null && values.size == 0) {
        map.delete(key);
    }
}
//# sourceMappingURL=set_operations.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/attribute_observer.js":
/*!*************************************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/attribute_observer.js ***!
  \*************************************************************************************************************/
/*! exports provided: AttributeObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "AttributeObserver", function() { return AttributeObserver; });
/* harmony import */ var _element_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./element_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/element_observer.js");

var AttributeObserver = /** @class */ (function () {
    function AttributeObserver(element, attributeName, delegate) {
        this.attributeName = attributeName;
        this.delegate = delegate;
        this.elementObserver = new _element_observer__WEBPACK_IMPORTED_MODULE_0__["ElementObserver"](element, this);
    }
    Object.defineProperty(AttributeObserver.prototype, "element", {
        get: function () {
            return this.elementObserver.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(AttributeObserver.prototype, "selector", {
        get: function () {
            return "[" + this.attributeName + "]";
        },
        enumerable: false,
        configurable: true
    });
    AttributeObserver.prototype.start = function () {
        this.elementObserver.start();
    };
    AttributeObserver.prototype.stop = function () {
        this.elementObserver.stop();
    };
    AttributeObserver.prototype.refresh = function () {
        this.elementObserver.refresh();
    };
    Object.defineProperty(AttributeObserver.prototype, "started", {
        get: function () {
            return this.elementObserver.started;
        },
        enumerable: false,
        configurable: true
    });
    // Element observer delegate
    AttributeObserver.prototype.matchElement = function (element) {
        return element.hasAttribute(this.attributeName);
    };
    AttributeObserver.prototype.matchElementsInTree = function (tree) {
        var match = this.matchElement(tree) ? [tree] : [];
        var matches = Array.from(tree.querySelectorAll(this.selector));
        return match.concat(matches);
    };
    AttributeObserver.prototype.elementMatched = function (element) {
        if (this.delegate.elementMatchedAttribute) {
            this.delegate.elementMatchedAttribute(element, this.attributeName);
        }
    };
    AttributeObserver.prototype.elementUnmatched = function (element) {
        if (this.delegate.elementUnmatchedAttribute) {
            this.delegate.elementUnmatchedAttribute(element, this.attributeName);
        }
    };
    AttributeObserver.prototype.elementAttributeChanged = function (element, attributeName) {
        if (this.delegate.elementAttributeValueChanged && this.attributeName == attributeName) {
            this.delegate.elementAttributeValueChanged(element, attributeName);
        }
    };
    return AttributeObserver;
}());

//# sourceMappingURL=attribute_observer.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/element_observer.js":
/*!***********************************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/element_observer.js ***!
  \***********************************************************************************************************/
/*! exports provided: ElementObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ElementObserver", function() { return ElementObserver; });
var ElementObserver = /** @class */ (function () {
    function ElementObserver(element, delegate) {
        var _this = this;
        this.element = element;
        this.started = false;
        this.delegate = delegate;
        this.elements = new Set;
        this.mutationObserver = new MutationObserver(function (mutations) { return _this.processMutations(mutations); });
    }
    ElementObserver.prototype.start = function () {
        if (!this.started) {
            this.started = true;
            this.mutationObserver.observe(this.element, { attributes: true, childList: true, subtree: true });
            this.refresh();
        }
    };
    ElementObserver.prototype.stop = function () {
        if (this.started) {
            this.mutationObserver.takeRecords();
            this.mutationObserver.disconnect();
            this.started = false;
        }
    };
    ElementObserver.prototype.refresh = function () {
        if (this.started) {
            var matches = new Set(this.matchElementsInTree());
            for (var _i = 0, _a = Array.from(this.elements); _i < _a.length; _i++) {
                var element = _a[_i];
                if (!matches.has(element)) {
                    this.removeElement(element);
                }
            }
            for (var _b = 0, _c = Array.from(matches); _b < _c.length; _b++) {
                var element = _c[_b];
                this.addElement(element);
            }
        }
    };
    // Mutation record processing
    ElementObserver.prototype.processMutations = function (mutations) {
        if (this.started) {
            for (var _i = 0, mutations_1 = mutations; _i < mutations_1.length; _i++) {
                var mutation = mutations_1[_i];
                this.processMutation(mutation);
            }
        }
    };
    ElementObserver.prototype.processMutation = function (mutation) {
        if (mutation.type == "attributes") {
            this.processAttributeChange(mutation.target, mutation.attributeName);
        }
        else if (mutation.type == "childList") {
            this.processRemovedNodes(mutation.removedNodes);
            this.processAddedNodes(mutation.addedNodes);
        }
    };
    ElementObserver.prototype.processAttributeChange = function (node, attributeName) {
        var element = node;
        if (this.elements.has(element)) {
            if (this.delegate.elementAttributeChanged && this.matchElement(element)) {
                this.delegate.elementAttributeChanged(element, attributeName);
            }
            else {
                this.removeElement(element);
            }
        }
        else if (this.matchElement(element)) {
            this.addElement(element);
        }
    };
    ElementObserver.prototype.processRemovedNodes = function (nodes) {
        for (var _i = 0, _a = Array.from(nodes); _i < _a.length; _i++) {
            var node = _a[_i];
            var element = this.elementFromNode(node);
            if (element) {
                this.processTree(element, this.removeElement);
            }
        }
    };
    ElementObserver.prototype.processAddedNodes = function (nodes) {
        for (var _i = 0, _a = Array.from(nodes); _i < _a.length; _i++) {
            var node = _a[_i];
            var element = this.elementFromNode(node);
            if (element && this.elementIsActive(element)) {
                this.processTree(element, this.addElement);
            }
        }
    };
    // Element matching
    ElementObserver.prototype.matchElement = function (element) {
        return this.delegate.matchElement(element);
    };
    ElementObserver.prototype.matchElementsInTree = function (tree) {
        if (tree === void 0) { tree = this.element; }
        return this.delegate.matchElementsInTree(tree);
    };
    ElementObserver.prototype.processTree = function (tree, processor) {
        for (var _i = 0, _a = this.matchElementsInTree(tree); _i < _a.length; _i++) {
            var element = _a[_i];
            processor.call(this, element);
        }
    };
    ElementObserver.prototype.elementFromNode = function (node) {
        if (node.nodeType == Node.ELEMENT_NODE) {
            return node;
        }
    };
    ElementObserver.prototype.elementIsActive = function (element) {
        if (element.isConnected != this.element.isConnected) {
            return false;
        }
        else {
            return this.element.contains(element);
        }
    };
    // Element tracking
    ElementObserver.prototype.addElement = function (element) {
        if (!this.elements.has(element)) {
            if (this.elementIsActive(element)) {
                this.elements.add(element);
                if (this.delegate.elementMatched) {
                    this.delegate.elementMatched(element);
                }
            }
        }
    };
    ElementObserver.prototype.removeElement = function (element) {
        if (this.elements.has(element)) {
            this.elements.delete(element);
            if (this.delegate.elementUnmatched) {
                this.delegate.elementUnmatched(element);
            }
        }
    };
    return ElementObserver;
}());

//# sourceMappingURL=element_observer.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/index.js":
/*!************************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/index.js ***!
  \************************************************************************************************/
/*! exports provided: AttributeObserver, ElementObserver, StringMapObserver, TokenListObserver, ValueListObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _attribute_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./attribute_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/attribute_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "AttributeObserver", function() { return _attribute_observer__WEBPACK_IMPORTED_MODULE_0__["AttributeObserver"]; });

/* harmony import */ var _element_observer__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./element_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/element_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "ElementObserver", function() { return _element_observer__WEBPACK_IMPORTED_MODULE_1__["ElementObserver"]; });

/* harmony import */ var _string_map_observer__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./string_map_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/string_map_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "StringMapObserver", function() { return _string_map_observer__WEBPACK_IMPORTED_MODULE_2__["StringMapObserver"]; });

/* harmony import */ var _token_list_observer__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./token_list_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/token_list_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "TokenListObserver", function() { return _token_list_observer__WEBPACK_IMPORTED_MODULE_3__["TokenListObserver"]; });

/* harmony import */ var _value_list_observer__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./value_list_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/value_list_observer.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "ValueListObserver", function() { return _value_list_observer__WEBPACK_IMPORTED_MODULE_4__["ValueListObserver"]; });






//# sourceMappingURL=index.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/string_map_observer.js":
/*!**************************************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/string_map_observer.js ***!
  \**************************************************************************************************************/
/*! exports provided: StringMapObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "StringMapObserver", function() { return StringMapObserver; });
var StringMapObserver = /** @class */ (function () {
    function StringMapObserver(element, delegate) {
        var _this = this;
        this.element = element;
        this.delegate = delegate;
        this.started = false;
        this.stringMap = new Map;
        this.mutationObserver = new MutationObserver(function (mutations) { return _this.processMutations(mutations); });
    }
    StringMapObserver.prototype.start = function () {
        if (!this.started) {
            this.started = true;
            this.mutationObserver.observe(this.element, { attributes: true });
            this.refresh();
        }
    };
    StringMapObserver.prototype.stop = function () {
        if (this.started) {
            this.mutationObserver.takeRecords();
            this.mutationObserver.disconnect();
            this.started = false;
        }
    };
    StringMapObserver.prototype.refresh = function () {
        if (this.started) {
            for (var _i = 0, _a = this.knownAttributeNames; _i < _a.length; _i++) {
                var attributeName = _a[_i];
                this.refreshAttribute(attributeName);
            }
        }
    };
    // Mutation record processing
    StringMapObserver.prototype.processMutations = function (mutations) {
        if (this.started) {
            for (var _i = 0, mutations_1 = mutations; _i < mutations_1.length; _i++) {
                var mutation = mutations_1[_i];
                this.processMutation(mutation);
            }
        }
    };
    StringMapObserver.prototype.processMutation = function (mutation) {
        var attributeName = mutation.attributeName;
        if (attributeName) {
            this.refreshAttribute(attributeName);
        }
    };
    // State tracking
    StringMapObserver.prototype.refreshAttribute = function (attributeName) {
        var key = this.delegate.getStringMapKeyForAttribute(attributeName);
        if (key != null) {
            if (!this.stringMap.has(attributeName)) {
                this.stringMapKeyAdded(key, attributeName);
            }
            var value = this.element.getAttribute(attributeName);
            if (this.stringMap.get(attributeName) != value) {
                this.stringMapValueChanged(value, key);
            }
            if (value == null) {
                this.stringMap.delete(attributeName);
                this.stringMapKeyRemoved(key, attributeName);
            }
            else {
                this.stringMap.set(attributeName, value);
            }
        }
    };
    StringMapObserver.prototype.stringMapKeyAdded = function (key, attributeName) {
        if (this.delegate.stringMapKeyAdded) {
            this.delegate.stringMapKeyAdded(key, attributeName);
        }
    };
    StringMapObserver.prototype.stringMapValueChanged = function (value, key) {
        if (this.delegate.stringMapValueChanged) {
            this.delegate.stringMapValueChanged(value, key);
        }
    };
    StringMapObserver.prototype.stringMapKeyRemoved = function (key, attributeName) {
        if (this.delegate.stringMapKeyRemoved) {
            this.delegate.stringMapKeyRemoved(key, attributeName);
        }
    };
    Object.defineProperty(StringMapObserver.prototype, "knownAttributeNames", {
        get: function () {
            return Array.from(new Set(this.currentAttributeNames.concat(this.recordedAttributeNames)));
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(StringMapObserver.prototype, "currentAttributeNames", {
        get: function () {
            return Array.from(this.element.attributes).map(function (attribute) { return attribute.name; });
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(StringMapObserver.prototype, "recordedAttributeNames", {
        get: function () {
            return Array.from(this.stringMap.keys());
        },
        enumerable: false,
        configurable: true
    });
    return StringMapObserver;
}());

//# sourceMappingURL=string_map_observer.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/token_list_observer.js":
/*!**************************************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/token_list_observer.js ***!
  \**************************************************************************************************************/
/*! exports provided: TokenListObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "TokenListObserver", function() { return TokenListObserver; });
/* harmony import */ var _attribute_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./attribute_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/attribute_observer.js");
/* harmony import */ var _stimulus_multimap__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @stimulus/multimap */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/multimap/dist/index.js");


var TokenListObserver = /** @class */ (function () {
    function TokenListObserver(element, attributeName, delegate) {
        this.attributeObserver = new _attribute_observer__WEBPACK_IMPORTED_MODULE_0__["AttributeObserver"](element, attributeName, this);
        this.delegate = delegate;
        this.tokensByElement = new _stimulus_multimap__WEBPACK_IMPORTED_MODULE_1__["Multimap"];
    }
    Object.defineProperty(TokenListObserver.prototype, "started", {
        get: function () {
            return this.attributeObserver.started;
        },
        enumerable: false,
        configurable: true
    });
    TokenListObserver.prototype.start = function () {
        this.attributeObserver.start();
    };
    TokenListObserver.prototype.stop = function () {
        this.attributeObserver.stop();
    };
    TokenListObserver.prototype.refresh = function () {
        this.attributeObserver.refresh();
    };
    Object.defineProperty(TokenListObserver.prototype, "element", {
        get: function () {
            return this.attributeObserver.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(TokenListObserver.prototype, "attributeName", {
        get: function () {
            return this.attributeObserver.attributeName;
        },
        enumerable: false,
        configurable: true
    });
    // Attribute observer delegate
    TokenListObserver.prototype.elementMatchedAttribute = function (element) {
        this.tokensMatched(this.readTokensForElement(element));
    };
    TokenListObserver.prototype.elementAttributeValueChanged = function (element) {
        var _a = this.refreshTokensForElement(element), unmatchedTokens = _a[0], matchedTokens = _a[1];
        this.tokensUnmatched(unmatchedTokens);
        this.tokensMatched(matchedTokens);
    };
    TokenListObserver.prototype.elementUnmatchedAttribute = function (element) {
        this.tokensUnmatched(this.tokensByElement.getValuesForKey(element));
    };
    TokenListObserver.prototype.tokensMatched = function (tokens) {
        var _this = this;
        tokens.forEach(function (token) { return _this.tokenMatched(token); });
    };
    TokenListObserver.prototype.tokensUnmatched = function (tokens) {
        var _this = this;
        tokens.forEach(function (token) { return _this.tokenUnmatched(token); });
    };
    TokenListObserver.prototype.tokenMatched = function (token) {
        this.delegate.tokenMatched(token);
        this.tokensByElement.add(token.element, token);
    };
    TokenListObserver.prototype.tokenUnmatched = function (token) {
        this.delegate.tokenUnmatched(token);
        this.tokensByElement.delete(token.element, token);
    };
    TokenListObserver.prototype.refreshTokensForElement = function (element) {
        var previousTokens = this.tokensByElement.getValuesForKey(element);
        var currentTokens = this.readTokensForElement(element);
        var firstDifferingIndex = zip(previousTokens, currentTokens)
            .findIndex(function (_a) {
            var previousToken = _a[0], currentToken = _a[1];
            return !tokensAreEqual(previousToken, currentToken);
        });
        if (firstDifferingIndex == -1) {
            return [[], []];
        }
        else {
            return [previousTokens.slice(firstDifferingIndex), currentTokens.slice(firstDifferingIndex)];
        }
    };
    TokenListObserver.prototype.readTokensForElement = function (element) {
        var attributeName = this.attributeName;
        var tokenString = element.getAttribute(attributeName) || "";
        return parseTokenString(tokenString, element, attributeName);
    };
    return TokenListObserver;
}());

function parseTokenString(tokenString, element, attributeName) {
    return tokenString.trim().split(/\s+/).filter(function (content) { return content.length; })
        .map(function (content, index) { return ({ element: element, attributeName: attributeName, content: content, index: index }); });
}
function zip(left, right) {
    var length = Math.max(left.length, right.length);
    return Array.from({ length: length }, function (_, index) { return [left[index], right[index]]; });
}
function tokensAreEqual(left, right) {
    return left && right && left.index == right.index && left.content == right.content;
}
//# sourceMappingURL=token_list_observer.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/value_list_observer.js":
/*!**************************************************************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/value_list_observer.js ***!
  \**************************************************************************************************************/
/*! exports provided: ValueListObserver */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ValueListObserver", function() { return ValueListObserver; });
/* harmony import */ var _token_list_observer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./token_list_observer */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/mutation-observers/dist/token_list_observer.js");

var ValueListObserver = /** @class */ (function () {
    function ValueListObserver(element, attributeName, delegate) {
        this.tokenListObserver = new _token_list_observer__WEBPACK_IMPORTED_MODULE_0__["TokenListObserver"](element, attributeName, this);
        this.delegate = delegate;
        this.parseResultsByToken = new WeakMap;
        this.valuesByTokenByElement = new WeakMap;
    }
    Object.defineProperty(ValueListObserver.prototype, "started", {
        get: function () {
            return this.tokenListObserver.started;
        },
        enumerable: false,
        configurable: true
    });
    ValueListObserver.prototype.start = function () {
        this.tokenListObserver.start();
    };
    ValueListObserver.prototype.stop = function () {
        this.tokenListObserver.stop();
    };
    ValueListObserver.prototype.refresh = function () {
        this.tokenListObserver.refresh();
    };
    Object.defineProperty(ValueListObserver.prototype, "element", {
        get: function () {
            return this.tokenListObserver.element;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(ValueListObserver.prototype, "attributeName", {
        get: function () {
            return this.tokenListObserver.attributeName;
        },
        enumerable: false,
        configurable: true
    });
    ValueListObserver.prototype.tokenMatched = function (token) {
        var element = token.element;
        var value = this.fetchParseResultForToken(token).value;
        if (value) {
            this.fetchValuesByTokenForElement(element).set(token, value);
            this.delegate.elementMatchedValue(element, value);
        }
    };
    ValueListObserver.prototype.tokenUnmatched = function (token) {
        var element = token.element;
        var value = this.fetchParseResultForToken(token).value;
        if (value) {
            this.fetchValuesByTokenForElement(element).delete(token);
            this.delegate.elementUnmatchedValue(element, value);
        }
    };
    ValueListObserver.prototype.fetchParseResultForToken = function (token) {
        var parseResult = this.parseResultsByToken.get(token);
        if (!parseResult) {
            parseResult = this.parseToken(token);
            this.parseResultsByToken.set(token, parseResult);
        }
        return parseResult;
    };
    ValueListObserver.prototype.fetchValuesByTokenForElement = function (element) {
        var valuesByToken = this.valuesByTokenByElement.get(element);
        if (!valuesByToken) {
            valuesByToken = new Map;
            this.valuesByTokenByElement.set(element, valuesByToken);
        }
        return valuesByToken;
    };
    ValueListObserver.prototype.parseToken = function (token) {
        try {
            var value = this.delegate.parseValueForToken(token);
            return { value: value };
        }
        catch (error) {
            return { error: error };
        }
    };
    return ValueListObserver;
}());

//# sourceMappingURL=value_list_observer.js.map

/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/node_modules/stimulus/index.js":
/*!***********************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/node_modules/stimulus/index.js ***!
  \***********************************************************************/
/*! exports provided: Application, Context, Controller, defaultSchema */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _stimulus_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @stimulus/core */ "./node_modules/@symfony/ux-turbo/node_modules/@stimulus/core/dist/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Application", function() { return _stimulus_core__WEBPACK_IMPORTED_MODULE_0__["Application"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Context", function() { return _stimulus_core__WEBPACK_IMPORTED_MODULE_0__["Context"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Controller", function() { return _stimulus_core__WEBPACK_IMPORTED_MODULE_0__["Controller"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "defaultSchema", function() { return _stimulus_core__WEBPACK_IMPORTED_MODULE_0__["defaultSchema"]; });




/***/ }),

/***/ "./node_modules/@symfony/ux-turbo/src/controller.js":
/*!**********************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/src/controller.js ***!
  \**********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! stimulus */ "./node_modules/@symfony/ux-turbo/node_modules/stimulus/index.js");
/* harmony import */ var _hotwired_turbo__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @hotwired/turbo */ "./node_modules/@symfony/ux-turbo/node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js");



// TODO: allow to use polyfills for URL and EventSource
/* harmony default export */ __webpack_exports__["default"] = (class extends stimulus__WEBPACK_IMPORTED_MODULE_0__["Controller"] {
  initialize() {
    if (!this.element.id) {
      console.error(`The element must have an "id" attribute.`);
    }

    this.hub = this.element.getAttribute("data-hub");
    if (!this.hub) {
      console.error(`The element must have a "data-hub" attribute pointing to the Mercure hub.`);
    }

    const u = new URL(this.hub);
    u.searchParams.append("topic", this.element.id);

    this.url = u.toString();
  }

  connect() {
    this.es = new EventSource(this.url);
    Object(_hotwired_turbo__WEBPACK_IMPORTED_MODULE_1__["connectStreamSource"])(this.es);
  }

  disconnect() {
    this.es.close();
    Object(_hotwired_turbo__WEBPACK_IMPORTED_MODULE_1__["disconnectStreamSource"])(this.es);
  }
});


/***/ }),

/***/ "./node_modules/stimulus/index.js":
/*!****************************************!*\
  !*** ./node_modules/stimulus/index.js ***!
  \****************************************/
/*! exports provided: Application, Context, Controller, defaultSchema */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _stimulus_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @stimulus/core */ "./node_modules/@stimulus/core/dist/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Application", function() { return _stimulus_core__WEBPACK_IMPORTED_MODULE_0__["Application"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Context", function() { return _stimulus_core__WEBPACK_IMPORTED_MODULE_0__["Context"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Controller", function() { return _stimulus_core__WEBPACK_IMPORTED_MODULE_0__["Controller"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "defaultSchema", function() { return _stimulus_core__WEBPACK_IMPORTED_MODULE_0__["defaultSchema"]; });




/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9hY3Rpb24uanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvYWN0aW9uX2Rlc2NyaXB0b3IuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvYXBwbGljYXRpb24uanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvYmluZGluZy5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9iaW5kaW5nX29ic2VydmVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2JsZXNzaW5nLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2NsYXNzX21hcC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9jbGFzc19wcm9wZXJ0aWVzLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2NvbnRleHQuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvY29udHJvbGxlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9kYXRhX21hcC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9kZWZpbml0aW9uLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2Rpc3BhdGNoZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvZXZlbnRfbGlzdGVuZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvZ3VpZGUuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvaW5kZXguanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvaW5oZXJpdGFibGVfc3RhdGljcy5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9tb2R1bGUuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3Qvcm91dGVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L3NjaGVtYS5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9zY29wZS5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9zY29wZV9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9zZWxlY3RvcnMuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3Qvc3RyaW5nX2hlbHBlcnMuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvdGFyZ2V0X3Byb3BlcnRpZXMuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvdGFyZ2V0X3NldC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC92YWx1ZV9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC92YWx1ZV9wcm9wZXJ0aWVzLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXVsdGltYXAvZGlzdC9pbmRleC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211bHRpbWFwL2Rpc3QvaW5kZXhlZF9tdWx0aW1hcC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211bHRpbWFwL2Rpc3QvbXVsdGltYXAuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9tdWx0aW1hcC9kaXN0L3NldF9vcGVyYXRpb25zLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzL2Rpc3QvYXR0cmlidXRlX29ic2VydmVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzL2Rpc3QvZWxlbWVudF9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211dGF0aW9uLW9ic2VydmVycy9kaXN0L2luZGV4LmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzL2Rpc3Qvc3RyaW5nX21hcF9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211dGF0aW9uLW9ic2VydmVycy9kaXN0L3Rva2VuX2xpc3Rfb2JzZXJ2ZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9tdXRhdGlvbi1vYnNlcnZlcnMvZGlzdC92YWx1ZV9saXN0X29ic2VydmVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQGhvdHdpcmVkL3R1cmJvL2Rpc3QvdHVyYm8uZXMyMDE3LWVzbS5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvYWN0aW9uLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9hY3Rpb25fZGVzY3JpcHRvci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvYXBwbGljYXRpb24uanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2JpbmRpbmcuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2JpbmRpbmdfb2JzZXJ2ZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2JsZXNzaW5nLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9jbGFzc19tYXAuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2NsYXNzX3Byb3BlcnRpZXMuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2NvbnRleHQuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2NvbnRyb2xsZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2RhdGFfbWFwLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9kZWZpbml0aW9uLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9kaXNwYXRjaGVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9ldmVudF9saXN0ZW5lci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvZ3VpZGUuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2luZGV4LmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9pbmhlcml0YWJsZV9zdGF0aWNzLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9tb2R1bGUuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L3JvdXRlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3Qvc2NoZW1hLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9zY29wZS5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3Qvc2NvcGVfb2JzZXJ2ZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L3NlbGVjdG9ycy5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3Qvc3RyaW5nX2hlbHBlcnMuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L3RhcmdldF9wcm9wZXJ0aWVzLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC90YXJnZXRfc2V0LmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC92YWx1ZV9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvdmFsdWVfcHJvcGVydGllcy5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9tdWx0aW1hcC9kaXN0L2luZGV4LmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211bHRpbWFwL2Rpc3QvaW5kZXhlZF9tdWx0aW1hcC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9tdWx0aW1hcC9kaXN0L211bHRpbWFwLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211bHRpbWFwL2Rpc3Qvc2V0X29wZXJhdGlvbnMuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzL2Rpc3QvYXR0cmlidXRlX29ic2VydmVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211dGF0aW9uLW9ic2VydmVycy9kaXN0L2VsZW1lbnRfb2JzZXJ2ZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzL2Rpc3QvaW5kZXguanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzL2Rpc3Qvc3RyaW5nX21hcF9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9tdXRhdGlvbi1vYnNlcnZlcnMvZGlzdC90b2tlbl9saXN0X29ic2VydmVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211dGF0aW9uLW9ic2VydmVycy9kaXN0L3ZhbHVlX2xpc3Rfb2JzZXJ2ZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3V4LXR1cmJvL25vZGVfbW9kdWxlcy9zdGltdWx1cy9pbmRleC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvdXgtdHVyYm8vc3JjL2NvbnRyb2xsZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL3N0aW11bHVzL2luZGV4LmpzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUF3RjtBQUN4RjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esb0RBQW9ELHNGQUEyQjtBQUMvRTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG1CQUFtQiwrRUFBb0I7QUFDdkMsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQSxDQUFDO0FBQ2lCO0FBQ2xCO0FBQ0EsdUJBQXVCLGdCQUFnQixFQUFFO0FBQ3pDLDRCQUE0QixnQkFBZ0IsRUFBRTtBQUM5QywwQkFBMEIsaUJBQWlCLEVBQUU7QUFDN0MsMkJBQTJCLCtEQUErRCxFQUFFO0FBQzVGLDRCQUE0QixpQkFBaUIsRUFBRTtBQUMvQyw4QkFBOEIsZ0JBQWdCO0FBQzlDO0FBQ087QUFDUDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esa0M7Ozs7Ozs7Ozs7OztBQzdDQTtBQUFBO0FBQUE7QUFBQTtBQUNBO0FBQ087QUFDUDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EscUVBQXFFO0FBQ3JFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDhDQUE4QztBQUM5QyxLQUFLLElBQUk7QUFDVDtBQUNPO0FBQ1A7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSw2Qzs7Ozs7Ozs7Ozs7O0FDbkNBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQSxpQkFBaUIsU0FBSSxJQUFJLFNBQUk7QUFDN0IsMkJBQTJCLCtEQUErRCxnQkFBZ0IsRUFBRSxFQUFFO0FBQzlHO0FBQ0EsbUNBQW1DLE1BQU0sNkJBQTZCLEVBQUUsWUFBWSxXQUFXLEVBQUU7QUFDakcsa0NBQWtDLE1BQU0saUNBQWlDLEVBQUUsWUFBWSxXQUFXLEVBQUU7QUFDcEcsK0JBQStCLHFGQUFxRjtBQUNwSDtBQUNBLEtBQUs7QUFDTDtBQUNBLG1CQUFtQixTQUFJLElBQUksU0FBSTtBQUMvQixhQUFhLDZCQUE2QiwwQkFBMEIsYUFBYSxFQUFFLHFCQUFxQjtBQUN4RyxnQkFBZ0IscURBQXFELG9FQUFvRSxhQUFhLEVBQUU7QUFDeEosc0JBQXNCLHNCQUFzQixxQkFBcUIsR0FBRztBQUNwRTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1Q0FBdUM7QUFDdkMsa0NBQWtDLFNBQVM7QUFDM0Msa0NBQWtDLFdBQVcsVUFBVTtBQUN2RCx5Q0FBeUMsY0FBYztBQUN2RDtBQUNBLDZHQUE2RyxPQUFPLFVBQVU7QUFDOUgsZ0ZBQWdGLGlCQUFpQixPQUFPO0FBQ3hHLHdEQUF3RCxnQkFBZ0IsUUFBUSxPQUFPO0FBQ3ZGLDhDQUE4QyxnQkFBZ0IsZ0JBQWdCLE9BQU87QUFDckY7QUFDQSxpQ0FBaUM7QUFDakM7QUFDQTtBQUNBLFNBQVMsWUFBWSxhQUFhLE9BQU8sRUFBRSxVQUFVLFdBQVc7QUFDaEUsbUNBQW1DLFNBQVM7QUFDNUM7QUFDQTtBQUNBLHNCQUFzQixTQUFJLElBQUksU0FBSTtBQUNsQyxpREFBaUQsUUFBUTtBQUN6RCx3Q0FBd0MsUUFBUTtBQUNoRCx3REFBd0QsUUFBUTtBQUNoRTtBQUNBO0FBQ0E7QUFDMEM7QUFDUjtBQUNPO0FBQ3pDO0FBQ0E7QUFDQSxpQ0FBaUMsb0NBQW9DO0FBQ3JFLGdDQUFnQyxVQUFVLHFEQUFhLENBQUM7QUFDeEQ7QUFDQTtBQUNBO0FBQ0EsOEJBQThCLHNEQUFVO0FBQ3hDLDBCQUEwQiw4Q0FBTTtBQUNoQztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsYUFBYTtBQUNiLFNBQVM7QUFDVDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxtQkFBbUIsdUVBQXVFO0FBQzFGO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esd0JBQXdCLHVCQUF1QjtBQUMvQztBQUNBO0FBQ0E7QUFDQSxtREFBbUQsZ0RBQWdELEVBQUU7QUFDckc7QUFDQTtBQUNBO0FBQ0E7QUFDQSx3QkFBd0IsdUJBQXVCO0FBQy9DO0FBQ0E7QUFDQTtBQUNBLG1EQUFtRCxrREFBa0QsRUFBRTtBQUN2RztBQUNBO0FBQ0E7QUFDQTtBQUNBLGdFQUFnRSwyQkFBMkIsRUFBRTtBQUM3RixTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxDQUFDO0FBQ3NCO0FBQ3ZCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQSx1Qzs7Ozs7Ozs7Ozs7O0FDaklBO0FBQUE7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsMEJBQTBCO0FBQzFCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0EsQ0FBQztBQUNrQjtBQUNuQixtQzs7Ozs7Ozs7Ozs7O0FDN0dBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBa0M7QUFDRTtBQUM2QjtBQUNqRTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EseUNBQXlDLDhFQUFpQjtBQUMxRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0EsMEJBQTBCLGdEQUFPO0FBQ2pDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGtEQUFrRCxvREFBb0QsRUFBRTtBQUN4RztBQUNBO0FBQ0E7QUFDQTtBQUNBLHFCQUFxQiw4Q0FBTTtBQUMzQjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsQ0FBQztBQUMwQjtBQUMzQiw0Qzs7Ozs7Ozs7Ozs7O0FDMUZBO0FBQUE7QUFBQTtBQUFBLGlCQUFpQixTQUFJLElBQUksU0FBSTtBQUM3QjtBQUNBO0FBQ0EsY0FBYyxnQkFBZ0Isc0NBQXNDLGlCQUFpQixFQUFFO0FBQ3ZGLDZCQUE2Qix1REFBdUQ7QUFDcEY7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1QkFBdUIsc0JBQXNCO0FBQzdDO0FBQ0E7QUFDQSxDQUFDO0FBQ0Qsc0JBQXNCLFNBQUksSUFBSSxTQUFJO0FBQ2xDLGlEQUFpRCxRQUFRO0FBQ3pELHdDQUF3QyxRQUFRO0FBQ2hELHdEQUF3RCxRQUFRO0FBQ2hFO0FBQ0E7QUFDQTtBQUN5RTtBQUN6RTtBQUNPO0FBQ1A7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esb0JBQW9CLDZGQUFnQztBQUNwRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLEtBQUssSUFBSTtBQUNUO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG9EQUFvRDtBQUNwRDtBQUNBO0FBQ0EsS0FBSyxJQUFJO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esa0NBQWtDLGlHQUFpRztBQUNuSTtBQUNBO0FBQ0E7QUFDQTtBQUNBLENBQUM7QUFDRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDBCQUEwQjtBQUMxQixTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQSw2QkFBNkIsbUJBQW1CO0FBQ2hEO0FBQ0EscUNBQXFDO0FBQ3JDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsdUNBQXVDO0FBQ3ZDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTLGVBQWU7QUFDeEI7QUFDQSxDQUFDO0FBQ0Qsb0M7Ozs7Ozs7Ozs7OztBQ3hHQTtBQUFBO0FBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBLENBQUM7QUFDbUI7QUFDcEIscUM7Ozs7Ozs7Ozs7OztBQzFCQTtBQUFBO0FBQUE7QUFBQTtBQUF5RTtBQUMzQjtBQUM5QztBQUNPO0FBQ1Asa0JBQWtCLDZGQUFnQztBQUNsRDtBQUNBO0FBQ0EsS0FBSyxJQUFJO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQSxrQkFBa0I7QUFDbEI7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVCxtQkFBbUIsa0VBQVU7QUFDN0I7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSw0Qzs7Ozs7Ozs7Ozs7O0FDaENBO0FBQUE7QUFBQTtBQUFBO0FBQXFEO0FBQ0o7QUFDakQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG1DQUFtQyxpRUFBZTtBQUNsRCxpQ0FBaUMsNkRBQWE7QUFDOUM7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBLGdDQUFnQyxhQUFhO0FBQzdDO0FBQ0EsZ0NBQWdDLG1FQUFtRTtBQUNuRztBQUNBO0FBQ0E7QUFDQSxDQUFDO0FBQ2tCO0FBQ25CLG1DOzs7Ozs7Ozs7Ozs7QUN4RkE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUE2RDtBQUNFO0FBQ0Y7QUFDN0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSw0QkFBNEIseUVBQXVCLEVBQUUsMkVBQXdCLEVBQUUseUVBQXVCO0FBQ3RHO0FBQ0E7QUFDQTtBQUNBLENBQUM7QUFDcUI7QUFDdEIsc0M7Ozs7Ozs7Ozs7OztBQ3ZFQTtBQUFBO0FBQUE7QUFBNkM7QUFDN0M7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxpREFBaUQsaUVBQVM7QUFDMUQ7QUFDQTtBQUNBLENBQUM7QUFDa0I7QUFDbkIsb0M7Ozs7Ozs7Ozs7OztBQ2hEQTtBQUFBO0FBQUE7QUFBbUM7QUFDbkM7QUFDTztBQUNQO0FBQ0E7QUFDQSwrQkFBK0IsdURBQUs7QUFDcEM7QUFDQTtBQUNBLHNDOzs7Ozs7Ozs7Ozs7QUNSQTtBQUFBO0FBQUE7QUFBaUQ7QUFDakQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esa0VBQWtFLGdDQUFnQyxFQUFFO0FBQ3BHO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxrRUFBa0UsbUNBQW1DLEVBQUU7QUFDdkc7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG1EQUFtRCxtREFBbUQsRUFBRTtBQUN4RyxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0NBQWdDLGFBQWE7QUFDN0M7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGdDQUFnQyw2REFBYTtBQUM3QztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQTtBQUNBLENBQUM7QUFDcUI7QUFDdEIsc0M7Ozs7Ozs7Ozs7OztBQ2hGQTtBQUFBO0FBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsNENBQTRDLGdCQUFnQjtBQUM1RDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsYUFBYTtBQUNiLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0EsQ0FBQztBQUN3QjtBQUN6QjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLDBDOzs7Ozs7Ozs7Ozs7QUM5REE7QUFBQTtBQUFBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxDQUFDO0FBQ2dCO0FBQ2pCLGlDOzs7Ozs7Ozs7Ozs7QUNuQkE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBNEM7QUFDUjtBQUNNO0FBQ0Q7QUFDekMsaUM7Ozs7Ozs7Ozs7OztBQ0pBO0FBQUE7QUFBQTtBQUFPO0FBQ1A7QUFDQTtBQUNBLG9GQUFvRix5QkFBeUIsRUFBRTtBQUMvRztBQUNBLEtBQUs7QUFDTDtBQUNPO0FBQ1A7QUFDQTtBQUNBO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esb0VBQW9FLCtCQUErQixFQUFFO0FBQ3JHO0FBQ0EsK0M7Ozs7Ozs7Ozs7OztBQzlCQTtBQUFBO0FBQUE7QUFBQTtBQUFvQztBQUNXO0FBQy9DO0FBQ0E7QUFDQTtBQUNBLDBCQUEwQixtRUFBZTtBQUN6QztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSwwQkFBMEIsZ0RBQU87QUFDakM7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLENBQUM7QUFDaUI7QUFDbEIsa0M7Ozs7Ozs7Ozs7OztBQ3JEQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBa0M7QUFDWTtBQUNkO0FBQ2lCO0FBQ2pEO0FBQ0E7QUFDQTtBQUNBLGlDQUFpQyw2REFBYTtBQUM5QyxzQ0FBc0MsMkRBQVE7QUFDOUM7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQSxvRUFBb0UseUNBQXlDLEVBQUU7QUFDL0csU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHlCQUF5Qiw4Q0FBTTtBQUMvQjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsNERBQTRELG1DQUFtQyxFQUFFO0FBQ2pHO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsbUJBQW1CLDRDQUFLO0FBQ3hCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHlDQUF5Qyw2Q0FBNkMsRUFBRTtBQUN4RjtBQUNBO0FBQ0E7QUFDQTtBQUNBLHlDQUF5QyxnREFBZ0QsRUFBRTtBQUMzRjtBQUNBO0FBQ0EsQ0FBQztBQUNpQjtBQUNsQixrQzs7Ozs7Ozs7Ozs7O0FDcEhBO0FBQUE7QUFBTztBQUNQO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esa0M7Ozs7Ozs7Ozs7OztBQ0xBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUEsc0JBQXNCLFNBQUksSUFBSSxTQUFJO0FBQ2xDLGlEQUFpRCxRQUFRO0FBQ3pELHdDQUF3QyxRQUFRO0FBQ2hELHdEQUF3RCxRQUFRO0FBQ2hFO0FBQ0E7QUFDQTtBQUN1QztBQUNGO0FBQ0w7QUFDMEI7QUFDakI7QUFDekM7QUFDQTtBQUNBO0FBQ0EsMkJBQTJCLHFEQUFTO0FBQ3BDLDJCQUEyQixtREFBUTtBQUNuQyx3QkFBd0IsaURBQU87QUFDL0I7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EseUJBQXlCLDRDQUFLO0FBQzlCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxtQkFBbUIsOEVBQTJCO0FBQzlDLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0EsQ0FBQztBQUNnQjtBQUNqQixpQzs7Ozs7Ozs7Ozs7O0FDL0NBO0FBQUE7QUFBQTtBQUFpRTtBQUNqRTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EscUNBQXFDLDhFQUFpQjtBQUN0RDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxDQUFDO0FBQ3dCO0FBQ3pCLDBDOzs7Ozs7Ozs7Ozs7QUNoRUE7QUFBQTtBQUFBO0FBQ087QUFDUDtBQUNBO0FBQ0EscUM7Ozs7Ozs7Ozs7OztBQ0pBO0FBQUE7QUFBQTtBQUFBO0FBQU87QUFDUCxvRUFBb0UsMkJBQTJCLEVBQUU7QUFDakc7QUFDTztBQUNQO0FBQ0E7QUFDTztBQUNQLHlEQUF5RCxpQ0FBaUMsRUFBRTtBQUM1RjtBQUNBLDBDOzs7Ozs7Ozs7Ozs7QUNUQTtBQUFBO0FBQUE7QUFBQTtBQUF5RTtBQUMzQjtBQUM5QztBQUNPO0FBQ1Asa0JBQWtCLDZGQUFnQztBQUNsRDtBQUNBO0FBQ0EsS0FBSyxJQUFJO0FBQ1Q7QUFDQTtBQUNBO0FBQ0Esa0JBQWtCO0FBQ2xCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNULG1CQUFtQixrRUFBVTtBQUM3QjtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLDZDOzs7Ozs7Ozs7Ozs7QUNuQ0E7QUFBQTtBQUFBO0FBQUEsc0JBQXNCLFNBQUksSUFBSSxTQUFJO0FBQ2xDLGlEQUFpRCxRQUFRO0FBQ3pELHdDQUF3QyxRQUFRO0FBQ2hELHdEQUF3RCxRQUFRO0FBQ2hFO0FBQ0E7QUFDQTtBQUMwRDtBQUMxRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHdCQUF3Qix1QkFBdUI7QUFDL0M7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esd0JBQXdCLHVCQUF1QjtBQUMvQztBQUNBO0FBQ0Esa0VBQWtFLDBHQUEwRyxFQUFFO0FBQzlLO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxlQUFlLDhFQUEyQjtBQUMxQztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsNEVBQTRFLDZDQUE2QyxFQUFFO0FBQzNIO0FBQ0E7QUFDQTtBQUNBLGVBQWUsOEVBQTJCO0FBQzFDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQSxDQUFDO0FBQ29CO0FBQ3JCLHNDOzs7Ozs7Ozs7Ozs7QUNwR0E7QUFBQTtBQUFBO0FBQWlFO0FBQ2pFO0FBQ0E7QUFDQTtBQUNBO0FBQ0EscUNBQXFDLDhFQUFpQjtBQUN0RDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG9EQUFvRCxnQkFBZ0I7QUFDcEU7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHVFQUF1RSxnQ0FBZ0MsRUFBRTtBQUN6RyxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBLENBQUM7QUFDd0I7QUFDekIsMEM7Ozs7Ozs7Ozs7OztBQ2pFQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQXlFO0FBQ047QUFDbkU7QUFDTztBQUNQLCtCQUErQiw2RkFBZ0M7QUFDL0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHlEQUF5RDtBQUN6RCxpQkFBaUIsSUFBSTtBQUNyQjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDTztBQUNQO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esa0JBQWtCO0FBQ2xCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGFBQWE7QUFDYjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNULG1CQUFtQixrRUFBVTtBQUM3QjtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsY0FBYyxpRUFBUztBQUN2QjtBQUNBO0FBQ0E7QUFDQSxjQUFjLGdFQUFRO0FBQ3RCLDRCQUE0QixrQ0FBa0M7QUFDOUQ7QUFDQTtBQUNBO0FBQ0EsaUJBQWlCLFdBQVcsRUFBRTtBQUM5QjtBQUNBO0FBQ0Esa0JBQWtCLFdBQVcsRUFBRTtBQUMvQjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSw0Qzs7Ozs7Ozs7Ozs7O0FDMUhBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQW1DO0FBQ1I7QUFDTTtBQUNqQyxpQzs7Ozs7Ozs7Ozs7O0FDSEE7QUFBQTtBQUFBO0FBQUE7QUFBQSxpQkFBaUIsU0FBSSxJQUFJLFNBQUk7QUFDN0I7QUFDQTtBQUNBLGNBQWMsZ0JBQWdCLHNDQUFzQyxpQkFBaUIsRUFBRTtBQUN2Riw2QkFBNkIsdURBQXVEO0FBQ3BGO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsdUJBQXVCLHNCQUFzQjtBQUM3QztBQUNBO0FBQ0EsQ0FBQztBQUNxQztBQUNNO0FBQzVDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBLFFBQVEsMkRBQUc7QUFDWDtBQUNBO0FBQ0E7QUFDQSxRQUFRLDJEQUFHO0FBQ1g7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsQ0FBQyxDQUFDLGtEQUFRO0FBQ2lCO0FBQzNCLDRDOzs7Ozs7Ozs7Ozs7QUMvQ0E7QUFBQTtBQUFBO0FBQTRDO0FBQzVDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsdURBQXVELHVDQUF1QyxFQUFFO0FBQ2hHLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLHFEQUFxRCx3QkFBd0IsRUFBRTtBQUMvRSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBLFFBQVEsMkRBQUc7QUFDWDtBQUNBO0FBQ0EsUUFBUSwyREFBRztBQUNYO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EseUNBQXlDLHVCQUF1QixFQUFFO0FBQ2xFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsQ0FBQztBQUNtQjtBQUNwQixvQzs7Ozs7Ozs7Ozs7O0FDeERBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBTztBQUNQO0FBQ0E7QUFDTztBQUNQO0FBQ0E7QUFDQTtBQUNPO0FBQ1A7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDTztBQUNQO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSwwQzs7Ozs7Ozs7Ozs7O0FDckJBO0FBQUE7QUFBQTtBQUFxRDtBQUNyRDtBQUNBO0FBQ0E7QUFDQTtBQUNBLG1DQUFtQyxpRUFBZTtBQUNsRDtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsQ0FBQztBQUM0QjtBQUM3Qiw4Qzs7Ozs7Ozs7Ozs7O0FDaEVBO0FBQUE7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDJFQUEyRSwwQ0FBMEMsRUFBRTtBQUN2SDtBQUNBO0FBQ0E7QUFDQTtBQUNBLHlEQUF5RCxtREFBbUQ7QUFDNUc7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSw0REFBNEQsZ0JBQWdCO0FBQzVFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxzREFBc0QsZ0JBQWdCO0FBQ3RFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxxREFBcUQseUJBQXlCO0FBQzlFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxnREFBZ0QsZ0JBQWdCO0FBQ2hFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxnREFBZ0QsZ0JBQWdCO0FBQ2hFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDhCQUE4QixxQkFBcUI7QUFDbkQ7QUFDQTtBQUNBO0FBQ0EsNkRBQTZELGdCQUFnQjtBQUM3RTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLENBQUM7QUFDMEI7QUFDM0IsNEM7Ozs7Ozs7Ozs7OztBQ3pJQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFxQztBQUNGO0FBQ0c7QUFDQTtBQUNBO0FBQ3RDLGlDOzs7Ozs7Ozs7Ozs7QUNMQTtBQUFBO0FBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSwyRUFBMkUsMENBQTBDLEVBQUU7QUFDdkg7QUFDQTtBQUNBO0FBQ0E7QUFDQSx5REFBeUQsbUJBQW1CO0FBQzVFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDJEQUEyRCxnQkFBZ0I7QUFDM0U7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHFEQUFxRCx5QkFBeUI7QUFDOUU7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0EsaUZBQWlGLHVCQUF1QixFQUFFO0FBQzFHLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0EsQ0FBQztBQUM0QjtBQUM3QiwrQzs7Ozs7Ozs7Ozs7O0FDekdBO0FBQUE7QUFBQTtBQUFBO0FBQXlEO0FBQ1g7QUFDOUM7QUFDQTtBQUNBLHFDQUFxQyxxRUFBaUI7QUFDdEQ7QUFDQSxtQ0FBbUMsMkRBQVE7QUFDM0M7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EseUNBQXlDLGtDQUFrQyxFQUFFO0FBQzdFO0FBQ0E7QUFDQTtBQUNBLHlDQUF5QyxvQ0FBb0MsRUFBRTtBQUMvRTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLENBQUM7QUFDNEI7QUFDN0I7QUFDQSxzRUFBc0UsdUJBQXVCLEVBQUU7QUFDL0Ysd0NBQXdDLFVBQVUsaUZBQWlGLEVBQUUsRUFBRTtBQUN2STtBQUNBO0FBQ0E7QUFDQSx1QkFBdUIsaUJBQWlCLHVCQUF1QixvQ0FBb0MsRUFBRTtBQUNyRztBQUNBO0FBQ0E7QUFDQTtBQUNBLCtDOzs7Ozs7Ozs7Ozs7QUNwR0E7QUFBQTtBQUFBO0FBQTBEO0FBQzFEO0FBQ0E7QUFDQSxxQ0FBcUMsc0VBQWlCO0FBQ3REO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG9CQUFvQjtBQUNwQjtBQUNBO0FBQ0Esb0JBQW9CO0FBQ3BCO0FBQ0E7QUFDQTtBQUNBLENBQUM7QUFDNEI7QUFDN0IsK0M7Ozs7Ozs7Ozs7OztBQ2xGQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxDQUFDOztBQUVEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMLENBQUM7O0FBRUQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHVEQUF1RDtBQUN2RDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUEsOEJBQThCLDZCQUE2QixLQUFLO0FBQ2hFLDhDQUE4QyxvQ0FBb0M7QUFDbEY7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBLDhCQUE4QixhQUFhO0FBQzNDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLEtBQUs7QUFDTDs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLENBQUMsa0NBQWtDO0FBQ25DO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxlQUFlLGVBQWU7QUFDOUIsZ0RBQWdELFVBQVUsZUFBZSxFQUFFO0FBQzNFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLCtEQUErRCw0QkFBNEIsZ0JBQWdCLEVBQUU7QUFDN0c7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSw4QkFBOEIsK0NBQStDO0FBQzdFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsQ0FBQyxrREFBa0Q7QUFDbkQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGVBQWUsMEJBQTBCO0FBQ3pDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGVBQWUsb0JBQW9CO0FBQ25DO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHdDQUF3QyxvQ0FBb0MsdUJBQXVCLEVBQUU7QUFDckc7QUFDQTtBQUNBO0FBQ0EsdUJBQXVCO0FBQ3ZCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSwyQkFBMkI7QUFDM0I7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1QkFBdUI7QUFDdkI7QUFDQTtBQUNBO0FBQ0EsdUJBQXVCO0FBQ3ZCO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esc0NBQXNDLGtEQUFrRCx1QkFBdUIsZ0JBQWdCO0FBQy9IO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxrRUFBa0U7QUFDbEU7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHlEQUF5RCxLQUFLO0FBQzlEO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBLDBDQUEwQztBQUMxQztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esa0RBQWtEO0FBQ2xEO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsZ0JBQWdCO0FBQ2hCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDZFQUE2RSxHQUFHO0FBQ2hGO0FBQ0E7QUFDQSwyRkFBMkYsR0FBRztBQUM5RjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHdDQUF3QyxRQUFRO0FBQ2hEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsbURBQW1ELDRCQUE0QjtBQUMvRTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxvQkFBb0I7QUFDcEI7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSwyQkFBMkIsaUJBQWlCLElBQUksUUFBUTtBQUN4RDtBQUNBO0FBQ0E7QUFD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O0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHlEQUF5RCxHQUFHO0FBQzVEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsdUJBQXVCLFFBQVE7QUFDL0I7QUFDQTtBQUNBO0FBQ0EsMkJBQTJCLHdCQUF3QjtBQUNuRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHVCQUF1QixTQUFTLHdCQUF3QjtBQUN4RDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZUFBZSx3QkFBd0I7QUFDdkM7QUFDQSxvRkFBb0Y7QUFDcEY7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1Q0FBdUM7QUFDdkM7QUFDQTtBQUNBO0FBQ0E7QUFDQSw0REFBNEQ7QUFDNUQ7QUFDQSwyR0FBMkcsMEJBQTBCO0FBQ3JJO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsdUJBQXVCLGFBQWE7QUFDcEMsc0NBQXNDLFlBQVksMkJBQTJCO0FBQzdFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxDQUFDLDhCQUE4QjtBQUMvQjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsbUJBQW1CLGFBQWE7QUFDaEM7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGlDQUFpQywrQ0FBK0M7QUFDaEY7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1QkFBdUIsVUFBVTtBQUNqQyw2Q0FBNkM7QUFDN0M7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1QkFBdUI7QUFDdkI7O0FBRUE7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxnQkFBZ0IsY0FBYztBQUM5QjtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxlQUFlLDhCQUE4QjtBQUM3QztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxhQUFhO0FBQ2I7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFD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UztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsMEJBQTBCO0FBQzFCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0EsQ0FBQztBQUNrQjtBQUNuQixtQzs7Ozs7Ozs7Ozs7O0FDN0dBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBa0M7QUFDRTtBQUM2QjtBQUNqRTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EseUNBQXlDLDhFQUFpQjtBQUMxRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0EsMEJBQTBCLGdEQUFPO0FB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OztBQ0E5QjtBQUFBO0FBQUE7QUFBc0M7QUFDd0M7O0FBRTlFO0FBQ2UsNkVBQWMsbURBQVU7QUFDdkM7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTs7QUFFQTtBQUNBOztBQUVBO0FBQ0E7QUFDQSxJQUFJLDJFQUFtQjtBQUN2Qjs7QUFFQTtBQUNBO0FBQ0EsSUFBSSw4RUFBc0I7QUFDMUI7QUFDQSxDQUFDOzs7Ozs7Ozs7Ozs7O0FDOUJEO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQThCIiwiZmlsZSI6InZlbmRvcnN+YXBwLmpzIiwic291cmNlc0NvbnRlbnQiOlsiaW1wb3J0IHsgcGFyc2VBY3Rpb25EZXNjcmlwdG9yU3RyaW5nLCBzdHJpbmdpZnlFdmVudFRhcmdldCB9IGZyb20gXCIuL2FjdGlvbl9kZXNjcmlwdG9yXCI7XG52YXIgQWN0aW9uID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIEFjdGlvbihlbGVtZW50LCBpbmRleCwgZGVzY3JpcHRvcikge1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBlbGVtZW50O1xuICAgICAgICB0aGlzLmluZGV4ID0gaW5kZXg7XG4gICAgICAgIHRoaXMuZXZlbnRUYXJnZXQgPSBkZXNjcmlwdG9yLmV2ZW50VGFyZ2V0IHx8IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuZXZlbnROYW1lID0gZGVzY3JpcHRvci5ldmVudE5hbWUgfHwgZ2V0RGVmYXVsdEV2ZW50TmFtZUZvckVsZW1lbnQoZWxlbWVudCkgfHwgZXJyb3IoXCJtaXNzaW5nIGV2ZW50IG5hbWVcIik7XG4gICAgICAgIHRoaXMuZXZlbnRPcHRpb25zID0gZGVzY3JpcHRvci5ldmVudE9wdGlvbnMgfHwge307XG4gICAgICAgIHRoaXMuaWRlbnRpZmllciA9IGRlc2NyaXB0b3IuaWRlbnRpZmllciB8fCBlcnJvcihcIm1pc3NpbmcgaWRlbnRpZmllclwiKTtcbiAgICAgICAgdGhpcy5tZXRob2ROYW1lID0gZGVzY3JpcHRvci5tZXRob2ROYW1lIHx8IGVycm9yKFwibWlzc2luZyBtZXRob2QgbmFtZVwiKTtcbiAgICB9XG4gICAgQWN0aW9uLmZvclRva2VuID0gZnVuY3Rpb24gKHRva2VuKSB7XG4gICAgICAgIHJldHVybiBuZXcgdGhpcyh0b2tlbi5lbGVtZW50LCB0b2tlbi5pbmRleCwgcGFyc2VBY3Rpb25EZXNjcmlwdG9yU3RyaW5nKHRva2VuLmNvbnRlbnQpKTtcbiAgICB9O1xuICAgIEFjdGlvbi5wcm90b3R5cGUudG9TdHJpbmcgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHZhciBldmVudE5hbWVTdWZmaXggPSB0aGlzLmV2ZW50VGFyZ2V0TmFtZSA/IFwiQFwiICsgdGhpcy5ldmVudFRhcmdldE5hbWUgOiBcIlwiO1xuICAgICAgICByZXR1cm4gXCJcIiArIHRoaXMuZXZlbnROYW1lICsgZXZlbnROYW1lU3VmZml4ICsgXCItPlwiICsgdGhpcy5pZGVudGlmaWVyICsgXCIjXCIgKyB0aGlzLm1ldGhvZE5hbWU7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQWN0aW9uLnByb3RvdHlwZSwgXCJldmVudFRhcmdldE5hbWVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBzdHJpbmdpZnlFdmVudFRhcmdldCh0aGlzLmV2ZW50VGFyZ2V0KTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIHJldHVybiBBY3Rpb247XG59KCkpO1xuZXhwb3J0IHsgQWN0aW9uIH07XG52YXIgZGVmYXVsdEV2ZW50TmFtZXMgPSB7XG4gICAgXCJhXCI6IGZ1bmN0aW9uIChlKSB7IHJldHVybiBcImNsaWNrXCI7IH0sXG4gICAgXCJidXR0b25cIjogZnVuY3Rpb24gKGUpIHsgcmV0dXJuIFwiY2xpY2tcIjsgfSxcbiAgICBcImZvcm1cIjogZnVuY3Rpb24gKGUpIHsgcmV0dXJuIFwic3VibWl0XCI7IH0sXG4gICAgXCJpbnB1dFwiOiBmdW5jdGlvbiAoZSkgeyByZXR1cm4gZS5nZXRBdHRyaWJ1dGUoXCJ0eXBlXCIpID09IFwic3VibWl0XCIgPyBcImNsaWNrXCIgOiBcImlucHV0XCI7IH0sXG4gICAgXCJzZWxlY3RcIjogZnVuY3Rpb24gKGUpIHsgcmV0dXJuIFwiY2hhbmdlXCI7IH0sXG4gICAgXCJ0ZXh0YXJlYVwiOiBmdW5jdGlvbiAoZSkgeyByZXR1cm4gXCJpbnB1dFwiOyB9XG59O1xuZXhwb3J0IGZ1bmN0aW9uIGdldERlZmF1bHRFdmVudE5hbWVGb3JFbGVtZW50KGVsZW1lbnQpIHtcbiAgICB2YXIgdGFnTmFtZSA9IGVsZW1lbnQudGFnTmFtZS50b0xvd2VyQ2FzZSgpO1xuICAgIGlmICh0YWdOYW1lIGluIGRlZmF1bHRFdmVudE5hbWVzKSB7XG4gICAgICAgIHJldHVybiBkZWZhdWx0RXZlbnROYW1lc1t0YWdOYW1lXShlbGVtZW50KTtcbiAgICB9XG59XG5mdW5jdGlvbiBlcnJvcihtZXNzYWdlKSB7XG4gICAgdGhyb3cgbmV3IEVycm9yKG1lc3NhZ2UpO1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9YWN0aW9uLmpzLm1hcCIsIi8vIGNhcHR1cmUgbm9zLjogICAgICAgICAgICAxMiAgIDIzIDQgICAgICAgICAgICAgICA0MyAgIDEgNSAgIDU2IDcgICAgICA3NjggOSAgOThcbnZhciBkZXNjcmlwdG9yUGF0dGVybiA9IC9eKCguKz8pKEAod2luZG93fGRvY3VtZW50KSk/LT4pPyguKz8pKCMoW146XSs/KSkoOiguKykpPyQvO1xuZXhwb3J0IGZ1bmN0aW9uIHBhcnNlQWN0aW9uRGVzY3JpcHRvclN0cmluZyhkZXNjcmlwdG9yU3RyaW5nKSB7XG4gICAgdmFyIHNvdXJjZSA9IGRlc2NyaXB0b3JTdHJpbmcudHJpbSgpO1xuICAgIHZhciBtYXRjaGVzID0gc291cmNlLm1hdGNoKGRlc2NyaXB0b3JQYXR0ZXJuKSB8fCBbXTtcbiAgICByZXR1cm4ge1xuICAgICAgICBldmVudFRhcmdldDogcGFyc2VFdmVudFRhcmdldChtYXRjaGVzWzRdKSxcbiAgICAgICAgZXZlbnROYW1lOiBtYXRjaGVzWzJdLFxuICAgICAgICBldmVudE9wdGlvbnM6IG1hdGNoZXNbOV0gPyBwYXJzZUV2ZW50T3B0aW9ucyhtYXRjaGVzWzldKSA6IHt9LFxuICAgICAgICBpZGVudGlmaWVyOiBtYXRjaGVzWzVdLFxuICAgICAgICBtZXRob2ROYW1lOiBtYXRjaGVzWzddXG4gICAgfTtcbn1cbmZ1bmN0aW9uIHBhcnNlRXZlbnRUYXJnZXQoZXZlbnRUYXJnZXROYW1lKSB7XG4gICAgaWYgKGV2ZW50VGFyZ2V0TmFtZSA9PSBcIndpbmRvd1wiKSB7XG4gICAgICAgIHJldHVybiB3aW5kb3c7XG4gICAgfVxuICAgIGVsc2UgaWYgKGV2ZW50VGFyZ2V0TmFtZSA9PSBcImRvY3VtZW50XCIpIHtcbiAgICAgICAgcmV0dXJuIGRvY3VtZW50O1xuICAgIH1cbn1cbmZ1bmN0aW9uIHBhcnNlRXZlbnRPcHRpb25zKGV2ZW50T3B0aW9ucykge1xuICAgIHJldHVybiBldmVudE9wdGlvbnMuc3BsaXQoXCI6XCIpLnJlZHVjZShmdW5jdGlvbiAob3B0aW9ucywgdG9rZW4pIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICByZXR1cm4gT2JqZWN0LmFzc2lnbihvcHRpb25zLCAoX2EgPSB7fSwgX2FbdG9rZW4ucmVwbGFjZSgvXiEvLCBcIlwiKV0gPSAhL14hLy50ZXN0KHRva2VuKSwgX2EpKTtcbiAgICB9LCB7fSk7XG59XG5leHBvcnQgZnVuY3Rpb24gc3RyaW5naWZ5RXZlbnRUYXJnZXQoZXZlbnRUYXJnZXQpIHtcbiAgICBpZiAoZXZlbnRUYXJnZXQgPT0gd2luZG93KSB7XG4gICAgICAgIHJldHVybiBcIndpbmRvd1wiO1xuICAgIH1cbiAgICBlbHNlIGlmIChldmVudFRhcmdldCA9PSBkb2N1bWVudCkge1xuICAgICAgICByZXR1cm4gXCJkb2N1bWVudFwiO1xuICAgIH1cbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWFjdGlvbl9kZXNjcmlwdG9yLmpzLm1hcCIsInZhciBfX2F3YWl0ZXIgPSAodGhpcyAmJiB0aGlzLl9fYXdhaXRlcikgfHwgZnVuY3Rpb24gKHRoaXNBcmcsIF9hcmd1bWVudHMsIFAsIGdlbmVyYXRvcikge1xuICAgIGZ1bmN0aW9uIGFkb3B0KHZhbHVlKSB7IHJldHVybiB2YWx1ZSBpbnN0YW5jZW9mIFAgPyB2YWx1ZSA6IG5ldyBQKGZ1bmN0aW9uIChyZXNvbHZlKSB7IHJlc29sdmUodmFsdWUpOyB9KTsgfVxuICAgIHJldHVybiBuZXcgKFAgfHwgKFAgPSBQcm9taXNlKSkoZnVuY3Rpb24gKHJlc29sdmUsIHJlamVjdCkge1xuICAgICAgICBmdW5jdGlvbiBmdWxmaWxsZWQodmFsdWUpIHsgdHJ5IHsgc3RlcChnZW5lcmF0b3IubmV4dCh2YWx1ZSkpOyB9IGNhdGNoIChlKSB7IHJlamVjdChlKTsgfSB9XG4gICAgICAgIGZ1bmN0aW9uIHJlamVjdGVkKHZhbHVlKSB7IHRyeSB7IHN0ZXAoZ2VuZXJhdG9yW1widGhyb3dcIl0odmFsdWUpKTsgfSBjYXRjaCAoZSkgeyByZWplY3QoZSk7IH0gfVxuICAgICAgICBmdW5jdGlvbiBzdGVwKHJlc3VsdCkgeyByZXN1bHQuZG9uZSA/IHJlc29sdmUocmVzdWx0LnZhbHVlKSA6IGFkb3B0KHJlc3VsdC52YWx1ZSkudGhlbihmdWxmaWxsZWQsIHJlamVjdGVkKTsgfVxuICAgICAgICBzdGVwKChnZW5lcmF0b3IgPSBnZW5lcmF0b3IuYXBwbHkodGhpc0FyZywgX2FyZ3VtZW50cyB8fCBbXSkpLm5leHQoKSk7XG4gICAgfSk7XG59O1xudmFyIF9fZ2VuZXJhdG9yID0gKHRoaXMgJiYgdGhpcy5fX2dlbmVyYXRvcikgfHwgZnVuY3Rpb24gKHRoaXNBcmcsIGJvZHkpIHtcbiAgICB2YXIgXyA9IHsgbGFiZWw6IDAsIHNlbnQ6IGZ1bmN0aW9uKCkgeyBpZiAodFswXSAmIDEpIHRocm93IHRbMV07IHJldHVybiB0WzFdOyB9LCB0cnlzOiBbXSwgb3BzOiBbXSB9LCBmLCB5LCB0LCBnO1xuICAgIHJldHVybiBnID0geyBuZXh0OiB2ZXJiKDApLCBcInRocm93XCI6IHZlcmIoMSksIFwicmV0dXJuXCI6IHZlcmIoMikgfSwgdHlwZW9mIFN5bWJvbCA9PT0gXCJmdW5jdGlvblwiICYmIChnW1N5bWJvbC5pdGVyYXRvcl0gPSBmdW5jdGlvbigpIHsgcmV0dXJuIHRoaXM7IH0pLCBnO1xuICAgIGZ1bmN0aW9uIHZlcmIobikgeyByZXR1cm4gZnVuY3Rpb24gKHYpIHsgcmV0dXJuIHN0ZXAoW24sIHZdKTsgfTsgfVxuICAgIGZ1bmN0aW9uIHN0ZXAob3ApIHtcbiAgICAgICAgaWYgKGYpIHRocm93IG5ldyBUeXBlRXJyb3IoXCJHZW5lcmF0b3IgaXMgYWxyZWFkeSBleGVjdXRpbmcuXCIpO1xuICAgICAgICB3aGlsZSAoXykgdHJ5IHtcbiAgICAgICAgICAgIGlmIChmID0gMSwgeSAmJiAodCA9IG9wWzBdICYgMiA/IHlbXCJyZXR1cm5cIl0gOiBvcFswXSA/IHlbXCJ0aHJvd1wiXSB8fCAoKHQgPSB5W1wicmV0dXJuXCJdKSAmJiB0LmNhbGwoeSksIDApIDogeS5uZXh0KSAmJiAhKHQgPSB0LmNhbGwoeSwgb3BbMV0pKS5kb25lKSByZXR1cm4gdDtcbiAgICAgICAgICAgIGlmICh5ID0gMCwgdCkgb3AgPSBbb3BbMF0gJiAyLCB0LnZhbHVlXTtcbiAgICAgICAgICAgIHN3aXRjaCAob3BbMF0pIHtcbiAgICAgICAgICAgICAgICBjYXNlIDA6IGNhc2UgMTogdCA9IG9wOyBicmVhaztcbiAgICAgICAgICAgICAgICBjYXNlIDQ6IF8ubGFiZWwrKzsgcmV0dXJuIHsgdmFsdWU6IG9wWzFdLCBkb25lOiBmYWxzZSB9O1xuICAgICAgICAgICAgICAgIGNhc2UgNTogXy5sYWJlbCsrOyB5ID0gb3BbMV07IG9wID0gWzBdOyBjb250aW51ZTtcbiAgICAgICAgICAgICAgICBjYXNlIDc6IG9wID0gXy5vcHMucG9wKCk7IF8udHJ5cy5wb3AoKTsgY29udGludWU7XG4gICAgICAgICAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgICAgICAgICAgaWYgKCEodCA9IF8udHJ5cywgdCA9IHQubGVuZ3RoID4gMCAmJiB0W3QubGVuZ3RoIC0gMV0pICYmIChvcFswXSA9PT0gNiB8fCBvcFswXSA9PT0gMikpIHsgXyA9IDA7IGNvbnRpbnVlOyB9XG4gICAgICAgICAgICAgICAgICAgIGlmIChvcFswXSA9PT0gMyAmJiAoIXQgfHwgKG9wWzFdID4gdFswXSAmJiBvcFsxXSA8IHRbM10pKSkgeyBfLmxhYmVsID0gb3BbMV07IGJyZWFrOyB9XG4gICAgICAgICAgICAgICAgICAgIGlmIChvcFswXSA9PT0gNiAmJiBfLmxhYmVsIDwgdFsxXSkgeyBfLmxhYmVsID0gdFsxXTsgdCA9IG9wOyBicmVhazsgfVxuICAgICAgICAgICAgICAgICAgICBpZiAodCAmJiBfLmxhYmVsIDwgdFsyXSkgeyBfLmxhYmVsID0gdFsyXTsgXy5vcHMucHVzaChvcCk7IGJyZWFrOyB9XG4gICAgICAgICAgICAgICAgICAgIGlmICh0WzJdKSBfLm9wcy5wb3AoKTtcbiAgICAgICAgICAgICAgICAgICAgXy50cnlzLnBvcCgpOyBjb250aW51ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIG9wID0gYm9keS5jYWxsKHRoaXNBcmcsIF8pO1xuICAgICAgICB9IGNhdGNoIChlKSB7IG9wID0gWzYsIGVdOyB5ID0gMDsgfSBmaW5hbGx5IHsgZiA9IHQgPSAwOyB9XG4gICAgICAgIGlmIChvcFswXSAmIDUpIHRocm93IG9wWzFdOyByZXR1cm4geyB2YWx1ZTogb3BbMF0gPyBvcFsxXSA6IHZvaWQgMCwgZG9uZTogdHJ1ZSB9O1xuICAgIH1cbn07XG52YXIgX19zcHJlYWRBcnJheXMgPSAodGhpcyAmJiB0aGlzLl9fc3ByZWFkQXJyYXlzKSB8fCBmdW5jdGlvbiAoKSB7XG4gICAgZm9yICh2YXIgcyA9IDAsIGkgPSAwLCBpbCA9IGFyZ3VtZW50cy5sZW5ndGg7IGkgPCBpbDsgaSsrKSBzICs9IGFyZ3VtZW50c1tpXS5sZW5ndGg7XG4gICAgZm9yICh2YXIgciA9IEFycmF5KHMpLCBrID0gMCwgaSA9IDA7IGkgPCBpbDsgaSsrKVxuICAgICAgICBmb3IgKHZhciBhID0gYXJndW1lbnRzW2ldLCBqID0gMCwgamwgPSBhLmxlbmd0aDsgaiA8IGpsOyBqKyssIGsrKylcbiAgICAgICAgICAgIHJba10gPSBhW2pdO1xuICAgIHJldHVybiByO1xufTtcbmltcG9ydCB7IERpc3BhdGNoZXIgfSBmcm9tIFwiLi9kaXNwYXRjaGVyXCI7XG5pbXBvcnQgeyBSb3V0ZXIgfSBmcm9tIFwiLi9yb3V0ZXJcIjtcbmltcG9ydCB7IGRlZmF1bHRTY2hlbWEgfSBmcm9tIFwiLi9zY2hlbWFcIjtcbnZhciBBcHBsaWNhdGlvbiA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBBcHBsaWNhdGlvbihlbGVtZW50LCBzY2hlbWEpIHtcbiAgICAgICAgaWYgKGVsZW1lbnQgPT09IHZvaWQgMCkgeyBlbGVtZW50ID0gZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50OyB9XG4gICAgICAgIGlmIChzY2hlbWEgPT09IHZvaWQgMCkgeyBzY2hlbWEgPSBkZWZhdWx0U2NoZW1hOyB9XG4gICAgICAgIHRoaXMubG9nZ2VyID0gY29uc29sZTtcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5zY2hlbWEgPSBzY2hlbWE7XG4gICAgICAgIHRoaXMuZGlzcGF0Y2hlciA9IG5ldyBEaXNwYXRjaGVyKHRoaXMpO1xuICAgICAgICB0aGlzLnJvdXRlciA9IG5ldyBSb3V0ZXIodGhpcyk7XG4gICAgfVxuICAgIEFwcGxpY2F0aW9uLnN0YXJ0ID0gZnVuY3Rpb24gKGVsZW1lbnQsIHNjaGVtYSkge1xuICAgICAgICB2YXIgYXBwbGljYXRpb24gPSBuZXcgQXBwbGljYXRpb24oZWxlbWVudCwgc2NoZW1hKTtcbiAgICAgICAgYXBwbGljYXRpb24uc3RhcnQoKTtcbiAgICAgICAgcmV0dXJuIGFwcGxpY2F0aW9uO1xuICAgIH07XG4gICAgQXBwbGljYXRpb24ucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICByZXR1cm4gX19hd2FpdGVyKHRoaXMsIHZvaWQgMCwgdm9pZCAwLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gX19nZW5lcmF0b3IodGhpcywgZnVuY3Rpb24gKF9hKSB7XG4gICAgICAgICAgICAgICAgc3dpdGNoIChfYS5sYWJlbCkge1xuICAgICAgICAgICAgICAgICAgICBjYXNlIDA6IHJldHVybiBbNCAvKnlpZWxkKi8sIGRvbVJlYWR5KCldO1xuICAgICAgICAgICAgICAgICAgICBjYXNlIDE6XG4gICAgICAgICAgICAgICAgICAgICAgICBfYS5zZW50KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLmRpc3BhdGNoZXIuc3RhcnQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMucm91dGVyLnN0YXJ0KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gWzIgLypyZXR1cm4qL107XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0pO1xuICAgIH07XG4gICAgQXBwbGljYXRpb24ucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuZGlzcGF0Y2hlci5zdG9wKCk7XG4gICAgICAgIHRoaXMucm91dGVyLnN0b3AoKTtcbiAgICB9O1xuICAgIEFwcGxpY2F0aW9uLnByb3RvdHlwZS5yZWdpc3RlciA9IGZ1bmN0aW9uIChpZGVudGlmaWVyLCBjb250cm9sbGVyQ29uc3RydWN0b3IpIHtcbiAgICAgICAgdGhpcy5sb2FkKHsgaWRlbnRpZmllcjogaWRlbnRpZmllciwgY29udHJvbGxlckNvbnN0cnVjdG9yOiBjb250cm9sbGVyQ29uc3RydWN0b3IgfSk7XG4gICAgfTtcbiAgICBBcHBsaWNhdGlvbi5wcm90b3R5cGUubG9hZCA9IGZ1bmN0aW9uIChoZWFkKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHZhciByZXN0ID0gW107XG4gICAgICAgIGZvciAodmFyIF9pID0gMTsgX2kgPCBhcmd1bWVudHMubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICByZXN0W19pIC0gMV0gPSBhcmd1bWVudHNbX2ldO1xuICAgICAgICB9XG4gICAgICAgIHZhciBkZWZpbml0aW9ucyA9IEFycmF5LmlzQXJyYXkoaGVhZCkgPyBoZWFkIDogX19zcHJlYWRBcnJheXMoW2hlYWRdLCByZXN0KTtcbiAgICAgICAgZGVmaW5pdGlvbnMuZm9yRWFjaChmdW5jdGlvbiAoZGVmaW5pdGlvbikgeyByZXR1cm4gX3RoaXMucm91dGVyLmxvYWREZWZpbml0aW9uKGRlZmluaXRpb24pOyB9KTtcbiAgICB9O1xuICAgIEFwcGxpY2F0aW9uLnByb3RvdHlwZS51bmxvYWQgPSBmdW5jdGlvbiAoaGVhZCkge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB2YXIgcmVzdCA9IFtdO1xuICAgICAgICBmb3IgKHZhciBfaSA9IDE7IF9pIDwgYXJndW1lbnRzLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgcmVzdFtfaSAtIDFdID0gYXJndW1lbnRzW19pXTtcbiAgICAgICAgfVxuICAgICAgICB2YXIgaWRlbnRpZmllcnMgPSBBcnJheS5pc0FycmF5KGhlYWQpID8gaGVhZCA6IF9fc3ByZWFkQXJyYXlzKFtoZWFkXSwgcmVzdCk7XG4gICAgICAgIGlkZW50aWZpZXJzLmZvckVhY2goZnVuY3Rpb24gKGlkZW50aWZpZXIpIHsgcmV0dXJuIF90aGlzLnJvdXRlci51bmxvYWRJZGVudGlmaWVyKGlkZW50aWZpZXIpOyB9KTtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShBcHBsaWNhdGlvbi5wcm90b3R5cGUsIFwiY29udHJvbGxlcnNcIiwge1xuICAgICAgICAvLyBDb250cm9sbGVyc1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnJvdXRlci5jb250ZXh0cy5tYXAoZnVuY3Rpb24gKGNvbnRleHQpIHsgcmV0dXJuIGNvbnRleHQuY29udHJvbGxlcjsgfSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBBcHBsaWNhdGlvbi5wcm90b3R5cGUuZ2V0Q29udHJvbGxlckZvckVsZW1lbnRBbmRJZGVudGlmaWVyID0gZnVuY3Rpb24gKGVsZW1lbnQsIGlkZW50aWZpZXIpIHtcbiAgICAgICAgdmFyIGNvbnRleHQgPSB0aGlzLnJvdXRlci5nZXRDb250ZXh0Rm9yRWxlbWVudEFuZElkZW50aWZpZXIoZWxlbWVudCwgaWRlbnRpZmllcik7XG4gICAgICAgIHJldHVybiBjb250ZXh0ID8gY29udGV4dC5jb250cm9sbGVyIDogbnVsbDtcbiAgICB9O1xuICAgIC8vIEVycm9yIGhhbmRsaW5nXG4gICAgQXBwbGljYXRpb24ucHJvdG90eXBlLmhhbmRsZUVycm9yID0gZnVuY3Rpb24gKGVycm9yLCBtZXNzYWdlLCBkZXRhaWwpIHtcbiAgICAgICAgdGhpcy5sb2dnZXIuZXJyb3IoXCIlc1xcblxcbiVvXFxuXFxuJW9cIiwgbWVzc2FnZSwgZXJyb3IsIGRldGFpbCk7XG4gICAgfTtcbiAgICByZXR1cm4gQXBwbGljYXRpb247XG59KCkpO1xuZXhwb3J0IHsgQXBwbGljYXRpb24gfTtcbmZ1bmN0aW9uIGRvbVJlYWR5KCkge1xuICAgIHJldHVybiBuZXcgUHJvbWlzZShmdW5jdGlvbiAocmVzb2x2ZSkge1xuICAgICAgICBpZiAoZG9jdW1lbnQucmVhZHlTdGF0ZSA9PSBcImxvYWRpbmdcIikge1xuICAgICAgICAgICAgZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcIkRPTUNvbnRlbnRMb2FkZWRcIiwgcmVzb2x2ZSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXNvbHZlKCk7XG4gICAgICAgIH1cbiAgICB9KTtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWFwcGxpY2F0aW9uLmpzLm1hcCIsInZhciBCaW5kaW5nID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIEJpbmRpbmcoY29udGV4dCwgYWN0aW9uKSB7XG4gICAgICAgIHRoaXMuY29udGV4dCA9IGNvbnRleHQ7XG4gICAgICAgIHRoaXMuYWN0aW9uID0gYWN0aW9uO1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwiaW5kZXhcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFjdGlvbi5pbmRleDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJldmVudFRhcmdldFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYWN0aW9uLmV2ZW50VGFyZ2V0O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcImV2ZW50T3B0aW9uc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYWN0aW9uLmV2ZW50T3B0aW9ucztcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJpZGVudGlmaWVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmlkZW50aWZpZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBCaW5kaW5nLnByb3RvdHlwZS5oYW5kbGVFdmVudCA9IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICBpZiAodGhpcy53aWxsQmVJbnZva2VkQnlFdmVudChldmVudCkpIHtcbiAgICAgICAgICAgIHRoaXMuaW52b2tlV2l0aEV2ZW50KGV2ZW50KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcImV2ZW50TmFtZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYWN0aW9uLmV2ZW50TmFtZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJtZXRob2RcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHZhciBtZXRob2QgPSB0aGlzLmNvbnRyb2xsZXJbdGhpcy5tZXRob2ROYW1lXTtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgbWV0aG9kID09IFwiZnVuY3Rpb25cIikge1xuICAgICAgICAgICAgICAgIHJldHVybiBtZXRob2Q7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoXCJBY3Rpb24gXFxcIlwiICsgdGhpcy5hY3Rpb24gKyBcIlxcXCIgcmVmZXJlbmNlcyB1bmRlZmluZWQgbWV0aG9kIFxcXCJcIiArIHRoaXMubWV0aG9kTmFtZSArIFwiXFxcIlwiKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIEJpbmRpbmcucHJvdG90eXBlLmludm9rZVdpdGhFdmVudCA9IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgdGhpcy5tZXRob2QuY2FsbCh0aGlzLmNvbnRyb2xsZXIsIGV2ZW50KTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHZhciBfYSA9IHRoaXMsIGlkZW50aWZpZXIgPSBfYS5pZGVudGlmaWVyLCBjb250cm9sbGVyID0gX2EuY29udHJvbGxlciwgZWxlbWVudCA9IF9hLmVsZW1lbnQsIGluZGV4ID0gX2EuaW5kZXg7XG4gICAgICAgICAgICB2YXIgZGV0YWlsID0geyBpZGVudGlmaWVyOiBpZGVudGlmaWVyLCBjb250cm9sbGVyOiBjb250cm9sbGVyLCBlbGVtZW50OiBlbGVtZW50LCBpbmRleDogaW5kZXgsIGV2ZW50OiBldmVudCB9O1xuICAgICAgICAgICAgdGhpcy5jb250ZXh0LmhhbmRsZUVycm9yKGVycm9yLCBcImludm9raW5nIGFjdGlvbiBcXFwiXCIgKyB0aGlzLmFjdGlvbiArIFwiXFxcIlwiLCBkZXRhaWwpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBCaW5kaW5nLnByb3RvdHlwZS53aWxsQmVJbnZva2VkQnlFdmVudCA9IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICB2YXIgZXZlbnRUYXJnZXQgPSBldmVudC50YXJnZXQ7XG4gICAgICAgIGlmICh0aGlzLmVsZW1lbnQgPT09IGV2ZW50VGFyZ2V0KSB7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChldmVudFRhcmdldCBpbnN0YW5jZW9mIEVsZW1lbnQgJiYgdGhpcy5lbGVtZW50LmNvbnRhaW5zKGV2ZW50VGFyZ2V0KSkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuY29udGFpbnNFbGVtZW50KGV2ZW50VGFyZ2V0KTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmNvbnRhaW5zRWxlbWVudCh0aGlzLmFjdGlvbi5lbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcImNvbnRyb2xsZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuY29udHJvbGxlcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJtZXRob2ROYW1lXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb24ubWV0aG9kTmFtZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcInNjb3BlXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LnNjb3BlO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgcmV0dXJuIEJpbmRpbmc7XG59KCkpO1xuZXhwb3J0IHsgQmluZGluZyB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9YmluZGluZy5qcy5tYXAiLCJpbXBvcnQgeyBBY3Rpb24gfSBmcm9tIFwiLi9hY3Rpb25cIjtcbmltcG9ydCB7IEJpbmRpbmcgfSBmcm9tIFwiLi9iaW5kaW5nXCI7XG5pbXBvcnQgeyBWYWx1ZUxpc3RPYnNlcnZlciB9IGZyb20gXCJAc3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzXCI7XG52YXIgQmluZGluZ09ic2VydmVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIEJpbmRpbmdPYnNlcnZlcihjb250ZXh0LCBkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLmNvbnRleHQgPSBjb250ZXh0O1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMuYmluZGluZ3NCeUFjdGlvbiA9IG5ldyBNYXA7XG4gICAgfVxuICAgIEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICghdGhpcy52YWx1ZUxpc3RPYnNlcnZlcikge1xuICAgICAgICAgICAgdGhpcy52YWx1ZUxpc3RPYnNlcnZlciA9IG5ldyBWYWx1ZUxpc3RPYnNlcnZlcih0aGlzLmVsZW1lbnQsIHRoaXMuYWN0aW9uQXR0cmlidXRlLCB0aGlzKTtcbiAgICAgICAgICAgIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgQmluZGluZ09ic2VydmVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAodGhpcy52YWx1ZUxpc3RPYnNlcnZlcikge1xuICAgICAgICAgICAgdGhpcy52YWx1ZUxpc3RPYnNlcnZlci5zdG9wKCk7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy52YWx1ZUxpc3RPYnNlcnZlcjtcbiAgICAgICAgICAgIHRoaXMuZGlzY29ubmVjdEFsbEFjdGlvbnMoKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUsIFwiaWRlbnRpZmllclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5pZGVudGlmaWVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUsIFwiYWN0aW9uQXR0cmlidXRlXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY2hlbWEuYWN0aW9uQXR0cmlidXRlO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUsIFwic2NoZW1hXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LnNjaGVtYTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLCBcImJpbmRpbmdzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLmJpbmRpbmdzQnlBY3Rpb24udmFsdWVzKCkpO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgQmluZGluZ09ic2VydmVyLnByb3RvdHlwZS5jb25uZWN0QWN0aW9uID0gZnVuY3Rpb24gKGFjdGlvbikge1xuICAgICAgICB2YXIgYmluZGluZyA9IG5ldyBCaW5kaW5nKHRoaXMuY29udGV4dCwgYWN0aW9uKTtcbiAgICAgICAgdGhpcy5iaW5kaW5nc0J5QWN0aW9uLnNldChhY3Rpb24sIGJpbmRpbmcpO1xuICAgICAgICB0aGlzLmRlbGVnYXRlLmJpbmRpbmdDb25uZWN0ZWQoYmluZGluZyk7XG4gICAgfTtcbiAgICBCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLmRpc2Nvbm5lY3RBY3Rpb24gPSBmdW5jdGlvbiAoYWN0aW9uKSB7XG4gICAgICAgIHZhciBiaW5kaW5nID0gdGhpcy5iaW5kaW5nc0J5QWN0aW9uLmdldChhY3Rpb24pO1xuICAgICAgICBpZiAoYmluZGluZykge1xuICAgICAgICAgICAgdGhpcy5iaW5kaW5nc0J5QWN0aW9uLmRlbGV0ZShhY3Rpb24pO1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5iaW5kaW5nRGlzY29ubmVjdGVkKGJpbmRpbmcpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLmRpc2Nvbm5lY3RBbGxBY3Rpb25zID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB0aGlzLmJpbmRpbmdzLmZvckVhY2goZnVuY3Rpb24gKGJpbmRpbmcpIHsgcmV0dXJuIF90aGlzLmRlbGVnYXRlLmJpbmRpbmdEaXNjb25uZWN0ZWQoYmluZGluZyk7IH0pO1xuICAgICAgICB0aGlzLmJpbmRpbmdzQnlBY3Rpb24uY2xlYXIoKTtcbiAgICB9O1xuICAgIC8vIFZhbHVlIG9ic2VydmVyIGRlbGVnYXRlXG4gICAgQmluZGluZ09ic2VydmVyLnByb3RvdHlwZS5wYXJzZVZhbHVlRm9yVG9rZW4gPSBmdW5jdGlvbiAodG9rZW4pIHtcbiAgICAgICAgdmFyIGFjdGlvbiA9IEFjdGlvbi5mb3JUb2tlbih0b2tlbik7XG4gICAgICAgIGlmIChhY3Rpb24uaWRlbnRpZmllciA9PSB0aGlzLmlkZW50aWZpZXIpIHtcbiAgICAgICAgICAgIHJldHVybiBhY3Rpb247XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudE1hdGNoZWRWYWx1ZSA9IGZ1bmN0aW9uIChlbGVtZW50LCBhY3Rpb24pIHtcbiAgICAgICAgdGhpcy5jb25uZWN0QWN0aW9uKGFjdGlvbik7XG4gICAgfTtcbiAgICBCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRVbm1hdGNoZWRWYWx1ZSA9IGZ1bmN0aW9uIChlbGVtZW50LCBhY3Rpb24pIHtcbiAgICAgICAgdGhpcy5kaXNjb25uZWN0QWN0aW9uKGFjdGlvbik7XG4gICAgfTtcbiAgICByZXR1cm4gQmluZGluZ09ic2VydmVyO1xufSgpKTtcbmV4cG9ydCB7IEJpbmRpbmdPYnNlcnZlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9YmluZGluZ19vYnNlcnZlci5qcy5tYXAiLCJ2YXIgX19leHRlbmRzID0gKHRoaXMgJiYgdGhpcy5fX2V4dGVuZHMpIHx8IChmdW5jdGlvbiAoKSB7XG4gICAgdmFyIGV4dGVuZFN0YXRpY3MgPSBmdW5jdGlvbiAoZCwgYikge1xuICAgICAgICBleHRlbmRTdGF0aWNzID0gT2JqZWN0LnNldFByb3RvdHlwZU9mIHx8XG4gICAgICAgICAgICAoeyBfX3Byb3RvX186IFtdIH0gaW5zdGFuY2VvZiBBcnJheSAmJiBmdW5jdGlvbiAoZCwgYikgeyBkLl9fcHJvdG9fXyA9IGI7IH0pIHx8XG4gICAgICAgICAgICBmdW5jdGlvbiAoZCwgYikgeyBmb3IgKHZhciBwIGluIGIpIGlmIChiLmhhc093blByb3BlcnR5KHApKSBkW3BdID0gYltwXTsgfTtcbiAgICAgICAgcmV0dXJuIGV4dGVuZFN0YXRpY3MoZCwgYik7XG4gICAgfTtcbiAgICByZXR1cm4gZnVuY3Rpb24gKGQsIGIpIHtcbiAgICAgICAgZXh0ZW5kU3RhdGljcyhkLCBiKTtcbiAgICAgICAgZnVuY3Rpb24gX18oKSB7IHRoaXMuY29uc3RydWN0b3IgPSBkOyB9XG4gICAgICAgIGQucHJvdG90eXBlID0gYiA9PT0gbnVsbCA/IE9iamVjdC5jcmVhdGUoYikgOiAoX18ucHJvdG90eXBlID0gYi5wcm90b3R5cGUsIG5ldyBfXygpKTtcbiAgICB9O1xufSkoKTtcbnZhciBfX3NwcmVhZEFycmF5cyA9ICh0aGlzICYmIHRoaXMuX19zcHJlYWRBcnJheXMpIHx8IGZ1bmN0aW9uICgpIHtcbiAgICBmb3IgKHZhciBzID0gMCwgaSA9IDAsIGlsID0gYXJndW1lbnRzLmxlbmd0aDsgaSA8IGlsOyBpKyspIHMgKz0gYXJndW1lbnRzW2ldLmxlbmd0aDtcbiAgICBmb3IgKHZhciByID0gQXJyYXkocyksIGsgPSAwLCBpID0gMDsgaSA8IGlsOyBpKyspXG4gICAgICAgIGZvciAodmFyIGEgPSBhcmd1bWVudHNbaV0sIGogPSAwLCBqbCA9IGEubGVuZ3RoOyBqIDwgamw7IGorKywgaysrKVxuICAgICAgICAgICAgcltrXSA9IGFbal07XG4gICAgcmV0dXJuIHI7XG59O1xuaW1wb3J0IHsgcmVhZEluaGVyaXRhYmxlU3RhdGljQXJyYXlWYWx1ZXMgfSBmcm9tIFwiLi9pbmhlcml0YWJsZV9zdGF0aWNzXCI7XG4vKiogQGhpZGRlbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGJsZXNzKGNvbnN0cnVjdG9yKSB7XG4gICAgcmV0dXJuIHNoYWRvdyhjb25zdHJ1Y3RvciwgZ2V0Qmxlc3NlZFByb3BlcnRpZXMoY29uc3RydWN0b3IpKTtcbn1cbmZ1bmN0aW9uIHNoYWRvdyhjb25zdHJ1Y3RvciwgcHJvcGVydGllcykge1xuICAgIHZhciBzaGFkb3dDb25zdHJ1Y3RvciA9IGV4dGVuZChjb25zdHJ1Y3Rvcik7XG4gICAgdmFyIHNoYWRvd1Byb3BlcnRpZXMgPSBnZXRTaGFkb3dQcm9wZXJ0aWVzKGNvbnN0cnVjdG9yLnByb3RvdHlwZSwgcHJvcGVydGllcyk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnRpZXMoc2hhZG93Q29uc3RydWN0b3IucHJvdG90eXBlLCBzaGFkb3dQcm9wZXJ0aWVzKTtcbiAgICByZXR1cm4gc2hhZG93Q29uc3RydWN0b3I7XG59XG5mdW5jdGlvbiBnZXRCbGVzc2VkUHJvcGVydGllcyhjb25zdHJ1Y3Rvcikge1xuICAgIHZhciBibGVzc2luZ3MgPSByZWFkSW5oZXJpdGFibGVTdGF0aWNBcnJheVZhbHVlcyhjb25zdHJ1Y3RvciwgXCJibGVzc2luZ3NcIik7XG4gICAgcmV0dXJuIGJsZXNzaW5ncy5yZWR1Y2UoZnVuY3Rpb24gKGJsZXNzZWRQcm9wZXJ0aWVzLCBibGVzc2luZykge1xuICAgICAgICB2YXIgcHJvcGVydGllcyA9IGJsZXNzaW5nKGNvbnN0cnVjdG9yKTtcbiAgICAgICAgZm9yICh2YXIga2V5IGluIHByb3BlcnRpZXMpIHtcbiAgICAgICAgICAgIHZhciBkZXNjcmlwdG9yID0gYmxlc3NlZFByb3BlcnRpZXNba2V5XSB8fCB7fTtcbiAgICAgICAgICAgIGJsZXNzZWRQcm9wZXJ0aWVzW2tleV0gPSBPYmplY3QuYXNzaWduKGRlc2NyaXB0b3IsIHByb3BlcnRpZXNba2V5XSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGJsZXNzZWRQcm9wZXJ0aWVzO1xuICAgIH0sIHt9KTtcbn1cbmZ1bmN0aW9uIGdldFNoYWRvd1Byb3BlcnRpZXMocHJvdG90eXBlLCBwcm9wZXJ0aWVzKSB7XG4gICAgcmV0dXJuIGdldE93bktleXMocHJvcGVydGllcykucmVkdWNlKGZ1bmN0aW9uIChzaGFkb3dQcm9wZXJ0aWVzLCBrZXkpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICB2YXIgZGVzY3JpcHRvciA9IGdldFNoYWRvd2VkRGVzY3JpcHRvcihwcm90b3R5cGUsIHByb3BlcnRpZXMsIGtleSk7XG4gICAgICAgIGlmIChkZXNjcmlwdG9yKSB7XG4gICAgICAgICAgICBPYmplY3QuYXNzaWduKHNoYWRvd1Byb3BlcnRpZXMsIChfYSA9IHt9LCBfYVtrZXldID0gZGVzY3JpcHRvciwgX2EpKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gc2hhZG93UHJvcGVydGllcztcbiAgICB9LCB7fSk7XG59XG5mdW5jdGlvbiBnZXRTaGFkb3dlZERlc2NyaXB0b3IocHJvdG90eXBlLCBwcm9wZXJ0aWVzLCBrZXkpIHtcbiAgICB2YXIgc2hhZG93aW5nRGVzY3JpcHRvciA9IE9iamVjdC5nZXRPd25Qcm9wZXJ0eURlc2NyaXB0b3IocHJvdG90eXBlLCBrZXkpO1xuICAgIHZhciBzaGFkb3dlZEJ5VmFsdWUgPSBzaGFkb3dpbmdEZXNjcmlwdG9yICYmIFwidmFsdWVcIiBpbiBzaGFkb3dpbmdEZXNjcmlwdG9yO1xuICAgIGlmICghc2hhZG93ZWRCeVZhbHVlKSB7XG4gICAgICAgIHZhciBkZXNjcmlwdG9yID0gT2JqZWN0LmdldE93blByb3BlcnR5RGVzY3JpcHRvcihwcm9wZXJ0aWVzLCBrZXkpLnZhbHVlO1xuICAgICAgICBpZiAoc2hhZG93aW5nRGVzY3JpcHRvcikge1xuICAgICAgICAgICAgZGVzY3JpcHRvci5nZXQgPSBzaGFkb3dpbmdEZXNjcmlwdG9yLmdldCB8fCBkZXNjcmlwdG9yLmdldDtcbiAgICAgICAgICAgIGRlc2NyaXB0b3Iuc2V0ID0gc2hhZG93aW5nRGVzY3JpcHRvci5zZXQgfHwgZGVzY3JpcHRvci5zZXQ7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGRlc2NyaXB0b3I7XG4gICAgfVxufVxudmFyIGdldE93bktleXMgPSAoZnVuY3Rpb24gKCkge1xuICAgIGlmICh0eXBlb2YgT2JqZWN0LmdldE93blByb3BlcnR5U3ltYm9scyA9PSBcImZ1bmN0aW9uXCIpIHtcbiAgICAgICAgcmV0dXJuIGZ1bmN0aW9uIChvYmplY3QpIHsgcmV0dXJuIF9fc3ByZWFkQXJyYXlzKE9iamVjdC5nZXRPd25Qcm9wZXJ0eU5hbWVzKG9iamVjdCksIE9iamVjdC5nZXRPd25Qcm9wZXJ0eVN5bWJvbHMob2JqZWN0KSk7IH07XG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgICByZXR1cm4gT2JqZWN0LmdldE93blByb3BlcnR5TmFtZXM7XG4gICAgfVxufSkoKTtcbnZhciBleHRlbmQgPSAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIGV4dGVuZFdpdGhSZWZsZWN0KGNvbnN0cnVjdG9yKSB7XG4gICAgICAgIGZ1bmN0aW9uIGV4dGVuZGVkKCkge1xuICAgICAgICAgICAgdmFyIF9uZXdUYXJnZXQgPSB0aGlzICYmIHRoaXMgaW5zdGFuY2VvZiBleHRlbmRlZCA/IHRoaXMuY29uc3RydWN0b3IgOiB2b2lkIDA7XG4gICAgICAgICAgICByZXR1cm4gUmVmbGVjdC5jb25zdHJ1Y3QoY29uc3RydWN0b3IsIGFyZ3VtZW50cywgX25ld1RhcmdldCk7XG4gICAgICAgIH1cbiAgICAgICAgZXh0ZW5kZWQucHJvdG90eXBlID0gT2JqZWN0LmNyZWF0ZShjb25zdHJ1Y3Rvci5wcm90b3R5cGUsIHtcbiAgICAgICAgICAgIGNvbnN0cnVjdG9yOiB7IHZhbHVlOiBleHRlbmRlZCB9XG4gICAgICAgIH0pO1xuICAgICAgICBSZWZsZWN0LnNldFByb3RvdHlwZU9mKGV4dGVuZGVkLCBjb25zdHJ1Y3Rvcik7XG4gICAgICAgIHJldHVybiBleHRlbmRlZDtcbiAgICB9XG4gICAgZnVuY3Rpb24gdGVzdFJlZmxlY3RFeHRlbnNpb24oKSB7XG4gICAgICAgIHZhciBhID0gZnVuY3Rpb24gKCkgeyB0aGlzLmEuY2FsbCh0aGlzKTsgfTtcbiAgICAgICAgdmFyIGIgPSBleHRlbmRXaXRoUmVmbGVjdChhKTtcbiAgICAgICAgYi5wcm90b3R5cGUuYSA9IGZ1bmN0aW9uICgpIHsgfTtcbiAgICAgICAgcmV0dXJuIG5ldyBiO1xuICAgIH1cbiAgICB0cnkge1xuICAgICAgICB0ZXN0UmVmbGVjdEV4dGVuc2lvbigpO1xuICAgICAgICByZXR1cm4gZXh0ZW5kV2l0aFJlZmxlY3Q7XG4gICAgfVxuICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICByZXR1cm4gZnVuY3Rpb24gKGNvbnN0cnVjdG9yKSB7IHJldHVybiAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoX3N1cGVyKSB7XG4gICAgICAgICAgICBfX2V4dGVuZHMoZXh0ZW5kZWQsIF9zdXBlcik7XG4gICAgICAgICAgICBmdW5jdGlvbiBleHRlbmRlZCgpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gX3N1cGVyICE9PSBudWxsICYmIF9zdXBlci5hcHBseSh0aGlzLCBhcmd1bWVudHMpIHx8IHRoaXM7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gZXh0ZW5kZWQ7XG4gICAgICAgIH0oY29uc3RydWN0b3IpKTsgfTtcbiAgICB9XG59KSgpO1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9Ymxlc3NpbmcuanMubWFwIiwidmFyIENsYXNzTWFwID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIENsYXNzTWFwKHNjb3BlKSB7XG4gICAgICAgIHRoaXMuc2NvcGUgPSBzY29wZTtcbiAgICB9XG4gICAgQ2xhc3NNYXAucHJvdG90eXBlLmhhcyA9IGZ1bmN0aW9uIChuYW1lKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmRhdGEuaGFzKHRoaXMuZ2V0RGF0YUtleShuYW1lKSk7XG4gICAgfTtcbiAgICBDbGFzc01hcC5wcm90b3R5cGUuZ2V0ID0gZnVuY3Rpb24gKG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGF0YS5nZXQodGhpcy5nZXREYXRhS2V5KG5hbWUpKTtcbiAgICB9O1xuICAgIENsYXNzTWFwLnByb3RvdHlwZS5nZXRBdHRyaWJ1dGVOYW1lID0gZnVuY3Rpb24gKG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGF0YS5nZXRBdHRyaWJ1dGVOYW1lRm9yS2V5KHRoaXMuZ2V0RGF0YUtleShuYW1lKSk7XG4gICAgfTtcbiAgICBDbGFzc01hcC5wcm90b3R5cGUuZ2V0RGF0YUtleSA9IGZ1bmN0aW9uIChuYW1lKSB7XG4gICAgICAgIHJldHVybiBuYW1lICsgXCItY2xhc3NcIjtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDbGFzc01hcC5wcm90b3R5cGUsIFwiZGF0YVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZGF0YTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIHJldHVybiBDbGFzc01hcDtcbn0oKSk7XG5leHBvcnQgeyBDbGFzc01hcCB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9Y2xhc3NfbWFwLmpzLm1hcCIsImltcG9ydCB7IHJlYWRJbmhlcml0YWJsZVN0YXRpY0FycmF5VmFsdWVzIH0gZnJvbSBcIi4vaW5oZXJpdGFibGVfc3RhdGljc1wiO1xuaW1wb3J0IHsgY2FwaXRhbGl6ZSB9IGZyb20gXCIuL3N0cmluZ19oZWxwZXJzXCI7XG4vKiogQGhpZGRlbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIENsYXNzUHJvcGVydGllc0JsZXNzaW5nKGNvbnN0cnVjdG9yKSB7XG4gICAgdmFyIGNsYXNzZXMgPSByZWFkSW5oZXJpdGFibGVTdGF0aWNBcnJheVZhbHVlcyhjb25zdHJ1Y3RvciwgXCJjbGFzc2VzXCIpO1xuICAgIHJldHVybiBjbGFzc2VzLnJlZHVjZShmdW5jdGlvbiAocHJvcGVydGllcywgY2xhc3NEZWZpbml0aW9uKSB7XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKHByb3BlcnRpZXMsIHByb3BlcnRpZXNGb3JDbGFzc0RlZmluaXRpb24oY2xhc3NEZWZpbml0aW9uKSk7XG4gICAgfSwge30pO1xufVxuZnVuY3Rpb24gcHJvcGVydGllc0ZvckNsYXNzRGVmaW5pdGlvbihrZXkpIHtcbiAgICB2YXIgX2E7XG4gICAgdmFyIG5hbWUgPSBrZXkgKyBcIkNsYXNzXCI7XG4gICAgcmV0dXJuIF9hID0ge30sXG4gICAgICAgIF9hW25hbWVdID0ge1xuICAgICAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgdmFyIGNsYXNzZXMgPSB0aGlzLmNsYXNzZXM7XG4gICAgICAgICAgICAgICAgaWYgKGNsYXNzZXMuaGFzKGtleSkpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGNsYXNzZXMuZ2V0KGtleSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICB2YXIgYXR0cmlidXRlID0gY2xhc3Nlcy5nZXRBdHRyaWJ1dGVOYW1lKGtleSk7XG4gICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihcIk1pc3NpbmcgYXR0cmlidXRlIFxcXCJcIiArIGF0dHJpYnV0ZSArIFwiXFxcIlwiKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIF9hW1wiaGFzXCIgKyBjYXBpdGFsaXplKG5hbWUpXSA9IHtcbiAgICAgICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLmNsYXNzZXMuaGFzKGtleSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIF9hO1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9Y2xhc3NfcHJvcGVydGllcy5qcy5tYXAiLCJpbXBvcnQgeyBCaW5kaW5nT2JzZXJ2ZXIgfSBmcm9tIFwiLi9iaW5kaW5nX29ic2VydmVyXCI7XG5pbXBvcnQgeyBWYWx1ZU9ic2VydmVyIH0gZnJvbSBcIi4vdmFsdWVfb2JzZXJ2ZXJcIjtcbnZhciBDb250ZXh0ID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIENvbnRleHQobW9kdWxlLCBzY29wZSkge1xuICAgICAgICB0aGlzLm1vZHVsZSA9IG1vZHVsZTtcbiAgICAgICAgdGhpcy5zY29wZSA9IHNjb3BlO1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXIgPSBuZXcgbW9kdWxlLmNvbnRyb2xsZXJDb25zdHJ1Y3Rvcih0aGlzKTtcbiAgICAgICAgdGhpcy5iaW5kaW5nT2JzZXJ2ZXIgPSBuZXcgQmluZGluZ09ic2VydmVyKHRoaXMsIHRoaXMuZGlzcGF0Y2hlcik7XG4gICAgICAgIHRoaXMudmFsdWVPYnNlcnZlciA9IG5ldyBWYWx1ZU9ic2VydmVyKHRoaXMsIHRoaXMuY29udHJvbGxlcik7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICB0aGlzLmNvbnRyb2xsZXIuaW5pdGlhbGl6ZSgpO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgdGhpcy5oYW5kbGVFcnJvcihlcnJvciwgXCJpbml0aWFsaXppbmcgY29udHJvbGxlclwiKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBDb250ZXh0LnByb3RvdHlwZS5jb25uZWN0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmJpbmRpbmdPYnNlcnZlci5zdGFydCgpO1xuICAgICAgICB0aGlzLnZhbHVlT2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHRoaXMuY29udHJvbGxlci5jb25uZWN0KCk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICB0aGlzLmhhbmRsZUVycm9yKGVycm9yLCBcImNvbm5lY3RpbmcgY29udHJvbGxlclwiKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgQ29udGV4dC5wcm90b3R5cGUuZGlzY29ubmVjdCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHRoaXMuY29udHJvbGxlci5kaXNjb25uZWN0KCk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICB0aGlzLmhhbmRsZUVycm9yKGVycm9yLCBcImRpc2Nvbm5lY3RpbmcgY29udHJvbGxlclwiKTtcbiAgICAgICAgfVxuICAgICAgICB0aGlzLnZhbHVlT2JzZXJ2ZXIuc3RvcCgpO1xuICAgICAgICB0aGlzLmJpbmRpbmdPYnNlcnZlci5zdG9wKCk7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udGV4dC5wcm90b3R5cGUsIFwiYXBwbGljYXRpb25cIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLm1vZHVsZS5hcHBsaWNhdGlvbjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250ZXh0LnByb3RvdHlwZSwgXCJpZGVudGlmaWVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5tb2R1bGUuaWRlbnRpZmllcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250ZXh0LnByb3RvdHlwZSwgXCJzY2hlbWFcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFwcGxpY2F0aW9uLnNjaGVtYTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250ZXh0LnByb3RvdHlwZSwgXCJkaXNwYXRjaGVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hcHBsaWNhdGlvbi5kaXNwYXRjaGVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRleHQucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udGV4dC5wcm90b3R5cGUsIFwicGFyZW50RWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5wYXJlbnRFbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgLy8gRXJyb3IgaGFuZGxpbmdcbiAgICBDb250ZXh0LnByb3RvdHlwZS5oYW5kbGVFcnJvciA9IGZ1bmN0aW9uIChlcnJvciwgbWVzc2FnZSwgZGV0YWlsKSB7XG4gICAgICAgIGlmIChkZXRhaWwgPT09IHZvaWQgMCkgeyBkZXRhaWwgPSB7fTsgfVxuICAgICAgICB2YXIgX2EgPSB0aGlzLCBpZGVudGlmaWVyID0gX2EuaWRlbnRpZmllciwgY29udHJvbGxlciA9IF9hLmNvbnRyb2xsZXIsIGVsZW1lbnQgPSBfYS5lbGVtZW50O1xuICAgICAgICBkZXRhaWwgPSBPYmplY3QuYXNzaWduKHsgaWRlbnRpZmllcjogaWRlbnRpZmllciwgY29udHJvbGxlcjogY29udHJvbGxlciwgZWxlbWVudDogZWxlbWVudCB9LCBkZXRhaWwpO1xuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uLmhhbmRsZUVycm9yKGVycm9yLCBcIkVycm9yIFwiICsgbWVzc2FnZSwgZGV0YWlsKTtcbiAgICB9O1xuICAgIHJldHVybiBDb250ZXh0O1xufSgpKTtcbmV4cG9ydCB7IENvbnRleHQgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWNvbnRleHQuanMubWFwIiwiaW1wb3J0IHsgQ2xhc3NQcm9wZXJ0aWVzQmxlc3NpbmcgfSBmcm9tIFwiLi9jbGFzc19wcm9wZXJ0aWVzXCI7XG5pbXBvcnQgeyBUYXJnZXRQcm9wZXJ0aWVzQmxlc3NpbmcgfSBmcm9tIFwiLi90YXJnZXRfcHJvcGVydGllc1wiO1xuaW1wb3J0IHsgVmFsdWVQcm9wZXJ0aWVzQmxlc3NpbmcgfSBmcm9tIFwiLi92YWx1ZV9wcm9wZXJ0aWVzXCI7XG52YXIgQ29udHJvbGxlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBDb250cm9sbGVyKGNvbnRleHQpIHtcbiAgICAgICAgdGhpcy5jb250ZXh0ID0gY29udGV4dDtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRyb2xsZXIucHJvdG90eXBlLCBcImFwcGxpY2F0aW9uXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmFwcGxpY2F0aW9uO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRyb2xsZXIucHJvdG90eXBlLCBcInNjb3BlXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LnNjb3BlO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRyb2xsZXIucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udHJvbGxlci5wcm90b3R5cGUsIFwiaWRlbnRpZmllclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuaWRlbnRpZmllcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250cm9sbGVyLnByb3RvdHlwZSwgXCJ0YXJnZXRzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS50YXJnZXRzO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRyb2xsZXIucHJvdG90eXBlLCBcImNsYXNzZXNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmNsYXNzZXM7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udHJvbGxlci5wcm90b3R5cGUsIFwiZGF0YVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZGF0YTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIENvbnRyb2xsZXIucHJvdG90eXBlLmluaXRpYWxpemUgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIC8vIE92ZXJyaWRlIGluIHlvdXIgc3ViY2xhc3MgdG8gc2V0IHVwIGluaXRpYWwgY29udHJvbGxlciBzdGF0ZVxuICAgIH07XG4gICAgQ29udHJvbGxlci5wcm90b3R5cGUuY29ubmVjdCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgLy8gT3ZlcnJpZGUgaW4geW91ciBzdWJjbGFzcyB0byByZXNwb25kIHdoZW4gdGhlIGNvbnRyb2xsZXIgaXMgY29ubmVjdGVkIHRvIHRoZSBET01cbiAgICB9O1xuICAgIENvbnRyb2xsZXIucHJvdG90eXBlLmRpc2Nvbm5lY3QgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIC8vIE92ZXJyaWRlIGluIHlvdXIgc3ViY2xhc3MgdG8gcmVzcG9uZCB3aGVuIHRoZSBjb250cm9sbGVyIGlzIGRpc2Nvbm5lY3RlZCBmcm9tIHRoZSBET01cbiAgICB9O1xuICAgIENvbnRyb2xsZXIuYmxlc3NpbmdzID0gW0NsYXNzUHJvcGVydGllc0JsZXNzaW5nLCBUYXJnZXRQcm9wZXJ0aWVzQmxlc3NpbmcsIFZhbHVlUHJvcGVydGllc0JsZXNzaW5nXTtcbiAgICBDb250cm9sbGVyLnRhcmdldHMgPSBbXTtcbiAgICBDb250cm9sbGVyLnZhbHVlcyA9IHt9O1xuICAgIHJldHVybiBDb250cm9sbGVyO1xufSgpKTtcbmV4cG9ydCB7IENvbnRyb2xsZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWNvbnRyb2xsZXIuanMubWFwIiwiaW1wb3J0IHsgZGFzaGVyaXplIH0gZnJvbSBcIi4vc3RyaW5nX2hlbHBlcnNcIjtcbnZhciBEYXRhTWFwID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIERhdGFNYXAoc2NvcGUpIHtcbiAgICAgICAgdGhpcy5zY29wZSA9IHNjb3BlO1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoRGF0YU1hcC5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShEYXRhTWFwLnByb3RvdHlwZSwgXCJpZGVudGlmaWVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5pZGVudGlmaWVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgRGF0YU1hcC5wcm90b3R5cGUuZ2V0ID0gZnVuY3Rpb24gKGtleSkge1xuICAgICAgICB2YXIgbmFtZSA9IHRoaXMuZ2V0QXR0cmlidXRlTmFtZUZvcktleShrZXkpO1xuICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50LmdldEF0dHJpYnV0ZShuYW1lKTtcbiAgICB9O1xuICAgIERhdGFNYXAucHJvdG90eXBlLnNldCA9IGZ1bmN0aW9uIChrZXksIHZhbHVlKSB7XG4gICAgICAgIHZhciBuYW1lID0gdGhpcy5nZXRBdHRyaWJ1dGVOYW1lRm9yS2V5KGtleSk7XG4gICAgICAgIHRoaXMuZWxlbWVudC5zZXRBdHRyaWJ1dGUobmFtZSwgdmFsdWUpO1xuICAgICAgICByZXR1cm4gdGhpcy5nZXQoa2V5KTtcbiAgICB9O1xuICAgIERhdGFNYXAucHJvdG90eXBlLmhhcyA9IGZ1bmN0aW9uIChrZXkpIHtcbiAgICAgICAgdmFyIG5hbWUgPSB0aGlzLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkoa2V5KTtcbiAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5oYXNBdHRyaWJ1dGUobmFtZSk7XG4gICAgfTtcbiAgICBEYXRhTWFwLnByb3RvdHlwZS5kZWxldGUgPSBmdW5jdGlvbiAoa2V5KSB7XG4gICAgICAgIGlmICh0aGlzLmhhcyhrZXkpKSB7XG4gICAgICAgICAgICB2YXIgbmFtZV8xID0gdGhpcy5nZXRBdHRyaWJ1dGVOYW1lRm9yS2V5KGtleSk7XG4gICAgICAgICAgICB0aGlzLmVsZW1lbnQucmVtb3ZlQXR0cmlidXRlKG5hbWVfMSk7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgRGF0YU1hcC5wcm90b3R5cGUuZ2V0QXR0cmlidXRlTmFtZUZvcktleSA9IGZ1bmN0aW9uIChrZXkpIHtcbiAgICAgICAgcmV0dXJuIFwiZGF0YS1cIiArIHRoaXMuaWRlbnRpZmllciArIFwiLVwiICsgZGFzaGVyaXplKGtleSk7XG4gICAgfTtcbiAgICByZXR1cm4gRGF0YU1hcDtcbn0oKSk7XG5leHBvcnQgeyBEYXRhTWFwIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1kYXRhX21hcC5qcy5tYXAiLCJpbXBvcnQgeyBibGVzcyB9IGZyb20gXCIuL2JsZXNzaW5nXCI7XG4vKiogQGhpZGRlbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGJsZXNzRGVmaW5pdGlvbihkZWZpbml0aW9uKSB7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgaWRlbnRpZmllcjogZGVmaW5pdGlvbi5pZGVudGlmaWVyLFxuICAgICAgICBjb250cm9sbGVyQ29uc3RydWN0b3I6IGJsZXNzKGRlZmluaXRpb24uY29udHJvbGxlckNvbnN0cnVjdG9yKVxuICAgIH07XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1kZWZpbml0aW9uLmpzLm1hcCIsImltcG9ydCB7IEV2ZW50TGlzdGVuZXIgfSBmcm9tIFwiLi9ldmVudF9saXN0ZW5lclwiO1xudmFyIERpc3BhdGNoZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gRGlzcGF0Y2hlcihhcHBsaWNhdGlvbikge1xuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uID0gYXBwbGljYXRpb247XG4gICAgICAgIHRoaXMuZXZlbnRMaXN0ZW5lck1hcHMgPSBuZXcgTWFwO1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICB9XG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICghdGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICAgICAgdGhpcy5ldmVudExpc3RlbmVycy5mb3JFYWNoKGZ1bmN0aW9uIChldmVudExpc3RlbmVyKSB7IHJldHVybiBldmVudExpc3RlbmVyLmNvbm5lY3QoKTsgfSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICAgICAgdGhpcy5ldmVudExpc3RlbmVycy5mb3JFYWNoKGZ1bmN0aW9uIChldmVudExpc3RlbmVyKSB7IHJldHVybiBldmVudExpc3RlbmVyLmRpc2Nvbm5lY3QoKTsgfSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShEaXNwYXRjaGVyLnByb3RvdHlwZSwgXCJldmVudExpc3RlbmVyc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5ldmVudExpc3RlbmVyTWFwcy52YWx1ZXMoKSlcbiAgICAgICAgICAgICAgICAucmVkdWNlKGZ1bmN0aW9uIChsaXN0ZW5lcnMsIG1hcCkgeyByZXR1cm4gbGlzdGVuZXJzLmNvbmNhdChBcnJheS5mcm9tKG1hcC52YWx1ZXMoKSkpOyB9LCBbXSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICAvLyBCaW5kaW5nIG9ic2VydmVyIGRlbGVnYXRlXG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5iaW5kaW5nQ29ubmVjdGVkID0gZnVuY3Rpb24gKGJpbmRpbmcpIHtcbiAgICAgICAgdGhpcy5mZXRjaEV2ZW50TGlzdGVuZXJGb3JCaW5kaW5nKGJpbmRpbmcpLmJpbmRpbmdDb25uZWN0ZWQoYmluZGluZyk7XG4gICAgfTtcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLmJpbmRpbmdEaXNjb25uZWN0ZWQgPSBmdW5jdGlvbiAoYmluZGluZykge1xuICAgICAgICB0aGlzLmZldGNoRXZlbnRMaXN0ZW5lckZvckJpbmRpbmcoYmluZGluZykuYmluZGluZ0Rpc2Nvbm5lY3RlZChiaW5kaW5nKTtcbiAgICB9O1xuICAgIC8vIEVycm9yIGhhbmRsaW5nXG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuaGFuZGxlRXJyb3IgPSBmdW5jdGlvbiAoZXJyb3IsIG1lc3NhZ2UsIGRldGFpbCkge1xuICAgICAgICBpZiAoZGV0YWlsID09PSB2b2lkIDApIHsgZGV0YWlsID0ge307IH1cbiAgICAgICAgdGhpcy5hcHBsaWNhdGlvbi5oYW5kbGVFcnJvcihlcnJvciwgXCJFcnJvciBcIiArIG1lc3NhZ2UsIGRldGFpbCk7XG4gICAgfTtcbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5mZXRjaEV2ZW50TGlzdGVuZXJGb3JCaW5kaW5nID0gZnVuY3Rpb24gKGJpbmRpbmcpIHtcbiAgICAgICAgdmFyIGV2ZW50VGFyZ2V0ID0gYmluZGluZy5ldmVudFRhcmdldCwgZXZlbnROYW1lID0gYmluZGluZy5ldmVudE5hbWUsIGV2ZW50T3B0aW9ucyA9IGJpbmRpbmcuZXZlbnRPcHRpb25zO1xuICAgICAgICByZXR1cm4gdGhpcy5mZXRjaEV2ZW50TGlzdGVuZXIoZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKTtcbiAgICB9O1xuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLmZldGNoRXZlbnRMaXN0ZW5lciA9IGZ1bmN0aW9uIChldmVudFRhcmdldCwgZXZlbnROYW1lLCBldmVudE9wdGlvbnMpIHtcbiAgICAgICAgdmFyIGV2ZW50TGlzdGVuZXJNYXAgPSB0aGlzLmZldGNoRXZlbnRMaXN0ZW5lck1hcEZvckV2ZW50VGFyZ2V0KGV2ZW50VGFyZ2V0KTtcbiAgICAgICAgdmFyIGNhY2hlS2V5ID0gdGhpcy5jYWNoZUtleShldmVudE5hbWUsIGV2ZW50T3B0aW9ucyk7XG4gICAgICAgIHZhciBldmVudExpc3RlbmVyID0gZXZlbnRMaXN0ZW5lck1hcC5nZXQoY2FjaGVLZXkpO1xuICAgICAgICBpZiAoIWV2ZW50TGlzdGVuZXIpIHtcbiAgICAgICAgICAgIGV2ZW50TGlzdGVuZXIgPSB0aGlzLmNyZWF0ZUV2ZW50TGlzdGVuZXIoZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKTtcbiAgICAgICAgICAgIGV2ZW50TGlzdGVuZXJNYXAuc2V0KGNhY2hlS2V5LCBldmVudExpc3RlbmVyKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZXZlbnRMaXN0ZW5lcjtcbiAgICB9O1xuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLmNyZWF0ZUV2ZW50TGlzdGVuZXIgPSBmdW5jdGlvbiAoZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKSB7XG4gICAgICAgIHZhciBldmVudExpc3RlbmVyID0gbmV3IEV2ZW50TGlzdGVuZXIoZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKTtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgZXZlbnRMaXN0ZW5lci5jb25uZWN0KCk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGV2ZW50TGlzdGVuZXI7XG4gICAgfTtcbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5mZXRjaEV2ZW50TGlzdGVuZXJNYXBGb3JFdmVudFRhcmdldCA9IGZ1bmN0aW9uIChldmVudFRhcmdldCkge1xuICAgICAgICB2YXIgZXZlbnRMaXN0ZW5lck1hcCA9IHRoaXMuZXZlbnRMaXN0ZW5lck1hcHMuZ2V0KGV2ZW50VGFyZ2V0KTtcbiAgICAgICAgaWYgKCFldmVudExpc3RlbmVyTWFwKSB7XG4gICAgICAgICAgICBldmVudExpc3RlbmVyTWFwID0gbmV3IE1hcDtcbiAgICAgICAgICAgIHRoaXMuZXZlbnRMaXN0ZW5lck1hcHMuc2V0KGV2ZW50VGFyZ2V0LCBldmVudExpc3RlbmVyTWFwKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZXZlbnRMaXN0ZW5lck1hcDtcbiAgICB9O1xuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLmNhY2hlS2V5ID0gZnVuY3Rpb24gKGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKSB7XG4gICAgICAgIHZhciBwYXJ0cyA9IFtldmVudE5hbWVdO1xuICAgICAgICBPYmplY3Qua2V5cyhldmVudE9wdGlvbnMpLnNvcnQoKS5mb3JFYWNoKGZ1bmN0aW9uIChrZXkpIHtcbiAgICAgICAgICAgIHBhcnRzLnB1c2goXCJcIiArIChldmVudE9wdGlvbnNba2V5XSA/IFwiXCIgOiBcIiFcIikgKyBrZXkpO1xuICAgICAgICB9KTtcbiAgICAgICAgcmV0dXJuIHBhcnRzLmpvaW4oXCI6XCIpO1xuICAgIH07XG4gICAgcmV0dXJuIERpc3BhdGNoZXI7XG59KCkpO1xuZXhwb3J0IHsgRGlzcGF0Y2hlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9ZGlzcGF0Y2hlci5qcy5tYXAiLCJ2YXIgRXZlbnRMaXN0ZW5lciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBFdmVudExpc3RlbmVyKGV2ZW50VGFyZ2V0LCBldmVudE5hbWUsIGV2ZW50T3B0aW9ucykge1xuICAgICAgICB0aGlzLmV2ZW50VGFyZ2V0ID0gZXZlbnRUYXJnZXQ7XG4gICAgICAgIHRoaXMuZXZlbnROYW1lID0gZXZlbnROYW1lO1xuICAgICAgICB0aGlzLmV2ZW50T3B0aW9ucyA9IGV2ZW50T3B0aW9ucztcbiAgICAgICAgdGhpcy51bm9yZGVyZWRCaW5kaW5ncyA9IG5ldyBTZXQoKTtcbiAgICB9XG4gICAgRXZlbnRMaXN0ZW5lci5wcm90b3R5cGUuY29ubmVjdCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5ldmVudFRhcmdldC5hZGRFdmVudExpc3RlbmVyKHRoaXMuZXZlbnROYW1lLCB0aGlzLCB0aGlzLmV2ZW50T3B0aW9ucyk7XG4gICAgfTtcbiAgICBFdmVudExpc3RlbmVyLnByb3RvdHlwZS5kaXNjb25uZWN0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmV2ZW50VGFyZ2V0LnJlbW92ZUV2ZW50TGlzdGVuZXIodGhpcy5ldmVudE5hbWUsIHRoaXMsIHRoaXMuZXZlbnRPcHRpb25zKTtcbiAgICB9O1xuICAgIC8vIEJpbmRpbmcgb2JzZXJ2ZXIgZGVsZWdhdGVcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIEV2ZW50TGlzdGVuZXIucHJvdG90eXBlLmJpbmRpbmdDb25uZWN0ZWQgPSBmdW5jdGlvbiAoYmluZGluZykge1xuICAgICAgICB0aGlzLnVub3JkZXJlZEJpbmRpbmdzLmFkZChiaW5kaW5nKTtcbiAgICB9O1xuICAgIC8qKiBAaGlkZGVuICovXG4gICAgRXZlbnRMaXN0ZW5lci5wcm90b3R5cGUuYmluZGluZ0Rpc2Nvbm5lY3RlZCA9IGZ1bmN0aW9uIChiaW5kaW5nKSB7XG4gICAgICAgIHRoaXMudW5vcmRlcmVkQmluZGluZ3MuZGVsZXRlKGJpbmRpbmcpO1xuICAgIH07XG4gICAgRXZlbnRMaXN0ZW5lci5wcm90b3R5cGUuaGFuZGxlRXZlbnQgPSBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAgICAgdmFyIGV4dGVuZGVkRXZlbnQgPSBleHRlbmRFdmVudChldmVudCk7XG4gICAgICAgIGZvciAodmFyIF9pID0gMCwgX2EgPSB0aGlzLmJpbmRpbmdzOyBfaSA8IF9hLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgdmFyIGJpbmRpbmcgPSBfYVtfaV07XG4gICAgICAgICAgICBpZiAoZXh0ZW5kZWRFdmVudC5pbW1lZGlhdGVQcm9wYWdhdGlvblN0b3BwZWQpIHtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIGJpbmRpbmcuaGFuZGxlRXZlbnQoZXh0ZW5kZWRFdmVudCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShFdmVudExpc3RlbmVyLnByb3RvdHlwZSwgXCJiaW5kaW5nc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy51bm9yZGVyZWRCaW5kaW5ncykuc29ydChmdW5jdGlvbiAobGVmdCwgcmlnaHQpIHtcbiAgICAgICAgICAgICAgICB2YXIgbGVmdEluZGV4ID0gbGVmdC5pbmRleCwgcmlnaHRJbmRleCA9IHJpZ2h0LmluZGV4O1xuICAgICAgICAgICAgICAgIHJldHVybiBsZWZ0SW5kZXggPCByaWdodEluZGV4ID8gLTEgOiBsZWZ0SW5kZXggPiByaWdodEluZGV4ID8gMSA6IDA7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIHJldHVybiBFdmVudExpc3RlbmVyO1xufSgpKTtcbmV4cG9ydCB7IEV2ZW50TGlzdGVuZXIgfTtcbmZ1bmN0aW9uIGV4dGVuZEV2ZW50KGV2ZW50KSB7XG4gICAgaWYgKFwiaW1tZWRpYXRlUHJvcGFnYXRpb25TdG9wcGVkXCIgaW4gZXZlbnQpIHtcbiAgICAgICAgcmV0dXJuIGV2ZW50O1xuICAgIH1cbiAgICBlbHNlIHtcbiAgICAgICAgdmFyIHN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbl8xID0gZXZlbnQuc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uO1xuICAgICAgICByZXR1cm4gT2JqZWN0LmFzc2lnbihldmVudCwge1xuICAgICAgICAgICAgaW1tZWRpYXRlUHJvcGFnYXRpb25TdG9wcGVkOiBmYWxzZSxcbiAgICAgICAgICAgIHN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbjogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHRoaXMuaW1tZWRpYXRlUHJvcGFnYXRpb25TdG9wcGVkID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICBzdG9wSW1tZWRpYXRlUHJvcGFnYXRpb25fMS5jYWxsKHRoaXMpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICB9XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1ldmVudF9saXN0ZW5lci5qcy5tYXAiLCJ2YXIgR3VpZGUgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gR3VpZGUobG9nZ2VyKSB7XG4gICAgICAgIHRoaXMud2FybmVkS2V5c0J5T2JqZWN0ID0gbmV3IFdlYWtNYXA7XG4gICAgICAgIHRoaXMubG9nZ2VyID0gbG9nZ2VyO1xuICAgIH1cbiAgICBHdWlkZS5wcm90b3R5cGUud2FybiA9IGZ1bmN0aW9uIChvYmplY3QsIGtleSwgbWVzc2FnZSkge1xuICAgICAgICB2YXIgd2FybmVkS2V5cyA9IHRoaXMud2FybmVkS2V5c0J5T2JqZWN0LmdldChvYmplY3QpO1xuICAgICAgICBpZiAoIXdhcm5lZEtleXMpIHtcbiAgICAgICAgICAgIHdhcm5lZEtleXMgPSBuZXcgU2V0O1xuICAgICAgICAgICAgdGhpcy53YXJuZWRLZXlzQnlPYmplY3Quc2V0KG9iamVjdCwgd2FybmVkS2V5cyk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKCF3YXJuZWRLZXlzLmhhcyhrZXkpKSB7XG4gICAgICAgICAgICB3YXJuZWRLZXlzLmFkZChrZXkpO1xuICAgICAgICAgICAgdGhpcy5sb2dnZXIud2FybihtZXNzYWdlLCBvYmplY3QpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICByZXR1cm4gR3VpZGU7XG59KCkpO1xuZXhwb3J0IHsgR3VpZGUgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWd1aWRlLmpzLm1hcCIsImV4cG9ydCB7IEFwcGxpY2F0aW9uIH0gZnJvbSBcIi4vYXBwbGljYXRpb25cIjtcbmV4cG9ydCB7IENvbnRleHQgfSBmcm9tIFwiLi9jb250ZXh0XCI7XG5leHBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSBcIi4vY29udHJvbGxlclwiO1xuZXhwb3J0IHsgZGVmYXVsdFNjaGVtYSB9IGZyb20gXCIuL3NjaGVtYVwiO1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9aW5kZXguanMubWFwIiwiZXhwb3J0IGZ1bmN0aW9uIHJlYWRJbmhlcml0YWJsZVN0YXRpY0FycmF5VmFsdWVzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpIHtcbiAgICB2YXIgYW5jZXN0b3JzID0gZ2V0QW5jZXN0b3JzRm9yQ29uc3RydWN0b3IoY29uc3RydWN0b3IpO1xuICAgIHJldHVybiBBcnJheS5mcm9tKGFuY2VzdG9ycy5yZWR1Y2UoZnVuY3Rpb24gKHZhbHVlcywgY29uc3RydWN0b3IpIHtcbiAgICAgICAgZ2V0T3duU3RhdGljQXJyYXlWYWx1ZXMoY29uc3RydWN0b3IsIHByb3BlcnR5TmFtZSkuZm9yRWFjaChmdW5jdGlvbiAobmFtZSkgeyByZXR1cm4gdmFsdWVzLmFkZChuYW1lKTsgfSk7XG4gICAgICAgIHJldHVybiB2YWx1ZXM7XG4gICAgfSwgbmV3IFNldCkpO1xufVxuZXhwb3J0IGZ1bmN0aW9uIHJlYWRJbmhlcml0YWJsZVN0YXRpY09iamVjdFBhaXJzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpIHtcbiAgICB2YXIgYW5jZXN0b3JzID0gZ2V0QW5jZXN0b3JzRm9yQ29uc3RydWN0b3IoY29uc3RydWN0b3IpO1xuICAgIHJldHVybiBhbmNlc3RvcnMucmVkdWNlKGZ1bmN0aW9uIChwYWlycywgY29uc3RydWN0b3IpIHtcbiAgICAgICAgcGFpcnMucHVzaC5hcHBseShwYWlycywgZ2V0T3duU3RhdGljT2JqZWN0UGFpcnMoY29uc3RydWN0b3IsIHByb3BlcnR5TmFtZSkpO1xuICAgICAgICByZXR1cm4gcGFpcnM7XG4gICAgfSwgW10pO1xufVxuZnVuY3Rpb24gZ2V0QW5jZXN0b3JzRm9yQ29uc3RydWN0b3IoY29uc3RydWN0b3IpIHtcbiAgICB2YXIgYW5jZXN0b3JzID0gW107XG4gICAgd2hpbGUgKGNvbnN0cnVjdG9yKSB7XG4gICAgICAgIGFuY2VzdG9ycy5wdXNoKGNvbnN0cnVjdG9yKTtcbiAgICAgICAgY29uc3RydWN0b3IgPSBPYmplY3QuZ2V0UHJvdG90eXBlT2YoY29uc3RydWN0b3IpO1xuICAgIH1cbiAgICByZXR1cm4gYW5jZXN0b3JzLnJldmVyc2UoKTtcbn1cbmZ1bmN0aW9uIGdldE93blN0YXRpY0FycmF5VmFsdWVzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpIHtcbiAgICB2YXIgZGVmaW5pdGlvbiA9IGNvbnN0cnVjdG9yW3Byb3BlcnR5TmFtZV07XG4gICAgcmV0dXJuIEFycmF5LmlzQXJyYXkoZGVmaW5pdGlvbikgPyBkZWZpbml0aW9uIDogW107XG59XG5mdW5jdGlvbiBnZXRPd25TdGF0aWNPYmplY3RQYWlycyhjb25zdHJ1Y3RvciwgcHJvcGVydHlOYW1lKSB7XG4gICAgdmFyIGRlZmluaXRpb24gPSBjb25zdHJ1Y3Rvcltwcm9wZXJ0eU5hbWVdO1xuICAgIHJldHVybiBkZWZpbml0aW9uID8gT2JqZWN0LmtleXMoZGVmaW5pdGlvbikubWFwKGZ1bmN0aW9uIChrZXkpIHsgcmV0dXJuIFtrZXksIGRlZmluaXRpb25ba2V5XV07IH0pIDogW107XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1pbmhlcml0YWJsZV9zdGF0aWNzLmpzLm1hcCIsImltcG9ydCB7IENvbnRleHQgfSBmcm9tIFwiLi9jb250ZXh0XCI7XG5pbXBvcnQgeyBibGVzc0RlZmluaXRpb24gfSBmcm9tIFwiLi9kZWZpbml0aW9uXCI7XG52YXIgTW9kdWxlID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIE1vZHVsZShhcHBsaWNhdGlvbiwgZGVmaW5pdGlvbikge1xuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uID0gYXBwbGljYXRpb247XG4gICAgICAgIHRoaXMuZGVmaW5pdGlvbiA9IGJsZXNzRGVmaW5pdGlvbihkZWZpbml0aW9uKTtcbiAgICAgICAgdGhpcy5jb250ZXh0c0J5U2NvcGUgPSBuZXcgV2Vha01hcDtcbiAgICAgICAgdGhpcy5jb25uZWN0ZWRDb250ZXh0cyA9IG5ldyBTZXQ7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShNb2R1bGUucHJvdG90eXBlLCBcImlkZW50aWZpZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmRlZmluaXRpb24uaWRlbnRpZmllcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShNb2R1bGUucHJvdG90eXBlLCBcImNvbnRyb2xsZXJDb25zdHJ1Y3RvclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZGVmaW5pdGlvbi5jb250cm9sbGVyQ29uc3RydWN0b3I7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoTW9kdWxlLnByb3RvdHlwZSwgXCJjb250ZXh0c1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5jb25uZWN0ZWRDb250ZXh0cyk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBNb2R1bGUucHJvdG90eXBlLmNvbm5lY3RDb250ZXh0Rm9yU2NvcGUgPSBmdW5jdGlvbiAoc2NvcGUpIHtcbiAgICAgICAgdmFyIGNvbnRleHQgPSB0aGlzLmZldGNoQ29udGV4dEZvclNjb3BlKHNjb3BlKTtcbiAgICAgICAgdGhpcy5jb25uZWN0ZWRDb250ZXh0cy5hZGQoY29udGV4dCk7XG4gICAgICAgIGNvbnRleHQuY29ubmVjdCgpO1xuICAgIH07XG4gICAgTW9kdWxlLnByb3RvdHlwZS5kaXNjb25uZWN0Q29udGV4dEZvclNjb3BlID0gZnVuY3Rpb24gKHNjb3BlKSB7XG4gICAgICAgIHZhciBjb250ZXh0ID0gdGhpcy5jb250ZXh0c0J5U2NvcGUuZ2V0KHNjb3BlKTtcbiAgICAgICAgaWYgKGNvbnRleHQpIHtcbiAgICAgICAgICAgIHRoaXMuY29ubmVjdGVkQ29udGV4dHMuZGVsZXRlKGNvbnRleHQpO1xuICAgICAgICAgICAgY29udGV4dC5kaXNjb25uZWN0KCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIE1vZHVsZS5wcm90b3R5cGUuZmV0Y2hDb250ZXh0Rm9yU2NvcGUgPSBmdW5jdGlvbiAoc2NvcGUpIHtcbiAgICAgICAgdmFyIGNvbnRleHQgPSB0aGlzLmNvbnRleHRzQnlTY29wZS5nZXQoc2NvcGUpO1xuICAgICAgICBpZiAoIWNvbnRleHQpIHtcbiAgICAgICAgICAgIGNvbnRleHQgPSBuZXcgQ29udGV4dCh0aGlzLCBzY29wZSk7XG4gICAgICAgICAgICB0aGlzLmNvbnRleHRzQnlTY29wZS5zZXQoc2NvcGUsIGNvbnRleHQpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBjb250ZXh0O1xuICAgIH07XG4gICAgcmV0dXJuIE1vZHVsZTtcbn0oKSk7XG5leHBvcnQgeyBNb2R1bGUgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPW1vZHVsZS5qcy5tYXAiLCJpbXBvcnQgeyBNb2R1bGUgfSBmcm9tIFwiLi9tb2R1bGVcIjtcbmltcG9ydCB7IE11bHRpbWFwIH0gZnJvbSBcIkBzdGltdWx1cy9tdWx0aW1hcFwiO1xuaW1wb3J0IHsgU2NvcGUgfSBmcm9tIFwiLi9zY29wZVwiO1xuaW1wb3J0IHsgU2NvcGVPYnNlcnZlciB9IGZyb20gXCIuL3Njb3BlX29ic2VydmVyXCI7XG52YXIgUm91dGVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIFJvdXRlcihhcHBsaWNhdGlvbikge1xuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uID0gYXBwbGljYXRpb247XG4gICAgICAgIHRoaXMuc2NvcGVPYnNlcnZlciA9IG5ldyBTY29wZU9ic2VydmVyKHRoaXMuZWxlbWVudCwgdGhpcy5zY2hlbWEsIHRoaXMpO1xuICAgICAgICB0aGlzLnNjb3Blc0J5SWRlbnRpZmllciA9IG5ldyBNdWx0aW1hcDtcbiAgICAgICAgdGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyID0gbmV3IE1hcDtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFJvdXRlci5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYXBwbGljYXRpb24uZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShSb3V0ZXIucHJvdG90eXBlLCBcInNjaGVtYVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYXBwbGljYXRpb24uc2NoZW1hO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFJvdXRlci5wcm90b3R5cGUsIFwibG9nZ2VyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hcHBsaWNhdGlvbi5sb2dnZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoUm91dGVyLnByb3RvdHlwZSwgXCJjb250cm9sbGVyQXR0cmlidXRlXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY2hlbWEuY29udHJvbGxlckF0dHJpYnV0ZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShSb3V0ZXIucHJvdG90eXBlLCBcIm1vZHVsZXNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMubW9kdWxlc0J5SWRlbnRpZmllci52YWx1ZXMoKSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoUm91dGVyLnByb3RvdHlwZSwgXCJjb250ZXh0c1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMubW9kdWxlcy5yZWR1Y2UoZnVuY3Rpb24gKGNvbnRleHRzLCBtb2R1bGUpIHsgcmV0dXJuIGNvbnRleHRzLmNvbmNhdChtb2R1bGUuY29udGV4dHMpOyB9LCBbXSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBSb3V0ZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnNjb3BlT2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICB9O1xuICAgIFJvdXRlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5zY29wZU9ic2VydmVyLnN0b3AoKTtcbiAgICB9O1xuICAgIFJvdXRlci5wcm90b3R5cGUubG9hZERlZmluaXRpb24gPSBmdW5jdGlvbiAoZGVmaW5pdGlvbikge1xuICAgICAgICB0aGlzLnVubG9hZElkZW50aWZpZXIoZGVmaW5pdGlvbi5pZGVudGlmaWVyKTtcbiAgICAgICAgdmFyIG1vZHVsZSA9IG5ldyBNb2R1bGUodGhpcy5hcHBsaWNhdGlvbiwgZGVmaW5pdGlvbik7XG4gICAgICAgIHRoaXMuY29ubmVjdE1vZHVsZShtb2R1bGUpO1xuICAgIH07XG4gICAgUm91dGVyLnByb3RvdHlwZS51bmxvYWRJZGVudGlmaWVyID0gZnVuY3Rpb24gKGlkZW50aWZpZXIpIHtcbiAgICAgICAgdmFyIG1vZHVsZSA9IHRoaXMubW9kdWxlc0J5SWRlbnRpZmllci5nZXQoaWRlbnRpZmllcik7XG4gICAgICAgIGlmIChtb2R1bGUpIHtcbiAgICAgICAgICAgIHRoaXMuZGlzY29ubmVjdE1vZHVsZShtb2R1bGUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBSb3V0ZXIucHJvdG90eXBlLmdldENvbnRleHRGb3JFbGVtZW50QW5kSWRlbnRpZmllciA9IGZ1bmN0aW9uIChlbGVtZW50LCBpZGVudGlmaWVyKSB7XG4gICAgICAgIHZhciBtb2R1bGUgPSB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIuZ2V0KGlkZW50aWZpZXIpO1xuICAgICAgICBpZiAobW9kdWxlKSB7XG4gICAgICAgICAgICByZXR1cm4gbW9kdWxlLmNvbnRleHRzLmZpbmQoZnVuY3Rpb24gKGNvbnRleHQpIHsgcmV0dXJuIGNvbnRleHQuZWxlbWVudCA9PSBlbGVtZW50OyB9KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgLy8gRXJyb3IgaGFuZGxlciBkZWxlZ2F0ZVxuICAgIC8qKiBAaGlkZGVuICovXG4gICAgUm91dGVyLnByb3RvdHlwZS5oYW5kbGVFcnJvciA9IGZ1bmN0aW9uIChlcnJvciwgbWVzc2FnZSwgZGV0YWlsKSB7XG4gICAgICAgIHRoaXMuYXBwbGljYXRpb24uaGFuZGxlRXJyb3IoZXJyb3IsIG1lc3NhZ2UsIGRldGFpbCk7XG4gICAgfTtcbiAgICAvLyBTY29wZSBvYnNlcnZlciBkZWxlZ2F0ZVxuICAgIC8qKiBAaGlkZGVuICovXG4gICAgUm91dGVyLnByb3RvdHlwZS5jcmVhdGVTY29wZUZvckVsZW1lbnRBbmRJZGVudGlmaWVyID0gZnVuY3Rpb24gKGVsZW1lbnQsIGlkZW50aWZpZXIpIHtcbiAgICAgICAgcmV0dXJuIG5ldyBTY29wZSh0aGlzLnNjaGVtYSwgZWxlbWVudCwgaWRlbnRpZmllciwgdGhpcy5sb2dnZXIpO1xuICAgIH07XG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBSb3V0ZXIucHJvdG90eXBlLnNjb3BlQ29ubmVjdGVkID0gZnVuY3Rpb24gKHNjb3BlKSB7XG4gICAgICAgIHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyLmFkZChzY29wZS5pZGVudGlmaWVyLCBzY29wZSk7XG4gICAgICAgIHZhciBtb2R1bGUgPSB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIuZ2V0KHNjb3BlLmlkZW50aWZpZXIpO1xuICAgICAgICBpZiAobW9kdWxlKSB7XG4gICAgICAgICAgICBtb2R1bGUuY29ubmVjdENvbnRleHRGb3JTY29wZShzY29wZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIC8qKiBAaGlkZGVuICovXG4gICAgUm91dGVyLnByb3RvdHlwZS5zY29wZURpc2Nvbm5lY3RlZCA9IGZ1bmN0aW9uIChzY29wZSkge1xuICAgICAgICB0aGlzLnNjb3Blc0J5SWRlbnRpZmllci5kZWxldGUoc2NvcGUuaWRlbnRpZmllciwgc2NvcGUpO1xuICAgICAgICB2YXIgbW9kdWxlID0gdGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyLmdldChzY29wZS5pZGVudGlmaWVyKTtcbiAgICAgICAgaWYgKG1vZHVsZSkge1xuICAgICAgICAgICAgbW9kdWxlLmRpc2Nvbm5lY3RDb250ZXh0Rm9yU2NvcGUoc2NvcGUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICAvLyBNb2R1bGVzXG4gICAgUm91dGVyLnByb3RvdHlwZS5jb25uZWN0TW9kdWxlID0gZnVuY3Rpb24gKG1vZHVsZSkge1xuICAgICAgICB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIuc2V0KG1vZHVsZS5pZGVudGlmaWVyLCBtb2R1bGUpO1xuICAgICAgICB2YXIgc2NvcGVzID0gdGhpcy5zY29wZXNCeUlkZW50aWZpZXIuZ2V0VmFsdWVzRm9yS2V5KG1vZHVsZS5pZGVudGlmaWVyKTtcbiAgICAgICAgc2NvcGVzLmZvckVhY2goZnVuY3Rpb24gKHNjb3BlKSB7IHJldHVybiBtb2R1bGUuY29ubmVjdENvbnRleHRGb3JTY29wZShzY29wZSk7IH0pO1xuICAgIH07XG4gICAgUm91dGVyLnByb3RvdHlwZS5kaXNjb25uZWN0TW9kdWxlID0gZnVuY3Rpb24gKG1vZHVsZSkge1xuICAgICAgICB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIuZGVsZXRlKG1vZHVsZS5pZGVudGlmaWVyKTtcbiAgICAgICAgdmFyIHNjb3BlcyA9IHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyLmdldFZhbHVlc0ZvcktleShtb2R1bGUuaWRlbnRpZmllcik7XG4gICAgICAgIHNjb3Blcy5mb3JFYWNoKGZ1bmN0aW9uIChzY29wZSkgeyByZXR1cm4gbW9kdWxlLmRpc2Nvbm5lY3RDb250ZXh0Rm9yU2NvcGUoc2NvcGUpOyB9KTtcbiAgICB9O1xuICAgIHJldHVybiBSb3V0ZXI7XG59KCkpO1xuZXhwb3J0IHsgUm91dGVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1yb3V0ZXIuanMubWFwIiwiZXhwb3J0IHZhciBkZWZhdWx0U2NoZW1hID0ge1xuICAgIGNvbnRyb2xsZXJBdHRyaWJ1dGU6IFwiZGF0YS1jb250cm9sbGVyXCIsXG4gICAgYWN0aW9uQXR0cmlidXRlOiBcImRhdGEtYWN0aW9uXCIsXG4gICAgdGFyZ2V0QXR0cmlidXRlOiBcImRhdGEtdGFyZ2V0XCJcbn07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1zY2hlbWEuanMubWFwIiwidmFyIF9fc3ByZWFkQXJyYXlzID0gKHRoaXMgJiYgdGhpcy5fX3NwcmVhZEFycmF5cykgfHwgZnVuY3Rpb24gKCkge1xuICAgIGZvciAodmFyIHMgPSAwLCBpID0gMCwgaWwgPSBhcmd1bWVudHMubGVuZ3RoOyBpIDwgaWw7IGkrKykgcyArPSBhcmd1bWVudHNbaV0ubGVuZ3RoO1xuICAgIGZvciAodmFyIHIgPSBBcnJheShzKSwgayA9IDAsIGkgPSAwOyBpIDwgaWw7IGkrKylcbiAgICAgICAgZm9yICh2YXIgYSA9IGFyZ3VtZW50c1tpXSwgaiA9IDAsIGpsID0gYS5sZW5ndGg7IGogPCBqbDsgaisrLCBrKyspXG4gICAgICAgICAgICByW2tdID0gYVtqXTtcbiAgICByZXR1cm4gcjtcbn07XG5pbXBvcnQgeyBDbGFzc01hcCB9IGZyb20gXCIuL2NsYXNzX21hcFwiO1xuaW1wb3J0IHsgRGF0YU1hcCB9IGZyb20gXCIuL2RhdGFfbWFwXCI7XG5pbXBvcnQgeyBHdWlkZSB9IGZyb20gXCIuL2d1aWRlXCI7XG5pbXBvcnQgeyBhdHRyaWJ1dGVWYWx1ZUNvbnRhaW5zVG9rZW4gfSBmcm9tIFwiLi9zZWxlY3RvcnNcIjtcbmltcG9ydCB7IFRhcmdldFNldCB9IGZyb20gXCIuL3RhcmdldF9zZXRcIjtcbnZhciBTY29wZSA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBTY29wZShzY2hlbWEsIGVsZW1lbnQsIGlkZW50aWZpZXIsIGxvZ2dlcikge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB0aGlzLnRhcmdldHMgPSBuZXcgVGFyZ2V0U2V0KHRoaXMpO1xuICAgICAgICB0aGlzLmNsYXNzZXMgPSBuZXcgQ2xhc3NNYXAodGhpcyk7XG4gICAgICAgIHRoaXMuZGF0YSA9IG5ldyBEYXRhTWFwKHRoaXMpO1xuICAgICAgICB0aGlzLmNvbnRhaW5zRWxlbWVudCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgICAgICByZXR1cm4gZWxlbWVudC5jbG9zZXN0KF90aGlzLmNvbnRyb2xsZXJTZWxlY3RvcikgPT09IF90aGlzLmVsZW1lbnQ7XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuc2NoZW1hID0gc2NoZW1hO1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBlbGVtZW50O1xuICAgICAgICB0aGlzLmlkZW50aWZpZXIgPSBpZGVudGlmaWVyO1xuICAgICAgICB0aGlzLmd1aWRlID0gbmV3IEd1aWRlKGxvZ2dlcik7XG4gICAgfVxuICAgIFNjb3BlLnByb3RvdHlwZS5maW5kRWxlbWVudCA9IGZ1bmN0aW9uIChzZWxlY3Rvcikge1xuICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50Lm1hdGNoZXMoc2VsZWN0b3IpXG4gICAgICAgICAgICA/IHRoaXMuZWxlbWVudFxuICAgICAgICAgICAgOiB0aGlzLnF1ZXJ5RWxlbWVudHMoc2VsZWN0b3IpLmZpbmQodGhpcy5jb250YWluc0VsZW1lbnQpO1xuICAgIH07XG4gICAgU2NvcGUucHJvdG90eXBlLmZpbmRBbGxFbGVtZW50cyA9IGZ1bmN0aW9uIChzZWxlY3Rvcikge1xuICAgICAgICByZXR1cm4gX19zcHJlYWRBcnJheXModGhpcy5lbGVtZW50Lm1hdGNoZXMoc2VsZWN0b3IpID8gW3RoaXMuZWxlbWVudF0gOiBbXSwgdGhpcy5xdWVyeUVsZW1lbnRzKHNlbGVjdG9yKS5maWx0ZXIodGhpcy5jb250YWluc0VsZW1lbnQpKTtcbiAgICB9O1xuICAgIFNjb3BlLnByb3RvdHlwZS5xdWVyeUVsZW1lbnRzID0gZnVuY3Rpb24gKHNlbGVjdG9yKSB7XG4gICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMuZWxlbWVudC5xdWVyeVNlbGVjdG9yQWxsKHNlbGVjdG9yKSk7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoU2NvcGUucHJvdG90eXBlLCBcImNvbnRyb2xsZXJTZWxlY3RvclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIGF0dHJpYnV0ZVZhbHVlQ29udGFpbnNUb2tlbih0aGlzLnNjaGVtYS5jb250cm9sbGVyQXR0cmlidXRlLCB0aGlzLmlkZW50aWZpZXIpO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgcmV0dXJuIFNjb3BlO1xufSgpKTtcbmV4cG9ydCB7IFNjb3BlIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1zY29wZS5qcy5tYXAiLCJpbXBvcnQgeyBWYWx1ZUxpc3RPYnNlcnZlciB9IGZyb20gXCJAc3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzXCI7XG52YXIgU2NvcGVPYnNlcnZlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBTY29wZU9ic2VydmVyKGVsZW1lbnQsIHNjaGVtYSwgZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5zY2hlbWEgPSBzY2hlbWE7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy52YWx1ZUxpc3RPYnNlcnZlciA9IG5ldyBWYWx1ZUxpc3RPYnNlcnZlcih0aGlzLmVsZW1lbnQsIHRoaXMuY29udHJvbGxlckF0dHJpYnV0ZSwgdGhpcyk7XG4gICAgICAgIHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyQnlFbGVtZW50ID0gbmV3IFdlYWtNYXA7XG4gICAgICAgIHRoaXMuc2NvcGVSZWZlcmVuY2VDb3VudHMgPSBuZXcgV2Vha01hcDtcbiAgICB9XG4gICAgU2NvcGVPYnNlcnZlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICB9O1xuICAgIFNjb3BlT2JzZXJ2ZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIuc3RvcCgpO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFNjb3BlT2JzZXJ2ZXIucHJvdG90eXBlLCBcImNvbnRyb2xsZXJBdHRyaWJ1dGVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjaGVtYS5jb250cm9sbGVyQXR0cmlidXRlO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgLy8gVmFsdWUgb2JzZXJ2ZXIgZGVsZWdhdGVcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIFNjb3BlT2JzZXJ2ZXIucHJvdG90eXBlLnBhcnNlVmFsdWVGb3JUb2tlbiA9IGZ1bmN0aW9uICh0b2tlbikge1xuICAgICAgICB2YXIgZWxlbWVudCA9IHRva2VuLmVsZW1lbnQsIGlkZW50aWZpZXIgPSB0b2tlbi5jb250ZW50O1xuICAgICAgICB2YXIgc2NvcGVzQnlJZGVudGlmaWVyID0gdGhpcy5mZXRjaFNjb3Blc0J5SWRlbnRpZmllckZvckVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIHZhciBzY29wZSA9IHNjb3Blc0J5SWRlbnRpZmllci5nZXQoaWRlbnRpZmllcik7XG4gICAgICAgIGlmICghc2NvcGUpIHtcbiAgICAgICAgICAgIHNjb3BlID0gdGhpcy5kZWxlZ2F0ZS5jcmVhdGVTY29wZUZvckVsZW1lbnRBbmRJZGVudGlmaWVyKGVsZW1lbnQsIGlkZW50aWZpZXIpO1xuICAgICAgICAgICAgc2NvcGVzQnlJZGVudGlmaWVyLnNldChpZGVudGlmaWVyLCBzY29wZSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHNjb3BlO1xuICAgIH07XG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBTY29wZU9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50TWF0Y2hlZFZhbHVlID0gZnVuY3Rpb24gKGVsZW1lbnQsIHZhbHVlKSB7XG4gICAgICAgIHZhciByZWZlcmVuY2VDb3VudCA9ICh0aGlzLnNjb3BlUmVmZXJlbmNlQ291bnRzLmdldCh2YWx1ZSkgfHwgMCkgKyAxO1xuICAgICAgICB0aGlzLnNjb3BlUmVmZXJlbmNlQ291bnRzLnNldCh2YWx1ZSwgcmVmZXJlbmNlQ291bnQpO1xuICAgICAgICBpZiAocmVmZXJlbmNlQ291bnQgPT0gMSkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zY29wZUNvbm5lY3RlZCh2YWx1ZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIC8qKiBAaGlkZGVuICovXG4gICAgU2NvcGVPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudFVubWF0Y2hlZFZhbHVlID0gZnVuY3Rpb24gKGVsZW1lbnQsIHZhbHVlKSB7XG4gICAgICAgIHZhciByZWZlcmVuY2VDb3VudCA9IHRoaXMuc2NvcGVSZWZlcmVuY2VDb3VudHMuZ2V0KHZhbHVlKTtcbiAgICAgICAgaWYgKHJlZmVyZW5jZUNvdW50KSB7XG4gICAgICAgICAgICB0aGlzLnNjb3BlUmVmZXJlbmNlQ291bnRzLnNldCh2YWx1ZSwgcmVmZXJlbmNlQ291bnQgLSAxKTtcbiAgICAgICAgICAgIGlmIChyZWZlcmVuY2VDb3VudCA9PSAxKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zY29wZURpc2Nvbm5lY3RlZCh2YWx1ZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFNjb3BlT2JzZXJ2ZXIucHJvdG90eXBlLmZldGNoU2NvcGVzQnlJZGVudGlmaWVyRm9yRWxlbWVudCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHZhciBzY29wZXNCeUlkZW50aWZpZXIgPSB0aGlzLnNjb3Blc0J5SWRlbnRpZmllckJ5RWxlbWVudC5nZXQoZWxlbWVudCk7XG4gICAgICAgIGlmICghc2NvcGVzQnlJZGVudGlmaWVyKSB7XG4gICAgICAgICAgICBzY29wZXNCeUlkZW50aWZpZXIgPSBuZXcgTWFwO1xuICAgICAgICAgICAgdGhpcy5zY29wZXNCeUlkZW50aWZpZXJCeUVsZW1lbnQuc2V0KGVsZW1lbnQsIHNjb3Blc0J5SWRlbnRpZmllcik7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHNjb3Blc0J5SWRlbnRpZmllcjtcbiAgICB9O1xuICAgIHJldHVybiBTY29wZU9ic2VydmVyO1xufSgpKTtcbmV4cG9ydCB7IFNjb3BlT2JzZXJ2ZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXNjb3BlX29ic2VydmVyLmpzLm1hcCIsIi8qKiBAaGlkZGVuICovXG5leHBvcnQgZnVuY3Rpb24gYXR0cmlidXRlVmFsdWVDb250YWluc1Rva2VuKGF0dHJpYnV0ZU5hbWUsIHRva2VuKSB7XG4gICAgcmV0dXJuIFwiW1wiICsgYXR0cmlidXRlTmFtZSArIFwifj1cXFwiXCIgKyB0b2tlbiArIFwiXFxcIl1cIjtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXNlbGVjdG9ycy5qcy5tYXAiLCJleHBvcnQgZnVuY3Rpb24gY2FtZWxpemUodmFsdWUpIHtcbiAgICByZXR1cm4gdmFsdWUucmVwbGFjZSgvKD86W18tXSkoW2EtejAtOV0pL2csIGZ1bmN0aW9uIChfLCBjaGFyKSB7IHJldHVybiBjaGFyLnRvVXBwZXJDYXNlKCk7IH0pO1xufVxuZXhwb3J0IGZ1bmN0aW9uIGNhcGl0YWxpemUodmFsdWUpIHtcbiAgICByZXR1cm4gdmFsdWUuY2hhckF0KDApLnRvVXBwZXJDYXNlKCkgKyB2YWx1ZS5zbGljZSgxKTtcbn1cbmV4cG9ydCBmdW5jdGlvbiBkYXNoZXJpemUodmFsdWUpIHtcbiAgICByZXR1cm4gdmFsdWUucmVwbGFjZSgvKFtBLVpdKS9nLCBmdW5jdGlvbiAoXywgY2hhcikgeyByZXR1cm4gXCItXCIgKyBjaGFyLnRvTG93ZXJDYXNlKCk7IH0pO1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9c3RyaW5nX2hlbHBlcnMuanMubWFwIiwiaW1wb3J0IHsgcmVhZEluaGVyaXRhYmxlU3RhdGljQXJyYXlWYWx1ZXMgfSBmcm9tIFwiLi9pbmhlcml0YWJsZV9zdGF0aWNzXCI7XG5pbXBvcnQgeyBjYXBpdGFsaXplIH0gZnJvbSBcIi4vc3RyaW5nX2hlbHBlcnNcIjtcbi8qKiBAaGlkZGVuICovXG5leHBvcnQgZnVuY3Rpb24gVGFyZ2V0UHJvcGVydGllc0JsZXNzaW5nKGNvbnN0cnVjdG9yKSB7XG4gICAgdmFyIHRhcmdldHMgPSByZWFkSW5oZXJpdGFibGVTdGF0aWNBcnJheVZhbHVlcyhjb25zdHJ1Y3RvciwgXCJ0YXJnZXRzXCIpO1xuICAgIHJldHVybiB0YXJnZXRzLnJlZHVjZShmdW5jdGlvbiAocHJvcGVydGllcywgdGFyZ2V0RGVmaW5pdGlvbikge1xuICAgICAgICByZXR1cm4gT2JqZWN0LmFzc2lnbihwcm9wZXJ0aWVzLCBwcm9wZXJ0aWVzRm9yVGFyZ2V0RGVmaW5pdGlvbih0YXJnZXREZWZpbml0aW9uKSk7XG4gICAgfSwge30pO1xufVxuZnVuY3Rpb24gcHJvcGVydGllc0ZvclRhcmdldERlZmluaXRpb24obmFtZSkge1xuICAgIHZhciBfYTtcbiAgICByZXR1cm4gX2EgPSB7fSxcbiAgICAgICAgX2FbbmFtZSArIFwiVGFyZ2V0XCJdID0ge1xuICAgICAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgdmFyIHRhcmdldCA9IHRoaXMudGFyZ2V0cy5maW5kKG5hbWUpO1xuICAgICAgICAgICAgICAgIGlmICh0YXJnZXQpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHRhcmdldDtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihcIk1pc3NpbmcgdGFyZ2V0IGVsZW1lbnQgXFxcIlwiICsgdGhpcy5pZGVudGlmaWVyICsgXCIuXCIgKyBuYW1lICsgXCJcXFwiXCIpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgX2FbbmFtZSArIFwiVGFyZ2V0c1wiXSA9IHtcbiAgICAgICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLnRhcmdldHMuZmluZEFsbChuYW1lKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgX2FbXCJoYXNcIiArIGNhcGl0YWxpemUobmFtZSkgKyBcIlRhcmdldFwiXSA9IHtcbiAgICAgICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLnRhcmdldHMuaGFzKG5hbWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBfYTtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXRhcmdldF9wcm9wZXJ0aWVzLmpzLm1hcCIsInZhciBfX3NwcmVhZEFycmF5cyA9ICh0aGlzICYmIHRoaXMuX19zcHJlYWRBcnJheXMpIHx8IGZ1bmN0aW9uICgpIHtcbiAgICBmb3IgKHZhciBzID0gMCwgaSA9IDAsIGlsID0gYXJndW1lbnRzLmxlbmd0aDsgaSA8IGlsOyBpKyspIHMgKz0gYXJndW1lbnRzW2ldLmxlbmd0aDtcbiAgICBmb3IgKHZhciByID0gQXJyYXkocyksIGsgPSAwLCBpID0gMDsgaSA8IGlsOyBpKyspXG4gICAgICAgIGZvciAodmFyIGEgPSBhcmd1bWVudHNbaV0sIGogPSAwLCBqbCA9IGEubGVuZ3RoOyBqIDwgamw7IGorKywgaysrKVxuICAgICAgICAgICAgcltrXSA9IGFbal07XG4gICAgcmV0dXJuIHI7XG59O1xuaW1wb3J0IHsgYXR0cmlidXRlVmFsdWVDb250YWluc1Rva2VuIH0gZnJvbSBcIi4vc2VsZWN0b3JzXCI7XG52YXIgVGFyZ2V0U2V0ID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIFRhcmdldFNldChzY29wZSkge1xuICAgICAgICB0aGlzLnNjb3BlID0gc2NvcGU7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShUYXJnZXRTZXQucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVGFyZ2V0U2V0LnByb3RvdHlwZSwgXCJpZGVudGlmaWVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5pZGVudGlmaWVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFRhcmdldFNldC5wcm90b3R5cGUsIFwic2NoZW1hXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5zY2hlbWE7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmhhcyA9IGZ1bmN0aW9uICh0YXJnZXROYW1lKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmZpbmQodGFyZ2V0TmFtZSkgIT0gbnVsbDtcbiAgICB9O1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuZmluZCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdmFyIHRhcmdldE5hbWVzID0gW107XG4gICAgICAgIGZvciAodmFyIF9pID0gMDsgX2kgPCBhcmd1bWVudHMubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICB0YXJnZXROYW1lc1tfaV0gPSBhcmd1bWVudHNbX2ldO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiB0YXJnZXROYW1lcy5yZWR1Y2UoZnVuY3Rpb24gKHRhcmdldCwgdGFyZ2V0TmFtZSkge1xuICAgICAgICAgICAgcmV0dXJuIHRhcmdldFxuICAgICAgICAgICAgICAgIHx8IF90aGlzLmZpbmRUYXJnZXQodGFyZ2V0TmFtZSlcbiAgICAgICAgICAgICAgICB8fCBfdGhpcy5maW5kTGVnYWN5VGFyZ2V0KHRhcmdldE5hbWUpO1xuICAgICAgICB9LCB1bmRlZmluZWQpO1xuICAgIH07XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5maW5kQWxsID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB2YXIgdGFyZ2V0TmFtZXMgPSBbXTtcbiAgICAgICAgZm9yICh2YXIgX2kgPSAwOyBfaSA8IGFyZ3VtZW50cy5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgIHRhcmdldE5hbWVzW19pXSA9IGFyZ3VtZW50c1tfaV07XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHRhcmdldE5hbWVzLnJlZHVjZShmdW5jdGlvbiAodGFyZ2V0cywgdGFyZ2V0TmFtZSkgeyByZXR1cm4gX19zcHJlYWRBcnJheXModGFyZ2V0cywgX3RoaXMuZmluZEFsbFRhcmdldHModGFyZ2V0TmFtZSksIF90aGlzLmZpbmRBbGxMZWdhY3lUYXJnZXRzKHRhcmdldE5hbWUpKTsgfSwgW10pO1xuICAgIH07XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5maW5kVGFyZ2V0ID0gZnVuY3Rpb24gKHRhcmdldE5hbWUpIHtcbiAgICAgICAgdmFyIHNlbGVjdG9yID0gdGhpcy5nZXRTZWxlY3RvckZvclRhcmdldE5hbWUodGFyZ2V0TmFtZSk7XG4gICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmZpbmRFbGVtZW50KHNlbGVjdG9yKTtcbiAgICB9O1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuZmluZEFsbFRhcmdldHMgPSBmdW5jdGlvbiAodGFyZ2V0TmFtZSkge1xuICAgICAgICB2YXIgc2VsZWN0b3IgPSB0aGlzLmdldFNlbGVjdG9yRm9yVGFyZ2V0TmFtZSh0YXJnZXROYW1lKTtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZmluZEFsbEVsZW1lbnRzKHNlbGVjdG9yKTtcbiAgICB9O1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuZ2V0U2VsZWN0b3JGb3JUYXJnZXROYW1lID0gZnVuY3Rpb24gKHRhcmdldE5hbWUpIHtcbiAgICAgICAgdmFyIGF0dHJpYnV0ZU5hbWUgPSBcImRhdGEtXCIgKyB0aGlzLmlkZW50aWZpZXIgKyBcIi10YXJnZXRcIjtcbiAgICAgICAgcmV0dXJuIGF0dHJpYnV0ZVZhbHVlQ29udGFpbnNUb2tlbihhdHRyaWJ1dGVOYW1lLCB0YXJnZXROYW1lKTtcbiAgICB9O1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuZmluZExlZ2FjeVRhcmdldCA9IGZ1bmN0aW9uICh0YXJnZXROYW1lKSB7XG4gICAgICAgIHZhciBzZWxlY3RvciA9IHRoaXMuZ2V0TGVnYWN5U2VsZWN0b3JGb3JUYXJnZXROYW1lKHRhcmdldE5hbWUpO1xuICAgICAgICByZXR1cm4gdGhpcy5kZXByZWNhdGUodGhpcy5zY29wZS5maW5kRWxlbWVudChzZWxlY3RvciksIHRhcmdldE5hbWUpO1xuICAgIH07XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5maW5kQWxsTGVnYWN5VGFyZ2V0cyA9IGZ1bmN0aW9uICh0YXJnZXROYW1lKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHZhciBzZWxlY3RvciA9IHRoaXMuZ2V0TGVnYWN5U2VsZWN0b3JGb3JUYXJnZXROYW1lKHRhcmdldE5hbWUpO1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5maW5kQWxsRWxlbWVudHMoc2VsZWN0b3IpLm1hcChmdW5jdGlvbiAoZWxlbWVudCkgeyByZXR1cm4gX3RoaXMuZGVwcmVjYXRlKGVsZW1lbnQsIHRhcmdldE5hbWUpOyB9KTtcbiAgICB9O1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuZ2V0TGVnYWN5U2VsZWN0b3JGb3JUYXJnZXROYW1lID0gZnVuY3Rpb24gKHRhcmdldE5hbWUpIHtcbiAgICAgICAgdmFyIHRhcmdldERlc2NyaXB0b3IgPSB0aGlzLmlkZW50aWZpZXIgKyBcIi5cIiArIHRhcmdldE5hbWU7XG4gICAgICAgIHJldHVybiBhdHRyaWJ1dGVWYWx1ZUNvbnRhaW5zVG9rZW4odGhpcy5zY2hlbWEudGFyZ2V0QXR0cmlidXRlLCB0YXJnZXREZXNjcmlwdG9yKTtcbiAgICB9O1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuZGVwcmVjYXRlID0gZnVuY3Rpb24gKGVsZW1lbnQsIHRhcmdldE5hbWUpIHtcbiAgICAgICAgaWYgKGVsZW1lbnQpIHtcbiAgICAgICAgICAgIHZhciBpZGVudGlmaWVyID0gdGhpcy5pZGVudGlmaWVyO1xuICAgICAgICAgICAgdmFyIGF0dHJpYnV0ZU5hbWUgPSB0aGlzLnNjaGVtYS50YXJnZXRBdHRyaWJ1dGU7XG4gICAgICAgICAgICB0aGlzLmd1aWRlLndhcm4oZWxlbWVudCwgXCJ0YXJnZXQ6XCIgKyB0YXJnZXROYW1lLCBcIlBsZWFzZSByZXBsYWNlIFwiICsgYXR0cmlidXRlTmFtZSArIFwiPVxcXCJcIiArIGlkZW50aWZpZXIgKyBcIi5cIiArIHRhcmdldE5hbWUgKyBcIlxcXCIgd2l0aCBkYXRhLVwiICsgaWRlbnRpZmllciArIFwiLXRhcmdldD1cXFwiXCIgKyB0YXJnZXROYW1lICsgXCJcXFwiLiBcIiArXG4gICAgICAgICAgICAgICAgKFwiVGhlIFwiICsgYXR0cmlidXRlTmFtZSArIFwiIGF0dHJpYnV0ZSBpcyBkZXByZWNhdGVkIGFuZCB3aWxsIGJlIHJlbW92ZWQgaW4gYSBmdXR1cmUgdmVyc2lvbiBvZiBTdGltdWx1cy5cIikpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBlbGVtZW50O1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFRhcmdldFNldC5wcm90b3R5cGUsIFwiZ3VpZGVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmd1aWRlO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgcmV0dXJuIFRhcmdldFNldDtcbn0oKSk7XG5leHBvcnQgeyBUYXJnZXRTZXQgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXRhcmdldF9zZXQuanMubWFwIiwiaW1wb3J0IHsgU3RyaW5nTWFwT2JzZXJ2ZXIgfSBmcm9tIFwiQHN0aW11bHVzL211dGF0aW9uLW9ic2VydmVyc1wiO1xudmFyIFZhbHVlT2JzZXJ2ZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gVmFsdWVPYnNlcnZlcihjb250ZXh0LCByZWNlaXZlcikge1xuICAgICAgICB0aGlzLmNvbnRleHQgPSBjb250ZXh0O1xuICAgICAgICB0aGlzLnJlY2VpdmVyID0gcmVjZWl2ZXI7XG4gICAgICAgIHRoaXMuc3RyaW5nTWFwT2JzZXJ2ZXIgPSBuZXcgU3RyaW5nTWFwT2JzZXJ2ZXIodGhpcy5lbGVtZW50LCB0aGlzKTtcbiAgICAgICAgdGhpcy52YWx1ZURlc2NyaXB0b3JNYXAgPSB0aGlzLmNvbnRyb2xsZXIudmFsdWVEZXNjcmlwdG9yTWFwO1xuICAgICAgICB0aGlzLmludm9rZUNoYW5nZWRDYWxsYmFja3NGb3JEZWZhdWx0VmFsdWVzKCk7XG4gICAgfVxuICAgIFZhbHVlT2JzZXJ2ZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnN0cmluZ01hcE9ic2VydmVyLnN0YXJ0KCk7XG4gICAgfTtcbiAgICBWYWx1ZU9ic2VydmVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnN0cmluZ01hcE9ic2VydmVyLnN0b3AoKTtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShWYWx1ZU9ic2VydmVyLnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVmFsdWVPYnNlcnZlci5wcm90b3R5cGUsIFwiY29udHJvbGxlclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5jb250cm9sbGVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgLy8gU3RyaW5nIG1hcCBvYnNlcnZlciBkZWxlZ2F0ZVxuICAgIFZhbHVlT2JzZXJ2ZXIucHJvdG90eXBlLmdldFN0cmluZ01hcEtleUZvckF0dHJpYnV0ZSA9IGZ1bmN0aW9uIChhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGlmIChhdHRyaWJ1dGVOYW1lIGluIHRoaXMudmFsdWVEZXNjcmlwdG9yTWFwKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy52YWx1ZURlc2NyaXB0b3JNYXBbYXR0cmlidXRlTmFtZV0ubmFtZTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgVmFsdWVPYnNlcnZlci5wcm90b3R5cGUuc3RyaW5nTWFwVmFsdWVDaGFuZ2VkID0gZnVuY3Rpb24gKGF0dHJpYnV0ZVZhbHVlLCBuYW1lKSB7XG4gICAgICAgIHRoaXMuaW52b2tlQ2hhbmdlZENhbGxiYWNrRm9yVmFsdWUobmFtZSk7XG4gICAgfTtcbiAgICBWYWx1ZU9ic2VydmVyLnByb3RvdHlwZS5pbnZva2VDaGFuZ2VkQ2FsbGJhY2tzRm9yRGVmYXVsdFZhbHVlcyA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgZm9yICh2YXIgX2kgPSAwLCBfYSA9IHRoaXMudmFsdWVEZXNjcmlwdG9yczsgX2kgPCBfYS5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgIHZhciBfYiA9IF9hW19pXSwga2V5ID0gX2Iua2V5LCBuYW1lXzEgPSBfYi5uYW1lLCBkZWZhdWx0VmFsdWUgPSBfYi5kZWZhdWx0VmFsdWU7XG4gICAgICAgICAgICBpZiAoZGVmYXVsdFZhbHVlICE9IHVuZGVmaW5lZCAmJiAhdGhpcy5jb250cm9sbGVyLmRhdGEuaGFzKGtleSkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmludm9rZUNoYW5nZWRDYWxsYmFja0ZvclZhbHVlKG5hbWVfMSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFZhbHVlT2JzZXJ2ZXIucHJvdG90eXBlLmludm9rZUNoYW5nZWRDYWxsYmFja0ZvclZhbHVlID0gZnVuY3Rpb24gKG5hbWUpIHtcbiAgICAgICAgdmFyIG1ldGhvZE5hbWUgPSBuYW1lICsgXCJDaGFuZ2VkXCI7XG4gICAgICAgIHZhciBtZXRob2QgPSB0aGlzLnJlY2VpdmVyW21ldGhvZE5hbWVdO1xuICAgICAgICBpZiAodHlwZW9mIG1ldGhvZCA9PSBcImZ1bmN0aW9uXCIpIHtcbiAgICAgICAgICAgIHZhciB2YWx1ZSA9IHRoaXMucmVjZWl2ZXJbbmFtZV07XG4gICAgICAgICAgICBtZXRob2QuY2FsbCh0aGlzLnJlY2VpdmVyLCB2YWx1ZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShWYWx1ZU9ic2VydmVyLnByb3RvdHlwZSwgXCJ2YWx1ZURlc2NyaXB0b3JzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgdmFsdWVEZXNjcmlwdG9yTWFwID0gdGhpcy52YWx1ZURlc2NyaXB0b3JNYXA7XG4gICAgICAgICAgICByZXR1cm4gT2JqZWN0LmtleXModmFsdWVEZXNjcmlwdG9yTWFwKS5tYXAoZnVuY3Rpb24gKGtleSkgeyByZXR1cm4gdmFsdWVEZXNjcmlwdG9yTWFwW2tleV07IH0pO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgcmV0dXJuIFZhbHVlT2JzZXJ2ZXI7XG59KCkpO1xuZXhwb3J0IHsgVmFsdWVPYnNlcnZlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9dmFsdWVfb2JzZXJ2ZXIuanMubWFwIiwiaW1wb3J0IHsgcmVhZEluaGVyaXRhYmxlU3RhdGljT2JqZWN0UGFpcnMgfSBmcm9tIFwiLi9pbmhlcml0YWJsZV9zdGF0aWNzXCI7XG5pbXBvcnQgeyBjYW1lbGl6ZSwgY2FwaXRhbGl6ZSwgZGFzaGVyaXplIH0gZnJvbSBcIi4vc3RyaW5nX2hlbHBlcnNcIjtcbi8qKiBAaGlkZGVuICovXG5leHBvcnQgZnVuY3Rpb24gVmFsdWVQcm9wZXJ0aWVzQmxlc3NpbmcoY29uc3RydWN0b3IpIHtcbiAgICB2YXIgdmFsdWVEZWZpbml0aW9uUGFpcnMgPSByZWFkSW5oZXJpdGFibGVTdGF0aWNPYmplY3RQYWlycyhjb25zdHJ1Y3RvciwgXCJ2YWx1ZXNcIik7XG4gICAgdmFyIHByb3BlcnR5RGVzY3JpcHRvck1hcCA9IHtcbiAgICAgICAgdmFsdWVEZXNjcmlwdG9yTWFwOiB7XG4gICAgICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICAgICAgICAgIHJldHVybiB2YWx1ZURlZmluaXRpb25QYWlycy5yZWR1Y2UoZnVuY3Rpb24gKHJlc3VsdCwgdmFsdWVEZWZpbml0aW9uUGFpcikge1xuICAgICAgICAgICAgICAgICAgICB2YXIgX2E7XG4gICAgICAgICAgICAgICAgICAgIHZhciB2YWx1ZURlc2NyaXB0b3IgPSBwYXJzZVZhbHVlRGVmaW5pdGlvblBhaXIodmFsdWVEZWZpbml0aW9uUGFpcik7XG4gICAgICAgICAgICAgICAgICAgIHZhciBhdHRyaWJ1dGVOYW1lID0gX3RoaXMuZGF0YS5nZXRBdHRyaWJ1dGVOYW1lRm9yS2V5KHZhbHVlRGVzY3JpcHRvci5rZXkpO1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gT2JqZWN0LmFzc2lnbihyZXN1bHQsIChfYSA9IHt9LCBfYVthdHRyaWJ1dGVOYW1lXSA9IHZhbHVlRGVzY3JpcHRvciwgX2EpKTtcbiAgICAgICAgICAgICAgICB9LCB7fSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIHJldHVybiB2YWx1ZURlZmluaXRpb25QYWlycy5yZWR1Y2UoZnVuY3Rpb24gKHByb3BlcnRpZXMsIHZhbHVlRGVmaW5pdGlvblBhaXIpIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24ocHJvcGVydGllcywgcHJvcGVydGllc0ZvclZhbHVlRGVmaW5pdGlvblBhaXIodmFsdWVEZWZpbml0aW9uUGFpcikpO1xuICAgIH0sIHByb3BlcnR5RGVzY3JpcHRvck1hcCk7XG59XG4vKiogQGhpZGRlbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIHByb3BlcnRpZXNGb3JWYWx1ZURlZmluaXRpb25QYWlyKHZhbHVlRGVmaW5pdGlvblBhaXIpIHtcbiAgICB2YXIgX2E7XG4gICAgdmFyIGRlZmluaXRpb24gPSBwYXJzZVZhbHVlRGVmaW5pdGlvblBhaXIodmFsdWVEZWZpbml0aW9uUGFpcik7XG4gICAgdmFyIHR5cGUgPSBkZWZpbml0aW9uLnR5cGUsIGtleSA9IGRlZmluaXRpb24ua2V5LCBuYW1lID0gZGVmaW5pdGlvbi5uYW1lO1xuICAgIHZhciByZWFkID0gcmVhZGVyc1t0eXBlXSwgd3JpdGUgPSB3cml0ZXJzW3R5cGVdIHx8IHdyaXRlcnMuZGVmYXVsdDtcbiAgICByZXR1cm4gX2EgPSB7fSxcbiAgICAgICAgX2FbbmFtZV0gPSB7XG4gICAgICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB2YXIgdmFsdWUgPSB0aGlzLmRhdGEuZ2V0KGtleSk7XG4gICAgICAgICAgICAgICAgaWYgKHZhbHVlICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiByZWFkKHZhbHVlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBkZWZpbml0aW9uLmRlZmF1bHRWYWx1ZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9LFxuICAgICAgICAgICAgc2V0OiBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgICAgICAgICBpZiAodmFsdWUgPT09IHVuZGVmaW5lZCkge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmRhdGEuZGVsZXRlKGtleSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmRhdGEuc2V0KGtleSwgd3JpdGUodmFsdWUpKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIF9hW1wiaGFzXCIgKyBjYXBpdGFsaXplKG5hbWUpXSA9IHtcbiAgICAgICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLmRhdGEuaGFzKGtleSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIF9hO1xufVxuZnVuY3Rpb24gcGFyc2VWYWx1ZURlZmluaXRpb25QYWlyKF9hKSB7XG4gICAgdmFyIHRva2VuID0gX2FbMF0sIHR5cGVDb25zdGFudCA9IF9hWzFdO1xuICAgIHZhciB0eXBlID0gcGFyc2VWYWx1ZVR5cGVDb25zdGFudCh0eXBlQ29uc3RhbnQpO1xuICAgIHJldHVybiB2YWx1ZURlc2NyaXB0b3JGb3JUb2tlbkFuZFR5cGUodG9rZW4sIHR5cGUpO1xufVxuZnVuY3Rpb24gcGFyc2VWYWx1ZVR5cGVDb25zdGFudCh0eXBlQ29uc3RhbnQpIHtcbiAgICBzd2l0Y2ggKHR5cGVDb25zdGFudCkge1xuICAgICAgICBjYXNlIEFycmF5OiByZXR1cm4gXCJhcnJheVwiO1xuICAgICAgICBjYXNlIEJvb2xlYW46IHJldHVybiBcImJvb2xlYW5cIjtcbiAgICAgICAgY2FzZSBOdW1iZXI6IHJldHVybiBcIm51bWJlclwiO1xuICAgICAgICBjYXNlIE9iamVjdDogcmV0dXJuIFwib2JqZWN0XCI7XG4gICAgICAgIGNhc2UgU3RyaW5nOiByZXR1cm4gXCJzdHJpbmdcIjtcbiAgICB9XG4gICAgdGhyb3cgbmV3IEVycm9yKFwiVW5rbm93biB2YWx1ZSB0eXBlIGNvbnN0YW50IFxcXCJcIiArIHR5cGVDb25zdGFudCArIFwiXFxcIlwiKTtcbn1cbmZ1bmN0aW9uIHZhbHVlRGVzY3JpcHRvckZvclRva2VuQW5kVHlwZSh0b2tlbiwgdHlwZSkge1xuICAgIHZhciBrZXkgPSBkYXNoZXJpemUodG9rZW4pICsgXCItdmFsdWVcIjtcbiAgICByZXR1cm4ge1xuICAgICAgICB0eXBlOiB0eXBlLFxuICAgICAgICBrZXk6IGtleSxcbiAgICAgICAgbmFtZTogY2FtZWxpemUoa2V5KSxcbiAgICAgICAgZ2V0IGRlZmF1bHRWYWx1ZSgpIHsgcmV0dXJuIGRlZmF1bHRWYWx1ZXNCeVR5cGVbdHlwZV07IH1cbiAgICB9O1xufVxudmFyIGRlZmF1bHRWYWx1ZXNCeVR5cGUgPSB7XG4gICAgZ2V0IGFycmF5KCkgeyByZXR1cm4gW107IH0sXG4gICAgYm9vbGVhbjogZmFsc2UsXG4gICAgbnVtYmVyOiAwLFxuICAgIGdldCBvYmplY3QoKSB7IHJldHVybiB7fTsgfSxcbiAgICBzdHJpbmc6IFwiXCJcbn07XG52YXIgcmVhZGVycyA9IHtcbiAgICBhcnJheTogZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgIHZhciBhcnJheSA9IEpTT04ucGFyc2UodmFsdWUpO1xuICAgICAgICBpZiAoIUFycmF5LmlzQXJyYXkoYXJyYXkpKSB7XG4gICAgICAgICAgICB0aHJvdyBuZXcgVHlwZUVycm9yKFwiRXhwZWN0ZWQgYXJyYXlcIik7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGFycmF5O1xuICAgIH0sXG4gICAgYm9vbGVhbjogZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgIHJldHVybiAhKHZhbHVlID09IFwiMFwiIHx8IHZhbHVlID09IFwiZmFsc2VcIik7XG4gICAgfSxcbiAgICBudW1iZXI6IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICByZXR1cm4gcGFyc2VGbG9hdCh2YWx1ZSk7XG4gICAgfSxcbiAgICBvYmplY3Q6IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICB2YXIgb2JqZWN0ID0gSlNPTi5wYXJzZSh2YWx1ZSk7XG4gICAgICAgIGlmIChvYmplY3QgPT09IG51bGwgfHwgdHlwZW9mIG9iamVjdCAhPSBcIm9iamVjdFwiIHx8IEFycmF5LmlzQXJyYXkob2JqZWN0KSkge1xuICAgICAgICAgICAgdGhyb3cgbmV3IFR5cGVFcnJvcihcIkV4cGVjdGVkIG9iamVjdFwiKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gb2JqZWN0O1xuICAgIH0sXG4gICAgc3RyaW5nOiBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgcmV0dXJuIHZhbHVlO1xuICAgIH1cbn07XG52YXIgd3JpdGVycyA9IHtcbiAgICBkZWZhdWx0OiB3cml0ZVN0cmluZyxcbiAgICBhcnJheTogd3JpdGVKU09OLFxuICAgIG9iamVjdDogd3JpdGVKU09OXG59O1xuZnVuY3Rpb24gd3JpdGVKU09OKHZhbHVlKSB7XG4gICAgcmV0dXJuIEpTT04uc3RyaW5naWZ5KHZhbHVlKTtcbn1cbmZ1bmN0aW9uIHdyaXRlU3RyaW5nKHZhbHVlKSB7XG4gICAgcmV0dXJuIFwiXCIgKyB2YWx1ZTtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXZhbHVlX3Byb3BlcnRpZXMuanMubWFwIiwiZXhwb3J0ICogZnJvbSBcIi4vaW5kZXhlZF9tdWx0aW1hcFwiO1xuZXhwb3J0ICogZnJvbSBcIi4vbXVsdGltYXBcIjtcbmV4cG9ydCAqIGZyb20gXCIuL3NldF9vcGVyYXRpb25zXCI7XG4vLyMgc291cmNlTWFwcGluZ1VSTD1pbmRleC5qcy5tYXAiLCJ2YXIgX19leHRlbmRzID0gKHRoaXMgJiYgdGhpcy5fX2V4dGVuZHMpIHx8IChmdW5jdGlvbiAoKSB7XG4gICAgdmFyIGV4dGVuZFN0YXRpY3MgPSBmdW5jdGlvbiAoZCwgYikge1xuICAgICAgICBleHRlbmRTdGF0aWNzID0gT2JqZWN0LnNldFByb3RvdHlwZU9mIHx8XG4gICAgICAgICAgICAoeyBfX3Byb3RvX186IFtdIH0gaW5zdGFuY2VvZiBBcnJheSAmJiBmdW5jdGlvbiAoZCwgYikgeyBkLl9fcHJvdG9fXyA9IGI7IH0pIHx8XG4gICAgICAgICAgICBmdW5jdGlvbiAoZCwgYikgeyBmb3IgKHZhciBwIGluIGIpIGlmIChiLmhhc093blByb3BlcnR5KHApKSBkW3BdID0gYltwXTsgfTtcbiAgICAgICAgcmV0dXJuIGV4dGVuZFN0YXRpY3MoZCwgYik7XG4gICAgfTtcbiAgICByZXR1cm4gZnVuY3Rpb24gKGQsIGIpIHtcbiAgICAgICAgZXh0ZW5kU3RhdGljcyhkLCBiKTtcbiAgICAgICAgZnVuY3Rpb24gX18oKSB7IHRoaXMuY29uc3RydWN0b3IgPSBkOyB9XG4gICAgICAgIGQucHJvdG90eXBlID0gYiA9PT0gbnVsbCA/IE9iamVjdC5jcmVhdGUoYikgOiAoX18ucHJvdG90eXBlID0gYi5wcm90b3R5cGUsIG5ldyBfXygpKTtcbiAgICB9O1xufSkoKTtcbmltcG9ydCB7IE11bHRpbWFwIH0gZnJvbSBcIi4vbXVsdGltYXBcIjtcbmltcG9ydCB7IGFkZCwgZGVsIH0gZnJvbSBcIi4vc2V0X29wZXJhdGlvbnNcIjtcbnZhciBJbmRleGVkTXVsdGltYXAgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoX3N1cGVyKSB7XG4gICAgX19leHRlbmRzKEluZGV4ZWRNdWx0aW1hcCwgX3N1cGVyKTtcbiAgICBmdW5jdGlvbiBJbmRleGVkTXVsdGltYXAoKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IF9zdXBlci5jYWxsKHRoaXMpIHx8IHRoaXM7XG4gICAgICAgIF90aGlzLmtleXNCeVZhbHVlID0gbmV3IE1hcDtcbiAgICAgICAgcmV0dXJuIF90aGlzO1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoSW5kZXhlZE11bHRpbWFwLnByb3RvdHlwZSwgXCJ2YWx1ZXNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMua2V5c0J5VmFsdWUua2V5cygpKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIEluZGV4ZWRNdWx0aW1hcC5wcm90b3R5cGUuYWRkID0gZnVuY3Rpb24gKGtleSwgdmFsdWUpIHtcbiAgICAgICAgX3N1cGVyLnByb3RvdHlwZS5hZGQuY2FsbCh0aGlzLCBrZXksIHZhbHVlKTtcbiAgICAgICAgYWRkKHRoaXMua2V5c0J5VmFsdWUsIHZhbHVlLCBrZXkpO1xuICAgIH07XG4gICAgSW5kZXhlZE11bHRpbWFwLnByb3RvdHlwZS5kZWxldGUgPSBmdW5jdGlvbiAoa2V5LCB2YWx1ZSkge1xuICAgICAgICBfc3VwZXIucHJvdG90eXBlLmRlbGV0ZS5jYWxsKHRoaXMsIGtleSwgdmFsdWUpO1xuICAgICAgICBkZWwodGhpcy5rZXlzQnlWYWx1ZSwgdmFsdWUsIGtleSk7XG4gICAgfTtcbiAgICBJbmRleGVkTXVsdGltYXAucHJvdG90eXBlLmhhc1ZhbHVlID0gZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmtleXNCeVZhbHVlLmhhcyh2YWx1ZSk7XG4gICAgfTtcbiAgICBJbmRleGVkTXVsdGltYXAucHJvdG90eXBlLmdldEtleXNGb3JWYWx1ZSA9IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICB2YXIgc2V0ID0gdGhpcy5rZXlzQnlWYWx1ZS5nZXQodmFsdWUpO1xuICAgICAgICByZXR1cm4gc2V0ID8gQXJyYXkuZnJvbShzZXQpIDogW107XG4gICAgfTtcbiAgICByZXR1cm4gSW5kZXhlZE11bHRpbWFwO1xufShNdWx0aW1hcCkpO1xuZXhwb3J0IHsgSW5kZXhlZE11bHRpbWFwIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1pbmRleGVkX211bHRpbWFwLmpzLm1hcCIsImltcG9ydCB7IGFkZCwgZGVsIH0gZnJvbSBcIi4vc2V0X29wZXJhdGlvbnNcIjtcbnZhciBNdWx0aW1hcCA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBNdWx0aW1hcCgpIHtcbiAgICAgICAgdGhpcy52YWx1ZXNCeUtleSA9IG5ldyBNYXAoKTtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KE11bHRpbWFwLnByb3RvdHlwZSwgXCJ2YWx1ZXNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHZhciBzZXRzID0gQXJyYXkuZnJvbSh0aGlzLnZhbHVlc0J5S2V5LnZhbHVlcygpKTtcbiAgICAgICAgICAgIHJldHVybiBzZXRzLnJlZHVjZShmdW5jdGlvbiAodmFsdWVzLCBzZXQpIHsgcmV0dXJuIHZhbHVlcy5jb25jYXQoQXJyYXkuZnJvbShzZXQpKTsgfSwgW10pO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KE11bHRpbWFwLnByb3RvdHlwZSwgXCJzaXplXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgc2V0cyA9IEFycmF5LmZyb20odGhpcy52YWx1ZXNCeUtleS52YWx1ZXMoKSk7XG4gICAgICAgICAgICByZXR1cm4gc2V0cy5yZWR1Y2UoZnVuY3Rpb24gKHNpemUsIHNldCkgeyByZXR1cm4gc2l6ZSArIHNldC5zaXplOyB9LCAwKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE11bHRpbWFwLnByb3RvdHlwZS5hZGQgPSBmdW5jdGlvbiAoa2V5LCB2YWx1ZSkge1xuICAgICAgICBhZGQodGhpcy52YWx1ZXNCeUtleSwga2V5LCB2YWx1ZSk7XG4gICAgfTtcbiAgICBNdWx0aW1hcC5wcm90b3R5cGUuZGVsZXRlID0gZnVuY3Rpb24gKGtleSwgdmFsdWUpIHtcbiAgICAgICAgZGVsKHRoaXMudmFsdWVzQnlLZXksIGtleSwgdmFsdWUpO1xuICAgIH07XG4gICAgTXVsdGltYXAucHJvdG90eXBlLmhhcyA9IGZ1bmN0aW9uIChrZXksIHZhbHVlKSB7XG4gICAgICAgIHZhciB2YWx1ZXMgPSB0aGlzLnZhbHVlc0J5S2V5LmdldChrZXkpO1xuICAgICAgICByZXR1cm4gdmFsdWVzICE9IG51bGwgJiYgdmFsdWVzLmhhcyh2YWx1ZSk7XG4gICAgfTtcbiAgICBNdWx0aW1hcC5wcm90b3R5cGUuaGFzS2V5ID0gZnVuY3Rpb24gKGtleSkge1xuICAgICAgICByZXR1cm4gdGhpcy52YWx1ZXNCeUtleS5oYXMoa2V5KTtcbiAgICB9O1xuICAgIE11bHRpbWFwLnByb3RvdHlwZS5oYXNWYWx1ZSA9IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICB2YXIgc2V0cyA9IEFycmF5LmZyb20odGhpcy52YWx1ZXNCeUtleS52YWx1ZXMoKSk7XG4gICAgICAgIHJldHVybiBzZXRzLnNvbWUoZnVuY3Rpb24gKHNldCkgeyByZXR1cm4gc2V0Lmhhcyh2YWx1ZSk7IH0pO1xuICAgIH07XG4gICAgTXVsdGltYXAucHJvdG90eXBlLmdldFZhbHVlc0ZvcktleSA9IGZ1bmN0aW9uIChrZXkpIHtcbiAgICAgICAgdmFyIHZhbHVlcyA9IHRoaXMudmFsdWVzQnlLZXkuZ2V0KGtleSk7XG4gICAgICAgIHJldHVybiB2YWx1ZXMgPyBBcnJheS5mcm9tKHZhbHVlcykgOiBbXTtcbiAgICB9O1xuICAgIE11bHRpbWFwLnByb3RvdHlwZS5nZXRLZXlzRm9yVmFsdWUgPSBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy52YWx1ZXNCeUtleSlcbiAgICAgICAgICAgIC5maWx0ZXIoZnVuY3Rpb24gKF9hKSB7XG4gICAgICAgICAgICB2YXIga2V5ID0gX2FbMF0sIHZhbHVlcyA9IF9hWzFdO1xuICAgICAgICAgICAgcmV0dXJuIHZhbHVlcy5oYXModmFsdWUpO1xuICAgICAgICB9KVxuICAgICAgICAgICAgLm1hcChmdW5jdGlvbiAoX2EpIHtcbiAgICAgICAgICAgIHZhciBrZXkgPSBfYVswXSwgdmFsdWVzID0gX2FbMV07XG4gICAgICAgICAgICByZXR1cm4ga2V5O1xuICAgICAgICB9KTtcbiAgICB9O1xuICAgIHJldHVybiBNdWx0aW1hcDtcbn0oKSk7XG5leHBvcnQgeyBNdWx0aW1hcCB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9bXVsdGltYXAuanMubWFwIiwiZXhwb3J0IGZ1bmN0aW9uIGFkZChtYXAsIGtleSwgdmFsdWUpIHtcbiAgICBmZXRjaChtYXAsIGtleSkuYWRkKHZhbHVlKTtcbn1cbmV4cG9ydCBmdW5jdGlvbiBkZWwobWFwLCBrZXksIHZhbHVlKSB7XG4gICAgZmV0Y2gobWFwLCBrZXkpLmRlbGV0ZSh2YWx1ZSk7XG4gICAgcHJ1bmUobWFwLCBrZXkpO1xufVxuZXhwb3J0IGZ1bmN0aW9uIGZldGNoKG1hcCwga2V5KSB7XG4gICAgdmFyIHZhbHVlcyA9IG1hcC5nZXQoa2V5KTtcbiAgICBpZiAoIXZhbHVlcykge1xuICAgICAgICB2YWx1ZXMgPSBuZXcgU2V0KCk7XG4gICAgICAgIG1hcC5zZXQoa2V5LCB2YWx1ZXMpO1xuICAgIH1cbiAgICByZXR1cm4gdmFsdWVzO1xufVxuZXhwb3J0IGZ1bmN0aW9uIHBydW5lKG1hcCwga2V5KSB7XG4gICAgdmFyIHZhbHVlcyA9IG1hcC5nZXQoa2V5KTtcbiAgICBpZiAodmFsdWVzICE9IG51bGwgJiYgdmFsdWVzLnNpemUgPT0gMCkge1xuICAgICAgICBtYXAuZGVsZXRlKGtleSk7XG4gICAgfVxufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9c2V0X29wZXJhdGlvbnMuanMubWFwIiwiaW1wb3J0IHsgRWxlbWVudE9ic2VydmVyIH0gZnJvbSBcIi4vZWxlbWVudF9vYnNlcnZlclwiO1xudmFyIEF0dHJpYnV0ZU9ic2VydmVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIEF0dHJpYnV0ZU9ic2VydmVyKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUsIGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlTmFtZSA9IGF0dHJpYnV0ZU5hbWU7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5lbGVtZW50T2JzZXJ2ZXIgPSBuZXcgRWxlbWVudE9ic2VydmVyKGVsZW1lbnQsIHRoaXMpO1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnRPYnNlcnZlci5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZSwgXCJzZWxlY3RvclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIFwiW1wiICsgdGhpcy5hdHRyaWJ1dGVOYW1lICsgXCJdXCI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudE9ic2VydmVyLnN0YXJ0KCk7XG4gICAgfTtcbiAgICBBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50T2JzZXJ2ZXIuc3RvcCgpO1xuICAgIH07XG4gICAgQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLnJlZnJlc2ggPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudE9ic2VydmVyLnJlZnJlc2goKTtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUsIFwic3RhcnRlZFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudE9ic2VydmVyLnN0YXJ0ZWQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICAvLyBFbGVtZW50IG9ic2VydmVyIGRlbGVnYXRlXG4gICAgQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLm1hdGNoRWxlbWVudCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHJldHVybiBlbGVtZW50Lmhhc0F0dHJpYnV0ZSh0aGlzLmF0dHJpYnV0ZU5hbWUpO1xuICAgIH07XG4gICAgQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLm1hdGNoRWxlbWVudHNJblRyZWUgPSBmdW5jdGlvbiAodHJlZSkge1xuICAgICAgICB2YXIgbWF0Y2ggPSB0aGlzLm1hdGNoRWxlbWVudCh0cmVlKSA/IFt0cmVlXSA6IFtdO1xuICAgICAgICB2YXIgbWF0Y2hlcyA9IEFycmF5LmZyb20odHJlZS5xdWVyeVNlbGVjdG9yQWxsKHRoaXMuc2VsZWN0b3IpKTtcbiAgICAgICAgcmV0dXJuIG1hdGNoLmNvbmNhdChtYXRjaGVzKTtcbiAgICB9O1xuICAgIEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50TWF0Y2hlZCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLmVsZW1lbnRNYXRjaGVkQXR0cmlidXRlKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRNYXRjaGVkQXR0cmlidXRlKGVsZW1lbnQsIHRoaXMuYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50VW5tYXRjaGVkID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuZWxlbWVudFVubWF0Y2hlZEF0dHJpYnV0ZSkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5lbGVtZW50VW5tYXRjaGVkQXR0cmlidXRlKGVsZW1lbnQsIHRoaXMuYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50QXR0cmlidXRlQ2hhbmdlZCA9IGZ1bmN0aW9uIChlbGVtZW50LCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLmVsZW1lbnRBdHRyaWJ1dGVWYWx1ZUNoYW5nZWQgJiYgdGhpcy5hdHRyaWJ1dGVOYW1lID09IGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudEF0dHJpYnV0ZVZhbHVlQ2hhbmdlZChlbGVtZW50LCBhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgcmV0dXJuIEF0dHJpYnV0ZU9ic2VydmVyO1xufSgpKTtcbmV4cG9ydCB7IEF0dHJpYnV0ZU9ic2VydmVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1hdHRyaWJ1dGVfb2JzZXJ2ZXIuanMubWFwIiwidmFyIEVsZW1lbnRPYnNlcnZlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBFbGVtZW50T2JzZXJ2ZXIoZWxlbWVudCwgZGVsZWdhdGUpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5lbGVtZW50cyA9IG5ldyBTZXQ7XG4gICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlciA9IG5ldyBNdXRhdGlvbk9ic2VydmVyKGZ1bmN0aW9uIChtdXRhdGlvbnMpIHsgcmV0dXJuIF90aGlzLnByb2Nlc3NNdXRhdGlvbnMobXV0YXRpb25zKTsgfSk7XG4gICAgfVxuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICghdGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLm9ic2VydmUodGhpcy5lbGVtZW50LCB7IGF0dHJpYnV0ZXM6IHRydWUsIGNoaWxkTGlzdDogdHJ1ZSwgc3VidHJlZTogdHJ1ZSB9KTtcbiAgICAgICAgICAgIHRoaXMucmVmcmVzaCgpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlci50YWtlUmVjb3JkcygpO1xuICAgICAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLmRpc2Nvbm5lY3QoKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnJlZnJlc2ggPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHZhciBtYXRjaGVzID0gbmV3IFNldCh0aGlzLm1hdGNoRWxlbWVudHNJblRyZWUoKSk7XG4gICAgICAgICAgICBmb3IgKHZhciBfaSA9IDAsIF9hID0gQXJyYXkuZnJvbSh0aGlzLmVsZW1lbnRzKTsgX2kgPCBfYS5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgICAgICB2YXIgZWxlbWVudCA9IF9hW19pXTtcbiAgICAgICAgICAgICAgICBpZiAoIW1hdGNoZXMuaGFzKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMucmVtb3ZlRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBmb3IgKHZhciBfYiA9IDAsIF9jID0gQXJyYXkuZnJvbShtYXRjaGVzKTsgX2IgPCBfYy5sZW5ndGg7IF9iKyspIHtcbiAgICAgICAgICAgICAgICB2YXIgZWxlbWVudCA9IF9jW19iXTtcbiAgICAgICAgICAgICAgICB0aGlzLmFkZEVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIC8vIE11dGF0aW9uIHJlY29yZCBwcm9jZXNzaW5nXG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5wcm9jZXNzTXV0YXRpb25zID0gZnVuY3Rpb24gKG11dGF0aW9ucykge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICBmb3IgKHZhciBfaSA9IDAsIG11dGF0aW9uc18xID0gbXV0YXRpb25zOyBfaSA8IG11dGF0aW9uc18xLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgICAgIHZhciBtdXRhdGlvbiA9IG11dGF0aW9uc18xW19pXTtcbiAgICAgICAgICAgICAgICB0aGlzLnByb2Nlc3NNdXRhdGlvbihtdXRhdGlvbik7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUucHJvY2Vzc011dGF0aW9uID0gZnVuY3Rpb24gKG11dGF0aW9uKSB7XG4gICAgICAgIGlmIChtdXRhdGlvbi50eXBlID09IFwiYXR0cmlidXRlc1wiKSB7XG4gICAgICAgICAgICB0aGlzLnByb2Nlc3NBdHRyaWJ1dGVDaGFuZ2UobXV0YXRpb24udGFyZ2V0LCBtdXRhdGlvbi5hdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChtdXRhdGlvbi50eXBlID09IFwiY2hpbGRMaXN0XCIpIHtcbiAgICAgICAgICAgIHRoaXMucHJvY2Vzc1JlbW92ZWROb2RlcyhtdXRhdGlvbi5yZW1vdmVkTm9kZXMpO1xuICAgICAgICAgICAgdGhpcy5wcm9jZXNzQWRkZWROb2RlcyhtdXRhdGlvbi5hZGRlZE5vZGVzKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5wcm9jZXNzQXR0cmlidXRlQ2hhbmdlID0gZnVuY3Rpb24gKG5vZGUsIGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgdmFyIGVsZW1lbnQgPSBub2RlO1xuICAgICAgICBpZiAodGhpcy5lbGVtZW50cy5oYXMoZWxlbWVudCkpIHtcbiAgICAgICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLmVsZW1lbnRBdHRyaWJ1dGVDaGFuZ2VkICYmIHRoaXMubWF0Y2hFbGVtZW50KGVsZW1lbnQpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5lbGVtZW50QXR0cmlidXRlQ2hhbmdlZChlbGVtZW50LCBhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIHRoaXMucmVtb3ZlRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmICh0aGlzLm1hdGNoRWxlbWVudChlbGVtZW50KSkge1xuICAgICAgICAgICAgdGhpcy5hZGRFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnByb2Nlc3NSZW1vdmVkTm9kZXMgPSBmdW5jdGlvbiAobm9kZXMpIHtcbiAgICAgICAgZm9yICh2YXIgX2kgPSAwLCBfYSA9IEFycmF5LmZyb20obm9kZXMpOyBfaSA8IF9hLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgdmFyIG5vZGUgPSBfYVtfaV07XG4gICAgICAgICAgICB2YXIgZWxlbWVudCA9IHRoaXMuZWxlbWVudEZyb21Ob2RlKG5vZGUpO1xuICAgICAgICAgICAgaWYgKGVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnByb2Nlc3NUcmVlKGVsZW1lbnQsIHRoaXMucmVtb3ZlRWxlbWVudCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUucHJvY2Vzc0FkZGVkTm9kZXMgPSBmdW5jdGlvbiAobm9kZXMpIHtcbiAgICAgICAgZm9yICh2YXIgX2kgPSAwLCBfYSA9IEFycmF5LmZyb20obm9kZXMpOyBfaSA8IF9hLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgdmFyIG5vZGUgPSBfYVtfaV07XG4gICAgICAgICAgICB2YXIgZWxlbWVudCA9IHRoaXMuZWxlbWVudEZyb21Ob2RlKG5vZGUpO1xuICAgICAgICAgICAgaWYgKGVsZW1lbnQgJiYgdGhpcy5lbGVtZW50SXNBY3RpdmUoZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnByb2Nlc3NUcmVlKGVsZW1lbnQsIHRoaXMuYWRkRWxlbWVudCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIC8vIEVsZW1lbnQgbWF0Y2hpbmdcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLm1hdGNoRWxlbWVudCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHJldHVybiB0aGlzLmRlbGVnYXRlLm1hdGNoRWxlbWVudChlbGVtZW50KTtcbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUubWF0Y2hFbGVtZW50c0luVHJlZSA9IGZ1bmN0aW9uICh0cmVlKSB7XG4gICAgICAgIGlmICh0cmVlID09PSB2b2lkIDApIHsgdHJlZSA9IHRoaXMuZWxlbWVudDsgfVxuICAgICAgICByZXR1cm4gdGhpcy5kZWxlZ2F0ZS5tYXRjaEVsZW1lbnRzSW5UcmVlKHRyZWUpO1xuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5wcm9jZXNzVHJlZSA9IGZ1bmN0aW9uICh0cmVlLCBwcm9jZXNzb3IpIHtcbiAgICAgICAgZm9yICh2YXIgX2kgPSAwLCBfYSA9IHRoaXMubWF0Y2hFbGVtZW50c0luVHJlZSh0cmVlKTsgX2kgPCBfYS5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgIHZhciBlbGVtZW50ID0gX2FbX2ldO1xuICAgICAgICAgICAgcHJvY2Vzc29yLmNhbGwodGhpcywgZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudEZyb21Ob2RlID0gZnVuY3Rpb24gKG5vZGUpIHtcbiAgICAgICAgaWYgKG5vZGUubm9kZVR5cGUgPT0gTm9kZS5FTEVNRU5UX05PREUpIHtcbiAgICAgICAgICAgIHJldHVybiBub2RlO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRJc0FjdGl2ZSA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIGlmIChlbGVtZW50LmlzQ29ubmVjdGVkICE9IHRoaXMuZWxlbWVudC5pc0Nvbm5lY3RlZCkge1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5jb250YWlucyhlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgLy8gRWxlbWVudCB0cmFja2luZ1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUuYWRkRWxlbWVudCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIGlmICghdGhpcy5lbGVtZW50cy5oYXMoZWxlbWVudCkpIHtcbiAgICAgICAgICAgIGlmICh0aGlzLmVsZW1lbnRJc0FjdGl2ZShlbGVtZW50KSkge1xuICAgICAgICAgICAgICAgIHRoaXMuZWxlbWVudHMuYWRkKGVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLmVsZW1lbnRNYXRjaGVkKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudE1hdGNoZWQoZWxlbWVudCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnJlbW92ZUVsZW1lbnQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICBpZiAodGhpcy5lbGVtZW50cy5oYXMoZWxlbWVudCkpIHtcbiAgICAgICAgICAgIHRoaXMuZWxlbWVudHMuZGVsZXRlKGVsZW1lbnQpO1xuICAgICAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuZWxlbWVudFVubWF0Y2hlZCkge1xuICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudFVubWF0Y2hlZChlbGVtZW50KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgcmV0dXJuIEVsZW1lbnRPYnNlcnZlcjtcbn0oKSk7XG5leHBvcnQgeyBFbGVtZW50T2JzZXJ2ZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWVsZW1lbnRfb2JzZXJ2ZXIuanMubWFwIiwiZXhwb3J0ICogZnJvbSBcIi4vYXR0cmlidXRlX29ic2VydmVyXCI7XG5leHBvcnQgKiBmcm9tIFwiLi9lbGVtZW50X29ic2VydmVyXCI7XG5leHBvcnQgKiBmcm9tIFwiLi9zdHJpbmdfbWFwX29ic2VydmVyXCI7XG5leHBvcnQgKiBmcm9tIFwiLi90b2tlbl9saXN0X29ic2VydmVyXCI7XG5leHBvcnQgKiBmcm9tIFwiLi92YWx1ZV9saXN0X29ic2VydmVyXCI7XG4vLyMgc291cmNlTWFwcGluZ1VSTD1pbmRleC5qcy5tYXAiLCJ2YXIgU3RyaW5nTWFwT2JzZXJ2ZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gU3RyaW5nTWFwT2JzZXJ2ZXIoZWxlbWVudCwgZGVsZWdhdGUpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5zdHJpbmdNYXAgPSBuZXcgTWFwO1xuICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIgPSBuZXcgTXV0YXRpb25PYnNlcnZlcihmdW5jdGlvbiAobXV0YXRpb25zKSB7IHJldHVybiBfdGhpcy5wcm9jZXNzTXV0YXRpb25zKG11dGF0aW9ucyk7IH0pO1xuICAgIH1cbiAgICBTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICghdGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLm9ic2VydmUodGhpcy5lbGVtZW50LCB7IGF0dHJpYnV0ZXM6IHRydWUgfSk7XG4gICAgICAgICAgICB0aGlzLnJlZnJlc2goKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlci50YWtlUmVjb3JkcygpO1xuICAgICAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLmRpc2Nvbm5lY3QoKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUucmVmcmVzaCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgZm9yICh2YXIgX2kgPSAwLCBfYSA9IHRoaXMua25vd25BdHRyaWJ1dGVOYW1lczsgX2kgPCBfYS5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgICAgICB2YXIgYXR0cmlidXRlTmFtZSA9IF9hW19pXTtcbiAgICAgICAgICAgICAgICB0aGlzLnJlZnJlc2hBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIC8vIE11dGF0aW9uIHJlY29yZCBwcm9jZXNzaW5nXG4gICAgU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLnByb2Nlc3NNdXRhdGlvbnMgPSBmdW5jdGlvbiAobXV0YXRpb25zKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGZvciAodmFyIF9pID0gMCwgbXV0YXRpb25zXzEgPSBtdXRhdGlvbnM7IF9pIDwgbXV0YXRpb25zXzEubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICAgICAgdmFyIG11dGF0aW9uID0gbXV0YXRpb25zXzFbX2ldO1xuICAgICAgICAgICAgICAgIHRoaXMucHJvY2Vzc011dGF0aW9uKG11dGF0aW9uKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLnByb2Nlc3NNdXRhdGlvbiA9IGZ1bmN0aW9uIChtdXRhdGlvbikge1xuICAgICAgICB2YXIgYXR0cmlidXRlTmFtZSA9IG11dGF0aW9uLmF0dHJpYnV0ZU5hbWU7XG4gICAgICAgIGlmIChhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgICAgICB0aGlzLnJlZnJlc2hBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIC8vIFN0YXRlIHRyYWNraW5nXG4gICAgU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLnJlZnJlc2hBdHRyaWJ1dGUgPSBmdW5jdGlvbiAoYXR0cmlidXRlTmFtZSkge1xuICAgICAgICB2YXIga2V5ID0gdGhpcy5kZWxlZ2F0ZS5nZXRTdHJpbmdNYXBLZXlGb3JBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIGlmIChrZXkgIT0gbnVsbCkge1xuICAgICAgICAgICAgaWYgKCF0aGlzLnN0cmluZ01hcC5oYXMoYXR0cmlidXRlTmFtZSkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnN0cmluZ01hcEtleUFkZGVkKGtleSwgYXR0cmlidXRlTmFtZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB2YXIgdmFsdWUgPSB0aGlzLmVsZW1lbnQuZ2V0QXR0cmlidXRlKGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICAgICAgaWYgKHRoaXMuc3RyaW5nTWFwLmdldChhdHRyaWJ1dGVOYW1lKSAhPSB2YWx1ZSkge1xuICAgICAgICAgICAgICAgIHRoaXMuc3RyaW5nTWFwVmFsdWVDaGFuZ2VkKHZhbHVlLCBrZXkpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKHZhbHVlID09IG51bGwpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnN0cmluZ01hcC5kZWxldGUoYXR0cmlidXRlTmFtZSk7XG4gICAgICAgICAgICAgICAgdGhpcy5zdHJpbmdNYXBLZXlSZW1vdmVkKGtleSwgYXR0cmlidXRlTmFtZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aGlzLnN0cmluZ01hcC5zZXQoYXR0cmlidXRlTmFtZSwgdmFsdWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICBTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUuc3RyaW5nTWFwS2V5QWRkZWQgPSBmdW5jdGlvbiAoa2V5LCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLnN0cmluZ01hcEtleUFkZGVkKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnN0cmluZ01hcEtleUFkZGVkKGtleSwgYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZS5zdHJpbmdNYXBWYWx1ZUNoYW5nZWQgPSBmdW5jdGlvbiAodmFsdWUsIGtleSkge1xuICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBWYWx1ZUNoYW5nZWQpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuc3RyaW5nTWFwVmFsdWVDaGFuZ2VkKHZhbHVlLCBrZXkpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUuc3RyaW5nTWFwS2V5UmVtb3ZlZCA9IGZ1bmN0aW9uIChrZXksIGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuc3RyaW5nTWFwS2V5UmVtb3ZlZCkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBLZXlSZW1vdmVkKGtleSwgYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUsIFwia25vd25BdHRyaWJ1dGVOYW1lc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIEFycmF5LmZyb20obmV3IFNldCh0aGlzLmN1cnJlbnRBdHRyaWJ1dGVOYW1lcy5jb25jYXQodGhpcy5yZWNvcmRlZEF0dHJpYnV0ZU5hbWVzKSkpO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZSwgXCJjdXJyZW50QXR0cmlidXRlTmFtZXNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMuZWxlbWVudC5hdHRyaWJ1dGVzKS5tYXAoZnVuY3Rpb24gKGF0dHJpYnV0ZSkgeyByZXR1cm4gYXR0cmlidXRlLm5hbWU7IH0pO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZSwgXCJyZWNvcmRlZEF0dHJpYnV0ZU5hbWVzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLnN0cmluZ01hcC5rZXlzKCkpO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgcmV0dXJuIFN0cmluZ01hcE9ic2VydmVyO1xufSgpKTtcbmV4cG9ydCB7IFN0cmluZ01hcE9ic2VydmVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1zdHJpbmdfbWFwX29ic2VydmVyLmpzLm1hcCIsImltcG9ydCB7IEF0dHJpYnV0ZU9ic2VydmVyIH0gZnJvbSBcIi4vYXR0cmlidXRlX29ic2VydmVyXCI7XG5pbXBvcnQgeyBNdWx0aW1hcCB9IGZyb20gXCJAc3RpbXVsdXMvbXVsdGltYXBcIjtcbnZhciBUb2tlbkxpc3RPYnNlcnZlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBUb2tlbkxpc3RPYnNlcnZlcihlbGVtZW50LCBhdHRyaWJ1dGVOYW1lLCBkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyID0gbmV3IEF0dHJpYnV0ZU9ic2VydmVyKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUsIHRoaXMpO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMudG9rZW5zQnlFbGVtZW50ID0gbmV3IE11bHRpbWFwO1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLCBcInN0YXJ0ZWRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyLnN0YXJ0ZWQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyLnN0b3AoKTtcbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS5yZWZyZXNoID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyLnJlZnJlc2goKTtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXIuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUsIFwiYXR0cmlidXRlTmFtZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXIuYXR0cmlidXRlTmFtZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIC8vIEF0dHJpYnV0ZSBvYnNlcnZlciBkZWxlZ2F0ZVxuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50TWF0Y2hlZEF0dHJpYnV0ZSA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHRoaXMudG9rZW5zTWF0Y2hlZCh0aGlzLnJlYWRUb2tlbnNGb3JFbGVtZW50KGVsZW1lbnQpKTtcbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50QXR0cmlidXRlVmFsdWVDaGFuZ2VkID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgdmFyIF9hID0gdGhpcy5yZWZyZXNoVG9rZW5zRm9yRWxlbWVudChlbGVtZW50KSwgdW5tYXRjaGVkVG9rZW5zID0gX2FbMF0sIG1hdGNoZWRUb2tlbnMgPSBfYVsxXTtcbiAgICAgICAgdGhpcy50b2tlbnNVbm1hdGNoZWQodW5tYXRjaGVkVG9rZW5zKTtcbiAgICAgICAgdGhpcy50b2tlbnNNYXRjaGVkKG1hdGNoZWRUb2tlbnMpO1xuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRVbm1hdGNoZWRBdHRyaWJ1dGUgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICB0aGlzLnRva2Vuc1VubWF0Y2hlZCh0aGlzLnRva2Vuc0J5RWxlbWVudC5nZXRWYWx1ZXNGb3JLZXkoZWxlbWVudCkpO1xuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnRva2Vuc01hdGNoZWQgPSBmdW5jdGlvbiAodG9rZW5zKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHRva2Vucy5mb3JFYWNoKGZ1bmN0aW9uICh0b2tlbikgeyByZXR1cm4gX3RoaXMudG9rZW5NYXRjaGVkKHRva2VuKTsgfSk7XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUudG9rZW5zVW5tYXRjaGVkID0gZnVuY3Rpb24gKHRva2Vucykge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB0b2tlbnMuZm9yRWFjaChmdW5jdGlvbiAodG9rZW4pIHsgcmV0dXJuIF90aGlzLnRva2VuVW5tYXRjaGVkKHRva2VuKTsgfSk7XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUudG9rZW5NYXRjaGVkID0gZnVuY3Rpb24gKHRva2VuKSB7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUudG9rZW5NYXRjaGVkKHRva2VuKTtcbiAgICAgICAgdGhpcy50b2tlbnNCeUVsZW1lbnQuYWRkKHRva2VuLmVsZW1lbnQsIHRva2VuKTtcbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS50b2tlblVubWF0Y2hlZCA9IGZ1bmN0aW9uICh0b2tlbikge1xuICAgICAgICB0aGlzLmRlbGVnYXRlLnRva2VuVW5tYXRjaGVkKHRva2VuKTtcbiAgICAgICAgdGhpcy50b2tlbnNCeUVsZW1lbnQuZGVsZXRlKHRva2VuLmVsZW1lbnQsIHRva2VuKTtcbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS5yZWZyZXNoVG9rZW5zRm9yRWxlbWVudCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHZhciBwcmV2aW91c1Rva2VucyA9IHRoaXMudG9rZW5zQnlFbGVtZW50LmdldFZhbHVlc0ZvcktleShlbGVtZW50KTtcbiAgICAgICAgdmFyIGN1cnJlbnRUb2tlbnMgPSB0aGlzLnJlYWRUb2tlbnNGb3JFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICB2YXIgZmlyc3REaWZmZXJpbmdJbmRleCA9IHppcChwcmV2aW91c1Rva2VucywgY3VycmVudFRva2VucylcbiAgICAgICAgICAgIC5maW5kSW5kZXgoZnVuY3Rpb24gKF9hKSB7XG4gICAgICAgICAgICB2YXIgcHJldmlvdXNUb2tlbiA9IF9hWzBdLCBjdXJyZW50VG9rZW4gPSBfYVsxXTtcbiAgICAgICAgICAgIHJldHVybiAhdG9rZW5zQXJlRXF1YWwocHJldmlvdXNUb2tlbiwgY3VycmVudFRva2VuKTtcbiAgICAgICAgfSk7XG4gICAgICAgIGlmIChmaXJzdERpZmZlcmluZ0luZGV4ID09IC0xKSB7XG4gICAgICAgICAgICByZXR1cm4gW1tdLCBbXV07XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gW3ByZXZpb3VzVG9rZW5zLnNsaWNlKGZpcnN0RGlmZmVyaW5nSW5kZXgpLCBjdXJyZW50VG9rZW5zLnNsaWNlKGZpcnN0RGlmZmVyaW5nSW5kZXgpXTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnJlYWRUb2tlbnNGb3JFbGVtZW50ID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgdmFyIGF0dHJpYnV0ZU5hbWUgPSB0aGlzLmF0dHJpYnV0ZU5hbWU7XG4gICAgICAgIHZhciB0b2tlblN0cmluZyA9IGVsZW1lbnQuZ2V0QXR0cmlidXRlKGF0dHJpYnV0ZU5hbWUpIHx8IFwiXCI7XG4gICAgICAgIHJldHVybiBwYXJzZVRva2VuU3RyaW5nKHRva2VuU3RyaW5nLCBlbGVtZW50LCBhdHRyaWJ1dGVOYW1lKTtcbiAgICB9O1xuICAgIHJldHVybiBUb2tlbkxpc3RPYnNlcnZlcjtcbn0oKSk7XG5leHBvcnQgeyBUb2tlbkxpc3RPYnNlcnZlciB9O1xuZnVuY3Rpb24gcGFyc2VUb2tlblN0cmluZyh0b2tlblN0cmluZywgZWxlbWVudCwgYXR0cmlidXRlTmFtZSkge1xuICAgIHJldHVybiB0b2tlblN0cmluZy50cmltKCkuc3BsaXQoL1xccysvKS5maWx0ZXIoZnVuY3Rpb24gKGNvbnRlbnQpIHsgcmV0dXJuIGNvbnRlbnQubGVuZ3RoOyB9KVxuICAgICAgICAubWFwKGZ1bmN0aW9uIChjb250ZW50LCBpbmRleCkgeyByZXR1cm4gKHsgZWxlbWVudDogZWxlbWVudCwgYXR0cmlidXRlTmFtZTogYXR0cmlidXRlTmFtZSwgY29udGVudDogY29udGVudCwgaW5kZXg6IGluZGV4IH0pOyB9KTtcbn1cbmZ1bmN0aW9uIHppcChsZWZ0LCByaWdodCkge1xuICAgIHZhciBsZW5ndGggPSBNYXRoLm1heChsZWZ0Lmxlbmd0aCwgcmlnaHQubGVuZ3RoKTtcbiAgICByZXR1cm4gQXJyYXkuZnJvbSh7IGxlbmd0aDogbGVuZ3RoIH0sIGZ1bmN0aW9uIChfLCBpbmRleCkgeyByZXR1cm4gW2xlZnRbaW5kZXhdLCByaWdodFtpbmRleF1dOyB9KTtcbn1cbmZ1bmN0aW9uIHRva2Vuc0FyZUVxdWFsKGxlZnQsIHJpZ2h0KSB7XG4gICAgcmV0dXJuIGxlZnQgJiYgcmlnaHQgJiYgbGVmdC5pbmRleCA9PSByaWdodC5pbmRleCAmJiBsZWZ0LmNvbnRlbnQgPT0gcmlnaHQuY29udGVudDtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXRva2VuX2xpc3Rfb2JzZXJ2ZXIuanMubWFwIiwiaW1wb3J0IHsgVG9rZW5MaXN0T2JzZXJ2ZXIgfSBmcm9tIFwiLi90b2tlbl9saXN0X29ic2VydmVyXCI7XG52YXIgVmFsdWVMaXN0T2JzZXJ2ZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gVmFsdWVMaXN0T2JzZXJ2ZXIoZWxlbWVudCwgYXR0cmlidXRlTmFtZSwgZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy50b2tlbkxpc3RPYnNlcnZlciA9IG5ldyBUb2tlbkxpc3RPYnNlcnZlcihlbGVtZW50LCBhdHRyaWJ1dGVOYW1lLCB0aGlzKTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLnBhcnNlUmVzdWx0c0J5VG9rZW4gPSBuZXcgV2Vha01hcDtcbiAgICAgICAgdGhpcy52YWx1ZXNCeVRva2VuQnlFbGVtZW50ID0gbmV3IFdlYWtNYXA7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUsIFwic3RhcnRlZFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMudG9rZW5MaXN0T2JzZXJ2ZXIuc3RhcnRlZDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy50b2tlbkxpc3RPYnNlcnZlci5zdGFydCgpO1xuICAgIH07XG4gICAgVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMudG9rZW5MaXN0T2JzZXJ2ZXIuc3RvcCgpO1xuICAgIH07XG4gICAgVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnJlZnJlc2ggPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMudG9rZW5MaXN0T2JzZXJ2ZXIucmVmcmVzaCgpO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy50b2tlbkxpc3RPYnNlcnZlci5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZSwgXCJhdHRyaWJ1dGVOYW1lXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy50b2tlbkxpc3RPYnNlcnZlci5hdHRyaWJ1dGVOYW1lO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnRva2VuTWF0Y2hlZCA9IGZ1bmN0aW9uICh0b2tlbikge1xuICAgICAgICB2YXIgZWxlbWVudCA9IHRva2VuLmVsZW1lbnQ7XG4gICAgICAgIHZhciB2YWx1ZSA9IHRoaXMuZmV0Y2hQYXJzZVJlc3VsdEZvclRva2VuKHRva2VuKS52YWx1ZTtcbiAgICAgICAgaWYgKHZhbHVlKSB7XG4gICAgICAgICAgICB0aGlzLmZldGNoVmFsdWVzQnlUb2tlbkZvckVsZW1lbnQoZWxlbWVudCkuc2V0KHRva2VuLCB2YWx1ZSk7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRNYXRjaGVkVmFsdWUoZWxlbWVudCwgdmFsdWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUudG9rZW5Vbm1hdGNoZWQgPSBmdW5jdGlvbiAodG9rZW4pIHtcbiAgICAgICAgdmFyIGVsZW1lbnQgPSB0b2tlbi5lbGVtZW50O1xuICAgICAgICB2YXIgdmFsdWUgPSB0aGlzLmZldGNoUGFyc2VSZXN1bHRGb3JUb2tlbih0b2tlbikudmFsdWU7XG4gICAgICAgIGlmICh2YWx1ZSkge1xuICAgICAgICAgICAgdGhpcy5mZXRjaFZhbHVlc0J5VG9rZW5Gb3JFbGVtZW50KGVsZW1lbnQpLmRlbGV0ZSh0b2tlbik7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRVbm1hdGNoZWRWYWx1ZShlbGVtZW50LCB2YWx1ZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZS5mZXRjaFBhcnNlUmVzdWx0Rm9yVG9rZW4gPSBmdW5jdGlvbiAodG9rZW4pIHtcbiAgICAgICAgdmFyIHBhcnNlUmVzdWx0ID0gdGhpcy5wYXJzZVJlc3VsdHNCeVRva2VuLmdldCh0b2tlbik7XG4gICAgICAgIGlmICghcGFyc2VSZXN1bHQpIHtcbiAgICAgICAgICAgIHBhcnNlUmVzdWx0ID0gdGhpcy5wYXJzZVRva2VuKHRva2VuKTtcbiAgICAgICAgICAgIHRoaXMucGFyc2VSZXN1bHRzQnlUb2tlbi5zZXQodG9rZW4sIHBhcnNlUmVzdWx0KTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gcGFyc2VSZXN1bHQ7XG4gICAgfTtcbiAgICBWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUuZmV0Y2hWYWx1ZXNCeVRva2VuRm9yRWxlbWVudCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHZhciB2YWx1ZXNCeVRva2VuID0gdGhpcy52YWx1ZXNCeVRva2VuQnlFbGVtZW50LmdldChlbGVtZW50KTtcbiAgICAgICAgaWYgKCF2YWx1ZXNCeVRva2VuKSB7XG4gICAgICAgICAgICB2YWx1ZXNCeVRva2VuID0gbmV3IE1hcDtcbiAgICAgICAgICAgIHRoaXMudmFsdWVzQnlUb2tlbkJ5RWxlbWVudC5zZXQoZWxlbWVudCwgdmFsdWVzQnlUb2tlbik7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHZhbHVlc0J5VG9rZW47XG4gICAgfTtcbiAgICBWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUucGFyc2VUb2tlbiA9IGZ1bmN0aW9uICh0b2tlbikge1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgdmFyIHZhbHVlID0gdGhpcy5kZWxlZ2F0ZS5wYXJzZVZhbHVlRm9yVG9rZW4odG9rZW4pO1xuICAgICAgICAgICAgcmV0dXJuIHsgdmFsdWU6IHZhbHVlIH07XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICByZXR1cm4geyBlcnJvcjogZXJyb3IgfTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgcmV0dXJuIFZhbHVlTGlzdE9ic2VydmVyO1xufSgpKTtcbmV4cG9ydCB7IFZhbHVlTGlzdE9ic2VydmVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD12YWx1ZV9saXN0X29ic2VydmVyLmpzLm1hcCIsIi8qXG5UdXJibyA3LjAuMC1iZXRhLjFcbkNvcHlyaWdodCDCqSAyMDIwIEJhc2VjYW1wLCBMTENcbiAqL1xuKGZ1bmN0aW9uICgpIHtcbiAgICBpZiAod2luZG93LlJlZmxlY3QgPT09IHVuZGVmaW5lZCB8fCB3aW5kb3cuY3VzdG9tRWxlbWVudHMgPT09IHVuZGVmaW5lZCB8fFxuICAgICAgICB3aW5kb3cuY3VzdG9tRWxlbWVudHMucG9seWZpbGxXcmFwRmx1c2hDYWxsYmFjaykge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIGNvbnN0IEJ1aWx0SW5IVE1MRWxlbWVudCA9IEhUTUxFbGVtZW50O1xuICAgIGNvbnN0IHdyYXBwZXJGb3JUaGVOYW1lID0ge1xuICAgICAgICAnSFRNTEVsZW1lbnQnOiBmdW5jdGlvbiBIVE1MRWxlbWVudCgpIHtcbiAgICAgICAgICAgIHJldHVybiBSZWZsZWN0LmNvbnN0cnVjdChCdWlsdEluSFRNTEVsZW1lbnQsIFtdLCB0aGlzLmNvbnN0cnVjdG9yKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgd2luZG93LkhUTUxFbGVtZW50ID1cbiAgICAgICAgd3JhcHBlckZvclRoZU5hbWVbJ0hUTUxFbGVtZW50J107XG4gICAgSFRNTEVsZW1lbnQucHJvdG90eXBlID0gQnVpbHRJbkhUTUxFbGVtZW50LnByb3RvdHlwZTtcbiAgICBIVE1MRWxlbWVudC5wcm90b3R5cGUuY29uc3RydWN0b3IgPSBIVE1MRWxlbWVudDtcbiAgICBPYmplY3Quc2V0UHJvdG90eXBlT2YoSFRNTEVsZW1lbnQsIEJ1aWx0SW5IVE1MRWxlbWVudCk7XG59KSgpO1xuXG5jb25zdCBzdWJtaXR0ZXJzQnlGb3JtID0gbmV3IFdlYWtNYXA7XG5mdW5jdGlvbiBmaW5kU3VibWl0dGVyRnJvbUNsaWNrVGFyZ2V0KHRhcmdldCkge1xuICAgIGNvbnN0IGVsZW1lbnQgPSB0YXJnZXQgaW5zdGFuY2VvZiBFbGVtZW50ID8gdGFyZ2V0IDogdGFyZ2V0IGluc3RhbmNlb2YgTm9kZSA/IHRhcmdldC5wYXJlbnRFbGVtZW50IDogbnVsbDtcbiAgICBjb25zdCBjYW5kaWRhdGUgPSBlbGVtZW50ID8gZWxlbWVudC5jbG9zZXN0KFwiaW5wdXQsIGJ1dHRvblwiKSA6IG51bGw7XG4gICAgcmV0dXJuIChjYW5kaWRhdGUgPT09IG51bGwgfHwgY2FuZGlkYXRlID09PSB2b2lkIDAgPyB2b2lkIDAgOiBjYW5kaWRhdGUuZ2V0QXR0cmlidXRlKFwidHlwZVwiKSkgPT0gXCJzdWJtaXRcIiA/IGNhbmRpZGF0ZSA6IG51bGw7XG59XG5mdW5jdGlvbiBjbGlja0NhcHR1cmVkKGV2ZW50KSB7XG4gICAgY29uc3Qgc3VibWl0dGVyID0gZmluZFN1Ym1pdHRlckZyb21DbGlja1RhcmdldChldmVudC50YXJnZXQpO1xuICAgIGlmIChzdWJtaXR0ZXIgJiYgc3VibWl0dGVyLmZvcm0pIHtcbiAgICAgICAgc3VibWl0dGVyc0J5Rm9ybS5zZXQoc3VibWl0dGVyLmZvcm0sIHN1Ym1pdHRlcik7XG4gICAgfVxufVxuKGZ1bmN0aW9uICgpIHtcbiAgICBpZiAoXCJTdWJtaXRFdmVudFwiIGluIHdpbmRvdylcbiAgICAgICAgcmV0dXJuO1xuICAgIGFkZEV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCBjbGlja0NhcHR1cmVkLCB0cnVlKTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoRXZlbnQucHJvdG90eXBlLCBcInN1Ym1pdHRlclwiLCB7XG4gICAgICAgIGdldCgpIHtcbiAgICAgICAgICAgIGlmICh0aGlzLnR5cGUgPT0gXCJzdWJtaXRcIiAmJiB0aGlzLnRhcmdldCBpbnN0YW5jZW9mIEhUTUxGb3JtRWxlbWVudCkge1xuICAgICAgICAgICAgICAgIHJldHVybiBzdWJtaXR0ZXJzQnlGb3JtLmdldCh0aGlzLnRhcmdldCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9KTtcbn0pKCk7XG5cbmNsYXNzIExvY2F0aW9uIHtcbiAgICBjb25zdHJ1Y3Rvcih1cmwpIHtcbiAgICAgICAgY29uc3QgbGlua1dpdGhBbmNob3IgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwiYVwiKTtcbiAgICAgICAgbGlua1dpdGhBbmNob3IuaHJlZiA9IHVybDtcbiAgICAgICAgdGhpcy5hYnNvbHV0ZVVSTCA9IGxpbmtXaXRoQW5jaG9yLmhyZWY7XG4gICAgICAgIGNvbnN0IGFuY2hvckxlbmd0aCA9IGxpbmtXaXRoQW5jaG9yLmhhc2gubGVuZ3RoO1xuICAgICAgICBpZiAoYW5jaG9yTGVuZ3RoIDwgMikge1xuICAgICAgICAgICAgdGhpcy5yZXF1ZXN0VVJMID0gdGhpcy5hYnNvbHV0ZVVSTDtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMucmVxdWVzdFVSTCA9IHRoaXMuYWJzb2x1dGVVUkwuc2xpY2UoMCwgLWFuY2hvckxlbmd0aCk7XG4gICAgICAgICAgICB0aGlzLmFuY2hvciA9IGxpbmtXaXRoQW5jaG9yLmhhc2guc2xpY2UoMSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RhdGljIGdldCBjdXJyZW50TG9jYXRpb24oKSB7XG4gICAgICAgIHJldHVybiB0aGlzLndyYXAod2luZG93LmxvY2F0aW9uLnRvU3RyaW5nKCkpO1xuICAgIH1cbiAgICBzdGF0aWMgd3JhcChsb2NhdGFibGUpIHtcbiAgICAgICAgaWYgKHR5cGVvZiBsb2NhdGFibGUgPT0gXCJzdHJpbmdcIikge1xuICAgICAgICAgICAgcmV0dXJuIG5ldyB0aGlzKGxvY2F0YWJsZSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAobG9jYXRhYmxlICE9IG51bGwpIHtcbiAgICAgICAgICAgIHJldHVybiBsb2NhdGFibGU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0T3JpZ2luKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hYnNvbHV0ZVVSTC5zcGxpdChcIi9cIiwgMykuam9pbihcIi9cIik7XG4gICAgfVxuICAgIGdldFBhdGgoKSB7XG4gICAgICAgIHJldHVybiAodGhpcy5yZXF1ZXN0VVJMLm1hdGNoKC9cXC9cXC9bXi9dKihcXC9bXj87XSopLykgfHwgW10pWzFdIHx8IFwiL1wiO1xuICAgIH1cbiAgICBnZXRQYXRoQ29tcG9uZW50cygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0UGF0aCgpLnNwbGl0KFwiL1wiKS5zbGljZSgxKTtcbiAgICB9XG4gICAgZ2V0TGFzdFBhdGhDb21wb25lbnQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldFBhdGhDb21wb25lbnRzKCkuc2xpY2UoLTEpWzBdO1xuICAgIH1cbiAgICBnZXRFeHRlbnNpb24oKSB7XG4gICAgICAgIHJldHVybiAodGhpcy5nZXRMYXN0UGF0aENvbXBvbmVudCgpLm1hdGNoKC9cXC5bXi5dKiQvKSB8fCBbXSlbMF0gfHwgXCJcIjtcbiAgICB9XG4gICAgaXNIVE1MKCkge1xuICAgICAgICByZXR1cm4gISF0aGlzLmdldEV4dGVuc2lvbigpLm1hdGNoKC9eKD86fFxcLig/Omh0bXxodG1sfHhodG1sKSkkLyk7XG4gICAgfVxuICAgIGlzUHJlZml4ZWRCeShsb2NhdGlvbikge1xuICAgICAgICBjb25zdCBwcmVmaXhVUkwgPSBnZXRQcmVmaXhVUkwobG9jYXRpb24pO1xuICAgICAgICByZXR1cm4gdGhpcy5pc0VxdWFsVG8obG9jYXRpb24pIHx8IHN0cmluZ1N0YXJ0c1dpdGgodGhpcy5hYnNvbHV0ZVVSTCwgcHJlZml4VVJMKTtcbiAgICB9XG4gICAgaXNFcXVhbFRvKGxvY2F0aW9uKSB7XG4gICAgICAgIHJldHVybiBsb2NhdGlvbiAmJiB0aGlzLmFic29sdXRlVVJMID09PSBsb2NhdGlvbi5hYnNvbHV0ZVVSTDtcbiAgICB9XG4gICAgdG9DYWNoZUtleSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMucmVxdWVzdFVSTDtcbiAgICB9XG4gICAgdG9KU09OKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hYnNvbHV0ZVVSTDtcbiAgICB9XG4gICAgdG9TdHJpbmcoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFic29sdXRlVVJMO1xuICAgIH1cbiAgICB2YWx1ZU9mKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hYnNvbHV0ZVVSTDtcbiAgICB9XG59XG5mdW5jdGlvbiBnZXRQcmVmaXhVUkwobG9jYXRpb24pIHtcbiAgICByZXR1cm4gYWRkVHJhaWxpbmdTbGFzaChsb2NhdGlvbi5nZXRPcmlnaW4oKSArIGxvY2F0aW9uLmdldFBhdGgoKSk7XG59XG5mdW5jdGlvbiBhZGRUcmFpbGluZ1NsYXNoKHVybCkge1xuICAgIHJldHVybiBzdHJpbmdFbmRzV2l0aCh1cmwsIFwiL1wiKSA/IHVybCA6IHVybCArIFwiL1wiO1xufVxuZnVuY3Rpb24gc3RyaW5nU3RhcnRzV2l0aChzdHJpbmcsIHByZWZpeCkge1xuICAgIHJldHVybiBzdHJpbmcuc2xpY2UoMCwgcHJlZml4Lmxlbmd0aCkgPT09IHByZWZpeDtcbn1cbmZ1bmN0aW9uIHN0cmluZ0VuZHNXaXRoKHN0cmluZywgc3VmZml4KSB7XG4gICAgcmV0dXJuIHN0cmluZy5zbGljZSgtc3VmZml4Lmxlbmd0aCkgPT09IHN1ZmZpeDtcbn1cblxuY2xhc3MgRmV0Y2hSZXNwb25zZSB7XG4gICAgY29uc3RydWN0b3IocmVzcG9uc2UpIHtcbiAgICAgICAgdGhpcy5yZXNwb25zZSA9IHJlc3BvbnNlO1xuICAgIH1cbiAgICBnZXQgc3VjY2VlZGVkKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5yZXNwb25zZS5vaztcbiAgICB9XG4gICAgZ2V0IGZhaWxlZCgpIHtcbiAgICAgICAgcmV0dXJuICF0aGlzLnN1Y2NlZWRlZDtcbiAgICB9XG4gICAgZ2V0IHJlZGlyZWN0ZWQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnJlc3BvbnNlLnJlZGlyZWN0ZWQ7XG4gICAgfVxuICAgIGdldCBsb2NhdGlvbigpIHtcbiAgICAgICAgcmV0dXJuIExvY2F0aW9uLndyYXAodGhpcy5yZXNwb25zZS51cmwpO1xuICAgIH1cbiAgICBnZXQgaXNIVE1MKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250ZW50VHlwZSAmJiB0aGlzLmNvbnRlbnRUeXBlLm1hdGNoKC9edGV4dFxcL2h0bWx8XmFwcGxpY2F0aW9uXFwveGh0bWxcXCt4bWwvKTtcbiAgICB9XG4gICAgZ2V0IHN0YXR1c0NvZGUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnJlc3BvbnNlLnN0YXR1cztcbiAgICB9XG4gICAgZ2V0IGNvbnRlbnRUeXBlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5oZWFkZXIoXCJDb250ZW50LVR5cGVcIik7XG4gICAgfVxuICAgIGdldCByZXNwb25zZVRleHQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnJlc3BvbnNlLnRleHQoKTtcbiAgICB9XG4gICAgZ2V0IHJlc3BvbnNlSFRNTCgpIHtcbiAgICAgICAgaWYgKHRoaXMuaXNIVE1MKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5yZXNwb25zZS50ZXh0KCk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gUHJvbWlzZS5yZXNvbHZlKHVuZGVmaW5lZCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgaGVhZGVyKG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMucmVzcG9uc2UuaGVhZGVycy5nZXQobmFtZSk7XG4gICAgfVxufVxuXG5mdW5jdGlvbiBkaXNwYXRjaChldmVudE5hbWUsIHsgdGFyZ2V0LCBjYW5jZWxhYmxlLCBkZXRhaWwgfSA9IHt9KSB7XG4gICAgY29uc3QgZXZlbnQgPSBuZXcgQ3VzdG9tRXZlbnQoZXZlbnROYW1lLCB7IGNhbmNlbGFibGUsIGJ1YmJsZXM6IHRydWUsIGRldGFpbCB9KTtcbiAgICB2b2lkICh0YXJnZXQgfHwgZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50KS5kaXNwYXRjaEV2ZW50KGV2ZW50KTtcbiAgICByZXR1cm4gZXZlbnQ7XG59XG5mdW5jdGlvbiBuZXh0QW5pbWF0aW9uRnJhbWUoKSB7XG4gICAgcmV0dXJuIG5ldyBQcm9taXNlKHJlc29sdmUgPT4gcmVxdWVzdEFuaW1hdGlvbkZyYW1lKCgpID0+IHJlc29sdmUoKSkpO1xufVxuZnVuY3Rpb24gbmV4dE1pY3JvdGFzaygpIHtcbiAgICByZXR1cm4gUHJvbWlzZS5yZXNvbHZlKCk7XG59XG5mdW5jdGlvbiB1bmluZGVudChzdHJpbmdzLCAuLi52YWx1ZXMpIHtcbiAgICBjb25zdCBsaW5lcyA9IGludGVycG9sYXRlKHN0cmluZ3MsIHZhbHVlcykucmVwbGFjZSgvXlxcbi8sIFwiXCIpLnNwbGl0KFwiXFxuXCIpO1xuICAgIGNvbnN0IG1hdGNoID0gbGluZXNbMF0ubWF0Y2goL15cXHMrLyk7XG4gICAgY29uc3QgaW5kZW50ID0gbWF0Y2ggPyBtYXRjaFswXS5sZW5ndGggOiAwO1xuICAgIHJldHVybiBsaW5lcy5tYXAobGluZSA9PiBsaW5lLnNsaWNlKGluZGVudCkpLmpvaW4oXCJcXG5cIik7XG59XG5mdW5jdGlvbiBpbnRlcnBvbGF0ZShzdHJpbmdzLCB2YWx1ZXMpIHtcbiAgICByZXR1cm4gc3RyaW5ncy5yZWR1Y2UoKHJlc3VsdCwgc3RyaW5nLCBpKSA9PiB7XG4gICAgICAgIGNvbnN0IHZhbHVlID0gdmFsdWVzW2ldID09IHVuZGVmaW5lZCA/IFwiXCIgOiB2YWx1ZXNbaV07XG4gICAgICAgIHJldHVybiByZXN1bHQgKyBzdHJpbmcgKyB2YWx1ZTtcbiAgICB9LCBcIlwiKTtcbn1cbmZ1bmN0aW9uIHV1aWQoKSB7XG4gICAgcmV0dXJuIEFycmF5LmFwcGx5KG51bGwsIHsgbGVuZ3RoOiAzNiB9KS5tYXAoKF8sIGkpID0+IHtcbiAgICAgICAgaWYgKGkgPT0gOCB8fCBpID09IDEzIHx8IGkgPT0gMTggfHwgaSA9PSAyMykge1xuICAgICAgICAgICAgcmV0dXJuIFwiLVwiO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKGkgPT0gMTQpIHtcbiAgICAgICAgICAgIHJldHVybiBcIjRcIjtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChpID09IDE5KSB7XG4gICAgICAgICAgICByZXR1cm4gKE1hdGguZmxvb3IoTWF0aC5yYW5kb20oKSAqIDQpICsgOCkudG9TdHJpbmcoMTYpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIE1hdGguZmxvb3IoTWF0aC5yYW5kb20oKSAqIDE1KS50b1N0cmluZygxNik7XG4gICAgICAgIH1cbiAgICB9KS5qb2luKFwiXCIpO1xufVxuXG52YXIgRmV0Y2hNZXRob2Q7XG4oZnVuY3Rpb24gKEZldGNoTWV0aG9kKSB7XG4gICAgRmV0Y2hNZXRob2RbRmV0Y2hNZXRob2RbXCJnZXRcIl0gPSAwXSA9IFwiZ2V0XCI7XG4gICAgRmV0Y2hNZXRob2RbRmV0Y2hNZXRob2RbXCJwb3N0XCJdID0gMV0gPSBcInBvc3RcIjtcbiAgICBGZXRjaE1ldGhvZFtGZXRjaE1ldGhvZFtcInB1dFwiXSA9IDJdID0gXCJwdXRcIjtcbiAgICBGZXRjaE1ldGhvZFtGZXRjaE1ldGhvZFtcInBhdGNoXCJdID0gM10gPSBcInBhdGNoXCI7XG4gICAgRmV0Y2hNZXRob2RbRmV0Y2hNZXRob2RbXCJkZWxldGVcIl0gPSA0XSA9IFwiZGVsZXRlXCI7XG59KShGZXRjaE1ldGhvZCB8fCAoRmV0Y2hNZXRob2QgPSB7fSkpO1xuZnVuY3Rpb24gZmV0Y2hNZXRob2RGcm9tU3RyaW5nKG1ldGhvZCkge1xuICAgIHN3aXRjaCAobWV0aG9kLnRvTG93ZXJDYXNlKCkpIHtcbiAgICAgICAgY2FzZSBcImdldFwiOiByZXR1cm4gRmV0Y2hNZXRob2QuZ2V0O1xuICAgICAgICBjYXNlIFwicG9zdFwiOiByZXR1cm4gRmV0Y2hNZXRob2QucG9zdDtcbiAgICAgICAgY2FzZSBcInB1dFwiOiByZXR1cm4gRmV0Y2hNZXRob2QucHV0O1xuICAgICAgICBjYXNlIFwicGF0Y2hcIjogcmV0dXJuIEZldGNoTWV0aG9kLnBhdGNoO1xuICAgICAgICBjYXNlIFwiZGVsZXRlXCI6IHJldHVybiBGZXRjaE1ldGhvZC5kZWxldGU7XG4gICAgfVxufVxuY2xhc3MgRmV0Y2hSZXF1ZXN0IHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSwgbWV0aG9kLCBsb2NhdGlvbiwgYm9keSkge1xuICAgICAgICB0aGlzLmFib3J0Q29udHJvbGxlciA9IG5ldyBBYm9ydENvbnRyb2xsZXI7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5tZXRob2QgPSBtZXRob2Q7XG4gICAgICAgIHRoaXMubG9jYXRpb24gPSBsb2NhdGlvbjtcbiAgICAgICAgdGhpcy5ib2R5ID0gYm9keTtcbiAgICB9XG4gICAgZ2V0IHVybCgpIHtcbiAgICAgICAgY29uc3QgdXJsID0gdGhpcy5sb2NhdGlvbi5hYnNvbHV0ZVVSTDtcbiAgICAgICAgY29uc3QgcXVlcnkgPSB0aGlzLnBhcmFtcy50b1N0cmluZygpO1xuICAgICAgICBpZiAodGhpcy5pc0lkZW1wb3RlbnQgJiYgcXVlcnkubGVuZ3RoKSB7XG4gICAgICAgICAgICByZXR1cm4gW3VybCwgcXVlcnldLmpvaW4odXJsLmluY2x1ZGVzKFwiP1wiKSA/IFwiJlwiIDogXCI/XCIpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIHVybDtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXQgcGFyYW1zKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5lbnRyaWVzLnJlZHVjZSgocGFyYW1zLCBbbmFtZSwgdmFsdWVdKSA9PiB7XG4gICAgICAgICAgICBwYXJhbXMuYXBwZW5kKG5hbWUsIHZhbHVlLnRvU3RyaW5nKCkpO1xuICAgICAgICAgICAgcmV0dXJuIHBhcmFtcztcbiAgICAgICAgfSwgbmV3IFVSTFNlYXJjaFBhcmFtcyk7XG4gICAgfVxuICAgIGdldCBlbnRyaWVzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5ib2R5ID8gQXJyYXkuZnJvbSh0aGlzLmJvZHkuZW50cmllcygpKSA6IFtdO1xuICAgIH1cbiAgICBjYW5jZWwoKSB7XG4gICAgICAgIHRoaXMuYWJvcnRDb250cm9sbGVyLmFib3J0KCk7XG4gICAgfVxuICAgIGFzeW5jIHBlcmZvcm0oKSB7XG4gICAgICAgIGNvbnN0IHsgZmV0Y2hPcHRpb25zIH0gPSB0aGlzO1xuICAgICAgICBkaXNwYXRjaChcInR1cmJvOmJlZm9yZS1mZXRjaC1yZXF1ZXN0XCIsIHsgZGV0YWlsOiB7IGZldGNoT3B0aW9ucyB9IH0pO1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5yZXF1ZXN0U3RhcnRlZCh0aGlzKTtcbiAgICAgICAgICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2godGhpcy51cmwsIGZldGNoT3B0aW9ucyk7XG4gICAgICAgICAgICByZXR1cm4gYXdhaXQgdGhpcy5yZWNlaXZlKHJlc3BvbnNlKTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUucmVxdWVzdEVycm9yZWQodGhpcywgZXJyb3IpO1xuICAgICAgICAgICAgdGhyb3cgZXJyb3I7XG4gICAgICAgIH1cbiAgICAgICAgZmluYWxseSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnJlcXVlc3RGaW5pc2hlZCh0aGlzKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBhc3luYyByZWNlaXZlKHJlc3BvbnNlKSB7XG4gICAgICAgIGNvbnN0IGZldGNoUmVzcG9uc2UgPSBuZXcgRmV0Y2hSZXNwb25zZShyZXNwb25zZSk7XG4gICAgICAgIGNvbnN0IGV2ZW50ID0gZGlzcGF0Y2goXCJ0dXJibzpiZWZvcmUtZmV0Y2gtcmVzcG9uc2VcIiwgeyBjYW5jZWxhYmxlOiB0cnVlLCBkZXRhaWw6IHsgZmV0Y2hSZXNwb25zZSB9IH0pO1xuICAgICAgICBpZiAoZXZlbnQuZGVmYXVsdFByZXZlbnRlZCkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5yZXF1ZXN0UHJldmVudGVkSGFuZGxpbmdSZXNwb25zZSh0aGlzLCBmZXRjaFJlc3BvbnNlKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChmZXRjaFJlc3BvbnNlLnN1Y2NlZWRlZCkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5yZXF1ZXN0U3VjY2VlZGVkV2l0aFJlc3BvbnNlKHRoaXMsIGZldGNoUmVzcG9uc2UpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5yZXF1ZXN0RmFpbGVkV2l0aFJlc3BvbnNlKHRoaXMsIGZldGNoUmVzcG9uc2UpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBmZXRjaFJlc3BvbnNlO1xuICAgIH1cbiAgICBnZXQgZmV0Y2hPcHRpb25zKCkge1xuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgbWV0aG9kOiBGZXRjaE1ldGhvZFt0aGlzLm1ldGhvZF0udG9VcHBlckNhc2UoKSxcbiAgICAgICAgICAgIGNyZWRlbnRpYWxzOiBcInNhbWUtb3JpZ2luXCIsXG4gICAgICAgICAgICBoZWFkZXJzOiB0aGlzLmhlYWRlcnMsXG4gICAgICAgICAgICByZWRpcmVjdDogXCJmb2xsb3dcIixcbiAgICAgICAgICAgIGJvZHk6IHRoaXMuaXNJZGVtcG90ZW50ID8gdW5kZWZpbmVkIDogdGhpcy5ib2R5LFxuICAgICAgICAgICAgc2lnbmFsOiB0aGlzLmFib3J0U2lnbmFsXG4gICAgICAgIH07XG4gICAgfVxuICAgIGdldCBpc0lkZW1wb3RlbnQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLm1ldGhvZCA9PSBGZXRjaE1ldGhvZC5nZXQ7XG4gICAgfVxuICAgIGdldCBoZWFkZXJzKCkge1xuICAgICAgICByZXR1cm4gT2JqZWN0LmFzc2lnbih7IFwiQWNjZXB0XCI6IFwidGV4dC9odG1sLCBhcHBsaWNhdGlvbi94aHRtbCt4bWxcIiB9LCB0aGlzLmFkZGl0aW9uYWxIZWFkZXJzKTtcbiAgICB9XG4gICAgZ2V0IGFkZGl0aW9uYWxIZWFkZXJzKCkge1xuICAgICAgICBpZiAodHlwZW9mIHRoaXMuZGVsZWdhdGUuYWRkaXRpb25hbEhlYWRlcnNGb3JSZXF1ZXN0ID09IFwiZnVuY3Rpb25cIikge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZGVsZWdhdGUuYWRkaXRpb25hbEhlYWRlcnNGb3JSZXF1ZXN0KHRoaXMpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIHt9O1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldCBhYm9ydFNpZ25hbCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYWJvcnRDb250cm9sbGVyLnNpZ25hbDtcbiAgICB9XG59XG5cbmNsYXNzIEZvcm1JbnRlcmNlcHRvciB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUsIGVsZW1lbnQpIHtcbiAgICAgICAgdGhpcy5zdWJtaXRCdWJibGVkID0gKChldmVudCkgPT4ge1xuICAgICAgICAgICAgaWYgKGV2ZW50LnRhcmdldCBpbnN0YW5jZW9mIEhUTUxGb3JtRWxlbWVudCkge1xuICAgICAgICAgICAgICAgIGNvbnN0IGZvcm0gPSBldmVudC50YXJnZXQ7XG4gICAgICAgICAgICAgICAgY29uc3Qgc3VibWl0dGVyID0gZXZlbnQuc3VibWl0dGVyIHx8IHVuZGVmaW5lZDtcbiAgICAgICAgICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5zaG91bGRJbnRlcmNlcHRGb3JtU3VibWlzc2lvbihmb3JtLCBzdWJtaXR0ZXIpKSB7XG4gICAgICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgICAgIGV2ZW50LnN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbigpO1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmZvcm1TdWJtaXNzaW9uSW50ZXJjZXB0ZWQoZm9ybSwgc3VibWl0dGVyKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICB0aGlzLmVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcInN1Ym1pdFwiLCB0aGlzLnN1Ym1pdEJ1YmJsZWQpO1xuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICB0aGlzLmVsZW1lbnQucmVtb3ZlRXZlbnRMaXN0ZW5lcihcInN1Ym1pdFwiLCB0aGlzLnN1Ym1pdEJ1YmJsZWQpO1xuICAgIH1cbn1cblxudmFyIEZvcm1TdWJtaXNzaW9uU3RhdGU7XG4oZnVuY3Rpb24gKEZvcm1TdWJtaXNzaW9uU3RhdGUpIHtcbiAgICBGb3JtU3VibWlzc2lvblN0YXRlW0Zvcm1TdWJtaXNzaW9uU3RhdGVbXCJpbml0aWFsaXplZFwiXSA9IDBdID0gXCJpbml0aWFsaXplZFwiO1xuICAgIEZvcm1TdWJtaXNzaW9uU3RhdGVbRm9ybVN1Ym1pc3Npb25TdGF0ZVtcInJlcXVlc3RpbmdcIl0gPSAxXSA9IFwicmVxdWVzdGluZ1wiO1xuICAgIEZvcm1TdWJtaXNzaW9uU3RhdGVbRm9ybVN1Ym1pc3Npb25TdGF0ZVtcIndhaXRpbmdcIl0gPSAyXSA9IFwid2FpdGluZ1wiO1xuICAgIEZvcm1TdWJtaXNzaW9uU3RhdGVbRm9ybVN1Ym1pc3Npb25TdGF0ZVtcInJlY2VpdmluZ1wiXSA9IDNdID0gXCJyZWNlaXZpbmdcIjtcbiAgICBGb3JtU3VibWlzc2lvblN0YXRlW0Zvcm1TdWJtaXNzaW9uU3RhdGVbXCJzdG9wcGluZ1wiXSA9IDRdID0gXCJzdG9wcGluZ1wiO1xuICAgIEZvcm1TdWJtaXNzaW9uU3RhdGVbRm9ybVN1Ym1pc3Npb25TdGF0ZVtcInN0b3BwZWRcIl0gPSA1XSA9IFwic3RvcHBlZFwiO1xufSkoRm9ybVN1Ym1pc3Npb25TdGF0ZSB8fCAoRm9ybVN1Ym1pc3Npb25TdGF0ZSA9IHt9KSk7XG5jbGFzcyBGb3JtU3VibWlzc2lvbiB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUsIGZvcm1FbGVtZW50LCBzdWJtaXR0ZXIsIG11c3RSZWRpcmVjdCA9IGZhbHNlKSB7XG4gICAgICAgIHRoaXMuc3RhdGUgPSBGb3JtU3VibWlzc2lvblN0YXRlLmluaXRpYWxpemVkO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMuZm9ybUVsZW1lbnQgPSBmb3JtRWxlbWVudDtcbiAgICAgICAgdGhpcy5mb3JtRGF0YSA9IGJ1aWxkRm9ybURhdGEoZm9ybUVsZW1lbnQsIHN1Ym1pdHRlcik7XG4gICAgICAgIHRoaXMuc3VibWl0dGVyID0gc3VibWl0dGVyO1xuICAgICAgICB0aGlzLmZldGNoUmVxdWVzdCA9IG5ldyBGZXRjaFJlcXVlc3QodGhpcywgdGhpcy5tZXRob2QsIHRoaXMubG9jYXRpb24sIHRoaXMuZm9ybURhdGEpO1xuICAgICAgICB0aGlzLm11c3RSZWRpcmVjdCA9IG11c3RSZWRpcmVjdDtcbiAgICB9XG4gICAgZ2V0IG1ldGhvZCgpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICBjb25zdCBtZXRob2QgPSAoKF9hID0gdGhpcy5zdWJtaXR0ZXIpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5nZXRBdHRyaWJ1dGUoXCJmb3JtbWV0aG9kXCIpKSB8fCB0aGlzLmZvcm1FbGVtZW50Lm1ldGhvZDtcbiAgICAgICAgcmV0dXJuIGZldGNoTWV0aG9kRnJvbVN0cmluZyhtZXRob2QudG9Mb3dlckNhc2UoKSkgfHwgRmV0Y2hNZXRob2QuZ2V0O1xuICAgIH1cbiAgICBnZXQgYWN0aW9uKCkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIHJldHVybiAoKF9hID0gdGhpcy5zdWJtaXR0ZXIpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5nZXRBdHRyaWJ1dGUoXCJmb3JtYWN0aW9uXCIpKSB8fCB0aGlzLmZvcm1FbGVtZW50LmFjdGlvbjtcbiAgICB9XG4gICAgZ2V0IGxvY2F0aW9uKCkge1xuICAgICAgICByZXR1cm4gTG9jYXRpb24ud3JhcCh0aGlzLmFjdGlvbik7XG4gICAgfVxuICAgIGFzeW5jIHN0YXJ0KCkge1xuICAgICAgICBjb25zdCB7IGluaXRpYWxpemVkLCByZXF1ZXN0aW5nIH0gPSBGb3JtU3VibWlzc2lvblN0YXRlO1xuICAgICAgICBpZiAodGhpcy5zdGF0ZSA9PSBpbml0aWFsaXplZCkge1xuICAgICAgICAgICAgdGhpcy5zdGF0ZSA9IHJlcXVlc3Rpbmc7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5mZXRjaFJlcXVlc3QucGVyZm9ybSgpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIGNvbnN0IHsgc3RvcHBpbmcsIHN0b3BwZWQgfSA9IEZvcm1TdWJtaXNzaW9uU3RhdGU7XG4gICAgICAgIGlmICh0aGlzLnN0YXRlICE9IHN0b3BwaW5nICYmIHRoaXMuc3RhdGUgIT0gc3RvcHBlZCkge1xuICAgICAgICAgICAgdGhpcy5zdGF0ZSA9IHN0b3BwaW5nO1xuICAgICAgICAgICAgdGhpcy5mZXRjaFJlcXVlc3QuY2FuY2VsKCk7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBhZGRpdGlvbmFsSGVhZGVyc0ZvclJlcXVlc3QocmVxdWVzdCkge1xuICAgICAgICBjb25zdCBoZWFkZXJzID0ge307XG4gICAgICAgIGlmICh0aGlzLm1ldGhvZCAhPSBGZXRjaE1ldGhvZC5nZXQpIHtcbiAgICAgICAgICAgIGNvbnN0IHRva2VuID0gZ2V0Q29va2llVmFsdWUoZ2V0TWV0YUNvbnRlbnQoXCJjc3JmLXBhcmFtXCIpKSB8fCBnZXRNZXRhQ29udGVudChcImNzcmYtdG9rZW5cIik7XG4gICAgICAgICAgICBpZiAodG9rZW4pIHtcbiAgICAgICAgICAgICAgICBoZWFkZXJzW1wiWC1DU1JGLVRva2VuXCJdID0gdG9rZW47XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGhlYWRlcnM7XG4gICAgfVxuICAgIHJlcXVlc3RTdGFydGVkKHJlcXVlc3QpIHtcbiAgICAgICAgdGhpcy5zdGF0ZSA9IEZvcm1TdWJtaXNzaW9uU3RhdGUud2FpdGluZztcbiAgICAgICAgZGlzcGF0Y2goXCJ0dXJibzpzdWJtaXQtc3RhcnRcIiwgeyB0YXJnZXQ6IHRoaXMuZm9ybUVsZW1lbnQsIGRldGFpbDogeyBmb3JtU3VibWlzc2lvbjogdGhpcyB9IH0pO1xuICAgICAgICB0aGlzLmRlbGVnYXRlLmZvcm1TdWJtaXNzaW9uU3RhcnRlZCh0aGlzKTtcbiAgICB9XG4gICAgcmVxdWVzdFByZXZlbnRlZEhhbmRsaW5nUmVzcG9uc2UocmVxdWVzdCwgcmVzcG9uc2UpIHtcbiAgICAgICAgdGhpcy5yZXN1bHQgPSB7IHN1Y2Nlc3M6IHJlc3BvbnNlLnN1Y2NlZWRlZCwgZmV0Y2hSZXNwb25zZTogcmVzcG9uc2UgfTtcbiAgICB9XG4gICAgcmVxdWVzdFN1Y2NlZWRlZFdpdGhSZXNwb25zZShyZXF1ZXN0LCByZXNwb25zZSkge1xuICAgICAgICBpZiAodGhpcy5yZXF1ZXN0TXVzdFJlZGlyZWN0KHJlcXVlc3QpICYmICFyZXNwb25zZS5yZWRpcmVjdGVkKSB7XG4gICAgICAgICAgICBjb25zdCBlcnJvciA9IG5ldyBFcnJvcihcIkZvcm0gcmVzcG9uc2VzIG11c3QgcmVkaXJlY3QgdG8gYW5vdGhlciBsb2NhdGlvblwiKTtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZm9ybVN1Ym1pc3Npb25FcnJvcmVkKHRoaXMsIGVycm9yKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMuc3RhdGUgPSBGb3JtU3VibWlzc2lvblN0YXRlLnJlY2VpdmluZztcbiAgICAgICAgICAgIHRoaXMucmVzdWx0ID0geyBzdWNjZXNzOiB0cnVlLCBmZXRjaFJlc3BvbnNlOiByZXNwb25zZSB9O1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5mb3JtU3VibWlzc2lvblN1Y2NlZWRlZFdpdGhSZXNwb25zZSh0aGlzLCByZXNwb25zZSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVxdWVzdEZhaWxlZFdpdGhSZXNwb25zZShyZXF1ZXN0LCByZXNwb25zZSkge1xuICAgICAgICB0aGlzLnJlc3VsdCA9IHsgc3VjY2VzczogZmFsc2UsIGZldGNoUmVzcG9uc2U6IHJlc3BvbnNlIH07XG4gICAgICAgIHRoaXMuZGVsZWdhdGUuZm9ybVN1Ym1pc3Npb25GYWlsZWRXaXRoUmVzcG9uc2UodGhpcywgcmVzcG9uc2UpO1xuICAgIH1cbiAgICByZXF1ZXN0RXJyb3JlZChyZXF1ZXN0LCBlcnJvcikge1xuICAgICAgICB0aGlzLnJlc3VsdCA9IHsgc3VjY2VzczogZmFsc2UsIGVycm9yIH07XG4gICAgICAgIHRoaXMuZGVsZWdhdGUuZm9ybVN1Ym1pc3Npb25FcnJvcmVkKHRoaXMsIGVycm9yKTtcbiAgICB9XG4gICAgcmVxdWVzdEZpbmlzaGVkKHJlcXVlc3QpIHtcbiAgICAgICAgdGhpcy5zdGF0ZSA9IEZvcm1TdWJtaXNzaW9uU3RhdGUuc3RvcHBlZDtcbiAgICAgICAgZGlzcGF0Y2goXCJ0dXJibzpzdWJtaXQtZW5kXCIsIHsgdGFyZ2V0OiB0aGlzLmZvcm1FbGVtZW50LCBkZXRhaWw6IE9iamVjdC5hc3NpZ24oeyBmb3JtU3VibWlzc2lvbjogdGhpcyB9LCB0aGlzLnJlc3VsdCkgfSk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUuZm9ybVN1Ym1pc3Npb25GaW5pc2hlZCh0aGlzKTtcbiAgICB9XG4gICAgcmVxdWVzdE11c3RSZWRpcmVjdChyZXF1ZXN0KSB7XG4gICAgICAgIHJldHVybiAhcmVxdWVzdC5pc0lkZW1wb3RlbnQgJiYgdGhpcy5tdXN0UmVkaXJlY3Q7XG4gICAgfVxufVxuZnVuY3Rpb24gYnVpbGRGb3JtRGF0YShmb3JtRWxlbWVudCwgc3VibWl0dGVyKSB7XG4gICAgY29uc3QgZm9ybURhdGEgPSBuZXcgRm9ybURhdGEoZm9ybUVsZW1lbnQpO1xuICAgIGNvbnN0IG5hbWUgPSBzdWJtaXR0ZXIgPT09IG51bGwgfHwgc3VibWl0dGVyID09PSB2b2lkIDAgPyB2b2lkIDAgOiBzdWJtaXR0ZXIuZ2V0QXR0cmlidXRlKFwibmFtZVwiKTtcbiAgICBjb25zdCB2YWx1ZSA9IHN1Ym1pdHRlciA9PT0gbnVsbCB8fCBzdWJtaXR0ZXIgPT09IHZvaWQgMCA/IHZvaWQgMCA6IHN1Ym1pdHRlci5nZXRBdHRyaWJ1dGUoXCJ2YWx1ZVwiKTtcbiAgICBpZiAobmFtZSAmJiBmb3JtRGF0YS5nZXQobmFtZSkgIT0gdmFsdWUpIHtcbiAgICAgICAgZm9ybURhdGEuYXBwZW5kKG5hbWUsIHZhbHVlIHx8IFwiXCIpO1xuICAgIH1cbiAgICByZXR1cm4gZm9ybURhdGE7XG59XG5mdW5jdGlvbiBnZXRDb29raWVWYWx1ZShjb29raWVOYW1lKSB7XG4gICAgaWYgKGNvb2tpZU5hbWUgIT0gbnVsbCkge1xuICAgICAgICBjb25zdCBjb29raWVzID0gZG9jdW1lbnQuY29va2llID8gZG9jdW1lbnQuY29va2llLnNwbGl0KFwiOyBcIikgOiBbXTtcbiAgICAgICAgY29uc3QgY29va2llID0gY29va2llcy5maW5kKChjb29raWUpID0+IGNvb2tpZS5zdGFydHNXaXRoKGNvb2tpZU5hbWUpKTtcbiAgICAgICAgaWYgKGNvb2tpZSkge1xuICAgICAgICAgICAgY29uc3QgdmFsdWUgPSBjb29raWUuc3BsaXQoXCI9XCIpLnNsaWNlKDEpLmpvaW4oXCI9XCIpO1xuICAgICAgICAgICAgcmV0dXJuIHZhbHVlID8gZGVjb2RlVVJJQ29tcG9uZW50KHZhbHVlKSA6IHVuZGVmaW5lZDtcbiAgICAgICAgfVxuICAgIH1cbn1cbmZ1bmN0aW9uIGdldE1ldGFDb250ZW50KG5hbWUpIHtcbiAgICBjb25zdCBlbGVtZW50ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihgbWV0YVtuYW1lPVwiJHtuYW1lfVwiXWApO1xuICAgIHJldHVybiBlbGVtZW50ICYmIGVsZW1lbnQuY29udGVudDtcbn1cblxuY2xhc3MgTGlua0ludGVyY2VwdG9yIHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSwgZWxlbWVudCkge1xuICAgICAgICB0aGlzLmNsaWNrQnViYmxlZCA9IChldmVudCkgPT4ge1xuICAgICAgICAgICAgaWYgKHRoaXMucmVzcG9uZHNUb0V2ZW50VGFyZ2V0KGV2ZW50LnRhcmdldCkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmNsaWNrRXZlbnQgPSBldmVudDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIGRlbGV0ZSB0aGlzLmNsaWNrRXZlbnQ7XG4gICAgICAgICAgICB9XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMubGlua0NsaWNrZWQgPSAoKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBpZiAodGhpcy5jbGlja0V2ZW50ICYmIHRoaXMucmVzcG9uZHNUb0V2ZW50VGFyZ2V0KGV2ZW50LnRhcmdldCkgJiYgZXZlbnQudGFyZ2V0IGluc3RhbmNlb2YgRWxlbWVudCkge1xuICAgICAgICAgICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLnNob3VsZEludGVyY2VwdExpbmtDbGljayhldmVudC50YXJnZXQsIGV2ZW50LmRldGFpbC51cmwpKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuY2xpY2tFdmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmxpbmtDbGlja0ludGVyY2VwdGVkKGV2ZW50LnRhcmdldCwgZXZlbnQuZGV0YWlsLnVybCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZGVsZXRlIHRoaXMuY2xpY2tFdmVudDtcbiAgICAgICAgfSk7XG4gICAgICAgIHRoaXMud2lsbFZpc2l0ID0gKCkgPT4ge1xuICAgICAgICAgICAgZGVsZXRlIHRoaXMuY2xpY2tFdmVudDtcbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBlbGVtZW50O1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50LmFkZEV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCB0aGlzLmNsaWNrQnViYmxlZCk7XG4gICAgICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoXCJ0dXJibzpjbGlja1wiLCB0aGlzLmxpbmtDbGlja2VkKTtcbiAgICAgICAgZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcInR1cmJvOmJlZm9yZS12aXNpdFwiLCB0aGlzLndpbGxWaXNpdCk7XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudC5yZW1vdmVFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgdGhpcy5jbGlja0J1YmJsZWQpO1xuICAgICAgICBkb2N1bWVudC5yZW1vdmVFdmVudExpc3RlbmVyKFwidHVyYm86Y2xpY2tcIiwgdGhpcy5saW5rQ2xpY2tlZCk7XG4gICAgICAgIGRvY3VtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoXCJ0dXJibzpiZWZvcmUtdmlzaXRcIiwgdGhpcy53aWxsVmlzaXQpO1xuICAgIH1cbiAgICByZXNwb25kc1RvRXZlbnRUYXJnZXQodGFyZ2V0KSB7XG4gICAgICAgIGNvbnN0IGVsZW1lbnQgPSB0YXJnZXQgaW5zdGFuY2VvZiBFbGVtZW50XG4gICAgICAgICAgICA/IHRhcmdldFxuICAgICAgICAgICAgOiB0YXJnZXQgaW5zdGFuY2VvZiBOb2RlXG4gICAgICAgICAgICAgICAgPyB0YXJnZXQucGFyZW50RWxlbWVudFxuICAgICAgICAgICAgICAgIDogbnVsbDtcbiAgICAgICAgcmV0dXJuIGVsZW1lbnQgJiYgZWxlbWVudC5jbG9zZXN0KFwidHVyYm8tZnJhbWUsIGh0bWxcIikgPT0gdGhpcy5lbGVtZW50O1xuICAgIH1cbn1cblxuY2xhc3MgRnJhbWVDb250cm9sbGVyIHtcbiAgICBjb25zdHJ1Y3RvcihlbGVtZW50KSB7XG4gICAgICAgIHRoaXMucmVzb2x2ZVZpc2l0UHJvbWlzZSA9ICgpID0+IHsgfTtcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5saW5rSW50ZXJjZXB0b3IgPSBuZXcgTGlua0ludGVyY2VwdG9yKHRoaXMsIHRoaXMuZWxlbWVudCk7XG4gICAgICAgIHRoaXMuZm9ybUludGVyY2VwdG9yID0gbmV3IEZvcm1JbnRlcmNlcHRvcih0aGlzLCB0aGlzLmVsZW1lbnQpO1xuICAgIH1cbiAgICBjb25uZWN0KCkge1xuICAgICAgICB0aGlzLmxpbmtJbnRlcmNlcHRvci5zdGFydCgpO1xuICAgICAgICB0aGlzLmZvcm1JbnRlcmNlcHRvci5zdGFydCgpO1xuICAgIH1cbiAgICBkaXNjb25uZWN0KCkge1xuICAgICAgICB0aGlzLmxpbmtJbnRlcmNlcHRvci5zdG9wKCk7XG4gICAgICAgIHRoaXMuZm9ybUludGVyY2VwdG9yLnN0b3AoKTtcbiAgICB9XG4gICAgc2hvdWxkSW50ZXJjZXB0TGlua0NsaWNrKGVsZW1lbnQsIHVybCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zaG91bGRJbnRlcmNlcHROYXZpZ2F0aW9uKGVsZW1lbnQpO1xuICAgIH1cbiAgICBsaW5rQ2xpY2tJbnRlcmNlcHRlZChlbGVtZW50LCB1cmwpIHtcbiAgICAgICAgdGhpcy5uYXZpZ2F0ZUZyYW1lKGVsZW1lbnQsIHVybCk7XG4gICAgfVxuICAgIHNob3VsZEludGVyY2VwdEZvcm1TdWJtaXNzaW9uKGVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2hvdWxkSW50ZXJjZXB0TmF2aWdhdGlvbihlbGVtZW50KTtcbiAgICB9XG4gICAgZm9ybVN1Ym1pc3Npb25JbnRlcmNlcHRlZChlbGVtZW50LCBzdWJtaXR0ZXIpIHtcbiAgICAgICAgaWYgKHRoaXMuZm9ybVN1Ym1pc3Npb24pIHtcbiAgICAgICAgICAgIHRoaXMuZm9ybVN1Ym1pc3Npb24uc3RvcCgpO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMuZm9ybVN1Ym1pc3Npb24gPSBuZXcgRm9ybVN1Ym1pc3Npb24odGhpcywgZWxlbWVudCwgc3VibWl0dGVyKTtcbiAgICAgICAgaWYgKHRoaXMuZm9ybVN1Ym1pc3Npb24uZmV0Y2hSZXF1ZXN0LmlzSWRlbXBvdGVudCkge1xuICAgICAgICAgICAgdGhpcy5uYXZpZ2F0ZUZyYW1lKGVsZW1lbnQsIHRoaXMuZm9ybVN1Ym1pc3Npb24uZmV0Y2hSZXF1ZXN0LnVybCk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLmZvcm1TdWJtaXNzaW9uLnN0YXJ0KCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgYXN5bmMgdmlzaXQodXJsKSB7XG4gICAgICAgIGNvbnN0IGxvY2F0aW9uID0gTG9jYXRpb24ud3JhcCh1cmwpO1xuICAgICAgICBjb25zdCByZXF1ZXN0ID0gbmV3IEZldGNoUmVxdWVzdCh0aGlzLCBGZXRjaE1ldGhvZC5nZXQsIGxvY2F0aW9uKTtcbiAgICAgICAgcmV0dXJuIG5ldyBQcm9taXNlKHJlc29sdmUgPT4ge1xuICAgICAgICAgICAgdGhpcy5yZXNvbHZlVmlzaXRQcm9taXNlID0gKCkgPT4ge1xuICAgICAgICAgICAgICAgIHRoaXMucmVzb2x2ZVZpc2l0UHJvbWlzZSA9ICgpID0+IHsgfTtcbiAgICAgICAgICAgICAgICByZXNvbHZlKCk7XG4gICAgICAgICAgICB9O1xuICAgICAgICAgICAgcmVxdWVzdC5wZXJmb3JtKCk7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBhZGRpdGlvbmFsSGVhZGVyc0ZvclJlcXVlc3QocmVxdWVzdCkge1xuICAgICAgICByZXR1cm4geyBcIlR1cmJvLUZyYW1lXCI6IHRoaXMuaWQgfTtcbiAgICB9XG4gICAgcmVxdWVzdFN0YXJ0ZWQocmVxdWVzdCkge1xuICAgICAgICB0aGlzLmVsZW1lbnQuc2V0QXR0cmlidXRlKFwiYnVzeVwiLCBcIlwiKTtcbiAgICB9XG4gICAgcmVxdWVzdFByZXZlbnRlZEhhbmRsaW5nUmVzcG9uc2UocmVxdWVzdCwgcmVzcG9uc2UpIHtcbiAgICAgICAgdGhpcy5yZXNvbHZlVmlzaXRQcm9taXNlKCk7XG4gICAgfVxuICAgIGFzeW5jIHJlcXVlc3RTdWNjZWVkZWRXaXRoUmVzcG9uc2UocmVxdWVzdCwgcmVzcG9uc2UpIHtcbiAgICAgICAgYXdhaXQgdGhpcy5sb2FkUmVzcG9uc2UocmVzcG9uc2UpO1xuICAgICAgICB0aGlzLnJlc29sdmVWaXNpdFByb21pc2UoKTtcbiAgICB9XG4gICAgcmVxdWVzdEZhaWxlZFdpdGhSZXNwb25zZShyZXF1ZXN0LCByZXNwb25zZSkge1xuICAgICAgICBjb25zb2xlLmVycm9yKHJlc3BvbnNlKTtcbiAgICAgICAgdGhpcy5yZXNvbHZlVmlzaXRQcm9taXNlKCk7XG4gICAgfVxuICAgIHJlcXVlc3RFcnJvcmVkKHJlcXVlc3QsIGVycm9yKSB7XG4gICAgICAgIGNvbnNvbGUuZXJyb3IoZXJyb3IpO1xuICAgICAgICB0aGlzLnJlc29sdmVWaXNpdFByb21pc2UoKTtcbiAgICB9XG4gICAgcmVxdWVzdEZpbmlzaGVkKHJlcXVlc3QpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50LnJlbW92ZUF0dHJpYnV0ZShcImJ1c3lcIik7XG4gICAgfVxuICAgIGZvcm1TdWJtaXNzaW9uU3RhcnRlZChmb3JtU3VibWlzc2lvbikge1xuICAgIH1cbiAgICBmb3JtU3VibWlzc2lvblN1Y2NlZWRlZFdpdGhSZXNwb25zZShmb3JtU3VibWlzc2lvbiwgcmVzcG9uc2UpIHtcbiAgICAgICAgY29uc3QgZnJhbWUgPSB0aGlzLmZpbmRGcmFtZUVsZW1lbnQoZm9ybVN1Ym1pc3Npb24uZm9ybUVsZW1lbnQpO1xuICAgICAgICBmcmFtZS5jb250cm9sbGVyLmxvYWRSZXNwb25zZShyZXNwb25zZSk7XG4gICAgfVxuICAgIGZvcm1TdWJtaXNzaW9uRmFpbGVkV2l0aFJlc3BvbnNlKGZvcm1TdWJtaXNzaW9uLCBmZXRjaFJlc3BvbnNlKSB7XG4gICAgfVxuICAgIGZvcm1TdWJtaXNzaW9uRXJyb3JlZChmb3JtU3VibWlzc2lvbiwgZXJyb3IpIHtcbiAgICB9XG4gICAgZm9ybVN1Ym1pc3Npb25GaW5pc2hlZChmb3JtU3VibWlzc2lvbikge1xuICAgIH1cbiAgICBuYXZpZ2F0ZUZyYW1lKGVsZW1lbnQsIHVybCkge1xuICAgICAgICBjb25zdCBmcmFtZSA9IHRoaXMuZmluZEZyYW1lRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgZnJhbWUuc3JjID0gdXJsO1xuICAgIH1cbiAgICBmaW5kRnJhbWVFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICBjb25zdCBpZCA9IGVsZW1lbnQuZ2V0QXR0cmlidXRlKFwiZGF0YS10dXJiby1mcmFtZVwiKTtcbiAgICAgICAgcmV0dXJuIChfYSA9IGdldEZyYW1lRWxlbWVudEJ5SWQoaWQpKSAhPT0gbnVsbCAmJiBfYSAhPT0gdm9pZCAwID8gX2EgOiB0aGlzLmVsZW1lbnQ7XG4gICAgfVxuICAgIGFzeW5jIGxvYWRSZXNwb25zZShyZXNwb25zZSkge1xuICAgICAgICBjb25zdCBmcmFnbWVudCA9IGZyYWdtZW50RnJvbUhUTUwoYXdhaXQgcmVzcG9uc2UucmVzcG9uc2VIVE1MKTtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IGF3YWl0IHRoaXMuZXh0cmFjdEZvcmVpZ25GcmFtZUVsZW1lbnQoZnJhZ21lbnQpO1xuICAgICAgICBpZiAoZWxlbWVudCkge1xuICAgICAgICAgICAgYXdhaXQgbmV4dEFuaW1hdGlvbkZyYW1lKCk7XG4gICAgICAgICAgICB0aGlzLmxvYWRGcmFtZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgICAgICB0aGlzLnNjcm9sbEZyYW1lSW50b1ZpZXcoZWxlbWVudCk7XG4gICAgICAgICAgICBhd2FpdCBuZXh0QW5pbWF0aW9uRnJhbWUoKTtcbiAgICAgICAgICAgIHRoaXMuZm9jdXNGaXJzdEF1dG9mb2N1c2FibGVFbGVtZW50KCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgYXN5bmMgZXh0cmFjdEZvcmVpZ25GcmFtZUVsZW1lbnQoY29udGFpbmVyKSB7XG4gICAgICAgIGxldCBlbGVtZW50O1xuICAgICAgICBjb25zdCBpZCA9IENTUy5lc2NhcGUodGhpcy5pZCk7XG4gICAgICAgIGlmIChlbGVtZW50ID0gYWN0aXZhdGVFbGVtZW50KGNvbnRhaW5lci5xdWVyeVNlbGVjdG9yKGB0dXJiby1mcmFtZSMke2lkfWApKSkge1xuICAgICAgICAgICAgcmV0dXJuIGVsZW1lbnQ7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKGVsZW1lbnQgPSBhY3RpdmF0ZUVsZW1lbnQoY29udGFpbmVyLnF1ZXJ5U2VsZWN0b3IoYHR1cmJvLWZyYW1lW3NyY11bcmVjdXJzZX49JHtpZH1dYCkpKSB7XG4gICAgICAgICAgICBhd2FpdCBlbGVtZW50LmxvYWRlZDtcbiAgICAgICAgICAgIHJldHVybiBhd2FpdCB0aGlzLmV4dHJhY3RGb3JlaWduRnJhbWVFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGxvYWRGcmFtZUVsZW1lbnQoZnJhbWVFbGVtZW50KSB7XG4gICAgICAgIHZhciBfYTtcbiAgICAgICAgY29uc3QgZGVzdGluYXRpb25SYW5nZSA9IGRvY3VtZW50LmNyZWF0ZVJhbmdlKCk7XG4gICAgICAgIGRlc3RpbmF0aW9uUmFuZ2Uuc2VsZWN0Tm9kZUNvbnRlbnRzKHRoaXMuZWxlbWVudCk7XG4gICAgICAgIGRlc3RpbmF0aW9uUmFuZ2UuZGVsZXRlQ29udGVudHMoKTtcbiAgICAgICAgY29uc3Qgc291cmNlUmFuZ2UgPSAoX2EgPSBmcmFtZUVsZW1lbnQub3duZXJEb2N1bWVudCkgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLmNyZWF0ZVJhbmdlKCk7XG4gICAgICAgIGlmIChzb3VyY2VSYW5nZSkge1xuICAgICAgICAgICAgc291cmNlUmFuZ2Uuc2VsZWN0Tm9kZUNvbnRlbnRzKGZyYW1lRWxlbWVudCk7XG4gICAgICAgICAgICB0aGlzLmVsZW1lbnQuYXBwZW5kQ2hpbGQoc291cmNlUmFuZ2UuZXh0cmFjdENvbnRlbnRzKCkpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGZvY3VzRmlyc3RBdXRvZm9jdXNhYmxlRWxlbWVudCgpIHtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IHRoaXMuZmlyc3RBdXRvZm9jdXNhYmxlRWxlbWVudDtcbiAgICAgICAgaWYgKGVsZW1lbnQpIHtcbiAgICAgICAgICAgIGVsZW1lbnQuZm9jdXMoKTtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG4gICAgc2Nyb2xsRnJhbWVJbnRvVmlldyhmcmFtZSkge1xuICAgICAgICBpZiAodGhpcy5lbGVtZW50LmF1dG9zY3JvbGwgfHwgZnJhbWUuYXV0b3Njcm9sbCkge1xuICAgICAgICAgICAgY29uc3QgZWxlbWVudCA9IHRoaXMuZWxlbWVudC5maXJzdEVsZW1lbnRDaGlsZDtcbiAgICAgICAgICAgIGNvbnN0IGJsb2NrID0gcmVhZFNjcm9sbExvZ2ljYWxQb3NpdGlvbih0aGlzLmVsZW1lbnQuZ2V0QXR0cmlidXRlKFwiZGF0YS1hdXRvc2Nyb2xsLWJsb2NrXCIpLCBcImVuZFwiKTtcbiAgICAgICAgICAgIGlmIChlbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgZWxlbWVudC5zY3JvbGxJbnRvVmlldyh7IGJsb2NrIH0pO1xuICAgICAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG4gICAgc2hvdWxkSW50ZXJjZXB0TmF2aWdhdGlvbihlbGVtZW50KSB7XG4gICAgICAgIGNvbnN0IGlkID0gZWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJkYXRhLXR1cmJvLWZyYW1lXCIpIHx8IHRoaXMuZWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJ0YXJnZXRcIik7XG4gICAgICAgIGlmICghdGhpcy5lbmFibGVkIHx8IGlkID09IFwiX3RvcFwiKSB7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKGlkKSB7XG4gICAgICAgICAgICBjb25zdCBmcmFtZUVsZW1lbnQgPSBnZXRGcmFtZUVsZW1lbnRCeUlkKGlkKTtcbiAgICAgICAgICAgIGlmIChmcmFtZUVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gIWZyYW1lRWxlbWVudC5kaXNhYmxlZDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9XG4gICAgZ2V0IGZpcnN0QXV0b2ZvY3VzYWJsZUVsZW1lbnQoKSB7XG4gICAgICAgIGNvbnN0IGVsZW1lbnQgPSB0aGlzLmVsZW1lbnQucXVlcnlTZWxlY3RvcihcIlthdXRvZm9jdXNdXCIpO1xuICAgICAgICByZXR1cm4gZWxlbWVudCBpbnN0YW5jZW9mIEhUTUxFbGVtZW50ID8gZWxlbWVudCA6IG51bGw7XG4gICAgfVxuICAgIGdldCBpZCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5pZDtcbiAgICB9XG4gICAgZ2V0IGVuYWJsZWQoKSB7XG4gICAgICAgIHJldHVybiAhdGhpcy5lbGVtZW50LmRpc2FibGVkO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGdldEZyYW1lRWxlbWVudEJ5SWQoaWQpIHtcbiAgICBpZiAoaWQgIT0gbnVsbCkge1xuICAgICAgICBjb25zdCBlbGVtZW50ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoaWQpO1xuICAgICAgICBpZiAoZWxlbWVudCBpbnN0YW5jZW9mIEZyYW1lRWxlbWVudCkge1xuICAgICAgICAgICAgcmV0dXJuIGVsZW1lbnQ7XG4gICAgICAgIH1cbiAgICB9XG59XG5mdW5jdGlvbiByZWFkU2Nyb2xsTG9naWNhbFBvc2l0aW9uKHZhbHVlLCBkZWZhdWx0VmFsdWUpIHtcbiAgICBpZiAodmFsdWUgPT0gXCJlbmRcIiB8fCB2YWx1ZSA9PSBcInN0YXJ0XCIgfHwgdmFsdWUgPT0gXCJjZW50ZXJcIiB8fCB2YWx1ZSA9PSBcIm5lYXJlc3RcIikge1xuICAgICAgICByZXR1cm4gdmFsdWU7XG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgICByZXR1cm4gZGVmYXVsdFZhbHVlO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGZyYWdtZW50RnJvbUhUTUwoaHRtbCA9IFwiXCIpIHtcbiAgICBjb25zdCBmb3JlaWduRG9jdW1lbnQgPSBkb2N1bWVudC5pbXBsZW1lbnRhdGlvbi5jcmVhdGVIVE1MRG9jdW1lbnQoKTtcbiAgICByZXR1cm4gZm9yZWlnbkRvY3VtZW50LmNyZWF0ZVJhbmdlKCkuY3JlYXRlQ29udGV4dHVhbEZyYWdtZW50KGh0bWwpO1xufVxuZnVuY3Rpb24gYWN0aXZhdGVFbGVtZW50KGVsZW1lbnQpIHtcbiAgICBpZiAoZWxlbWVudCAmJiBlbGVtZW50Lm93bmVyRG9jdW1lbnQgIT09IGRvY3VtZW50KSB7XG4gICAgICAgIGVsZW1lbnQgPSBkb2N1bWVudC5pbXBvcnROb2RlKGVsZW1lbnQsIHRydWUpO1xuICAgIH1cbiAgICBpZiAoZWxlbWVudCBpbnN0YW5jZW9mIEZyYW1lRWxlbWVudCkge1xuICAgICAgICByZXR1cm4gZWxlbWVudDtcbiAgICB9XG59XG5cbmNsYXNzIEZyYW1lRWxlbWVudCBleHRlbmRzIEhUTUxFbGVtZW50IHtcbiAgICBjb25zdHJ1Y3RvcigpIHtcbiAgICAgICAgc3VwZXIoKTtcbiAgICAgICAgdGhpcy5jb250cm9sbGVyID0gbmV3IEZyYW1lQ29udHJvbGxlcih0aGlzKTtcbiAgICB9XG4gICAgc3RhdGljIGdldCBvYnNlcnZlZEF0dHJpYnV0ZXMoKSB7XG4gICAgICAgIHJldHVybiBbXCJzcmNcIl07XG4gICAgfVxuICAgIGNvbm5lY3RlZENhbGxiYWNrKCkge1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXIuY29ubmVjdCgpO1xuICAgIH1cbiAgICBkaXNjb25uZWN0ZWRDYWxsYmFjaygpIHtcbiAgICAgICAgdGhpcy5jb250cm9sbGVyLmRpc2Nvbm5lY3QoKTtcbiAgICB9XG4gICAgYXR0cmlidXRlQ2hhbmdlZENhbGxiYWNrKCkge1xuICAgICAgICBpZiAodGhpcy5zcmMgJiYgdGhpcy5pc0FjdGl2ZSkge1xuICAgICAgICAgICAgY29uc3QgdmFsdWUgPSB0aGlzLmNvbnRyb2xsZXIudmlzaXQodGhpcy5zcmMpO1xuICAgICAgICAgICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KHRoaXMsIFwibG9hZGVkXCIsIHsgdmFsdWUsIGNvbmZpZ3VyYWJsZTogdHJ1ZSB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBmb3JtU3VibWlzc2lvbkludGVyY2VwdGVkKGVsZW1lbnQsIHN1Ym1pdHRlcikge1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXIuZm9ybVN1Ym1pc3Npb25JbnRlcmNlcHRlZChlbGVtZW50LCBzdWJtaXR0ZXIpO1xuICAgIH1cbiAgICBnZXQgc3JjKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRBdHRyaWJ1dGUoXCJzcmNcIik7XG4gICAgfVxuICAgIHNldCBzcmModmFsdWUpIHtcbiAgICAgICAgaWYgKHZhbHVlKSB7XG4gICAgICAgICAgICB0aGlzLnNldEF0dHJpYnV0ZShcInNyY1wiLCB2YWx1ZSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLnJlbW92ZUF0dHJpYnV0ZShcInNyY1wiKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXQgbG9hZGVkKCkge1xuICAgICAgICByZXR1cm4gUHJvbWlzZS5yZXNvbHZlKHVuZGVmaW5lZCk7XG4gICAgfVxuICAgIGdldCBkaXNhYmxlZCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuaGFzQXR0cmlidXRlKFwiZGlzYWJsZWRcIik7XG4gICAgfVxuICAgIHNldCBkaXNhYmxlZCh2YWx1ZSkge1xuICAgICAgICBpZiAodmFsdWUpIHtcbiAgICAgICAgICAgIHRoaXMuc2V0QXR0cmlidXRlKFwiZGlzYWJsZWRcIiwgXCJcIik7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLnJlbW92ZUF0dHJpYnV0ZShcImRpc2FibGVkXCIpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldCBhdXRvc2Nyb2xsKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5oYXNBdHRyaWJ1dGUoXCJhdXRvc2Nyb2xsXCIpO1xuICAgIH1cbiAgICBzZXQgYXV0b3Njcm9sbCh2YWx1ZSkge1xuICAgICAgICBpZiAodmFsdWUpIHtcbiAgICAgICAgICAgIHRoaXMuc2V0QXR0cmlidXRlKFwiYXV0b3Njcm9sbFwiLCBcIlwiKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMucmVtb3ZlQXR0cmlidXRlKFwiYXV0b3Njcm9sbFwiKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXQgaXNBY3RpdmUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLm93bmVyRG9jdW1lbnQgPT09IGRvY3VtZW50ICYmICF0aGlzLmlzUHJldmlldztcbiAgICB9XG4gICAgZ2V0IGlzUHJldmlldygpIHtcbiAgICAgICAgdmFyIF9hLCBfYjtcbiAgICAgICAgcmV0dXJuIChfYiA9IChfYSA9IHRoaXMub3duZXJEb2N1bWVudCkgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLmRvY3VtZW50RWxlbWVudCkgPT09IG51bGwgfHwgX2IgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9iLmhhc0F0dHJpYnV0ZShcImRhdGEtdHVyYm8tcHJldmlld1wiKTtcbiAgICB9XG59XG5jdXN0b21FbGVtZW50cy5kZWZpbmUoXCJ0dXJiby1mcmFtZVwiLCBGcmFtZUVsZW1lbnQpO1xuXG5jb25zdCBTdHJlYW1BY3Rpb25zID0ge1xuICAgIGFwcGVuZCgpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICAoX2EgPSB0aGlzLnRhcmdldEVsZW1lbnQpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5hcHBlbmQodGhpcy50ZW1wbGF0ZUNvbnRlbnQpO1xuICAgIH0sXG4gICAgcHJlcGVuZCgpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICAoX2EgPSB0aGlzLnRhcmdldEVsZW1lbnQpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5wcmVwZW5kKHRoaXMudGVtcGxhdGVDb250ZW50KTtcbiAgICB9LFxuICAgIHJlbW92ZSgpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICAoX2EgPSB0aGlzLnRhcmdldEVsZW1lbnQpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5yZW1vdmUoKTtcbiAgICB9LFxuICAgIHJlcGxhY2UoKSB7XG4gICAgICAgIHZhciBfYTtcbiAgICAgICAgKF9hID0gdGhpcy50YXJnZXRFbGVtZW50KSA9PT0gbnVsbCB8fCBfYSA9PT0gdm9pZCAwID8gdm9pZCAwIDogX2EucmVwbGFjZVdpdGgodGhpcy50ZW1wbGF0ZUNvbnRlbnQpO1xuICAgIH0sXG4gICAgdXBkYXRlKCkge1xuICAgICAgICBpZiAodGhpcy50YXJnZXRFbGVtZW50KSB7XG4gICAgICAgICAgICB0aGlzLnRhcmdldEVsZW1lbnQuaW5uZXJIVE1MID0gXCJcIjtcbiAgICAgICAgICAgIHRoaXMudGFyZ2V0RWxlbWVudC5hcHBlbmQodGhpcy50ZW1wbGF0ZUNvbnRlbnQpO1xuICAgICAgICB9XG4gICAgfVxufTtcblxuY2xhc3MgU3RyZWFtRWxlbWVudCBleHRlbmRzIEhUTUxFbGVtZW50IHtcbiAgICBhc3luYyBjb25uZWN0ZWRDYWxsYmFjaygpIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIGF3YWl0IHRoaXMucmVuZGVyKCk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICBjb25zb2xlLmVycm9yKGVycm9yKTtcbiAgICAgICAgfVxuICAgICAgICBmaW5hbGx5IHtcbiAgICAgICAgICAgIHRoaXMuZGlzY29ubmVjdCgpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGFzeW5jIHJlbmRlcigpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICByZXR1cm4gKF9hID0gdGhpcy5yZW5kZXJQcm9taXNlKSAhPT0gbnVsbCAmJiBfYSAhPT0gdm9pZCAwID8gX2EgOiAodGhpcy5yZW5kZXJQcm9taXNlID0gKGFzeW5jICgpID0+IHtcbiAgICAgICAgICAgIGlmICh0aGlzLmRpc3BhdGNoRXZlbnQodGhpcy5iZWZvcmVSZW5kZXJFdmVudCkpIHtcbiAgICAgICAgICAgICAgICBhd2FpdCBuZXh0QW5pbWF0aW9uRnJhbWUoKTtcbiAgICAgICAgICAgICAgICB0aGlzLnBlcmZvcm1BY3Rpb24oKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSkoKSk7XG4gICAgfVxuICAgIGRpc2Nvbm5lY3QoKSB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICB0aGlzLnJlbW92ZSgpO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChfYSkgeyB9XG4gICAgfVxuICAgIGdldCBwZXJmb3JtQWN0aW9uKCkge1xuICAgICAgICBpZiAodGhpcy5hY3Rpb24pIHtcbiAgICAgICAgICAgIGNvbnN0IGFjdGlvbkZ1bmN0aW9uID0gU3RyZWFtQWN0aW9uc1t0aGlzLmFjdGlvbl07XG4gICAgICAgICAgICBpZiAoYWN0aW9uRnVuY3Rpb24pIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gYWN0aW9uRnVuY3Rpb247XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0aGlzLnJhaXNlKFwidW5rbm93biBhY3Rpb25cIik7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5yYWlzZShcImFjdGlvbiBhdHRyaWJ1dGUgaXMgbWlzc2luZ1wiKTtcbiAgICB9XG4gICAgZ2V0IHRhcmdldEVsZW1lbnQoKSB7XG4gICAgICAgIHZhciBfYTtcbiAgICAgICAgaWYgKHRoaXMudGFyZ2V0KSB7XG4gICAgICAgICAgICByZXR1cm4gKF9hID0gdGhpcy5vd25lckRvY3VtZW50KSA9PT0gbnVsbCB8fCBfYSA9PT0gdm9pZCAwID8gdm9pZCAwIDogX2EuZ2V0RWxlbWVudEJ5SWQodGhpcy50YXJnZXQpO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMucmFpc2UoXCJ0YXJnZXQgYXR0cmlidXRlIGlzIG1pc3NpbmdcIik7XG4gICAgfVxuICAgIGdldCB0ZW1wbGF0ZUNvbnRlbnQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnRlbXBsYXRlRWxlbWVudC5jb250ZW50O1xuICAgIH1cbiAgICBnZXQgdGVtcGxhdGVFbGVtZW50KCkge1xuICAgICAgICBpZiAodGhpcy5maXJzdEVsZW1lbnRDaGlsZCBpbnN0YW5jZW9mIEhUTUxUZW1wbGF0ZUVsZW1lbnQpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmZpcnN0RWxlbWVudENoaWxkO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMucmFpc2UoXCJmaXJzdCBjaGlsZCBlbGVtZW50IG11c3QgYmUgYSA8dGVtcGxhdGU+IGVsZW1lbnRcIik7XG4gICAgfVxuICAgIGdldCBhY3Rpb24oKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldEF0dHJpYnV0ZShcImFjdGlvblwiKTtcbiAgICB9XG4gICAgZ2V0IHRhcmdldCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0QXR0cmlidXRlKFwidGFyZ2V0XCIpO1xuICAgIH1cbiAgICByYWlzZShtZXNzYWdlKSB7XG4gICAgICAgIHRocm93IG5ldyBFcnJvcihgJHt0aGlzLmRlc2NyaXB0aW9ufTogJHttZXNzYWdlfWApO1xuICAgIH1cbiAgICBnZXQgZGVzY3JpcHRpb24oKSB7XG4gICAgICAgIHZhciBfYSwgX2I7XG4gICAgICAgIHJldHVybiAoX2IgPSAoKF9hID0gdGhpcy5vdXRlckhUTUwubWF0Y2goLzxbXj5dKz4vKSkgIT09IG51bGwgJiYgX2EgIT09IHZvaWQgMCA/IF9hIDogW10pWzBdKSAhPT0gbnVsbCAmJiBfYiAhPT0gdm9pZCAwID8gX2IgOiBcIjx0dXJiby1zdHJlYW0+XCI7XG4gICAgfVxuICAgIGdldCBiZWZvcmVSZW5kZXJFdmVudCgpIHtcbiAgICAgICAgcmV0dXJuIG5ldyBDdXN0b21FdmVudChcInR1cmJvOmJlZm9yZS1zdHJlYW0tcmVuZGVyXCIsIHsgYnViYmxlczogdHJ1ZSwgY2FuY2VsYWJsZTogdHJ1ZSB9KTtcbiAgICB9XG59XG5jdXN0b21FbGVtZW50cy5kZWZpbmUoXCJ0dXJiby1zdHJlYW1cIiwgU3RyZWFtRWxlbWVudCk7XG5cbigoKSA9PiB7XG4gICAgbGV0IGVsZW1lbnQgPSBkb2N1bWVudC5jdXJyZW50U2NyaXB0O1xuICAgIGlmICghZWxlbWVudClcbiAgICAgICAgcmV0dXJuO1xuICAgIGlmIChlbGVtZW50Lmhhc0F0dHJpYnV0ZShcImRhdGEtdHVyYm8tc3VwcHJlc3Mtd2FybmluZ1wiKSlcbiAgICAgICAgcmV0dXJuO1xuICAgIHdoaWxlIChlbGVtZW50ID0gZWxlbWVudC5wYXJlbnRFbGVtZW50KSB7XG4gICAgICAgIGlmIChlbGVtZW50ID09IGRvY3VtZW50LmJvZHkpIHtcbiAgICAgICAgICAgIHJldHVybiBjb25zb2xlLndhcm4odW5pbmRlbnQgYFxuICAgICAgICBZb3UgYXJlIGxvYWRpbmcgVHVyYm8gZnJvbSBhIDxzY3JpcHQ+IGVsZW1lbnQgaW5zaWRlIHRoZSA8Ym9keT4gZWxlbWVudC4gVGhpcyBpcyBwcm9iYWJseSBub3Qgd2hhdCB5b3UgbWVhbnQgdG8gZG8hXG5cbiAgICAgICAgTG9hZCB5b3VyIGFwcGxpY2F0aW9u4oCZcyBKYXZhU2NyaXB0IGJ1bmRsZSBpbnNpZGUgdGhlIDxoZWFkPiBlbGVtZW50IGluc3RlYWQuIDxzY3JpcHQ+IGVsZW1lbnRzIGluIDxib2R5PiBhcmUgZXZhbHVhdGVkIHdpdGggZWFjaCBwYWdlIGNoYW5nZS5cblxuICAgICAgICBGb3IgbW9yZSBpbmZvcm1hdGlvbiwgc2VlOiBodHRwczovL3R1cmJvLmhvdHdpcmUuZGV2L2hhbmRib29rL2J1aWxkaW5nI3dvcmtpbmctd2l0aC1zY3JpcHQtZWxlbWVudHNcblxuICAgICAgICDigJTigJRcbiAgICAgICAgU3VwcHJlc3MgdGhpcyB3YXJuaW5nIGJ5IGFkZGluZyBhIFwiZGF0YS10dXJiby1zdXBwcmVzcy13YXJuaW5nXCIgYXR0cmlidXRlIHRvOiAlc1xuICAgICAgYCwgZWxlbWVudC5vdXRlckhUTUwpO1xuICAgICAgICB9XG4gICAgfVxufSkoKTtcblxuY2xhc3MgUHJvZ3Jlc3NCYXIge1xuICAgIGNvbnN0cnVjdG9yKCkge1xuICAgICAgICB0aGlzLmhpZGluZyA9IGZhbHNlO1xuICAgICAgICB0aGlzLnZhbHVlID0gMDtcbiAgICAgICAgdGhpcy52aXNpYmxlID0gZmFsc2U7XG4gICAgICAgIHRoaXMudHJpY2tsZSA9ICgpID0+IHtcbiAgICAgICAgICAgIHRoaXMuc2V0VmFsdWUodGhpcy52YWx1ZSArIE1hdGgucmFuZG9tKCkgLyAxMDApO1xuICAgICAgICB9O1xuICAgICAgICB0aGlzLnN0eWxlc2hlZXRFbGVtZW50ID0gdGhpcy5jcmVhdGVTdHlsZXNoZWV0RWxlbWVudCgpO1xuICAgICAgICB0aGlzLnByb2dyZXNzRWxlbWVudCA9IHRoaXMuY3JlYXRlUHJvZ3Jlc3NFbGVtZW50KCk7XG4gICAgICAgIHRoaXMuaW5zdGFsbFN0eWxlc2hlZXRFbGVtZW50KCk7XG4gICAgICAgIHRoaXMuc2V0VmFsdWUoMCk7XG4gICAgfVxuICAgIHN0YXRpYyBnZXQgZGVmYXVsdENTUygpIHtcbiAgICAgICAgcmV0dXJuIHVuaW5kZW50IGBcbiAgICAgIC50dXJiby1wcm9ncmVzcy1iYXIge1xuICAgICAgICBwb3NpdGlvbjogZml4ZWQ7XG4gICAgICAgIGRpc3BsYXk6IGJsb2NrO1xuICAgICAgICB0b3A6IDA7XG4gICAgICAgIGxlZnQ6IDA7XG4gICAgICAgIGhlaWdodDogM3B4O1xuICAgICAgICBiYWNrZ3JvdW5kOiAjMDA3NmZmO1xuICAgICAgICB6LWluZGV4OiA5OTk5O1xuICAgICAgICB0cmFuc2l0aW9uOlxuICAgICAgICAgIHdpZHRoICR7UHJvZ3Jlc3NCYXIuYW5pbWF0aW9uRHVyYXRpb259bXMgZWFzZS1vdXQsXG4gICAgICAgICAgb3BhY2l0eSAke1Byb2dyZXNzQmFyLmFuaW1hdGlvbkR1cmF0aW9uIC8gMn1tcyAke1Byb2dyZXNzQmFyLmFuaW1hdGlvbkR1cmF0aW9uIC8gMn1tcyBlYXNlLWluO1xuICAgICAgICB0cmFuc2Zvcm06IHRyYW5zbGF0ZTNkKDAsIDAsIDApO1xuICAgICAgfVxuICAgIGA7XG4gICAgfVxuICAgIHNob3coKSB7XG4gICAgICAgIGlmICghdGhpcy52aXNpYmxlKSB7XG4gICAgICAgICAgICB0aGlzLnZpc2libGUgPSB0cnVlO1xuICAgICAgICAgICAgdGhpcy5pbnN0YWxsUHJvZ3Jlc3NFbGVtZW50KCk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0VHJpY2tsaW5nKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgaGlkZSgpIHtcbiAgICAgICAgaWYgKHRoaXMudmlzaWJsZSAmJiAhdGhpcy5oaWRpbmcpIHtcbiAgICAgICAgICAgIHRoaXMuaGlkaW5nID0gdHJ1ZTtcbiAgICAgICAgICAgIHRoaXMuZmFkZVByb2dyZXNzRWxlbWVudCgoKSA9PiB7XG4gICAgICAgICAgICAgICAgdGhpcy51bmluc3RhbGxQcm9ncmVzc0VsZW1lbnQoKTtcbiAgICAgICAgICAgICAgICB0aGlzLnN0b3BUcmlja2xpbmcoKTtcbiAgICAgICAgICAgICAgICB0aGlzLnZpc2libGUgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICB0aGlzLmhpZGluZyA9IGZhbHNlO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc2V0VmFsdWUodmFsdWUpIHtcbiAgICAgICAgdGhpcy52YWx1ZSA9IHZhbHVlO1xuICAgICAgICB0aGlzLnJlZnJlc2goKTtcbiAgICB9XG4gICAgaW5zdGFsbFN0eWxlc2hlZXRFbGVtZW50KCkge1xuICAgICAgICBkb2N1bWVudC5oZWFkLmluc2VydEJlZm9yZSh0aGlzLnN0eWxlc2hlZXRFbGVtZW50LCBkb2N1bWVudC5oZWFkLmZpcnN0Q2hpbGQpO1xuICAgIH1cbiAgICBpbnN0YWxsUHJvZ3Jlc3NFbGVtZW50KCkge1xuICAgICAgICB0aGlzLnByb2dyZXNzRWxlbWVudC5zdHlsZS53aWR0aCA9IFwiMFwiO1xuICAgICAgICB0aGlzLnByb2dyZXNzRWxlbWVudC5zdHlsZS5vcGFjaXR5ID0gXCIxXCI7XG4gICAgICAgIGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5pbnNlcnRCZWZvcmUodGhpcy5wcm9ncmVzc0VsZW1lbnQsIGRvY3VtZW50LmJvZHkpO1xuICAgICAgICB0aGlzLnJlZnJlc2goKTtcbiAgICB9XG4gICAgZmFkZVByb2dyZXNzRWxlbWVudChjYWxsYmFjaykge1xuICAgICAgICB0aGlzLnByb2dyZXNzRWxlbWVudC5zdHlsZS5vcGFjaXR5ID0gXCIwXCI7XG4gICAgICAgIHNldFRpbWVvdXQoY2FsbGJhY2ssIFByb2dyZXNzQmFyLmFuaW1hdGlvbkR1cmF0aW9uICogMS41KTtcbiAgICB9XG4gICAgdW5pbnN0YWxsUHJvZ3Jlc3NFbGVtZW50KCkge1xuICAgICAgICBpZiAodGhpcy5wcm9ncmVzc0VsZW1lbnQucGFyZW50Tm9kZSkge1xuICAgICAgICAgICAgZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnJlbW92ZUNoaWxkKHRoaXMucHJvZ3Jlc3NFbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdGFydFRyaWNrbGluZygpIHtcbiAgICAgICAgaWYgKCF0aGlzLnRyaWNrbGVJbnRlcnZhbCkge1xuICAgICAgICAgICAgdGhpcy50cmlja2xlSW50ZXJ2YWwgPSB3aW5kb3cuc2V0SW50ZXJ2YWwodGhpcy50cmlja2xlLCBQcm9ncmVzc0Jhci5hbmltYXRpb25EdXJhdGlvbik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcFRyaWNrbGluZygpIHtcbiAgICAgICAgd2luZG93LmNsZWFySW50ZXJ2YWwodGhpcy50cmlja2xlSW50ZXJ2YWwpO1xuICAgICAgICBkZWxldGUgdGhpcy50cmlja2xlSW50ZXJ2YWw7XG4gICAgfVxuICAgIHJlZnJlc2goKSB7XG4gICAgICAgIHJlcXVlc3RBbmltYXRpb25GcmFtZSgoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLnByb2dyZXNzRWxlbWVudC5zdHlsZS53aWR0aCA9IGAkezEwICsgKHRoaXMudmFsdWUgKiA5MCl9JWA7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBjcmVhdGVTdHlsZXNoZWV0RWxlbWVudCgpIHtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJzdHlsZVwiKTtcbiAgICAgICAgZWxlbWVudC50eXBlID0gXCJ0ZXh0L2Nzc1wiO1xuICAgICAgICBlbGVtZW50LnRleHRDb250ZW50ID0gUHJvZ3Jlc3NCYXIuZGVmYXVsdENTUztcbiAgICAgICAgcmV0dXJuIGVsZW1lbnQ7XG4gICAgfVxuICAgIGNyZWF0ZVByb2dyZXNzRWxlbWVudCgpIHtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJkaXZcIik7XG4gICAgICAgIGVsZW1lbnQuY2xhc3NOYW1lID0gXCJ0dXJiby1wcm9ncmVzcy1iYXJcIjtcbiAgICAgICAgcmV0dXJuIGVsZW1lbnQ7XG4gICAgfVxufVxuUHJvZ3Jlc3NCYXIuYW5pbWF0aW9uRHVyYXRpb24gPSAzMDA7XG5cbmNsYXNzIEhlYWREZXRhaWxzIHtcbiAgICBjb25zdHJ1Y3RvcihjaGlsZHJlbikge1xuICAgICAgICB0aGlzLmRldGFpbHNCeU91dGVySFRNTCA9IGNoaWxkcmVuLnJlZHVjZSgocmVzdWx0LCBlbGVtZW50KSA9PiB7XG4gICAgICAgICAgICBjb25zdCB7IG91dGVySFRNTCB9ID0gZWxlbWVudDtcbiAgICAgICAgICAgIGNvbnN0IGRldGFpbHMgPSBvdXRlckhUTUwgaW4gcmVzdWx0XG4gICAgICAgICAgICAgICAgPyByZXN1bHRbb3V0ZXJIVE1MXVxuICAgICAgICAgICAgICAgIDoge1xuICAgICAgICAgICAgICAgICAgICB0eXBlOiBlbGVtZW50VHlwZShlbGVtZW50KSxcbiAgICAgICAgICAgICAgICAgICAgdHJhY2tlZDogZWxlbWVudElzVHJhY2tlZChlbGVtZW50KSxcbiAgICAgICAgICAgICAgICAgICAgZWxlbWVudHM6IFtdXG4gICAgICAgICAgICAgICAgfTtcbiAgICAgICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKE9iamVjdC5hc3NpZ24oe30sIHJlc3VsdCksIHsgW291dGVySFRNTF06IE9iamVjdC5hc3NpZ24oT2JqZWN0LmFzc2lnbih7fSwgZGV0YWlscyksIHsgZWxlbWVudHM6IFsuLi5kZXRhaWxzLmVsZW1lbnRzLCBlbGVtZW50XSB9KSB9KTtcbiAgICAgICAgfSwge30pO1xuICAgIH1cbiAgICBzdGF0aWMgZnJvbUhlYWRFbGVtZW50KGhlYWRFbGVtZW50KSB7XG4gICAgICAgIGNvbnN0IGNoaWxkcmVuID0gaGVhZEVsZW1lbnQgPyBbLi4uaGVhZEVsZW1lbnQuY2hpbGRyZW5dIDogW107XG4gICAgICAgIHJldHVybiBuZXcgdGhpcyhjaGlsZHJlbik7XG4gICAgfVxuICAgIGdldFRyYWNrZWRFbGVtZW50U2lnbmF0dXJlKCkge1xuICAgICAgICByZXR1cm4gT2JqZWN0LmtleXModGhpcy5kZXRhaWxzQnlPdXRlckhUTUwpXG4gICAgICAgICAgICAuZmlsdGVyKG91dGVySFRNTCA9PiB0aGlzLmRldGFpbHNCeU91dGVySFRNTFtvdXRlckhUTUxdLnRyYWNrZWQpXG4gICAgICAgICAgICAuam9pbihcIlwiKTtcbiAgICB9XG4gICAgZ2V0U2NyaXB0RWxlbWVudHNOb3RJbkRldGFpbHMoaGVhZERldGFpbHMpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0RWxlbWVudHNNYXRjaGluZ1R5cGVOb3RJbkRldGFpbHMoXCJzY3JpcHRcIiwgaGVhZERldGFpbHMpO1xuICAgIH1cbiAgICBnZXRTdHlsZXNoZWV0RWxlbWVudHNOb3RJbkRldGFpbHMoaGVhZERldGFpbHMpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0RWxlbWVudHNNYXRjaGluZ1R5cGVOb3RJbkRldGFpbHMoXCJzdHlsZXNoZWV0XCIsIGhlYWREZXRhaWxzKTtcbiAgICB9XG4gICAgZ2V0RWxlbWVudHNNYXRjaGluZ1R5cGVOb3RJbkRldGFpbHMobWF0Y2hlZFR5cGUsIGhlYWREZXRhaWxzKSB7XG4gICAgICAgIHJldHVybiBPYmplY3Qua2V5cyh0aGlzLmRldGFpbHNCeU91dGVySFRNTClcbiAgICAgICAgICAgIC5maWx0ZXIob3V0ZXJIVE1MID0+ICEob3V0ZXJIVE1MIGluIGhlYWREZXRhaWxzLmRldGFpbHNCeU91dGVySFRNTCkpXG4gICAgICAgICAgICAubWFwKG91dGVySFRNTCA9PiB0aGlzLmRldGFpbHNCeU91dGVySFRNTFtvdXRlckhUTUxdKVxuICAgICAgICAgICAgLmZpbHRlcigoeyB0eXBlIH0pID0+IHR5cGUgPT0gbWF0Y2hlZFR5cGUpXG4gICAgICAgICAgICAubWFwKCh7IGVsZW1lbnRzOiBbZWxlbWVudF0gfSkgPT4gZWxlbWVudCk7XG4gICAgfVxuICAgIGdldFByb3Zpc2lvbmFsRWxlbWVudHMoKSB7XG4gICAgICAgIHJldHVybiBPYmplY3Qua2V5cyh0aGlzLmRldGFpbHNCeU91dGVySFRNTCkucmVkdWNlKChyZXN1bHQsIG91dGVySFRNTCkgPT4ge1xuICAgICAgICAgICAgY29uc3QgeyB0eXBlLCB0cmFja2VkLCBlbGVtZW50cyB9ID0gdGhpcy5kZXRhaWxzQnlPdXRlckhUTUxbb3V0ZXJIVE1MXTtcbiAgICAgICAgICAgIGlmICh0eXBlID09IG51bGwgJiYgIXRyYWNrZWQpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gWy4uLnJlc3VsdCwgLi4uZWxlbWVudHNdO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSBpZiAoZWxlbWVudHMubGVuZ3RoID4gMSkge1xuICAgICAgICAgICAgICAgIHJldHVybiBbLi4ucmVzdWx0LCAuLi5lbGVtZW50cy5zbGljZSgxKV07XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gcmVzdWx0O1xuICAgICAgICAgICAgfVxuICAgICAgICB9LCBbXSk7XG4gICAgfVxuICAgIGdldE1ldGFWYWx1ZShuYW1lKSB7XG4gICAgICAgIGNvbnN0IGVsZW1lbnQgPSB0aGlzLmZpbmRNZXRhRWxlbWVudEJ5TmFtZShuYW1lKTtcbiAgICAgICAgcmV0dXJuIGVsZW1lbnRcbiAgICAgICAgICAgID8gZWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJjb250ZW50XCIpXG4gICAgICAgICAgICA6IG51bGw7XG4gICAgfVxuICAgIGZpbmRNZXRhRWxlbWVudEJ5TmFtZShuYW1lKSB7XG4gICAgICAgIHJldHVybiBPYmplY3Qua2V5cyh0aGlzLmRldGFpbHNCeU91dGVySFRNTCkucmVkdWNlKChyZXN1bHQsIG91dGVySFRNTCkgPT4ge1xuICAgICAgICAgICAgY29uc3QgeyBlbGVtZW50czogW2VsZW1lbnRdIH0gPSB0aGlzLmRldGFpbHNCeU91dGVySFRNTFtvdXRlckhUTUxdO1xuICAgICAgICAgICAgcmV0dXJuIGVsZW1lbnRJc01ldGFFbGVtZW50V2l0aE5hbWUoZWxlbWVudCwgbmFtZSkgPyBlbGVtZW50IDogcmVzdWx0O1xuICAgICAgICB9LCB1bmRlZmluZWQpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGVsZW1lbnRUeXBlKGVsZW1lbnQpIHtcbiAgICBpZiAoZWxlbWVudElzU2NyaXB0KGVsZW1lbnQpKSB7XG4gICAgICAgIHJldHVybiBcInNjcmlwdFwiO1xuICAgIH1cbiAgICBlbHNlIGlmIChlbGVtZW50SXNTdHlsZXNoZWV0KGVsZW1lbnQpKSB7XG4gICAgICAgIHJldHVybiBcInN0eWxlc2hlZXRcIjtcbiAgICB9XG59XG5mdW5jdGlvbiBlbGVtZW50SXNUcmFja2VkKGVsZW1lbnQpIHtcbiAgICByZXR1cm4gZWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJkYXRhLXR1cmJvLXRyYWNrXCIpID09IFwicmVsb2FkXCI7XG59XG5mdW5jdGlvbiBlbGVtZW50SXNTY3JpcHQoZWxlbWVudCkge1xuICAgIGNvbnN0IHRhZ05hbWUgPSBlbGVtZW50LnRhZ05hbWUudG9Mb3dlckNhc2UoKTtcbiAgICByZXR1cm4gdGFnTmFtZSA9PSBcInNjcmlwdFwiO1xufVxuZnVuY3Rpb24gZWxlbWVudElzU3R5bGVzaGVldChlbGVtZW50KSB7XG4gICAgY29uc3QgdGFnTmFtZSA9IGVsZW1lbnQudGFnTmFtZS50b0xvd2VyQ2FzZSgpO1xuICAgIHJldHVybiB0YWdOYW1lID09IFwic3R5bGVcIiB8fCAodGFnTmFtZSA9PSBcImxpbmtcIiAmJiBlbGVtZW50LmdldEF0dHJpYnV0ZShcInJlbFwiKSA9PSBcInN0eWxlc2hlZXRcIik7XG59XG5mdW5jdGlvbiBlbGVtZW50SXNNZXRhRWxlbWVudFdpdGhOYW1lKGVsZW1lbnQsIG5hbWUpIHtcbiAgICBjb25zdCB0YWdOYW1lID0gZWxlbWVudC50YWdOYW1lLnRvTG93ZXJDYXNlKCk7XG4gICAgcmV0dXJuIHRhZ05hbWUgPT0gXCJtZXRhXCIgJiYgZWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJuYW1lXCIpID09IG5hbWU7XG59XG5cbmNsYXNzIFNuYXBzaG90IHtcbiAgICBjb25zdHJ1Y3RvcihoZWFkRGV0YWlscywgYm9keUVsZW1lbnQpIHtcbiAgICAgICAgdGhpcy5oZWFkRGV0YWlscyA9IGhlYWREZXRhaWxzO1xuICAgICAgICB0aGlzLmJvZHlFbGVtZW50ID0gYm9keUVsZW1lbnQ7XG4gICAgfVxuICAgIHN0YXRpYyB3cmFwKHZhbHVlKSB7XG4gICAgICAgIGlmICh2YWx1ZSBpbnN0YW5jZW9mIHRoaXMpIHtcbiAgICAgICAgICAgIHJldHVybiB2YWx1ZTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmICh0eXBlb2YgdmFsdWUgPT0gXCJzdHJpbmdcIikge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZnJvbUhUTUxTdHJpbmcodmFsdWUpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZnJvbUhUTUxFbGVtZW50KHZhbHVlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdGF0aWMgZnJvbUhUTUxTdHJpbmcoaHRtbCkge1xuICAgICAgICBjb25zdCB7IGRvY3VtZW50RWxlbWVudCB9ID0gbmV3IERPTVBhcnNlcigpLnBhcnNlRnJvbVN0cmluZyhodG1sLCBcInRleHQvaHRtbFwiKTtcbiAgICAgICAgcmV0dXJuIHRoaXMuZnJvbUhUTUxFbGVtZW50KGRvY3VtZW50RWxlbWVudCk7XG4gICAgfVxuICAgIHN0YXRpYyBmcm9tSFRNTEVsZW1lbnQoaHRtbEVsZW1lbnQpIHtcbiAgICAgICAgY29uc3QgaGVhZEVsZW1lbnQgPSBodG1sRWxlbWVudC5xdWVyeVNlbGVjdG9yKFwiaGVhZFwiKTtcbiAgICAgICAgY29uc3QgYm9keUVsZW1lbnQgPSBodG1sRWxlbWVudC5xdWVyeVNlbGVjdG9yKFwiYm9keVwiKSB8fCBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwiYm9keVwiKTtcbiAgICAgICAgY29uc3QgaGVhZERldGFpbHMgPSBIZWFkRGV0YWlscy5mcm9tSGVhZEVsZW1lbnQoaGVhZEVsZW1lbnQpO1xuICAgICAgICByZXR1cm4gbmV3IHRoaXMoaGVhZERldGFpbHMsIGJvZHlFbGVtZW50KTtcbiAgICB9XG4gICAgY2xvbmUoKSB7XG4gICAgICAgIGNvbnN0IHsgYm9keUVsZW1lbnQgfSA9IFNuYXBzaG90LmZyb21IVE1MU3RyaW5nKHRoaXMuYm9keUVsZW1lbnQub3V0ZXJIVE1MKTtcbiAgICAgICAgcmV0dXJuIG5ldyBTbmFwc2hvdCh0aGlzLmhlYWREZXRhaWxzLCBib2R5RWxlbWVudCk7XG4gICAgfVxuICAgIGdldFJvb3RMb2NhdGlvbigpIHtcbiAgICAgICAgY29uc3Qgcm9vdCA9IHRoaXMuZ2V0U2V0dGluZyhcInJvb3RcIiwgXCIvXCIpO1xuICAgICAgICByZXR1cm4gbmV3IExvY2F0aW9uKHJvb3QpO1xuICAgIH1cbiAgICBnZXRDYWNoZUNvbnRyb2xWYWx1ZSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0U2V0dGluZyhcImNhY2hlLWNvbnRyb2xcIik7XG4gICAgfVxuICAgIGdldEVsZW1lbnRGb3JBbmNob3IoYW5jaG9yKSB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5ib2R5RWxlbWVudC5xdWVyeVNlbGVjdG9yKGBbaWQ9JyR7YW5jaG9yfSddLCBhW25hbWU9JyR7YW5jaG9yfSddYCk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKF9hKSB7XG4gICAgICAgICAgICByZXR1cm4gbnVsbDtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXRQZXJtYW5lbnRFbGVtZW50cygpIHtcbiAgICAgICAgcmV0dXJuIFsuLi50aGlzLmJvZHlFbGVtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoXCJbaWRdW2RhdGEtdHVyYm8tcGVybWFuZW50XVwiKV07XG4gICAgfVxuICAgIGdldFBlcm1hbmVudEVsZW1lbnRCeUlkKGlkKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmJvZHlFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoYCMke2lkfVtkYXRhLXR1cmJvLXBlcm1hbmVudF1gKTtcbiAgICB9XG4gICAgZ2V0UGVybWFuZW50RWxlbWVudHNQcmVzZW50SW5TbmFwc2hvdChzbmFwc2hvdCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRQZXJtYW5lbnRFbGVtZW50cygpLmZpbHRlcigoeyBpZCB9KSA9PiBzbmFwc2hvdC5nZXRQZXJtYW5lbnRFbGVtZW50QnlJZChpZCkpO1xuICAgIH1cbiAgICBmaW5kRmlyc3RBdXRvZm9jdXNhYmxlRWxlbWVudCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYm9keUVsZW1lbnQucXVlcnlTZWxlY3RvcihcIlthdXRvZm9jdXNdXCIpO1xuICAgIH1cbiAgICBoYXNBbmNob3IoYW5jaG9yKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldEVsZW1lbnRGb3JBbmNob3IoYW5jaG9yKSAhPSBudWxsO1xuICAgIH1cbiAgICBpc1ByZXZpZXdhYmxlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRDYWNoZUNvbnRyb2xWYWx1ZSgpICE9IFwibm8tcHJldmlld1wiO1xuICAgIH1cbiAgICBpc0NhY2hlYWJsZSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0Q2FjaGVDb250cm9sVmFsdWUoKSAhPSBcIm5vLWNhY2hlXCI7XG4gICAgfVxuICAgIGlzVmlzaXRhYmxlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRTZXR0aW5nKFwidmlzaXQtY29udHJvbFwiKSAhPSBcInJlbG9hZFwiO1xuICAgIH1cbiAgICBnZXRTZXR0aW5nKG5hbWUsIGRlZmF1bHRWYWx1ZSkge1xuICAgICAgICBjb25zdCB2YWx1ZSA9IHRoaXMuaGVhZERldGFpbHMuZ2V0TWV0YVZhbHVlKGB0dXJiby0ke25hbWV9YCk7XG4gICAgICAgIHJldHVybiB2YWx1ZSA9PSBudWxsID8gZGVmYXVsdFZhbHVlIDogdmFsdWU7XG4gICAgfVxufVxuXG52YXIgVGltaW5nTWV0cmljO1xuKGZ1bmN0aW9uIChUaW1pbmdNZXRyaWMpIHtcbiAgICBUaW1pbmdNZXRyaWNbXCJ2aXNpdFN0YXJ0XCJdID0gXCJ2aXNpdFN0YXJ0XCI7XG4gICAgVGltaW5nTWV0cmljW1wicmVxdWVzdFN0YXJ0XCJdID0gXCJyZXF1ZXN0U3RhcnRcIjtcbiAgICBUaW1pbmdNZXRyaWNbXCJyZXF1ZXN0RW5kXCJdID0gXCJyZXF1ZXN0RW5kXCI7XG4gICAgVGltaW5nTWV0cmljW1widmlzaXRFbmRcIl0gPSBcInZpc2l0RW5kXCI7XG59KShUaW1pbmdNZXRyaWMgfHwgKFRpbWluZ01ldHJpYyA9IHt9KSk7XG52YXIgVmlzaXRTdGF0ZTtcbihmdW5jdGlvbiAoVmlzaXRTdGF0ZSkge1xuICAgIFZpc2l0U3RhdGVbXCJpbml0aWFsaXplZFwiXSA9IFwiaW5pdGlhbGl6ZWRcIjtcbiAgICBWaXNpdFN0YXRlW1wic3RhcnRlZFwiXSA9IFwic3RhcnRlZFwiO1xuICAgIFZpc2l0U3RhdGVbXCJjYW5jZWxlZFwiXSA9IFwiY2FuY2VsZWRcIjtcbiAgICBWaXNpdFN0YXRlW1wiZmFpbGVkXCJdID0gXCJmYWlsZWRcIjtcbiAgICBWaXNpdFN0YXRlW1wiY29tcGxldGVkXCJdID0gXCJjb21wbGV0ZWRcIjtcbn0pKFZpc2l0U3RhdGUgfHwgKFZpc2l0U3RhdGUgPSB7fSkpO1xuY29uc3QgZGVmYXVsdE9wdGlvbnMgPSB7XG4gICAgYWN0aW9uOiBcImFkdmFuY2VcIixcbiAgICBoaXN0b3J5Q2hhbmdlZDogZmFsc2Vcbn07XG52YXIgU3lzdGVtU3RhdHVzQ29kZTtcbihmdW5jdGlvbiAoU3lzdGVtU3RhdHVzQ29kZSkge1xuICAgIFN5c3RlbVN0YXR1c0NvZGVbU3lzdGVtU3RhdHVzQ29kZVtcIm5ldHdvcmtGYWlsdXJlXCJdID0gMF0gPSBcIm5ldHdvcmtGYWlsdXJlXCI7XG4gICAgU3lzdGVtU3RhdHVzQ29kZVtTeXN0ZW1TdGF0dXNDb2RlW1widGltZW91dEZhaWx1cmVcIl0gPSAtMV0gPSBcInRpbWVvdXRGYWlsdXJlXCI7XG4gICAgU3lzdGVtU3RhdHVzQ29kZVtTeXN0ZW1TdGF0dXNDb2RlW1wiY29udGVudFR5cGVNaXNtYXRjaFwiXSA9IC0yXSA9IFwiY29udGVudFR5cGVNaXNtYXRjaFwiO1xufSkoU3lzdGVtU3RhdHVzQ29kZSB8fCAoU3lzdGVtU3RhdHVzQ29kZSA9IHt9KSk7XG5jbGFzcyBWaXNpdCB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUsIGxvY2F0aW9uLCByZXN0b3JhdGlvbklkZW50aWZpZXIsIG9wdGlvbnMgPSB7fSkge1xuICAgICAgICB0aGlzLmlkZW50aWZpZXIgPSB1dWlkKCk7XG4gICAgICAgIHRoaXMudGltaW5nTWV0cmljcyA9IHt9O1xuICAgICAgICB0aGlzLmZvbGxvd2VkUmVkaXJlY3QgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5oaXN0b3J5Q2hhbmdlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLnNjcm9sbGVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMuc25hcHNob3RDYWNoZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5zdGF0ZSA9IFZpc2l0U3RhdGUuaW5pdGlhbGl6ZWQ7XG4gICAgICAgIHRoaXMucGVyZm9ybVNjcm9sbCA9ICgpID0+IHtcbiAgICAgICAgICAgIGlmICghdGhpcy5zY3JvbGxlZCkge1xuICAgICAgICAgICAgICAgIGlmICh0aGlzLmFjdGlvbiA9PSBcInJlc3RvcmVcIikge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLnNjcm9sbFRvUmVzdG9yZWRQb3NpdGlvbigpIHx8IHRoaXMuc2Nyb2xsVG9Ub3AoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuc2Nyb2xsVG9BbmNob3IoKSB8fCB0aGlzLnNjcm9sbFRvVG9wKCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIHRoaXMuc2Nyb2xsZWQgPSB0cnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICB9O1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMubG9jYXRpb24gPSBsb2NhdGlvbjtcbiAgICAgICAgdGhpcy5yZXN0b3JhdGlvbklkZW50aWZpZXIgPSByZXN0b3JhdGlvbklkZW50aWZpZXIgfHwgdXVpZCgpO1xuICAgICAgICBjb25zdCB7IGFjdGlvbiwgaGlzdG9yeUNoYW5nZWQsIHJlZmVycmVyLCBzbmFwc2hvdEhUTUwsIHJlc3BvbnNlIH0gPSBPYmplY3QuYXNzaWduKE9iamVjdC5hc3NpZ24oe30sIGRlZmF1bHRPcHRpb25zKSwgb3B0aW9ucyk7XG4gICAgICAgIHRoaXMuYWN0aW9uID0gYWN0aW9uO1xuICAgICAgICB0aGlzLmhpc3RvcnlDaGFuZ2VkID0gaGlzdG9yeUNoYW5nZWQ7XG4gICAgICAgIHRoaXMucmVmZXJyZXIgPSByZWZlcnJlcjtcbiAgICAgICAgdGhpcy5zbmFwc2hvdEhUTUwgPSBzbmFwc2hvdEhUTUw7XG4gICAgICAgIHRoaXMucmVzcG9uc2UgPSByZXNwb25zZTtcbiAgICB9XG4gICAgZ2V0IGFkYXB0ZXIoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmRlbGVnYXRlLmFkYXB0ZXI7XG4gICAgfVxuICAgIGdldCB2aWV3KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5kZWxlZ2F0ZS52aWV3O1xuICAgIH1cbiAgICBnZXQgaGlzdG9yeSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVsZWdhdGUuaGlzdG9yeTtcbiAgICB9XG4gICAgZ2V0IHJlc3RvcmF0aW9uRGF0YSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuaGlzdG9yeS5nZXRSZXN0b3JhdGlvbkRhdGFGb3JJZGVudGlmaWVyKHRoaXMucmVzdG9yYXRpb25JZGVudGlmaWVyKTtcbiAgICB9XG4gICAgc3RhcnQoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXRlID09IFZpc2l0U3RhdGUuaW5pdGlhbGl6ZWQpIHtcbiAgICAgICAgICAgIHRoaXMucmVjb3JkVGltaW5nTWV0cmljKFRpbWluZ01ldHJpYy52aXNpdFN0YXJ0KTtcbiAgICAgICAgICAgIHRoaXMuc3RhdGUgPSBWaXNpdFN0YXRlLnN0YXJ0ZWQ7XG4gICAgICAgICAgICB0aGlzLmFkYXB0ZXIudmlzaXRTdGFydGVkKHRoaXMpO1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS52aXNpdFN0YXJ0ZWQodGhpcyk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY2FuY2VsKCkge1xuICAgICAgICBpZiAodGhpcy5zdGF0ZSA9PSBWaXNpdFN0YXRlLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGlmICh0aGlzLnJlcXVlc3QpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnJlcXVlc3QuY2FuY2VsKCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0aGlzLmNhbmNlbFJlbmRlcigpO1xuICAgICAgICAgICAgdGhpcy5zdGF0ZSA9IFZpc2l0U3RhdGUuY2FuY2VsZWQ7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY29tcGxldGUoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXRlID09IFZpc2l0U3RhdGUuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5yZWNvcmRUaW1pbmdNZXRyaWMoVGltaW5nTWV0cmljLnZpc2l0RW5kKTtcbiAgICAgICAgICAgIHRoaXMuc3RhdGUgPSBWaXNpdFN0YXRlLmNvbXBsZXRlZDtcbiAgICAgICAgICAgIHRoaXMuYWRhcHRlci52aXNpdENvbXBsZXRlZCh0aGlzKTtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUudmlzaXRDb21wbGV0ZWQodGhpcyk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZmFpbCgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhdGUgPT0gVmlzaXRTdGF0ZS5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXRlID0gVmlzaXRTdGF0ZS5mYWlsZWQ7XG4gICAgICAgICAgICB0aGlzLmFkYXB0ZXIudmlzaXRGYWlsZWQodGhpcyk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY2hhbmdlSGlzdG9yeSgpIHtcbiAgICAgICAgaWYgKCF0aGlzLmhpc3RvcnlDaGFuZ2VkKSB7XG4gICAgICAgICAgICBjb25zdCBhY3Rpb25Gb3JIaXN0b3J5ID0gdGhpcy5sb2NhdGlvbi5pc0VxdWFsVG8odGhpcy5yZWZlcnJlcikgPyBcInJlcGxhY2VcIiA6IHRoaXMuYWN0aW9uO1xuICAgICAgICAgICAgY29uc3QgbWV0aG9kID0gdGhpcy5nZXRIaXN0b3J5TWV0aG9kRm9yQWN0aW9uKGFjdGlvbkZvckhpc3RvcnkpO1xuICAgICAgICAgICAgdGhpcy5oaXN0b3J5LnVwZGF0ZShtZXRob2QsIHRoaXMubG9jYXRpb24sIHRoaXMucmVzdG9yYXRpb25JZGVudGlmaWVyKTtcbiAgICAgICAgICAgIHRoaXMuaGlzdG9yeUNoYW5nZWQgPSB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIGlzc3VlUmVxdWVzdCgpIHtcbiAgICAgICAgaWYgKHRoaXMuaGFzUHJlbG9hZGVkUmVzcG9uc2UoKSkge1xuICAgICAgICAgICAgdGhpcy5zaW11bGF0ZVJlcXVlc3QoKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmICh0aGlzLnNob3VsZElzc3VlUmVxdWVzdCgpICYmICF0aGlzLnJlcXVlc3QpIHtcbiAgICAgICAgICAgIHRoaXMucmVxdWVzdCA9IG5ldyBGZXRjaFJlcXVlc3QodGhpcywgRmV0Y2hNZXRob2QuZ2V0LCB0aGlzLmxvY2F0aW9uKTtcbiAgICAgICAgICAgIHRoaXMucmVxdWVzdC5wZXJmb3JtKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc2ltdWxhdGVSZXF1ZXN0KCkge1xuICAgICAgICBpZiAodGhpcy5yZXNwb25zZSkge1xuICAgICAgICAgICAgdGhpcy5zdGFydFJlcXVlc3QoKTtcbiAgICAgICAgICAgIHRoaXMucmVjb3JkUmVzcG9uc2UoKTtcbiAgICAgICAgICAgIHRoaXMuZmluaXNoUmVxdWVzdCgpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0YXJ0UmVxdWVzdCgpIHtcbiAgICAgICAgdGhpcy5yZWNvcmRUaW1pbmdNZXRyaWMoVGltaW5nTWV0cmljLnJlcXVlc3RTdGFydCk7XG4gICAgICAgIHRoaXMuYWRhcHRlci52aXNpdFJlcXVlc3RTdGFydGVkKHRoaXMpO1xuICAgIH1cbiAgICByZWNvcmRSZXNwb25zZShyZXNwb25zZSA9IHRoaXMucmVzcG9uc2UpIHtcbiAgICAgICAgdGhpcy5yZXNwb25zZSA9IHJlc3BvbnNlO1xuICAgICAgICBpZiAocmVzcG9uc2UpIHtcbiAgICAgICAgICAgIGNvbnN0IHsgc3RhdHVzQ29kZSB9ID0gcmVzcG9uc2U7XG4gICAgICAgICAgICBpZiAoaXNTdWNjZXNzZnVsKHN0YXR1c0NvZGUpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5hZGFwdGVyLnZpc2l0UmVxdWVzdENvbXBsZXRlZCh0aGlzKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIHRoaXMuYWRhcHRlci52aXNpdFJlcXVlc3RGYWlsZWRXaXRoU3RhdHVzQ29kZSh0aGlzLCBzdGF0dXNDb2RlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbiAgICBmaW5pc2hSZXF1ZXN0KCkge1xuICAgICAgICB0aGlzLnJlY29yZFRpbWluZ01ldHJpYyhUaW1pbmdNZXRyaWMucmVxdWVzdEVuZCk7XG4gICAgICAgIHRoaXMuYWRhcHRlci52aXNpdFJlcXVlc3RGaW5pc2hlZCh0aGlzKTtcbiAgICB9XG4gICAgbG9hZFJlc3BvbnNlKCkge1xuICAgICAgICBpZiAodGhpcy5yZXNwb25zZSkge1xuICAgICAgICAgICAgY29uc3QgeyBzdGF0dXNDb2RlLCByZXNwb25zZUhUTUwgfSA9IHRoaXMucmVzcG9uc2U7XG4gICAgICAgICAgICB0aGlzLnJlbmRlcigoKSA9PiB7XG4gICAgICAgICAgICAgICAgdGhpcy5jYWNoZVNuYXBzaG90KCk7XG4gICAgICAgICAgICAgICAgaWYgKGlzU3VjY2Vzc2Z1bChzdGF0dXNDb2RlKSAmJiByZXNwb25zZUhUTUwgIT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLnZpZXcucmVuZGVyKHsgc25hcHNob3Q6IFNuYXBzaG90LmZyb21IVE1MU3RyaW5nKHJlc3BvbnNlSFRNTCkgfSwgdGhpcy5wZXJmb3JtU2Nyb2xsKTtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5hZGFwdGVyLnZpc2l0UmVuZGVyZWQodGhpcyk7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuY29tcGxldGUoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMudmlldy5yZW5kZXIoeyBlcnJvcjogcmVzcG9uc2VIVE1MIH0sIHRoaXMucGVyZm9ybVNjcm9sbCk7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuYWRhcHRlci52aXNpdFJlbmRlcmVkKHRoaXMpO1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmZhaWwoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXRDYWNoZWRTbmFwc2hvdCgpIHtcbiAgICAgICAgY29uc3Qgc25hcHNob3QgPSB0aGlzLnZpZXcuZ2V0Q2FjaGVkU25hcHNob3RGb3JMb2NhdGlvbih0aGlzLmxvY2F0aW9uKSB8fCB0aGlzLmdldFByZWxvYWRlZFNuYXBzaG90KCk7XG4gICAgICAgIGlmIChzbmFwc2hvdCAmJiAoIXRoaXMubG9jYXRpb24uYW5jaG9yIHx8IHNuYXBzaG90Lmhhc0FuY2hvcih0aGlzLmxvY2F0aW9uLmFuY2hvcikpKSB7XG4gICAgICAgICAgICBpZiAodGhpcy5hY3Rpb24gPT0gXCJyZXN0b3JlXCIgfHwgc25hcHNob3QuaXNQcmV2aWV3YWJsZSgpKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHNuYXBzaG90O1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIGdldFByZWxvYWRlZFNuYXBzaG90KCkge1xuICAgICAgICBpZiAodGhpcy5zbmFwc2hvdEhUTUwpIHtcbiAgICAgICAgICAgIHJldHVybiBTbmFwc2hvdC53cmFwKHRoaXMuc25hcHNob3RIVE1MKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBoYXNDYWNoZWRTbmFwc2hvdCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0Q2FjaGVkU25hcHNob3QoKSAhPSBudWxsO1xuICAgIH1cbiAgICBsb2FkQ2FjaGVkU25hcHNob3QoKSB7XG4gICAgICAgIGNvbnN0IHNuYXBzaG90ID0gdGhpcy5nZXRDYWNoZWRTbmFwc2hvdCgpO1xuICAgICAgICBpZiAoc25hcHNob3QpIHtcbiAgICAgICAgICAgIGNvbnN0IGlzUHJldmlldyA9IHRoaXMuc2hvdWxkSXNzdWVSZXF1ZXN0KCk7XG4gICAgICAgICAgICB0aGlzLnJlbmRlcigoKSA9PiB7XG4gICAgICAgICAgICAgICAgdGhpcy5jYWNoZVNuYXBzaG90KCk7XG4gICAgICAgICAgICAgICAgdGhpcy52aWV3LnJlbmRlcih7IHNuYXBzaG90LCBpc1ByZXZpZXcgfSwgdGhpcy5wZXJmb3JtU2Nyb2xsKTtcbiAgICAgICAgICAgICAgICB0aGlzLmFkYXB0ZXIudmlzaXRSZW5kZXJlZCh0aGlzKTtcbiAgICAgICAgICAgICAgICBpZiAoIWlzUHJldmlldykge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmNvbXBsZXRlKCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZm9sbG93UmVkaXJlY3QoKSB7XG4gICAgICAgIGlmICh0aGlzLnJlZGlyZWN0ZWRUb0xvY2F0aW9uICYmICF0aGlzLmZvbGxvd2VkUmVkaXJlY3QpIHtcbiAgICAgICAgICAgIHRoaXMubG9jYXRpb24gPSB0aGlzLnJlZGlyZWN0ZWRUb0xvY2F0aW9uO1xuICAgICAgICAgICAgdGhpcy5oaXN0b3J5LnJlcGxhY2UodGhpcy5yZWRpcmVjdGVkVG9Mb2NhdGlvbiwgdGhpcy5yZXN0b3JhdGlvbklkZW50aWZpZXIpO1xuICAgICAgICAgICAgdGhpcy5mb2xsb3dlZFJlZGlyZWN0ID0gdHJ1ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZXF1ZXN0U3RhcnRlZCgpIHtcbiAgICAgICAgdGhpcy5zdGFydFJlcXVlc3QoKTtcbiAgICB9XG4gICAgcmVxdWVzdFByZXZlbnRlZEhhbmRsaW5nUmVzcG9uc2UocmVxdWVzdCwgcmVzcG9uc2UpIHtcbiAgICB9XG4gICAgYXN5bmMgcmVxdWVzdFN1Y2NlZWRlZFdpdGhSZXNwb25zZShyZXF1ZXN0LCByZXNwb25zZSkge1xuICAgICAgICBjb25zdCByZXNwb25zZUhUTUwgPSBhd2FpdCByZXNwb25zZS5yZXNwb25zZUhUTUw7XG4gICAgICAgIGlmIChyZXNwb25zZUhUTUwgPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICB0aGlzLnJlY29yZFJlc3BvbnNlKHsgc3RhdHVzQ29kZTogU3lzdGVtU3RhdHVzQ29kZS5jb250ZW50VHlwZU1pc21hdGNoIH0pO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5yZWRpcmVjdGVkVG9Mb2NhdGlvbiA9IHJlc3BvbnNlLnJlZGlyZWN0ZWQgPyByZXNwb25zZS5sb2NhdGlvbiA6IHVuZGVmaW5lZDtcbiAgICAgICAgICAgIHRoaXMucmVjb3JkUmVzcG9uc2UoeyBzdGF0dXNDb2RlOiByZXNwb25zZS5zdGF0dXNDb2RlLCByZXNwb25zZUhUTUwgfSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgYXN5bmMgcmVxdWVzdEZhaWxlZFdpdGhSZXNwb25zZShyZXF1ZXN0LCByZXNwb25zZSkge1xuICAgICAgICBjb25zdCByZXNwb25zZUhUTUwgPSBhd2FpdCByZXNwb25zZS5yZXNwb25zZUhUTUw7XG4gICAgICAgIGlmIChyZXNwb25zZUhUTUwgPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICB0aGlzLnJlY29yZFJlc3BvbnNlKHsgc3RhdHVzQ29kZTogU3lzdGVtU3RhdHVzQ29kZS5jb250ZW50VHlwZU1pc21hdGNoIH0pO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5yZWNvcmRSZXNwb25zZSh7IHN0YXR1c0NvZGU6IHJlc3BvbnNlLnN0YXR1c0NvZGUsIHJlc3BvbnNlSFRNTCB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZXF1ZXN0RXJyb3JlZChyZXF1ZXN0LCBlcnJvcikge1xuICAgICAgICB0aGlzLnJlY29yZFJlc3BvbnNlKHsgc3RhdHVzQ29kZTogU3lzdGVtU3RhdHVzQ29kZS5uZXR3b3JrRmFpbHVyZSB9KTtcbiAgICB9XG4gICAgcmVxdWVzdEZpbmlzaGVkKCkge1xuICAgICAgICB0aGlzLmZpbmlzaFJlcXVlc3QoKTtcbiAgICB9XG4gICAgc2Nyb2xsVG9SZXN0b3JlZFBvc2l0aW9uKCkge1xuICAgICAgICBjb25zdCB7IHNjcm9sbFBvc2l0aW9uIH0gPSB0aGlzLnJlc3RvcmF0aW9uRGF0YTtcbiAgICAgICAgaWYgKHNjcm9sbFBvc2l0aW9uKSB7XG4gICAgICAgICAgICB0aGlzLnZpZXcuc2Nyb2xsVG9Qb3NpdGlvbihzY3JvbGxQb3NpdGlvbik7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzY3JvbGxUb0FuY2hvcigpIHtcbiAgICAgICAgaWYgKHRoaXMubG9jYXRpb24uYW5jaG9yICE9IG51bGwpIHtcbiAgICAgICAgICAgIHRoaXMudmlldy5zY3JvbGxUb0FuY2hvcih0aGlzLmxvY2F0aW9uLmFuY2hvcik7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzY3JvbGxUb1RvcCgpIHtcbiAgICAgICAgdGhpcy52aWV3LnNjcm9sbFRvUG9zaXRpb24oeyB4OiAwLCB5OiAwIH0pO1xuICAgIH1cbiAgICByZWNvcmRUaW1pbmdNZXRyaWMobWV0cmljKSB7XG4gICAgICAgIHRoaXMudGltaW5nTWV0cmljc1ttZXRyaWNdID0gbmV3IERhdGUoKS5nZXRUaW1lKCk7XG4gICAgfVxuICAgIGdldFRpbWluZ01ldHJpY3MoKSB7XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKHt9LCB0aGlzLnRpbWluZ01ldHJpY3MpO1xuICAgIH1cbiAgICBnZXRIaXN0b3J5TWV0aG9kRm9yQWN0aW9uKGFjdGlvbikge1xuICAgICAgICBzd2l0Y2ggKGFjdGlvbikge1xuICAgICAgICAgICAgY2FzZSBcInJlcGxhY2VcIjogcmV0dXJuIGhpc3RvcnkucmVwbGFjZVN0YXRlO1xuICAgICAgICAgICAgY2FzZSBcImFkdmFuY2VcIjpcbiAgICAgICAgICAgIGNhc2UgXCJyZXN0b3JlXCI6IHJldHVybiBoaXN0b3J5LnB1c2hTdGF0ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBoYXNQcmVsb2FkZWRSZXNwb25zZSgpIHtcbiAgICAgICAgcmV0dXJuIHR5cGVvZiB0aGlzLnJlc3BvbnNlID09IFwib2JqZWN0XCI7XG4gICAgfVxuICAgIHNob3VsZElzc3VlUmVxdWVzdCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYWN0aW9uID09IFwicmVzdG9yZVwiXG4gICAgICAgICAgICA/ICF0aGlzLmhhc0NhY2hlZFNuYXBzaG90KClcbiAgICAgICAgICAgIDogdHJ1ZTtcbiAgICB9XG4gICAgY2FjaGVTbmFwc2hvdCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnNuYXBzaG90Q2FjaGVkKSB7XG4gICAgICAgICAgICB0aGlzLnZpZXcuY2FjaGVTbmFwc2hvdCgpO1xuICAgICAgICAgICAgdGhpcy5zbmFwc2hvdENhY2hlZCA9IHRydWU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVuZGVyKGNhbGxiYWNrKSB7XG4gICAgICAgIHRoaXMuY2FuY2VsUmVuZGVyKCk7XG4gICAgICAgIHRoaXMuZnJhbWUgPSByZXF1ZXN0QW5pbWF0aW9uRnJhbWUoKCkgPT4ge1xuICAgICAgICAgICAgZGVsZXRlIHRoaXMuZnJhbWU7XG4gICAgICAgICAgICBjYWxsYmFjay5jYWxsKHRoaXMpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgY2FuY2VsUmVuZGVyKCkge1xuICAgICAgICBpZiAodGhpcy5mcmFtZSkge1xuICAgICAgICAgICAgY2FuY2VsQW5pbWF0aW9uRnJhbWUodGhpcy5mcmFtZSk7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy5mcmFtZTtcbiAgICAgICAgfVxuICAgIH1cbn1cbmZ1bmN0aW9uIGlzU3VjY2Vzc2Z1bChzdGF0dXNDb2RlKSB7XG4gICAgcmV0dXJuIHN0YXR1c0NvZGUgPj0gMjAwICYmIHN0YXR1c0NvZGUgPCAzMDA7XG59XG5cbmNsYXNzIEJyb3dzZXJBZGFwdGVyIHtcbiAgICBjb25zdHJ1Y3RvcihzZXNzaW9uKSB7XG4gICAgICAgIHRoaXMucHJvZ3Jlc3NCYXIgPSBuZXcgUHJvZ3Jlc3NCYXI7XG4gICAgICAgIHRoaXMuc2hvd1Byb2dyZXNzQmFyID0gKCkgPT4ge1xuICAgICAgICAgICAgdGhpcy5wcm9ncmVzc0Jhci5zaG93KCk7XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuc2Vzc2lvbiA9IHNlc3Npb247XG4gICAgfVxuICAgIHZpc2l0UHJvcG9zZWRUb0xvY2F0aW9uKGxvY2F0aW9uLCBvcHRpb25zKSB7XG4gICAgICAgIHRoaXMubmF2aWdhdG9yLnN0YXJ0VmlzaXQobG9jYXRpb24sIHV1aWQoKSwgb3B0aW9ucyk7XG4gICAgfVxuICAgIHZpc2l0U3RhcnRlZCh2aXNpdCkge1xuICAgICAgICB2aXNpdC5pc3N1ZVJlcXVlc3QoKTtcbiAgICAgICAgdmlzaXQuY2hhbmdlSGlzdG9yeSgpO1xuICAgICAgICB2aXNpdC5sb2FkQ2FjaGVkU25hcHNob3QoKTtcbiAgICB9XG4gICAgdmlzaXRSZXF1ZXN0U3RhcnRlZCh2aXNpdCkge1xuICAgICAgICB0aGlzLnByb2dyZXNzQmFyLnNldFZhbHVlKDApO1xuICAgICAgICBpZiAodmlzaXQuaGFzQ2FjaGVkU25hcHNob3QoKSB8fCB2aXNpdC5hY3Rpb24gIT0gXCJyZXN0b3JlXCIpIHtcbiAgICAgICAgICAgIHRoaXMuc2hvd1Byb2dyZXNzQmFyQWZ0ZXJEZWxheSgpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5zaG93UHJvZ3Jlc3NCYXIoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICB2aXNpdFJlcXVlc3RDb21wbGV0ZWQodmlzaXQpIHtcbiAgICAgICAgdmlzaXQubG9hZFJlc3BvbnNlKCk7XG4gICAgfVxuICAgIHZpc2l0UmVxdWVzdEZhaWxlZFdpdGhTdGF0dXNDb2RlKHZpc2l0LCBzdGF0dXNDb2RlKSB7XG4gICAgICAgIHN3aXRjaCAoc3RhdHVzQ29kZSkge1xuICAgICAgICAgICAgY2FzZSBTeXN0ZW1TdGF0dXNDb2RlLm5ldHdvcmtGYWlsdXJlOlxuICAgICAgICAgICAgY2FzZSBTeXN0ZW1TdGF0dXNDb2RlLnRpbWVvdXRGYWlsdXJlOlxuICAgICAgICAgICAgY2FzZSBTeXN0ZW1TdGF0dXNDb2RlLmNvbnRlbnRUeXBlTWlzbWF0Y2g6XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMucmVsb2FkKCk7XG4gICAgICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgICAgIHJldHVybiB2aXNpdC5sb2FkUmVzcG9uc2UoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICB2aXNpdFJlcXVlc3RGaW5pc2hlZCh2aXNpdCkge1xuICAgICAgICB0aGlzLnByb2dyZXNzQmFyLnNldFZhbHVlKDEpO1xuICAgICAgICB0aGlzLmhpZGVQcm9ncmVzc0JhcigpO1xuICAgIH1cbiAgICB2aXNpdENvbXBsZXRlZCh2aXNpdCkge1xuICAgICAgICB2aXNpdC5mb2xsb3dSZWRpcmVjdCgpO1xuICAgIH1cbiAgICBwYWdlSW52YWxpZGF0ZWQoKSB7XG4gICAgICAgIHRoaXMucmVsb2FkKCk7XG4gICAgfVxuICAgIHZpc2l0RmFpbGVkKHZpc2l0KSB7XG4gICAgfVxuICAgIHZpc2l0UmVuZGVyZWQodmlzaXQpIHtcbiAgICB9XG4gICAgc2hvd1Byb2dyZXNzQmFyQWZ0ZXJEZWxheSgpIHtcbiAgICAgICAgdGhpcy5wcm9ncmVzc0JhclRpbWVvdXQgPSB3aW5kb3cuc2V0VGltZW91dCh0aGlzLnNob3dQcm9ncmVzc0JhciwgdGhpcy5zZXNzaW9uLnByb2dyZXNzQmFyRGVsYXkpO1xuICAgIH1cbiAgICBoaWRlUHJvZ3Jlc3NCYXIoKSB7XG4gICAgICAgIHRoaXMucHJvZ3Jlc3NCYXIuaGlkZSgpO1xuICAgICAgICBpZiAodGhpcy5wcm9ncmVzc0JhclRpbWVvdXQgIT0gbnVsbCkge1xuICAgICAgICAgICAgd2luZG93LmNsZWFyVGltZW91dCh0aGlzLnByb2dyZXNzQmFyVGltZW91dCk7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy5wcm9ncmVzc0JhclRpbWVvdXQ7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVsb2FkKCkge1xuICAgICAgICB3aW5kb3cubG9jYXRpb24ucmVsb2FkKCk7XG4gICAgfVxuICAgIGdldCBuYXZpZ2F0b3IoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNlc3Npb24ubmF2aWdhdG9yO1xuICAgIH1cbn1cblxuY2xhc3MgRm9ybVN1Ym1pdE9ic2VydmVyIHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5zdWJtaXRDYXB0dXJlZCA9ICgpID0+IHtcbiAgICAgICAgICAgIHJlbW92ZUV2ZW50TGlzdGVuZXIoXCJzdWJtaXRcIiwgdGhpcy5zdWJtaXRCdWJibGVkLCBmYWxzZSk7XG4gICAgICAgICAgICBhZGRFdmVudExpc3RlbmVyKFwic3VibWl0XCIsIHRoaXMuc3VibWl0QnViYmxlZCwgZmFsc2UpO1xuICAgICAgICB9O1xuICAgICAgICB0aGlzLnN1Ym1pdEJ1YmJsZWQgPSAoKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBpZiAoIWV2ZW50LmRlZmF1bHRQcmV2ZW50ZWQpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBmb3JtID0gZXZlbnQudGFyZ2V0IGluc3RhbmNlb2YgSFRNTEZvcm1FbGVtZW50ID8gZXZlbnQudGFyZ2V0IDogdW5kZWZpbmVkO1xuICAgICAgICAgICAgICAgIGNvbnN0IHN1Ym1pdHRlciA9IGV2ZW50LnN1Ym1pdHRlciB8fCB1bmRlZmluZWQ7XG4gICAgICAgICAgICAgICAgaWYgKGZvcm0pIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUud2lsbFN1Ym1pdEZvcm0oZm9ybSwgc3VibWl0dGVyKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZm9ybVN1Ym1pdHRlZChmb3JtLCBzdWJtaXR0ZXIpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGFkZEV2ZW50TGlzdGVuZXIoXCJzdWJtaXRcIiwgdGhpcy5zdWJtaXRDYXB0dXJlZCwgdHJ1ZSk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHJlbW92ZUV2ZW50TGlzdGVuZXIoXCJzdWJtaXRcIiwgdGhpcy5zdWJtaXRDYXB0dXJlZCwgdHJ1ZSk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgfVxuICAgIH1cbn1cblxuY2xhc3MgRnJhbWVSZWRpcmVjdG9yIHtcbiAgICBjb25zdHJ1Y3RvcihlbGVtZW50KSB7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMubGlua0ludGVyY2VwdG9yID0gbmV3IExpbmtJbnRlcmNlcHRvcih0aGlzLCBlbGVtZW50KTtcbiAgICAgICAgdGhpcy5mb3JtSW50ZXJjZXB0b3IgPSBuZXcgRm9ybUludGVyY2VwdG9yKHRoaXMsIGVsZW1lbnQpO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgdGhpcy5saW5rSW50ZXJjZXB0b3Iuc3RhcnQoKTtcbiAgICAgICAgdGhpcy5mb3JtSW50ZXJjZXB0b3Iuc3RhcnQoKTtcbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgdGhpcy5saW5rSW50ZXJjZXB0b3Iuc3RvcCgpO1xuICAgICAgICB0aGlzLmZvcm1JbnRlcmNlcHRvci5zdG9wKCk7XG4gICAgfVxuICAgIHNob3VsZEludGVyY2VwdExpbmtDbGljayhlbGVtZW50LCB1cmwpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2hvdWxkUmVkaXJlY3QoZWxlbWVudCk7XG4gICAgfVxuICAgIGxpbmtDbGlja0ludGVyY2VwdGVkKGVsZW1lbnQsIHVybCkge1xuICAgICAgICBjb25zdCBmcmFtZSA9IHRoaXMuZmluZEZyYW1lRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgaWYgKGZyYW1lKSB7XG4gICAgICAgICAgICBmcmFtZS5zcmMgPSB1cmw7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc2hvdWxkSW50ZXJjZXB0Rm9ybVN1Ym1pc3Npb24oZWxlbWVudCwgc3VibWl0dGVyKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNob3VsZFJlZGlyZWN0KGVsZW1lbnQsIHN1Ym1pdHRlcik7XG4gICAgfVxuICAgIGZvcm1TdWJtaXNzaW9uSW50ZXJjZXB0ZWQoZWxlbWVudCwgc3VibWl0dGVyKSB7XG4gICAgICAgIGNvbnN0IGZyYW1lID0gdGhpcy5maW5kRnJhbWVFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICBpZiAoZnJhbWUpIHtcbiAgICAgICAgICAgIGZyYW1lLmZvcm1TdWJtaXNzaW9uSW50ZXJjZXB0ZWQoZWxlbWVudCwgc3VibWl0dGVyKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzaG91bGRSZWRpcmVjdChlbGVtZW50LCBzdWJtaXR0ZXIpIHtcbiAgICAgICAgY29uc3QgZnJhbWUgPSB0aGlzLmZpbmRGcmFtZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIHJldHVybiBmcmFtZSA/IGZyYW1lICE9IGVsZW1lbnQuY2xvc2VzdChcInR1cmJvLWZyYW1lXCIpIDogZmFsc2U7XG4gICAgfVxuICAgIGZpbmRGcmFtZUVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBjb25zdCBpZCA9IGVsZW1lbnQuZ2V0QXR0cmlidXRlKFwiZGF0YS10dXJiby1mcmFtZVwiKTtcbiAgICAgICAgaWYgKGlkICYmIGlkICE9IFwiX3RvcFwiKSB7XG4gICAgICAgICAgICBjb25zdCBmcmFtZSA9IHRoaXMuZWxlbWVudC5xdWVyeVNlbGVjdG9yKGAjJHtpZH06bm90KFtkaXNhYmxlZF0pYCk7XG4gICAgICAgICAgICBpZiAoZnJhbWUgaW5zdGFuY2VvZiBGcmFtZUVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gZnJhbWU7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG59XG5cbmNsYXNzIEhpc3Rvcnkge1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMucmVzdG9yYXRpb25JZGVudGlmaWVyID0gdXVpZCgpO1xuICAgICAgICB0aGlzLnJlc3RvcmF0aW9uRGF0YSA9IHt9O1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5wYWdlTG9hZGVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMub25Qb3BTdGF0ZSA9IChldmVudCkgPT4ge1xuICAgICAgICAgICAgaWYgKHRoaXMuc2hvdWxkSGFuZGxlUG9wU3RhdGUoKSkge1xuICAgICAgICAgICAgICAgIGNvbnN0IHsgdHVyYm8gfSA9IGV2ZW50LnN0YXRlIHx8IHt9O1xuICAgICAgICAgICAgICAgIGlmICh0dXJibykge1xuICAgICAgICAgICAgICAgICAgICBjb25zdCBsb2NhdGlvbiA9IExvY2F0aW9uLmN1cnJlbnRMb2NhdGlvbjtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5sb2NhdGlvbiA9IGxvY2F0aW9uO1xuICAgICAgICAgICAgICAgICAgICBjb25zdCB7IHJlc3RvcmF0aW9uSWRlbnRpZmllciB9ID0gdHVyYm87XG4gICAgICAgICAgICAgICAgICAgIHRoaXMucmVzdG9yYXRpb25JZGVudGlmaWVyID0gcmVzdG9yYXRpb25JZGVudGlmaWVyO1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmhpc3RvcnlQb3BwZWRUb0xvY2F0aW9uV2l0aFJlc3RvcmF0aW9uSWRlbnRpZmllcihsb2NhdGlvbiwgcmVzdG9yYXRpb25JZGVudGlmaWVyKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMub25QYWdlTG9hZCA9IGFzeW5jIChldmVudCkgPT4ge1xuICAgICAgICAgICAgYXdhaXQgbmV4dE1pY3JvdGFzaygpO1xuICAgICAgICAgICAgdGhpcy5wYWdlTG9hZGVkID0gdHJ1ZTtcbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMucHJldmlvdXNTY3JvbGxSZXN0b3JhdGlvbiA9IGhpc3Rvcnkuc2Nyb2xsUmVzdG9yYXRpb247XG4gICAgICAgICAgICBoaXN0b3J5LnNjcm9sbFJlc3RvcmF0aW9uID0gXCJtYW51YWxcIjtcbiAgICAgICAgICAgIGFkZEV2ZW50TGlzdGVuZXIoXCJwb3BzdGF0ZVwiLCB0aGlzLm9uUG9wU3RhdGUsIGZhbHNlKTtcbiAgICAgICAgICAgIGFkZEV2ZW50TGlzdGVuZXIoXCJsb2FkXCIsIHRoaXMub25QYWdlTG9hZCwgZmFsc2UpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gdHJ1ZTtcbiAgICAgICAgICAgIHRoaXMucmVwbGFjZShMb2NhdGlvbi5jdXJyZW50TG9jYXRpb24pO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIHZhciBfYTtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgaGlzdG9yeS5zY3JvbGxSZXN0b3JhdGlvbiA9IChfYSA9IHRoaXMucHJldmlvdXNTY3JvbGxSZXN0b3JhdGlvbikgIT09IG51bGwgJiYgX2EgIT09IHZvaWQgMCA/IF9hIDogXCJhdXRvXCI7XG4gICAgICAgICAgICByZW1vdmVFdmVudExpc3RlbmVyKFwicG9wc3RhdGVcIiwgdGhpcy5vblBvcFN0YXRlLCBmYWxzZSk7XG4gICAgICAgICAgICByZW1vdmVFdmVudExpc3RlbmVyKFwibG9hZFwiLCB0aGlzLm9uUGFnZUxvYWQsIGZhbHNlKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHB1c2gobG9jYXRpb24sIHJlc3RvcmF0aW9uSWRlbnRpZmllcikge1xuICAgICAgICB0aGlzLnVwZGF0ZShoaXN0b3J5LnB1c2hTdGF0ZSwgbG9jYXRpb24sIHJlc3RvcmF0aW9uSWRlbnRpZmllcik7XG4gICAgfVxuICAgIHJlcGxhY2UobG9jYXRpb24sIHJlc3RvcmF0aW9uSWRlbnRpZmllcikge1xuICAgICAgICB0aGlzLnVwZGF0ZShoaXN0b3J5LnJlcGxhY2VTdGF0ZSwgbG9jYXRpb24sIHJlc3RvcmF0aW9uSWRlbnRpZmllcik7XG4gICAgfVxuICAgIHVwZGF0ZShtZXRob2QsIGxvY2F0aW9uLCByZXN0b3JhdGlvbklkZW50aWZpZXIgPSB1dWlkKCkpIHtcbiAgICAgICAgY29uc3Qgc3RhdGUgPSB7IHR1cmJvOiB7IHJlc3RvcmF0aW9uSWRlbnRpZmllciB9IH07XG4gICAgICAgIG1ldGhvZC5jYWxsKGhpc3RvcnksIHN0YXRlLCBcIlwiLCBsb2NhdGlvbi5hYnNvbHV0ZVVSTCk7XG4gICAgICAgIHRoaXMubG9jYXRpb24gPSBsb2NhdGlvbjtcbiAgICAgICAgdGhpcy5yZXN0b3JhdGlvbklkZW50aWZpZXIgPSByZXN0b3JhdGlvbklkZW50aWZpZXI7XG4gICAgfVxuICAgIGdldFJlc3RvcmF0aW9uRGF0YUZvcklkZW50aWZpZXIocmVzdG9yYXRpb25JZGVudGlmaWVyKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnJlc3RvcmF0aW9uRGF0YVtyZXN0b3JhdGlvbklkZW50aWZpZXJdIHx8IHt9O1xuICAgIH1cbiAgICB1cGRhdGVSZXN0b3JhdGlvbkRhdGEoYWRkaXRpb25hbERhdGEpIHtcbiAgICAgICAgY29uc3QgeyByZXN0b3JhdGlvbklkZW50aWZpZXIgfSA9IHRoaXM7XG4gICAgICAgIGNvbnN0IHJlc3RvcmF0aW9uRGF0YSA9IHRoaXMucmVzdG9yYXRpb25EYXRhW3Jlc3RvcmF0aW9uSWRlbnRpZmllcl07XG4gICAgICAgIHRoaXMucmVzdG9yYXRpb25EYXRhW3Jlc3RvcmF0aW9uSWRlbnRpZmllcl0gPSBPYmplY3QuYXNzaWduKE9iamVjdC5hc3NpZ24oe30sIHJlc3RvcmF0aW9uRGF0YSksIGFkZGl0aW9uYWxEYXRhKTtcbiAgICB9XG4gICAgc2hvdWxkSGFuZGxlUG9wU3RhdGUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnBhZ2VJc0xvYWRlZCgpO1xuICAgIH1cbiAgICBwYWdlSXNMb2FkZWQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnBhZ2VMb2FkZWQgfHwgZG9jdW1lbnQucmVhZHlTdGF0ZSA9PSBcImNvbXBsZXRlXCI7XG4gICAgfVxufVxuXG5jbGFzcyBMaW5rQ2xpY2tPYnNlcnZlciB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMuY2xpY2tDYXB0dXJlZCA9ICgpID0+IHtcbiAgICAgICAgICAgIHJlbW92ZUV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCB0aGlzLmNsaWNrQnViYmxlZCwgZmFsc2UpO1xuICAgICAgICAgICAgYWRkRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsIHRoaXMuY2xpY2tCdWJibGVkLCBmYWxzZSk7XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuY2xpY2tCdWJibGVkID0gKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBpZiAodGhpcy5jbGlja0V2ZW50SXNTaWduaWZpY2FudChldmVudCkpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBsaW5rID0gdGhpcy5maW5kTGlua0Zyb21DbGlja1RhcmdldChldmVudC50YXJnZXQpO1xuICAgICAgICAgICAgICAgIGlmIChsaW5rKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnN0IGxvY2F0aW9uID0gdGhpcy5nZXRMb2NhdGlvbkZvckxpbmsobGluayk7XG4gICAgICAgICAgICAgICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLndpbGxGb2xsb3dMaW5rVG9Mb2NhdGlvbihsaW5rLCBsb2NhdGlvbikpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmZvbGxvd2VkTGlua1RvTG9jYXRpb24obGluaywgbG9jYXRpb24pO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9O1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICBpZiAoIXRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgYWRkRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsIHRoaXMuY2xpY2tDYXB0dXJlZCwgdHJ1ZSk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHJlbW92ZUV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCB0aGlzLmNsaWNrQ2FwdHVyZWQsIHRydWUpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY2xpY2tFdmVudElzU2lnbmlmaWNhbnQoZXZlbnQpIHtcbiAgICAgICAgcmV0dXJuICEoKGV2ZW50LnRhcmdldCAmJiBldmVudC50YXJnZXQuaXNDb250ZW50RWRpdGFibGUpXG4gICAgICAgICAgICB8fCBldmVudC5kZWZhdWx0UHJldmVudGVkXG4gICAgICAgICAgICB8fCBldmVudC53aGljaCA+IDFcbiAgICAgICAgICAgIHx8IGV2ZW50LmFsdEtleVxuICAgICAgICAgICAgfHwgZXZlbnQuY3RybEtleVxuICAgICAgICAgICAgfHwgZXZlbnQubWV0YUtleVxuICAgICAgICAgICAgfHwgZXZlbnQuc2hpZnRLZXkpO1xuICAgIH1cbiAgICBmaW5kTGlua0Zyb21DbGlja1RhcmdldCh0YXJnZXQpIHtcbiAgICAgICAgaWYgKHRhcmdldCBpbnN0YW5jZW9mIEVsZW1lbnQpIHtcbiAgICAgICAgICAgIHJldHVybiB0YXJnZXQuY2xvc2VzdChcImFbaHJlZl06bm90KFt0YXJnZXRePV9dKTpub3QoW2Rvd25sb2FkXSlcIik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0TG9jYXRpb25Gb3JMaW5rKGxpbmspIHtcbiAgICAgICAgcmV0dXJuIG5ldyBMb2NhdGlvbihsaW5rLmdldEF0dHJpYnV0ZShcImhyZWZcIikgfHwgXCJcIik7XG4gICAgfVxufVxuXG5jbGFzcyBOYXZpZ2F0b3Ige1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICB9XG4gICAgcHJvcG9zZVZpc2l0KGxvY2F0aW9uLCBvcHRpb25zID0ge30pIHtcbiAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuYWxsb3dzVmlzaXRpbmdMb2NhdGlvbihsb2NhdGlvbikpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUudmlzaXRQcm9wb3NlZFRvTG9jYXRpb24obG9jYXRpb24sIG9wdGlvbnMpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0YXJ0VmlzaXQobG9jYXRpb24sIHJlc3RvcmF0aW9uSWRlbnRpZmllciwgb3B0aW9ucyA9IHt9KSB7XG4gICAgICAgIHRoaXMuc3RvcCgpO1xuICAgICAgICB0aGlzLmN1cnJlbnRWaXNpdCA9IG5ldyBWaXNpdCh0aGlzLCBMb2NhdGlvbi53cmFwKGxvY2F0aW9uKSwgcmVzdG9yYXRpb25JZGVudGlmaWVyLCBPYmplY3QuYXNzaWduKHsgcmVmZXJyZXI6IHRoaXMubG9jYXRpb24gfSwgb3B0aW9ucykpO1xuICAgICAgICB0aGlzLmN1cnJlbnRWaXNpdC5zdGFydCgpO1xuICAgIH1cbiAgICBzdWJtaXRGb3JtKGZvcm0sIHN1Ym1pdHRlcikge1xuICAgICAgICB0aGlzLnN0b3AoKTtcbiAgICAgICAgdGhpcy5mb3JtU3VibWlzc2lvbiA9IG5ldyBGb3JtU3VibWlzc2lvbih0aGlzLCBmb3JtLCBzdWJtaXR0ZXIsIHRydWUpO1xuICAgICAgICB0aGlzLmZvcm1TdWJtaXNzaW9uLnN0YXJ0KCk7XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIGlmICh0aGlzLmZvcm1TdWJtaXNzaW9uKSB7XG4gICAgICAgICAgICB0aGlzLmZvcm1TdWJtaXNzaW9uLnN0b3AoKTtcbiAgICAgICAgICAgIGRlbGV0ZSB0aGlzLmZvcm1TdWJtaXNzaW9uO1xuICAgICAgICB9XG4gICAgICAgIGlmICh0aGlzLmN1cnJlbnRWaXNpdCkge1xuICAgICAgICAgICAgdGhpcy5jdXJyZW50VmlzaXQuY2FuY2VsKCk7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy5jdXJyZW50VmlzaXQ7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0IGFkYXB0ZXIoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmRlbGVnYXRlLmFkYXB0ZXI7XG4gICAgfVxuICAgIGdldCB2aWV3KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5kZWxlZ2F0ZS52aWV3O1xuICAgIH1cbiAgICBnZXQgaGlzdG9yeSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVsZWdhdGUuaGlzdG9yeTtcbiAgICB9XG4gICAgZm9ybVN1Ym1pc3Npb25TdGFydGVkKGZvcm1TdWJtaXNzaW9uKSB7XG4gICAgfVxuICAgIGFzeW5jIGZvcm1TdWJtaXNzaW9uU3VjY2VlZGVkV2l0aFJlc3BvbnNlKGZvcm1TdWJtaXNzaW9uLCBmZXRjaFJlc3BvbnNlKSB7XG4gICAgICAgIGNvbnNvbGUubG9nKFwiRm9ybSBzdWJtaXNzaW9uIHN1Y2NlZWRlZFwiLCBmb3JtU3VibWlzc2lvbik7XG4gICAgICAgIGlmIChmb3JtU3VibWlzc2lvbiA9PSB0aGlzLmZvcm1TdWJtaXNzaW9uKSB7XG4gICAgICAgICAgICBjb25zdCByZXNwb25zZUhUTUwgPSBhd2FpdCBmZXRjaFJlc3BvbnNlLnJlc3BvbnNlSFRNTDtcbiAgICAgICAgICAgIGlmIChyZXNwb25zZUhUTUwpIHtcbiAgICAgICAgICAgICAgICBpZiAoZm9ybVN1Ym1pc3Npb24ubWV0aG9kICE9IEZldGNoTWV0aG9kLmdldCkge1xuICAgICAgICAgICAgICAgICAgICBjb25zb2xlLmxvZyhcIkNsZWFyaW5nIHNuYXBzaG90IGNhY2hlIGFmdGVyIHN1Y2Nlc3NmdWwgZm9ybSBzdWJtaXNzaW9uXCIpO1xuICAgICAgICAgICAgICAgICAgICB0aGlzLnZpZXcuY2xlYXJTbmFwc2hvdENhY2hlKCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGNvbnN0IHsgc3RhdHVzQ29kZSB9ID0gZmV0Y2hSZXNwb25zZTtcbiAgICAgICAgICAgICAgICBjb25zdCB2aXNpdE9wdGlvbnMgPSB7IHJlc3BvbnNlOiB7IHN0YXR1c0NvZGUsIHJlc3BvbnNlSFRNTCB9IH07XG4gICAgICAgICAgICAgICAgY29uc29sZS5sb2coXCJWaXNpdGluZ1wiLCBmZXRjaFJlc3BvbnNlLmxvY2F0aW9uLCB2aXNpdE9wdGlvbnMpO1xuICAgICAgICAgICAgICAgIHRoaXMucHJvcG9zZVZpc2l0KGZldGNoUmVzcG9uc2UubG9jYXRpb24sIHZpc2l0T3B0aW9ucyk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG4gICAgZm9ybVN1Ym1pc3Npb25GYWlsZWRXaXRoUmVzcG9uc2UoZm9ybVN1Ym1pc3Npb24sIGZldGNoUmVzcG9uc2UpIHtcbiAgICAgICAgY29uc29sZS5lcnJvcihcIkZvcm0gc3VibWlzc2lvbiBmYWlsZWRcIiwgZm9ybVN1Ym1pc3Npb24sIGZldGNoUmVzcG9uc2UpO1xuICAgIH1cbiAgICBmb3JtU3VibWlzc2lvbkVycm9yZWQoZm9ybVN1Ym1pc3Npb24sIGVycm9yKSB7XG4gICAgICAgIGNvbnNvbGUuZXJyb3IoXCJGb3JtIHN1Ym1pc3Npb24gZmFpbGVkXCIsIGZvcm1TdWJtaXNzaW9uLCBlcnJvcik7XG4gICAgfVxuICAgIGZvcm1TdWJtaXNzaW9uRmluaXNoZWQoZm9ybVN1Ym1pc3Npb24pIHtcbiAgICB9XG4gICAgdmlzaXRTdGFydGVkKHZpc2l0KSB7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUudmlzaXRTdGFydGVkKHZpc2l0KTtcbiAgICB9XG4gICAgdmlzaXRDb21wbGV0ZWQodmlzaXQpIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS52aXNpdENvbXBsZXRlZCh2aXNpdCk7XG4gICAgfVxuICAgIGdldCBsb2NhdGlvbigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuaGlzdG9yeS5sb2NhdGlvbjtcbiAgICB9XG4gICAgZ2V0IHJlc3RvcmF0aW9uSWRlbnRpZmllcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuaGlzdG9yeS5yZXN0b3JhdGlvbklkZW50aWZpZXI7XG4gICAgfVxufVxuXG52YXIgUGFnZVN0YWdlO1xuKGZ1bmN0aW9uIChQYWdlU3RhZ2UpIHtcbiAgICBQYWdlU3RhZ2VbUGFnZVN0YWdlW1wiaW5pdGlhbFwiXSA9IDBdID0gXCJpbml0aWFsXCI7XG4gICAgUGFnZVN0YWdlW1BhZ2VTdGFnZVtcImxvYWRpbmdcIl0gPSAxXSA9IFwibG9hZGluZ1wiO1xuICAgIFBhZ2VTdGFnZVtQYWdlU3RhZ2VbXCJpbnRlcmFjdGl2ZVwiXSA9IDJdID0gXCJpbnRlcmFjdGl2ZVwiO1xuICAgIFBhZ2VTdGFnZVtQYWdlU3RhZ2VbXCJjb21wbGV0ZVwiXSA9IDNdID0gXCJjb21wbGV0ZVwiO1xuICAgIFBhZ2VTdGFnZVtQYWdlU3RhZ2VbXCJpbnZhbGlkYXRlZFwiXSA9IDRdID0gXCJpbnZhbGlkYXRlZFwiO1xufSkoUGFnZVN0YWdlIHx8IChQYWdlU3RhZ2UgPSB7fSkpO1xuY2xhc3MgUGFnZU9ic2VydmVyIHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLnN0YWdlID0gUGFnZVN0YWdlLmluaXRpYWw7XG4gICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLmludGVycHJldFJlYWR5U3RhdGUgPSAoKSA9PiB7XG4gICAgICAgICAgICBjb25zdCB7IHJlYWR5U3RhdGUgfSA9IHRoaXM7XG4gICAgICAgICAgICBpZiAocmVhZHlTdGF0ZSA9PSBcImludGVyYWN0aXZlXCIpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnBhZ2VJc0ludGVyYWN0aXZlKCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIGlmIChyZWFkeVN0YXRlID09IFwiY29tcGxldGVcIikge1xuICAgICAgICAgICAgICAgIHRoaXMucGFnZUlzQ29tcGxldGUoKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGlmICh0aGlzLnN0YWdlID09IFBhZ2VTdGFnZS5pbml0aWFsKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5zdGFnZSA9IFBhZ2VTdGFnZS5sb2FkaW5nO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcInJlYWR5c3RhdGVjaGFuZ2VcIiwgdGhpcy5pbnRlcnByZXRSZWFkeVN0YXRlLCBmYWxzZSk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGRvY3VtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoXCJyZWFkeXN0YXRlY2hhbmdlXCIsIHRoaXMuaW50ZXJwcmV0UmVhZHlTdGF0ZSwgZmFsc2UpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9XG4gICAgaW52YWxpZGF0ZSgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhZ2UgIT0gUGFnZVN0YWdlLmludmFsaWRhdGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YWdlID0gUGFnZVN0YWdlLmludmFsaWRhdGVkO1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5wYWdlSW52YWxpZGF0ZWQoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBwYWdlSXNJbnRlcmFjdGl2ZSgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhZ2UgPT0gUGFnZVN0YWdlLmxvYWRpbmcpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhZ2UgPSBQYWdlU3RhZ2UuaW50ZXJhY3RpdmU7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnBhZ2VCZWNhbWVJbnRlcmFjdGl2ZSgpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHBhZ2VJc0NvbXBsZXRlKCkge1xuICAgICAgICB0aGlzLnBhZ2VJc0ludGVyYWN0aXZlKCk7XG4gICAgICAgIGlmICh0aGlzLnN0YWdlID09IFBhZ2VTdGFnZS5pbnRlcmFjdGl2ZSkge1xuICAgICAgICAgICAgdGhpcy5zdGFnZSA9IFBhZ2VTdGFnZS5jb21wbGV0ZTtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUucGFnZUxvYWRlZCgpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldCByZWFkeVN0YXRlKCkge1xuICAgICAgICByZXR1cm4gZG9jdW1lbnQucmVhZHlTdGF0ZTtcbiAgICB9XG59XG5cbmNsYXNzIFNjcm9sbE9ic2VydmVyIHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5vblNjcm9sbCA9ICgpID0+IHtcbiAgICAgICAgICAgIHRoaXMudXBkYXRlUG9zaXRpb24oeyB4OiB3aW5kb3cucGFnZVhPZmZzZXQsIHk6IHdpbmRvdy5wYWdlWU9mZnNldCB9KTtcbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGFkZEV2ZW50TGlzdGVuZXIoXCJzY3JvbGxcIiwgdGhpcy5vblNjcm9sbCwgZmFsc2UpO1xuICAgICAgICAgICAgdGhpcy5vblNjcm9sbCgpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gdHJ1ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICByZW1vdmVFdmVudExpc3RlbmVyKFwic2Nyb2xsXCIsIHRoaXMub25TY3JvbGwsIGZhbHNlKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHVwZGF0ZVBvc2l0aW9uKHBvc2l0aW9uKSB7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUuc2Nyb2xsUG9zaXRpb25DaGFuZ2VkKHBvc2l0aW9uKTtcbiAgICB9XG59XG5cbmNsYXNzIFN0cmVhbU1lc3NhZ2Uge1xuICAgIGNvbnN0cnVjdG9yKGh0bWwpIHtcbiAgICAgICAgdGhpcy50ZW1wbGF0ZUVsZW1lbnQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwidGVtcGxhdGVcIik7XG4gICAgICAgIHRoaXMudGVtcGxhdGVFbGVtZW50LmlubmVySFRNTCA9IGh0bWw7XG4gICAgfVxuICAgIHN0YXRpYyB3cmFwKG1lc3NhZ2UpIHtcbiAgICAgICAgaWYgKHR5cGVvZiBtZXNzYWdlID09IFwic3RyaW5nXCIpIHtcbiAgICAgICAgICAgIHJldHVybiBuZXcgdGhpcyhtZXNzYWdlKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBtZXNzYWdlO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldCBmcmFnbWVudCgpIHtcbiAgICAgICAgY29uc3QgZnJhZ21lbnQgPSBkb2N1bWVudC5jcmVhdGVEb2N1bWVudEZyYWdtZW50KCk7XG4gICAgICAgIGZvciAoY29uc3QgZWxlbWVudCBvZiB0aGlzLmZvcmVpZ25FbGVtZW50cykge1xuICAgICAgICAgICAgZnJhZ21lbnQuYXBwZW5kQ2hpbGQoZG9jdW1lbnQuaW1wb3J0Tm9kZShlbGVtZW50LCB0cnVlKSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGZyYWdtZW50O1xuICAgIH1cbiAgICBnZXQgZm9yZWlnbkVsZW1lbnRzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy50ZW1wbGF0ZUNoaWxkcmVuLnJlZHVjZSgoc3RyZWFtRWxlbWVudHMsIGNoaWxkKSA9PiB7XG4gICAgICAgICAgICBpZiAoY2hpbGQudGFnTmFtZS50b0xvd2VyQ2FzZSgpID09IFwidHVyYm8tc3RyZWFtXCIpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gWy4uLnN0cmVhbUVsZW1lbnRzLCBjaGlsZF07XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gc3RyZWFtRWxlbWVudHM7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sIFtdKTtcbiAgICB9XG4gICAgZ2V0IHRlbXBsYXRlQ2hpbGRyZW4oKSB7XG4gICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMudGVtcGxhdGVFbGVtZW50LmNvbnRlbnQuY2hpbGRyZW4pO1xuICAgIH1cbn1cblxuY2xhc3MgU3RyZWFtT2JzZXJ2ZXIge1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuc291cmNlcyA9IG5ldyBTZXQ7XG4gICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLnByZXBhcmVGZXRjaFJlcXVlc3QgPSAoKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICB2YXIgX2E7XG4gICAgICAgICAgICBjb25zdCBmZXRjaE9wdGlvbnMgPSAoX2EgPSBldmVudC5kZXRhaWwpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5mZXRjaE9wdGlvbnM7XG4gICAgICAgICAgICBpZiAoZmV0Y2hPcHRpb25zKSB7XG4gICAgICAgICAgICAgICAgY29uc3QgeyBoZWFkZXJzIH0gPSBmZXRjaE9wdGlvbnM7XG4gICAgICAgICAgICAgICAgaGVhZGVycy5BY2NlcHQgPSBbXCJ0ZXh0L2h0bWw7IHR1cmJvLXN0cmVhbVwiLCBoZWFkZXJzLkFjY2VwdF0uam9pbihcIiwgXCIpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy5pbnNwZWN0RmV0Y2hSZXNwb25zZSA9ICgoZXZlbnQpID0+IHtcbiAgICAgICAgICAgIGNvbnN0IHJlc3BvbnNlID0gZmV0Y2hSZXNwb25zZUZyb21FdmVudChldmVudCk7XG4gICAgICAgICAgICBpZiAocmVzcG9uc2UgJiYgZmV0Y2hSZXNwb25zZUlzU3RyZWFtKHJlc3BvbnNlKSkge1xuICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgdGhpcy5yZWNlaXZlTWVzc2FnZVJlc3BvbnNlKHJlc3BvbnNlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICAgIHRoaXMucmVjZWl2ZU1lc3NhZ2VFdmVudCA9IChldmVudCkgPT4ge1xuICAgICAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCAmJiB0eXBlb2YgZXZlbnQuZGF0YSA9PSBcInN0cmluZ1wiKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5yZWNlaXZlTWVzc2FnZUhUTUwoZXZlbnQuZGF0YSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICB9XG4gICAgc3RhcnQoKSB7XG4gICAgICAgIGlmICghdGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICAgICAgYWRkRXZlbnRMaXN0ZW5lcihcInR1cmJvOmJlZm9yZS1mZXRjaC1yZXF1ZXN0XCIsIHRoaXMucHJlcGFyZUZldGNoUmVxdWVzdCwgdHJ1ZSk7XG4gICAgICAgICAgICBhZGRFdmVudExpc3RlbmVyKFwidHVyYm86YmVmb3JlLWZldGNoLXJlc3BvbnNlXCIsIHRoaXMuaW5zcGVjdEZldGNoUmVzcG9uc2UsIGZhbHNlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgICAgIHJlbW92ZUV2ZW50TGlzdGVuZXIoXCJ0dXJibzpiZWZvcmUtZmV0Y2gtcmVxdWVzdFwiLCB0aGlzLnByZXBhcmVGZXRjaFJlcXVlc3QsIHRydWUpO1xuICAgICAgICAgICAgcmVtb3ZlRXZlbnRMaXN0ZW5lcihcInR1cmJvOmJlZm9yZS1mZXRjaC1yZXNwb25zZVwiLCB0aGlzLmluc3BlY3RGZXRjaFJlc3BvbnNlLCBmYWxzZSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY29ubmVjdFN0cmVhbVNvdXJjZShzb3VyY2UpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0cmVhbVNvdXJjZUlzQ29ubmVjdGVkKHNvdXJjZSkpIHtcbiAgICAgICAgICAgIHRoaXMuc291cmNlcy5hZGQoc291cmNlKTtcbiAgICAgICAgICAgIHNvdXJjZS5hZGRFdmVudExpc3RlbmVyKFwibWVzc2FnZVwiLCB0aGlzLnJlY2VpdmVNZXNzYWdlRXZlbnQsIGZhbHNlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBkaXNjb25uZWN0U3RyZWFtU291cmNlKHNvdXJjZSkge1xuICAgICAgICBpZiAodGhpcy5zdHJlYW1Tb3VyY2VJc0Nvbm5lY3RlZChzb3VyY2UpKSB7XG4gICAgICAgICAgICB0aGlzLnNvdXJjZXMuZGVsZXRlKHNvdXJjZSk7XG4gICAgICAgICAgICBzb3VyY2UucmVtb3ZlRXZlbnRMaXN0ZW5lcihcIm1lc3NhZ2VcIiwgdGhpcy5yZWNlaXZlTWVzc2FnZUV2ZW50LCBmYWxzZSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RyZWFtU291cmNlSXNDb25uZWN0ZWQoc291cmNlKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNvdXJjZXMuaGFzKHNvdXJjZSk7XG4gICAgfVxuICAgIGFzeW5jIHJlY2VpdmVNZXNzYWdlUmVzcG9uc2UocmVzcG9uc2UpIHtcbiAgICAgICAgY29uc3QgaHRtbCA9IGF3YWl0IHJlc3BvbnNlLnJlc3BvbnNlSFRNTDtcbiAgICAgICAgaWYgKGh0bWwpIHtcbiAgICAgICAgICAgIHRoaXMucmVjZWl2ZU1lc3NhZ2VIVE1MKGh0bWwpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJlY2VpdmVNZXNzYWdlSFRNTChodG1sKSB7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUucmVjZWl2ZWRNZXNzYWdlRnJvbVN0cmVhbShuZXcgU3RyZWFtTWVzc2FnZShodG1sKSk7XG4gICAgfVxufVxuZnVuY3Rpb24gZmV0Y2hSZXNwb25zZUZyb21FdmVudChldmVudCkge1xuICAgIHZhciBfYTtcbiAgICBjb25zdCBmZXRjaFJlc3BvbnNlID0gKF9hID0gZXZlbnQuZGV0YWlsKSA9PT0gbnVsbCB8fCBfYSA9PT0gdm9pZCAwID8gdm9pZCAwIDogX2EuZmV0Y2hSZXNwb25zZTtcbiAgICBpZiAoZmV0Y2hSZXNwb25zZSBpbnN0YW5jZW9mIEZldGNoUmVzcG9uc2UpIHtcbiAgICAgICAgcmV0dXJuIGZldGNoUmVzcG9uc2U7XG4gICAgfVxufVxuZnVuY3Rpb24gZmV0Y2hSZXNwb25zZUlzU3RyZWFtKHJlc3BvbnNlKSB7XG4gICAgdmFyIF9hO1xuICAgIGNvbnN0IGNvbnRlbnRUeXBlID0gKF9hID0gcmVzcG9uc2UuY29udGVudFR5cGUpICE9PSBudWxsICYmIF9hICE9PSB2b2lkIDAgPyBfYSA6IFwiXCI7XG4gICAgcmV0dXJuIC90ZXh0XFwvaHRtbDsuKlxcYnR1cmJvLXN0cmVhbVxcYi8udGVzdChjb250ZW50VHlwZSk7XG59XG5cbmZ1bmN0aW9uIGlzQWN0aW9uKGFjdGlvbikge1xuICAgIHJldHVybiBhY3Rpb24gPT0gXCJhZHZhbmNlXCIgfHwgYWN0aW9uID09IFwicmVwbGFjZVwiIHx8IGFjdGlvbiA9PSBcInJlc3RvcmVcIjtcbn1cblxuY2xhc3MgUmVuZGVyZXIge1xuICAgIHJlbmRlclZpZXcoY2FsbGJhY2spIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS52aWV3V2lsbFJlbmRlcih0aGlzLm5ld0JvZHkpO1xuICAgICAgICBjYWxsYmFjaygpO1xuICAgICAgICB0aGlzLmRlbGVnYXRlLnZpZXdSZW5kZXJlZCh0aGlzLm5ld0JvZHkpO1xuICAgIH1cbiAgICBpbnZhbGlkYXRlVmlldygpIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS52aWV3SW52YWxpZGF0ZWQoKTtcbiAgICB9XG4gICAgY3JlYXRlU2NyaXB0RWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGlmIChlbGVtZW50LmdldEF0dHJpYnV0ZShcImRhdGEtdHVyYm8tZXZhbFwiKSA9PSBcImZhbHNlXCIpIHtcbiAgICAgICAgICAgIHJldHVybiBlbGVtZW50O1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgY29uc3QgY3JlYXRlZFNjcmlwdEVsZW1lbnQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwic2NyaXB0XCIpO1xuICAgICAgICAgICAgY3JlYXRlZFNjcmlwdEVsZW1lbnQudGV4dENvbnRlbnQgPSBlbGVtZW50LnRleHRDb250ZW50O1xuICAgICAgICAgICAgY3JlYXRlZFNjcmlwdEVsZW1lbnQuYXN5bmMgPSBmYWxzZTtcbiAgICAgICAgICAgIGNvcHlFbGVtZW50QXR0cmlidXRlcyhjcmVhdGVkU2NyaXB0RWxlbWVudCwgZWxlbWVudCk7XG4gICAgICAgICAgICByZXR1cm4gY3JlYXRlZFNjcmlwdEVsZW1lbnQ7XG4gICAgICAgIH1cbiAgICB9XG59XG5mdW5jdGlvbiBjb3B5RWxlbWVudEF0dHJpYnV0ZXMoZGVzdGluYXRpb25FbGVtZW50LCBzb3VyY2VFbGVtZW50KSB7XG4gICAgZm9yIChjb25zdCB7IG5hbWUsIHZhbHVlIH0gb2YgWy4uLnNvdXJjZUVsZW1lbnQuYXR0cmlidXRlc10pIHtcbiAgICAgICAgZGVzdGluYXRpb25FbGVtZW50LnNldEF0dHJpYnV0ZShuYW1lLCB2YWx1ZSk7XG4gICAgfVxufVxuXG5jbGFzcyBFcnJvclJlbmRlcmVyIGV4dGVuZHMgUmVuZGVyZXIge1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlLCBodG1sKSB7XG4gICAgICAgIHN1cGVyKCk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5odG1sRWxlbWVudCA9ICgoKSA9PiB7XG4gICAgICAgICAgICBjb25zdCBodG1sRWxlbWVudCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJodG1sXCIpO1xuICAgICAgICAgICAgaHRtbEVsZW1lbnQuaW5uZXJIVE1MID0gaHRtbDtcbiAgICAgICAgICAgIHJldHVybiBodG1sRWxlbWVudDtcbiAgICAgICAgfSkoKTtcbiAgICAgICAgdGhpcy5uZXdIZWFkID0gdGhpcy5odG1sRWxlbWVudC5xdWVyeVNlbGVjdG9yKFwiaGVhZFwiKSB8fCBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwiaGVhZFwiKTtcbiAgICAgICAgdGhpcy5uZXdCb2R5ID0gdGhpcy5odG1sRWxlbWVudC5xdWVyeVNlbGVjdG9yKFwiYm9keVwiKSB8fCBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwiYm9keVwiKTtcbiAgICB9XG4gICAgc3RhdGljIHJlbmRlcihkZWxlZ2F0ZSwgY2FsbGJhY2ssIGh0bWwpIHtcbiAgICAgICAgcmV0dXJuIG5ldyB0aGlzKGRlbGVnYXRlLCBodG1sKS5yZW5kZXIoY2FsbGJhY2spO1xuICAgIH1cbiAgICByZW5kZXIoY2FsbGJhY2spIHtcbiAgICAgICAgdGhpcy5yZW5kZXJWaWV3KCgpID0+IHtcbiAgICAgICAgICAgIHRoaXMucmVwbGFjZUhlYWRBbmRCb2R5KCk7XG4gICAgICAgICAgICB0aGlzLmFjdGl2YXRlQm9keVNjcmlwdEVsZW1lbnRzKCk7XG4gICAgICAgICAgICBjYWxsYmFjaygpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgcmVwbGFjZUhlYWRBbmRCb2R5KCkge1xuICAgICAgICBjb25zdCB7IGRvY3VtZW50RWxlbWVudCwgaGVhZCwgYm9keSB9ID0gZG9jdW1lbnQ7XG4gICAgICAgIGRvY3VtZW50RWxlbWVudC5yZXBsYWNlQ2hpbGQodGhpcy5uZXdIZWFkLCBoZWFkKTtcbiAgICAgICAgZG9jdW1lbnRFbGVtZW50LnJlcGxhY2VDaGlsZCh0aGlzLm5ld0JvZHksIGJvZHkpO1xuICAgIH1cbiAgICBhY3RpdmF0ZUJvZHlTY3JpcHRFbGVtZW50cygpIHtcbiAgICAgICAgZm9yIChjb25zdCByZXBsYWNlYWJsZUVsZW1lbnQgb2YgdGhpcy5nZXRTY3JpcHRFbGVtZW50cygpKSB7XG4gICAgICAgICAgICBjb25zdCBwYXJlbnROb2RlID0gcmVwbGFjZWFibGVFbGVtZW50LnBhcmVudE5vZGU7XG4gICAgICAgICAgICBpZiAocGFyZW50Tm9kZSkge1xuICAgICAgICAgICAgICAgIGNvbnN0IGVsZW1lbnQgPSB0aGlzLmNyZWF0ZVNjcmlwdEVsZW1lbnQocmVwbGFjZWFibGVFbGVtZW50KTtcbiAgICAgICAgICAgICAgICBwYXJlbnROb2RlLnJlcGxhY2VDaGlsZChlbGVtZW50LCByZXBsYWNlYWJsZUVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIGdldFNjcmlwdEVsZW1lbnRzKCkge1xuICAgICAgICByZXR1cm4gWy4uLmRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5xdWVyeVNlbGVjdG9yQWxsKFwic2NyaXB0XCIpXTtcbiAgICB9XG59XG5cbmNsYXNzIFNuYXBzaG90Q2FjaGUge1xuICAgIGNvbnN0cnVjdG9yKHNpemUpIHtcbiAgICAgICAgdGhpcy5rZXlzID0gW107XG4gICAgICAgIHRoaXMuc25hcHNob3RzID0ge307XG4gICAgICAgIHRoaXMuc2l6ZSA9IHNpemU7XG4gICAgfVxuICAgIGhhcyhsb2NhdGlvbikge1xuICAgICAgICByZXR1cm4gbG9jYXRpb24udG9DYWNoZUtleSgpIGluIHRoaXMuc25hcHNob3RzO1xuICAgIH1cbiAgICBnZXQobG9jYXRpb24pIHtcbiAgICAgICAgaWYgKHRoaXMuaGFzKGxvY2F0aW9uKSkge1xuICAgICAgICAgICAgY29uc3Qgc25hcHNob3QgPSB0aGlzLnJlYWQobG9jYXRpb24pO1xuICAgICAgICAgICAgdGhpcy50b3VjaChsb2NhdGlvbik7XG4gICAgICAgICAgICByZXR1cm4gc25hcHNob3Q7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcHV0KGxvY2F0aW9uLCBzbmFwc2hvdCkge1xuICAgICAgICB0aGlzLndyaXRlKGxvY2F0aW9uLCBzbmFwc2hvdCk7XG4gICAgICAgIHRoaXMudG91Y2gobG9jYXRpb24pO1xuICAgICAgICByZXR1cm4gc25hcHNob3Q7XG4gICAgfVxuICAgIGNsZWFyKCkge1xuICAgICAgICB0aGlzLnNuYXBzaG90cyA9IHt9O1xuICAgIH1cbiAgICByZWFkKGxvY2F0aW9uKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNuYXBzaG90c1tsb2NhdGlvbi50b0NhY2hlS2V5KCldO1xuICAgIH1cbiAgICB3cml0ZShsb2NhdGlvbiwgc25hcHNob3QpIHtcbiAgICAgICAgdGhpcy5zbmFwc2hvdHNbbG9jYXRpb24udG9DYWNoZUtleSgpXSA9IHNuYXBzaG90O1xuICAgIH1cbiAgICB0b3VjaChsb2NhdGlvbikge1xuICAgICAgICBjb25zdCBrZXkgPSBsb2NhdGlvbi50b0NhY2hlS2V5KCk7XG4gICAgICAgIGNvbnN0IGluZGV4ID0gdGhpcy5rZXlzLmluZGV4T2Yoa2V5KTtcbiAgICAgICAgaWYgKGluZGV4ID4gLTEpXG4gICAgICAgICAgICB0aGlzLmtleXMuc3BsaWNlKGluZGV4LCAxKTtcbiAgICAgICAgdGhpcy5rZXlzLnVuc2hpZnQoa2V5KTtcbiAgICAgICAgdGhpcy50cmltKCk7XG4gICAgfVxuICAgIHRyaW0oKSB7XG4gICAgICAgIGZvciAoY29uc3Qga2V5IG9mIHRoaXMua2V5cy5zcGxpY2UodGhpcy5zaXplKSkge1xuICAgICAgICAgICAgZGVsZXRlIHRoaXMuc25hcHNob3RzW2tleV07XG4gICAgICAgIH1cbiAgICB9XG59XG5cbmNsYXNzIFNuYXBzaG90UmVuZGVyZXIgZXh0ZW5kcyBSZW5kZXJlciB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUsIGN1cnJlbnRTbmFwc2hvdCwgbmV3U25hcHNob3QsIGlzUHJldmlldykge1xuICAgICAgICBzdXBlcigpO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMuY3VycmVudFNuYXBzaG90ID0gY3VycmVudFNuYXBzaG90O1xuICAgICAgICB0aGlzLmN1cnJlbnRIZWFkRGV0YWlscyA9IGN1cnJlbnRTbmFwc2hvdC5oZWFkRGV0YWlscztcbiAgICAgICAgdGhpcy5uZXdTbmFwc2hvdCA9IG5ld1NuYXBzaG90O1xuICAgICAgICB0aGlzLm5ld0hlYWREZXRhaWxzID0gbmV3U25hcHNob3QuaGVhZERldGFpbHM7XG4gICAgICAgIHRoaXMubmV3Qm9keSA9IG5ld1NuYXBzaG90LmJvZHlFbGVtZW50O1xuICAgICAgICB0aGlzLmlzUHJldmlldyA9IGlzUHJldmlldztcbiAgICB9XG4gICAgc3RhdGljIHJlbmRlcihkZWxlZ2F0ZSwgY2FsbGJhY2ssIGN1cnJlbnRTbmFwc2hvdCwgbmV3U25hcHNob3QsIGlzUHJldmlldykge1xuICAgICAgICByZXR1cm4gbmV3IHRoaXMoZGVsZWdhdGUsIGN1cnJlbnRTbmFwc2hvdCwgbmV3U25hcHNob3QsIGlzUHJldmlldykucmVuZGVyKGNhbGxiYWNrKTtcbiAgICB9XG4gICAgcmVuZGVyKGNhbGxiYWNrKSB7XG4gICAgICAgIGlmICh0aGlzLnNob3VsZFJlbmRlcigpKSB7XG4gICAgICAgICAgICB0aGlzLm1lcmdlSGVhZCgpO1xuICAgICAgICAgICAgdGhpcy5yZW5kZXJWaWV3KCgpID0+IHtcbiAgICAgICAgICAgICAgICB0aGlzLnJlcGxhY2VCb2R5KCk7XG4gICAgICAgICAgICAgICAgaWYgKCF0aGlzLmlzUHJldmlldykge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmZvY3VzRmlyc3RBdXRvZm9jdXNhYmxlRWxlbWVudCgpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBjYWxsYmFjaygpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLmludmFsaWRhdGVWaWV3KCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgbWVyZ2VIZWFkKCkge1xuICAgICAgICB0aGlzLmNvcHlOZXdIZWFkU3R5bGVzaGVldEVsZW1lbnRzKCk7XG4gICAgICAgIHRoaXMuY29weU5ld0hlYWRTY3JpcHRFbGVtZW50cygpO1xuICAgICAgICB0aGlzLnJlbW92ZUN1cnJlbnRIZWFkUHJvdmlzaW9uYWxFbGVtZW50cygpO1xuICAgICAgICB0aGlzLmNvcHlOZXdIZWFkUHJvdmlzaW9uYWxFbGVtZW50cygpO1xuICAgIH1cbiAgICByZXBsYWNlQm9keSgpIHtcbiAgICAgICAgY29uc3QgcGxhY2Vob2xkZXJzID0gdGhpcy5yZWxvY2F0ZUN1cnJlbnRCb2R5UGVybWFuZW50RWxlbWVudHMoKTtcbiAgICAgICAgdGhpcy5hY3RpdmF0ZU5ld0JvZHkoKTtcbiAgICAgICAgdGhpcy5hc3NpZ25OZXdCb2R5KCk7XG4gICAgICAgIHRoaXMucmVwbGFjZVBsYWNlaG9sZGVyRWxlbWVudHNXaXRoQ2xvbmVkUGVybWFuZW50RWxlbWVudHMocGxhY2Vob2xkZXJzKTtcbiAgICB9XG4gICAgc2hvdWxkUmVuZGVyKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5uZXdTbmFwc2hvdC5pc1Zpc2l0YWJsZSgpICYmIHRoaXMudHJhY2tlZEVsZW1lbnRzQXJlSWRlbnRpY2FsKCk7XG4gICAgfVxuICAgIHRyYWNrZWRFbGVtZW50c0FyZUlkZW50aWNhbCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY3VycmVudEhlYWREZXRhaWxzLmdldFRyYWNrZWRFbGVtZW50U2lnbmF0dXJlKCkgPT0gdGhpcy5uZXdIZWFkRGV0YWlscy5nZXRUcmFja2VkRWxlbWVudFNpZ25hdHVyZSgpO1xuICAgIH1cbiAgICBjb3B5TmV3SGVhZFN0eWxlc2hlZXRFbGVtZW50cygpIHtcbiAgICAgICAgZm9yIChjb25zdCBlbGVtZW50IG9mIHRoaXMuZ2V0TmV3SGVhZFN0eWxlc2hlZXRFbGVtZW50cygpKSB7XG4gICAgICAgICAgICBkb2N1bWVudC5oZWFkLmFwcGVuZENoaWxkKGVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGNvcHlOZXdIZWFkU2NyaXB0RWxlbWVudHMoKSB7XG4gICAgICAgIGZvciAoY29uc3QgZWxlbWVudCBvZiB0aGlzLmdldE5ld0hlYWRTY3JpcHRFbGVtZW50cygpKSB7XG4gICAgICAgICAgICBkb2N1bWVudC5oZWFkLmFwcGVuZENoaWxkKHRoaXMuY3JlYXRlU2NyaXB0RWxlbWVudChlbGVtZW50KSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVtb3ZlQ3VycmVudEhlYWRQcm92aXNpb25hbEVsZW1lbnRzKCkge1xuICAgICAgICBmb3IgKGNvbnN0IGVsZW1lbnQgb2YgdGhpcy5nZXRDdXJyZW50SGVhZFByb3Zpc2lvbmFsRWxlbWVudHMoKSkge1xuICAgICAgICAgICAgZG9jdW1lbnQuaGVhZC5yZW1vdmVDaGlsZChlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBjb3B5TmV3SGVhZFByb3Zpc2lvbmFsRWxlbWVudHMoKSB7XG4gICAgICAgIGZvciAoY29uc3QgZWxlbWVudCBvZiB0aGlzLmdldE5ld0hlYWRQcm92aXNpb25hbEVsZW1lbnRzKCkpIHtcbiAgICAgICAgICAgIGRvY3VtZW50LmhlYWQuYXBwZW5kQ2hpbGQoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVsb2NhdGVDdXJyZW50Qm9keVBlcm1hbmVudEVsZW1lbnRzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRDdXJyZW50Qm9keVBlcm1hbmVudEVsZW1lbnRzKCkucmVkdWNlKChwbGFjZWhvbGRlcnMsIHBlcm1hbmVudEVsZW1lbnQpID0+IHtcbiAgICAgICAgICAgIGNvbnN0IG5ld0VsZW1lbnQgPSB0aGlzLm5ld1NuYXBzaG90LmdldFBlcm1hbmVudEVsZW1lbnRCeUlkKHBlcm1hbmVudEVsZW1lbnQuaWQpO1xuICAgICAgICAgICAgaWYgKG5ld0VsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBwbGFjZWhvbGRlciA9IGNyZWF0ZVBsYWNlaG9sZGVyRm9yUGVybWFuZW50RWxlbWVudChwZXJtYW5lbnRFbGVtZW50KTtcbiAgICAgICAgICAgICAgICByZXBsYWNlRWxlbWVudFdpdGhFbGVtZW50KHBlcm1hbmVudEVsZW1lbnQsIHBsYWNlaG9sZGVyLmVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIHJlcGxhY2VFbGVtZW50V2l0aEVsZW1lbnQobmV3RWxlbWVudCwgcGVybWFuZW50RWxlbWVudCk7XG4gICAgICAgICAgICAgICAgcmV0dXJuIFsuLi5wbGFjZWhvbGRlcnMsIHBsYWNlaG9sZGVyXTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIHJldHVybiBwbGFjZWhvbGRlcnM7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sIFtdKTtcbiAgICB9XG4gICAgcmVwbGFjZVBsYWNlaG9sZGVyRWxlbWVudHNXaXRoQ2xvbmVkUGVybWFuZW50RWxlbWVudHMocGxhY2Vob2xkZXJzKSB7XG4gICAgICAgIGZvciAoY29uc3QgeyBlbGVtZW50LCBwZXJtYW5lbnRFbGVtZW50IH0gb2YgcGxhY2Vob2xkZXJzKSB7XG4gICAgICAgICAgICBjb25zdCBjbG9uZWRFbGVtZW50ID0gcGVybWFuZW50RWxlbWVudC5jbG9uZU5vZGUodHJ1ZSk7XG4gICAgICAgICAgICByZXBsYWNlRWxlbWVudFdpdGhFbGVtZW50KGVsZW1lbnQsIGNsb25lZEVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGFjdGl2YXRlTmV3Qm9keSgpIHtcbiAgICAgICAgZG9jdW1lbnQuYWRvcHROb2RlKHRoaXMubmV3Qm9keSk7XG4gICAgICAgIHRoaXMuYWN0aXZhdGVOZXdCb2R5U2NyaXB0RWxlbWVudHMoKTtcbiAgICB9XG4gICAgYWN0aXZhdGVOZXdCb2R5U2NyaXB0RWxlbWVudHMoKSB7XG4gICAgICAgIGZvciAoY29uc3QgaW5lcnRTY3JpcHRFbGVtZW50IG9mIHRoaXMuZ2V0TmV3Qm9keVNjcmlwdEVsZW1lbnRzKCkpIHtcbiAgICAgICAgICAgIGNvbnN0IGFjdGl2YXRlZFNjcmlwdEVsZW1lbnQgPSB0aGlzLmNyZWF0ZVNjcmlwdEVsZW1lbnQoaW5lcnRTY3JpcHRFbGVtZW50KTtcbiAgICAgICAgICAgIHJlcGxhY2VFbGVtZW50V2l0aEVsZW1lbnQoaW5lcnRTY3JpcHRFbGVtZW50LCBhY3RpdmF0ZWRTY3JpcHRFbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBhc3NpZ25OZXdCb2R5KCkge1xuICAgICAgICBpZiAoZG9jdW1lbnQuYm9keSkge1xuICAgICAgICAgICAgcmVwbGFjZUVsZW1lbnRXaXRoRWxlbWVudChkb2N1bWVudC5ib2R5LCB0aGlzLm5ld0JvZHkpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LmFwcGVuZENoaWxkKHRoaXMubmV3Qm9keSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZm9jdXNGaXJzdEF1dG9mb2N1c2FibGVFbGVtZW50KCkge1xuICAgICAgICBjb25zdCBlbGVtZW50ID0gdGhpcy5uZXdTbmFwc2hvdC5maW5kRmlyc3RBdXRvZm9jdXNhYmxlRWxlbWVudCgpO1xuICAgICAgICBpZiAoZWxlbWVudElzRm9jdXNhYmxlKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICBlbGVtZW50LmZvY3VzKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0TmV3SGVhZFN0eWxlc2hlZXRFbGVtZW50cygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMubmV3SGVhZERldGFpbHMuZ2V0U3R5bGVzaGVldEVsZW1lbnRzTm90SW5EZXRhaWxzKHRoaXMuY3VycmVudEhlYWREZXRhaWxzKTtcbiAgICB9XG4gICAgZ2V0TmV3SGVhZFNjcmlwdEVsZW1lbnRzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5uZXdIZWFkRGV0YWlscy5nZXRTY3JpcHRFbGVtZW50c05vdEluRGV0YWlscyh0aGlzLmN1cnJlbnRIZWFkRGV0YWlscyk7XG4gICAgfVxuICAgIGdldEN1cnJlbnRIZWFkUHJvdmlzaW9uYWxFbGVtZW50cygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY3VycmVudEhlYWREZXRhaWxzLmdldFByb3Zpc2lvbmFsRWxlbWVudHMoKTtcbiAgICB9XG4gICAgZ2V0TmV3SGVhZFByb3Zpc2lvbmFsRWxlbWVudHMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLm5ld0hlYWREZXRhaWxzLmdldFByb3Zpc2lvbmFsRWxlbWVudHMoKTtcbiAgICB9XG4gICAgZ2V0Q3VycmVudEJvZHlQZXJtYW5lbnRFbGVtZW50cygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY3VycmVudFNuYXBzaG90LmdldFBlcm1hbmVudEVsZW1lbnRzUHJlc2VudEluU25hcHNob3QodGhpcy5uZXdTbmFwc2hvdCk7XG4gICAgfVxuICAgIGdldE5ld0JvZHlTY3JpcHRFbGVtZW50cygpIHtcbiAgICAgICAgcmV0dXJuIFsuLi50aGlzLm5ld0JvZHkucXVlcnlTZWxlY3RvckFsbChcInNjcmlwdFwiKV07XG4gICAgfVxufVxuZnVuY3Rpb24gY3JlYXRlUGxhY2Vob2xkZXJGb3JQZXJtYW5lbnRFbGVtZW50KHBlcm1hbmVudEVsZW1lbnQpIHtcbiAgICBjb25zdCBlbGVtZW50ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudChcIm1ldGFcIik7XG4gICAgZWxlbWVudC5zZXRBdHRyaWJ1dGUoXCJuYW1lXCIsIFwidHVyYm8tcGVybWFuZW50LXBsYWNlaG9sZGVyXCIpO1xuICAgIGVsZW1lbnQuc2V0QXR0cmlidXRlKFwiY29udGVudFwiLCBwZXJtYW5lbnRFbGVtZW50LmlkKTtcbiAgICByZXR1cm4geyBlbGVtZW50LCBwZXJtYW5lbnRFbGVtZW50IH07XG59XG5mdW5jdGlvbiByZXBsYWNlRWxlbWVudFdpdGhFbGVtZW50KGZyb21FbGVtZW50LCB0b0VsZW1lbnQpIHtcbiAgICBjb25zdCBwYXJlbnRFbGVtZW50ID0gZnJvbUVsZW1lbnQucGFyZW50RWxlbWVudDtcbiAgICBpZiAocGFyZW50RWxlbWVudCkge1xuICAgICAgICByZXR1cm4gcGFyZW50RWxlbWVudC5yZXBsYWNlQ2hpbGQodG9FbGVtZW50LCBmcm9tRWxlbWVudCk7XG4gICAgfVxufVxuZnVuY3Rpb24gZWxlbWVudElzRm9jdXNhYmxlKGVsZW1lbnQpIHtcbiAgICByZXR1cm4gZWxlbWVudCAmJiB0eXBlb2YgZWxlbWVudC5mb2N1cyA9PSBcImZ1bmN0aW9uXCI7XG59XG5cbmNsYXNzIFZpZXcge1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuaHRtbEVsZW1lbnQgPSBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQ7XG4gICAgICAgIHRoaXMuc25hcHNob3RDYWNoZSA9IG5ldyBTbmFwc2hvdENhY2hlKDEwKTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgIH1cbiAgICBnZXRSb290TG9jYXRpb24oKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldFNuYXBzaG90KCkuZ2V0Um9vdExvY2F0aW9uKCk7XG4gICAgfVxuICAgIGdldEVsZW1lbnRGb3JBbmNob3IoYW5jaG9yKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldFNuYXBzaG90KCkuZ2V0RWxlbWVudEZvckFuY2hvcihhbmNob3IpO1xuICAgIH1cbiAgICBnZXRTbmFwc2hvdCgpIHtcbiAgICAgICAgcmV0dXJuIFNuYXBzaG90LmZyb21IVE1MRWxlbWVudCh0aGlzLmh0bWxFbGVtZW50KTtcbiAgICB9XG4gICAgY2xlYXJTbmFwc2hvdENhY2hlKCkge1xuICAgICAgICB0aGlzLnNuYXBzaG90Q2FjaGUuY2xlYXIoKTtcbiAgICB9XG4gICAgc2hvdWxkQ2FjaGVTbmFwc2hvdCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0U25hcHNob3QoKS5pc0NhY2hlYWJsZSgpO1xuICAgIH1cbiAgICBhc3luYyBjYWNoZVNuYXBzaG90KCkge1xuICAgICAgICBpZiAodGhpcy5zaG91bGRDYWNoZVNuYXBzaG90KCkpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUudmlld1dpbGxDYWNoZVNuYXBzaG90KCk7XG4gICAgICAgICAgICBjb25zdCBzbmFwc2hvdCA9IHRoaXMuZ2V0U25hcHNob3QoKTtcbiAgICAgICAgICAgIGNvbnN0IGxvY2F0aW9uID0gdGhpcy5sYXN0UmVuZGVyZWRMb2NhdGlvbiB8fCBMb2NhdGlvbi5jdXJyZW50TG9jYXRpb247XG4gICAgICAgICAgICBhd2FpdCBuZXh0TWljcm90YXNrKCk7XG4gICAgICAgICAgICB0aGlzLnNuYXBzaG90Q2FjaGUucHV0KGxvY2F0aW9uLCBzbmFwc2hvdC5jbG9uZSgpKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXRDYWNoZWRTbmFwc2hvdEZvckxvY2F0aW9uKGxvY2F0aW9uKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNuYXBzaG90Q2FjaGUuZ2V0KGxvY2F0aW9uKTtcbiAgICB9XG4gICAgcmVuZGVyKHsgc25hcHNob3QsIGVycm9yLCBpc1ByZXZpZXcgfSwgY2FsbGJhY2spIHtcbiAgICAgICAgdGhpcy5tYXJrQXNQcmV2aWV3KGlzUHJldmlldyk7XG4gICAgICAgIGlmIChzbmFwc2hvdCkge1xuICAgICAgICAgICAgdGhpcy5yZW5kZXJTbmFwc2hvdChzbmFwc2hvdCwgaXNQcmV2aWV3LCBjYWxsYmFjayk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLnJlbmRlckVycm9yKGVycm9yLCBjYWxsYmFjayk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc2Nyb2xsVG9BbmNob3IoYW5jaG9yKSB7XG4gICAgICAgIGNvbnN0IGVsZW1lbnQgPSB0aGlzLmdldEVsZW1lbnRGb3JBbmNob3IoYW5jaG9yKTtcbiAgICAgICAgaWYgKGVsZW1lbnQpIHtcbiAgICAgICAgICAgIHRoaXMuc2Nyb2xsVG9FbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5zY3JvbGxUb1Bvc2l0aW9uKHsgeDogMCwgeTogMCB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzY3JvbGxUb0VsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBlbGVtZW50LnNjcm9sbEludG9WaWV3KCk7XG4gICAgfVxuICAgIHNjcm9sbFRvUG9zaXRpb24oeyB4LCB5IH0pIHtcbiAgICAgICAgd2luZG93LnNjcm9sbFRvKHgsIHkpO1xuICAgIH1cbiAgICBtYXJrQXNQcmV2aWV3KGlzUHJldmlldykge1xuICAgICAgICBpZiAoaXNQcmV2aWV3KSB7XG4gICAgICAgICAgICB0aGlzLmh0bWxFbGVtZW50LnNldEF0dHJpYnV0ZShcImRhdGEtdHVyYm8tcHJldmlld1wiLCBcIlwiKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMuaHRtbEVsZW1lbnQucmVtb3ZlQXR0cmlidXRlKFwiZGF0YS10dXJiby1wcmV2aWV3XCIpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJlbmRlclNuYXBzaG90KHNuYXBzaG90LCBpc1ByZXZpZXcsIGNhbGxiYWNrKSB7XG4gICAgICAgIFNuYXBzaG90UmVuZGVyZXIucmVuZGVyKHRoaXMuZGVsZWdhdGUsIGNhbGxiYWNrLCB0aGlzLmdldFNuYXBzaG90KCksIHNuYXBzaG90LCBpc1ByZXZpZXcgfHwgZmFsc2UpO1xuICAgIH1cbiAgICByZW5kZXJFcnJvcihlcnJvciwgY2FsbGJhY2spIHtcbiAgICAgICAgRXJyb3JSZW5kZXJlci5yZW5kZXIodGhpcy5kZWxlZ2F0ZSwgY2FsbGJhY2ssIGVycm9yIHx8IFwiXCIpO1xuICAgIH1cbn1cblxuY2xhc3MgU2Vzc2lvbiB7XG4gICAgY29uc3RydWN0b3IoKSB7XG4gICAgICAgIHRoaXMubmF2aWdhdG9yID0gbmV3IE5hdmlnYXRvcih0aGlzKTtcbiAgICAgICAgdGhpcy5oaXN0b3J5ID0gbmV3IEhpc3RvcnkodGhpcyk7XG4gICAgICAgIHRoaXMudmlldyA9IG5ldyBWaWV3KHRoaXMpO1xuICAgICAgICB0aGlzLmFkYXB0ZXIgPSBuZXcgQnJvd3NlckFkYXB0ZXIodGhpcyk7XG4gICAgICAgIHRoaXMucGFnZU9ic2VydmVyID0gbmV3IFBhZ2VPYnNlcnZlcih0aGlzKTtcbiAgICAgICAgdGhpcy5saW5rQ2xpY2tPYnNlcnZlciA9IG5ldyBMaW5rQ2xpY2tPYnNlcnZlcih0aGlzKTtcbiAgICAgICAgdGhpcy5mb3JtU3VibWl0T2JzZXJ2ZXIgPSBuZXcgRm9ybVN1Ym1pdE9ic2VydmVyKHRoaXMpO1xuICAgICAgICB0aGlzLnNjcm9sbE9ic2VydmVyID0gbmV3IFNjcm9sbE9ic2VydmVyKHRoaXMpO1xuICAgICAgICB0aGlzLnN0cmVhbU9ic2VydmVyID0gbmV3IFN0cmVhbU9ic2VydmVyKHRoaXMpO1xuICAgICAgICB0aGlzLmZyYW1lUmVkaXJlY3RvciA9IG5ldyBGcmFtZVJlZGlyZWN0b3IoZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50KTtcbiAgICAgICAgdGhpcy5lbmFibGVkID0gdHJ1ZTtcbiAgICAgICAgdGhpcy5wcm9ncmVzc0JhckRlbGF5ID0gNTAwO1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICB9XG4gICAgc3RhcnQoKSB7XG4gICAgICAgIGlmICghdGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnBhZ2VPYnNlcnZlci5zdGFydCgpO1xuICAgICAgICAgICAgdGhpcy5saW5rQ2xpY2tPYnNlcnZlci5zdGFydCgpO1xuICAgICAgICAgICAgdGhpcy5mb3JtU3VibWl0T2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICAgICAgICAgIHRoaXMuc2Nyb2xsT2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICAgICAgICAgIHRoaXMuc3RyZWFtT2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICAgICAgICAgIHRoaXMuZnJhbWVSZWRpcmVjdG9yLnN0YXJ0KCk7XG4gICAgICAgICAgICB0aGlzLmhpc3Rvcnkuc3RhcnQoKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IHRydWU7XG4gICAgICAgICAgICB0aGlzLmVuYWJsZWQgPSB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIGRpc2FibGUoKSB7XG4gICAgICAgIHRoaXMuZW5hYmxlZCA9IGZhbHNlO1xuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnBhZ2VPYnNlcnZlci5zdG9wKCk7XG4gICAgICAgICAgICB0aGlzLmxpbmtDbGlja09ic2VydmVyLnN0b3AoKTtcbiAgICAgICAgICAgIHRoaXMuZm9ybVN1Ym1pdE9ic2VydmVyLnN0b3AoKTtcbiAgICAgICAgICAgIHRoaXMuc2Nyb2xsT2JzZXJ2ZXIuc3RvcCgpO1xuICAgICAgICAgICAgdGhpcy5zdHJlYW1PYnNlcnZlci5zdG9wKCk7XG4gICAgICAgICAgICB0aGlzLmZyYW1lUmVkaXJlY3Rvci5zdG9wKCk7XG4gICAgICAgICAgICB0aGlzLmhpc3Rvcnkuc3RvcCgpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVnaXN0ZXJBZGFwdGVyKGFkYXB0ZXIpIHtcbiAgICAgICAgdGhpcy5hZGFwdGVyID0gYWRhcHRlcjtcbiAgICB9XG4gICAgdmlzaXQobG9jYXRpb24sIG9wdGlvbnMgPSB7fSkge1xuICAgICAgICB0aGlzLm5hdmlnYXRvci5wcm9wb3NlVmlzaXQoTG9jYXRpb24ud3JhcChsb2NhdGlvbiksIG9wdGlvbnMpO1xuICAgIH1cbiAgICBjb25uZWN0U3RyZWFtU291cmNlKHNvdXJjZSkge1xuICAgICAgICB0aGlzLnN0cmVhbU9ic2VydmVyLmNvbm5lY3RTdHJlYW1Tb3VyY2Uoc291cmNlKTtcbiAgICB9XG4gICAgZGlzY29ubmVjdFN0cmVhbVNvdXJjZShzb3VyY2UpIHtcbiAgICAgICAgdGhpcy5zdHJlYW1PYnNlcnZlci5kaXNjb25uZWN0U3RyZWFtU291cmNlKHNvdXJjZSk7XG4gICAgfVxuICAgIHJlbmRlclN0cmVhbU1lc3NhZ2UobWVzc2FnZSkge1xuICAgICAgICBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuYXBwZW5kQ2hpbGQoU3RyZWFtTWVzc2FnZS53cmFwKG1lc3NhZ2UpLmZyYWdtZW50KTtcbiAgICB9XG4gICAgY2xlYXJDYWNoZSgpIHtcbiAgICAgICAgdGhpcy52aWV3LmNsZWFyU25hcHNob3RDYWNoZSgpO1xuICAgIH1cbiAgICBzZXRQcm9ncmVzc0JhckRlbGF5KGRlbGF5KSB7XG4gICAgICAgIHRoaXMucHJvZ3Jlc3NCYXJEZWxheSA9IGRlbGF5O1xuICAgIH1cbiAgICBnZXQgbG9jYXRpb24oKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmhpc3RvcnkubG9jYXRpb247XG4gICAgfVxuICAgIGdldCByZXN0b3JhdGlvbklkZW50aWZpZXIoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmhpc3RvcnkucmVzdG9yYXRpb25JZGVudGlmaWVyO1xuICAgIH1cbiAgICBoaXN0b3J5UG9wcGVkVG9Mb2NhdGlvbldpdGhSZXN0b3JhdGlvbklkZW50aWZpZXIobG9jYXRpb24pIHtcbiAgICAgICAgaWYgKHRoaXMuZW5hYmxlZCkge1xuICAgICAgICAgICAgdGhpcy5uYXZpZ2F0b3IucHJvcG9zZVZpc2l0KGxvY2F0aW9uLCB7IGFjdGlvbjogXCJyZXN0b3JlXCIsIGhpc3RvcnlDaGFuZ2VkOiB0cnVlIH0pO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5hZGFwdGVyLnBhZ2VJbnZhbGlkYXRlZCgpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHNjcm9sbFBvc2l0aW9uQ2hhbmdlZChwb3NpdGlvbikge1xuICAgICAgICB0aGlzLmhpc3RvcnkudXBkYXRlUmVzdG9yYXRpb25EYXRhKHsgc2Nyb2xsUG9zaXRpb246IHBvc2l0aW9uIH0pO1xuICAgIH1cbiAgICB3aWxsRm9sbG93TGlua1RvTG9jYXRpb24obGluaywgbG9jYXRpb24pIHtcbiAgICAgICAgcmV0dXJuIHRoaXMubGlua0lzVmlzaXRhYmxlKGxpbmspXG4gICAgICAgICAgICAmJiB0aGlzLmxvY2F0aW9uSXNWaXNpdGFibGUobG9jYXRpb24pXG4gICAgICAgICAgICAmJiB0aGlzLmFwcGxpY2F0aW9uQWxsb3dzRm9sbG93aW5nTGlua1RvTG9jYXRpb24obGluaywgbG9jYXRpb24pO1xuICAgIH1cbiAgICBmb2xsb3dlZExpbmtUb0xvY2F0aW9uKGxpbmssIGxvY2F0aW9uKSB7XG4gICAgICAgIGNvbnN0IGFjdGlvbiA9IHRoaXMuZ2V0QWN0aW9uRm9yTGluayhsaW5rKTtcbiAgICAgICAgdGhpcy52aXNpdChsb2NhdGlvbiwgeyBhY3Rpb24gfSk7XG4gICAgfVxuICAgIGFsbG93c1Zpc2l0aW5nTG9jYXRpb24obG9jYXRpb24pIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYXBwbGljYXRpb25BbGxvd3NWaXNpdGluZ0xvY2F0aW9uKGxvY2F0aW9uKTtcbiAgICB9XG4gICAgdmlzaXRQcm9wb3NlZFRvTG9jYXRpb24obG9jYXRpb24sIG9wdGlvbnMpIHtcbiAgICAgICAgdGhpcy5hZGFwdGVyLnZpc2l0UHJvcG9zZWRUb0xvY2F0aW9uKGxvY2F0aW9uLCBvcHRpb25zKTtcbiAgICB9XG4gICAgdmlzaXRTdGFydGVkKHZpc2l0KSB7XG4gICAgICAgIHRoaXMubm90aWZ5QXBwbGljYXRpb25BZnRlclZpc2l0aW5nTG9jYXRpb24odmlzaXQubG9jYXRpb24pO1xuICAgIH1cbiAgICB2aXNpdENvbXBsZXRlZCh2aXNpdCkge1xuICAgICAgICB0aGlzLm5vdGlmeUFwcGxpY2F0aW9uQWZ0ZXJQYWdlTG9hZCh2aXNpdC5nZXRUaW1pbmdNZXRyaWNzKCkpO1xuICAgIH1cbiAgICB3aWxsU3VibWl0Rm9ybShmb3JtLCBzdWJtaXR0ZXIpIHtcbiAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgfVxuICAgIGZvcm1TdWJtaXR0ZWQoZm9ybSwgc3VibWl0dGVyKSB7XG4gICAgICAgIHRoaXMubmF2aWdhdG9yLnN1Ym1pdEZvcm0oZm9ybSwgc3VibWl0dGVyKTtcbiAgICB9XG4gICAgcGFnZUJlY2FtZUludGVyYWN0aXZlKCkge1xuICAgICAgICB0aGlzLnZpZXcubGFzdFJlbmRlcmVkTG9jYXRpb24gPSB0aGlzLmxvY2F0aW9uO1xuICAgICAgICB0aGlzLm5vdGlmeUFwcGxpY2F0aW9uQWZ0ZXJQYWdlTG9hZCgpO1xuICAgIH1cbiAgICBwYWdlTG9hZGVkKCkge1xuICAgIH1cbiAgICBwYWdlSW52YWxpZGF0ZWQoKSB7XG4gICAgICAgIHRoaXMuYWRhcHRlci5wYWdlSW52YWxpZGF0ZWQoKTtcbiAgICB9XG4gICAgcmVjZWl2ZWRNZXNzYWdlRnJvbVN0cmVhbShtZXNzYWdlKSB7XG4gICAgICAgIHRoaXMucmVuZGVyU3RyZWFtTWVzc2FnZShtZXNzYWdlKTtcbiAgICB9XG4gICAgdmlld1dpbGxSZW5kZXIobmV3Qm9keSkge1xuICAgICAgICB0aGlzLm5vdGlmeUFwcGxpY2F0aW9uQmVmb3JlUmVuZGVyKG5ld0JvZHkpO1xuICAgIH1cbiAgICB2aWV3UmVuZGVyZWQoKSB7XG4gICAgICAgIHRoaXMudmlldy5sYXN0UmVuZGVyZWRMb2NhdGlvbiA9IHRoaXMuaGlzdG9yeS5sb2NhdGlvbjtcbiAgICAgICAgdGhpcy5ub3RpZnlBcHBsaWNhdGlvbkFmdGVyUmVuZGVyKCk7XG4gICAgfVxuICAgIHZpZXdJbnZhbGlkYXRlZCgpIHtcbiAgICAgICAgdGhpcy5wYWdlT2JzZXJ2ZXIuaW52YWxpZGF0ZSgpO1xuICAgIH1cbiAgICB2aWV3V2lsbENhY2hlU25hcHNob3QoKSB7XG4gICAgICAgIHRoaXMubm90aWZ5QXBwbGljYXRpb25CZWZvcmVDYWNoaW5nU25hcHNob3QoKTtcbiAgICB9XG4gICAgYXBwbGljYXRpb25BbGxvd3NGb2xsb3dpbmdMaW5rVG9Mb2NhdGlvbihsaW5rLCBsb2NhdGlvbikge1xuICAgICAgICBjb25zdCBldmVudCA9IHRoaXMubm90aWZ5QXBwbGljYXRpb25BZnRlckNsaWNraW5nTGlua1RvTG9jYXRpb24obGluaywgbG9jYXRpb24pO1xuICAgICAgICByZXR1cm4gIWV2ZW50LmRlZmF1bHRQcmV2ZW50ZWQ7XG4gICAgfVxuICAgIGFwcGxpY2F0aW9uQWxsb3dzVmlzaXRpbmdMb2NhdGlvbihsb2NhdGlvbikge1xuICAgICAgICBjb25zdCBldmVudCA9IHRoaXMubm90aWZ5QXBwbGljYXRpb25CZWZvcmVWaXNpdGluZ0xvY2F0aW9uKGxvY2F0aW9uKTtcbiAgICAgICAgcmV0dXJuICFldmVudC5kZWZhdWx0UHJldmVudGVkO1xuICAgIH1cbiAgICBub3RpZnlBcHBsaWNhdGlvbkFmdGVyQ2xpY2tpbmdMaW5rVG9Mb2NhdGlvbihsaW5rLCBsb2NhdGlvbikge1xuICAgICAgICByZXR1cm4gZGlzcGF0Y2goXCJ0dXJibzpjbGlja1wiLCB7IHRhcmdldDogbGluaywgZGV0YWlsOiB7IHVybDogbG9jYXRpb24uYWJzb2x1dGVVUkwgfSwgY2FuY2VsYWJsZTogdHJ1ZSB9KTtcbiAgICB9XG4gICAgbm90aWZ5QXBwbGljYXRpb25CZWZvcmVWaXNpdGluZ0xvY2F0aW9uKGxvY2F0aW9uKSB7XG4gICAgICAgIHJldHVybiBkaXNwYXRjaChcInR1cmJvOmJlZm9yZS12aXNpdFwiLCB7IGRldGFpbDogeyB1cmw6IGxvY2F0aW9uLmFic29sdXRlVVJMIH0sIGNhbmNlbGFibGU6IHRydWUgfSk7XG4gICAgfVxuICAgIG5vdGlmeUFwcGxpY2F0aW9uQWZ0ZXJWaXNpdGluZ0xvY2F0aW9uKGxvY2F0aW9uKSB7XG4gICAgICAgIHJldHVybiBkaXNwYXRjaChcInR1cmJvOnZpc2l0XCIsIHsgZGV0YWlsOiB7IHVybDogbG9jYXRpb24uYWJzb2x1dGVVUkwgfSB9KTtcbiAgICB9XG4gICAgbm90aWZ5QXBwbGljYXRpb25CZWZvcmVDYWNoaW5nU25hcHNob3QoKSB7XG4gICAgICAgIHJldHVybiBkaXNwYXRjaChcInR1cmJvOmJlZm9yZS1jYWNoZVwiKTtcbiAgICB9XG4gICAgbm90aWZ5QXBwbGljYXRpb25CZWZvcmVSZW5kZXIobmV3Qm9keSkge1xuICAgICAgICByZXR1cm4gZGlzcGF0Y2goXCJ0dXJibzpiZWZvcmUtcmVuZGVyXCIsIHsgZGV0YWlsOiB7IG5ld0JvZHkgfSB9KTtcbiAgICB9XG4gICAgbm90aWZ5QXBwbGljYXRpb25BZnRlclJlbmRlcigpIHtcbiAgICAgICAgcmV0dXJuIGRpc3BhdGNoKFwidHVyYm86cmVuZGVyXCIpO1xuICAgIH1cbiAgICBub3RpZnlBcHBsaWNhdGlvbkFmdGVyUGFnZUxvYWQodGltaW5nID0ge30pIHtcbiAgICAgICAgcmV0dXJuIGRpc3BhdGNoKFwidHVyYm86bG9hZFwiLCB7IGRldGFpbDogeyB1cmw6IHRoaXMubG9jYXRpb24uYWJzb2x1dGVVUkwsIHRpbWluZyB9IH0pO1xuICAgIH1cbiAgICBnZXRBY3Rpb25Gb3JMaW5rKGxpbmspIHtcbiAgICAgICAgY29uc3QgYWN0aW9uID0gbGluay5nZXRBdHRyaWJ1dGUoXCJkYXRhLXR1cmJvLWFjdGlvblwiKTtcbiAgICAgICAgcmV0dXJuIGlzQWN0aW9uKGFjdGlvbikgPyBhY3Rpb24gOiBcImFkdmFuY2VcIjtcbiAgICB9XG4gICAgbGlua0lzVmlzaXRhYmxlKGxpbmspIHtcbiAgICAgICAgY29uc3QgY29udGFpbmVyID0gbGluay5jbG9zZXN0KFwiW2RhdGEtdHVyYm9dXCIpO1xuICAgICAgICBpZiAoY29udGFpbmVyKSB7XG4gICAgICAgICAgICByZXR1cm4gY29udGFpbmVyLmdldEF0dHJpYnV0ZShcImRhdGEtdHVyYm9cIikgIT0gXCJmYWxzZVwiO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgbG9jYXRpb25Jc1Zpc2l0YWJsZShsb2NhdGlvbikge1xuICAgICAgICByZXR1cm4gbG9jYXRpb24uaXNQcmVmaXhlZEJ5KHRoaXMudmlldy5nZXRSb290TG9jYXRpb24oKSkgJiYgbG9jYXRpb24uaXNIVE1MKCk7XG4gICAgfVxufVxuXG5jb25zdCBzZXNzaW9uID0gbmV3IFNlc3Npb247XG5jb25zdCB7IG5hdmlnYXRvciB9ID0gc2Vzc2lvbjtcbmZ1bmN0aW9uIHN0YXJ0KCkge1xuICAgIHNlc3Npb24uc3RhcnQoKTtcbn1cbmZ1bmN0aW9uIHJlZ2lzdGVyQWRhcHRlcihhZGFwdGVyKSB7XG4gICAgc2Vzc2lvbi5yZWdpc3RlckFkYXB0ZXIoYWRhcHRlcik7XG59XG5mdW5jdGlvbiB2aXNpdChsb2NhdGlvbiwgb3B0aW9ucykge1xuICAgIHNlc3Npb24udmlzaXQobG9jYXRpb24sIG9wdGlvbnMpO1xufVxuZnVuY3Rpb24gY29ubmVjdFN0cmVhbVNvdXJjZShzb3VyY2UpIHtcbiAgICBzZXNzaW9uLmNvbm5lY3RTdHJlYW1Tb3VyY2Uoc291cmNlKTtcbn1cbmZ1bmN0aW9uIGRpc2Nvbm5lY3RTdHJlYW1Tb3VyY2Uoc291cmNlKSB7XG4gICAgc2Vzc2lvbi5kaXNjb25uZWN0U3RyZWFtU291cmNlKHNvdXJjZSk7XG59XG5mdW5jdGlvbiByZW5kZXJTdHJlYW1NZXNzYWdlKG1lc3NhZ2UpIHtcbiAgICBzZXNzaW9uLnJlbmRlclN0cmVhbU1lc3NhZ2UobWVzc2FnZSk7XG59XG5mdW5jdGlvbiBjbGVhckNhY2hlKCkge1xuICAgIHNlc3Npb24uY2xlYXJDYWNoZSgpO1xufVxuZnVuY3Rpb24gc2V0UHJvZ3Jlc3NCYXJEZWxheShkZWxheSkge1xuICAgIHNlc3Npb24uc2V0UHJvZ3Jlc3NCYXJEZWxheShkZWxheSk7XG59XG5cbnN0YXJ0KCk7XG5cbmV4cG9ydCB7IGNsZWFyQ2FjaGUsIGNvbm5lY3RTdHJlYW1Tb3VyY2UsIGRpc2Nvbm5lY3RTdHJlYW1Tb3VyY2UsIG5hdmlnYXRvciwgcmVnaXN0ZXJBZGFwdGVyLCByZW5kZXJTdHJlYW1NZXNzYWdlLCBzZXRQcm9ncmVzc0JhckRlbGF5LCBzdGFydCwgdmlzaXQgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXR1cmJvLmVzMjAxNy1lc20uanMubWFwXG4iLCJpbXBvcnQgeyBwYXJzZUFjdGlvbkRlc2NyaXB0b3JTdHJpbmcsIHN0cmluZ2lmeUV2ZW50VGFyZ2V0IH0gZnJvbSBcIi4vYWN0aW9uX2Rlc2NyaXB0b3JcIjtcbnZhciBBY3Rpb24gPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gQWN0aW9uKGVsZW1lbnQsIGluZGV4LCBkZXNjcmlwdG9yKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuaW5kZXggPSBpbmRleDtcbiAgICAgICAgdGhpcy5ldmVudFRhcmdldCA9IGRlc2NyaXB0b3IuZXZlbnRUYXJnZXQgfHwgZWxlbWVudDtcbiAgICAgICAgdGhpcy5ldmVudE5hbWUgPSBkZXNjcmlwdG9yLmV2ZW50TmFtZSB8fCBnZXREZWZhdWx0RXZlbnROYW1lRm9yRWxlbWVudChlbGVtZW50KSB8fCBlcnJvcihcIm1pc3NpbmcgZXZlbnQgbmFtZVwiKTtcbiAgICAgICAgdGhpcy5ldmVudE9wdGlvbnMgPSBkZXNjcmlwdG9yLmV2ZW50T3B0aW9ucyB8fCB7fTtcbiAgICAgICAgdGhpcy5pZGVudGlmaWVyID0gZGVzY3JpcHRvci5pZGVudGlmaWVyIHx8IGVycm9yKFwibWlzc2luZyBpZGVudGlmaWVyXCIpO1xuICAgICAgICB0aGlzLm1ldGhvZE5hbWUgPSBkZXNjcmlwdG9yLm1ldGhvZE5hbWUgfHwgZXJyb3IoXCJtaXNzaW5nIG1ldGhvZCBuYW1lXCIpO1xuICAgIH1cbiAgICBBY3Rpb24uZm9yVG9rZW4gPSBmdW5jdGlvbiAodG9rZW4pIHtcbiAgICAgICAgcmV0dXJuIG5ldyB0aGlzKHRva2VuLmVsZW1lbnQsIHRva2VuLmluZGV4LCBwYXJzZUFjdGlvbkRlc2NyaXB0b3JTdHJpbmcodG9rZW4uY29udGVudCkpO1xuICAgIH07XG4gICAgQWN0aW9uLnByb3RvdHlwZS50b1N0cmluZyA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdmFyIGV2ZW50TmFtZVN1ZmZpeCA9IHRoaXMuZXZlbnRUYXJnZXROYW1lID8gXCJAXCIgKyB0aGlzLmV2ZW50VGFyZ2V0TmFtZSA6IFwiXCI7XG4gICAgICAgIHJldHVybiBcIlwiICsgdGhpcy5ldmVudE5hbWUgKyBldmVudE5hbWVTdWZmaXggKyBcIi0+XCIgKyB0aGlzLmlkZW50aWZpZXIgKyBcIiNcIiArIHRoaXMubWV0aG9kTmFtZTtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShBY3Rpb24ucHJvdG90eXBlLCBcImV2ZW50VGFyZ2V0TmFtZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHN0cmluZ2lmeUV2ZW50VGFyZ2V0KHRoaXMuZXZlbnRUYXJnZXQpO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgcmV0dXJuIEFjdGlvbjtcbn0oKSk7XG5leHBvcnQgeyBBY3Rpb24gfTtcbnZhciBkZWZhdWx0RXZlbnROYW1lcyA9IHtcbiAgICBcImFcIjogZnVuY3Rpb24gKGUpIHsgcmV0dXJuIFwiY2xpY2tcIjsgfSxcbiAgICBcImJ1dHRvblwiOiBmdW5jdGlvbiAoZSkgeyByZXR1cm4gXCJjbGlja1wiOyB9LFxuICAgIFwiZm9ybVwiOiBmdW5jdGlvbiAoZSkgeyByZXR1cm4gXCJzdWJtaXRcIjsgfSxcbiAgICBcImlucHV0XCI6IGZ1bmN0aW9uIChlKSB7IHJldHVybiBlLmdldEF0dHJpYnV0ZShcInR5cGVcIikgPT0gXCJzdWJtaXRcIiA/IFwiY2xpY2tcIiA6IFwiaW5wdXRcIjsgfSxcbiAgICBcInNlbGVjdFwiOiBmdW5jdGlvbiAoZSkgeyByZXR1cm4gXCJjaGFuZ2VcIjsgfSxcbiAgICBcInRleHRhcmVhXCI6IGZ1bmN0aW9uIChlKSB7IHJldHVybiBcImlucHV0XCI7IH1cbn07XG5leHBvcnQgZnVuY3Rpb24gZ2V0RGVmYXVsdEV2ZW50TmFtZUZvckVsZW1lbnQoZWxlbWVudCkge1xuICAgIHZhciB0YWdOYW1lID0gZWxlbWVudC50YWdOYW1lLnRvTG93ZXJDYXNlKCk7XG4gICAgaWYgKHRhZ05hbWUgaW4gZGVmYXVsdEV2ZW50TmFtZXMpIHtcbiAgICAgICAgcmV0dXJuIGRlZmF1bHRFdmVudE5hbWVzW3RhZ05hbWVdKGVsZW1lbnQpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGVycm9yKG1lc3NhZ2UpIHtcbiAgICB0aHJvdyBuZXcgRXJyb3IobWVzc2FnZSk7XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1hY3Rpb24uanMubWFwIiwiLy8gY2FwdHVyZSBub3MuOiAgICAgICAgICAgIDEyICAgMjMgNCAgICAgICAgICAgICAgIDQzICAgMSA1ICAgNTYgNyAgICAgIDc2OCA5ICA5OFxudmFyIGRlc2NyaXB0b3JQYXR0ZXJuID0gL14oKC4rPykoQCh3aW5kb3d8ZG9jdW1lbnQpKT8tPik/KC4rPykoIyhbXjpdKz8pKSg6KC4rKSk/JC87XG5leHBvcnQgZnVuY3Rpb24gcGFyc2VBY3Rpb25EZXNjcmlwdG9yU3RyaW5nKGRlc2NyaXB0b3JTdHJpbmcpIHtcbiAgICB2YXIgc291cmNlID0gZGVzY3JpcHRvclN0cmluZy50cmltKCk7XG4gICAgdmFyIG1hdGNoZXMgPSBzb3VyY2UubWF0Y2goZGVzY3JpcHRvclBhdHRlcm4pIHx8IFtdO1xuICAgIHJldHVybiB7XG4gICAgICAgIGV2ZW50VGFyZ2V0OiBwYXJzZUV2ZW50VGFyZ2V0KG1hdGNoZXNbNF0pLFxuICAgICAgICBldmVudE5hbWU6IG1hdGNoZXNbMl0sXG4gICAgICAgIGV2ZW50T3B0aW9uczogbWF0Y2hlc1s5XSA/IHBhcnNlRXZlbnRPcHRpb25zKG1hdGNoZXNbOV0pIDoge30sXG4gICAgICAgIGlkZW50aWZpZXI6IG1hdGNoZXNbNV0sXG4gICAgICAgIG1ldGhvZE5hbWU6IG1hdGNoZXNbN11cbiAgICB9O1xufVxuZnVuY3Rpb24gcGFyc2VFdmVudFRhcmdldChldmVudFRhcmdldE5hbWUpIHtcbiAgICBpZiAoZXZlbnRUYXJnZXROYW1lID09IFwid2luZG93XCIpIHtcbiAgICAgICAgcmV0dXJuIHdpbmRvdztcbiAgICB9XG4gICAgZWxzZSBpZiAoZXZlbnRUYXJnZXROYW1lID09IFwiZG9jdW1lbnRcIikge1xuICAgICAgICByZXR1cm4gZG9jdW1lbnQ7XG4gICAgfVxufVxuZnVuY3Rpb24gcGFyc2VFdmVudE9wdGlvbnMoZXZlbnRPcHRpb25zKSB7XG4gICAgcmV0dXJuIGV2ZW50T3B0aW9ucy5zcGxpdChcIjpcIikucmVkdWNlKGZ1bmN0aW9uIChvcHRpb25zLCB0b2tlbikge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKG9wdGlvbnMsIChfYSA9IHt9LCBfYVt0b2tlbi5yZXBsYWNlKC9eIS8sIFwiXCIpXSA9ICEvXiEvLnRlc3QodG9rZW4pLCBfYSkpO1xuICAgIH0sIHt9KTtcbn1cbmV4cG9ydCBmdW5jdGlvbiBzdHJpbmdpZnlFdmVudFRhcmdldChldmVudFRhcmdldCkge1xuICAgIGlmIChldmVudFRhcmdldCA9PSB3aW5kb3cpIHtcbiAgICAgICAgcmV0dXJuIFwid2luZG93XCI7XG4gICAgfVxuICAgIGVsc2UgaWYgKGV2ZW50VGFyZ2V0ID09IGRvY3VtZW50KSB7XG4gICAgICAgIHJldHVybiBcImRvY3VtZW50XCI7XG4gICAgfVxufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9YWN0aW9uX2Rlc2NyaXB0b3IuanMubWFwIiwidmFyIF9fYXdhaXRlciA9ICh0aGlzICYmIHRoaXMuX19hd2FpdGVyKSB8fCBmdW5jdGlvbiAodGhpc0FyZywgX2FyZ3VtZW50cywgUCwgZ2VuZXJhdG9yKSB7XG4gICAgZnVuY3Rpb24gYWRvcHQodmFsdWUpIHsgcmV0dXJuIHZhbHVlIGluc3RhbmNlb2YgUCA/IHZhbHVlIDogbmV3IFAoZnVuY3Rpb24gKHJlc29sdmUpIHsgcmVzb2x2ZSh2YWx1ZSk7IH0pOyB9XG4gICAgcmV0dXJuIG5ldyAoUCB8fCAoUCA9IFByb21pc2UpKShmdW5jdGlvbiAocmVzb2x2ZSwgcmVqZWN0KSB7XG4gICAgICAgIGZ1bmN0aW9uIGZ1bGZpbGxlZCh2YWx1ZSkgeyB0cnkgeyBzdGVwKGdlbmVyYXRvci5uZXh0KHZhbHVlKSk7IH0gY2F0Y2ggKGUpIHsgcmVqZWN0KGUpOyB9IH1cbiAgICAgICAgZnVuY3Rpb24gcmVqZWN0ZWQodmFsdWUpIHsgdHJ5IHsgc3RlcChnZW5lcmF0b3JbXCJ0aHJvd1wiXSh2YWx1ZSkpOyB9IGNhdGNoIChlKSB7IHJlamVjdChlKTsgfSB9XG4gICAgICAgIGZ1bmN0aW9uIHN0ZXAocmVzdWx0KSB7IHJlc3VsdC5kb25lID8gcmVzb2x2ZShyZXN1bHQudmFsdWUpIDogYWRvcHQocmVzdWx0LnZhbHVlKS50aGVuKGZ1bGZpbGxlZCwgcmVqZWN0ZWQpOyB9XG4gICAgICAgIHN0ZXAoKGdlbmVyYXRvciA9IGdlbmVyYXRvci5hcHBseSh0aGlzQXJnLCBfYXJndW1lbnRzIHx8IFtdKSkubmV4dCgpKTtcbiAgICB9KTtcbn07XG52YXIgX19nZW5lcmF0b3IgPSAodGhpcyAmJiB0aGlzLl9fZ2VuZXJhdG9yKSB8fCBmdW5jdGlvbiAodGhpc0FyZywgYm9keSkge1xuICAgIHZhciBfID0geyBsYWJlbDogMCwgc2VudDogZnVuY3Rpb24oKSB7IGlmICh0WzBdICYgMSkgdGhyb3cgdFsxXTsgcmV0dXJuIHRbMV07IH0sIHRyeXM6IFtdLCBvcHM6IFtdIH0sIGYsIHksIHQsIGc7XG4gICAgcmV0dXJuIGcgPSB7IG5leHQ6IHZlcmIoMCksIFwidGhyb3dcIjogdmVyYigxKSwgXCJyZXR1cm5cIjogdmVyYigyKSB9LCB0eXBlb2YgU3ltYm9sID09PSBcImZ1bmN0aW9uXCIgJiYgKGdbU3ltYm9sLml0ZXJhdG9yXSA9IGZ1bmN0aW9uKCkgeyByZXR1cm4gdGhpczsgfSksIGc7XG4gICAgZnVuY3Rpb24gdmVyYihuKSB7IHJldHVybiBmdW5jdGlvbiAodikgeyByZXR1cm4gc3RlcChbbiwgdl0pOyB9OyB9XG4gICAgZnVuY3Rpb24gc3RlcChvcCkge1xuICAgICAgICBpZiAoZikgdGhyb3cgbmV3IFR5cGVFcnJvcihcIkdlbmVyYXRvciBpcyBhbHJlYWR5IGV4ZWN1dGluZy5cIik7XG4gICAgICAgIHdoaWxlIChfKSB0cnkge1xuICAgICAgICAgICAgaWYgKGYgPSAxLCB5ICYmICh0ID0gb3BbMF0gJiAyID8geVtcInJldHVyblwiXSA6IG9wWzBdID8geVtcInRocm93XCJdIHx8ICgodCA9IHlbXCJyZXR1cm5cIl0pICYmIHQuY2FsbCh5KSwgMCkgOiB5Lm5leHQpICYmICEodCA9IHQuY2FsbCh5LCBvcFsxXSkpLmRvbmUpIHJldHVybiB0O1xuICAgICAgICAgICAgaWYgKHkgPSAwLCB0KSBvcCA9IFtvcFswXSAmIDIsIHQudmFsdWVdO1xuICAgICAgICAgICAgc3dpdGNoIChvcFswXSkge1xuICAgICAgICAgICAgICAgIGNhc2UgMDogY2FzZSAxOiB0ID0gb3A7IGJyZWFrO1xuICAgICAgICAgICAgICAgIGNhc2UgNDogXy5sYWJlbCsrOyByZXR1cm4geyB2YWx1ZTogb3BbMV0sIGRvbmU6IGZhbHNlIH07XG4gICAgICAgICAgICAgICAgY2FzZSA1OiBfLmxhYmVsKys7IHkgPSBvcFsxXTsgb3AgPSBbMF07IGNvbnRpbnVlO1xuICAgICAgICAgICAgICAgIGNhc2UgNzogb3AgPSBfLm9wcy5wb3AoKTsgXy50cnlzLnBvcCgpOyBjb250aW51ZTtcbiAgICAgICAgICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgICAgICAgICBpZiAoISh0ID0gXy50cnlzLCB0ID0gdC5sZW5ndGggPiAwICYmIHRbdC5sZW5ndGggLSAxXSkgJiYgKG9wWzBdID09PSA2IHx8IG9wWzBdID09PSAyKSkgeyBfID0gMDsgY29udGludWU7IH1cbiAgICAgICAgICAgICAgICAgICAgaWYgKG9wWzBdID09PSAzICYmICghdCB8fCAob3BbMV0gPiB0WzBdICYmIG9wWzFdIDwgdFszXSkpKSB7IF8ubGFiZWwgPSBvcFsxXTsgYnJlYWs7IH1cbiAgICAgICAgICAgICAgICAgICAgaWYgKG9wWzBdID09PSA2ICYmIF8ubGFiZWwgPCB0WzFdKSB7IF8ubGFiZWwgPSB0WzFdOyB0ID0gb3A7IGJyZWFrOyB9XG4gICAgICAgICAgICAgICAgICAgIGlmICh0ICYmIF8ubGFiZWwgPCB0WzJdKSB7IF8ubGFiZWwgPSB0WzJdOyBfLm9wcy5wdXNoKG9wKTsgYnJlYWs7IH1cbiAgICAgICAgICAgICAgICAgICAgaWYgKHRbMl0pIF8ub3BzLnBvcCgpO1xuICAgICAgICAgICAgICAgICAgICBfLnRyeXMucG9wKCk7IGNvbnRpbnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgb3AgPSBib2R5LmNhbGwodGhpc0FyZywgXyk7XG4gICAgICAgIH0gY2F0Y2ggKGUpIHsgb3AgPSBbNiwgZV07IHkgPSAwOyB9IGZpbmFsbHkgeyBmID0gdCA9IDA7IH1cbiAgICAgICAgaWYgKG9wWzBdICYgNSkgdGhyb3cgb3BbMV07IHJldHVybiB7IHZhbHVlOiBvcFswXSA/IG9wWzFdIDogdm9pZCAwLCBkb25lOiB0cnVlIH07XG4gICAgfVxufTtcbnZhciBfX3NwcmVhZEFycmF5cyA9ICh0aGlzICYmIHRoaXMuX19zcHJlYWRBcnJheXMpIHx8IGZ1bmN0aW9uICgpIHtcbiAgICBmb3IgKHZhciBzID0gMCwgaSA9IDAsIGlsID0gYXJndW1lbnRzLmxlbmd0aDsgaSA8IGlsOyBpKyspIHMgKz0gYXJndW1lbnRzW2ldLmxlbmd0aDtcbiAgICBmb3IgKHZhciByID0gQXJyYXkocyksIGsgPSAwLCBpID0gMDsgaSA8IGlsOyBpKyspXG4gICAgICAgIGZvciAodmFyIGEgPSBhcmd1bWVudHNbaV0sIGogPSAwLCBqbCA9IGEubGVuZ3RoOyBqIDwgamw7IGorKywgaysrKVxuICAgICAgICAgICAgcltrXSA9IGFbal07XG4gICAgcmV0dXJuIHI7XG59O1xuaW1wb3J0IHsgRGlzcGF0Y2hlciB9IGZyb20gXCIuL2Rpc3BhdGNoZXJcIjtcbmltcG9ydCB7IFJvdXRlciB9IGZyb20gXCIuL3JvdXRlclwiO1xuaW1wb3J0IHsgZGVmYXVsdFNjaGVtYSB9IGZyb20gXCIuL3NjaGVtYVwiO1xudmFyIEFwcGxpY2F0aW9uID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIEFwcGxpY2F0aW9uKGVsZW1lbnQsIHNjaGVtYSkge1xuICAgICAgICBpZiAoZWxlbWVudCA9PT0gdm9pZCAwKSB7IGVsZW1lbnQgPSBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQ7IH1cbiAgICAgICAgaWYgKHNjaGVtYSA9PT0gdm9pZCAwKSB7IHNjaGVtYSA9IGRlZmF1bHRTY2hlbWE7IH1cbiAgICAgICAgdGhpcy5sb2dnZXIgPSBjb25zb2xlO1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBlbGVtZW50O1xuICAgICAgICB0aGlzLnNjaGVtYSA9IHNjaGVtYTtcbiAgICAgICAgdGhpcy5kaXNwYXRjaGVyID0gbmV3IERpc3BhdGNoZXIodGhpcyk7XG4gICAgICAgIHRoaXMucm91dGVyID0gbmV3IFJvdXRlcih0aGlzKTtcbiAgICB9XG4gICAgQXBwbGljYXRpb24uc3RhcnQgPSBmdW5jdGlvbiAoZWxlbWVudCwgc2NoZW1hKSB7XG4gICAgICAgIHZhciBhcHBsaWNhdGlvbiA9IG5ldyBBcHBsaWNhdGlvbihlbGVtZW50LCBzY2hlbWEpO1xuICAgICAgICBhcHBsaWNhdGlvbi5zdGFydCgpO1xuICAgICAgICByZXR1cm4gYXBwbGljYXRpb247XG4gICAgfTtcbiAgICBBcHBsaWNhdGlvbi5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHJldHVybiBfX2F3YWl0ZXIodGhpcywgdm9pZCAwLCB2b2lkIDAsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBfX2dlbmVyYXRvcih0aGlzLCBmdW5jdGlvbiAoX2EpIHtcbiAgICAgICAgICAgICAgICBzd2l0Y2ggKF9hLmxhYmVsKSB7XG4gICAgICAgICAgICAgICAgICAgIGNhc2UgMDogcmV0dXJuIFs0IC8qeWllbGQqLywgZG9tUmVhZHkoKV07XG4gICAgICAgICAgICAgICAgICAgIGNhc2UgMTpcbiAgICAgICAgICAgICAgICAgICAgICAgIF9hLnNlbnQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMuZGlzcGF0Y2hlci5zdGFydCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5yb3V0ZXIuc3RhcnQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBbMiAvKnJldHVybiovXTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSk7XG4gICAgfTtcbiAgICBBcHBsaWNhdGlvbi5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5kaXNwYXRjaGVyLnN0b3AoKTtcbiAgICAgICAgdGhpcy5yb3V0ZXIuc3RvcCgpO1xuICAgIH07XG4gICAgQXBwbGljYXRpb24ucHJvdG90eXBlLnJlZ2lzdGVyID0gZnVuY3Rpb24gKGlkZW50aWZpZXIsIGNvbnRyb2xsZXJDb25zdHJ1Y3Rvcikge1xuICAgICAgICB0aGlzLmxvYWQoeyBpZGVudGlmaWVyOiBpZGVudGlmaWVyLCBjb250cm9sbGVyQ29uc3RydWN0b3I6IGNvbnRyb2xsZXJDb25zdHJ1Y3RvciB9KTtcbiAgICB9O1xuICAgIEFwcGxpY2F0aW9uLnByb3RvdHlwZS5sb2FkID0gZnVuY3Rpb24gKGhlYWQpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdmFyIHJlc3QgPSBbXTtcbiAgICAgICAgZm9yICh2YXIgX2kgPSAxOyBfaSA8IGFyZ3VtZW50cy5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgIHJlc3RbX2kgLSAxXSA9IGFyZ3VtZW50c1tfaV07XG4gICAgICAgIH1cbiAgICAgICAgdmFyIGRlZmluaXRpb25zID0gQXJyYXkuaXNBcnJheShoZWFkKSA/IGhlYWQgOiBfX3NwcmVhZEFycmF5cyhbaGVhZF0sIHJlc3QpO1xuICAgICAgICBkZWZpbml0aW9ucy5mb3JFYWNoKGZ1bmN0aW9uIChkZWZpbml0aW9uKSB7IHJldHVybiBfdGhpcy5yb3V0ZXIubG9hZERlZmluaXRpb24oZGVmaW5pdGlvbik7IH0pO1xuICAgIH07XG4gICAgQXBwbGljYXRpb24ucHJvdG90eXBlLnVubG9hZCA9IGZ1bmN0aW9uIChoZWFkKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHZhciByZXN0ID0gW107XG4gICAgICAgIGZvciAodmFyIF9pID0gMTsgX2kgPCBhcmd1bWVudHMubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICByZXN0W19pIC0gMV0gPSBhcmd1bWVudHNbX2ldO1xuICAgICAgICB9XG4gICAgICAgIHZhciBpZGVudGlmaWVycyA9IEFycmF5LmlzQXJyYXkoaGVhZCkgPyBoZWFkIDogX19zcHJlYWRBcnJheXMoW2hlYWRdLCByZXN0KTtcbiAgICAgICAgaWRlbnRpZmllcnMuZm9yRWFjaChmdW5jdGlvbiAoaWRlbnRpZmllcikgeyByZXR1cm4gX3RoaXMucm91dGVyLnVubG9hZElkZW50aWZpZXIoaWRlbnRpZmllcik7IH0pO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEFwcGxpY2F0aW9uLnByb3RvdHlwZSwgXCJjb250cm9sbGVyc1wiLCB7XG4gICAgICAgIC8vIENvbnRyb2xsZXJzXG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMucm91dGVyLmNvbnRleHRzLm1hcChmdW5jdGlvbiAoY29udGV4dCkgeyByZXR1cm4gY29udGV4dC5jb250cm9sbGVyOyB9KTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIEFwcGxpY2F0aW9uLnByb3RvdHlwZS5nZXRDb250cm9sbGVyRm9yRWxlbWVudEFuZElkZW50aWZpZXIgPSBmdW5jdGlvbiAoZWxlbWVudCwgaWRlbnRpZmllcikge1xuICAgICAgICB2YXIgY29udGV4dCA9IHRoaXMucm91dGVyLmdldENvbnRleHRGb3JFbGVtZW50QW5kSWRlbnRpZmllcihlbGVtZW50LCBpZGVudGlmaWVyKTtcbiAgICAgICAgcmV0dXJuIGNvbnRleHQgPyBjb250ZXh0LmNvbnRyb2xsZXIgOiBudWxsO1xuICAgIH07XG4gICAgLy8gRXJyb3IgaGFuZGxpbmdcbiAgICBBcHBsaWNhdGlvbi5wcm90b3R5cGUuaGFuZGxlRXJyb3IgPSBmdW5jdGlvbiAoZXJyb3IsIG1lc3NhZ2UsIGRldGFpbCkge1xuICAgICAgICB0aGlzLmxvZ2dlci5lcnJvcihcIiVzXFxuXFxuJW9cXG5cXG4lb1wiLCBtZXNzYWdlLCBlcnJvciwgZGV0YWlsKTtcbiAgICB9O1xuICAgIHJldHVybiBBcHBsaWNhdGlvbjtcbn0oKSk7XG5leHBvcnQgeyBBcHBsaWNhdGlvbiB9O1xuZnVuY3Rpb24gZG9tUmVhZHkoKSB7XG4gICAgcmV0dXJuIG5ldyBQcm9taXNlKGZ1bmN0aW9uIChyZXNvbHZlKSB7XG4gICAgICAgIGlmIChkb2N1bWVudC5yZWFkeVN0YXRlID09IFwibG9hZGluZ1wiKSB7XG4gICAgICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKFwiRE9NQ29udGVudExvYWRlZFwiLCByZXNvbHZlKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJlc29sdmUoKTtcbiAgICAgICAgfVxuICAgIH0pO1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9YXBwbGljYXRpb24uanMubWFwIiwidmFyIEJpbmRpbmcgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gQmluZGluZyhjb250ZXh0LCBhY3Rpb24pIHtcbiAgICAgICAgdGhpcy5jb250ZXh0ID0gY29udGV4dDtcbiAgICAgICAgdGhpcy5hY3Rpb24gPSBhY3Rpb247XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJpbmRleFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYWN0aW9uLmluZGV4O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcImV2ZW50VGFyZ2V0XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb24uZXZlbnRUYXJnZXQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwiZXZlbnRPcHRpb25zXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb24uZXZlbnRPcHRpb25zO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcImlkZW50aWZpZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuaWRlbnRpZmllcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIEJpbmRpbmcucHJvdG90eXBlLmhhbmRsZUV2ZW50ID0gZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgIGlmICh0aGlzLndpbGxCZUludm9rZWRCeUV2ZW50KGV2ZW50KSkge1xuICAgICAgICAgICAgdGhpcy5pbnZva2VXaXRoRXZlbnQoZXZlbnQpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwiZXZlbnROYW1lXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb24uZXZlbnROYW1lO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcIm1ldGhvZFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgdmFyIG1ldGhvZCA9IHRoaXMuY29udHJvbGxlclt0aGlzLm1ldGhvZE5hbWVdO1xuICAgICAgICAgICAgaWYgKHR5cGVvZiBtZXRob2QgPT0gXCJmdW5jdGlvblwiKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIG1ldGhvZDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihcIkFjdGlvbiBcXFwiXCIgKyB0aGlzLmFjdGlvbiArIFwiXFxcIiByZWZlcmVuY2VzIHVuZGVmaW5lZCBtZXRob2QgXFxcIlwiICsgdGhpcy5tZXRob2ROYW1lICsgXCJcXFwiXCIpO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgQmluZGluZy5wcm90b3R5cGUuaW52b2tlV2l0aEV2ZW50ID0gZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICB0aGlzLm1ldGhvZC5jYWxsKHRoaXMuY29udHJvbGxlciwgZXZlbnQpO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgdmFyIF9hID0gdGhpcywgaWRlbnRpZmllciA9IF9hLmlkZW50aWZpZXIsIGNvbnRyb2xsZXIgPSBfYS5jb250cm9sbGVyLCBlbGVtZW50ID0gX2EuZWxlbWVudCwgaW5kZXggPSBfYS5pbmRleDtcbiAgICAgICAgICAgIHZhciBkZXRhaWwgPSB7IGlkZW50aWZpZXI6IGlkZW50aWZpZXIsIGNvbnRyb2xsZXI6IGNvbnRyb2xsZXIsIGVsZW1lbnQ6IGVsZW1lbnQsIGluZGV4OiBpbmRleCwgZXZlbnQ6IGV2ZW50IH07XG4gICAgICAgICAgICB0aGlzLmNvbnRleHQuaGFuZGxlRXJyb3IoZXJyb3IsIFwiaW52b2tpbmcgYWN0aW9uIFxcXCJcIiArIHRoaXMuYWN0aW9uICsgXCJcXFwiXCIsIGRldGFpbCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEJpbmRpbmcucHJvdG90eXBlLndpbGxCZUludm9rZWRCeUV2ZW50ID0gZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgIHZhciBldmVudFRhcmdldCA9IGV2ZW50LnRhcmdldDtcbiAgICAgICAgaWYgKHRoaXMuZWxlbWVudCA9PT0gZXZlbnRUYXJnZXQpIHtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKGV2ZW50VGFyZ2V0IGluc3RhbmNlb2YgRWxlbWVudCAmJiB0aGlzLmVsZW1lbnQuY29udGFpbnMoZXZlbnRUYXJnZXQpKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5jb250YWluc0VsZW1lbnQoZXZlbnRUYXJnZXQpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuY29udGFpbnNFbGVtZW50KHRoaXMuYWN0aW9uLmVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwiY29udHJvbGxlclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5jb250cm9sbGVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcIm1ldGhvZE5hbWVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFjdGlvbi5tZXRob2ROYW1lO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwic2NvcGVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuc2NvcGU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICByZXR1cm4gQmluZGluZztcbn0oKSk7XG5leHBvcnQgeyBCaW5kaW5nIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1iaW5kaW5nLmpzLm1hcCIsImltcG9ydCB7IEFjdGlvbiB9IGZyb20gXCIuL2FjdGlvblwiO1xuaW1wb3J0IHsgQmluZGluZyB9IGZyb20gXCIuL2JpbmRpbmdcIjtcbmltcG9ydCB7IFZhbHVlTGlzdE9ic2VydmVyIH0gZnJvbSBcIkBzdGltdWx1cy9tdXRhdGlvbi1vYnNlcnZlcnNcIjtcbnZhciBCaW5kaW5nT2JzZXJ2ZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gQmluZGluZ09ic2VydmVyKGNvbnRleHQsIGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuY29udGV4dCA9IGNvbnRleHQ7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5iaW5kaW5nc0J5QWN0aW9uID0gbmV3IE1hcDtcbiAgICB9XG4gICAgQmluZGluZ09ic2VydmVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnZhbHVlTGlzdE9ic2VydmVyKSB7XG4gICAgICAgICAgICB0aGlzLnZhbHVlTGlzdE9ic2VydmVyID0gbmV3IFZhbHVlTGlzdE9ic2VydmVyKHRoaXMuZWxlbWVudCwgdGhpcy5hY3Rpb25BdHRyaWJ1dGUsIHRoaXMpO1xuICAgICAgICAgICAgdGhpcy52YWx1ZUxpc3RPYnNlcnZlci5zdGFydCgpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICh0aGlzLnZhbHVlTGlzdE9ic2VydmVyKSB7XG4gICAgICAgICAgICB0aGlzLnZhbHVlTGlzdE9ic2VydmVyLnN0b3AoKTtcbiAgICAgICAgICAgIGRlbGV0ZSB0aGlzLnZhbHVlTGlzdE9ic2VydmVyO1xuICAgICAgICAgICAgdGhpcy5kaXNjb25uZWN0QWxsQWN0aW9ucygpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZ09ic2VydmVyLnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZ09ic2VydmVyLnByb3RvdHlwZSwgXCJpZGVudGlmaWVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmlkZW50aWZpZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZ09ic2VydmVyLnByb3RvdHlwZSwgXCJhY3Rpb25BdHRyaWJ1dGVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjaGVtYS5hY3Rpb25BdHRyaWJ1dGU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZ09ic2VydmVyLnByb3RvdHlwZSwgXCJzY2hlbWFcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuc2NoZW1hO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUsIFwiYmluZGluZ3NcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMuYmluZGluZ3NCeUFjdGlvbi52YWx1ZXMoKSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLmNvbm5lY3RBY3Rpb24gPSBmdW5jdGlvbiAoYWN0aW9uKSB7XG4gICAgICAgIHZhciBiaW5kaW5nID0gbmV3IEJpbmRpbmcodGhpcy5jb250ZXh0LCBhY3Rpb24pO1xuICAgICAgICB0aGlzLmJpbmRpbmdzQnlBY3Rpb24uc2V0KGFjdGlvbiwgYmluZGluZyk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUuYmluZGluZ0Nvbm5lY3RlZChiaW5kaW5nKTtcbiAgICB9O1xuICAgIEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUuZGlzY29ubmVjdEFjdGlvbiA9IGZ1bmN0aW9uIChhY3Rpb24pIHtcbiAgICAgICAgdmFyIGJpbmRpbmcgPSB0aGlzLmJpbmRpbmdzQnlBY3Rpb24uZ2V0KGFjdGlvbik7XG4gICAgICAgIGlmIChiaW5kaW5nKSB7XG4gICAgICAgICAgICB0aGlzLmJpbmRpbmdzQnlBY3Rpb24uZGVsZXRlKGFjdGlvbik7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmJpbmRpbmdEaXNjb25uZWN0ZWQoYmluZGluZyk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUuZGlzY29ubmVjdEFsbEFjdGlvbnMgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHRoaXMuYmluZGluZ3MuZm9yRWFjaChmdW5jdGlvbiAoYmluZGluZykgeyByZXR1cm4gX3RoaXMuZGVsZWdhdGUuYmluZGluZ0Rpc2Nvbm5lY3RlZChiaW5kaW5nKTsgfSk7XG4gICAgICAgIHRoaXMuYmluZGluZ3NCeUFjdGlvbi5jbGVhcigpO1xuICAgIH07XG4gICAgLy8gVmFsdWUgb2JzZXJ2ZXIgZGVsZWdhdGVcbiAgICBCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLnBhcnNlVmFsdWVGb3JUb2tlbiA9IGZ1bmN0aW9uICh0b2tlbikge1xuICAgICAgICB2YXIgYWN0aW9uID0gQWN0aW9uLmZvclRva2VuKHRva2VuKTtcbiAgICAgICAgaWYgKGFjdGlvbi5pZGVudGlmaWVyID09IHRoaXMuaWRlbnRpZmllcikge1xuICAgICAgICAgICAgcmV0dXJuIGFjdGlvbjtcbiAgICAgICAgfVxuICAgIH07XG4gICAgQmluZGluZ09ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50TWF0Y2hlZFZhbHVlID0gZnVuY3Rpb24gKGVsZW1lbnQsIGFjdGlvbikge1xuICAgICAgICB0aGlzLmNvbm5lY3RBY3Rpb24oYWN0aW9uKTtcbiAgICB9O1xuICAgIEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudFVubWF0Y2hlZFZhbHVlID0gZnVuY3Rpb24gKGVsZW1lbnQsIGFjdGlvbikge1xuICAgICAgICB0aGlzLmRpc2Nvbm5lY3RBY3Rpb24oYWN0aW9uKTtcbiAgICB9O1xuICAgIHJldHVybiBCaW5kaW5nT2JzZXJ2ZXI7XG59KCkpO1xuZXhwb3J0IHsgQmluZGluZ09ic2VydmVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1iaW5kaW5nX29ic2VydmVyLmpzLm1hcCIsInZhciBfX2V4dGVuZHMgPSAodGhpcyAmJiB0aGlzLl9fZXh0ZW5kcykgfHwgKGZ1bmN0aW9uICgpIHtcbiAgICB2YXIgZXh0ZW5kU3RhdGljcyA9IGZ1bmN0aW9uIChkLCBiKSB7XG4gICAgICAgIGV4dGVuZFN0YXRpY3MgPSBPYmplY3Quc2V0UHJvdG90eXBlT2YgfHxcbiAgICAgICAgICAgICh7IF9fcHJvdG9fXzogW10gfSBpbnN0YW5jZW9mIEFycmF5ICYmIGZ1bmN0aW9uIChkLCBiKSB7IGQuX19wcm90b19fID0gYjsgfSkgfHxcbiAgICAgICAgICAgIGZ1bmN0aW9uIChkLCBiKSB7IGZvciAodmFyIHAgaW4gYikgaWYgKGIuaGFzT3duUHJvcGVydHkocCkpIGRbcF0gPSBiW3BdOyB9O1xuICAgICAgICByZXR1cm4gZXh0ZW5kU3RhdGljcyhkLCBiKTtcbiAgICB9O1xuICAgIHJldHVybiBmdW5jdGlvbiAoZCwgYikge1xuICAgICAgICBleHRlbmRTdGF0aWNzKGQsIGIpO1xuICAgICAgICBmdW5jdGlvbiBfXygpIHsgdGhpcy5jb25zdHJ1Y3RvciA9IGQ7IH1cbiAgICAgICAgZC5wcm90b3R5cGUgPSBiID09PSBudWxsID8gT2JqZWN0LmNyZWF0ZShiKSA6IChfXy5wcm90b3R5cGUgPSBiLnByb3RvdHlwZSwgbmV3IF9fKCkpO1xuICAgIH07XG59KSgpO1xudmFyIF9fc3ByZWFkQXJyYXlzID0gKHRoaXMgJiYgdGhpcy5fX3NwcmVhZEFycmF5cykgfHwgZnVuY3Rpb24gKCkge1xuICAgIGZvciAodmFyIHMgPSAwLCBpID0gMCwgaWwgPSBhcmd1bWVudHMubGVuZ3RoOyBpIDwgaWw7IGkrKykgcyArPSBhcmd1bWVudHNbaV0ubGVuZ3RoO1xuICAgIGZvciAodmFyIHIgPSBBcnJheShzKSwgayA9IDAsIGkgPSAwOyBpIDwgaWw7IGkrKylcbiAgICAgICAgZm9yICh2YXIgYSA9IGFyZ3VtZW50c1tpXSwgaiA9IDAsIGpsID0gYS5sZW5ndGg7IGogPCBqbDsgaisrLCBrKyspXG4gICAgICAgICAgICByW2tdID0gYVtqXTtcbiAgICByZXR1cm4gcjtcbn07XG5pbXBvcnQgeyByZWFkSW5oZXJpdGFibGVTdGF0aWNBcnJheVZhbHVlcyB9IGZyb20gXCIuL2luaGVyaXRhYmxlX3N0YXRpY3NcIjtcbi8qKiBAaGlkZGVuICovXG5leHBvcnQgZnVuY3Rpb24gYmxlc3MoY29uc3RydWN0b3IpIHtcbiAgICByZXR1cm4gc2hhZG93KGNvbnN0cnVjdG9yLCBnZXRCbGVzc2VkUHJvcGVydGllcyhjb25zdHJ1Y3RvcikpO1xufVxuZnVuY3Rpb24gc2hhZG93KGNvbnN0cnVjdG9yLCBwcm9wZXJ0aWVzKSB7XG4gICAgdmFyIHNoYWRvd0NvbnN0cnVjdG9yID0gZXh0ZW5kKGNvbnN0cnVjdG9yKTtcbiAgICB2YXIgc2hhZG93UHJvcGVydGllcyA9IGdldFNoYWRvd1Byb3BlcnRpZXMoY29uc3RydWN0b3IucHJvdG90eXBlLCBwcm9wZXJ0aWVzKTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydGllcyhzaGFkb3dDb25zdHJ1Y3Rvci5wcm90b3R5cGUsIHNoYWRvd1Byb3BlcnRpZXMpO1xuICAgIHJldHVybiBzaGFkb3dDb25zdHJ1Y3Rvcjtcbn1cbmZ1bmN0aW9uIGdldEJsZXNzZWRQcm9wZXJ0aWVzKGNvbnN0cnVjdG9yKSB7XG4gICAgdmFyIGJsZXNzaW5ncyA9IHJlYWRJbmhlcml0YWJsZVN0YXRpY0FycmF5VmFsdWVzKGNvbnN0cnVjdG9yLCBcImJsZXNzaW5nc1wiKTtcbiAgICByZXR1cm4gYmxlc3NpbmdzLnJlZHVjZShmdW5jdGlvbiAoYmxlc3NlZFByb3BlcnRpZXMsIGJsZXNzaW5nKSB7XG4gICAgICAgIHZhciBwcm9wZXJ0aWVzID0gYmxlc3NpbmcoY29uc3RydWN0b3IpO1xuICAgICAgICBmb3IgKHZhciBrZXkgaW4gcHJvcGVydGllcykge1xuICAgICAgICAgICAgdmFyIGRlc2NyaXB0b3IgPSBibGVzc2VkUHJvcGVydGllc1trZXldIHx8IHt9O1xuICAgICAgICAgICAgYmxlc3NlZFByb3BlcnRpZXNba2V5XSA9IE9iamVjdC5hc3NpZ24oZGVzY3JpcHRvciwgcHJvcGVydGllc1trZXldKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gYmxlc3NlZFByb3BlcnRpZXM7XG4gICAgfSwge30pO1xufVxuZnVuY3Rpb24gZ2V0U2hhZG93UHJvcGVydGllcyhwcm90b3R5cGUsIHByb3BlcnRpZXMpIHtcbiAgICByZXR1cm4gZ2V0T3duS2V5cyhwcm9wZXJ0aWVzKS5yZWR1Y2UoZnVuY3Rpb24gKHNoYWRvd1Byb3BlcnRpZXMsIGtleSkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIHZhciBkZXNjcmlwdG9yID0gZ2V0U2hhZG93ZWREZXNjcmlwdG9yKHByb3RvdHlwZSwgcHJvcGVydGllcywga2V5KTtcbiAgICAgICAgaWYgKGRlc2NyaXB0b3IpIHtcbiAgICAgICAgICAgIE9iamVjdC5hc3NpZ24oc2hhZG93UHJvcGVydGllcywgKF9hID0ge30sIF9hW2tleV0gPSBkZXNjcmlwdG9yLCBfYSkpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBzaGFkb3dQcm9wZXJ0aWVzO1xuICAgIH0sIHt9KTtcbn1cbmZ1bmN0aW9uIGdldFNoYWRvd2VkRGVzY3JpcHRvcihwcm90b3R5cGUsIHByb3BlcnRpZXMsIGtleSkge1xuICAgIHZhciBzaGFkb3dpbmdEZXNjcmlwdG9yID0gT2JqZWN0LmdldE93blByb3BlcnR5RGVzY3JpcHRvcihwcm90b3R5cGUsIGtleSk7XG4gICAgdmFyIHNoYWRvd2VkQnlWYWx1ZSA9IHNoYWRvd2luZ0Rlc2NyaXB0b3IgJiYgXCJ2YWx1ZVwiIGluIHNoYWRvd2luZ0Rlc2NyaXB0b3I7XG4gICAgaWYgKCFzaGFkb3dlZEJ5VmFsdWUpIHtcbiAgICAgICAgdmFyIGRlc2NyaXB0b3IgPSBPYmplY3QuZ2V0T3duUHJvcGVydHlEZXNjcmlwdG9yKHByb3BlcnRpZXMsIGtleSkudmFsdWU7XG4gICAgICAgIGlmIChzaGFkb3dpbmdEZXNjcmlwdG9yKSB7XG4gICAgICAgICAgICBkZXNjcmlwdG9yLmdldCA9IHNoYWRvd2luZ0Rlc2NyaXB0b3IuZ2V0IHx8IGRlc2NyaXB0b3IuZ2V0O1xuICAgICAgICAgICAgZGVzY3JpcHRvci5zZXQgPSBzaGFkb3dpbmdEZXNjcmlwdG9yLnNldCB8fCBkZXNjcmlwdG9yLnNldDtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZGVzY3JpcHRvcjtcbiAgICB9XG59XG52YXIgZ2V0T3duS2V5cyA9IChmdW5jdGlvbiAoKSB7XG4gICAgaWYgKHR5cGVvZiBPYmplY3QuZ2V0T3duUHJvcGVydHlTeW1ib2xzID09IFwiZnVuY3Rpb25cIikge1xuICAgICAgICByZXR1cm4gZnVuY3Rpb24gKG9iamVjdCkgeyByZXR1cm4gX19zcHJlYWRBcnJheXMoT2JqZWN0LmdldE93blByb3BlcnR5TmFtZXMob2JqZWN0KSwgT2JqZWN0LmdldE93blByb3BlcnR5U3ltYm9scyhvYmplY3QpKTsgfTtcbiAgICB9XG4gICAgZWxzZSB7XG4gICAgICAgIHJldHVybiBPYmplY3QuZ2V0T3duUHJvcGVydHlOYW1lcztcbiAgICB9XG59KSgpO1xudmFyIGV4dGVuZCA9IChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gZXh0ZW5kV2l0aFJlZmxlY3QoY29uc3RydWN0b3IpIHtcbiAgICAgICAgZnVuY3Rpb24gZXh0ZW5kZWQoKSB7XG4gICAgICAgICAgICB2YXIgX25ld1RhcmdldCA9IHRoaXMgJiYgdGhpcyBpbnN0YW5jZW9mIGV4dGVuZGVkID8gdGhpcy5jb25zdHJ1Y3RvciA6IHZvaWQgMDtcbiAgICAgICAgICAgIHJldHVybiBSZWZsZWN0LmNvbnN0cnVjdChjb25zdHJ1Y3RvciwgYXJndW1lbnRzLCBfbmV3VGFyZ2V0KTtcbiAgICAgICAgfVxuICAgICAgICBleHRlbmRlZC5wcm90b3R5cGUgPSBPYmplY3QuY3JlYXRlKGNvbnN0cnVjdG9yLnByb3RvdHlwZSwge1xuICAgICAgICAgICAgY29uc3RydWN0b3I6IHsgdmFsdWU6IGV4dGVuZGVkIH1cbiAgICAgICAgfSk7XG4gICAgICAgIFJlZmxlY3Quc2V0UHJvdG90eXBlT2YoZXh0ZW5kZWQsIGNvbnN0cnVjdG9yKTtcbiAgICAgICAgcmV0dXJuIGV4dGVuZGVkO1xuICAgIH1cbiAgICBmdW5jdGlvbiB0ZXN0UmVmbGVjdEV4dGVuc2lvbigpIHtcbiAgICAgICAgdmFyIGEgPSBmdW5jdGlvbiAoKSB7IHRoaXMuYS5jYWxsKHRoaXMpOyB9O1xuICAgICAgICB2YXIgYiA9IGV4dGVuZFdpdGhSZWZsZWN0KGEpO1xuICAgICAgICBiLnByb3RvdHlwZS5hID0gZnVuY3Rpb24gKCkgeyB9O1xuICAgICAgICByZXR1cm4gbmV3IGI7XG4gICAgfVxuICAgIHRyeSB7XG4gICAgICAgIHRlc3RSZWZsZWN0RXh0ZW5zaW9uKCk7XG4gICAgICAgIHJldHVybiBleHRlbmRXaXRoUmVmbGVjdDtcbiAgICB9XG4gICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgIHJldHVybiBmdW5jdGlvbiAoY29uc3RydWN0b3IpIHsgcmV0dXJuIC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uIChfc3VwZXIpIHtcbiAgICAgICAgICAgIF9fZXh0ZW5kcyhleHRlbmRlZCwgX3N1cGVyKTtcbiAgICAgICAgICAgIGZ1bmN0aW9uIGV4dGVuZGVkKCkge1xuICAgICAgICAgICAgICAgIHJldHVybiBfc3VwZXIgIT09IG51bGwgJiYgX3N1cGVyLmFwcGx5KHRoaXMsIGFyZ3VtZW50cykgfHwgdGhpcztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiBleHRlbmRlZDtcbiAgICAgICAgfShjb25zdHJ1Y3RvcikpOyB9O1xuICAgIH1cbn0pKCk7XG4vLyMgc291cmNlTWFwcGluZ1VSTD1ibGVzc2luZy5qcy5tYXAiLCJ2YXIgQ2xhc3NNYXAgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gQ2xhc3NNYXAoc2NvcGUpIHtcbiAgICAgICAgdGhpcy5zY29wZSA9IHNjb3BlO1xuICAgIH1cbiAgICBDbGFzc01hcC5wcm90b3R5cGUuaGFzID0gZnVuY3Rpb24gKG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGF0YS5oYXModGhpcy5nZXREYXRhS2V5KG5hbWUpKTtcbiAgICB9O1xuICAgIENsYXNzTWFwLnByb3RvdHlwZS5nZXQgPSBmdW5jdGlvbiAobmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5kYXRhLmdldCh0aGlzLmdldERhdGFLZXkobmFtZSkpO1xuICAgIH07XG4gICAgQ2xhc3NNYXAucHJvdG90eXBlLmdldEF0dHJpYnV0ZU5hbWUgPSBmdW5jdGlvbiAobmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5kYXRhLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkodGhpcy5nZXREYXRhS2V5KG5hbWUpKTtcbiAgICB9O1xuICAgIENsYXNzTWFwLnByb3RvdHlwZS5nZXREYXRhS2V5ID0gZnVuY3Rpb24gKG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIG5hbWUgKyBcIi1jbGFzc1wiO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENsYXNzTWFwLnByb3RvdHlwZSwgXCJkYXRhXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5kYXRhO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgcmV0dXJuIENsYXNzTWFwO1xufSgpKTtcbmV4cG9ydCB7IENsYXNzTWFwIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1jbGFzc19tYXAuanMubWFwIiwiaW1wb3J0IHsgcmVhZEluaGVyaXRhYmxlU3RhdGljQXJyYXlWYWx1ZXMgfSBmcm9tIFwiLi9pbmhlcml0YWJsZV9zdGF0aWNzXCI7XG5pbXBvcnQgeyBjYXBpdGFsaXplIH0gZnJvbSBcIi4vc3RyaW5nX2hlbHBlcnNcIjtcbi8qKiBAaGlkZGVuICovXG5leHBvcnQgZnVuY3Rpb24gQ2xhc3NQcm9wZXJ0aWVzQmxlc3NpbmcoY29uc3RydWN0b3IpIHtcbiAgICB2YXIgY2xhc3NlcyA9IHJlYWRJbmhlcml0YWJsZVN0YXRpY0FycmF5VmFsdWVzKGNvbnN0cnVjdG9yLCBcImNsYXNzZXNcIik7XG4gICAgcmV0dXJuIGNsYXNzZXMucmVkdWNlKGZ1bmN0aW9uIChwcm9wZXJ0aWVzLCBjbGFzc0RlZmluaXRpb24pIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24ocHJvcGVydGllcywgcHJvcGVydGllc0ZvckNsYXNzRGVmaW5pdGlvbihjbGFzc0RlZmluaXRpb24pKTtcbiAgICB9LCB7fSk7XG59XG5mdW5jdGlvbiBwcm9wZXJ0aWVzRm9yQ2xhc3NEZWZpbml0aW9uKGtleSkge1xuICAgIHZhciBfYTtcbiAgICB2YXIgbmFtZSA9IGtleSArIFwiQ2xhc3NcIjtcbiAgICByZXR1cm4gX2EgPSB7fSxcbiAgICAgICAgX2FbbmFtZV0gPSB7XG4gICAgICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB2YXIgY2xhc3NlcyA9IHRoaXMuY2xhc3NlcztcbiAgICAgICAgICAgICAgICBpZiAoY2xhc3Nlcy5oYXMoa2V5KSkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gY2xhc3Nlcy5nZXQoa2V5KTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIHZhciBhdHRyaWJ1dGUgPSBjbGFzc2VzLmdldEF0dHJpYnV0ZU5hbWUoa2V5KTtcbiAgICAgICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKFwiTWlzc2luZyBhdHRyaWJ1dGUgXFxcIlwiICsgYXR0cmlidXRlICsgXCJcXFwiXCIpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgX2FbXCJoYXNcIiArIGNhcGl0YWxpemUobmFtZSldID0ge1xuICAgICAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMuY2xhc3Nlcy5oYXMoa2V5KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgX2E7XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1jbGFzc19wcm9wZXJ0aWVzLmpzLm1hcCIsImltcG9ydCB7IEJpbmRpbmdPYnNlcnZlciB9IGZyb20gXCIuL2JpbmRpbmdfb2JzZXJ2ZXJcIjtcbmltcG9ydCB7IFZhbHVlT2JzZXJ2ZXIgfSBmcm9tIFwiLi92YWx1ZV9vYnNlcnZlclwiO1xudmFyIENvbnRleHQgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gQ29udGV4dChtb2R1bGUsIHNjb3BlKSB7XG4gICAgICAgIHRoaXMubW9kdWxlID0gbW9kdWxlO1xuICAgICAgICB0aGlzLnNjb3BlID0gc2NvcGU7XG4gICAgICAgIHRoaXMuY29udHJvbGxlciA9IG5ldyBtb2R1bGUuY29udHJvbGxlckNvbnN0cnVjdG9yKHRoaXMpO1xuICAgICAgICB0aGlzLmJpbmRpbmdPYnNlcnZlciA9IG5ldyBCaW5kaW5nT2JzZXJ2ZXIodGhpcywgdGhpcy5kaXNwYXRjaGVyKTtcbiAgICAgICAgdGhpcy52YWx1ZU9ic2VydmVyID0gbmV3IFZhbHVlT2JzZXJ2ZXIodGhpcywgdGhpcy5jb250cm9sbGVyKTtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHRoaXMuY29udHJvbGxlci5pbml0aWFsaXplKCk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICB0aGlzLmhhbmRsZUVycm9yKGVycm9yLCBcImluaXRpYWxpemluZyBjb250cm9sbGVyXCIpO1xuICAgICAgICB9XG4gICAgfVxuICAgIENvbnRleHQucHJvdG90eXBlLmNvbm5lY3QgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuYmluZGluZ09ic2VydmVyLnN0YXJ0KCk7XG4gICAgICAgIHRoaXMudmFsdWVPYnNlcnZlci5zdGFydCgpO1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgdGhpcy5jb250cm9sbGVyLmNvbm5lY3QoKTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHRoaXMuaGFuZGxlRXJyb3IoZXJyb3IsIFwiY29ubmVjdGluZyBjb250cm9sbGVyXCIpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBDb250ZXh0LnByb3RvdHlwZS5kaXNjb25uZWN0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgdGhpcy5jb250cm9sbGVyLmRpc2Nvbm5lY3QoKTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHRoaXMuaGFuZGxlRXJyb3IoZXJyb3IsIFwiZGlzY29ubmVjdGluZyBjb250cm9sbGVyXCIpO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMudmFsdWVPYnNlcnZlci5zdG9wKCk7XG4gICAgICAgIHRoaXMuYmluZGluZ09ic2VydmVyLnN0b3AoKTtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250ZXh0LnByb3RvdHlwZSwgXCJhcHBsaWNhdGlvblwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMubW9kdWxlLmFwcGxpY2F0aW9uO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRleHQucHJvdG90eXBlLCBcImlkZW50aWZpZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLm1vZHVsZS5pZGVudGlmaWVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRleHQucHJvdG90eXBlLCBcInNjaGVtYVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYXBwbGljYXRpb24uc2NoZW1hO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRleHQucHJvdG90eXBlLCBcImRpc3BhdGNoZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFwcGxpY2F0aW9uLmRpc3BhdGNoZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udGV4dC5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250ZXh0LnByb3RvdHlwZSwgXCJwYXJlbnRFbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50LnBhcmVudEVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICAvLyBFcnJvciBoYW5kbGluZ1xuICAgIENvbnRleHQucHJvdG90eXBlLmhhbmRsZUVycm9yID0gZnVuY3Rpb24gKGVycm9yLCBtZXNzYWdlLCBkZXRhaWwpIHtcbiAgICAgICAgaWYgKGRldGFpbCA9PT0gdm9pZCAwKSB7IGRldGFpbCA9IHt9OyB9XG4gICAgICAgIHZhciBfYSA9IHRoaXMsIGlkZW50aWZpZXIgPSBfYS5pZGVudGlmaWVyLCBjb250cm9sbGVyID0gX2EuY29udHJvbGxlciwgZWxlbWVudCA9IF9hLmVsZW1lbnQ7XG4gICAgICAgIGRldGFpbCA9IE9iamVjdC5hc3NpZ24oeyBpZGVudGlmaWVyOiBpZGVudGlmaWVyLCBjb250cm9sbGVyOiBjb250cm9sbGVyLCBlbGVtZW50OiBlbGVtZW50IH0sIGRldGFpbCk7XG4gICAgICAgIHRoaXMuYXBwbGljYXRpb24uaGFuZGxlRXJyb3IoZXJyb3IsIFwiRXJyb3IgXCIgKyBtZXNzYWdlLCBkZXRhaWwpO1xuICAgIH07XG4gICAgcmV0dXJuIENvbnRleHQ7XG59KCkpO1xuZXhwb3J0IHsgQ29udGV4dCB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9Y29udGV4dC5qcy5tYXAiLCJpbXBvcnQgeyBDbGFzc1Byb3BlcnRpZXNCbGVzc2luZyB9IGZyb20gXCIuL2NsYXNzX3Byb3BlcnRpZXNcIjtcbmltcG9ydCB7IFRhcmdldFByb3BlcnRpZXNCbGVzc2luZyB9IGZyb20gXCIuL3RhcmdldF9wcm9wZXJ0aWVzXCI7XG5pbXBvcnQgeyBWYWx1ZVByb3BlcnRpZXNCbGVzc2luZyB9IGZyb20gXCIuL3ZhbHVlX3Byb3BlcnRpZXNcIjtcbnZhciBDb250cm9sbGVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIENvbnRyb2xsZXIoY29udGV4dCkge1xuICAgICAgICB0aGlzLmNvbnRleHQgPSBjb250ZXh0O1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udHJvbGxlci5wcm90b3R5cGUsIFwiYXBwbGljYXRpb25cIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuYXBwbGljYXRpb247XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udHJvbGxlci5wcm90b3R5cGUsIFwic2NvcGVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuc2NvcGU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udHJvbGxlci5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250cm9sbGVyLnByb3RvdHlwZSwgXCJpZGVudGlmaWVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5pZGVudGlmaWVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRyb2xsZXIucHJvdG90eXBlLCBcInRhcmdldHNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLnRhcmdldHM7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udHJvbGxlci5wcm90b3R5cGUsIFwiY2xhc3Nlc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuY2xhc3NlcztcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250cm9sbGVyLnByb3RvdHlwZSwgXCJkYXRhXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5kYXRhO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgQ29udHJvbGxlci5wcm90b3R5cGUuaW5pdGlhbGl6ZSA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgLy8gT3ZlcnJpZGUgaW4geW91ciBzdWJjbGFzcyB0byBzZXQgdXAgaW5pdGlhbCBjb250cm9sbGVyIHN0YXRlXG4gICAgfTtcbiAgICBDb250cm9sbGVyLnByb3RvdHlwZS5jb25uZWN0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICAvLyBPdmVycmlkZSBpbiB5b3VyIHN1YmNsYXNzIHRvIHJlc3BvbmQgd2hlbiB0aGUgY29udHJvbGxlciBpcyBjb25uZWN0ZWQgdG8gdGhlIERPTVxuICAgIH07XG4gICAgQ29udHJvbGxlci5wcm90b3R5cGUuZGlzY29ubmVjdCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgLy8gT3ZlcnJpZGUgaW4geW91ciBzdWJjbGFzcyB0byByZXNwb25kIHdoZW4gdGhlIGNvbnRyb2xsZXIgaXMgZGlzY29ubmVjdGVkIGZyb20gdGhlIERPTVxuICAgIH07XG4gICAgQ29udHJvbGxlci5ibGVzc2luZ3MgPSBbQ2xhc3NQcm9wZXJ0aWVzQmxlc3NpbmcsIFRhcmdldFByb3BlcnRpZXNCbGVzc2luZywgVmFsdWVQcm9wZXJ0aWVzQmxlc3NpbmddO1xuICAgIENvbnRyb2xsZXIudGFyZ2V0cyA9IFtdO1xuICAgIENvbnRyb2xsZXIudmFsdWVzID0ge307XG4gICAgcmV0dXJuIENvbnRyb2xsZXI7XG59KCkpO1xuZXhwb3J0IHsgQ29udHJvbGxlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9Y29udHJvbGxlci5qcy5tYXAiLCJpbXBvcnQgeyBkYXNoZXJpemUgfSBmcm9tIFwiLi9zdHJpbmdfaGVscGVyc1wiO1xudmFyIERhdGFNYXAgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gRGF0YU1hcChzY29wZSkge1xuICAgICAgICB0aGlzLnNjb3BlID0gc2NvcGU7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShEYXRhTWFwLnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KERhdGFNYXAucHJvdG90eXBlLCBcImlkZW50aWZpZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmlkZW50aWZpZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBEYXRhTWFwLnByb3RvdHlwZS5nZXQgPSBmdW5jdGlvbiAoa2V5KSB7XG4gICAgICAgIHZhciBuYW1lID0gdGhpcy5nZXRBdHRyaWJ1dGVOYW1lRm9yS2V5KGtleSk7XG4gICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnQuZ2V0QXR0cmlidXRlKG5hbWUpO1xuICAgIH07XG4gICAgRGF0YU1hcC5wcm90b3R5cGUuc2V0ID0gZnVuY3Rpb24gKGtleSwgdmFsdWUpIHtcbiAgICAgICAgdmFyIG5hbWUgPSB0aGlzLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkoa2V5KTtcbiAgICAgICAgdGhpcy5lbGVtZW50LnNldEF0dHJpYnV0ZShuYW1lLCB2YWx1ZSk7XG4gICAgICAgIHJldHVybiB0aGlzLmdldChrZXkpO1xuICAgIH07XG4gICAgRGF0YU1hcC5wcm90b3R5cGUuaGFzID0gZnVuY3Rpb24gKGtleSkge1xuICAgICAgICB2YXIgbmFtZSA9IHRoaXMuZ2V0QXR0cmlidXRlTmFtZUZvcktleShrZXkpO1xuICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50Lmhhc0F0dHJpYnV0ZShuYW1lKTtcbiAgICB9O1xuICAgIERhdGFNYXAucHJvdG90eXBlLmRlbGV0ZSA9IGZ1bmN0aW9uIChrZXkpIHtcbiAgICAgICAgaWYgKHRoaXMuaGFzKGtleSkpIHtcbiAgICAgICAgICAgIHZhciBuYW1lXzEgPSB0aGlzLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkoa2V5KTtcbiAgICAgICAgICAgIHRoaXMuZWxlbWVudC5yZW1vdmVBdHRyaWJ1dGUobmFtZV8xKTtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBEYXRhTWFwLnByb3RvdHlwZS5nZXRBdHRyaWJ1dGVOYW1lRm9yS2V5ID0gZnVuY3Rpb24gKGtleSkge1xuICAgICAgICByZXR1cm4gXCJkYXRhLVwiICsgdGhpcy5pZGVudGlmaWVyICsgXCItXCIgKyBkYXNoZXJpemUoa2V5KTtcbiAgICB9O1xuICAgIHJldHVybiBEYXRhTWFwO1xufSgpKTtcbmV4cG9ydCB7IERhdGFNYXAgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWRhdGFfbWFwLmpzLm1hcCIsImltcG9ydCB7IGJsZXNzIH0gZnJvbSBcIi4vYmxlc3NpbmdcIjtcbi8qKiBAaGlkZGVuICovXG5leHBvcnQgZnVuY3Rpb24gYmxlc3NEZWZpbml0aW9uKGRlZmluaXRpb24pIHtcbiAgICByZXR1cm4ge1xuICAgICAgICBpZGVudGlmaWVyOiBkZWZpbml0aW9uLmlkZW50aWZpZXIsXG4gICAgICAgIGNvbnRyb2xsZXJDb25zdHJ1Y3RvcjogYmxlc3MoZGVmaW5pdGlvbi5jb250cm9sbGVyQ29uc3RydWN0b3IpXG4gICAgfTtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWRlZmluaXRpb24uanMubWFwIiwiaW1wb3J0IHsgRXZlbnRMaXN0ZW5lciB9IGZyb20gXCIuL2V2ZW50X2xpc3RlbmVyXCI7XG52YXIgRGlzcGF0Y2hlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBEaXNwYXRjaGVyKGFwcGxpY2F0aW9uKSB7XG4gICAgICAgIHRoaXMuYXBwbGljYXRpb24gPSBhcHBsaWNhdGlvbjtcbiAgICAgICAgdGhpcy5ldmVudExpc3RlbmVyTWFwcyA9IG5ldyBNYXA7XG4gICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgIH1cbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IHRydWU7XG4gICAgICAgICAgICB0aGlzLmV2ZW50TGlzdGVuZXJzLmZvckVhY2goZnVuY3Rpb24gKGV2ZW50TGlzdGVuZXIpIHsgcmV0dXJuIGV2ZW50TGlzdGVuZXIuY29ubmVjdCgpOyB9KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgICAgICB0aGlzLmV2ZW50TGlzdGVuZXJzLmZvckVhY2goZnVuY3Rpb24gKGV2ZW50TGlzdGVuZXIpIHsgcmV0dXJuIGV2ZW50TGlzdGVuZXIuZGlzY29ubmVjdCgpOyB9KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KERpc3BhdGNoZXIucHJvdG90eXBlLCBcImV2ZW50TGlzdGVuZXJzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLmV2ZW50TGlzdGVuZXJNYXBzLnZhbHVlcygpKVxuICAgICAgICAgICAgICAgIC5yZWR1Y2UoZnVuY3Rpb24gKGxpc3RlbmVycywgbWFwKSB7IHJldHVybiBsaXN0ZW5lcnMuY29uY2F0KEFycmF5LmZyb20obWFwLnZhbHVlcygpKSk7IH0sIFtdKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIC8vIEJpbmRpbmcgb2JzZXJ2ZXIgZGVsZWdhdGVcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLmJpbmRpbmdDb25uZWN0ZWQgPSBmdW5jdGlvbiAoYmluZGluZykge1xuICAgICAgICB0aGlzLmZldGNoRXZlbnRMaXN0ZW5lckZvckJpbmRpbmcoYmluZGluZykuYmluZGluZ0Nvbm5lY3RlZChiaW5kaW5nKTtcbiAgICB9O1xuICAgIC8qKiBAaGlkZGVuICovXG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuYmluZGluZ0Rpc2Nvbm5lY3RlZCA9IGZ1bmN0aW9uIChiaW5kaW5nKSB7XG4gICAgICAgIHRoaXMuZmV0Y2hFdmVudExpc3RlbmVyRm9yQmluZGluZyhiaW5kaW5nKS5iaW5kaW5nRGlzY29ubmVjdGVkKGJpbmRpbmcpO1xuICAgIH07XG4gICAgLy8gRXJyb3IgaGFuZGxpbmdcbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5oYW5kbGVFcnJvciA9IGZ1bmN0aW9uIChlcnJvciwgbWVzc2FnZSwgZGV0YWlsKSB7XG4gICAgICAgIGlmIChkZXRhaWwgPT09IHZvaWQgMCkgeyBkZXRhaWwgPSB7fTsgfVxuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uLmhhbmRsZUVycm9yKGVycm9yLCBcIkVycm9yIFwiICsgbWVzc2FnZSwgZGV0YWlsKTtcbiAgICB9O1xuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLmZldGNoRXZlbnRMaXN0ZW5lckZvckJpbmRpbmcgPSBmdW5jdGlvbiAoYmluZGluZykge1xuICAgICAgICB2YXIgZXZlbnRUYXJnZXQgPSBiaW5kaW5nLmV2ZW50VGFyZ2V0LCBldmVudE5hbWUgPSBiaW5kaW5nLmV2ZW50TmFtZSwgZXZlbnRPcHRpb25zID0gYmluZGluZy5ldmVudE9wdGlvbnM7XG4gICAgICAgIHJldHVybiB0aGlzLmZldGNoRXZlbnRMaXN0ZW5lcihldmVudFRhcmdldCwgZXZlbnROYW1lLCBldmVudE9wdGlvbnMpO1xuICAgIH07XG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuZmV0Y2hFdmVudExpc3RlbmVyID0gZnVuY3Rpb24gKGV2ZW50VGFyZ2V0LCBldmVudE5hbWUsIGV2ZW50T3B0aW9ucykge1xuICAgICAgICB2YXIgZXZlbnRMaXN0ZW5lck1hcCA9IHRoaXMuZmV0Y2hFdmVudExpc3RlbmVyTWFwRm9yRXZlbnRUYXJnZXQoZXZlbnRUYXJnZXQpO1xuICAgICAgICB2YXIgY2FjaGVLZXkgPSB0aGlzLmNhY2hlS2V5KGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKTtcbiAgICAgICAgdmFyIGV2ZW50TGlzdGVuZXIgPSBldmVudExpc3RlbmVyTWFwLmdldChjYWNoZUtleSk7XG4gICAgICAgIGlmICghZXZlbnRMaXN0ZW5lcikge1xuICAgICAgICAgICAgZXZlbnRMaXN0ZW5lciA9IHRoaXMuY3JlYXRlRXZlbnRMaXN0ZW5lcihldmVudFRhcmdldCwgZXZlbnROYW1lLCBldmVudE9wdGlvbnMpO1xuICAgICAgICAgICAgZXZlbnRMaXN0ZW5lck1hcC5zZXQoY2FjaGVLZXksIGV2ZW50TGlzdGVuZXIpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBldmVudExpc3RlbmVyO1xuICAgIH07XG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuY3JlYXRlRXZlbnRMaXN0ZW5lciA9IGZ1bmN0aW9uIChldmVudFRhcmdldCwgZXZlbnROYW1lLCBldmVudE9wdGlvbnMpIHtcbiAgICAgICAgdmFyIGV2ZW50TGlzdGVuZXIgPSBuZXcgRXZlbnRMaXN0ZW5lcihldmVudFRhcmdldCwgZXZlbnROYW1lLCBldmVudE9wdGlvbnMpO1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICBldmVudExpc3RlbmVyLmNvbm5lY3QoKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZXZlbnRMaXN0ZW5lcjtcbiAgICB9O1xuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLmZldGNoRXZlbnRMaXN0ZW5lck1hcEZvckV2ZW50VGFyZ2V0ID0gZnVuY3Rpb24gKGV2ZW50VGFyZ2V0KSB7XG4gICAgICAgIHZhciBldmVudExpc3RlbmVyTWFwID0gdGhpcy5ldmVudExpc3RlbmVyTWFwcy5nZXQoZXZlbnRUYXJnZXQpO1xuICAgICAgICBpZiAoIWV2ZW50TGlzdGVuZXJNYXApIHtcbiAgICAgICAgICAgIGV2ZW50TGlzdGVuZXJNYXAgPSBuZXcgTWFwO1xuICAgICAgICAgICAgdGhpcy5ldmVudExpc3RlbmVyTWFwcy5zZXQoZXZlbnRUYXJnZXQsIGV2ZW50TGlzdGVuZXJNYXApO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBldmVudExpc3RlbmVyTWFwO1xuICAgIH07XG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuY2FjaGVLZXkgPSBmdW5jdGlvbiAoZXZlbnROYW1lLCBldmVudE9wdGlvbnMpIHtcbiAgICAgICAgdmFyIHBhcnRzID0gW2V2ZW50TmFtZV07XG4gICAgICAgIE9iamVjdC5rZXlzKGV2ZW50T3B0aW9ucykuc29ydCgpLmZvckVhY2goZnVuY3Rpb24gKGtleSkge1xuICAgICAgICAgICAgcGFydHMucHVzaChcIlwiICsgKGV2ZW50T3B0aW9uc1trZXldID8gXCJcIiA6IFwiIVwiKSArIGtleSk7XG4gICAgICAgIH0pO1xuICAgICAgICByZXR1cm4gcGFydHMuam9pbihcIjpcIik7XG4gICAgfTtcbiAgICByZXR1cm4gRGlzcGF0Y2hlcjtcbn0oKSk7XG5leHBvcnQgeyBEaXNwYXRjaGVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1kaXNwYXRjaGVyLmpzLm1hcCIsInZhciBFdmVudExpc3RlbmVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIEV2ZW50TGlzdGVuZXIoZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKSB7XG4gICAgICAgIHRoaXMuZXZlbnRUYXJnZXQgPSBldmVudFRhcmdldDtcbiAgICAgICAgdGhpcy5ldmVudE5hbWUgPSBldmVudE5hbWU7XG4gICAgICAgIHRoaXMuZXZlbnRPcHRpb25zID0gZXZlbnRPcHRpb25zO1xuICAgICAgICB0aGlzLnVub3JkZXJlZEJpbmRpbmdzID0gbmV3IFNldCgpO1xuICAgIH1cbiAgICBFdmVudExpc3RlbmVyLnByb3RvdHlwZS5jb25uZWN0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmV2ZW50VGFyZ2V0LmFkZEV2ZW50TGlzdGVuZXIodGhpcy5ldmVudE5hbWUsIHRoaXMsIHRoaXMuZXZlbnRPcHRpb25zKTtcbiAgICB9O1xuICAgIEV2ZW50TGlzdGVuZXIucHJvdG90eXBlLmRpc2Nvbm5lY3QgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuZXZlbnRUYXJnZXQucmVtb3ZlRXZlbnRMaXN0ZW5lcih0aGlzLmV2ZW50TmFtZSwgdGhpcywgdGhpcy5ldmVudE9wdGlvbnMpO1xuICAgIH07XG4gICAgLy8gQmluZGluZyBvYnNlcnZlciBkZWxlZ2F0ZVxuICAgIC8qKiBAaGlkZGVuICovXG4gICAgRXZlbnRMaXN0ZW5lci5wcm90b3R5cGUuYmluZGluZ0Nvbm5lY3RlZCA9IGZ1bmN0aW9uIChiaW5kaW5nKSB7XG4gICAgICAgIHRoaXMudW5vcmRlcmVkQmluZGluZ3MuYWRkKGJpbmRpbmcpO1xuICAgIH07XG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBFdmVudExpc3RlbmVyLnByb3RvdHlwZS5iaW5kaW5nRGlzY29ubmVjdGVkID0gZnVuY3Rpb24gKGJpbmRpbmcpIHtcbiAgICAgICAgdGhpcy51bm9yZGVyZWRCaW5kaW5ncy5kZWxldGUoYmluZGluZyk7XG4gICAgfTtcbiAgICBFdmVudExpc3RlbmVyLnByb3RvdHlwZS5oYW5kbGVFdmVudCA9IGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgICB2YXIgZXh0ZW5kZWRFdmVudCA9IGV4dGVuZEV2ZW50KGV2ZW50KTtcbiAgICAgICAgZm9yICh2YXIgX2kgPSAwLCBfYSA9IHRoaXMuYmluZGluZ3M7IF9pIDwgX2EubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICB2YXIgYmluZGluZyA9IF9hW19pXTtcbiAgICAgICAgICAgIGlmIChleHRlbmRlZEV2ZW50LmltbWVkaWF0ZVByb3BhZ2F0aW9uU3RvcHBlZCkge1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgYmluZGluZy5oYW5kbGVFdmVudChleHRlbmRlZEV2ZW50KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEV2ZW50TGlzdGVuZXIucHJvdG90eXBlLCBcImJpbmRpbmdzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLnVub3JkZXJlZEJpbmRpbmdzKS5zb3J0KGZ1bmN0aW9uIChsZWZ0LCByaWdodCkge1xuICAgICAgICAgICAgICAgIHZhciBsZWZ0SW5kZXggPSBsZWZ0LmluZGV4LCByaWdodEluZGV4ID0gcmlnaHQuaW5kZXg7XG4gICAgICAgICAgICAgICAgcmV0dXJuIGxlZnRJbmRleCA8IHJpZ2h0SW5kZXggPyAtMSA6IGxlZnRJbmRleCA+IHJpZ2h0SW5kZXggPyAxIDogMDtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgcmV0dXJuIEV2ZW50TGlzdGVuZXI7XG59KCkpO1xuZXhwb3J0IHsgRXZlbnRMaXN0ZW5lciB9O1xuZnVuY3Rpb24gZXh0ZW5kRXZlbnQoZXZlbnQpIHtcbiAgICBpZiAoXCJpbW1lZGlhdGVQcm9wYWdhdGlvblN0b3BwZWRcIiBpbiBldmVudCkge1xuICAgICAgICByZXR1cm4gZXZlbnQ7XG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgICB2YXIgc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uXzEgPSBldmVudC5zdG9wSW1tZWRpYXRlUHJvcGFnYXRpb247XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKGV2ZW50LCB7XG4gICAgICAgICAgICBpbW1lZGlhdGVQcm9wYWdhdGlvblN0b3BwZWQ6IGZhbHNlLFxuICAgICAgICAgICAgc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5pbW1lZGlhdGVQcm9wYWdhdGlvblN0b3BwZWQgPSB0cnVlO1xuICAgICAgICAgICAgICAgIHN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbl8xLmNhbGwodGhpcyk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgIH1cbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWV2ZW50X2xpc3RlbmVyLmpzLm1hcCIsInZhciBHdWlkZSA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBHdWlkZShsb2dnZXIpIHtcbiAgICAgICAgdGhpcy53YXJuZWRLZXlzQnlPYmplY3QgPSBuZXcgV2Vha01hcDtcbiAgICAgICAgdGhpcy5sb2dnZXIgPSBsb2dnZXI7XG4gICAgfVxuICAgIEd1aWRlLnByb3RvdHlwZS53YXJuID0gZnVuY3Rpb24gKG9iamVjdCwga2V5LCBtZXNzYWdlKSB7XG4gICAgICAgIHZhciB3YXJuZWRLZXlzID0gdGhpcy53YXJuZWRLZXlzQnlPYmplY3QuZ2V0KG9iamVjdCk7XG4gICAgICAgIGlmICghd2FybmVkS2V5cykge1xuICAgICAgICAgICAgd2FybmVkS2V5cyA9IG5ldyBTZXQ7XG4gICAgICAgICAgICB0aGlzLndhcm5lZEtleXNCeU9iamVjdC5zZXQob2JqZWN0LCB3YXJuZWRLZXlzKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoIXdhcm5lZEtleXMuaGFzKGtleSkpIHtcbiAgICAgICAgICAgIHdhcm5lZEtleXMuYWRkKGtleSk7XG4gICAgICAgICAgICB0aGlzLmxvZ2dlci53YXJuKG1lc3NhZ2UsIG9iamVjdCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIHJldHVybiBHdWlkZTtcbn0oKSk7XG5leHBvcnQgeyBHdWlkZSB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9Z3VpZGUuanMubWFwIiwiZXhwb3J0IHsgQXBwbGljYXRpb24gfSBmcm9tIFwiLi9hcHBsaWNhdGlvblwiO1xuZXhwb3J0IHsgQ29udGV4dCB9IGZyb20gXCIuL2NvbnRleHRcIjtcbmV4cG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tIFwiLi9jb250cm9sbGVyXCI7XG5leHBvcnQgeyBkZWZhdWx0U2NoZW1hIH0gZnJvbSBcIi4vc2NoZW1hXCI7XG4vLyMgc291cmNlTWFwcGluZ1VSTD1pbmRleC5qcy5tYXAiLCJleHBvcnQgZnVuY3Rpb24gcmVhZEluaGVyaXRhYmxlU3RhdGljQXJyYXlWYWx1ZXMoY29uc3RydWN0b3IsIHByb3BlcnR5TmFtZSkge1xuICAgIHZhciBhbmNlc3RvcnMgPSBnZXRBbmNlc3RvcnNGb3JDb25zdHJ1Y3Rvcihjb25zdHJ1Y3Rvcik7XG4gICAgcmV0dXJuIEFycmF5LmZyb20oYW5jZXN0b3JzLnJlZHVjZShmdW5jdGlvbiAodmFsdWVzLCBjb25zdHJ1Y3Rvcikge1xuICAgICAgICBnZXRPd25TdGF0aWNBcnJheVZhbHVlcyhjb25zdHJ1Y3RvciwgcHJvcGVydHlOYW1lKS5mb3JFYWNoKGZ1bmN0aW9uIChuYW1lKSB7IHJldHVybiB2YWx1ZXMuYWRkKG5hbWUpOyB9KTtcbiAgICAgICAgcmV0dXJuIHZhbHVlcztcbiAgICB9LCBuZXcgU2V0KSk7XG59XG5leHBvcnQgZnVuY3Rpb24gcmVhZEluaGVyaXRhYmxlU3RhdGljT2JqZWN0UGFpcnMoY29uc3RydWN0b3IsIHByb3BlcnR5TmFtZSkge1xuICAgIHZhciBhbmNlc3RvcnMgPSBnZXRBbmNlc3RvcnNGb3JDb25zdHJ1Y3Rvcihjb25zdHJ1Y3Rvcik7XG4gICAgcmV0dXJuIGFuY2VzdG9ycy5yZWR1Y2UoZnVuY3Rpb24gKHBhaXJzLCBjb25zdHJ1Y3Rvcikge1xuICAgICAgICBwYWlycy5wdXNoLmFwcGx5KHBhaXJzLCBnZXRPd25TdGF0aWNPYmplY3RQYWlycyhjb25zdHJ1Y3RvciwgcHJvcGVydHlOYW1lKSk7XG4gICAgICAgIHJldHVybiBwYWlycztcbiAgICB9LCBbXSk7XG59XG5mdW5jdGlvbiBnZXRBbmNlc3RvcnNGb3JDb25zdHJ1Y3Rvcihjb25zdHJ1Y3Rvcikge1xuICAgIHZhciBhbmNlc3RvcnMgPSBbXTtcbiAgICB3aGlsZSAoY29uc3RydWN0b3IpIHtcbiAgICAgICAgYW5jZXN0b3JzLnB1c2goY29uc3RydWN0b3IpO1xuICAgICAgICBjb25zdHJ1Y3RvciA9IE9iamVjdC5nZXRQcm90b3R5cGVPZihjb25zdHJ1Y3Rvcik7XG4gICAgfVxuICAgIHJldHVybiBhbmNlc3RvcnMucmV2ZXJzZSgpO1xufVxuZnVuY3Rpb24gZ2V0T3duU3RhdGljQXJyYXlWYWx1ZXMoY29uc3RydWN0b3IsIHByb3BlcnR5TmFtZSkge1xuICAgIHZhciBkZWZpbml0aW9uID0gY29uc3RydWN0b3JbcHJvcGVydHlOYW1lXTtcbiAgICByZXR1cm4gQXJyYXkuaXNBcnJheShkZWZpbml0aW9uKSA/IGRlZmluaXRpb24gOiBbXTtcbn1cbmZ1bmN0aW9uIGdldE93blN0YXRpY09iamVjdFBhaXJzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpIHtcbiAgICB2YXIgZGVmaW5pdGlvbiA9IGNvbnN0cnVjdG9yW3Byb3BlcnR5TmFtZV07XG4gICAgcmV0dXJuIGRlZmluaXRpb24gPyBPYmplY3Qua2V5cyhkZWZpbml0aW9uKS5tYXAoZnVuY3Rpb24gKGtleSkgeyByZXR1cm4gW2tleSwgZGVmaW5pdGlvbltrZXldXTsgfSkgOiBbXTtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWluaGVyaXRhYmxlX3N0YXRpY3MuanMubWFwIiwiaW1wb3J0IHsgQ29udGV4dCB9IGZyb20gXCIuL2NvbnRleHRcIjtcbmltcG9ydCB7IGJsZXNzRGVmaW5pdGlvbiB9IGZyb20gXCIuL2RlZmluaXRpb25cIjtcbnZhciBNb2R1bGUgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gTW9kdWxlKGFwcGxpY2F0aW9uLCBkZWZpbml0aW9uKSB7XG4gICAgICAgIHRoaXMuYXBwbGljYXRpb24gPSBhcHBsaWNhdGlvbjtcbiAgICAgICAgdGhpcy5kZWZpbml0aW9uID0gYmxlc3NEZWZpbml0aW9uKGRlZmluaXRpb24pO1xuICAgICAgICB0aGlzLmNvbnRleHRzQnlTY29wZSA9IG5ldyBXZWFrTWFwO1xuICAgICAgICB0aGlzLmNvbm5lY3RlZENvbnRleHRzID0gbmV3IFNldDtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KE1vZHVsZS5wcm90b3R5cGUsIFwiaWRlbnRpZmllclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZGVmaW5pdGlvbi5pZGVudGlmaWVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KE1vZHVsZS5wcm90b3R5cGUsIFwiY29udHJvbGxlckNvbnN0cnVjdG9yXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5kZWZpbml0aW9uLmNvbnRyb2xsZXJDb25zdHJ1Y3RvcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShNb2R1bGUucHJvdG90eXBlLCBcImNvbnRleHRzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLmNvbm5lY3RlZENvbnRleHRzKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE1vZHVsZS5wcm90b3R5cGUuY29ubmVjdENvbnRleHRGb3JTY29wZSA9IGZ1bmN0aW9uIChzY29wZSkge1xuICAgICAgICB2YXIgY29udGV4dCA9IHRoaXMuZmV0Y2hDb250ZXh0Rm9yU2NvcGUoc2NvcGUpO1xuICAgICAgICB0aGlzLmNvbm5lY3RlZENvbnRleHRzLmFkZChjb250ZXh0KTtcbiAgICAgICAgY29udGV4dC5jb25uZWN0KCk7XG4gICAgfTtcbiAgICBNb2R1bGUucHJvdG90eXBlLmRpc2Nvbm5lY3RDb250ZXh0Rm9yU2NvcGUgPSBmdW5jdGlvbiAoc2NvcGUpIHtcbiAgICAgICAgdmFyIGNvbnRleHQgPSB0aGlzLmNvbnRleHRzQnlTY29wZS5nZXQoc2NvcGUpO1xuICAgICAgICBpZiAoY29udGV4dCkge1xuICAgICAgICAgICAgdGhpcy5jb25uZWN0ZWRDb250ZXh0cy5kZWxldGUoY29udGV4dCk7XG4gICAgICAgICAgICBjb250ZXh0LmRpc2Nvbm5lY3QoKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgTW9kdWxlLnByb3RvdHlwZS5mZXRjaENvbnRleHRGb3JTY29wZSA9IGZ1bmN0aW9uIChzY29wZSkge1xuICAgICAgICB2YXIgY29udGV4dCA9IHRoaXMuY29udGV4dHNCeVNjb3BlLmdldChzY29wZSk7XG4gICAgICAgIGlmICghY29udGV4dCkge1xuICAgICAgICAgICAgY29udGV4dCA9IG5ldyBDb250ZXh0KHRoaXMsIHNjb3BlKTtcbiAgICAgICAgICAgIHRoaXMuY29udGV4dHNCeVNjb3BlLnNldChzY29wZSwgY29udGV4dCk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGNvbnRleHQ7XG4gICAgfTtcbiAgICByZXR1cm4gTW9kdWxlO1xufSgpKTtcbmV4cG9ydCB7IE1vZHVsZSB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9bW9kdWxlLmpzLm1hcCIsImltcG9ydCB7IE1vZHVsZSB9IGZyb20gXCIuL21vZHVsZVwiO1xuaW1wb3J0IHsgTXVsdGltYXAgfSBmcm9tIFwiQHN0aW11bHVzL211bHRpbWFwXCI7XG5pbXBvcnQgeyBTY29wZSB9IGZyb20gXCIuL3Njb3BlXCI7XG5pbXBvcnQgeyBTY29wZU9ic2VydmVyIH0gZnJvbSBcIi4vc2NvcGVfb2JzZXJ2ZXJcIjtcbnZhciBSb3V0ZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gUm91dGVyKGFwcGxpY2F0aW9uKSB7XG4gICAgICAgIHRoaXMuYXBwbGljYXRpb24gPSBhcHBsaWNhdGlvbjtcbiAgICAgICAgdGhpcy5zY29wZU9ic2VydmVyID0gbmV3IFNjb3BlT2JzZXJ2ZXIodGhpcy5lbGVtZW50LCB0aGlzLnNjaGVtYSwgdGhpcyk7XG4gICAgICAgIHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyID0gbmV3IE11bHRpbWFwO1xuICAgICAgICB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIgPSBuZXcgTWFwO1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoUm91dGVyLnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hcHBsaWNhdGlvbi5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFJvdXRlci5wcm90b3R5cGUsIFwic2NoZW1hXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hcHBsaWNhdGlvbi5zY2hlbWE7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoUm91dGVyLnByb3RvdHlwZSwgXCJsb2dnZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFwcGxpY2F0aW9uLmxvZ2dlcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShSb3V0ZXIucHJvdG90eXBlLCBcImNvbnRyb2xsZXJBdHRyaWJ1dGVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjaGVtYS5jb250cm9sbGVyQXR0cmlidXRlO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFJvdXRlci5wcm90b3R5cGUsIFwibW9kdWxlc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyLnZhbHVlcygpKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShSb3V0ZXIucHJvdG90eXBlLCBcImNvbnRleHRzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5tb2R1bGVzLnJlZHVjZShmdW5jdGlvbiAoY29udGV4dHMsIG1vZHVsZSkgeyByZXR1cm4gY29udGV4dHMuY29uY2F0KG1vZHVsZS5jb250ZXh0cyk7IH0sIFtdKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIFJvdXRlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuc2NvcGVPYnNlcnZlci5zdGFydCgpO1xuICAgIH07XG4gICAgUm91dGVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnNjb3BlT2JzZXJ2ZXIuc3RvcCgpO1xuICAgIH07XG4gICAgUm91dGVyLnByb3RvdHlwZS5sb2FkRGVmaW5pdGlvbiA9IGZ1bmN0aW9uIChkZWZpbml0aW9uKSB7XG4gICAgICAgIHRoaXMudW5sb2FkSWRlbnRpZmllcihkZWZpbml0aW9uLmlkZW50aWZpZXIpO1xuICAgICAgICB2YXIgbW9kdWxlID0gbmV3IE1vZHVsZSh0aGlzLmFwcGxpY2F0aW9uLCBkZWZpbml0aW9uKTtcbiAgICAgICAgdGhpcy5jb25uZWN0TW9kdWxlKG1vZHVsZSk7XG4gICAgfTtcbiAgICBSb3V0ZXIucHJvdG90eXBlLnVubG9hZElkZW50aWZpZXIgPSBmdW5jdGlvbiAoaWRlbnRpZmllcikge1xuICAgICAgICB2YXIgbW9kdWxlID0gdGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyLmdldChpZGVudGlmaWVyKTtcbiAgICAgICAgaWYgKG1vZHVsZSkge1xuICAgICAgICAgICAgdGhpcy5kaXNjb25uZWN0TW9kdWxlKG1vZHVsZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFJvdXRlci5wcm90b3R5cGUuZ2V0Q29udGV4dEZvckVsZW1lbnRBbmRJZGVudGlmaWVyID0gZnVuY3Rpb24gKGVsZW1lbnQsIGlkZW50aWZpZXIpIHtcbiAgICAgICAgdmFyIG1vZHVsZSA9IHRoaXMubW9kdWxlc0J5SWRlbnRpZmllci5nZXQoaWRlbnRpZmllcik7XG4gICAgICAgIGlmIChtb2R1bGUpIHtcbiAgICAgICAgICAgIHJldHVybiBtb2R1bGUuY29udGV4dHMuZmluZChmdW5jdGlvbiAoY29udGV4dCkgeyByZXR1cm4gY29udGV4dC5lbGVtZW50ID09IGVsZW1lbnQ7IH0pO1xuICAgICAgICB9XG4gICAgfTtcbiAgICAvLyBFcnJvciBoYW5kbGVyIGRlbGVnYXRlXG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBSb3V0ZXIucHJvdG90eXBlLmhhbmRsZUVycm9yID0gZnVuY3Rpb24gKGVycm9yLCBtZXNzYWdlLCBkZXRhaWwpIHtcbiAgICAgICAgdGhpcy5hcHBsaWNhdGlvbi5oYW5kbGVFcnJvcihlcnJvciwgbWVzc2FnZSwgZGV0YWlsKTtcbiAgICB9O1xuICAgIC8vIFNjb3BlIG9ic2VydmVyIGRlbGVnYXRlXG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBSb3V0ZXIucHJvdG90eXBlLmNyZWF0ZVNjb3BlRm9yRWxlbWVudEFuZElkZW50aWZpZXIgPSBmdW5jdGlvbiAoZWxlbWVudCwgaWRlbnRpZmllcikge1xuICAgICAgICByZXR1cm4gbmV3IFNjb3BlKHRoaXMuc2NoZW1hLCBlbGVtZW50LCBpZGVudGlmaWVyLCB0aGlzLmxvZ2dlcik7XG4gICAgfTtcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIFJvdXRlci5wcm90b3R5cGUuc2NvcGVDb25uZWN0ZWQgPSBmdW5jdGlvbiAoc2NvcGUpIHtcbiAgICAgICAgdGhpcy5zY29wZXNCeUlkZW50aWZpZXIuYWRkKHNjb3BlLmlkZW50aWZpZXIsIHNjb3BlKTtcbiAgICAgICAgdmFyIG1vZHVsZSA9IHRoaXMubW9kdWxlc0J5SWRlbnRpZmllci5nZXQoc2NvcGUuaWRlbnRpZmllcik7XG4gICAgICAgIGlmIChtb2R1bGUpIHtcbiAgICAgICAgICAgIG1vZHVsZS5jb25uZWN0Q29udGV4dEZvclNjb3BlKHNjb3BlKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBSb3V0ZXIucHJvdG90eXBlLnNjb3BlRGlzY29ubmVjdGVkID0gZnVuY3Rpb24gKHNjb3BlKSB7XG4gICAgICAgIHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyLmRlbGV0ZShzY29wZS5pZGVudGlmaWVyLCBzY29wZSk7XG4gICAgICAgIHZhciBtb2R1bGUgPSB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIuZ2V0KHNjb3BlLmlkZW50aWZpZXIpO1xuICAgICAgICBpZiAobW9kdWxlKSB7XG4gICAgICAgICAgICBtb2R1bGUuZGlzY29ubmVjdENvbnRleHRGb3JTY29wZShzY29wZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIC8vIE1vZHVsZXNcbiAgICBSb3V0ZXIucHJvdG90eXBlLmNvbm5lY3RNb2R1bGUgPSBmdW5jdGlvbiAobW9kdWxlKSB7XG4gICAgICAgIHRoaXMubW9kdWxlc0J5SWRlbnRpZmllci5zZXQobW9kdWxlLmlkZW50aWZpZXIsIG1vZHVsZSk7XG4gICAgICAgIHZhciBzY29wZXMgPSB0aGlzLnNjb3Blc0J5SWRlbnRpZmllci5nZXRWYWx1ZXNGb3JLZXkobW9kdWxlLmlkZW50aWZpZXIpO1xuICAgICAgICBzY29wZXMuZm9yRWFjaChmdW5jdGlvbiAoc2NvcGUpIHsgcmV0dXJuIG1vZHVsZS5jb25uZWN0Q29udGV4dEZvclNjb3BlKHNjb3BlKTsgfSk7XG4gICAgfTtcbiAgICBSb3V0ZXIucHJvdG90eXBlLmRpc2Nvbm5lY3RNb2R1bGUgPSBmdW5jdGlvbiAobW9kdWxlKSB7XG4gICAgICAgIHRoaXMubW9kdWxlc0J5SWRlbnRpZmllci5kZWxldGUobW9kdWxlLmlkZW50aWZpZXIpO1xuICAgICAgICB2YXIgc2NvcGVzID0gdGhpcy5zY29wZXNCeUlkZW50aWZpZXIuZ2V0VmFsdWVzRm9yS2V5KG1vZHVsZS5pZGVudGlmaWVyKTtcbiAgICAgICAgc2NvcGVzLmZvckVhY2goZnVuY3Rpb24gKHNjb3BlKSB7IHJldHVybiBtb2R1bGUuZGlzY29ubmVjdENvbnRleHRGb3JTY29wZShzY29wZSk7IH0pO1xuICAgIH07XG4gICAgcmV0dXJuIFJvdXRlcjtcbn0oKSk7XG5leHBvcnQgeyBSb3V0ZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXJvdXRlci5qcy5tYXAiLCJleHBvcnQgdmFyIGRlZmF1bHRTY2hlbWEgPSB7XG4gICAgY29udHJvbGxlckF0dHJpYnV0ZTogXCJkYXRhLWNvbnRyb2xsZXJcIixcbiAgICBhY3Rpb25BdHRyaWJ1dGU6IFwiZGF0YS1hY3Rpb25cIixcbiAgICB0YXJnZXRBdHRyaWJ1dGU6IFwiZGF0YS10YXJnZXRcIlxufTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXNjaGVtYS5qcy5tYXAiLCJ2YXIgX19zcHJlYWRBcnJheXMgPSAodGhpcyAmJiB0aGlzLl9fc3ByZWFkQXJyYXlzKSB8fCBmdW5jdGlvbiAoKSB7XG4gICAgZm9yICh2YXIgcyA9IDAsIGkgPSAwLCBpbCA9IGFyZ3VtZW50cy5sZW5ndGg7IGkgPCBpbDsgaSsrKSBzICs9IGFyZ3VtZW50c1tpXS5sZW5ndGg7XG4gICAgZm9yICh2YXIgciA9IEFycmF5KHMpLCBrID0gMCwgaSA9IDA7IGkgPCBpbDsgaSsrKVxuICAgICAgICBmb3IgKHZhciBhID0gYXJndW1lbnRzW2ldLCBqID0gMCwgamwgPSBhLmxlbmd0aDsgaiA8IGpsOyBqKyssIGsrKylcbiAgICAgICAgICAgIHJba10gPSBhW2pdO1xuICAgIHJldHVybiByO1xufTtcbmltcG9ydCB7IENsYXNzTWFwIH0gZnJvbSBcIi4vY2xhc3NfbWFwXCI7XG5pbXBvcnQgeyBEYXRhTWFwIH0gZnJvbSBcIi4vZGF0YV9tYXBcIjtcbmltcG9ydCB7IEd1aWRlIH0gZnJvbSBcIi4vZ3VpZGVcIjtcbmltcG9ydCB7IGF0dHJpYnV0ZVZhbHVlQ29udGFpbnNUb2tlbiB9IGZyb20gXCIuL3NlbGVjdG9yc1wiO1xuaW1wb3J0IHsgVGFyZ2V0U2V0IH0gZnJvbSBcIi4vdGFyZ2V0X3NldFwiO1xudmFyIFNjb3BlID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIFNjb3BlKHNjaGVtYSwgZWxlbWVudCwgaWRlbnRpZmllciwgbG9nZ2VyKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHRoaXMudGFyZ2V0cyA9IG5ldyBUYXJnZXRTZXQodGhpcyk7XG4gICAgICAgIHRoaXMuY2xhc3NlcyA9IG5ldyBDbGFzc01hcCh0aGlzKTtcbiAgICAgICAgdGhpcy5kYXRhID0gbmV3IERhdGFNYXAodGhpcyk7XG4gICAgICAgIHRoaXMuY29udGFpbnNFbGVtZW50ID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgICAgIHJldHVybiBlbGVtZW50LmNsb3Nlc3QoX3RoaXMuY29udHJvbGxlclNlbGVjdG9yKSA9PT0gX3RoaXMuZWxlbWVudDtcbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5zY2hlbWEgPSBzY2hlbWE7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuaWRlbnRpZmllciA9IGlkZW50aWZpZXI7XG4gICAgICAgIHRoaXMuZ3VpZGUgPSBuZXcgR3VpZGUobG9nZ2VyKTtcbiAgICB9XG4gICAgU2NvcGUucHJvdG90eXBlLmZpbmRFbGVtZW50ID0gZnVuY3Rpb24gKHNlbGVjdG9yKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnQubWF0Y2hlcyhzZWxlY3RvcilcbiAgICAgICAgICAgID8gdGhpcy5lbGVtZW50XG4gICAgICAgICAgICA6IHRoaXMucXVlcnlFbGVtZW50cyhzZWxlY3RvcikuZmluZCh0aGlzLmNvbnRhaW5zRWxlbWVudCk7XG4gICAgfTtcbiAgICBTY29wZS5wcm90b3R5cGUuZmluZEFsbEVsZW1lbnRzID0gZnVuY3Rpb24gKHNlbGVjdG9yKSB7XG4gICAgICAgIHJldHVybiBfX3NwcmVhZEFycmF5cyh0aGlzLmVsZW1lbnQubWF0Y2hlcyhzZWxlY3RvcikgPyBbdGhpcy5lbGVtZW50XSA6IFtdLCB0aGlzLnF1ZXJ5RWxlbWVudHMoc2VsZWN0b3IpLmZpbHRlcih0aGlzLmNvbnRhaW5zRWxlbWVudCkpO1xuICAgIH07XG4gICAgU2NvcGUucHJvdG90eXBlLnF1ZXJ5RWxlbWVudHMgPSBmdW5jdGlvbiAoc2VsZWN0b3IpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5lbGVtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoc2VsZWN0b3IpKTtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShTY29wZS5wcm90b3R5cGUsIFwiY29udHJvbGxlclNlbGVjdG9yXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gYXR0cmlidXRlVmFsdWVDb250YWluc1Rva2VuKHRoaXMuc2NoZW1hLmNvbnRyb2xsZXJBdHRyaWJ1dGUsIHRoaXMuaWRlbnRpZmllcik7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICByZXR1cm4gU2NvcGU7XG59KCkpO1xuZXhwb3J0IHsgU2NvcGUgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXNjb3BlLmpzLm1hcCIsImltcG9ydCB7IFZhbHVlTGlzdE9ic2VydmVyIH0gZnJvbSBcIkBzdGltdWx1cy9tdXRhdGlvbi1vYnNlcnZlcnNcIjtcbnZhciBTY29wZU9ic2VydmVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIFNjb3BlT2JzZXJ2ZXIoZWxlbWVudCwgc2NoZW1hLCBkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBlbGVtZW50O1xuICAgICAgICB0aGlzLnNjaGVtYSA9IHNjaGVtYTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLnZhbHVlTGlzdE9ic2VydmVyID0gbmV3IFZhbHVlTGlzdE9ic2VydmVyKHRoaXMuZWxlbWVudCwgdGhpcy5jb250cm9sbGVyQXR0cmlidXRlLCB0aGlzKTtcbiAgICAgICAgdGhpcy5zY29wZXNCeUlkZW50aWZpZXJCeUVsZW1lbnQgPSBuZXcgV2Vha01hcDtcbiAgICAgICAgdGhpcy5zY29wZVJlZmVyZW5jZUNvdW50cyA9IG5ldyBXZWFrTWFwO1xuICAgIH1cbiAgICBTY29wZU9ic2VydmVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy52YWx1ZUxpc3RPYnNlcnZlci5zdGFydCgpO1xuICAgIH07XG4gICAgU2NvcGVPYnNlcnZlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy52YWx1ZUxpc3RPYnNlcnZlci5zdG9wKCk7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoU2NvcGVPYnNlcnZlci5wcm90b3R5cGUsIFwiY29udHJvbGxlckF0dHJpYnV0ZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NoZW1hLmNvbnRyb2xsZXJBdHRyaWJ1dGU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICAvLyBWYWx1ZSBvYnNlcnZlciBkZWxlZ2F0ZVxuICAgIC8qKiBAaGlkZGVuICovXG4gICAgU2NvcGVPYnNlcnZlci5wcm90b3R5cGUucGFyc2VWYWx1ZUZvclRva2VuID0gZnVuY3Rpb24gKHRva2VuKSB7XG4gICAgICAgIHZhciBlbGVtZW50ID0gdG9rZW4uZWxlbWVudCwgaWRlbnRpZmllciA9IHRva2VuLmNvbnRlbnQ7XG4gICAgICAgIHZhciBzY29wZXNCeUlkZW50aWZpZXIgPSB0aGlzLmZldGNoU2NvcGVzQnlJZGVudGlmaWVyRm9yRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgdmFyIHNjb3BlID0gc2NvcGVzQnlJZGVudGlmaWVyLmdldChpZGVudGlmaWVyKTtcbiAgICAgICAgaWYgKCFzY29wZSkge1xuICAgICAgICAgICAgc2NvcGUgPSB0aGlzLmRlbGVnYXRlLmNyZWF0ZVNjb3BlRm9yRWxlbWVudEFuZElkZW50aWZpZXIoZWxlbWVudCwgaWRlbnRpZmllcik7XG4gICAgICAgICAgICBzY29wZXNCeUlkZW50aWZpZXIuc2V0KGlkZW50aWZpZXIsIHNjb3BlKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gc2NvcGU7XG4gICAgfTtcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIFNjb3BlT2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRNYXRjaGVkVmFsdWUgPSBmdW5jdGlvbiAoZWxlbWVudCwgdmFsdWUpIHtcbiAgICAgICAgdmFyIHJlZmVyZW5jZUNvdW50ID0gKHRoaXMuc2NvcGVSZWZlcmVuY2VDb3VudHMuZ2V0KHZhbHVlKSB8fCAwKSArIDE7XG4gICAgICAgIHRoaXMuc2NvcGVSZWZlcmVuY2VDb3VudHMuc2V0KHZhbHVlLCByZWZlcmVuY2VDb3VudCk7XG4gICAgICAgIGlmIChyZWZlcmVuY2VDb3VudCA9PSAxKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnNjb3BlQ29ubmVjdGVkKHZhbHVlKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBTY29wZU9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50VW5tYXRjaGVkVmFsdWUgPSBmdW5jdGlvbiAoZWxlbWVudCwgdmFsdWUpIHtcbiAgICAgICAgdmFyIHJlZmVyZW5jZUNvdW50ID0gdGhpcy5zY29wZVJlZmVyZW5jZUNvdW50cy5nZXQodmFsdWUpO1xuICAgICAgICBpZiAocmVmZXJlbmNlQ291bnQpIHtcbiAgICAgICAgICAgIHRoaXMuc2NvcGVSZWZlcmVuY2VDb3VudHMuc2V0KHZhbHVlLCByZWZlcmVuY2VDb3VudCAtIDEpO1xuICAgICAgICAgICAgaWYgKHJlZmVyZW5jZUNvdW50ID09IDEpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnNjb3BlRGlzY29ubmVjdGVkKHZhbHVlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgU2NvcGVPYnNlcnZlci5wcm90b3R5cGUuZmV0Y2hTY29wZXNCeUlkZW50aWZpZXJGb3JFbGVtZW50ID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgdmFyIHNjb3Blc0J5SWRlbnRpZmllciA9IHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyQnlFbGVtZW50LmdldChlbGVtZW50KTtcbiAgICAgICAgaWYgKCFzY29wZXNCeUlkZW50aWZpZXIpIHtcbiAgICAgICAgICAgIHNjb3Blc0J5SWRlbnRpZmllciA9IG5ldyBNYXA7XG4gICAgICAgICAgICB0aGlzLnNjb3Blc0J5SWRlbnRpZmllckJ5RWxlbWVudC5zZXQoZWxlbWVudCwgc2NvcGVzQnlJZGVudGlmaWVyKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gc2NvcGVzQnlJZGVudGlmaWVyO1xuICAgIH07XG4gICAgcmV0dXJuIFNjb3BlT2JzZXJ2ZXI7XG59KCkpO1xuZXhwb3J0IHsgU2NvcGVPYnNlcnZlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9c2NvcGVfb2JzZXJ2ZXIuanMubWFwIiwiLyoqIEBoaWRkZW4gKi9cbmV4cG9ydCBmdW5jdGlvbiBhdHRyaWJ1dGVWYWx1ZUNvbnRhaW5zVG9rZW4oYXR0cmlidXRlTmFtZSwgdG9rZW4pIHtcbiAgICByZXR1cm4gXCJbXCIgKyBhdHRyaWJ1dGVOYW1lICsgXCJ+PVxcXCJcIiArIHRva2VuICsgXCJcXFwiXVwiO1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9c2VsZWN0b3JzLmpzLm1hcCIsImV4cG9ydCBmdW5jdGlvbiBjYW1lbGl6ZSh2YWx1ZSkge1xuICAgIHJldHVybiB2YWx1ZS5yZXBsYWNlKC8oPzpbXy1dKShbYS16MC05XSkvZywgZnVuY3Rpb24gKF8sIGNoYXIpIHsgcmV0dXJuIGNoYXIudG9VcHBlckNhc2UoKTsgfSk7XG59XG5leHBvcnQgZnVuY3Rpb24gY2FwaXRhbGl6ZSh2YWx1ZSkge1xuICAgIHJldHVybiB2YWx1ZS5jaGFyQXQoMCkudG9VcHBlckNhc2UoKSArIHZhbHVlLnNsaWNlKDEpO1xufVxuZXhwb3J0IGZ1bmN0aW9uIGRhc2hlcml6ZSh2YWx1ZSkge1xuICAgIHJldHVybiB2YWx1ZS5yZXBsYWNlKC8oW0EtWl0pL2csIGZ1bmN0aW9uIChfLCBjaGFyKSB7IHJldHVybiBcIi1cIiArIGNoYXIudG9Mb3dlckNhc2UoKTsgfSk7XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1zdHJpbmdfaGVscGVycy5qcy5tYXAiLCJpbXBvcnQgeyByZWFkSW5oZXJpdGFibGVTdGF0aWNBcnJheVZhbHVlcyB9IGZyb20gXCIuL2luaGVyaXRhYmxlX3N0YXRpY3NcIjtcbmltcG9ydCB7IGNhcGl0YWxpemUgfSBmcm9tIFwiLi9zdHJpbmdfaGVscGVyc1wiO1xuLyoqIEBoaWRkZW4gKi9cbmV4cG9ydCBmdW5jdGlvbiBUYXJnZXRQcm9wZXJ0aWVzQmxlc3NpbmcoY29uc3RydWN0b3IpIHtcbiAgICB2YXIgdGFyZ2V0cyA9IHJlYWRJbmhlcml0YWJsZVN0YXRpY0FycmF5VmFsdWVzKGNvbnN0cnVjdG9yLCBcInRhcmdldHNcIik7XG4gICAgcmV0dXJuIHRhcmdldHMucmVkdWNlKGZ1bmN0aW9uIChwcm9wZXJ0aWVzLCB0YXJnZXREZWZpbml0aW9uKSB7XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKHByb3BlcnRpZXMsIHByb3BlcnRpZXNGb3JUYXJnZXREZWZpbml0aW9uKHRhcmdldERlZmluaXRpb24pKTtcbiAgICB9LCB7fSk7XG59XG5mdW5jdGlvbiBwcm9wZXJ0aWVzRm9yVGFyZ2V0RGVmaW5pdGlvbihuYW1lKSB7XG4gICAgdmFyIF9hO1xuICAgIHJldHVybiBfYSA9IHt9LFxuICAgICAgICBfYVtuYW1lICsgXCJUYXJnZXRcIl0gPSB7XG4gICAgICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB2YXIgdGFyZ2V0ID0gdGhpcy50YXJnZXRzLmZpbmQobmFtZSk7XG4gICAgICAgICAgICAgICAgaWYgKHRhcmdldCkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gdGFyZ2V0O1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKFwiTWlzc2luZyB0YXJnZXQgZWxlbWVudCBcXFwiXCIgKyB0aGlzLmlkZW50aWZpZXIgKyBcIi5cIiArIG5hbWUgKyBcIlxcXCJcIik7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBfYVtuYW1lICsgXCJUYXJnZXRzXCJdID0ge1xuICAgICAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMudGFyZ2V0cy5maW5kQWxsKG5hbWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBfYVtcImhhc1wiICsgY2FwaXRhbGl6ZShuYW1lKSArIFwiVGFyZ2V0XCJdID0ge1xuICAgICAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMudGFyZ2V0cy5oYXMobmFtZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIF9hO1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9dGFyZ2V0X3Byb3BlcnRpZXMuanMubWFwIiwidmFyIF9fc3ByZWFkQXJyYXlzID0gKHRoaXMgJiYgdGhpcy5fX3NwcmVhZEFycmF5cykgfHwgZnVuY3Rpb24gKCkge1xuICAgIGZvciAodmFyIHMgPSAwLCBpID0gMCwgaWwgPSBhcmd1bWVudHMubGVuZ3RoOyBpIDwgaWw7IGkrKykgcyArPSBhcmd1bWVudHNbaV0ubGVuZ3RoO1xuICAgIGZvciAodmFyIHIgPSBBcnJheShzKSwgayA9IDAsIGkgPSAwOyBpIDwgaWw7IGkrKylcbiAgICAgICAgZm9yICh2YXIgYSA9IGFyZ3VtZW50c1tpXSwgaiA9IDAsIGpsID0gYS5sZW5ndGg7IGogPCBqbDsgaisrLCBrKyspXG4gICAgICAgICAgICByW2tdID0gYVtqXTtcbiAgICByZXR1cm4gcjtcbn07XG5pbXBvcnQgeyBhdHRyaWJ1dGVWYWx1ZUNvbnRhaW5zVG9rZW4gfSBmcm9tIFwiLi9zZWxlY3RvcnNcIjtcbnZhciBUYXJnZXRTZXQgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gVGFyZ2V0U2V0KHNjb3BlKSB7XG4gICAgICAgIHRoaXMuc2NvcGUgPSBzY29wZTtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFRhcmdldFNldC5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShUYXJnZXRTZXQucHJvdG90eXBlLCBcImlkZW50aWZpZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmlkZW50aWZpZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVGFyZ2V0U2V0LnByb3RvdHlwZSwgXCJzY2hlbWFcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLnNjaGVtYTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuaGFzID0gZnVuY3Rpb24gKHRhcmdldE5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZmluZCh0YXJnZXROYW1lKSAhPSBudWxsO1xuICAgIH07XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5maW5kID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB2YXIgdGFyZ2V0TmFtZXMgPSBbXTtcbiAgICAgICAgZm9yICh2YXIgX2kgPSAwOyBfaSA8IGFyZ3VtZW50cy5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgIHRhcmdldE5hbWVzW19pXSA9IGFyZ3VtZW50c1tfaV07XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHRhcmdldE5hbWVzLnJlZHVjZShmdW5jdGlvbiAodGFyZ2V0LCB0YXJnZXROYW1lKSB7XG4gICAgICAgICAgICByZXR1cm4gdGFyZ2V0XG4gICAgICAgICAgICAgICAgfHwgX3RoaXMuZmluZFRhcmdldCh0YXJnZXROYW1lKVxuICAgICAgICAgICAgICAgIHx8IF90aGlzLmZpbmRMZWdhY3lUYXJnZXQodGFyZ2V0TmFtZSk7XG4gICAgICAgIH0sIHVuZGVmaW5lZCk7XG4gICAgfTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmZpbmRBbGwgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHZhciB0YXJnZXROYW1lcyA9IFtdO1xuICAgICAgICBmb3IgKHZhciBfaSA9IDA7IF9pIDwgYXJndW1lbnRzLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgdGFyZ2V0TmFtZXNbX2ldID0gYXJndW1lbnRzW19pXTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gdGFyZ2V0TmFtZXMucmVkdWNlKGZ1bmN0aW9uICh0YXJnZXRzLCB0YXJnZXROYW1lKSB7IHJldHVybiBfX3NwcmVhZEFycmF5cyh0YXJnZXRzLCBfdGhpcy5maW5kQWxsVGFyZ2V0cyh0YXJnZXROYW1lKSwgX3RoaXMuZmluZEFsbExlZ2FjeVRhcmdldHModGFyZ2V0TmFtZSkpOyB9LCBbXSk7XG4gICAgfTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmZpbmRUYXJnZXQgPSBmdW5jdGlvbiAodGFyZ2V0TmFtZSkge1xuICAgICAgICB2YXIgc2VsZWN0b3IgPSB0aGlzLmdldFNlbGVjdG9yRm9yVGFyZ2V0TmFtZSh0YXJnZXROYW1lKTtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZmluZEVsZW1lbnQoc2VsZWN0b3IpO1xuICAgIH07XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5maW5kQWxsVGFyZ2V0cyA9IGZ1bmN0aW9uICh0YXJnZXROYW1lKSB7XG4gICAgICAgIHZhciBzZWxlY3RvciA9IHRoaXMuZ2V0U2VsZWN0b3JGb3JUYXJnZXROYW1lKHRhcmdldE5hbWUpO1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5maW5kQWxsRWxlbWVudHMoc2VsZWN0b3IpO1xuICAgIH07XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5nZXRTZWxlY3RvckZvclRhcmdldE5hbWUgPSBmdW5jdGlvbiAodGFyZ2V0TmFtZSkge1xuICAgICAgICB2YXIgYXR0cmlidXRlTmFtZSA9IFwiZGF0YS1cIiArIHRoaXMuaWRlbnRpZmllciArIFwiLXRhcmdldFwiO1xuICAgICAgICByZXR1cm4gYXR0cmlidXRlVmFsdWVDb250YWluc1Rva2VuKGF0dHJpYnV0ZU5hbWUsIHRhcmdldE5hbWUpO1xuICAgIH07XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5maW5kTGVnYWN5VGFyZ2V0ID0gZnVuY3Rpb24gKHRhcmdldE5hbWUpIHtcbiAgICAgICAgdmFyIHNlbGVjdG9yID0gdGhpcy5nZXRMZWdhY3lTZWxlY3RvckZvclRhcmdldE5hbWUodGFyZ2V0TmFtZSk7XG4gICAgICAgIHJldHVybiB0aGlzLmRlcHJlY2F0ZSh0aGlzLnNjb3BlLmZpbmRFbGVtZW50KHNlbGVjdG9yKSwgdGFyZ2V0TmFtZSk7XG4gICAgfTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmZpbmRBbGxMZWdhY3lUYXJnZXRzID0gZnVuY3Rpb24gKHRhcmdldE5hbWUpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdmFyIHNlbGVjdG9yID0gdGhpcy5nZXRMZWdhY3lTZWxlY3RvckZvclRhcmdldE5hbWUodGFyZ2V0TmFtZSk7XG4gICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmZpbmRBbGxFbGVtZW50cyhzZWxlY3RvcikubWFwKGZ1bmN0aW9uIChlbGVtZW50KSB7IHJldHVybiBfdGhpcy5kZXByZWNhdGUoZWxlbWVudCwgdGFyZ2V0TmFtZSk7IH0pO1xuICAgIH07XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5nZXRMZWdhY3lTZWxlY3RvckZvclRhcmdldE5hbWUgPSBmdW5jdGlvbiAodGFyZ2V0TmFtZSkge1xuICAgICAgICB2YXIgdGFyZ2V0RGVzY3JpcHRvciA9IHRoaXMuaWRlbnRpZmllciArIFwiLlwiICsgdGFyZ2V0TmFtZTtcbiAgICAgICAgcmV0dXJuIGF0dHJpYnV0ZVZhbHVlQ29udGFpbnNUb2tlbih0aGlzLnNjaGVtYS50YXJnZXRBdHRyaWJ1dGUsIHRhcmdldERlc2NyaXB0b3IpO1xuICAgIH07XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5kZXByZWNhdGUgPSBmdW5jdGlvbiAoZWxlbWVudCwgdGFyZ2V0TmFtZSkge1xuICAgICAgICBpZiAoZWxlbWVudCkge1xuICAgICAgICAgICAgdmFyIGlkZW50aWZpZXIgPSB0aGlzLmlkZW50aWZpZXI7XG4gICAgICAgICAgICB2YXIgYXR0cmlidXRlTmFtZSA9IHRoaXMuc2NoZW1hLnRhcmdldEF0dHJpYnV0ZTtcbiAgICAgICAgICAgIHRoaXMuZ3VpZGUud2FybihlbGVtZW50LCBcInRhcmdldDpcIiArIHRhcmdldE5hbWUsIFwiUGxlYXNlIHJlcGxhY2UgXCIgKyBhdHRyaWJ1dGVOYW1lICsgXCI9XFxcIlwiICsgaWRlbnRpZmllciArIFwiLlwiICsgdGFyZ2V0TmFtZSArIFwiXFxcIiB3aXRoIGRhdGEtXCIgKyBpZGVudGlmaWVyICsgXCItdGFyZ2V0PVxcXCJcIiArIHRhcmdldE5hbWUgKyBcIlxcXCIuIFwiICtcbiAgICAgICAgICAgICAgICAoXCJUaGUgXCIgKyBhdHRyaWJ1dGVOYW1lICsgXCIgYXR0cmlidXRlIGlzIGRlcHJlY2F0ZWQgYW5kIHdpbGwgYmUgcmVtb3ZlZCBpbiBhIGZ1dHVyZSB2ZXJzaW9uIG9mIFN0aW11bHVzLlwiKSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGVsZW1lbnQ7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVGFyZ2V0U2V0LnByb3RvdHlwZSwgXCJndWlkZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZ3VpZGU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICByZXR1cm4gVGFyZ2V0U2V0O1xufSgpKTtcbmV4cG9ydCB7IFRhcmdldFNldCB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9dGFyZ2V0X3NldC5qcy5tYXAiLCJpbXBvcnQgeyBTdHJpbmdNYXBPYnNlcnZlciB9IGZyb20gXCJAc3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzXCI7XG52YXIgVmFsdWVPYnNlcnZlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBWYWx1ZU9ic2VydmVyKGNvbnRleHQsIHJlY2VpdmVyKSB7XG4gICAgICAgIHRoaXMuY29udGV4dCA9IGNvbnRleHQ7XG4gICAgICAgIHRoaXMucmVjZWl2ZXIgPSByZWNlaXZlcjtcbiAgICAgICAgdGhpcy5zdHJpbmdNYXBPYnNlcnZlciA9IG5ldyBTdHJpbmdNYXBPYnNlcnZlcih0aGlzLmVsZW1lbnQsIHRoaXMpO1xuICAgICAgICB0aGlzLnZhbHVlRGVzY3JpcHRvck1hcCA9IHRoaXMuY29udHJvbGxlci52YWx1ZURlc2NyaXB0b3JNYXA7XG4gICAgICAgIHRoaXMuaW52b2tlQ2hhbmdlZENhbGxiYWNrc0ZvckRlZmF1bHRWYWx1ZXMoKTtcbiAgICB9XG4gICAgVmFsdWVPYnNlcnZlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuc3RyaW5nTWFwT2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICB9O1xuICAgIFZhbHVlT2JzZXJ2ZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuc3RyaW5nTWFwT2JzZXJ2ZXIuc3RvcCgpO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFZhbHVlT2JzZXJ2ZXIucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShWYWx1ZU9ic2VydmVyLnByb3RvdHlwZSwgXCJjb250cm9sbGVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmNvbnRyb2xsZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICAvLyBTdHJpbmcgbWFwIG9ic2VydmVyIGRlbGVnYXRlXG4gICAgVmFsdWVPYnNlcnZlci5wcm90b3R5cGUuZ2V0U3RyaW5nTWFwS2V5Rm9yQXR0cmlidXRlID0gZnVuY3Rpb24gKGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgaWYgKGF0dHJpYnV0ZU5hbWUgaW4gdGhpcy52YWx1ZURlc2NyaXB0b3JNYXApIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnZhbHVlRGVzY3JpcHRvck1hcFthdHRyaWJ1dGVOYW1lXS5uYW1lO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBWYWx1ZU9ic2VydmVyLnByb3RvdHlwZS5zdHJpbmdNYXBWYWx1ZUNoYW5nZWQgPSBmdW5jdGlvbiAoYXR0cmlidXRlVmFsdWUsIG5hbWUpIHtcbiAgICAgICAgdGhpcy5pbnZva2VDaGFuZ2VkQ2FsbGJhY2tGb3JWYWx1ZShuYW1lKTtcbiAgICB9O1xuICAgIFZhbHVlT2JzZXJ2ZXIucHJvdG90eXBlLmludm9rZUNoYW5nZWRDYWxsYmFja3NGb3JEZWZhdWx0VmFsdWVzID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBmb3IgKHZhciBfaSA9IDAsIF9hID0gdGhpcy52YWx1ZURlc2NyaXB0b3JzOyBfaSA8IF9hLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgdmFyIF9iID0gX2FbX2ldLCBrZXkgPSBfYi5rZXksIG5hbWVfMSA9IF9iLm5hbWUsIGRlZmF1bHRWYWx1ZSA9IF9iLmRlZmF1bHRWYWx1ZTtcbiAgICAgICAgICAgIGlmIChkZWZhdWx0VmFsdWUgIT0gdW5kZWZpbmVkICYmICF0aGlzLmNvbnRyb2xsZXIuZGF0YS5oYXMoa2V5KSkge1xuICAgICAgICAgICAgICAgIHRoaXMuaW52b2tlQ2hhbmdlZENhbGxiYWNrRm9yVmFsdWUobmFtZV8xKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgVmFsdWVPYnNlcnZlci5wcm90b3R5cGUuaW52b2tlQ2hhbmdlZENhbGxiYWNrRm9yVmFsdWUgPSBmdW5jdGlvbiAobmFtZSkge1xuICAgICAgICB2YXIgbWV0aG9kTmFtZSA9IG5hbWUgKyBcIkNoYW5nZWRcIjtcbiAgICAgICAgdmFyIG1ldGhvZCA9IHRoaXMucmVjZWl2ZXJbbWV0aG9kTmFtZV07XG4gICAgICAgIGlmICh0eXBlb2YgbWV0aG9kID09IFwiZnVuY3Rpb25cIikge1xuICAgICAgICAgICAgdmFyIHZhbHVlID0gdGhpcy5yZWNlaXZlcltuYW1lXTtcbiAgICAgICAgICAgIG1ldGhvZC5jYWxsKHRoaXMucmVjZWl2ZXIsIHZhbHVlKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFZhbHVlT2JzZXJ2ZXIucHJvdG90eXBlLCBcInZhbHVlRGVzY3JpcHRvcnNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHZhciB2YWx1ZURlc2NyaXB0b3JNYXAgPSB0aGlzLnZhbHVlRGVzY3JpcHRvck1hcDtcbiAgICAgICAgICAgIHJldHVybiBPYmplY3Qua2V5cyh2YWx1ZURlc2NyaXB0b3JNYXApLm1hcChmdW5jdGlvbiAoa2V5KSB7IHJldHVybiB2YWx1ZURlc2NyaXB0b3JNYXBba2V5XTsgfSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICByZXR1cm4gVmFsdWVPYnNlcnZlcjtcbn0oKSk7XG5leHBvcnQgeyBWYWx1ZU9ic2VydmVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD12YWx1ZV9vYnNlcnZlci5qcy5tYXAiLCJpbXBvcnQgeyByZWFkSW5oZXJpdGFibGVTdGF0aWNPYmplY3RQYWlycyB9IGZyb20gXCIuL2luaGVyaXRhYmxlX3N0YXRpY3NcIjtcbmltcG9ydCB7IGNhbWVsaXplLCBjYXBpdGFsaXplLCBkYXNoZXJpemUgfSBmcm9tIFwiLi9zdHJpbmdfaGVscGVyc1wiO1xuLyoqIEBoaWRkZW4gKi9cbmV4cG9ydCBmdW5jdGlvbiBWYWx1ZVByb3BlcnRpZXNCbGVzc2luZyhjb25zdHJ1Y3Rvcikge1xuICAgIHZhciB2YWx1ZURlZmluaXRpb25QYWlycyA9IHJlYWRJbmhlcml0YWJsZVN0YXRpY09iamVjdFBhaXJzKGNvbnN0cnVjdG9yLCBcInZhbHVlc1wiKTtcbiAgICB2YXIgcHJvcGVydHlEZXNjcmlwdG9yTWFwID0ge1xuICAgICAgICB2YWx1ZURlc2NyaXB0b3JNYXA6IHtcbiAgICAgICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHZhbHVlRGVmaW5pdGlvblBhaXJzLnJlZHVjZShmdW5jdGlvbiAocmVzdWx0LCB2YWx1ZURlZmluaXRpb25QYWlyKSB7XG4gICAgICAgICAgICAgICAgICAgIHZhciBfYTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIHZhbHVlRGVzY3JpcHRvciA9IHBhcnNlVmFsdWVEZWZpbml0aW9uUGFpcih2YWx1ZURlZmluaXRpb25QYWlyKTtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGF0dHJpYnV0ZU5hbWUgPSBfdGhpcy5kYXRhLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkodmFsdWVEZXNjcmlwdG9yLmtleSk7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKHJlc3VsdCwgKF9hID0ge30sIF9hW2F0dHJpYnV0ZU5hbWVdID0gdmFsdWVEZXNjcmlwdG9yLCBfYSkpO1xuICAgICAgICAgICAgICAgIH0sIHt9KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgcmV0dXJuIHZhbHVlRGVmaW5pdGlvblBhaXJzLnJlZHVjZShmdW5jdGlvbiAocHJvcGVydGllcywgdmFsdWVEZWZpbml0aW9uUGFpcikge1xuICAgICAgICByZXR1cm4gT2JqZWN0LmFzc2lnbihwcm9wZXJ0aWVzLCBwcm9wZXJ0aWVzRm9yVmFsdWVEZWZpbml0aW9uUGFpcih2YWx1ZURlZmluaXRpb25QYWlyKSk7XG4gICAgfSwgcHJvcGVydHlEZXNjcmlwdG9yTWFwKTtcbn1cbi8qKiBAaGlkZGVuICovXG5leHBvcnQgZnVuY3Rpb24gcHJvcGVydGllc0ZvclZhbHVlRGVmaW5pdGlvblBhaXIodmFsdWVEZWZpbml0aW9uUGFpcikge1xuICAgIHZhciBfYTtcbiAgICB2YXIgZGVmaW5pdGlvbiA9IHBhcnNlVmFsdWVEZWZpbml0aW9uUGFpcih2YWx1ZURlZmluaXRpb25QYWlyKTtcbiAgICB2YXIgdHlwZSA9IGRlZmluaXRpb24udHlwZSwga2V5ID0gZGVmaW5pdGlvbi5rZXksIG5hbWUgPSBkZWZpbml0aW9uLm5hbWU7XG4gICAgdmFyIHJlYWQgPSByZWFkZXJzW3R5cGVdLCB3cml0ZSA9IHdyaXRlcnNbdHlwZV0gfHwgd3JpdGVycy5kZWZhdWx0O1xuICAgIHJldHVybiBfYSA9IHt9LFxuICAgICAgICBfYVtuYW1lXSA9IHtcbiAgICAgICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHZhciB2YWx1ZSA9IHRoaXMuZGF0YS5nZXQoa2V5KTtcbiAgICAgICAgICAgICAgICBpZiAodmFsdWUgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHJlYWQodmFsdWUpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGRlZmluaXRpb24uZGVmYXVsdFZhbHVlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBzZXQ6IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICAgICAgICAgIGlmICh2YWx1ZSA9PT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZGF0YS5kZWxldGUoa2V5KTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZGF0YS5zZXQoa2V5LCB3cml0ZSh2YWx1ZSkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgX2FbXCJoYXNcIiArIGNhcGl0YWxpemUobmFtZSldID0ge1xuICAgICAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMuZGF0YS5oYXMoa2V5KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgX2E7XG59XG5mdW5jdGlvbiBwYXJzZVZhbHVlRGVmaW5pdGlvblBhaXIoX2EpIHtcbiAgICB2YXIgdG9rZW4gPSBfYVswXSwgdHlwZUNvbnN0YW50ID0gX2FbMV07XG4gICAgdmFyIHR5cGUgPSBwYXJzZVZhbHVlVHlwZUNvbnN0YW50KHR5cGVDb25zdGFudCk7XG4gICAgcmV0dXJuIHZhbHVlRGVzY3JpcHRvckZvclRva2VuQW5kVHlwZSh0b2tlbiwgdHlwZSk7XG59XG5mdW5jdGlvbiBwYXJzZVZhbHVlVHlwZUNvbnN0YW50KHR5cGVDb25zdGFudCkge1xuICAgIHN3aXRjaCAodHlwZUNvbnN0YW50KSB7XG4gICAgICAgIGNhc2UgQXJyYXk6IHJldHVybiBcImFycmF5XCI7XG4gICAgICAgIGNhc2UgQm9vbGVhbjogcmV0dXJuIFwiYm9vbGVhblwiO1xuICAgICAgICBjYXNlIE51bWJlcjogcmV0dXJuIFwibnVtYmVyXCI7XG4gICAgICAgIGNhc2UgT2JqZWN0OiByZXR1cm4gXCJvYmplY3RcIjtcbiAgICAgICAgY2FzZSBTdHJpbmc6IHJldHVybiBcInN0cmluZ1wiO1xuICAgIH1cbiAgICB0aHJvdyBuZXcgRXJyb3IoXCJVbmtub3duIHZhbHVlIHR5cGUgY29uc3RhbnQgXFxcIlwiICsgdHlwZUNvbnN0YW50ICsgXCJcXFwiXCIpO1xufVxuZnVuY3Rpb24gdmFsdWVEZXNjcmlwdG9yRm9yVG9rZW5BbmRUeXBlKHRva2VuLCB0eXBlKSB7XG4gICAgdmFyIGtleSA9IGRhc2hlcml6ZSh0b2tlbikgKyBcIi12YWx1ZVwiO1xuICAgIHJldHVybiB7XG4gICAgICAgIHR5cGU6IHR5cGUsXG4gICAgICAgIGtleToga2V5LFxuICAgICAgICBuYW1lOiBjYW1lbGl6ZShrZXkpLFxuICAgICAgICBnZXQgZGVmYXVsdFZhbHVlKCkgeyByZXR1cm4gZGVmYXVsdFZhbHVlc0J5VHlwZVt0eXBlXTsgfVxuICAgIH07XG59XG52YXIgZGVmYXVsdFZhbHVlc0J5VHlwZSA9IHtcbiAgICBnZXQgYXJyYXkoKSB7IHJldHVybiBbXTsgfSxcbiAgICBib29sZWFuOiBmYWxzZSxcbiAgICBudW1iZXI6IDAsXG4gICAgZ2V0IG9iamVjdCgpIHsgcmV0dXJuIHt9OyB9LFxuICAgIHN0cmluZzogXCJcIlxufTtcbnZhciByZWFkZXJzID0ge1xuICAgIGFycmF5OiBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgdmFyIGFycmF5ID0gSlNPTi5wYXJzZSh2YWx1ZSk7XG4gICAgICAgIGlmICghQXJyYXkuaXNBcnJheShhcnJheSkpIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBUeXBlRXJyb3IoXCJFeHBlY3RlZCBhcnJheVwiKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gYXJyYXk7XG4gICAgfSxcbiAgICBib29sZWFuOiBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgcmV0dXJuICEodmFsdWUgPT0gXCIwXCIgfHwgdmFsdWUgPT0gXCJmYWxzZVwiKTtcbiAgICB9LFxuICAgIG51bWJlcjogZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgIHJldHVybiBwYXJzZUZsb2F0KHZhbHVlKTtcbiAgICB9LFxuICAgIG9iamVjdDogZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgIHZhciBvYmplY3QgPSBKU09OLnBhcnNlKHZhbHVlKTtcbiAgICAgICAgaWYgKG9iamVjdCA9PT0gbnVsbCB8fCB0eXBlb2Ygb2JqZWN0ICE9IFwib2JqZWN0XCIgfHwgQXJyYXkuaXNBcnJheShvYmplY3QpKSB7XG4gICAgICAgICAgICB0aHJvdyBuZXcgVHlwZUVycm9yKFwiRXhwZWN0ZWQgb2JqZWN0XCIpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBvYmplY3Q7XG4gICAgfSxcbiAgICBzdHJpbmc6IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICByZXR1cm4gdmFsdWU7XG4gICAgfVxufTtcbnZhciB3cml0ZXJzID0ge1xuICAgIGRlZmF1bHQ6IHdyaXRlU3RyaW5nLFxuICAgIGFycmF5OiB3cml0ZUpTT04sXG4gICAgb2JqZWN0OiB3cml0ZUpTT05cbn07XG5mdW5jdGlvbiB3cml0ZUpTT04odmFsdWUpIHtcbiAgICByZXR1cm4gSlNPTi5zdHJpbmdpZnkodmFsdWUpO1xufVxuZnVuY3Rpb24gd3JpdGVTdHJpbmcodmFsdWUpIHtcbiAgICByZXR1cm4gXCJcIiArIHZhbHVlO1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9dmFsdWVfcHJvcGVydGllcy5qcy5tYXAiLCJleHBvcnQgKiBmcm9tIFwiLi9pbmRleGVkX211bHRpbWFwXCI7XG5leHBvcnQgKiBmcm9tIFwiLi9tdWx0aW1hcFwiO1xuZXhwb3J0ICogZnJvbSBcIi4vc2V0X29wZXJhdGlvbnNcIjtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWluZGV4LmpzLm1hcCIsInZhciBfX2V4dGVuZHMgPSAodGhpcyAmJiB0aGlzLl9fZXh0ZW5kcykgfHwgKGZ1bmN0aW9uICgpIHtcbiAgICB2YXIgZXh0ZW5kU3RhdGljcyA9IGZ1bmN0aW9uIChkLCBiKSB7XG4gICAgICAgIGV4dGVuZFN0YXRpY3MgPSBPYmplY3Quc2V0UHJvdG90eXBlT2YgfHxcbiAgICAgICAgICAgICh7IF9fcHJvdG9fXzogW10gfSBpbnN0YW5jZW9mIEFycmF5ICYmIGZ1bmN0aW9uIChkLCBiKSB7IGQuX19wcm90b19fID0gYjsgfSkgfHxcbiAgICAgICAgICAgIGZ1bmN0aW9uIChkLCBiKSB7IGZvciAodmFyIHAgaW4gYikgaWYgKGIuaGFzT3duUHJvcGVydHkocCkpIGRbcF0gPSBiW3BdOyB9O1xuICAgICAgICByZXR1cm4gZXh0ZW5kU3RhdGljcyhkLCBiKTtcbiAgICB9O1xuICAgIHJldHVybiBmdW5jdGlvbiAoZCwgYikge1xuICAgICAgICBleHRlbmRTdGF0aWNzKGQsIGIpO1xuICAgICAgICBmdW5jdGlvbiBfXygpIHsgdGhpcy5jb25zdHJ1Y3RvciA9IGQ7IH1cbiAgICAgICAgZC5wcm90b3R5cGUgPSBiID09PSBudWxsID8gT2JqZWN0LmNyZWF0ZShiKSA6IChfXy5wcm90b3R5cGUgPSBiLnByb3RvdHlwZSwgbmV3IF9fKCkpO1xuICAgIH07XG59KSgpO1xuaW1wb3J0IHsgTXVsdGltYXAgfSBmcm9tIFwiLi9tdWx0aW1hcFwiO1xuaW1wb3J0IHsgYWRkLCBkZWwgfSBmcm9tIFwiLi9zZXRfb3BlcmF0aW9uc1wiO1xudmFyIEluZGV4ZWRNdWx0aW1hcCA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uIChfc3VwZXIpIHtcbiAgICBfX2V4dGVuZHMoSW5kZXhlZE11bHRpbWFwLCBfc3VwZXIpO1xuICAgIGZ1bmN0aW9uIEluZGV4ZWRNdWx0aW1hcCgpIHtcbiAgICAgICAgdmFyIF90aGlzID0gX3N1cGVyLmNhbGwodGhpcykgfHwgdGhpcztcbiAgICAgICAgX3RoaXMua2V5c0J5VmFsdWUgPSBuZXcgTWFwO1xuICAgICAgICByZXR1cm4gX3RoaXM7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShJbmRleGVkTXVsdGltYXAucHJvdG90eXBlLCBcInZhbHVlc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5rZXlzQnlWYWx1ZS5rZXlzKCkpO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgSW5kZXhlZE11bHRpbWFwLnByb3RvdHlwZS5hZGQgPSBmdW5jdGlvbiAoa2V5LCB2YWx1ZSkge1xuICAgICAgICBfc3VwZXIucHJvdG90eXBlLmFkZC5jYWxsKHRoaXMsIGtleSwgdmFsdWUpO1xuICAgICAgICBhZGQodGhpcy5rZXlzQnlWYWx1ZSwgdmFsdWUsIGtleSk7XG4gICAgfTtcbiAgICBJbmRleGVkTXVsdGltYXAucHJvdG90eXBlLmRlbGV0ZSA9IGZ1bmN0aW9uIChrZXksIHZhbHVlKSB7XG4gICAgICAgIF9zdXBlci5wcm90b3R5cGUuZGVsZXRlLmNhbGwodGhpcywga2V5LCB2YWx1ZSk7XG4gICAgICAgIGRlbCh0aGlzLmtleXNCeVZhbHVlLCB2YWx1ZSwga2V5KTtcbiAgICB9O1xuICAgIEluZGV4ZWRNdWx0aW1hcC5wcm90b3R5cGUuaGFzVmFsdWUgPSBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMua2V5c0J5VmFsdWUuaGFzKHZhbHVlKTtcbiAgICB9O1xuICAgIEluZGV4ZWRNdWx0aW1hcC5wcm90b3R5cGUuZ2V0S2V5c0ZvclZhbHVlID0gZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgIHZhciBzZXQgPSB0aGlzLmtleXNCeVZhbHVlLmdldCh2YWx1ZSk7XG4gICAgICAgIHJldHVybiBzZXQgPyBBcnJheS5mcm9tKHNldCkgOiBbXTtcbiAgICB9O1xuICAgIHJldHVybiBJbmRleGVkTXVsdGltYXA7XG59KE11bHRpbWFwKSk7XG5leHBvcnQgeyBJbmRleGVkTXVsdGltYXAgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWluZGV4ZWRfbXVsdGltYXAuanMubWFwIiwiaW1wb3J0IHsgYWRkLCBkZWwgfSBmcm9tIFwiLi9zZXRfb3BlcmF0aW9uc1wiO1xudmFyIE11bHRpbWFwID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIE11bHRpbWFwKCkge1xuICAgICAgICB0aGlzLnZhbHVlc0J5S2V5ID0gbmV3IE1hcCgpO1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoTXVsdGltYXAucHJvdG90eXBlLCBcInZhbHVlc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgdmFyIHNldHMgPSBBcnJheS5mcm9tKHRoaXMudmFsdWVzQnlLZXkudmFsdWVzKCkpO1xuICAgICAgICAgICAgcmV0dXJuIHNldHMucmVkdWNlKGZ1bmN0aW9uICh2YWx1ZXMsIHNldCkgeyByZXR1cm4gdmFsdWVzLmNvbmNhdChBcnJheS5mcm9tKHNldCkpOyB9LCBbXSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoTXVsdGltYXAucHJvdG90eXBlLCBcInNpemVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHZhciBzZXRzID0gQXJyYXkuZnJvbSh0aGlzLnZhbHVlc0J5S2V5LnZhbHVlcygpKTtcbiAgICAgICAgICAgIHJldHVybiBzZXRzLnJlZHVjZShmdW5jdGlvbiAoc2l6ZSwgc2V0KSB7IHJldHVybiBzaXplICsgc2V0LnNpemU7IH0sIDApO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgTXVsdGltYXAucHJvdG90eXBlLmFkZCA9IGZ1bmN0aW9uIChrZXksIHZhbHVlKSB7XG4gICAgICAgIGFkZCh0aGlzLnZhbHVlc0J5S2V5LCBrZXksIHZhbHVlKTtcbiAgICB9O1xuICAgIE11bHRpbWFwLnByb3RvdHlwZS5kZWxldGUgPSBmdW5jdGlvbiAoa2V5LCB2YWx1ZSkge1xuICAgICAgICBkZWwodGhpcy52YWx1ZXNCeUtleSwga2V5LCB2YWx1ZSk7XG4gICAgfTtcbiAgICBNdWx0aW1hcC5wcm90b3R5cGUuaGFzID0gZnVuY3Rpb24gKGtleSwgdmFsdWUpIHtcbiAgICAgICAgdmFyIHZhbHVlcyA9IHRoaXMudmFsdWVzQnlLZXkuZ2V0KGtleSk7XG4gICAgICAgIHJldHVybiB2YWx1ZXMgIT0gbnVsbCAmJiB2YWx1ZXMuaGFzKHZhbHVlKTtcbiAgICB9O1xuICAgIE11bHRpbWFwLnByb3RvdHlwZS5oYXNLZXkgPSBmdW5jdGlvbiAoa2V5KSB7XG4gICAgICAgIHJldHVybiB0aGlzLnZhbHVlc0J5S2V5LmhhcyhrZXkpO1xuICAgIH07XG4gICAgTXVsdGltYXAucHJvdG90eXBlLmhhc1ZhbHVlID0gZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgIHZhciBzZXRzID0gQXJyYXkuZnJvbSh0aGlzLnZhbHVlc0J5S2V5LnZhbHVlcygpKTtcbiAgICAgICAgcmV0dXJuIHNldHMuc29tZShmdW5jdGlvbiAoc2V0KSB7IHJldHVybiBzZXQuaGFzKHZhbHVlKTsgfSk7XG4gICAgfTtcbiAgICBNdWx0aW1hcC5wcm90b3R5cGUuZ2V0VmFsdWVzRm9yS2V5ID0gZnVuY3Rpb24gKGtleSkge1xuICAgICAgICB2YXIgdmFsdWVzID0gdGhpcy52YWx1ZXNCeUtleS5nZXQoa2V5KTtcbiAgICAgICAgcmV0dXJuIHZhbHVlcyA/IEFycmF5LmZyb20odmFsdWVzKSA6IFtdO1xuICAgIH07XG4gICAgTXVsdGltYXAucHJvdG90eXBlLmdldEtleXNGb3JWYWx1ZSA9IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLnZhbHVlc0J5S2V5KVxuICAgICAgICAgICAgLmZpbHRlcihmdW5jdGlvbiAoX2EpIHtcbiAgICAgICAgICAgIHZhciBrZXkgPSBfYVswXSwgdmFsdWVzID0gX2FbMV07XG4gICAgICAgICAgICByZXR1cm4gdmFsdWVzLmhhcyh2YWx1ZSk7XG4gICAgICAgIH0pXG4gICAgICAgICAgICAubWFwKGZ1bmN0aW9uIChfYSkge1xuICAgICAgICAgICAgdmFyIGtleSA9IF9hWzBdLCB2YWx1ZXMgPSBfYVsxXTtcbiAgICAgICAgICAgIHJldHVybiBrZXk7XG4gICAgICAgIH0pO1xuICAgIH07XG4gICAgcmV0dXJuIE11bHRpbWFwO1xufSgpKTtcbmV4cG9ydCB7IE11bHRpbWFwIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1tdWx0aW1hcC5qcy5tYXAiLCJleHBvcnQgZnVuY3Rpb24gYWRkKG1hcCwga2V5LCB2YWx1ZSkge1xuICAgIGZldGNoKG1hcCwga2V5KS5hZGQodmFsdWUpO1xufVxuZXhwb3J0IGZ1bmN0aW9uIGRlbChtYXAsIGtleSwgdmFsdWUpIHtcbiAgICBmZXRjaChtYXAsIGtleSkuZGVsZXRlKHZhbHVlKTtcbiAgICBwcnVuZShtYXAsIGtleSk7XG59XG5leHBvcnQgZnVuY3Rpb24gZmV0Y2gobWFwLCBrZXkpIHtcbiAgICB2YXIgdmFsdWVzID0gbWFwLmdldChrZXkpO1xuICAgIGlmICghdmFsdWVzKSB7XG4gICAgICAgIHZhbHVlcyA9IG5ldyBTZXQoKTtcbiAgICAgICAgbWFwLnNldChrZXksIHZhbHVlcyk7XG4gICAgfVxuICAgIHJldHVybiB2YWx1ZXM7XG59XG5leHBvcnQgZnVuY3Rpb24gcHJ1bmUobWFwLCBrZXkpIHtcbiAgICB2YXIgdmFsdWVzID0gbWFwLmdldChrZXkpO1xuICAgIGlmICh2YWx1ZXMgIT0gbnVsbCAmJiB2YWx1ZXMuc2l6ZSA9PSAwKSB7XG4gICAgICAgIG1hcC5kZWxldGUoa2V5KTtcbiAgICB9XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1zZXRfb3BlcmF0aW9ucy5qcy5tYXAiLCJpbXBvcnQgeyBFbGVtZW50T2JzZXJ2ZXIgfSBmcm9tIFwiLi9lbGVtZW50X29ic2VydmVyXCI7XG52YXIgQXR0cmlidXRlT2JzZXJ2ZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gQXR0cmlidXRlT2JzZXJ2ZXIoZWxlbWVudCwgYXR0cmlidXRlTmFtZSwgZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5hdHRyaWJ1dGVOYW1lID0gYXR0cmlidXRlTmFtZTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLmVsZW1lbnRPYnNlcnZlciA9IG5ldyBFbGVtZW50T2JzZXJ2ZXIoZWxlbWVudCwgdGhpcyk7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudE9ic2VydmVyLmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLCBcInNlbGVjdG9yXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gXCJbXCIgKyB0aGlzLmF0dHJpYnV0ZU5hbWUgKyBcIl1cIjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50T2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICB9O1xuICAgIEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmVsZW1lbnRPYnNlcnZlci5zdG9wKCk7XG4gICAgfTtcbiAgICBBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUucmVmcmVzaCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50T2JzZXJ2ZXIucmVmcmVzaCgpO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZSwgXCJzdGFydGVkXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50T2JzZXJ2ZXIuc3RhcnRlZDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIC8vIEVsZW1lbnQgb2JzZXJ2ZXIgZGVsZWdhdGVcbiAgICBBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUubWF0Y2hFbGVtZW50ID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIGVsZW1lbnQuaGFzQXR0cmlidXRlKHRoaXMuYXR0cmlidXRlTmFtZSk7XG4gICAgfTtcbiAgICBBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUubWF0Y2hFbGVtZW50c0luVHJlZSA9IGZ1bmN0aW9uICh0cmVlKSB7XG4gICAgICAgIHZhciBtYXRjaCA9IHRoaXMubWF0Y2hFbGVtZW50KHRyZWUpID8gW3RyZWVdIDogW107XG4gICAgICAgIHZhciBtYXRjaGVzID0gQXJyYXkuZnJvbSh0cmVlLnF1ZXJ5U2VsZWN0b3JBbGwodGhpcy5zZWxlY3RvcikpO1xuICAgICAgICByZXR1cm4gbWF0Y2guY29uY2F0KG1hdGNoZXMpO1xuICAgIH07XG4gICAgQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRNYXRjaGVkID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuZWxlbWVudE1hdGNoZWRBdHRyaWJ1dGUpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudE1hdGNoZWRBdHRyaWJ1dGUoZWxlbWVudCwgdGhpcy5hdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRVbm1hdGNoZWQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5lbGVtZW50VW5tYXRjaGVkQXR0cmlidXRlKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRVbm1hdGNoZWRBdHRyaWJ1dGUoZWxlbWVudCwgdGhpcy5hdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRBdHRyaWJ1dGVDaGFuZ2VkID0gZnVuY3Rpb24gKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuZWxlbWVudEF0dHJpYnV0ZVZhbHVlQ2hhbmdlZCAmJiB0aGlzLmF0dHJpYnV0ZU5hbWUgPT0gYXR0cmlidXRlTmFtZSkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5lbGVtZW50QXR0cmlidXRlVmFsdWVDaGFuZ2VkKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICByZXR1cm4gQXR0cmlidXRlT2JzZXJ2ZXI7XG59KCkpO1xuZXhwb3J0IHsgQXR0cmlidXRlT2JzZXJ2ZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWF0dHJpYnV0ZV9vYnNlcnZlci5qcy5tYXAiLCJ2YXIgRWxlbWVudE9ic2VydmVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIEVsZW1lbnRPYnNlcnZlcihlbGVtZW50LCBkZWxlZ2F0ZSkge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBlbGVtZW50O1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLmVsZW1lbnRzID0gbmV3IFNldDtcbiAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyID0gbmV3IE11dGF0aW9uT2JzZXJ2ZXIoZnVuY3Rpb24gKG11dGF0aW9ucykgeyByZXR1cm4gX3RoaXMucHJvY2Vzc011dGF0aW9ucyhtdXRhdGlvbnMpOyB9KTtcbiAgICB9XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IHRydWU7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIub2JzZXJ2ZSh0aGlzLmVsZW1lbnQsIHsgYXR0cmlidXRlczogdHJ1ZSwgY2hpbGRMaXN0OiB0cnVlLCBzdWJ0cmVlOiB0cnVlIH0pO1xuICAgICAgICAgICAgdGhpcy5yZWZyZXNoKCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLnRha2VSZWNvcmRzKCk7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIuZGlzY29ubmVjdCgpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUucmVmcmVzaCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdmFyIG1hdGNoZXMgPSBuZXcgU2V0KHRoaXMubWF0Y2hFbGVtZW50c0luVHJlZSgpKTtcbiAgICAgICAgICAgIGZvciAodmFyIF9pID0gMCwgX2EgPSBBcnJheS5mcm9tKHRoaXMuZWxlbWVudHMpOyBfaSA8IF9hLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgICAgIHZhciBlbGVtZW50ID0gX2FbX2ldO1xuICAgICAgICAgICAgICAgIGlmICghbWF0Y2hlcy5oYXMoZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5yZW1vdmVFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGZvciAodmFyIF9iID0gMCwgX2MgPSBBcnJheS5mcm9tKG1hdGNoZXMpOyBfYiA8IF9jLmxlbmd0aDsgX2IrKykge1xuICAgICAgICAgICAgICAgIHZhciBlbGVtZW50ID0gX2NbX2JdO1xuICAgICAgICAgICAgICAgIHRoaXMuYWRkRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgLy8gTXV0YXRpb24gcmVjb3JkIHByb2Nlc3NpbmdcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnByb2Nlc3NNdXRhdGlvbnMgPSBmdW5jdGlvbiAobXV0YXRpb25zKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGZvciAodmFyIF9pID0gMCwgbXV0YXRpb25zXzEgPSBtdXRhdGlvbnM7IF9pIDwgbXV0YXRpb25zXzEubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICAgICAgdmFyIG11dGF0aW9uID0gbXV0YXRpb25zXzFbX2ldO1xuICAgICAgICAgICAgICAgIHRoaXMucHJvY2Vzc011dGF0aW9uKG11dGF0aW9uKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5wcm9jZXNzTXV0YXRpb24gPSBmdW5jdGlvbiAobXV0YXRpb24pIHtcbiAgICAgICAgaWYgKG11dGF0aW9uLnR5cGUgPT0gXCJhdHRyaWJ1dGVzXCIpIHtcbiAgICAgICAgICAgIHRoaXMucHJvY2Vzc0F0dHJpYnV0ZUNoYW5nZShtdXRhdGlvbi50YXJnZXQsIG11dGF0aW9uLmF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKG11dGF0aW9uLnR5cGUgPT0gXCJjaGlsZExpc3RcIikge1xuICAgICAgICAgICAgdGhpcy5wcm9jZXNzUmVtb3ZlZE5vZGVzKG11dGF0aW9uLnJlbW92ZWROb2Rlcyk7XG4gICAgICAgICAgICB0aGlzLnByb2Nlc3NBZGRlZE5vZGVzKG11dGF0aW9uLmFkZGVkTm9kZXMpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnByb2Nlc3NBdHRyaWJ1dGVDaGFuZ2UgPSBmdW5jdGlvbiAobm9kZSwgYXR0cmlidXRlTmFtZSkge1xuICAgICAgICB2YXIgZWxlbWVudCA9IG5vZGU7XG4gICAgICAgIGlmICh0aGlzLmVsZW1lbnRzLmhhcyhlbGVtZW50KSkge1xuICAgICAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuZWxlbWVudEF0dHJpYnV0ZUNoYW5nZWQgJiYgdGhpcy5tYXRjaEVsZW1lbnQoZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRBdHRyaWJ1dGVDaGFuZ2VkKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhpcy5yZW1vdmVFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKHRoaXMubWF0Y2hFbGVtZW50KGVsZW1lbnQpKSB7XG4gICAgICAgICAgICB0aGlzLmFkZEVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUucHJvY2Vzc1JlbW92ZWROb2RlcyA9IGZ1bmN0aW9uIChub2Rlcykge1xuICAgICAgICBmb3IgKHZhciBfaSA9IDAsIF9hID0gQXJyYXkuZnJvbShub2Rlcyk7IF9pIDwgX2EubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICB2YXIgbm9kZSA9IF9hW19pXTtcbiAgICAgICAgICAgIHZhciBlbGVtZW50ID0gdGhpcy5lbGVtZW50RnJvbU5vZGUobm9kZSk7XG4gICAgICAgICAgICBpZiAoZWxlbWVudCkge1xuICAgICAgICAgICAgICAgIHRoaXMucHJvY2Vzc1RyZWUoZWxlbWVudCwgdGhpcy5yZW1vdmVFbGVtZW50KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5wcm9jZXNzQWRkZWROb2RlcyA9IGZ1bmN0aW9uIChub2Rlcykge1xuICAgICAgICBmb3IgKHZhciBfaSA9IDAsIF9hID0gQXJyYXkuZnJvbShub2Rlcyk7IF9pIDwgX2EubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICB2YXIgbm9kZSA9IF9hW19pXTtcbiAgICAgICAgICAgIHZhciBlbGVtZW50ID0gdGhpcy5lbGVtZW50RnJvbU5vZGUobm9kZSk7XG4gICAgICAgICAgICBpZiAoZWxlbWVudCAmJiB0aGlzLmVsZW1lbnRJc0FjdGl2ZShlbGVtZW50KSkge1xuICAgICAgICAgICAgICAgIHRoaXMucHJvY2Vzc1RyZWUoZWxlbWVudCwgdGhpcy5hZGRFbGVtZW50KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgLy8gRWxlbWVudCBtYXRjaGluZ1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUubWF0Y2hFbGVtZW50ID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVsZWdhdGUubWF0Y2hFbGVtZW50KGVsZW1lbnQpO1xuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5tYXRjaEVsZW1lbnRzSW5UcmVlID0gZnVuY3Rpb24gKHRyZWUpIHtcbiAgICAgICAgaWYgKHRyZWUgPT09IHZvaWQgMCkgeyB0cmVlID0gdGhpcy5lbGVtZW50OyB9XG4gICAgICAgIHJldHVybiB0aGlzLmRlbGVnYXRlLm1hdGNoRWxlbWVudHNJblRyZWUodHJlZSk7XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnByb2Nlc3NUcmVlID0gZnVuY3Rpb24gKHRyZWUsIHByb2Nlc3Nvcikge1xuICAgICAgICBmb3IgKHZhciBfaSA9IDAsIF9hID0gdGhpcy5tYXRjaEVsZW1lbnRzSW5UcmVlKHRyZWUpOyBfaSA8IF9hLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgdmFyIGVsZW1lbnQgPSBfYVtfaV07XG4gICAgICAgICAgICBwcm9jZXNzb3IuY2FsbCh0aGlzLCBlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50RnJvbU5vZGUgPSBmdW5jdGlvbiAobm9kZSkge1xuICAgICAgICBpZiAobm9kZS5ub2RlVHlwZSA9PSBOb2RlLkVMRU1FTlRfTk9ERSkge1xuICAgICAgICAgICAgcmV0dXJuIG5vZGU7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudElzQWN0aXZlID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgaWYgKGVsZW1lbnQuaXNDb25uZWN0ZWQgIT0gdGhpcy5lbGVtZW50LmlzQ29ubmVjdGVkKSB7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50LmNvbnRhaW5zKGVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICAvLyBFbGVtZW50IHRyYWNraW5nXG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5hZGRFbGVtZW50ID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgaWYgKCF0aGlzLmVsZW1lbnRzLmhhcyhlbGVtZW50KSkge1xuICAgICAgICAgICAgaWYgKHRoaXMuZWxlbWVudElzQWN0aXZlKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5lbGVtZW50cy5hZGQoZWxlbWVudCk7XG4gICAgICAgICAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuZWxlbWVudE1hdGNoZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5lbGVtZW50TWF0Y2hlZChlbGVtZW50KTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUucmVtb3ZlRWxlbWVudCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIGlmICh0aGlzLmVsZW1lbnRzLmhhcyhlbGVtZW50KSkge1xuICAgICAgICAgICAgdGhpcy5lbGVtZW50cy5kZWxldGUoZWxlbWVudCk7XG4gICAgICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5lbGVtZW50VW5tYXRjaGVkKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5lbGVtZW50VW5tYXRjaGVkKGVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICByZXR1cm4gRWxlbWVudE9ic2VydmVyO1xufSgpKTtcbmV4cG9ydCB7IEVsZW1lbnRPYnNlcnZlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9ZWxlbWVudF9vYnNlcnZlci5qcy5tYXAiLCJleHBvcnQgKiBmcm9tIFwiLi9hdHRyaWJ1dGVfb2JzZXJ2ZXJcIjtcbmV4cG9ydCAqIGZyb20gXCIuL2VsZW1lbnRfb2JzZXJ2ZXJcIjtcbmV4cG9ydCAqIGZyb20gXCIuL3N0cmluZ19tYXBfb2JzZXJ2ZXJcIjtcbmV4cG9ydCAqIGZyb20gXCIuL3Rva2VuX2xpc3Rfb2JzZXJ2ZXJcIjtcbmV4cG9ydCAqIGZyb20gXCIuL3ZhbHVlX2xpc3Rfb2JzZXJ2ZXJcIjtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWluZGV4LmpzLm1hcCIsInZhciBTdHJpbmdNYXBPYnNlcnZlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBTdHJpbmdNYXBPYnNlcnZlcihlbGVtZW50LCBkZWxlZ2F0ZSkge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBlbGVtZW50O1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLnN0cmluZ01hcCA9IG5ldyBNYXA7XG4gICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlciA9IG5ldyBNdXRhdGlvbk9ic2VydmVyKGZ1bmN0aW9uIChtdXRhdGlvbnMpIHsgcmV0dXJuIF90aGlzLnByb2Nlc3NNdXRhdGlvbnMobXV0YXRpb25zKTsgfSk7XG4gICAgfVxuICAgIFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IHRydWU7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIub2JzZXJ2ZSh0aGlzLmVsZW1lbnQsIHsgYXR0cmlidXRlczogdHJ1ZSB9KTtcbiAgICAgICAgICAgIHRoaXMucmVmcmVzaCgpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLnRha2VSZWNvcmRzKCk7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIuZGlzY29ubmVjdCgpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZS5yZWZyZXNoID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICBmb3IgKHZhciBfaSA9IDAsIF9hID0gdGhpcy5rbm93bkF0dHJpYnV0ZU5hbWVzOyBfaSA8IF9hLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgICAgIHZhciBhdHRyaWJ1dGVOYW1lID0gX2FbX2ldO1xuICAgICAgICAgICAgICAgIHRoaXMucmVmcmVzaEF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgLy8gTXV0YXRpb24gcmVjb3JkIHByb2Nlc3NpbmdcbiAgICBTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUucHJvY2Vzc011dGF0aW9ucyA9IGZ1bmN0aW9uIChtdXRhdGlvbnMpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgZm9yICh2YXIgX2kgPSAwLCBtdXRhdGlvbnNfMSA9IG11dGF0aW9uczsgX2kgPCBtdXRhdGlvbnNfMS5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgICAgICB2YXIgbXV0YXRpb24gPSBtdXRhdGlvbnNfMVtfaV07XG4gICAgICAgICAgICAgICAgdGhpcy5wcm9jZXNzTXV0YXRpb24obXV0YXRpb24pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICBTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUucHJvY2Vzc011dGF0aW9uID0gZnVuY3Rpb24gKG11dGF0aW9uKSB7XG4gICAgICAgIHZhciBhdHRyaWJ1dGVOYW1lID0gbXV0YXRpb24uYXR0cmlidXRlTmFtZTtcbiAgICAgICAgaWYgKGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgICAgIHRoaXMucmVmcmVzaEF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgLy8gU3RhdGUgdHJhY2tpbmdcbiAgICBTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUucmVmcmVzaEF0dHJpYnV0ZSA9IGZ1bmN0aW9uIChhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIHZhciBrZXkgPSB0aGlzLmRlbGVnYXRlLmdldFN0cmluZ01hcEtleUZvckF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgaWYgKGtleSAhPSBudWxsKSB7XG4gICAgICAgICAgICBpZiAoIXRoaXMuc3RyaW5nTWFwLmhhcyhhdHRyaWJ1dGVOYW1lKSkge1xuICAgICAgICAgICAgICAgIHRoaXMuc3RyaW5nTWFwS2V5QWRkZWQoa2V5LCBhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHZhciB2YWx1ZSA9IHRoaXMuZWxlbWVudC5nZXRBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSk7XG4gICAgICAgICAgICBpZiAodGhpcy5zdHJpbmdNYXAuZ2V0KGF0dHJpYnV0ZU5hbWUpICE9IHZhbHVlKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5zdHJpbmdNYXBWYWx1ZUNoYW5nZWQodmFsdWUsIGtleSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAodmFsdWUgPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIHRoaXMuc3RyaW5nTWFwLmRlbGV0ZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgICAgICAgICB0aGlzLnN0cmluZ01hcEtleVJlbW92ZWQoa2V5LCBhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIHRoaXMuc3RyaW5nTWFwLnNldChhdHRyaWJ1dGVOYW1lLCB2YWx1ZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZS5zdHJpbmdNYXBLZXlBZGRlZCA9IGZ1bmN0aW9uIChrZXksIGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuc3RyaW5nTWFwS2V5QWRkZWQpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuc3RyaW5nTWFwS2V5QWRkZWQoa2V5LCBhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLnN0cmluZ01hcFZhbHVlQ2hhbmdlZCA9IGZ1bmN0aW9uICh2YWx1ZSwga2V5KSB7XG4gICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLnN0cmluZ01hcFZhbHVlQ2hhbmdlZCkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBWYWx1ZUNoYW5nZWQodmFsdWUsIGtleSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZS5zdHJpbmdNYXBLZXlSZW1vdmVkID0gZnVuY3Rpb24gKGtleSwgYXR0cmlidXRlTmFtZSkge1xuICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBLZXlSZW1vdmVkKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnN0cmluZ01hcEtleVJlbW92ZWQoa2V5LCBhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZSwgXCJrbm93bkF0dHJpYnV0ZU5hbWVzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbShuZXcgU2V0KHRoaXMuY3VycmVudEF0dHJpYnV0ZU5hbWVzLmNvbmNhdCh0aGlzLnJlY29yZGVkQXR0cmlidXRlTmFtZXMpKSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLCBcImN1cnJlbnRBdHRyaWJ1dGVOYW1lc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5lbGVtZW50LmF0dHJpYnV0ZXMpLm1hcChmdW5jdGlvbiAoYXR0cmlidXRlKSB7IHJldHVybiBhdHRyaWJ1dGUubmFtZTsgfSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLCBcInJlY29yZGVkQXR0cmlidXRlTmFtZXNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMuc3RyaW5nTWFwLmtleXMoKSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICByZXR1cm4gU3RyaW5nTWFwT2JzZXJ2ZXI7XG59KCkpO1xuZXhwb3J0IHsgU3RyaW5nTWFwT2JzZXJ2ZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXN0cmluZ19tYXBfb2JzZXJ2ZXIuanMubWFwIiwiaW1wb3J0IHsgQXR0cmlidXRlT2JzZXJ2ZXIgfSBmcm9tIFwiLi9hdHRyaWJ1dGVfb2JzZXJ2ZXJcIjtcbmltcG9ydCB7IE11bHRpbWFwIH0gZnJvbSBcIkBzdGltdWx1cy9tdWx0aW1hcFwiO1xudmFyIFRva2VuTGlzdE9ic2VydmVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIFRva2VuTGlzdE9ic2VydmVyKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUsIGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXIgPSBuZXcgQXR0cmlidXRlT2JzZXJ2ZXIoZWxlbWVudCwgYXR0cmlidXRlTmFtZSwgdGhpcyk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy50b2tlbnNCeUVsZW1lbnQgPSBuZXcgTXVsdGltYXA7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUsIFwic3RhcnRlZFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXIuc3RhcnRlZDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5hdHRyaWJ1dGVPYnNlcnZlci5zdGFydCgpO1xuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXIuc3RvcCgpO1xuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnJlZnJlc2ggPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXIucmVmcmVzaCgpO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hdHRyaWJ1dGVPYnNlcnZlci5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZSwgXCJhdHRyaWJ1dGVOYW1lXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hdHRyaWJ1dGVPYnNlcnZlci5hdHRyaWJ1dGVOYW1lO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgLy8gQXR0cmlidXRlIG9ic2VydmVyIGRlbGVnYXRlXG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRNYXRjaGVkQXR0cmlidXRlID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgdGhpcy50b2tlbnNNYXRjaGVkKHRoaXMucmVhZFRva2Vuc0ZvckVsZW1lbnQoZWxlbWVudCkpO1xuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRBdHRyaWJ1dGVWYWx1ZUNoYW5nZWQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICB2YXIgX2EgPSB0aGlzLnJlZnJlc2hUb2tlbnNGb3JFbGVtZW50KGVsZW1lbnQpLCB1bm1hdGNoZWRUb2tlbnMgPSBfYVswXSwgbWF0Y2hlZFRva2VucyA9IF9hWzFdO1xuICAgICAgICB0aGlzLnRva2Vuc1VubWF0Y2hlZCh1bm1hdGNoZWRUb2tlbnMpO1xuICAgICAgICB0aGlzLnRva2Vuc01hdGNoZWQobWF0Y2hlZFRva2Vucyk7XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudFVubWF0Y2hlZEF0dHJpYnV0ZSA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHRoaXMudG9rZW5zVW5tYXRjaGVkKHRoaXMudG9rZW5zQnlFbGVtZW50LmdldFZhbHVlc0ZvcktleShlbGVtZW50KSk7XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUudG9rZW5zTWF0Y2hlZCA9IGZ1bmN0aW9uICh0b2tlbnMpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdG9rZW5zLmZvckVhY2goZnVuY3Rpb24gKHRva2VuKSB7IHJldHVybiBfdGhpcy50b2tlbk1hdGNoZWQodG9rZW4pOyB9KTtcbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS50b2tlbnNVbm1hdGNoZWQgPSBmdW5jdGlvbiAodG9rZW5zKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHRva2Vucy5mb3JFYWNoKGZ1bmN0aW9uICh0b2tlbikgeyByZXR1cm4gX3RoaXMudG9rZW5Vbm1hdGNoZWQodG9rZW4pOyB9KTtcbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS50b2tlbk1hdGNoZWQgPSBmdW5jdGlvbiAodG9rZW4pIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS50b2tlbk1hdGNoZWQodG9rZW4pO1xuICAgICAgICB0aGlzLnRva2Vuc0J5RWxlbWVudC5hZGQodG9rZW4uZWxlbWVudCwgdG9rZW4pO1xuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnRva2VuVW5tYXRjaGVkID0gZnVuY3Rpb24gKHRva2VuKSB7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUudG9rZW5Vbm1hdGNoZWQodG9rZW4pO1xuICAgICAgICB0aGlzLnRva2Vuc0J5RWxlbWVudC5kZWxldGUodG9rZW4uZWxlbWVudCwgdG9rZW4pO1xuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnJlZnJlc2hUb2tlbnNGb3JFbGVtZW50ID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgdmFyIHByZXZpb3VzVG9rZW5zID0gdGhpcy50b2tlbnNCeUVsZW1lbnQuZ2V0VmFsdWVzRm9yS2V5KGVsZW1lbnQpO1xuICAgICAgICB2YXIgY3VycmVudFRva2VucyA9IHRoaXMucmVhZFRva2Vuc0ZvckVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIHZhciBmaXJzdERpZmZlcmluZ0luZGV4ID0gemlwKHByZXZpb3VzVG9rZW5zLCBjdXJyZW50VG9rZW5zKVxuICAgICAgICAgICAgLmZpbmRJbmRleChmdW5jdGlvbiAoX2EpIHtcbiAgICAgICAgICAgIHZhciBwcmV2aW91c1Rva2VuID0gX2FbMF0sIGN1cnJlbnRUb2tlbiA9IF9hWzFdO1xuICAgICAgICAgICAgcmV0dXJuICF0b2tlbnNBcmVFcXVhbChwcmV2aW91c1Rva2VuLCBjdXJyZW50VG9rZW4pO1xuICAgICAgICB9KTtcbiAgICAgICAgaWYgKGZpcnN0RGlmZmVyaW5nSW5kZXggPT0gLTEpIHtcbiAgICAgICAgICAgIHJldHVybiBbW10sIFtdXTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBbcHJldmlvdXNUb2tlbnMuc2xpY2UoZmlyc3REaWZmZXJpbmdJbmRleCksIGN1cnJlbnRUb2tlbnMuc2xpY2UoZmlyc3REaWZmZXJpbmdJbmRleCldO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUucmVhZFRva2Vuc0ZvckVsZW1lbnQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICB2YXIgYXR0cmlidXRlTmFtZSA9IHRoaXMuYXR0cmlidXRlTmFtZTtcbiAgICAgICAgdmFyIHRva2VuU3RyaW5nID0gZWxlbWVudC5nZXRBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSkgfHwgXCJcIjtcbiAgICAgICAgcmV0dXJuIHBhcnNlVG9rZW5TdHJpbmcodG9rZW5TdHJpbmcsIGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUpO1xuICAgIH07XG4gICAgcmV0dXJuIFRva2VuTGlzdE9ic2VydmVyO1xufSgpKTtcbmV4cG9ydCB7IFRva2VuTGlzdE9ic2VydmVyIH07XG5mdW5jdGlvbiBwYXJzZVRva2VuU3RyaW5nKHRva2VuU3RyaW5nLCBlbGVtZW50LCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgcmV0dXJuIHRva2VuU3RyaW5nLnRyaW0oKS5zcGxpdCgvXFxzKy8pLmZpbHRlcihmdW5jdGlvbiAoY29udGVudCkgeyByZXR1cm4gY29udGVudC5sZW5ndGg7IH0pXG4gICAgICAgIC5tYXAoZnVuY3Rpb24gKGNvbnRlbnQsIGluZGV4KSB7IHJldHVybiAoeyBlbGVtZW50OiBlbGVtZW50LCBhdHRyaWJ1dGVOYW1lOiBhdHRyaWJ1dGVOYW1lLCBjb250ZW50OiBjb250ZW50LCBpbmRleDogaW5kZXggfSk7IH0pO1xufVxuZnVuY3Rpb24gemlwKGxlZnQsIHJpZ2h0KSB7XG4gICAgdmFyIGxlbmd0aCA9IE1hdGgubWF4KGxlZnQubGVuZ3RoLCByaWdodC5sZW5ndGgpO1xuICAgIHJldHVybiBBcnJheS5mcm9tKHsgbGVuZ3RoOiBsZW5ndGggfSwgZnVuY3Rpb24gKF8sIGluZGV4KSB7IHJldHVybiBbbGVmdFtpbmRleF0sIHJpZ2h0W2luZGV4XV07IH0pO1xufVxuZnVuY3Rpb24gdG9rZW5zQXJlRXF1YWwobGVmdCwgcmlnaHQpIHtcbiAgICByZXR1cm4gbGVmdCAmJiByaWdodCAmJiBsZWZ0LmluZGV4ID09IHJpZ2h0LmluZGV4ICYmIGxlZnQuY29udGVudCA9PSByaWdodC5jb250ZW50O1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9dG9rZW5fbGlzdF9vYnNlcnZlci5qcy5tYXAiLCJpbXBvcnQgeyBUb2tlbkxpc3RPYnNlcnZlciB9IGZyb20gXCIuL3Rva2VuX2xpc3Rfb2JzZXJ2ZXJcIjtcbnZhciBWYWx1ZUxpc3RPYnNlcnZlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBWYWx1ZUxpc3RPYnNlcnZlcihlbGVtZW50LCBhdHRyaWJ1dGVOYW1lLCBkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLnRva2VuTGlzdE9ic2VydmVyID0gbmV3IFRva2VuTGlzdE9ic2VydmVyKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUsIHRoaXMpO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMucGFyc2VSZXN1bHRzQnlUb2tlbiA9IG5ldyBXZWFrTWFwO1xuICAgICAgICB0aGlzLnZhbHVlc0J5VG9rZW5CeUVsZW1lbnQgPSBuZXcgV2Vha01hcDtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZSwgXCJzdGFydGVkXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy50b2tlbkxpc3RPYnNlcnZlci5zdGFydGVkO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnRva2VuTGlzdE9ic2VydmVyLnN0YXJ0KCk7XG4gICAgfTtcbiAgICBWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy50b2tlbkxpc3RPYnNlcnZlci5zdG9wKCk7XG4gICAgfTtcbiAgICBWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUucmVmcmVzaCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy50b2tlbkxpc3RPYnNlcnZlci5yZWZyZXNoKCk7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnRva2VuTGlzdE9ic2VydmVyLmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLCBcImF0dHJpYnV0ZU5hbWVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnRva2VuTGlzdE9ic2VydmVyLmF0dHJpYnV0ZU5hbWU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUudG9rZW5NYXRjaGVkID0gZnVuY3Rpb24gKHRva2VuKSB7XG4gICAgICAgIHZhciBlbGVtZW50ID0gdG9rZW4uZWxlbWVudDtcbiAgICAgICAgdmFyIHZhbHVlID0gdGhpcy5mZXRjaFBhcnNlUmVzdWx0Rm9yVG9rZW4odG9rZW4pLnZhbHVlO1xuICAgICAgICBpZiAodmFsdWUpIHtcbiAgICAgICAgICAgIHRoaXMuZmV0Y2hWYWx1ZXNCeVRva2VuRm9yRWxlbWVudChlbGVtZW50KS5zZXQodG9rZW4sIHZhbHVlKTtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudE1hdGNoZWRWYWx1ZShlbGVtZW50LCB2YWx1ZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZS50b2tlblVubWF0Y2hlZCA9IGZ1bmN0aW9uICh0b2tlbikge1xuICAgICAgICB2YXIgZWxlbWVudCA9IHRva2VuLmVsZW1lbnQ7XG4gICAgICAgIHZhciB2YWx1ZSA9IHRoaXMuZmV0Y2hQYXJzZVJlc3VsdEZvclRva2VuKHRva2VuKS52YWx1ZTtcbiAgICAgICAgaWYgKHZhbHVlKSB7XG4gICAgICAgICAgICB0aGlzLmZldGNoVmFsdWVzQnlUb2tlbkZvckVsZW1lbnQoZWxlbWVudCkuZGVsZXRlKHRva2VuKTtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudFVubWF0Y2hlZFZhbHVlKGVsZW1lbnQsIHZhbHVlKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLmZldGNoUGFyc2VSZXN1bHRGb3JUb2tlbiA9IGZ1bmN0aW9uICh0b2tlbikge1xuICAgICAgICB2YXIgcGFyc2VSZXN1bHQgPSB0aGlzLnBhcnNlUmVzdWx0c0J5VG9rZW4uZ2V0KHRva2VuKTtcbiAgICAgICAgaWYgKCFwYXJzZVJlc3VsdCkge1xuICAgICAgICAgICAgcGFyc2VSZXN1bHQgPSB0aGlzLnBhcnNlVG9rZW4odG9rZW4pO1xuICAgICAgICAgICAgdGhpcy5wYXJzZVJlc3VsdHNCeVRva2VuLnNldCh0b2tlbiwgcGFyc2VSZXN1bHQpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBwYXJzZVJlc3VsdDtcbiAgICB9O1xuICAgIFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZS5mZXRjaFZhbHVlc0J5VG9rZW5Gb3JFbGVtZW50ID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgdmFyIHZhbHVlc0J5VG9rZW4gPSB0aGlzLnZhbHVlc0J5VG9rZW5CeUVsZW1lbnQuZ2V0KGVsZW1lbnQpO1xuICAgICAgICBpZiAoIXZhbHVlc0J5VG9rZW4pIHtcbiAgICAgICAgICAgIHZhbHVlc0J5VG9rZW4gPSBuZXcgTWFwO1xuICAgICAgICAgICAgdGhpcy52YWx1ZXNCeVRva2VuQnlFbGVtZW50LnNldChlbGVtZW50LCB2YWx1ZXNCeVRva2VuKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gdmFsdWVzQnlUb2tlbjtcbiAgICB9O1xuICAgIFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZS5wYXJzZVRva2VuID0gZnVuY3Rpb24gKHRva2VuKSB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICB2YXIgdmFsdWUgPSB0aGlzLmRlbGVnYXRlLnBhcnNlVmFsdWVGb3JUb2tlbih0b2tlbik7XG4gICAgICAgICAgICByZXR1cm4geyB2YWx1ZTogdmFsdWUgfTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHJldHVybiB7IGVycm9yOiBlcnJvciB9O1xuICAgICAgICB9XG4gICAgfTtcbiAgICByZXR1cm4gVmFsdWVMaXN0T2JzZXJ2ZXI7XG59KCkpO1xuZXhwb3J0IHsgVmFsdWVMaXN0T2JzZXJ2ZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXZhbHVlX2xpc3Rfb2JzZXJ2ZXIuanMubWFwIiwiZXhwb3J0ICogZnJvbSBcIkBzdGltdWx1cy9jb3JlXCJcbiIsImltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tIFwic3RpbXVsdXNcIjtcbmltcG9ydCB7IGNvbm5lY3RTdHJlYW1Tb3VyY2UsIGRpc2Nvbm5lY3RTdHJlYW1Tb3VyY2UgfSBmcm9tIFwiQGhvdHdpcmVkL3R1cmJvXCI7XG5cbi8vIFRPRE86IGFsbG93IHRvIHVzZSBwb2x5ZmlsbHMgZm9yIFVSTCBhbmQgRXZlbnRTb3VyY2VcbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gIGluaXRpYWxpemUoKSB7XG4gICAgaWYgKCF0aGlzLmVsZW1lbnQuaWQpIHtcbiAgICAgIGNvbnNvbGUuZXJyb3IoYFRoZSBlbGVtZW50IG11c3QgaGF2ZSBhbiBcImlkXCIgYXR0cmlidXRlLmApO1xuICAgIH1cblxuICAgIHRoaXMuaHViID0gdGhpcy5lbGVtZW50LmdldEF0dHJpYnV0ZShcImRhdGEtaHViXCIpO1xuICAgIGlmICghdGhpcy5odWIpIHtcbiAgICAgIGNvbnNvbGUuZXJyb3IoYFRoZSBlbGVtZW50IG11c3QgaGF2ZSBhIFwiZGF0YS1odWJcIiBhdHRyaWJ1dGUgcG9pbnRpbmcgdG8gdGhlIE1lcmN1cmUgaHViLmApO1xuICAgIH1cblxuICAgIGNvbnN0IHUgPSBuZXcgVVJMKHRoaXMuaHViKTtcbiAgICB1LnNlYXJjaFBhcmFtcy5hcHBlbmQoXCJ0b3BpY1wiLCB0aGlzLmVsZW1lbnQuaWQpO1xuXG4gICAgdGhpcy51cmwgPSB1LnRvU3RyaW5nKCk7XG4gIH1cblxuICBjb25uZWN0KCkge1xuICAgIHRoaXMuZXMgPSBuZXcgRXZlbnRTb3VyY2UodGhpcy51cmwpO1xuICAgIGNvbm5lY3RTdHJlYW1Tb3VyY2UodGhpcy5lcyk7XG4gIH1cblxuICBkaXNjb25uZWN0KCkge1xuICAgIHRoaXMuZXMuY2xvc2UoKTtcbiAgICBkaXNjb25uZWN0U3RyZWFtU291cmNlKHRoaXMuZXMpO1xuICB9XG59XG4iLCJleHBvcnQgKiBmcm9tIFwiQHN0aW11bHVzL2NvcmVcIlxuIl0sInNvdXJjZVJvb3QiOiIifQ==