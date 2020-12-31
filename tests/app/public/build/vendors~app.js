(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["vendors~app"],{

/***/ "./node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js":
/*!***************************************************************!*\
  !*** ./node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js ***!
  \***************************************************************/
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

/***/ "./node_modules/@symfony/ux-turbo/src/stream_controller.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/src/stream_controller.js ***!
  \*****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! stimulus */ "./node_modules/stimulus/index.js");
/* harmony import */ var _hotwired_turbo__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @hotwired/turbo */ "./node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js");
/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */




/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
/* harmony default export */ __webpack_exports__["default"] = (class extends stimulus__WEBPACK_IMPORTED_MODULE_0__["Controller"] {
  initialize() {
    const topic = this.element.getAttribute("data-topic");
    if (!topic) {
      console.error(`The element must have a "data-topic" attribute.`);
      return;
    }

    const hub = this.element.getAttribute("data-hub");
    if (!hub) {
      console.error(`The element must have a "data-hub" attribute pointing to the Mercure hub.`);
      return;
    }

    const u = new URL(hub);
    u.searchParams.append("topic", topic);

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
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQGhvdHdpcmVkL3R1cmJvL2Rpc3QvdHVyYm8uZXMyMDE3LWVzbS5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9hY3Rpb24uanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvYWN0aW9uX2Rlc2NyaXB0b3IuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvYXBwbGljYXRpb24uanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvYmluZGluZy5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9iaW5kaW5nX29ic2VydmVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2JsZXNzaW5nLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2NsYXNzX21hcC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9jbGFzc19wcm9wZXJ0aWVzLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2NvbnRleHQuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvY29udHJvbGxlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9kYXRhX21hcC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9kZWZpbml0aW9uLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L2Rpc3BhdGNoZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvZXZlbnRfbGlzdGVuZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvZ3VpZGUuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvaW5kZXguanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvaW5oZXJpdGFibGVfc3RhdGljcy5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9tb2R1bGUuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3Qvcm91dGVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvY29yZS9kaXN0L3NjaGVtYS5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9zY29wZS5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9zY29wZV9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC9zZWxlY3RvcnMuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3Qvc3RyaW5nX2hlbHBlcnMuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvdGFyZ2V0X3Byb3BlcnRpZXMuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9jb3JlL2Rpc3QvdGFyZ2V0X3NldC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC92YWx1ZV9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL2NvcmUvZGlzdC92YWx1ZV9wcm9wZXJ0aWVzLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXVsdGltYXAvZGlzdC9pbmRleC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211bHRpbWFwL2Rpc3QvaW5kZXhlZF9tdWx0aW1hcC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211bHRpbWFwL2Rpc3QvbXVsdGltYXAuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9tdWx0aW1hcC9kaXN0L3NldF9vcGVyYXRpb25zLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzL2Rpc3QvYXR0cmlidXRlX29ic2VydmVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzL2Rpc3QvZWxlbWVudF9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211dGF0aW9uLW9ic2VydmVycy9kaXN0L2luZGV4LmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3RpbXVsdXMvbXV0YXRpb24tb2JzZXJ2ZXJzL2Rpc3Qvc3RyaW5nX21hcF9vYnNlcnZlci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHN0aW11bHVzL211dGF0aW9uLW9ic2VydmVycy9kaXN0L3Rva2VuX2xpc3Rfb2JzZXJ2ZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzdGltdWx1cy9tdXRhdGlvbi1vYnNlcnZlcnMvZGlzdC92YWx1ZV9saXN0X29ic2VydmVyLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS91eC10dXJiby9zcmMvc3RyZWFtX2NvbnRyb2xsZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL3N0aW11bHVzL2luZGV4LmpzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxDQUFDOztBQUVEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMLENBQUM7O0FBRUQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHVEQUF1RDtBQUN2RDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7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QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBLDBDQUEwQztBQUMxQztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esa0RBQWtEO0FBQ2xEO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0EsZ0JBQWdCO0FBQ2hCO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDZFQUE2RSxHQUFHO0FBQ2hGO0FBQ0E7QUFDQSwyRkFBMkYsR0FBRztBQUM5RjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHdDQUF3QyxRQUFRO0FBQ2hEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsbURBQW1ELDRCQUE0QjtBQUMvRTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFD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QUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxrREFBa0QsR0FBRztBQUNyRDtBQUNBO0FBQ0Esb0RBQW9ELEtBQUs7QUFDekQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDZEQUE2RCxLQUFLO0FBQ2xFO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxDQUFDLG9DQUFvQztBQUNyQztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLENBQUMsZ0NBQWdDO0FBQ2pDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLENBQUMsNENBQTRDO0FBQzdDO0FBQ0EsdUVBQXVFO0FBQ3ZFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGVBQWUsMkRBQTJELGlDQUFpQztBQUMzRztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsbUJBQW1CLGFBQWE7QUFDaEM7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG1CQUFtQiwyQkFBMkI7QUFDOUM7QUFDQTtBQUNBO0FBQ0Esc0NBQXNDLGtEQUFrRDtBQUN4RjtBQUNBO0FBQ0E7QUFDQTtBQUNBLHNDQUFzQyxzQkFBc0I7QUFDNUQ7QUFDQTtBQUNBO0FBQ0EsYUFBYTtBQUNiO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGtDQUFrQyxzQkFBc0I7QUFDeEQ7QUFDQTtBQUNBO0FBQ0E7QUFDQSxhQUFhO0FBQ2I7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGlDQUFpQyxtREFBbUQ7QUFDcEY7QUFDQTtBQUNBO0FBQ0EsaUNBQWlDLGdEQUFnRDtBQUNqRjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsaUNBQWlDLG1EQUFtRDtBQUNwRjtBQUNBO0FBQ0EsaUNBQWlDLGdEQUFnRDtBQUNqRjtBQUNBO0FBQ0E7QUFDQSw2QkFBNkIsOENBQThDO0FBQzNFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxlQUFlLGlCQUFpQjtBQUNoQztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxvQ0FBb0MsYUFBYTtBQUNqRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsK0JBQStCO0FBQy9CO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHlEQUF5RCxHQUFHO0FBQzVEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsdUJBQXVCLFFBQVE7QUFDL0I7QUFDQTtBQUNBO0FBQ0EsMkJBQTJCLHdCQUF3QjtBQUNuRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHVCQUF1QixTQUFTLHdCQUF3QjtBQUN4RDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZUFBZSx3QkFBd0I7QUFDdkM7QUFDQSxvRkFBb0Y7QUFDcEY7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1Q0FBdUM7QUFDdkM7QUFDQTtBQUNBO0FBQ0E7QUFDQSw0REFBNEQ7QUFDNUQ7QUFDQSwyR0FBMkcsMEJBQTBCO0FBQ3JJO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsdUJBQXVCLGFBQWE7QUFDcEMsc0NBQXNDLFlBQVksMkJBQTJCO0FBQzdFO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxDQUFDLDhCQUE4QjtBQUMvQjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsbUJBQW1CLGFBQWE7QUFDaEM7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLGlDQUFpQywrQ0FBK0M7QUFDaEY7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1QkFBdUIsVUFBVTtBQUNqQyw2Q0FBNkM7QUFDN0M7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1QkFBdUI7QUFDdkI7O0FBRUE7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxnQkFBZ0IsY0FBYztBQUM5QjtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFNBQVM7QUFDVDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxlQUFlLDhCQUE4QjtBQUM3QztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxhQUFhO0FBQ2I7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLG9CQUFvQiw0QkFBNEI7QUFDaEQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsWUFBWTtBQUNaO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNB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Q0o7QUFDakQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG1DQUFtQyxpRUFBZTtBQUNsRCxpQ0FBaUMsNkRBQWE7QUFDOUM7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0EsU0FBUztBQUNUO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBLGdDQUFnQyxhQUFhO0FBQzdDO0FBQ0EsZ0NBQWdDLG1FQUFtRTtBQUNuRztBQUNBO0FBQ0E7QUFDQSxDQUFDO0FBQ2tCO0FBQ25CLG1DOzs7Ozs7Ozs7Ozs7QUN4RkE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUE2RDtBQUNFO0FBQ0Y7QUFDN0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtB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QUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLG9CQUFvQjtBQUNwQjtBQUNBO0FBQ0Esb0JBQW9CO0FBQ3BCO0FBQ0E7QUFDQTtBQUNBLENBQUM7QUFDNEI7QUFDN0IsK0M7Ozs7Ozs7Ozs7OztBQ2xGQTtBQUFBO0FBQUE7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVzQztBQUN3Qzs7QUFFOUU7QUFDQTtBQUNBO0FBQ2UsNkVBQWMsbURBQVU7QUFDdkM7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTs7QUFFQTtBQUNBOztBQUVBO0FBQ0E7QUFDQSxJQUFJLDJFQUFtQjtBQUN2Qjs7QUFFQTtBQUNBO0FBQ0EsSUFBSSw4RUFBc0I7QUFDMUI7QUFDQSxDQUFDOzs7Ozs7Ozs7Ozs7O0FDNUNEO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQThCIiwiZmlsZSI6InZlbmRvcnN+YXBwLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLypcblR1cmJvIDcuMC4wLWJldGEuMVxuQ29weXJpZ2h0IMKpIDIwMjAgQmFzZWNhbXAsIExMQ1xuICovXG4oZnVuY3Rpb24gKCkge1xuICAgIGlmICh3aW5kb3cuUmVmbGVjdCA9PT0gdW5kZWZpbmVkIHx8IHdpbmRvdy5jdXN0b21FbGVtZW50cyA9PT0gdW5kZWZpbmVkIHx8XG4gICAgICAgIHdpbmRvdy5jdXN0b21FbGVtZW50cy5wb2x5ZmlsbFdyYXBGbHVzaENhbGxiYWNrKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgY29uc3QgQnVpbHRJbkhUTUxFbGVtZW50ID0gSFRNTEVsZW1lbnQ7XG4gICAgY29uc3Qgd3JhcHBlckZvclRoZU5hbWUgPSB7XG4gICAgICAgICdIVE1MRWxlbWVudCc6IGZ1bmN0aW9uIEhUTUxFbGVtZW50KCkge1xuICAgICAgICAgICAgcmV0dXJuIFJlZmxlY3QuY29uc3RydWN0KEJ1aWx0SW5IVE1MRWxlbWVudCwgW10sIHRoaXMuY29uc3RydWN0b3IpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICB3aW5kb3cuSFRNTEVsZW1lbnQgPVxuICAgICAgICB3cmFwcGVyRm9yVGhlTmFtZVsnSFRNTEVsZW1lbnQnXTtcbiAgICBIVE1MRWxlbWVudC5wcm90b3R5cGUgPSBCdWlsdEluSFRNTEVsZW1lbnQucHJvdG90eXBlO1xuICAgIEhUTUxFbGVtZW50LnByb3RvdHlwZS5jb25zdHJ1Y3RvciA9IEhUTUxFbGVtZW50O1xuICAgIE9iamVjdC5zZXRQcm90b3R5cGVPZihIVE1MRWxlbWVudCwgQnVpbHRJbkhUTUxFbGVtZW50KTtcbn0pKCk7XG5cbmNvbnN0IHN1Ym1pdHRlcnNCeUZvcm0gPSBuZXcgV2Vha01hcDtcbmZ1bmN0aW9uIGZpbmRTdWJtaXR0ZXJGcm9tQ2xpY2tUYXJnZXQodGFyZ2V0KSB7XG4gICAgY29uc3QgZWxlbWVudCA9IHRhcmdldCBpbnN0YW5jZW9mIEVsZW1lbnQgPyB0YXJnZXQgOiB0YXJnZXQgaW5zdGFuY2VvZiBOb2RlID8gdGFyZ2V0LnBhcmVudEVsZW1lbnQgOiBudWxsO1xuICAgIGNvbnN0IGNhbmRpZGF0ZSA9IGVsZW1lbnQgPyBlbGVtZW50LmNsb3Nlc3QoXCJpbnB1dCwgYnV0dG9uXCIpIDogbnVsbDtcbiAgICByZXR1cm4gKGNhbmRpZGF0ZSA9PT0gbnVsbCB8fCBjYW5kaWRhdGUgPT09IHZvaWQgMCA/IHZvaWQgMCA6IGNhbmRpZGF0ZS5nZXRBdHRyaWJ1dGUoXCJ0eXBlXCIpKSA9PSBcInN1Ym1pdFwiID8gY2FuZGlkYXRlIDogbnVsbDtcbn1cbmZ1bmN0aW9uIGNsaWNrQ2FwdHVyZWQoZXZlbnQpIHtcbiAgICBjb25zdCBzdWJtaXR0ZXIgPSBmaW5kU3VibWl0dGVyRnJvbUNsaWNrVGFyZ2V0KGV2ZW50LnRhcmdldCk7XG4gICAgaWYgKHN1Ym1pdHRlciAmJiBzdWJtaXR0ZXIuZm9ybSkge1xuICAgICAgICBzdWJtaXR0ZXJzQnlGb3JtLnNldChzdWJtaXR0ZXIuZm9ybSwgc3VibWl0dGVyKTtcbiAgICB9XG59XG4oZnVuY3Rpb24gKCkge1xuICAgIGlmIChcIlN1Ym1pdEV2ZW50XCIgaW4gd2luZG93KVxuICAgICAgICByZXR1cm47XG4gICAgYWRkRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsIGNsaWNrQ2FwdHVyZWQsIHRydWUpO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShFdmVudC5wcm90b3R5cGUsIFwic3VibWl0dGVyXCIsIHtcbiAgICAgICAgZ2V0KCkge1xuICAgICAgICAgICAgaWYgKHRoaXMudHlwZSA9PSBcInN1Ym1pdFwiICYmIHRoaXMudGFyZ2V0IGluc3RhbmNlb2YgSFRNTEZvcm1FbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHN1Ym1pdHRlcnNCeUZvcm0uZ2V0KHRoaXMudGFyZ2V0KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH0pO1xufSkoKTtcblxuY2xhc3MgTG9jYXRpb24ge1xuICAgIGNvbnN0cnVjdG9yKHVybCkge1xuICAgICAgICBjb25zdCBsaW5rV2l0aEFuY2hvciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJhXCIpO1xuICAgICAgICBsaW5rV2l0aEFuY2hvci5ocmVmID0gdXJsO1xuICAgICAgICB0aGlzLmFic29sdXRlVVJMID0gbGlua1dpdGhBbmNob3IuaHJlZjtcbiAgICAgICAgY29uc3QgYW5jaG9yTGVuZ3RoID0gbGlua1dpdGhBbmNob3IuaGFzaC5sZW5ndGg7XG4gICAgICAgIGlmIChhbmNob3JMZW5ndGggPCAyKSB7XG4gICAgICAgICAgICB0aGlzLnJlcXVlc3RVUkwgPSB0aGlzLmFic29sdXRlVVJMO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5yZXF1ZXN0VVJMID0gdGhpcy5hYnNvbHV0ZVVSTC5zbGljZSgwLCAtYW5jaG9yTGVuZ3RoKTtcbiAgICAgICAgICAgIHRoaXMuYW5jaG9yID0gbGlua1dpdGhBbmNob3IuaGFzaC5zbGljZSgxKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdGF0aWMgZ2V0IGN1cnJlbnRMb2NhdGlvbigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMud3JhcCh3aW5kb3cubG9jYXRpb24udG9TdHJpbmcoKSk7XG4gICAgfVxuICAgIHN0YXRpYyB3cmFwKGxvY2F0YWJsZSkge1xuICAgICAgICBpZiAodHlwZW9mIGxvY2F0YWJsZSA9PSBcInN0cmluZ1wiKSB7XG4gICAgICAgICAgICByZXR1cm4gbmV3IHRoaXMobG9jYXRhYmxlKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChsb2NhdGFibGUgIT0gbnVsbCkge1xuICAgICAgICAgICAgcmV0dXJuIGxvY2F0YWJsZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXRPcmlnaW4oKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFic29sdXRlVVJMLnNwbGl0KFwiL1wiLCAzKS5qb2luKFwiL1wiKTtcbiAgICB9XG4gICAgZ2V0UGF0aCgpIHtcbiAgICAgICAgcmV0dXJuICh0aGlzLnJlcXVlc3RVUkwubWF0Y2goL1xcL1xcL1teL10qKFxcL1tePztdKikvKSB8fCBbXSlbMV0gfHwgXCIvXCI7XG4gICAgfVxuICAgIGdldFBhdGhDb21wb25lbnRzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRQYXRoKCkuc3BsaXQoXCIvXCIpLnNsaWNlKDEpO1xuICAgIH1cbiAgICBnZXRMYXN0UGF0aENvbXBvbmVudCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0UGF0aENvbXBvbmVudHMoKS5zbGljZSgtMSlbMF07XG4gICAgfVxuICAgIGdldEV4dGVuc2lvbigpIHtcbiAgICAgICAgcmV0dXJuICh0aGlzLmdldExhc3RQYXRoQ29tcG9uZW50KCkubWF0Y2goL1xcLlteLl0qJC8pIHx8IFtdKVswXSB8fCBcIlwiO1xuICAgIH1cbiAgICBpc0hUTUwoKSB7XG4gICAgICAgIHJldHVybiAhIXRoaXMuZ2V0RXh0ZW5zaW9uKCkubWF0Y2goL14oPzp8XFwuKD86aHRtfGh0bWx8eGh0bWwpKSQvKTtcbiAgICB9XG4gICAgaXNQcmVmaXhlZEJ5KGxvY2F0aW9uKSB7XG4gICAgICAgIGNvbnN0IHByZWZpeFVSTCA9IGdldFByZWZpeFVSTChsb2NhdGlvbik7XG4gICAgICAgIHJldHVybiB0aGlzLmlzRXF1YWxUbyhsb2NhdGlvbikgfHwgc3RyaW5nU3RhcnRzV2l0aCh0aGlzLmFic29sdXRlVVJMLCBwcmVmaXhVUkwpO1xuICAgIH1cbiAgICBpc0VxdWFsVG8obG9jYXRpb24pIHtcbiAgICAgICAgcmV0dXJuIGxvY2F0aW9uICYmIHRoaXMuYWJzb2x1dGVVUkwgPT09IGxvY2F0aW9uLmFic29sdXRlVVJMO1xuICAgIH1cbiAgICB0b0NhY2hlS2V5KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5yZXF1ZXN0VVJMO1xuICAgIH1cbiAgICB0b0pTT04oKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFic29sdXRlVVJMO1xuICAgIH1cbiAgICB0b1N0cmluZygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYWJzb2x1dGVVUkw7XG4gICAgfVxuICAgIHZhbHVlT2YoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFic29sdXRlVVJMO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGdldFByZWZpeFVSTChsb2NhdGlvbikge1xuICAgIHJldHVybiBhZGRUcmFpbGluZ1NsYXNoKGxvY2F0aW9uLmdldE9yaWdpbigpICsgbG9jYXRpb24uZ2V0UGF0aCgpKTtcbn1cbmZ1bmN0aW9uIGFkZFRyYWlsaW5nU2xhc2godXJsKSB7XG4gICAgcmV0dXJuIHN0cmluZ0VuZHNXaXRoKHVybCwgXCIvXCIpID8gdXJsIDogdXJsICsgXCIvXCI7XG59XG5mdW5jdGlvbiBzdHJpbmdTdGFydHNXaXRoKHN0cmluZywgcHJlZml4KSB7XG4gICAgcmV0dXJuIHN0cmluZy5zbGljZSgwLCBwcmVmaXgubGVuZ3RoKSA9PT0gcHJlZml4O1xufVxuZnVuY3Rpb24gc3RyaW5nRW5kc1dpdGgoc3RyaW5nLCBzdWZmaXgpIHtcbiAgICByZXR1cm4gc3RyaW5nLnNsaWNlKC1zdWZmaXgubGVuZ3RoKSA9PT0gc3VmZml4O1xufVxuXG5jbGFzcyBGZXRjaFJlc3BvbnNlIHtcbiAgICBjb25zdHJ1Y3RvcihyZXNwb25zZSkge1xuICAgICAgICB0aGlzLnJlc3BvbnNlID0gcmVzcG9uc2U7XG4gICAgfVxuICAgIGdldCBzdWNjZWVkZWQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnJlc3BvbnNlLm9rO1xuICAgIH1cbiAgICBnZXQgZmFpbGVkKCkge1xuICAgICAgICByZXR1cm4gIXRoaXMuc3VjY2VlZGVkO1xuICAgIH1cbiAgICBnZXQgcmVkaXJlY3RlZCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMucmVzcG9uc2UucmVkaXJlY3RlZDtcbiAgICB9XG4gICAgZ2V0IGxvY2F0aW9uKCkge1xuICAgICAgICByZXR1cm4gTG9jYXRpb24ud3JhcCh0aGlzLnJlc3BvbnNlLnVybCk7XG4gICAgfVxuICAgIGdldCBpc0hUTUwoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmNvbnRlbnRUeXBlICYmIHRoaXMuY29udGVudFR5cGUubWF0Y2goL150ZXh0XFwvaHRtbHxeYXBwbGljYXRpb25cXC94aHRtbFxcK3htbC8pO1xuICAgIH1cbiAgICBnZXQgc3RhdHVzQ29kZSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMucmVzcG9uc2Uuc3RhdHVzO1xuICAgIH1cbiAgICBnZXQgY29udGVudFR5cGUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmhlYWRlcihcIkNvbnRlbnQtVHlwZVwiKTtcbiAgICB9XG4gICAgZ2V0IHJlc3BvbnNlVGV4dCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMucmVzcG9uc2UudGV4dCgpO1xuICAgIH1cbiAgICBnZXQgcmVzcG9uc2VIVE1MKCkge1xuICAgICAgICBpZiAodGhpcy5pc0hUTUwpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnJlc3BvbnNlLnRleHQoKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBQcm9taXNlLnJlc29sdmUodW5kZWZpbmVkKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBoZWFkZXIobmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5yZXNwb25zZS5oZWFkZXJzLmdldChuYW1lKTtcbiAgICB9XG59XG5cbmZ1bmN0aW9uIGRpc3BhdGNoKGV2ZW50TmFtZSwgeyB0YXJnZXQsIGNhbmNlbGFibGUsIGRldGFpbCB9ID0ge30pIHtcbiAgICBjb25zdCBldmVudCA9IG5ldyBDdXN0b21FdmVudChldmVudE5hbWUsIHsgY2FuY2VsYWJsZSwgYnViYmxlczogdHJ1ZSwgZGV0YWlsIH0pO1xuICAgIHZvaWQgKHRhcmdldCB8fCBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQpLmRpc3BhdGNoRXZlbnQoZXZlbnQpO1xuICAgIHJldHVybiBldmVudDtcbn1cbmZ1bmN0aW9uIG5leHRBbmltYXRpb25GcmFtZSgpIHtcbiAgICByZXR1cm4gbmV3IFByb21pc2UocmVzb2x2ZSA9PiByZXF1ZXN0QW5pbWF0aW9uRnJhbWUoKCkgPT4gcmVzb2x2ZSgpKSk7XG59XG5mdW5jdGlvbiBuZXh0TWljcm90YXNrKCkge1xuICAgIHJldHVybiBQcm9taXNlLnJlc29sdmUoKTtcbn1cbmZ1bmN0aW9uIHVuaW5kZW50KHN0cmluZ3MsIC4uLnZhbHVlcykge1xuICAgIGNvbnN0IGxpbmVzID0gaW50ZXJwb2xhdGUoc3RyaW5ncywgdmFsdWVzKS5yZXBsYWNlKC9eXFxuLywgXCJcIikuc3BsaXQoXCJcXG5cIik7XG4gICAgY29uc3QgbWF0Y2ggPSBsaW5lc1swXS5tYXRjaCgvXlxccysvKTtcbiAgICBjb25zdCBpbmRlbnQgPSBtYXRjaCA/IG1hdGNoWzBdLmxlbmd0aCA6IDA7XG4gICAgcmV0dXJuIGxpbmVzLm1hcChsaW5lID0+IGxpbmUuc2xpY2UoaW5kZW50KSkuam9pbihcIlxcblwiKTtcbn1cbmZ1bmN0aW9uIGludGVycG9sYXRlKHN0cmluZ3MsIHZhbHVlcykge1xuICAgIHJldHVybiBzdHJpbmdzLnJlZHVjZSgocmVzdWx0LCBzdHJpbmcsIGkpID0+IHtcbiAgICAgICAgY29uc3QgdmFsdWUgPSB2YWx1ZXNbaV0gPT0gdW5kZWZpbmVkID8gXCJcIiA6IHZhbHVlc1tpXTtcbiAgICAgICAgcmV0dXJuIHJlc3VsdCArIHN0cmluZyArIHZhbHVlO1xuICAgIH0sIFwiXCIpO1xufVxuZnVuY3Rpb24gdXVpZCgpIHtcbiAgICByZXR1cm4gQXJyYXkuYXBwbHkobnVsbCwgeyBsZW5ndGg6IDM2IH0pLm1hcCgoXywgaSkgPT4ge1xuICAgICAgICBpZiAoaSA9PSA4IHx8IGkgPT0gMTMgfHwgaSA9PSAxOCB8fCBpID09IDIzKSB7XG4gICAgICAgICAgICByZXR1cm4gXCItXCI7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAoaSA9PSAxNCkge1xuICAgICAgICAgICAgcmV0dXJuIFwiNFwiO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKGkgPT0gMTkpIHtcbiAgICAgICAgICAgIHJldHVybiAoTWF0aC5mbG9vcihNYXRoLnJhbmRvbSgpICogNCkgKyA4KS50b1N0cmluZygxNik7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gTWF0aC5mbG9vcihNYXRoLnJhbmRvbSgpICogMTUpLnRvU3RyaW5nKDE2KTtcbiAgICAgICAgfVxuICAgIH0pLmpvaW4oXCJcIik7XG59XG5cbnZhciBGZXRjaE1ldGhvZDtcbihmdW5jdGlvbiAoRmV0Y2hNZXRob2QpIHtcbiAgICBGZXRjaE1ldGhvZFtGZXRjaE1ldGhvZFtcImdldFwiXSA9IDBdID0gXCJnZXRcIjtcbiAgICBGZXRjaE1ldGhvZFtGZXRjaE1ldGhvZFtcInBvc3RcIl0gPSAxXSA9IFwicG9zdFwiO1xuICAgIEZldGNoTWV0aG9kW0ZldGNoTWV0aG9kW1wicHV0XCJdID0gMl0gPSBcInB1dFwiO1xuICAgIEZldGNoTWV0aG9kW0ZldGNoTWV0aG9kW1wicGF0Y2hcIl0gPSAzXSA9IFwicGF0Y2hcIjtcbiAgICBGZXRjaE1ldGhvZFtGZXRjaE1ldGhvZFtcImRlbGV0ZVwiXSA9IDRdID0gXCJkZWxldGVcIjtcbn0pKEZldGNoTWV0aG9kIHx8IChGZXRjaE1ldGhvZCA9IHt9KSk7XG5mdW5jdGlvbiBmZXRjaE1ldGhvZEZyb21TdHJpbmcobWV0aG9kKSB7XG4gICAgc3dpdGNoIChtZXRob2QudG9Mb3dlckNhc2UoKSkge1xuICAgICAgICBjYXNlIFwiZ2V0XCI6IHJldHVybiBGZXRjaE1ldGhvZC5nZXQ7XG4gICAgICAgIGNhc2UgXCJwb3N0XCI6IHJldHVybiBGZXRjaE1ldGhvZC5wb3N0O1xuICAgICAgICBjYXNlIFwicHV0XCI6IHJldHVybiBGZXRjaE1ldGhvZC5wdXQ7XG4gICAgICAgIGNhc2UgXCJwYXRjaFwiOiByZXR1cm4gRmV0Y2hNZXRob2QucGF0Y2g7XG4gICAgICAgIGNhc2UgXCJkZWxldGVcIjogcmV0dXJuIEZldGNoTWV0aG9kLmRlbGV0ZTtcbiAgICB9XG59XG5jbGFzcyBGZXRjaFJlcXVlc3Qge1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlLCBtZXRob2QsIGxvY2F0aW9uLCBib2R5KSB7XG4gICAgICAgIHRoaXMuYWJvcnRDb250cm9sbGVyID0gbmV3IEFib3J0Q29udHJvbGxlcjtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLm1ldGhvZCA9IG1ldGhvZDtcbiAgICAgICAgdGhpcy5sb2NhdGlvbiA9IGxvY2F0aW9uO1xuICAgICAgICB0aGlzLmJvZHkgPSBib2R5O1xuICAgIH1cbiAgICBnZXQgdXJsKCkge1xuICAgICAgICBjb25zdCB1cmwgPSB0aGlzLmxvY2F0aW9uLmFic29sdXRlVVJMO1xuICAgICAgICBjb25zdCBxdWVyeSA9IHRoaXMucGFyYW1zLnRvU3RyaW5nKCk7XG4gICAgICAgIGlmICh0aGlzLmlzSWRlbXBvdGVudCAmJiBxdWVyeS5sZW5ndGgpIHtcbiAgICAgICAgICAgIHJldHVybiBbdXJsLCBxdWVyeV0uam9pbih1cmwuaW5jbHVkZXMoXCI/XCIpID8gXCImXCIgOiBcIj9cIik7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gdXJsO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldCBwYXJhbXMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmVudHJpZXMucmVkdWNlKChwYXJhbXMsIFtuYW1lLCB2YWx1ZV0pID0+IHtcbiAgICAgICAgICAgIHBhcmFtcy5hcHBlbmQobmFtZSwgdmFsdWUudG9TdHJpbmcoKSk7XG4gICAgICAgICAgICByZXR1cm4gcGFyYW1zO1xuICAgICAgICB9LCBuZXcgVVJMU2VhcmNoUGFyYW1zKTtcbiAgICB9XG4gICAgZ2V0IGVudHJpZXMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmJvZHkgPyBBcnJheS5mcm9tKHRoaXMuYm9keS5lbnRyaWVzKCkpIDogW107XG4gICAgfVxuICAgIGNhbmNlbCgpIHtcbiAgICAgICAgdGhpcy5hYm9ydENvbnRyb2xsZXIuYWJvcnQoKTtcbiAgICB9XG4gICAgYXN5bmMgcGVyZm9ybSgpIHtcbiAgICAgICAgY29uc3QgeyBmZXRjaE9wdGlvbnMgfSA9IHRoaXM7XG4gICAgICAgIGRpc3BhdGNoKFwidHVyYm86YmVmb3JlLWZldGNoLXJlcXVlc3RcIiwgeyBkZXRhaWw6IHsgZmV0Y2hPcHRpb25zIH0gfSk7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnJlcXVlc3RTdGFydGVkKHRoaXMpO1xuICAgICAgICAgICAgY29uc3QgcmVzcG9uc2UgPSBhd2FpdCBmZXRjaCh0aGlzLnVybCwgZmV0Y2hPcHRpb25zKTtcbiAgICAgICAgICAgIHJldHVybiBhd2FpdCB0aGlzLnJlY2VpdmUocmVzcG9uc2UpO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5yZXF1ZXN0RXJyb3JlZCh0aGlzLCBlcnJvcik7XG4gICAgICAgICAgICB0aHJvdyBlcnJvcjtcbiAgICAgICAgfVxuICAgICAgICBmaW5hbGx5IHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUucmVxdWVzdEZpbmlzaGVkKHRoaXMpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGFzeW5jIHJlY2VpdmUocmVzcG9uc2UpIHtcbiAgICAgICAgY29uc3QgZmV0Y2hSZXNwb25zZSA9IG5ldyBGZXRjaFJlc3BvbnNlKHJlc3BvbnNlKTtcbiAgICAgICAgY29uc3QgZXZlbnQgPSBkaXNwYXRjaChcInR1cmJvOmJlZm9yZS1mZXRjaC1yZXNwb25zZVwiLCB7IGNhbmNlbGFibGU6IHRydWUsIGRldGFpbDogeyBmZXRjaFJlc3BvbnNlIH0gfSk7XG4gICAgICAgIGlmIChldmVudC5kZWZhdWx0UHJldmVudGVkKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnJlcXVlc3RQcmV2ZW50ZWRIYW5kbGluZ1Jlc3BvbnNlKHRoaXMsIGZldGNoUmVzcG9uc2UpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKGZldGNoUmVzcG9uc2Uuc3VjY2VlZGVkKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnJlcXVlc3RTdWNjZWVkZWRXaXRoUmVzcG9uc2UodGhpcywgZmV0Y2hSZXNwb25zZSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnJlcXVlc3RGYWlsZWRXaXRoUmVzcG9uc2UodGhpcywgZmV0Y2hSZXNwb25zZSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGZldGNoUmVzcG9uc2U7XG4gICAgfVxuICAgIGdldCBmZXRjaE9wdGlvbnMoKSB7XG4gICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICBtZXRob2Q6IEZldGNoTWV0aG9kW3RoaXMubWV0aG9kXS50b1VwcGVyQ2FzZSgpLFxuICAgICAgICAgICAgY3JlZGVudGlhbHM6IFwic2FtZS1vcmlnaW5cIixcbiAgICAgICAgICAgIGhlYWRlcnM6IHRoaXMuaGVhZGVycyxcbiAgICAgICAgICAgIHJlZGlyZWN0OiBcImZvbGxvd1wiLFxuICAgICAgICAgICAgYm9keTogdGhpcy5pc0lkZW1wb3RlbnQgPyB1bmRlZmluZWQgOiB0aGlzLmJvZHksXG4gICAgICAgICAgICBzaWduYWw6IHRoaXMuYWJvcnRTaWduYWxcbiAgICAgICAgfTtcbiAgICB9XG4gICAgZ2V0IGlzSWRlbXBvdGVudCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMubWV0aG9kID09IEZldGNoTWV0aG9kLmdldDtcbiAgICB9XG4gICAgZ2V0IGhlYWRlcnMoKSB7XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKHsgXCJBY2NlcHRcIjogXCJ0ZXh0L2h0bWwsIGFwcGxpY2F0aW9uL3hodG1sK3htbFwiIH0sIHRoaXMuYWRkaXRpb25hbEhlYWRlcnMpO1xuICAgIH1cbiAgICBnZXQgYWRkaXRpb25hbEhlYWRlcnMoKSB7XG4gICAgICAgIGlmICh0eXBlb2YgdGhpcy5kZWxlZ2F0ZS5hZGRpdGlvbmFsSGVhZGVyc0ZvclJlcXVlc3QgPT0gXCJmdW5jdGlvblwiKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5kZWxlZ2F0ZS5hZGRpdGlvbmFsSGVhZGVyc0ZvclJlcXVlc3QodGhpcyk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4ge307XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0IGFib3J0U2lnbmFsKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hYm9ydENvbnRyb2xsZXIuc2lnbmFsO1xuICAgIH1cbn1cblxuY2xhc3MgRm9ybUludGVyY2VwdG9yIHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSwgZWxlbWVudCkge1xuICAgICAgICB0aGlzLnN1Ym1pdEJ1YmJsZWQgPSAoKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBpZiAoZXZlbnQudGFyZ2V0IGluc3RhbmNlb2YgSFRNTEZvcm1FbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgY29uc3QgZm9ybSA9IGV2ZW50LnRhcmdldDtcbiAgICAgICAgICAgICAgICBjb25zdCBzdWJtaXR0ZXIgPSBldmVudC5zdWJtaXR0ZXIgfHwgdW5kZWZpbmVkO1xuICAgICAgICAgICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLnNob3VsZEludGVyY2VwdEZvcm1TdWJtaXNzaW9uKGZvcm0sIHN1Ym1pdHRlcikpIHtcbiAgICAgICAgICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICAgICAgICAgZXZlbnQuc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uKCk7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZm9ybVN1Ym1pc3Npb25JbnRlcmNlcHRlZChmb3JtLCBzdWJtaXR0ZXIpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICB9XG4gICAgc3RhcnQoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudC5hZGRFdmVudExpc3RlbmVyKFwic3VibWl0XCIsIHRoaXMuc3VibWl0QnViYmxlZCk7XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudC5yZW1vdmVFdmVudExpc3RlbmVyKFwic3VibWl0XCIsIHRoaXMuc3VibWl0QnViYmxlZCk7XG4gICAgfVxufVxuXG52YXIgRm9ybVN1Ym1pc3Npb25TdGF0ZTtcbihmdW5jdGlvbiAoRm9ybVN1Ym1pc3Npb25TdGF0ZSkge1xuICAgIEZvcm1TdWJtaXNzaW9uU3RhdGVbRm9ybVN1Ym1pc3Npb25TdGF0ZVtcImluaXRpYWxpemVkXCJdID0gMF0gPSBcImluaXRpYWxpemVkXCI7XG4gICAgRm9ybVN1Ym1pc3Npb25TdGF0ZVtGb3JtU3VibWlzc2lvblN0YXRlW1wicmVxdWVzdGluZ1wiXSA9IDFdID0gXCJyZXF1ZXN0aW5nXCI7XG4gICAgRm9ybVN1Ym1pc3Npb25TdGF0ZVtGb3JtU3VibWlzc2lvblN0YXRlW1wid2FpdGluZ1wiXSA9IDJdID0gXCJ3YWl0aW5nXCI7XG4gICAgRm9ybVN1Ym1pc3Npb25TdGF0ZVtGb3JtU3VibWlzc2lvblN0YXRlW1wicmVjZWl2aW5nXCJdID0gM10gPSBcInJlY2VpdmluZ1wiO1xuICAgIEZvcm1TdWJtaXNzaW9uU3RhdGVbRm9ybVN1Ym1pc3Npb25TdGF0ZVtcInN0b3BwaW5nXCJdID0gNF0gPSBcInN0b3BwaW5nXCI7XG4gICAgRm9ybVN1Ym1pc3Npb25TdGF0ZVtGb3JtU3VibWlzc2lvblN0YXRlW1wic3RvcHBlZFwiXSA9IDVdID0gXCJzdG9wcGVkXCI7XG59KShGb3JtU3VibWlzc2lvblN0YXRlIHx8IChGb3JtU3VibWlzc2lvblN0YXRlID0ge30pKTtcbmNsYXNzIEZvcm1TdWJtaXNzaW9uIHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSwgZm9ybUVsZW1lbnQsIHN1Ym1pdHRlciwgbXVzdFJlZGlyZWN0ID0gZmFsc2UpIHtcbiAgICAgICAgdGhpcy5zdGF0ZSA9IEZvcm1TdWJtaXNzaW9uU3RhdGUuaW5pdGlhbGl6ZWQ7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5mb3JtRWxlbWVudCA9IGZvcm1FbGVtZW50O1xuICAgICAgICB0aGlzLmZvcm1EYXRhID0gYnVpbGRGb3JtRGF0YShmb3JtRWxlbWVudCwgc3VibWl0dGVyKTtcbiAgICAgICAgdGhpcy5zdWJtaXR0ZXIgPSBzdWJtaXR0ZXI7XG4gICAgICAgIHRoaXMuZmV0Y2hSZXF1ZXN0ID0gbmV3IEZldGNoUmVxdWVzdCh0aGlzLCB0aGlzLm1ldGhvZCwgdGhpcy5sb2NhdGlvbiwgdGhpcy5mb3JtRGF0YSk7XG4gICAgICAgIHRoaXMubXVzdFJlZGlyZWN0ID0gbXVzdFJlZGlyZWN0O1xuICAgIH1cbiAgICBnZXQgbWV0aG9kKCkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIGNvbnN0IG1ldGhvZCA9ICgoX2EgPSB0aGlzLnN1Ym1pdHRlcikgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLmdldEF0dHJpYnV0ZShcImZvcm1tZXRob2RcIikpIHx8IHRoaXMuZm9ybUVsZW1lbnQubWV0aG9kO1xuICAgICAgICByZXR1cm4gZmV0Y2hNZXRob2RGcm9tU3RyaW5nKG1ldGhvZC50b0xvd2VyQ2FzZSgpKSB8fCBGZXRjaE1ldGhvZC5nZXQ7XG4gICAgfVxuICAgIGdldCBhY3Rpb24oKSB7XG4gICAgICAgIHZhciBfYTtcbiAgICAgICAgcmV0dXJuICgoX2EgPSB0aGlzLnN1Ym1pdHRlcikgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLmdldEF0dHJpYnV0ZShcImZvcm1hY3Rpb25cIikpIHx8IHRoaXMuZm9ybUVsZW1lbnQuYWN0aW9uO1xuICAgIH1cbiAgICBnZXQgbG9jYXRpb24oKSB7XG4gICAgICAgIHJldHVybiBMb2NhdGlvbi53cmFwKHRoaXMuYWN0aW9uKTtcbiAgICB9XG4gICAgYXN5bmMgc3RhcnQoKSB7XG4gICAgICAgIGNvbnN0IHsgaW5pdGlhbGl6ZWQsIHJlcXVlc3RpbmcgfSA9IEZvcm1TdWJtaXNzaW9uU3RhdGU7XG4gICAgICAgIGlmICh0aGlzLnN0YXRlID09IGluaXRpYWxpemVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXRlID0gcmVxdWVzdGluZztcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmZldGNoUmVxdWVzdC5wZXJmb3JtKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgY29uc3QgeyBzdG9wcGluZywgc3RvcHBlZCB9ID0gRm9ybVN1Ym1pc3Npb25TdGF0ZTtcbiAgICAgICAgaWYgKHRoaXMuc3RhdGUgIT0gc3RvcHBpbmcgJiYgdGhpcy5zdGF0ZSAhPSBzdG9wcGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXRlID0gc3RvcHBpbmc7XG4gICAgICAgICAgICB0aGlzLmZldGNoUmVxdWVzdC5jYW5jZWwoKTtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIGFkZGl0aW9uYWxIZWFkZXJzRm9yUmVxdWVzdChyZXF1ZXN0KSB7XG4gICAgICAgIGNvbnN0IGhlYWRlcnMgPSB7fTtcbiAgICAgICAgaWYgKHRoaXMubWV0aG9kICE9IEZldGNoTWV0aG9kLmdldCkge1xuICAgICAgICAgICAgY29uc3QgdG9rZW4gPSBnZXRDb29raWVWYWx1ZShnZXRNZXRhQ29udGVudChcImNzcmYtcGFyYW1cIikpIHx8IGdldE1ldGFDb250ZW50KFwiY3NyZi10b2tlblwiKTtcbiAgICAgICAgICAgIGlmICh0b2tlbikge1xuICAgICAgICAgICAgICAgIGhlYWRlcnNbXCJYLUNTUkYtVG9rZW5cIl0gPSB0b2tlbjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gaGVhZGVycztcbiAgICB9XG4gICAgcmVxdWVzdFN0YXJ0ZWQocmVxdWVzdCkge1xuICAgICAgICB0aGlzLnN0YXRlID0gRm9ybVN1Ym1pc3Npb25TdGF0ZS53YWl0aW5nO1xuICAgICAgICBkaXNwYXRjaChcInR1cmJvOnN1Ym1pdC1zdGFydFwiLCB7IHRhcmdldDogdGhpcy5mb3JtRWxlbWVudCwgZGV0YWlsOiB7IGZvcm1TdWJtaXNzaW9uOiB0aGlzIH0gfSk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUuZm9ybVN1Ym1pc3Npb25TdGFydGVkKHRoaXMpO1xuICAgIH1cbiAgICByZXF1ZXN0UHJldmVudGVkSGFuZGxpbmdSZXNwb25zZShyZXF1ZXN0LCByZXNwb25zZSkge1xuICAgICAgICB0aGlzLnJlc3VsdCA9IHsgc3VjY2VzczogcmVzcG9uc2Uuc3VjY2VlZGVkLCBmZXRjaFJlc3BvbnNlOiByZXNwb25zZSB9O1xuICAgIH1cbiAgICByZXF1ZXN0U3VjY2VlZGVkV2l0aFJlc3BvbnNlKHJlcXVlc3QsIHJlc3BvbnNlKSB7XG4gICAgICAgIGlmICh0aGlzLnJlcXVlc3RNdXN0UmVkaXJlY3QocmVxdWVzdCkgJiYgIXJlc3BvbnNlLnJlZGlyZWN0ZWQpIHtcbiAgICAgICAgICAgIGNvbnN0IGVycm9yID0gbmV3IEVycm9yKFwiRm9ybSByZXNwb25zZXMgbXVzdCByZWRpcmVjdCB0byBhbm90aGVyIGxvY2F0aW9uXCIpO1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5mb3JtU3VibWlzc2lvbkVycm9yZWQodGhpcywgZXJyb3IpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5zdGF0ZSA9IEZvcm1TdWJtaXNzaW9uU3RhdGUucmVjZWl2aW5nO1xuICAgICAgICAgICAgdGhpcy5yZXN1bHQgPSB7IHN1Y2Nlc3M6IHRydWUsIGZldGNoUmVzcG9uc2U6IHJlc3BvbnNlIH07XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmZvcm1TdWJtaXNzaW9uU3VjY2VlZGVkV2l0aFJlc3BvbnNlKHRoaXMsIHJlc3BvbnNlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZXF1ZXN0RmFpbGVkV2l0aFJlc3BvbnNlKHJlcXVlc3QsIHJlc3BvbnNlKSB7XG4gICAgICAgIHRoaXMucmVzdWx0ID0geyBzdWNjZXNzOiBmYWxzZSwgZmV0Y2hSZXNwb25zZTogcmVzcG9uc2UgfTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS5mb3JtU3VibWlzc2lvbkZhaWxlZFdpdGhSZXNwb25zZSh0aGlzLCByZXNwb25zZSk7XG4gICAgfVxuICAgIHJlcXVlc3RFcnJvcmVkKHJlcXVlc3QsIGVycm9yKSB7XG4gICAgICAgIHRoaXMucmVzdWx0ID0geyBzdWNjZXNzOiBmYWxzZSwgZXJyb3IgfTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS5mb3JtU3VibWlzc2lvbkVycm9yZWQodGhpcywgZXJyb3IpO1xuICAgIH1cbiAgICByZXF1ZXN0RmluaXNoZWQocmVxdWVzdCkge1xuICAgICAgICB0aGlzLnN0YXRlID0gRm9ybVN1Ym1pc3Npb25TdGF0ZS5zdG9wcGVkO1xuICAgICAgICBkaXNwYXRjaChcInR1cmJvOnN1Ym1pdC1lbmRcIiwgeyB0YXJnZXQ6IHRoaXMuZm9ybUVsZW1lbnQsIGRldGFpbDogT2JqZWN0LmFzc2lnbih7IGZvcm1TdWJtaXNzaW9uOiB0aGlzIH0sIHRoaXMucmVzdWx0KSB9KTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS5mb3JtU3VibWlzc2lvbkZpbmlzaGVkKHRoaXMpO1xuICAgIH1cbiAgICByZXF1ZXN0TXVzdFJlZGlyZWN0KHJlcXVlc3QpIHtcbiAgICAgICAgcmV0dXJuICFyZXF1ZXN0LmlzSWRlbXBvdGVudCAmJiB0aGlzLm11c3RSZWRpcmVjdDtcbiAgICB9XG59XG5mdW5jdGlvbiBidWlsZEZvcm1EYXRhKGZvcm1FbGVtZW50LCBzdWJtaXR0ZXIpIHtcbiAgICBjb25zdCBmb3JtRGF0YSA9IG5ldyBGb3JtRGF0YShmb3JtRWxlbWVudCk7XG4gICAgY29uc3QgbmFtZSA9IHN1Ym1pdHRlciA9PT0gbnVsbCB8fCBzdWJtaXR0ZXIgPT09IHZvaWQgMCA/IHZvaWQgMCA6IHN1Ym1pdHRlci5nZXRBdHRyaWJ1dGUoXCJuYW1lXCIpO1xuICAgIGNvbnN0IHZhbHVlID0gc3VibWl0dGVyID09PSBudWxsIHx8IHN1Ym1pdHRlciA9PT0gdm9pZCAwID8gdm9pZCAwIDogc3VibWl0dGVyLmdldEF0dHJpYnV0ZShcInZhbHVlXCIpO1xuICAgIGlmIChuYW1lICYmIGZvcm1EYXRhLmdldChuYW1lKSAhPSB2YWx1ZSkge1xuICAgICAgICBmb3JtRGF0YS5hcHBlbmQobmFtZSwgdmFsdWUgfHwgXCJcIik7XG4gICAgfVxuICAgIHJldHVybiBmb3JtRGF0YTtcbn1cbmZ1bmN0aW9uIGdldENvb2tpZVZhbHVlKGNvb2tpZU5hbWUpIHtcbiAgICBpZiAoY29va2llTmFtZSAhPSBudWxsKSB7XG4gICAgICAgIGNvbnN0IGNvb2tpZXMgPSBkb2N1bWVudC5jb29raWUgPyBkb2N1bWVudC5jb29raWUuc3BsaXQoXCI7IFwiKSA6IFtdO1xuICAgICAgICBjb25zdCBjb29raWUgPSBjb29raWVzLmZpbmQoKGNvb2tpZSkgPT4gY29va2llLnN0YXJ0c1dpdGgoY29va2llTmFtZSkpO1xuICAgICAgICBpZiAoY29va2llKSB7XG4gICAgICAgICAgICBjb25zdCB2YWx1ZSA9IGNvb2tpZS5zcGxpdChcIj1cIikuc2xpY2UoMSkuam9pbihcIj1cIik7XG4gICAgICAgICAgICByZXR1cm4gdmFsdWUgPyBkZWNvZGVVUklDb21wb25lbnQodmFsdWUpIDogdW5kZWZpbmVkO1xuICAgICAgICB9XG4gICAgfVxufVxuZnVuY3Rpb24gZ2V0TWV0YUNvbnRlbnQobmFtZSkge1xuICAgIGNvbnN0IGVsZW1lbnQgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKGBtZXRhW25hbWU9XCIke25hbWV9XCJdYCk7XG4gICAgcmV0dXJuIGVsZW1lbnQgJiYgZWxlbWVudC5jb250ZW50O1xufVxuXG5jbGFzcyBMaW5rSW50ZXJjZXB0b3Ige1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlLCBlbGVtZW50KSB7XG4gICAgICAgIHRoaXMuY2xpY2tCdWJibGVkID0gKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBpZiAodGhpcy5yZXNwb25kc1RvRXZlbnRUYXJnZXQoZXZlbnQudGFyZ2V0KSkge1xuICAgICAgICAgICAgICAgIHRoaXMuY2xpY2tFdmVudCA9IGV2ZW50O1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgZGVsZXRlIHRoaXMuY2xpY2tFdmVudDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5saW5rQ2xpY2tlZCA9ICgoZXZlbnQpID0+IHtcbiAgICAgICAgICAgIGlmICh0aGlzLmNsaWNrRXZlbnQgJiYgdGhpcy5yZXNwb25kc1RvRXZlbnRUYXJnZXQoZXZlbnQudGFyZ2V0KSAmJiBldmVudC50YXJnZXQgaW5zdGFuY2VvZiBFbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuc2hvdWxkSW50ZXJjZXB0TGlua0NsaWNrKGV2ZW50LnRhcmdldCwgZXZlbnQuZGV0YWlsLnVybCkpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5jbGlja0V2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUubGlua0NsaWNrSW50ZXJjZXB0ZWQoZXZlbnQudGFyZ2V0LCBldmVudC5kZXRhaWwudXJsKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBkZWxldGUgdGhpcy5jbGlja0V2ZW50O1xuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy53aWxsVmlzaXQgPSAoKSA9PiB7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy5jbGlja0V2ZW50O1xuICAgICAgICB9O1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICB0aGlzLmVsZW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsIHRoaXMuY2xpY2tCdWJibGVkKTtcbiAgICAgICAgZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcInR1cmJvOmNsaWNrXCIsIHRoaXMubGlua0NsaWNrZWQpO1xuICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKFwidHVyYm86YmVmb3JlLXZpc2l0XCIsIHRoaXMud2lsbFZpc2l0KTtcbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoXCJjbGlja1wiLCB0aGlzLmNsaWNrQnViYmxlZCk7XG4gICAgICAgIGRvY3VtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoXCJ0dXJibzpjbGlja1wiLCB0aGlzLmxpbmtDbGlja2VkKTtcbiAgICAgICAgZG9jdW1lbnQucmVtb3ZlRXZlbnRMaXN0ZW5lcihcInR1cmJvOmJlZm9yZS12aXNpdFwiLCB0aGlzLndpbGxWaXNpdCk7XG4gICAgfVxuICAgIHJlc3BvbmRzVG9FdmVudFRhcmdldCh0YXJnZXQpIHtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IHRhcmdldCBpbnN0YW5jZW9mIEVsZW1lbnRcbiAgICAgICAgICAgID8gdGFyZ2V0XG4gICAgICAgICAgICA6IHRhcmdldCBpbnN0YW5jZW9mIE5vZGVcbiAgICAgICAgICAgICAgICA/IHRhcmdldC5wYXJlbnRFbGVtZW50XG4gICAgICAgICAgICAgICAgOiBudWxsO1xuICAgICAgICByZXR1cm4gZWxlbWVudCAmJiBlbGVtZW50LmNsb3Nlc3QoXCJ0dXJiby1mcmFtZSwgaHRtbFwiKSA9PSB0aGlzLmVsZW1lbnQ7XG4gICAgfVxufVxuXG5jbGFzcyBGcmFtZUNvbnRyb2xsZXIge1xuICAgIGNvbnN0cnVjdG9yKGVsZW1lbnQpIHtcbiAgICAgICAgdGhpcy5yZXNvbHZlVmlzaXRQcm9taXNlID0gKCkgPT4geyB9O1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBlbGVtZW50O1xuICAgICAgICB0aGlzLmxpbmtJbnRlcmNlcHRvciA9IG5ldyBMaW5rSW50ZXJjZXB0b3IodGhpcywgdGhpcy5lbGVtZW50KTtcbiAgICAgICAgdGhpcy5mb3JtSW50ZXJjZXB0b3IgPSBuZXcgRm9ybUludGVyY2VwdG9yKHRoaXMsIHRoaXMuZWxlbWVudCk7XG4gICAgfVxuICAgIGNvbm5lY3QoKSB7XG4gICAgICAgIHRoaXMubGlua0ludGVyY2VwdG9yLnN0YXJ0KCk7XG4gICAgICAgIHRoaXMuZm9ybUludGVyY2VwdG9yLnN0YXJ0KCk7XG4gICAgfVxuICAgIGRpc2Nvbm5lY3QoKSB7XG4gICAgICAgIHRoaXMubGlua0ludGVyY2VwdG9yLnN0b3AoKTtcbiAgICAgICAgdGhpcy5mb3JtSW50ZXJjZXB0b3Iuc3RvcCgpO1xuICAgIH1cbiAgICBzaG91bGRJbnRlcmNlcHRMaW5rQ2xpY2soZWxlbWVudCwgdXJsKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNob3VsZEludGVyY2VwdE5hdmlnYXRpb24oZWxlbWVudCk7XG4gICAgfVxuICAgIGxpbmtDbGlja0ludGVyY2VwdGVkKGVsZW1lbnQsIHVybCkge1xuICAgICAgICB0aGlzLm5hdmlnYXRlRnJhbWUoZWxlbWVudCwgdXJsKTtcbiAgICB9XG4gICAgc2hvdWxkSW50ZXJjZXB0Rm9ybVN1Ym1pc3Npb24oZWxlbWVudCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zaG91bGRJbnRlcmNlcHROYXZpZ2F0aW9uKGVsZW1lbnQpO1xuICAgIH1cbiAgICBmb3JtU3VibWlzc2lvbkludGVyY2VwdGVkKGVsZW1lbnQsIHN1Ym1pdHRlcikge1xuICAgICAgICBpZiAodGhpcy5mb3JtU3VibWlzc2lvbikge1xuICAgICAgICAgICAgdGhpcy5mb3JtU3VibWlzc2lvbi5zdG9wKCk7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5mb3JtU3VibWlzc2lvbiA9IG5ldyBGb3JtU3VibWlzc2lvbih0aGlzLCBlbGVtZW50LCBzdWJtaXR0ZXIpO1xuICAgICAgICBpZiAodGhpcy5mb3JtU3VibWlzc2lvbi5mZXRjaFJlcXVlc3QuaXNJZGVtcG90ZW50KSB7XG4gICAgICAgICAgICB0aGlzLm5hdmlnYXRlRnJhbWUoZWxlbWVudCwgdGhpcy5mb3JtU3VibWlzc2lvbi5mZXRjaFJlcXVlc3QudXJsKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMuZm9ybVN1Ym1pc3Npb24uc3RhcnQoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBhc3luYyB2aXNpdCh1cmwpIHtcbiAgICAgICAgY29uc3QgbG9jYXRpb24gPSBMb2NhdGlvbi53cmFwKHVybCk7XG4gICAgICAgIGNvbnN0IHJlcXVlc3QgPSBuZXcgRmV0Y2hSZXF1ZXN0KHRoaXMsIEZldGNoTWV0aG9kLmdldCwgbG9jYXRpb24pO1xuICAgICAgICByZXR1cm4gbmV3IFByb21pc2UocmVzb2x2ZSA9PiB7XG4gICAgICAgICAgICB0aGlzLnJlc29sdmVWaXNpdFByb21pc2UgPSAoKSA9PiB7XG4gICAgICAgICAgICAgICAgdGhpcy5yZXNvbHZlVmlzaXRQcm9taXNlID0gKCkgPT4geyB9O1xuICAgICAgICAgICAgICAgIHJlc29sdmUoKTtcbiAgICAgICAgICAgIH07XG4gICAgICAgICAgICByZXF1ZXN0LnBlcmZvcm0oKTtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGFkZGl0aW9uYWxIZWFkZXJzRm9yUmVxdWVzdChyZXF1ZXN0KSB7XG4gICAgICAgIHJldHVybiB7IFwiVHVyYm8tRnJhbWVcIjogdGhpcy5pZCB9O1xuICAgIH1cbiAgICByZXF1ZXN0U3RhcnRlZChyZXF1ZXN0KSB7XG4gICAgICAgIHRoaXMuZWxlbWVudC5zZXRBdHRyaWJ1dGUoXCJidXN5XCIsIFwiXCIpO1xuICAgIH1cbiAgICByZXF1ZXN0UHJldmVudGVkSGFuZGxpbmdSZXNwb25zZShyZXF1ZXN0LCByZXNwb25zZSkge1xuICAgICAgICB0aGlzLnJlc29sdmVWaXNpdFByb21pc2UoKTtcbiAgICB9XG4gICAgYXN5bmMgcmVxdWVzdFN1Y2NlZWRlZFdpdGhSZXNwb25zZShyZXF1ZXN0LCByZXNwb25zZSkge1xuICAgICAgICBhd2FpdCB0aGlzLmxvYWRSZXNwb25zZShyZXNwb25zZSk7XG4gICAgICAgIHRoaXMucmVzb2x2ZVZpc2l0UHJvbWlzZSgpO1xuICAgIH1cbiAgICByZXF1ZXN0RmFpbGVkV2l0aFJlc3BvbnNlKHJlcXVlc3QsIHJlc3BvbnNlKSB7XG4gICAgICAgIGNvbnNvbGUuZXJyb3IocmVzcG9uc2UpO1xuICAgICAgICB0aGlzLnJlc29sdmVWaXNpdFByb21pc2UoKTtcbiAgICB9XG4gICAgcmVxdWVzdEVycm9yZWQocmVxdWVzdCwgZXJyb3IpIHtcbiAgICAgICAgY29uc29sZS5lcnJvcihlcnJvcik7XG4gICAgICAgIHRoaXMucmVzb2x2ZVZpc2l0UHJvbWlzZSgpO1xuICAgIH1cbiAgICByZXF1ZXN0RmluaXNoZWQocmVxdWVzdCkge1xuICAgICAgICB0aGlzLmVsZW1lbnQucmVtb3ZlQXR0cmlidXRlKFwiYnVzeVwiKTtcbiAgICB9XG4gICAgZm9ybVN1Ym1pc3Npb25TdGFydGVkKGZvcm1TdWJtaXNzaW9uKSB7XG4gICAgfVxuICAgIGZvcm1TdWJtaXNzaW9uU3VjY2VlZGVkV2l0aFJlc3BvbnNlKGZvcm1TdWJtaXNzaW9uLCByZXNwb25zZSkge1xuICAgICAgICBjb25zdCBmcmFtZSA9IHRoaXMuZmluZEZyYW1lRWxlbWVudChmb3JtU3VibWlzc2lvbi5mb3JtRWxlbWVudCk7XG4gICAgICAgIGZyYW1lLmNvbnRyb2xsZXIubG9hZFJlc3BvbnNlKHJlc3BvbnNlKTtcbiAgICB9XG4gICAgZm9ybVN1Ym1pc3Npb25GYWlsZWRXaXRoUmVzcG9uc2UoZm9ybVN1Ym1pc3Npb24sIGZldGNoUmVzcG9uc2UpIHtcbiAgICB9XG4gICAgZm9ybVN1Ym1pc3Npb25FcnJvcmVkKGZvcm1TdWJtaXNzaW9uLCBlcnJvcikge1xuICAgIH1cbiAgICBmb3JtU3VibWlzc2lvbkZpbmlzaGVkKGZvcm1TdWJtaXNzaW9uKSB7XG4gICAgfVxuICAgIG5hdmlnYXRlRnJhbWUoZWxlbWVudCwgdXJsKSB7XG4gICAgICAgIGNvbnN0IGZyYW1lID0gdGhpcy5maW5kRnJhbWVFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICBmcmFtZS5zcmMgPSB1cmw7XG4gICAgfVxuICAgIGZpbmRGcmFtZUVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIGNvbnN0IGlkID0gZWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJkYXRhLXR1cmJvLWZyYW1lXCIpO1xuICAgICAgICByZXR1cm4gKF9hID0gZ2V0RnJhbWVFbGVtZW50QnlJZChpZCkpICE9PSBudWxsICYmIF9hICE9PSB2b2lkIDAgPyBfYSA6IHRoaXMuZWxlbWVudDtcbiAgICB9XG4gICAgYXN5bmMgbG9hZFJlc3BvbnNlKHJlc3BvbnNlKSB7XG4gICAgICAgIGNvbnN0IGZyYWdtZW50ID0gZnJhZ21lbnRGcm9tSFRNTChhd2FpdCByZXNwb25zZS5yZXNwb25zZUhUTUwpO1xuICAgICAgICBjb25zdCBlbGVtZW50ID0gYXdhaXQgdGhpcy5leHRyYWN0Rm9yZWlnbkZyYW1lRWxlbWVudChmcmFnbWVudCk7XG4gICAgICAgIGlmIChlbGVtZW50KSB7XG4gICAgICAgICAgICBhd2FpdCBuZXh0QW5pbWF0aW9uRnJhbWUoKTtcbiAgICAgICAgICAgIHRoaXMubG9hZEZyYW1lRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgICAgIHRoaXMuc2Nyb2xsRnJhbWVJbnRvVmlldyhlbGVtZW50KTtcbiAgICAgICAgICAgIGF3YWl0IG5leHRBbmltYXRpb25GcmFtZSgpO1xuICAgICAgICAgICAgdGhpcy5mb2N1c0ZpcnN0QXV0b2ZvY3VzYWJsZUVsZW1lbnQoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBhc3luYyBleHRyYWN0Rm9yZWlnbkZyYW1lRWxlbWVudChjb250YWluZXIpIHtcbiAgICAgICAgbGV0IGVsZW1lbnQ7XG4gICAgICAgIGNvbnN0IGlkID0gQ1NTLmVzY2FwZSh0aGlzLmlkKTtcbiAgICAgICAgaWYgKGVsZW1lbnQgPSBhY3RpdmF0ZUVsZW1lbnQoY29udGFpbmVyLnF1ZXJ5U2VsZWN0b3IoYHR1cmJvLWZyYW1lIyR7aWR9YCkpKSB7XG4gICAgICAgICAgICByZXR1cm4gZWxlbWVudDtcbiAgICAgICAgfVxuICAgICAgICBpZiAoZWxlbWVudCA9IGFjdGl2YXRlRWxlbWVudChjb250YWluZXIucXVlcnlTZWxlY3RvcihgdHVyYm8tZnJhbWVbc3JjXVtyZWN1cnNlfj0ke2lkfV1gKSkpIHtcbiAgICAgICAgICAgIGF3YWl0IGVsZW1lbnQubG9hZGVkO1xuICAgICAgICAgICAgcmV0dXJuIGF3YWl0IHRoaXMuZXh0cmFjdEZvcmVpZ25GcmFtZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgbG9hZEZyYW1lRWxlbWVudChmcmFtZUVsZW1lbnQpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICBjb25zdCBkZXN0aW5hdGlvblJhbmdlID0gZG9jdW1lbnQuY3JlYXRlUmFuZ2UoKTtcbiAgICAgICAgZGVzdGluYXRpb25SYW5nZS5zZWxlY3ROb2RlQ29udGVudHModGhpcy5lbGVtZW50KTtcbiAgICAgICAgZGVzdGluYXRpb25SYW5nZS5kZWxldGVDb250ZW50cygpO1xuICAgICAgICBjb25zdCBzb3VyY2VSYW5nZSA9IChfYSA9IGZyYW1lRWxlbWVudC5vd25lckRvY3VtZW50KSA9PT0gbnVsbCB8fCBfYSA9PT0gdm9pZCAwID8gdm9pZCAwIDogX2EuY3JlYXRlUmFuZ2UoKTtcbiAgICAgICAgaWYgKHNvdXJjZVJhbmdlKSB7XG4gICAgICAgICAgICBzb3VyY2VSYW5nZS5zZWxlY3ROb2RlQ29udGVudHMoZnJhbWVFbGVtZW50KTtcbiAgICAgICAgICAgIHRoaXMuZWxlbWVudC5hcHBlbmRDaGlsZChzb3VyY2VSYW5nZS5leHRyYWN0Q29udGVudHMoKSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZm9jdXNGaXJzdEF1dG9mb2N1c2FibGVFbGVtZW50KCkge1xuICAgICAgICBjb25zdCBlbGVtZW50ID0gdGhpcy5maXJzdEF1dG9mb2N1c2FibGVFbGVtZW50O1xuICAgICAgICBpZiAoZWxlbWVudCkge1xuICAgICAgICAgICAgZWxlbWVudC5mb2N1cygpO1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cbiAgICBzY3JvbGxGcmFtZUludG9WaWV3KGZyYW1lKSB7XG4gICAgICAgIGlmICh0aGlzLmVsZW1lbnQuYXV0b3Njcm9sbCB8fCBmcmFtZS5hdXRvc2Nyb2xsKSB7XG4gICAgICAgICAgICBjb25zdCBlbGVtZW50ID0gdGhpcy5lbGVtZW50LmZpcnN0RWxlbWVudENoaWxkO1xuICAgICAgICAgICAgY29uc3QgYmxvY2sgPSByZWFkU2Nyb2xsTG9naWNhbFBvc2l0aW9uKHRoaXMuZWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJkYXRhLWF1dG9zY3JvbGwtYmxvY2tcIiksIFwiZW5kXCIpO1xuICAgICAgICAgICAgaWYgKGVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICBlbGVtZW50LnNjcm9sbEludG9WaWV3KHsgYmxvY2sgfSk7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cbiAgICBzaG91bGRJbnRlcmNlcHROYXZpZ2F0aW9uKGVsZW1lbnQpIHtcbiAgICAgICAgY29uc3QgaWQgPSBlbGVtZW50LmdldEF0dHJpYnV0ZShcImRhdGEtdHVyYm8tZnJhbWVcIikgfHwgdGhpcy5lbGVtZW50LmdldEF0dHJpYnV0ZShcInRhcmdldFwiKTtcbiAgICAgICAgaWYgKCF0aGlzLmVuYWJsZWQgfHwgaWQgPT0gXCJfdG9wXCIpIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoaWQpIHtcbiAgICAgICAgICAgIGNvbnN0IGZyYW1lRWxlbWVudCA9IGdldEZyYW1lRWxlbWVudEJ5SWQoaWQpO1xuICAgICAgICAgICAgaWYgKGZyYW1lRWxlbWVudCkge1xuICAgICAgICAgICAgICAgIHJldHVybiAhZnJhbWVFbGVtZW50LmRpc2FibGVkO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cbiAgICBnZXQgZmlyc3RBdXRvZm9jdXNhYmxlRWxlbWVudCgpIHtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IHRoaXMuZWxlbWVudC5xdWVyeVNlbGVjdG9yKFwiW2F1dG9mb2N1c11cIik7XG4gICAgICAgIHJldHVybiBlbGVtZW50IGluc3RhbmNlb2YgSFRNTEVsZW1lbnQgPyBlbGVtZW50IDogbnVsbDtcbiAgICB9XG4gICAgZ2V0IGlkKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50LmlkO1xuICAgIH1cbiAgICBnZXQgZW5hYmxlZCgpIHtcbiAgICAgICAgcmV0dXJuICF0aGlzLmVsZW1lbnQuZGlzYWJsZWQ7XG4gICAgfVxufVxuZnVuY3Rpb24gZ2V0RnJhbWVFbGVtZW50QnlJZChpZCkge1xuICAgIGlmIChpZCAhPSBudWxsKSB7XG4gICAgICAgIGNvbnN0IGVsZW1lbnQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZChpZCk7XG4gICAgICAgIGlmIChlbGVtZW50IGluc3RhbmNlb2YgRnJhbWVFbGVtZW50KSB7XG4gICAgICAgICAgICByZXR1cm4gZWxlbWVudDtcbiAgICAgICAgfVxuICAgIH1cbn1cbmZ1bmN0aW9uIHJlYWRTY3JvbGxMb2dpY2FsUG9zaXRpb24odmFsdWUsIGRlZmF1bHRWYWx1ZSkge1xuICAgIGlmICh2YWx1ZSA9PSBcImVuZFwiIHx8IHZhbHVlID09IFwic3RhcnRcIiB8fCB2YWx1ZSA9PSBcImNlbnRlclwiIHx8IHZhbHVlID09IFwibmVhcmVzdFwiKSB7XG4gICAgICAgIHJldHVybiB2YWx1ZTtcbiAgICB9XG4gICAgZWxzZSB7XG4gICAgICAgIHJldHVybiBkZWZhdWx0VmFsdWU7XG4gICAgfVxufVxuZnVuY3Rpb24gZnJhZ21lbnRGcm9tSFRNTChodG1sID0gXCJcIikge1xuICAgIGNvbnN0IGZvcmVpZ25Eb2N1bWVudCA9IGRvY3VtZW50LmltcGxlbWVudGF0aW9uLmNyZWF0ZUhUTUxEb2N1bWVudCgpO1xuICAgIHJldHVybiBmb3JlaWduRG9jdW1lbnQuY3JlYXRlUmFuZ2UoKS5jcmVhdGVDb250ZXh0dWFsRnJhZ21lbnQoaHRtbCk7XG59XG5mdW5jdGlvbiBhY3RpdmF0ZUVsZW1lbnQoZWxlbWVudCkge1xuICAgIGlmIChlbGVtZW50ICYmIGVsZW1lbnQub3duZXJEb2N1bWVudCAhPT0gZG9jdW1lbnQpIHtcbiAgICAgICAgZWxlbWVudCA9IGRvY3VtZW50LmltcG9ydE5vZGUoZWxlbWVudCwgdHJ1ZSk7XG4gICAgfVxuICAgIGlmIChlbGVtZW50IGluc3RhbmNlb2YgRnJhbWVFbGVtZW50KSB7XG4gICAgICAgIHJldHVybiBlbGVtZW50O1xuICAgIH1cbn1cblxuY2xhc3MgRnJhbWVFbGVtZW50IGV4dGVuZHMgSFRNTEVsZW1lbnQge1xuICAgIGNvbnN0cnVjdG9yKCkge1xuICAgICAgICBzdXBlcigpO1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXIgPSBuZXcgRnJhbWVDb250cm9sbGVyKHRoaXMpO1xuICAgIH1cbiAgICBzdGF0aWMgZ2V0IG9ic2VydmVkQXR0cmlidXRlcygpIHtcbiAgICAgICAgcmV0dXJuIFtcInNyY1wiXTtcbiAgICB9XG4gICAgY29ubmVjdGVkQ2FsbGJhY2soKSB7XG4gICAgICAgIHRoaXMuY29udHJvbGxlci5jb25uZWN0KCk7XG4gICAgfVxuICAgIGRpc2Nvbm5lY3RlZENhbGxiYWNrKCkge1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXIuZGlzY29ubmVjdCgpO1xuICAgIH1cbiAgICBhdHRyaWJ1dGVDaGFuZ2VkQ2FsbGJhY2soKSB7XG4gICAgICAgIGlmICh0aGlzLnNyYyAmJiB0aGlzLmlzQWN0aXZlKSB7XG4gICAgICAgICAgICBjb25zdCB2YWx1ZSA9IHRoaXMuY29udHJvbGxlci52aXNpdCh0aGlzLnNyYyk7XG4gICAgICAgICAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkodGhpcywgXCJsb2FkZWRcIiwgeyB2YWx1ZSwgY29uZmlndXJhYmxlOiB0cnVlIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIGZvcm1TdWJtaXNzaW9uSW50ZXJjZXB0ZWQoZWxlbWVudCwgc3VibWl0dGVyKSB7XG4gICAgICAgIHRoaXMuY29udHJvbGxlci5mb3JtU3VibWlzc2lvbkludGVyY2VwdGVkKGVsZW1lbnQsIHN1Ym1pdHRlcik7XG4gICAgfVxuICAgIGdldCBzcmMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldEF0dHJpYnV0ZShcInNyY1wiKTtcbiAgICB9XG4gICAgc2V0IHNyYyh2YWx1ZSkge1xuICAgICAgICBpZiAodmFsdWUpIHtcbiAgICAgICAgICAgIHRoaXMuc2V0QXR0cmlidXRlKFwic3JjXCIsIHZhbHVlKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMucmVtb3ZlQXR0cmlidXRlKFwic3JjXCIpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldCBsb2FkZWQoKSB7XG4gICAgICAgIHJldHVybiBQcm9taXNlLnJlc29sdmUodW5kZWZpbmVkKTtcbiAgICB9XG4gICAgZ2V0IGRpc2FibGVkKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5oYXNBdHRyaWJ1dGUoXCJkaXNhYmxlZFwiKTtcbiAgICB9XG4gICAgc2V0IGRpc2FibGVkKHZhbHVlKSB7XG4gICAgICAgIGlmICh2YWx1ZSkge1xuICAgICAgICAgICAgdGhpcy5zZXRBdHRyaWJ1dGUoXCJkaXNhYmxlZFwiLCBcIlwiKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMucmVtb3ZlQXR0cmlidXRlKFwiZGlzYWJsZWRcIik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0IGF1dG9zY3JvbGwoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmhhc0F0dHJpYnV0ZShcImF1dG9zY3JvbGxcIik7XG4gICAgfVxuICAgIHNldCBhdXRvc2Nyb2xsKHZhbHVlKSB7XG4gICAgICAgIGlmICh2YWx1ZSkge1xuICAgICAgICAgICAgdGhpcy5zZXRBdHRyaWJ1dGUoXCJhdXRvc2Nyb2xsXCIsIFwiXCIpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5yZW1vdmVBdHRyaWJ1dGUoXCJhdXRvc2Nyb2xsXCIpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldCBpc0FjdGl2ZSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMub3duZXJEb2N1bWVudCA9PT0gZG9jdW1lbnQgJiYgIXRoaXMuaXNQcmV2aWV3O1xuICAgIH1cbiAgICBnZXQgaXNQcmV2aWV3KCkge1xuICAgICAgICB2YXIgX2EsIF9iO1xuICAgICAgICByZXR1cm4gKF9iID0gKF9hID0gdGhpcy5vd25lckRvY3VtZW50KSA9PT0gbnVsbCB8fCBfYSA9PT0gdm9pZCAwID8gdm9pZCAwIDogX2EuZG9jdW1lbnRFbGVtZW50KSA9PT0gbnVsbCB8fCBfYiA9PT0gdm9pZCAwID8gdm9pZCAwIDogX2IuaGFzQXR0cmlidXRlKFwiZGF0YS10dXJiby1wcmV2aWV3XCIpO1xuICAgIH1cbn1cbmN1c3RvbUVsZW1lbnRzLmRlZmluZShcInR1cmJvLWZyYW1lXCIsIEZyYW1lRWxlbWVudCk7XG5cbmNvbnN0IFN0cmVhbUFjdGlvbnMgPSB7XG4gICAgYXBwZW5kKCkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIChfYSA9IHRoaXMudGFyZ2V0RWxlbWVudCkgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLmFwcGVuZCh0aGlzLnRlbXBsYXRlQ29udGVudCk7XG4gICAgfSxcbiAgICBwcmVwZW5kKCkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIChfYSA9IHRoaXMudGFyZ2V0RWxlbWVudCkgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLnByZXBlbmQodGhpcy50ZW1wbGF0ZUNvbnRlbnQpO1xuICAgIH0sXG4gICAgcmVtb3ZlKCkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIChfYSA9IHRoaXMudGFyZ2V0RWxlbWVudCkgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLnJlbW92ZSgpO1xuICAgIH0sXG4gICAgcmVwbGFjZSgpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICAoX2EgPSB0aGlzLnRhcmdldEVsZW1lbnQpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5yZXBsYWNlV2l0aCh0aGlzLnRlbXBsYXRlQ29udGVudCk7XG4gICAgfSxcbiAgICB1cGRhdGUoKSB7XG4gICAgICAgIGlmICh0aGlzLnRhcmdldEVsZW1lbnQpIHtcbiAgICAgICAgICAgIHRoaXMudGFyZ2V0RWxlbWVudC5pbm5lckhUTUwgPSBcIlwiO1xuICAgICAgICAgICAgdGhpcy50YXJnZXRFbGVtZW50LmFwcGVuZCh0aGlzLnRlbXBsYXRlQ29udGVudCk7XG4gICAgICAgIH1cbiAgICB9XG59O1xuXG5jbGFzcyBTdHJlYW1FbGVtZW50IGV4dGVuZHMgSFRNTEVsZW1lbnQge1xuICAgIGFzeW5jIGNvbm5lY3RlZENhbGxiYWNrKCkge1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgYXdhaXQgdGhpcy5yZW5kZXIoKTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIGNvbnNvbGUuZXJyb3IoZXJyb3IpO1xuICAgICAgICB9XG4gICAgICAgIGZpbmFsbHkge1xuICAgICAgICAgICAgdGhpcy5kaXNjb25uZWN0KCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgYXN5bmMgcmVuZGVyKCkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIHJldHVybiAoX2EgPSB0aGlzLnJlbmRlclByb21pc2UpICE9PSBudWxsICYmIF9hICE9PSB2b2lkIDAgPyBfYSA6ICh0aGlzLnJlbmRlclByb21pc2UgPSAoYXN5bmMgKCkgPT4ge1xuICAgICAgICAgICAgaWYgKHRoaXMuZGlzcGF0Y2hFdmVudCh0aGlzLmJlZm9yZVJlbmRlckV2ZW50KSkge1xuICAgICAgICAgICAgICAgIGF3YWl0IG5leHRBbmltYXRpb25GcmFtZSgpO1xuICAgICAgICAgICAgICAgIHRoaXMucGVyZm9ybUFjdGlvbigpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KSgpKTtcbiAgICB9XG4gICAgZGlzY29ubmVjdCgpIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHRoaXMucmVtb3ZlKCk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKF9hKSB7IH1cbiAgICB9XG4gICAgZ2V0IHBlcmZvcm1BY3Rpb24oKSB7XG4gICAgICAgIGlmICh0aGlzLmFjdGlvbikge1xuICAgICAgICAgICAgY29uc3QgYWN0aW9uRnVuY3Rpb24gPSBTdHJlYW1BY3Rpb25zW3RoaXMuYWN0aW9uXTtcbiAgICAgICAgICAgIGlmIChhY3Rpb25GdW5jdGlvbikge1xuICAgICAgICAgICAgICAgIHJldHVybiBhY3Rpb25GdW5jdGlvbjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHRoaXMucmFpc2UoXCJ1bmtub3duIGFjdGlvblwiKTtcbiAgICAgICAgfVxuICAgICAgICB0aGlzLnJhaXNlKFwiYWN0aW9uIGF0dHJpYnV0ZSBpcyBtaXNzaW5nXCIpO1xuICAgIH1cbiAgICBnZXQgdGFyZ2V0RWxlbWVudCgpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICBpZiAodGhpcy50YXJnZXQpIHtcbiAgICAgICAgICAgIHJldHVybiAoX2EgPSB0aGlzLm93bmVyRG9jdW1lbnQpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5nZXRFbGVtZW50QnlJZCh0aGlzLnRhcmdldCk7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5yYWlzZShcInRhcmdldCBhdHRyaWJ1dGUgaXMgbWlzc2luZ1wiKTtcbiAgICB9XG4gICAgZ2V0IHRlbXBsYXRlQ29udGVudCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMudGVtcGxhdGVFbGVtZW50LmNvbnRlbnQ7XG4gICAgfVxuICAgIGdldCB0ZW1wbGF0ZUVsZW1lbnQoKSB7XG4gICAgICAgIGlmICh0aGlzLmZpcnN0RWxlbWVudENoaWxkIGluc3RhbmNlb2YgSFRNTFRlbXBsYXRlRWxlbWVudCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZmlyc3RFbGVtZW50Q2hpbGQ7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5yYWlzZShcImZpcnN0IGNoaWxkIGVsZW1lbnQgbXVzdCBiZSBhIDx0ZW1wbGF0ZT4gZWxlbWVudFwiKTtcbiAgICB9XG4gICAgZ2V0IGFjdGlvbigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0QXR0cmlidXRlKFwiYWN0aW9uXCIpO1xuICAgIH1cbiAgICBnZXQgdGFyZ2V0KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRBdHRyaWJ1dGUoXCJ0YXJnZXRcIik7XG4gICAgfVxuICAgIHJhaXNlKG1lc3NhZ2UpIHtcbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKGAke3RoaXMuZGVzY3JpcHRpb259OiAke21lc3NhZ2V9YCk7XG4gICAgfVxuICAgIGdldCBkZXNjcmlwdGlvbigpIHtcbiAgICAgICAgdmFyIF9hLCBfYjtcbiAgICAgICAgcmV0dXJuIChfYiA9ICgoX2EgPSB0aGlzLm91dGVySFRNTC5tYXRjaCgvPFtePl0rPi8pKSAhPT0gbnVsbCAmJiBfYSAhPT0gdm9pZCAwID8gX2EgOiBbXSlbMF0pICE9PSBudWxsICYmIF9iICE9PSB2b2lkIDAgPyBfYiA6IFwiPHR1cmJvLXN0cmVhbT5cIjtcbiAgICB9XG4gICAgZ2V0IGJlZm9yZVJlbmRlckV2ZW50KCkge1xuICAgICAgICByZXR1cm4gbmV3IEN1c3RvbUV2ZW50KFwidHVyYm86YmVmb3JlLXN0cmVhbS1yZW5kZXJcIiwgeyBidWJibGVzOiB0cnVlLCBjYW5jZWxhYmxlOiB0cnVlIH0pO1xuICAgIH1cbn1cbmN1c3RvbUVsZW1lbnRzLmRlZmluZShcInR1cmJvLXN0cmVhbVwiLCBTdHJlYW1FbGVtZW50KTtcblxuKCgpID0+IHtcbiAgICBsZXQgZWxlbWVudCA9IGRvY3VtZW50LmN1cnJlbnRTY3JpcHQ7XG4gICAgaWYgKCFlbGVtZW50KVxuICAgICAgICByZXR1cm47XG4gICAgaWYgKGVsZW1lbnQuaGFzQXR0cmlidXRlKFwiZGF0YS10dXJiby1zdXBwcmVzcy13YXJuaW5nXCIpKVxuICAgICAgICByZXR1cm47XG4gICAgd2hpbGUgKGVsZW1lbnQgPSBlbGVtZW50LnBhcmVudEVsZW1lbnQpIHtcbiAgICAgICAgaWYgKGVsZW1lbnQgPT0gZG9jdW1lbnQuYm9keSkge1xuICAgICAgICAgICAgcmV0dXJuIGNvbnNvbGUud2Fybih1bmluZGVudCBgXG4gICAgICAgIFlvdSBhcmUgbG9hZGluZyBUdXJibyBmcm9tIGEgPHNjcmlwdD4gZWxlbWVudCBpbnNpZGUgdGhlIDxib2R5PiBlbGVtZW50LiBUaGlzIGlzIHByb2JhYmx5IG5vdCB3aGF0IHlvdSBtZWFudCB0byBkbyFcblxuICAgICAgICBMb2FkIHlvdXIgYXBwbGljYXRpb27igJlzIEphdmFTY3JpcHQgYnVuZGxlIGluc2lkZSB0aGUgPGhlYWQ+IGVsZW1lbnQgaW5zdGVhZC4gPHNjcmlwdD4gZWxlbWVudHMgaW4gPGJvZHk+IGFyZSBldmFsdWF0ZWQgd2l0aCBlYWNoIHBhZ2UgY2hhbmdlLlxuXG4gICAgICAgIEZvciBtb3JlIGluZm9ybWF0aW9uLCBzZWU6IGh0dHBzOi8vdHVyYm8uaG90d2lyZS5kZXYvaGFuZGJvb2svYnVpbGRpbmcjd29ya2luZy13aXRoLXNjcmlwdC1lbGVtZW50c1xuXG4gICAgICAgIOKAlOKAlFxuICAgICAgICBTdXBwcmVzcyB0aGlzIHdhcm5pbmcgYnkgYWRkaW5nIGEgXCJkYXRhLXR1cmJvLXN1cHByZXNzLXdhcm5pbmdcIiBhdHRyaWJ1dGUgdG86ICVzXG4gICAgICBgLCBlbGVtZW50Lm91dGVySFRNTCk7XG4gICAgICAgIH1cbiAgICB9XG59KSgpO1xuXG5jbGFzcyBQcm9ncmVzc0JhciB7XG4gICAgY29uc3RydWN0b3IoKSB7XG4gICAgICAgIHRoaXMuaGlkaW5nID0gZmFsc2U7XG4gICAgICAgIHRoaXMudmFsdWUgPSAwO1xuICAgICAgICB0aGlzLnZpc2libGUgPSBmYWxzZTtcbiAgICAgICAgdGhpcy50cmlja2xlID0gKCkgPT4ge1xuICAgICAgICAgICAgdGhpcy5zZXRWYWx1ZSh0aGlzLnZhbHVlICsgTWF0aC5yYW5kb20oKSAvIDEwMCk7XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuc3R5bGVzaGVldEVsZW1lbnQgPSB0aGlzLmNyZWF0ZVN0eWxlc2hlZXRFbGVtZW50KCk7XG4gICAgICAgIHRoaXMucHJvZ3Jlc3NFbGVtZW50ID0gdGhpcy5jcmVhdGVQcm9ncmVzc0VsZW1lbnQoKTtcbiAgICAgICAgdGhpcy5pbnN0YWxsU3R5bGVzaGVldEVsZW1lbnQoKTtcbiAgICAgICAgdGhpcy5zZXRWYWx1ZSgwKTtcbiAgICB9XG4gICAgc3RhdGljIGdldCBkZWZhdWx0Q1NTKCkge1xuICAgICAgICByZXR1cm4gdW5pbmRlbnQgYFxuICAgICAgLnR1cmJvLXByb2dyZXNzLWJhciB7XG4gICAgICAgIHBvc2l0aW9uOiBmaXhlZDtcbiAgICAgICAgZGlzcGxheTogYmxvY2s7XG4gICAgICAgIHRvcDogMDtcbiAgICAgICAgbGVmdDogMDtcbiAgICAgICAgaGVpZ2h0OiAzcHg7XG4gICAgICAgIGJhY2tncm91bmQ6ICMwMDc2ZmY7XG4gICAgICAgIHotaW5kZXg6IDk5OTk7XG4gICAgICAgIHRyYW5zaXRpb246XG4gICAgICAgICAgd2lkdGggJHtQcm9ncmVzc0Jhci5hbmltYXRpb25EdXJhdGlvbn1tcyBlYXNlLW91dCxcbiAgICAgICAgICBvcGFjaXR5ICR7UHJvZ3Jlc3NCYXIuYW5pbWF0aW9uRHVyYXRpb24gLyAyfW1zICR7UHJvZ3Jlc3NCYXIuYW5pbWF0aW9uRHVyYXRpb24gLyAyfW1zIGVhc2UtaW47XG4gICAgICAgIHRyYW5zZm9ybTogdHJhbnNsYXRlM2QoMCwgMCwgMCk7XG4gICAgICB9XG4gICAgYDtcbiAgICB9XG4gICAgc2hvdygpIHtcbiAgICAgICAgaWYgKCF0aGlzLnZpc2libGUpIHtcbiAgICAgICAgICAgIHRoaXMudmlzaWJsZSA9IHRydWU7XG4gICAgICAgICAgICB0aGlzLmluc3RhbGxQcm9ncmVzc0VsZW1lbnQoKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRUcmlja2xpbmcoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBoaWRlKCkge1xuICAgICAgICBpZiAodGhpcy52aXNpYmxlICYmICF0aGlzLmhpZGluZykge1xuICAgICAgICAgICAgdGhpcy5oaWRpbmcgPSB0cnVlO1xuICAgICAgICAgICAgdGhpcy5mYWRlUHJvZ3Jlc3NFbGVtZW50KCgpID0+IHtcbiAgICAgICAgICAgICAgICB0aGlzLnVuaW5zdGFsbFByb2dyZXNzRWxlbWVudCgpO1xuICAgICAgICAgICAgICAgIHRoaXMuc3RvcFRyaWNrbGluZygpO1xuICAgICAgICAgICAgICAgIHRoaXMudmlzaWJsZSA9IGZhbHNlO1xuICAgICAgICAgICAgICAgIHRoaXMuaGlkaW5nID0gZmFsc2U7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzZXRWYWx1ZSh2YWx1ZSkge1xuICAgICAgICB0aGlzLnZhbHVlID0gdmFsdWU7XG4gICAgICAgIHRoaXMucmVmcmVzaCgpO1xuICAgIH1cbiAgICBpbnN0YWxsU3R5bGVzaGVldEVsZW1lbnQoKSB7XG4gICAgICAgIGRvY3VtZW50LmhlYWQuaW5zZXJ0QmVmb3JlKHRoaXMuc3R5bGVzaGVldEVsZW1lbnQsIGRvY3VtZW50LmhlYWQuZmlyc3RDaGlsZCk7XG4gICAgfVxuICAgIGluc3RhbGxQcm9ncmVzc0VsZW1lbnQoKSB7XG4gICAgICAgIHRoaXMucHJvZ3Jlc3NFbGVtZW50LnN0eWxlLndpZHRoID0gXCIwXCI7XG4gICAgICAgIHRoaXMucHJvZ3Jlc3NFbGVtZW50LnN0eWxlLm9wYWNpdHkgPSBcIjFcIjtcbiAgICAgICAgZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50Lmluc2VydEJlZm9yZSh0aGlzLnByb2dyZXNzRWxlbWVudCwgZG9jdW1lbnQuYm9keSk7XG4gICAgICAgIHRoaXMucmVmcmVzaCgpO1xuICAgIH1cbiAgICBmYWRlUHJvZ3Jlc3NFbGVtZW50KGNhbGxiYWNrKSB7XG4gICAgICAgIHRoaXMucHJvZ3Jlc3NFbGVtZW50LnN0eWxlLm9wYWNpdHkgPSBcIjBcIjtcbiAgICAgICAgc2V0VGltZW91dChjYWxsYmFjaywgUHJvZ3Jlc3NCYXIuYW5pbWF0aW9uRHVyYXRpb24gKiAxLjUpO1xuICAgIH1cbiAgICB1bmluc3RhbGxQcm9ncmVzc0VsZW1lbnQoKSB7XG4gICAgICAgIGlmICh0aGlzLnByb2dyZXNzRWxlbWVudC5wYXJlbnROb2RlKSB7XG4gICAgICAgICAgICBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQucmVtb3ZlQ2hpbGQodGhpcy5wcm9ncmVzc0VsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0YXJ0VHJpY2tsaW5nKCkge1xuICAgICAgICBpZiAoIXRoaXMudHJpY2tsZUludGVydmFsKSB7XG4gICAgICAgICAgICB0aGlzLnRyaWNrbGVJbnRlcnZhbCA9IHdpbmRvdy5zZXRJbnRlcnZhbCh0aGlzLnRyaWNrbGUsIFByb2dyZXNzQmFyLmFuaW1hdGlvbkR1cmF0aW9uKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdG9wVHJpY2tsaW5nKCkge1xuICAgICAgICB3aW5kb3cuY2xlYXJJbnRlcnZhbCh0aGlzLnRyaWNrbGVJbnRlcnZhbCk7XG4gICAgICAgIGRlbGV0ZSB0aGlzLnRyaWNrbGVJbnRlcnZhbDtcbiAgICB9XG4gICAgcmVmcmVzaCgpIHtcbiAgICAgICAgcmVxdWVzdEFuaW1hdGlvbkZyYW1lKCgpID0+IHtcbiAgICAgICAgICAgIHRoaXMucHJvZ3Jlc3NFbGVtZW50LnN0eWxlLndpZHRoID0gYCR7MTAgKyAodGhpcy52YWx1ZSAqIDkwKX0lYDtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGNyZWF0ZVN0eWxlc2hlZXRFbGVtZW50KCkge1xuICAgICAgICBjb25zdCBlbGVtZW50ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudChcInN0eWxlXCIpO1xuICAgICAgICBlbGVtZW50LnR5cGUgPSBcInRleHQvY3NzXCI7XG4gICAgICAgIGVsZW1lbnQudGV4dENvbnRlbnQgPSBQcm9ncmVzc0Jhci5kZWZhdWx0Q1NTO1xuICAgICAgICByZXR1cm4gZWxlbWVudDtcbiAgICB9XG4gICAgY3JlYXRlUHJvZ3Jlc3NFbGVtZW50KCkge1xuICAgICAgICBjb25zdCBlbGVtZW50ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudChcImRpdlwiKTtcbiAgICAgICAgZWxlbWVudC5jbGFzc05hbWUgPSBcInR1cmJvLXByb2dyZXNzLWJhclwiO1xuICAgICAgICByZXR1cm4gZWxlbWVudDtcbiAgICB9XG59XG5Qcm9ncmVzc0Jhci5hbmltYXRpb25EdXJhdGlvbiA9IDMwMDtcblxuY2xhc3MgSGVhZERldGFpbHMge1xuICAgIGNvbnN0cnVjdG9yKGNoaWxkcmVuKSB7XG4gICAgICAgIHRoaXMuZGV0YWlsc0J5T3V0ZXJIVE1MID0gY2hpbGRyZW4ucmVkdWNlKChyZXN1bHQsIGVsZW1lbnQpID0+IHtcbiAgICAgICAgICAgIGNvbnN0IHsgb3V0ZXJIVE1MIH0gPSBlbGVtZW50O1xuICAgICAgICAgICAgY29uc3QgZGV0YWlscyA9IG91dGVySFRNTCBpbiByZXN1bHRcbiAgICAgICAgICAgICAgICA/IHJlc3VsdFtvdXRlckhUTUxdXG4gICAgICAgICAgICAgICAgOiB7XG4gICAgICAgICAgICAgICAgICAgIHR5cGU6IGVsZW1lbnRUeXBlKGVsZW1lbnQpLFxuICAgICAgICAgICAgICAgICAgICB0cmFja2VkOiBlbGVtZW50SXNUcmFja2VkKGVsZW1lbnQpLFxuICAgICAgICAgICAgICAgICAgICBlbGVtZW50czogW11cbiAgICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24oT2JqZWN0LmFzc2lnbih7fSwgcmVzdWx0KSwgeyBbb3V0ZXJIVE1MXTogT2JqZWN0LmFzc2lnbihPYmplY3QuYXNzaWduKHt9LCBkZXRhaWxzKSwgeyBlbGVtZW50czogWy4uLmRldGFpbHMuZWxlbWVudHMsIGVsZW1lbnRdIH0pIH0pO1xuICAgICAgICB9LCB7fSk7XG4gICAgfVxuICAgIHN0YXRpYyBmcm9tSGVhZEVsZW1lbnQoaGVhZEVsZW1lbnQpIHtcbiAgICAgICAgY29uc3QgY2hpbGRyZW4gPSBoZWFkRWxlbWVudCA/IFsuLi5oZWFkRWxlbWVudC5jaGlsZHJlbl0gOiBbXTtcbiAgICAgICAgcmV0dXJuIG5ldyB0aGlzKGNoaWxkcmVuKTtcbiAgICB9XG4gICAgZ2V0VHJhY2tlZEVsZW1lbnRTaWduYXR1cmUoKSB7XG4gICAgICAgIHJldHVybiBPYmplY3Qua2V5cyh0aGlzLmRldGFpbHNCeU91dGVySFRNTClcbiAgICAgICAgICAgIC5maWx0ZXIob3V0ZXJIVE1MID0+IHRoaXMuZGV0YWlsc0J5T3V0ZXJIVE1MW291dGVySFRNTF0udHJhY2tlZClcbiAgICAgICAgICAgIC5qb2luKFwiXCIpO1xuICAgIH1cbiAgICBnZXRTY3JpcHRFbGVtZW50c05vdEluRGV0YWlscyhoZWFkRGV0YWlscykge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRFbGVtZW50c01hdGNoaW5nVHlwZU5vdEluRGV0YWlscyhcInNjcmlwdFwiLCBoZWFkRGV0YWlscyk7XG4gICAgfVxuICAgIGdldFN0eWxlc2hlZXRFbGVtZW50c05vdEluRGV0YWlscyhoZWFkRGV0YWlscykge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRFbGVtZW50c01hdGNoaW5nVHlwZU5vdEluRGV0YWlscyhcInN0eWxlc2hlZXRcIiwgaGVhZERldGFpbHMpO1xuICAgIH1cbiAgICBnZXRFbGVtZW50c01hdGNoaW5nVHlwZU5vdEluRGV0YWlscyhtYXRjaGVkVHlwZSwgaGVhZERldGFpbHMpIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5rZXlzKHRoaXMuZGV0YWlsc0J5T3V0ZXJIVE1MKVxuICAgICAgICAgICAgLmZpbHRlcihvdXRlckhUTUwgPT4gIShvdXRlckhUTUwgaW4gaGVhZERldGFpbHMuZGV0YWlsc0J5T3V0ZXJIVE1MKSlcbiAgICAgICAgICAgIC5tYXAob3V0ZXJIVE1MID0+IHRoaXMuZGV0YWlsc0J5T3V0ZXJIVE1MW291dGVySFRNTF0pXG4gICAgICAgICAgICAuZmlsdGVyKCh7IHR5cGUgfSkgPT4gdHlwZSA9PSBtYXRjaGVkVHlwZSlcbiAgICAgICAgICAgIC5tYXAoKHsgZWxlbWVudHM6IFtlbGVtZW50XSB9KSA9PiBlbGVtZW50KTtcbiAgICB9XG4gICAgZ2V0UHJvdmlzaW9uYWxFbGVtZW50cygpIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5rZXlzKHRoaXMuZGV0YWlsc0J5T3V0ZXJIVE1MKS5yZWR1Y2UoKHJlc3VsdCwgb3V0ZXJIVE1MKSA9PiB7XG4gICAgICAgICAgICBjb25zdCB7IHR5cGUsIHRyYWNrZWQsIGVsZW1lbnRzIH0gPSB0aGlzLmRldGFpbHNCeU91dGVySFRNTFtvdXRlckhUTUxdO1xuICAgICAgICAgICAgaWYgKHR5cGUgPT0gbnVsbCAmJiAhdHJhY2tlZCkge1xuICAgICAgICAgICAgICAgIHJldHVybiBbLi4ucmVzdWx0LCAuLi5lbGVtZW50c107XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIGlmIChlbGVtZW50cy5sZW5ndGggPiAxKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIFsuLi5yZXN1bHQsIC4uLmVsZW1lbnRzLnNsaWNlKDEpXTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIHJldHVybiByZXN1bHQ7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sIFtdKTtcbiAgICB9XG4gICAgZ2V0TWV0YVZhbHVlKG5hbWUpIHtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IHRoaXMuZmluZE1ldGFFbGVtZW50QnlOYW1lKG5hbWUpO1xuICAgICAgICByZXR1cm4gZWxlbWVudFxuICAgICAgICAgICAgPyBlbGVtZW50LmdldEF0dHJpYnV0ZShcImNvbnRlbnRcIilcbiAgICAgICAgICAgIDogbnVsbDtcbiAgICB9XG4gICAgZmluZE1ldGFFbGVtZW50QnlOYW1lKG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5rZXlzKHRoaXMuZGV0YWlsc0J5T3V0ZXJIVE1MKS5yZWR1Y2UoKHJlc3VsdCwgb3V0ZXJIVE1MKSA9PiB7XG4gICAgICAgICAgICBjb25zdCB7IGVsZW1lbnRzOiBbZWxlbWVudF0gfSA9IHRoaXMuZGV0YWlsc0J5T3V0ZXJIVE1MW291dGVySFRNTF07XG4gICAgICAgICAgICByZXR1cm4gZWxlbWVudElzTWV0YUVsZW1lbnRXaXRoTmFtZShlbGVtZW50LCBuYW1lKSA/IGVsZW1lbnQgOiByZXN1bHQ7XG4gICAgICAgIH0sIHVuZGVmaW5lZCk7XG4gICAgfVxufVxuZnVuY3Rpb24gZWxlbWVudFR5cGUoZWxlbWVudCkge1xuICAgIGlmIChlbGVtZW50SXNTY3JpcHQoZWxlbWVudCkpIHtcbiAgICAgICAgcmV0dXJuIFwic2NyaXB0XCI7XG4gICAgfVxuICAgIGVsc2UgaWYgKGVsZW1lbnRJc1N0eWxlc2hlZXQoZWxlbWVudCkpIHtcbiAgICAgICAgcmV0dXJuIFwic3R5bGVzaGVldFwiO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGVsZW1lbnRJc1RyYWNrZWQoZWxlbWVudCkge1xuICAgIHJldHVybiBlbGVtZW50LmdldEF0dHJpYnV0ZShcImRhdGEtdHVyYm8tdHJhY2tcIikgPT0gXCJyZWxvYWRcIjtcbn1cbmZ1bmN0aW9uIGVsZW1lbnRJc1NjcmlwdChlbGVtZW50KSB7XG4gICAgY29uc3QgdGFnTmFtZSA9IGVsZW1lbnQudGFnTmFtZS50b0xvd2VyQ2FzZSgpO1xuICAgIHJldHVybiB0YWdOYW1lID09IFwic2NyaXB0XCI7XG59XG5mdW5jdGlvbiBlbGVtZW50SXNTdHlsZXNoZWV0KGVsZW1lbnQpIHtcbiAgICBjb25zdCB0YWdOYW1lID0gZWxlbWVudC50YWdOYW1lLnRvTG93ZXJDYXNlKCk7XG4gICAgcmV0dXJuIHRhZ05hbWUgPT0gXCJzdHlsZVwiIHx8ICh0YWdOYW1lID09IFwibGlua1wiICYmIGVsZW1lbnQuZ2V0QXR0cmlidXRlKFwicmVsXCIpID09IFwic3R5bGVzaGVldFwiKTtcbn1cbmZ1bmN0aW9uIGVsZW1lbnRJc01ldGFFbGVtZW50V2l0aE5hbWUoZWxlbWVudCwgbmFtZSkge1xuICAgIGNvbnN0IHRhZ05hbWUgPSBlbGVtZW50LnRhZ05hbWUudG9Mb3dlckNhc2UoKTtcbiAgICByZXR1cm4gdGFnTmFtZSA9PSBcIm1ldGFcIiAmJiBlbGVtZW50LmdldEF0dHJpYnV0ZShcIm5hbWVcIikgPT0gbmFtZTtcbn1cblxuY2xhc3MgU25hcHNob3Qge1xuICAgIGNvbnN0cnVjdG9yKGhlYWREZXRhaWxzLCBib2R5RWxlbWVudCkge1xuICAgICAgICB0aGlzLmhlYWREZXRhaWxzID0gaGVhZERldGFpbHM7XG4gICAgICAgIHRoaXMuYm9keUVsZW1lbnQgPSBib2R5RWxlbWVudDtcbiAgICB9XG4gICAgc3RhdGljIHdyYXAodmFsdWUpIHtcbiAgICAgICAgaWYgKHZhbHVlIGluc3RhbmNlb2YgdGhpcykge1xuICAgICAgICAgICAgcmV0dXJuIHZhbHVlO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKHR5cGVvZiB2YWx1ZSA9PSBcInN0cmluZ1wiKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5mcm9tSFRNTFN0cmluZyh2YWx1ZSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5mcm9tSFRNTEVsZW1lbnQodmFsdWUpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0YXRpYyBmcm9tSFRNTFN0cmluZyhodG1sKSB7XG4gICAgICAgIGNvbnN0IHsgZG9jdW1lbnRFbGVtZW50IH0gPSBuZXcgRE9NUGFyc2VyKCkucGFyc2VGcm9tU3RyaW5nKGh0bWwsIFwidGV4dC9odG1sXCIpO1xuICAgICAgICByZXR1cm4gdGhpcy5mcm9tSFRNTEVsZW1lbnQoZG9jdW1lbnRFbGVtZW50KTtcbiAgICB9XG4gICAgc3RhdGljIGZyb21IVE1MRWxlbWVudChodG1sRWxlbWVudCkge1xuICAgICAgICBjb25zdCBoZWFkRWxlbWVudCA9IGh0bWxFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoXCJoZWFkXCIpO1xuICAgICAgICBjb25zdCBib2R5RWxlbWVudCA9IGh0bWxFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoXCJib2R5XCIpIHx8IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJib2R5XCIpO1xuICAgICAgICBjb25zdCBoZWFkRGV0YWlscyA9IEhlYWREZXRhaWxzLmZyb21IZWFkRWxlbWVudChoZWFkRWxlbWVudCk7XG4gICAgICAgIHJldHVybiBuZXcgdGhpcyhoZWFkRGV0YWlscywgYm9keUVsZW1lbnQpO1xuICAgIH1cbiAgICBjbG9uZSgpIHtcbiAgICAgICAgY29uc3QgeyBib2R5RWxlbWVudCB9ID0gU25hcHNob3QuZnJvbUhUTUxTdHJpbmcodGhpcy5ib2R5RWxlbWVudC5vdXRlckhUTUwpO1xuICAgICAgICByZXR1cm4gbmV3IFNuYXBzaG90KHRoaXMuaGVhZERldGFpbHMsIGJvZHlFbGVtZW50KTtcbiAgICB9XG4gICAgZ2V0Um9vdExvY2F0aW9uKCkge1xuICAgICAgICBjb25zdCByb290ID0gdGhpcy5nZXRTZXR0aW5nKFwicm9vdFwiLCBcIi9cIik7XG4gICAgICAgIHJldHVybiBuZXcgTG9jYXRpb24ocm9vdCk7XG4gICAgfVxuICAgIGdldENhY2hlQ29udHJvbFZhbHVlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRTZXR0aW5nKFwiY2FjaGUtY29udHJvbFwiKTtcbiAgICB9XG4gICAgZ2V0RWxlbWVudEZvckFuY2hvcihhbmNob3IpIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmJvZHlFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoYFtpZD0nJHthbmNob3J9J10sIGFbbmFtZT0nJHthbmNob3J9J11gKTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoX2EpIHtcbiAgICAgICAgICAgIHJldHVybiBudWxsO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldFBlcm1hbmVudEVsZW1lbnRzKCkge1xuICAgICAgICByZXR1cm4gWy4uLnRoaXMuYm9keUVsZW1lbnQucXVlcnlTZWxlY3RvckFsbChcIltpZF1bZGF0YS10dXJiby1wZXJtYW5lbnRdXCIpXTtcbiAgICB9XG4gICAgZ2V0UGVybWFuZW50RWxlbWVudEJ5SWQoaWQpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYm9keUVsZW1lbnQucXVlcnlTZWxlY3RvcihgIyR7aWR9W2RhdGEtdHVyYm8tcGVybWFuZW50XWApO1xuICAgIH1cbiAgICBnZXRQZXJtYW5lbnRFbGVtZW50c1ByZXNlbnRJblNuYXBzaG90KHNuYXBzaG90KSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldFBlcm1hbmVudEVsZW1lbnRzKCkuZmlsdGVyKCh7IGlkIH0pID0+IHNuYXBzaG90LmdldFBlcm1hbmVudEVsZW1lbnRCeUlkKGlkKSk7XG4gICAgfVxuICAgIGZpbmRGaXJzdEF1dG9mb2N1c2FibGVFbGVtZW50KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5ib2R5RWxlbWVudC5xdWVyeVNlbGVjdG9yKFwiW2F1dG9mb2N1c11cIik7XG4gICAgfVxuICAgIGhhc0FuY2hvcihhbmNob3IpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0RWxlbWVudEZvckFuY2hvcihhbmNob3IpICE9IG51bGw7XG4gICAgfVxuICAgIGlzUHJldmlld2FibGUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldENhY2hlQ29udHJvbFZhbHVlKCkgIT0gXCJuby1wcmV2aWV3XCI7XG4gICAgfVxuICAgIGlzQ2FjaGVhYmxlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRDYWNoZUNvbnRyb2xWYWx1ZSgpICE9IFwibm8tY2FjaGVcIjtcbiAgICB9XG4gICAgaXNWaXNpdGFibGUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldFNldHRpbmcoXCJ2aXNpdC1jb250cm9sXCIpICE9IFwicmVsb2FkXCI7XG4gICAgfVxuICAgIGdldFNldHRpbmcobmFtZSwgZGVmYXVsdFZhbHVlKSB7XG4gICAgICAgIGNvbnN0IHZhbHVlID0gdGhpcy5oZWFkRGV0YWlscy5nZXRNZXRhVmFsdWUoYHR1cmJvLSR7bmFtZX1gKTtcbiAgICAgICAgcmV0dXJuIHZhbHVlID09IG51bGwgPyBkZWZhdWx0VmFsdWUgOiB2YWx1ZTtcbiAgICB9XG59XG5cbnZhciBUaW1pbmdNZXRyaWM7XG4oZnVuY3Rpb24gKFRpbWluZ01ldHJpYykge1xuICAgIFRpbWluZ01ldHJpY1tcInZpc2l0U3RhcnRcIl0gPSBcInZpc2l0U3RhcnRcIjtcbiAgICBUaW1pbmdNZXRyaWNbXCJyZXF1ZXN0U3RhcnRcIl0gPSBcInJlcXVlc3RTdGFydFwiO1xuICAgIFRpbWluZ01ldHJpY1tcInJlcXVlc3RFbmRcIl0gPSBcInJlcXVlc3RFbmRcIjtcbiAgICBUaW1pbmdNZXRyaWNbXCJ2aXNpdEVuZFwiXSA9IFwidmlzaXRFbmRcIjtcbn0pKFRpbWluZ01ldHJpYyB8fCAoVGltaW5nTWV0cmljID0ge30pKTtcbnZhciBWaXNpdFN0YXRlO1xuKGZ1bmN0aW9uIChWaXNpdFN0YXRlKSB7XG4gICAgVmlzaXRTdGF0ZVtcImluaXRpYWxpemVkXCJdID0gXCJpbml0aWFsaXplZFwiO1xuICAgIFZpc2l0U3RhdGVbXCJzdGFydGVkXCJdID0gXCJzdGFydGVkXCI7XG4gICAgVmlzaXRTdGF0ZVtcImNhbmNlbGVkXCJdID0gXCJjYW5jZWxlZFwiO1xuICAgIFZpc2l0U3RhdGVbXCJmYWlsZWRcIl0gPSBcImZhaWxlZFwiO1xuICAgIFZpc2l0U3RhdGVbXCJjb21wbGV0ZWRcIl0gPSBcImNvbXBsZXRlZFwiO1xufSkoVmlzaXRTdGF0ZSB8fCAoVmlzaXRTdGF0ZSA9IHt9KSk7XG5jb25zdCBkZWZhdWx0T3B0aW9ucyA9IHtcbiAgICBhY3Rpb246IFwiYWR2YW5jZVwiLFxuICAgIGhpc3RvcnlDaGFuZ2VkOiBmYWxzZVxufTtcbnZhciBTeXN0ZW1TdGF0dXNDb2RlO1xuKGZ1bmN0aW9uIChTeXN0ZW1TdGF0dXNDb2RlKSB7XG4gICAgU3lzdGVtU3RhdHVzQ29kZVtTeXN0ZW1TdGF0dXNDb2RlW1wibmV0d29ya0ZhaWx1cmVcIl0gPSAwXSA9IFwibmV0d29ya0ZhaWx1cmVcIjtcbiAgICBTeXN0ZW1TdGF0dXNDb2RlW1N5c3RlbVN0YXR1c0NvZGVbXCJ0aW1lb3V0RmFpbHVyZVwiXSA9IC0xXSA9IFwidGltZW91dEZhaWx1cmVcIjtcbiAgICBTeXN0ZW1TdGF0dXNDb2RlW1N5c3RlbVN0YXR1c0NvZGVbXCJjb250ZW50VHlwZU1pc21hdGNoXCJdID0gLTJdID0gXCJjb250ZW50VHlwZU1pc21hdGNoXCI7XG59KShTeXN0ZW1TdGF0dXNDb2RlIHx8IChTeXN0ZW1TdGF0dXNDb2RlID0ge30pKTtcbmNsYXNzIFZpc2l0IHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSwgbG9jYXRpb24sIHJlc3RvcmF0aW9uSWRlbnRpZmllciwgb3B0aW9ucyA9IHt9KSB7XG4gICAgICAgIHRoaXMuaWRlbnRpZmllciA9IHV1aWQoKTtcbiAgICAgICAgdGhpcy50aW1pbmdNZXRyaWNzID0ge307XG4gICAgICAgIHRoaXMuZm9sbG93ZWRSZWRpcmVjdCA9IGZhbHNlO1xuICAgICAgICB0aGlzLmhpc3RvcnlDaGFuZ2VkID0gZmFsc2U7XG4gICAgICAgIHRoaXMuc2Nyb2xsZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5zbmFwc2hvdENhY2hlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLnN0YXRlID0gVmlzaXRTdGF0ZS5pbml0aWFsaXplZDtcbiAgICAgICAgdGhpcy5wZXJmb3JtU2Nyb2xsID0gKCkgPT4ge1xuICAgICAgICAgICAgaWYgKCF0aGlzLnNjcm9sbGVkKSB7XG4gICAgICAgICAgICAgICAgaWYgKHRoaXMuYWN0aW9uID09IFwicmVzdG9yZVwiKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuc2Nyb2xsVG9SZXN0b3JlZFBvc2l0aW9uKCkgfHwgdGhpcy5zY3JvbGxUb1RvcCgpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5zY3JvbGxUb0FuY2hvcigpIHx8IHRoaXMuc2Nyb2xsVG9Ub3AoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgdGhpcy5zY3JvbGxlZCA9IHRydWU7XG4gICAgICAgICAgICB9XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5sb2NhdGlvbiA9IGxvY2F0aW9uO1xuICAgICAgICB0aGlzLnJlc3RvcmF0aW9uSWRlbnRpZmllciA9IHJlc3RvcmF0aW9uSWRlbnRpZmllciB8fCB1dWlkKCk7XG4gICAgICAgIGNvbnN0IHsgYWN0aW9uLCBoaXN0b3J5Q2hhbmdlZCwgcmVmZXJyZXIsIHNuYXBzaG90SFRNTCwgcmVzcG9uc2UgfSA9IE9iamVjdC5hc3NpZ24oT2JqZWN0LmFzc2lnbih7fSwgZGVmYXVsdE9wdGlvbnMpLCBvcHRpb25zKTtcbiAgICAgICAgdGhpcy5hY3Rpb24gPSBhY3Rpb247XG4gICAgICAgIHRoaXMuaGlzdG9yeUNoYW5nZWQgPSBoaXN0b3J5Q2hhbmdlZDtcbiAgICAgICAgdGhpcy5yZWZlcnJlciA9IHJlZmVycmVyO1xuICAgICAgICB0aGlzLnNuYXBzaG90SFRNTCA9IHNuYXBzaG90SFRNTDtcbiAgICAgICAgdGhpcy5yZXNwb25zZSA9IHJlc3BvbnNlO1xuICAgIH1cbiAgICBnZXQgYWRhcHRlcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVsZWdhdGUuYWRhcHRlcjtcbiAgICB9XG4gICAgZ2V0IHZpZXcoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmRlbGVnYXRlLnZpZXc7XG4gICAgfVxuICAgIGdldCBoaXN0b3J5KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5kZWxlZ2F0ZS5oaXN0b3J5O1xuICAgIH1cbiAgICBnZXQgcmVzdG9yYXRpb25EYXRhKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5oaXN0b3J5LmdldFJlc3RvcmF0aW9uRGF0YUZvcklkZW50aWZpZXIodGhpcy5yZXN0b3JhdGlvbklkZW50aWZpZXIpO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhdGUgPT0gVmlzaXRTdGF0ZS5pbml0aWFsaXplZCkge1xuICAgICAgICAgICAgdGhpcy5yZWNvcmRUaW1pbmdNZXRyaWMoVGltaW5nTWV0cmljLnZpc2l0U3RhcnQpO1xuICAgICAgICAgICAgdGhpcy5zdGF0ZSA9IFZpc2l0U3RhdGUuc3RhcnRlZDtcbiAgICAgICAgICAgIHRoaXMuYWRhcHRlci52aXNpdFN0YXJ0ZWQodGhpcyk7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnZpc2l0U3RhcnRlZCh0aGlzKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBjYW5jZWwoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXRlID09IFZpc2l0U3RhdGUuc3RhcnRlZCkge1xuICAgICAgICAgICAgaWYgKHRoaXMucmVxdWVzdCkge1xuICAgICAgICAgICAgICAgIHRoaXMucmVxdWVzdC5jYW5jZWwoKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHRoaXMuY2FuY2VsUmVuZGVyKCk7XG4gICAgICAgICAgICB0aGlzLnN0YXRlID0gVmlzaXRTdGF0ZS5jYW5jZWxlZDtcbiAgICAgICAgfVxuICAgIH1cbiAgICBjb21wbGV0ZSgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhdGUgPT0gVmlzaXRTdGF0ZS5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnJlY29yZFRpbWluZ01ldHJpYyhUaW1pbmdNZXRyaWMudmlzaXRFbmQpO1xuICAgICAgICAgICAgdGhpcy5zdGF0ZSA9IFZpc2l0U3RhdGUuY29tcGxldGVkO1xuICAgICAgICAgICAgdGhpcy5hZGFwdGVyLnZpc2l0Q29tcGxldGVkKHRoaXMpO1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS52aXNpdENvbXBsZXRlZCh0aGlzKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBmYWlsKCkge1xuICAgICAgICBpZiAodGhpcy5zdGF0ZSA9PSBWaXNpdFN0YXRlLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhdGUgPSBWaXNpdFN0YXRlLmZhaWxlZDtcbiAgICAgICAgICAgIHRoaXMuYWRhcHRlci52aXNpdEZhaWxlZCh0aGlzKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBjaGFuZ2VIaXN0b3J5KCkge1xuICAgICAgICBpZiAoIXRoaXMuaGlzdG9yeUNoYW5nZWQpIHtcbiAgICAgICAgICAgIGNvbnN0IGFjdGlvbkZvckhpc3RvcnkgPSB0aGlzLmxvY2F0aW9uLmlzRXF1YWxUbyh0aGlzLnJlZmVycmVyKSA/IFwicmVwbGFjZVwiIDogdGhpcy5hY3Rpb247XG4gICAgICAgICAgICBjb25zdCBtZXRob2QgPSB0aGlzLmdldEhpc3RvcnlNZXRob2RGb3JBY3Rpb24oYWN0aW9uRm9ySGlzdG9yeSk7XG4gICAgICAgICAgICB0aGlzLmhpc3RvcnkudXBkYXRlKG1ldGhvZCwgdGhpcy5sb2NhdGlvbiwgdGhpcy5yZXN0b3JhdGlvbklkZW50aWZpZXIpO1xuICAgICAgICAgICAgdGhpcy5oaXN0b3J5Q2hhbmdlZCA9IHRydWU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgaXNzdWVSZXF1ZXN0KCkge1xuICAgICAgICBpZiAodGhpcy5oYXNQcmVsb2FkZWRSZXNwb25zZSgpKSB7XG4gICAgICAgICAgICB0aGlzLnNpbXVsYXRlUmVxdWVzdCgpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKHRoaXMuc2hvdWxkSXNzdWVSZXF1ZXN0KCkgJiYgIXRoaXMucmVxdWVzdCkge1xuICAgICAgICAgICAgdGhpcy5yZXF1ZXN0ID0gbmV3IEZldGNoUmVxdWVzdCh0aGlzLCBGZXRjaE1ldGhvZC5nZXQsIHRoaXMubG9jYXRpb24pO1xuICAgICAgICAgICAgdGhpcy5yZXF1ZXN0LnBlcmZvcm0oKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzaW11bGF0ZVJlcXVlc3QoKSB7XG4gICAgICAgIGlmICh0aGlzLnJlc3BvbnNlKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0UmVxdWVzdCgpO1xuICAgICAgICAgICAgdGhpcy5yZWNvcmRSZXNwb25zZSgpO1xuICAgICAgICAgICAgdGhpcy5maW5pc2hSZXF1ZXN0KCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RhcnRSZXF1ZXN0KCkge1xuICAgICAgICB0aGlzLnJlY29yZFRpbWluZ01ldHJpYyhUaW1pbmdNZXRyaWMucmVxdWVzdFN0YXJ0KTtcbiAgICAgICAgdGhpcy5hZGFwdGVyLnZpc2l0UmVxdWVzdFN0YXJ0ZWQodGhpcyk7XG4gICAgfVxuICAgIHJlY29yZFJlc3BvbnNlKHJlc3BvbnNlID0gdGhpcy5yZXNwb25zZSkge1xuICAgICAgICB0aGlzLnJlc3BvbnNlID0gcmVzcG9uc2U7XG4gICAgICAgIGlmIChyZXNwb25zZSkge1xuICAgICAgICAgICAgY29uc3QgeyBzdGF0dXNDb2RlIH0gPSByZXNwb25zZTtcbiAgICAgICAgICAgIGlmIChpc1N1Y2Nlc3NmdWwoc3RhdHVzQ29kZSkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmFkYXB0ZXIudmlzaXRSZXF1ZXN0Q29tcGxldGVkKHRoaXMpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhpcy5hZGFwdGVyLnZpc2l0UmVxdWVzdEZhaWxlZFdpdGhTdGF0dXNDb2RlKHRoaXMsIHN0YXR1c0NvZGUpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIGZpbmlzaFJlcXVlc3QoKSB7XG4gICAgICAgIHRoaXMucmVjb3JkVGltaW5nTWV0cmljKFRpbWluZ01ldHJpYy5yZXF1ZXN0RW5kKTtcbiAgICAgICAgdGhpcy5hZGFwdGVyLnZpc2l0UmVxdWVzdEZpbmlzaGVkKHRoaXMpO1xuICAgIH1cbiAgICBsb2FkUmVzcG9uc2UoKSB7XG4gICAgICAgIGlmICh0aGlzLnJlc3BvbnNlKSB7XG4gICAgICAgICAgICBjb25zdCB7IHN0YXR1c0NvZGUsIHJlc3BvbnNlSFRNTCB9ID0gdGhpcy5yZXNwb25zZTtcbiAgICAgICAgICAgIHRoaXMucmVuZGVyKCgpID0+IHtcbiAgICAgICAgICAgICAgICB0aGlzLmNhY2hlU25hcHNob3QoKTtcbiAgICAgICAgICAgICAgICBpZiAoaXNTdWNjZXNzZnVsKHN0YXR1c0NvZGUpICYmIHJlc3BvbnNlSFRNTCAhPSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMudmlldy5yZW5kZXIoeyBzbmFwc2hvdDogU25hcHNob3QuZnJvbUhUTUxTdHJpbmcocmVzcG9uc2VIVE1MKSB9LCB0aGlzLnBlcmZvcm1TY3JvbGwpO1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmFkYXB0ZXIudmlzaXRSZW5kZXJlZCh0aGlzKTtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5jb21wbGV0ZSgpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy52aWV3LnJlbmRlcih7IGVycm9yOiByZXNwb25zZUhUTUwgfSwgdGhpcy5wZXJmb3JtU2Nyb2xsKTtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5hZGFwdGVyLnZpc2l0UmVuZGVyZWQodGhpcyk7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZmFpbCgpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldENhY2hlZFNuYXBzaG90KCkge1xuICAgICAgICBjb25zdCBzbmFwc2hvdCA9IHRoaXMudmlldy5nZXRDYWNoZWRTbmFwc2hvdEZvckxvY2F0aW9uKHRoaXMubG9jYXRpb24pIHx8IHRoaXMuZ2V0UHJlbG9hZGVkU25hcHNob3QoKTtcbiAgICAgICAgaWYgKHNuYXBzaG90ICYmICghdGhpcy5sb2NhdGlvbi5hbmNob3IgfHwgc25hcHNob3QuaGFzQW5jaG9yKHRoaXMubG9jYXRpb24uYW5jaG9yKSkpIHtcbiAgICAgICAgICAgIGlmICh0aGlzLmFjdGlvbiA9PSBcInJlc3RvcmVcIiB8fCBzbmFwc2hvdC5pc1ByZXZpZXdhYmxlKCkpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gc25hcHNob3Q7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0UHJlbG9hZGVkU25hcHNob3QoKSB7XG4gICAgICAgIGlmICh0aGlzLnNuYXBzaG90SFRNTCkge1xuICAgICAgICAgICAgcmV0dXJuIFNuYXBzaG90LndyYXAodGhpcy5zbmFwc2hvdEhUTUwpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGhhc0NhY2hlZFNuYXBzaG90KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRDYWNoZWRTbmFwc2hvdCgpICE9IG51bGw7XG4gICAgfVxuICAgIGxvYWRDYWNoZWRTbmFwc2hvdCgpIHtcbiAgICAgICAgY29uc3Qgc25hcHNob3QgPSB0aGlzLmdldENhY2hlZFNuYXBzaG90KCk7XG4gICAgICAgIGlmIChzbmFwc2hvdCkge1xuICAgICAgICAgICAgY29uc3QgaXNQcmV2aWV3ID0gdGhpcy5zaG91bGRJc3N1ZVJlcXVlc3QoKTtcbiAgICAgICAgICAgIHRoaXMucmVuZGVyKCgpID0+IHtcbiAgICAgICAgICAgICAgICB0aGlzLmNhY2hlU25hcHNob3QoKTtcbiAgICAgICAgICAgICAgICB0aGlzLnZpZXcucmVuZGVyKHsgc25hcHNob3QsIGlzUHJldmlldyB9LCB0aGlzLnBlcmZvcm1TY3JvbGwpO1xuICAgICAgICAgICAgICAgIHRoaXMuYWRhcHRlci52aXNpdFJlbmRlcmVkKHRoaXMpO1xuICAgICAgICAgICAgICAgIGlmICghaXNQcmV2aWV3KSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuY29tcGxldGUoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBmb2xsb3dSZWRpcmVjdCgpIHtcbiAgICAgICAgaWYgKHRoaXMucmVkaXJlY3RlZFRvTG9jYXRpb24gJiYgIXRoaXMuZm9sbG93ZWRSZWRpcmVjdCkge1xuICAgICAgICAgICAgdGhpcy5sb2NhdGlvbiA9IHRoaXMucmVkaXJlY3RlZFRvTG9jYXRpb247XG4gICAgICAgICAgICB0aGlzLmhpc3RvcnkucmVwbGFjZSh0aGlzLnJlZGlyZWN0ZWRUb0xvY2F0aW9uLCB0aGlzLnJlc3RvcmF0aW9uSWRlbnRpZmllcik7XG4gICAgICAgICAgICB0aGlzLmZvbGxvd2VkUmVkaXJlY3QgPSB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJlcXVlc3RTdGFydGVkKCkge1xuICAgICAgICB0aGlzLnN0YXJ0UmVxdWVzdCgpO1xuICAgIH1cbiAgICByZXF1ZXN0UHJldmVudGVkSGFuZGxpbmdSZXNwb25zZShyZXF1ZXN0LCByZXNwb25zZSkge1xuICAgIH1cbiAgICBhc3luYyByZXF1ZXN0U3VjY2VlZGVkV2l0aFJlc3BvbnNlKHJlcXVlc3QsIHJlc3BvbnNlKSB7XG4gICAgICAgIGNvbnN0IHJlc3BvbnNlSFRNTCA9IGF3YWl0IHJlc3BvbnNlLnJlc3BvbnNlSFRNTDtcbiAgICAgICAgaWYgKHJlc3BvbnNlSFRNTCA9PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgIHRoaXMucmVjb3JkUmVzcG9uc2UoeyBzdGF0dXNDb2RlOiBTeXN0ZW1TdGF0dXNDb2RlLmNvbnRlbnRUeXBlTWlzbWF0Y2ggfSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLnJlZGlyZWN0ZWRUb0xvY2F0aW9uID0gcmVzcG9uc2UucmVkaXJlY3RlZCA/IHJlc3BvbnNlLmxvY2F0aW9uIDogdW5kZWZpbmVkO1xuICAgICAgICAgICAgdGhpcy5yZWNvcmRSZXNwb25zZSh7IHN0YXR1c0NvZGU6IHJlc3BvbnNlLnN0YXR1c0NvZGUsIHJlc3BvbnNlSFRNTCB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBhc3luYyByZXF1ZXN0RmFpbGVkV2l0aFJlc3BvbnNlKHJlcXVlc3QsIHJlc3BvbnNlKSB7XG4gICAgICAgIGNvbnN0IHJlc3BvbnNlSFRNTCA9IGF3YWl0IHJlc3BvbnNlLnJlc3BvbnNlSFRNTDtcbiAgICAgICAgaWYgKHJlc3BvbnNlSFRNTCA9PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgIHRoaXMucmVjb3JkUmVzcG9uc2UoeyBzdGF0dXNDb2RlOiBTeXN0ZW1TdGF0dXNDb2RlLmNvbnRlbnRUeXBlTWlzbWF0Y2ggfSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLnJlY29yZFJlc3BvbnNlKHsgc3RhdHVzQ29kZTogcmVzcG9uc2Uuc3RhdHVzQ29kZSwgcmVzcG9uc2VIVE1MIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJlcXVlc3RFcnJvcmVkKHJlcXVlc3QsIGVycm9yKSB7XG4gICAgICAgIHRoaXMucmVjb3JkUmVzcG9uc2UoeyBzdGF0dXNDb2RlOiBTeXN0ZW1TdGF0dXNDb2RlLm5ldHdvcmtGYWlsdXJlIH0pO1xuICAgIH1cbiAgICByZXF1ZXN0RmluaXNoZWQoKSB7XG4gICAgICAgIHRoaXMuZmluaXNoUmVxdWVzdCgpO1xuICAgIH1cbiAgICBzY3JvbGxUb1Jlc3RvcmVkUG9zaXRpb24oKSB7XG4gICAgICAgIGNvbnN0IHsgc2Nyb2xsUG9zaXRpb24gfSA9IHRoaXMucmVzdG9yYXRpb25EYXRhO1xuICAgICAgICBpZiAoc2Nyb2xsUG9zaXRpb24pIHtcbiAgICAgICAgICAgIHRoaXMudmlldy5zY3JvbGxUb1Bvc2l0aW9uKHNjcm9sbFBvc2l0aW9uKTtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHNjcm9sbFRvQW5jaG9yKCkge1xuICAgICAgICBpZiAodGhpcy5sb2NhdGlvbi5hbmNob3IgIT0gbnVsbCkge1xuICAgICAgICAgICAgdGhpcy52aWV3LnNjcm9sbFRvQW5jaG9yKHRoaXMubG9jYXRpb24uYW5jaG9yKTtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHNjcm9sbFRvVG9wKCkge1xuICAgICAgICB0aGlzLnZpZXcuc2Nyb2xsVG9Qb3NpdGlvbih7IHg6IDAsIHk6IDAgfSk7XG4gICAgfVxuICAgIHJlY29yZFRpbWluZ01ldHJpYyhtZXRyaWMpIHtcbiAgICAgICAgdGhpcy50aW1pbmdNZXRyaWNzW21ldHJpY10gPSBuZXcgRGF0ZSgpLmdldFRpbWUoKTtcbiAgICB9XG4gICAgZ2V0VGltaW5nTWV0cmljcygpIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24oe30sIHRoaXMudGltaW5nTWV0cmljcyk7XG4gICAgfVxuICAgIGdldEhpc3RvcnlNZXRob2RGb3JBY3Rpb24oYWN0aW9uKSB7XG4gICAgICAgIHN3aXRjaCAoYWN0aW9uKSB7XG4gICAgICAgICAgICBjYXNlIFwicmVwbGFjZVwiOiByZXR1cm4gaGlzdG9yeS5yZXBsYWNlU3RhdGU7XG4gICAgICAgICAgICBjYXNlIFwiYWR2YW5jZVwiOlxuICAgICAgICAgICAgY2FzZSBcInJlc3RvcmVcIjogcmV0dXJuIGhpc3RvcnkucHVzaFN0YXRlO1xuICAgICAgICB9XG4gICAgfVxuICAgIGhhc1ByZWxvYWRlZFJlc3BvbnNlKCkge1xuICAgICAgICByZXR1cm4gdHlwZW9mIHRoaXMucmVzcG9uc2UgPT0gXCJvYmplY3RcIjtcbiAgICB9XG4gICAgc2hvdWxkSXNzdWVSZXF1ZXN0KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb24gPT0gXCJyZXN0b3JlXCJcbiAgICAgICAgICAgID8gIXRoaXMuaGFzQ2FjaGVkU25hcHNob3QoKVxuICAgICAgICAgICAgOiB0cnVlO1xuICAgIH1cbiAgICBjYWNoZVNuYXBzaG90KCkge1xuICAgICAgICBpZiAoIXRoaXMuc25hcHNob3RDYWNoZWQpIHtcbiAgICAgICAgICAgIHRoaXMudmlldy5jYWNoZVNuYXBzaG90KCk7XG4gICAgICAgICAgICB0aGlzLnNuYXBzaG90Q2FjaGVkID0gdHJ1ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZW5kZXIoY2FsbGJhY2spIHtcbiAgICAgICAgdGhpcy5jYW5jZWxSZW5kZXIoKTtcbiAgICAgICAgdGhpcy5mcmFtZSA9IHJlcXVlc3RBbmltYXRpb25GcmFtZSgoKSA9PiB7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy5mcmFtZTtcbiAgICAgICAgICAgIGNhbGxiYWNrLmNhbGwodGhpcyk7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBjYW5jZWxSZW5kZXIoKSB7XG4gICAgICAgIGlmICh0aGlzLmZyYW1lKSB7XG4gICAgICAgICAgICBjYW5jZWxBbmltYXRpb25GcmFtZSh0aGlzLmZyYW1lKTtcbiAgICAgICAgICAgIGRlbGV0ZSB0aGlzLmZyYW1lO1xuICAgICAgICB9XG4gICAgfVxufVxuZnVuY3Rpb24gaXNTdWNjZXNzZnVsKHN0YXR1c0NvZGUpIHtcbiAgICByZXR1cm4gc3RhdHVzQ29kZSA+PSAyMDAgJiYgc3RhdHVzQ29kZSA8IDMwMDtcbn1cblxuY2xhc3MgQnJvd3NlckFkYXB0ZXIge1xuICAgIGNvbnN0cnVjdG9yKHNlc3Npb24pIHtcbiAgICAgICAgdGhpcy5wcm9ncmVzc0JhciA9IG5ldyBQcm9ncmVzc0JhcjtcbiAgICAgICAgdGhpcy5zaG93UHJvZ3Jlc3NCYXIgPSAoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLnByb2dyZXNzQmFyLnNob3coKTtcbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5zZXNzaW9uID0gc2Vzc2lvbjtcbiAgICB9XG4gICAgdmlzaXRQcm9wb3NlZFRvTG9jYXRpb24obG9jYXRpb24sIG9wdGlvbnMpIHtcbiAgICAgICAgdGhpcy5uYXZpZ2F0b3Iuc3RhcnRWaXNpdChsb2NhdGlvbiwgdXVpZCgpLCBvcHRpb25zKTtcbiAgICB9XG4gICAgdmlzaXRTdGFydGVkKHZpc2l0KSB7XG4gICAgICAgIHZpc2l0Lmlzc3VlUmVxdWVzdCgpO1xuICAgICAgICB2aXNpdC5jaGFuZ2VIaXN0b3J5KCk7XG4gICAgICAgIHZpc2l0LmxvYWRDYWNoZWRTbmFwc2hvdCgpO1xuICAgIH1cbiAgICB2aXNpdFJlcXVlc3RTdGFydGVkKHZpc2l0KSB7XG4gICAgICAgIHRoaXMucHJvZ3Jlc3NCYXIuc2V0VmFsdWUoMCk7XG4gICAgICAgIGlmICh2aXNpdC5oYXNDYWNoZWRTbmFwc2hvdCgpIHx8IHZpc2l0LmFjdGlvbiAhPSBcInJlc3RvcmVcIikge1xuICAgICAgICAgICAgdGhpcy5zaG93UHJvZ3Jlc3NCYXJBZnRlckRlbGF5KCk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLnNob3dQcm9ncmVzc0JhcigpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHZpc2l0UmVxdWVzdENvbXBsZXRlZCh2aXNpdCkge1xuICAgICAgICB2aXNpdC5sb2FkUmVzcG9uc2UoKTtcbiAgICB9XG4gICAgdmlzaXRSZXF1ZXN0RmFpbGVkV2l0aFN0YXR1c0NvZGUodmlzaXQsIHN0YXR1c0NvZGUpIHtcbiAgICAgICAgc3dpdGNoIChzdGF0dXNDb2RlKSB7XG4gICAgICAgICAgICBjYXNlIFN5c3RlbVN0YXR1c0NvZGUubmV0d29ya0ZhaWx1cmU6XG4gICAgICAgICAgICBjYXNlIFN5c3RlbVN0YXR1c0NvZGUudGltZW91dEZhaWx1cmU6XG4gICAgICAgICAgICBjYXNlIFN5c3RlbVN0YXR1c0NvZGUuY29udGVudFR5cGVNaXNtYXRjaDpcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5yZWxvYWQoKTtcbiAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgICAgcmV0dXJuIHZpc2l0LmxvYWRSZXNwb25zZSgpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHZpc2l0UmVxdWVzdEZpbmlzaGVkKHZpc2l0KSB7XG4gICAgICAgIHRoaXMucHJvZ3Jlc3NCYXIuc2V0VmFsdWUoMSk7XG4gICAgICAgIHRoaXMuaGlkZVByb2dyZXNzQmFyKCk7XG4gICAgfVxuICAgIHZpc2l0Q29tcGxldGVkKHZpc2l0KSB7XG4gICAgICAgIHZpc2l0LmZvbGxvd1JlZGlyZWN0KCk7XG4gICAgfVxuICAgIHBhZ2VJbnZhbGlkYXRlZCgpIHtcbiAgICAgICAgdGhpcy5yZWxvYWQoKTtcbiAgICB9XG4gICAgdmlzaXRGYWlsZWQodmlzaXQpIHtcbiAgICB9XG4gICAgdmlzaXRSZW5kZXJlZCh2aXNpdCkge1xuICAgIH1cbiAgICBzaG93UHJvZ3Jlc3NCYXJBZnRlckRlbGF5KCkge1xuICAgICAgICB0aGlzLnByb2dyZXNzQmFyVGltZW91dCA9IHdpbmRvdy5zZXRUaW1lb3V0KHRoaXMuc2hvd1Byb2dyZXNzQmFyLCB0aGlzLnNlc3Npb24ucHJvZ3Jlc3NCYXJEZWxheSk7XG4gICAgfVxuICAgIGhpZGVQcm9ncmVzc0JhcigpIHtcbiAgICAgICAgdGhpcy5wcm9ncmVzc0Jhci5oaWRlKCk7XG4gICAgICAgIGlmICh0aGlzLnByb2dyZXNzQmFyVGltZW91dCAhPSBudWxsKSB7XG4gICAgICAgICAgICB3aW5kb3cuY2xlYXJUaW1lb3V0KHRoaXMucHJvZ3Jlc3NCYXJUaW1lb3V0KTtcbiAgICAgICAgICAgIGRlbGV0ZSB0aGlzLnByb2dyZXNzQmFyVGltZW91dDtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZWxvYWQoKSB7XG4gICAgICAgIHdpbmRvdy5sb2NhdGlvbi5yZWxvYWQoKTtcbiAgICB9XG4gICAgZ2V0IG5hdmlnYXRvcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2Vzc2lvbi5uYXZpZ2F0b3I7XG4gICAgfVxufVxuXG5jbGFzcyBGb3JtU3VibWl0T2JzZXJ2ZXIge1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLnN1Ym1pdENhcHR1cmVkID0gKCkgPT4ge1xuICAgICAgICAgICAgcmVtb3ZlRXZlbnRMaXN0ZW5lcihcInN1Ym1pdFwiLCB0aGlzLnN1Ym1pdEJ1YmJsZWQsIGZhbHNlKTtcbiAgICAgICAgICAgIGFkZEV2ZW50TGlzdGVuZXIoXCJzdWJtaXRcIiwgdGhpcy5zdWJtaXRCdWJibGVkLCBmYWxzZSk7XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuc3VibWl0QnViYmxlZCA9ICgoZXZlbnQpID0+IHtcbiAgICAgICAgICAgIGlmICghZXZlbnQuZGVmYXVsdFByZXZlbnRlZCkge1xuICAgICAgICAgICAgICAgIGNvbnN0IGZvcm0gPSBldmVudC50YXJnZXQgaW5zdGFuY2VvZiBIVE1MRm9ybUVsZW1lbnQgPyBldmVudC50YXJnZXQgOiB1bmRlZmluZWQ7XG4gICAgICAgICAgICAgICAgY29uc3Qgc3VibWl0dGVyID0gZXZlbnQuc3VibWl0dGVyIHx8IHVuZGVmaW5lZDtcbiAgICAgICAgICAgICAgICBpZiAoZm9ybSkge1xuICAgICAgICAgICAgICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS53aWxsU3VibWl0Rm9ybShmb3JtLCBzdWJtaXR0ZXIpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5mb3JtU3VibWl0dGVkKGZvcm0sIHN1Ym1pdHRlcik7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICBpZiAoIXRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgYWRkRXZlbnRMaXN0ZW5lcihcInN1Ym1pdFwiLCB0aGlzLnN1Ym1pdENhcHR1cmVkLCB0cnVlKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IHRydWU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgcmVtb3ZlRXZlbnRMaXN0ZW5lcihcInN1Ym1pdFwiLCB0aGlzLnN1Ym1pdENhcHR1cmVkLCB0cnVlKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB9XG4gICAgfVxufVxuXG5jbGFzcyBGcmFtZVJlZGlyZWN0b3Ige1xuICAgIGNvbnN0cnVjdG9yKGVsZW1lbnQpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5saW5rSW50ZXJjZXB0b3IgPSBuZXcgTGlua0ludGVyY2VwdG9yKHRoaXMsIGVsZW1lbnQpO1xuICAgICAgICB0aGlzLmZvcm1JbnRlcmNlcHRvciA9IG5ldyBGb3JtSW50ZXJjZXB0b3IodGhpcywgZWxlbWVudCk7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICB0aGlzLmxpbmtJbnRlcmNlcHRvci5zdGFydCgpO1xuICAgICAgICB0aGlzLmZvcm1JbnRlcmNlcHRvci5zdGFydCgpO1xuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICB0aGlzLmxpbmtJbnRlcmNlcHRvci5zdG9wKCk7XG4gICAgICAgIHRoaXMuZm9ybUludGVyY2VwdG9yLnN0b3AoKTtcbiAgICB9XG4gICAgc2hvdWxkSW50ZXJjZXB0TGlua0NsaWNrKGVsZW1lbnQsIHVybCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zaG91bGRSZWRpcmVjdChlbGVtZW50KTtcbiAgICB9XG4gICAgbGlua0NsaWNrSW50ZXJjZXB0ZWQoZWxlbWVudCwgdXJsKSB7XG4gICAgICAgIGNvbnN0IGZyYW1lID0gdGhpcy5maW5kRnJhbWVFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICBpZiAoZnJhbWUpIHtcbiAgICAgICAgICAgIGZyYW1lLnNyYyA9IHVybDtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzaG91bGRJbnRlcmNlcHRGb3JtU3VibWlzc2lvbihlbGVtZW50LCBzdWJtaXR0ZXIpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2hvdWxkUmVkaXJlY3QoZWxlbWVudCwgc3VibWl0dGVyKTtcbiAgICB9XG4gICAgZm9ybVN1Ym1pc3Npb25JbnRlcmNlcHRlZChlbGVtZW50LCBzdWJtaXR0ZXIpIHtcbiAgICAgICAgY29uc3QgZnJhbWUgPSB0aGlzLmZpbmRGcmFtZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIGlmIChmcmFtZSkge1xuICAgICAgICAgICAgZnJhbWUuZm9ybVN1Ym1pc3Npb25JbnRlcmNlcHRlZChlbGVtZW50LCBzdWJtaXR0ZXIpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHNob3VsZFJlZGlyZWN0KGVsZW1lbnQsIHN1Ym1pdHRlcikge1xuICAgICAgICBjb25zdCBmcmFtZSA9IHRoaXMuZmluZEZyYW1lRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgcmV0dXJuIGZyYW1lID8gZnJhbWUgIT0gZWxlbWVudC5jbG9zZXN0KFwidHVyYm8tZnJhbWVcIikgOiBmYWxzZTtcbiAgICB9XG4gICAgZmluZEZyYW1lRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGNvbnN0IGlkID0gZWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJkYXRhLXR1cmJvLWZyYW1lXCIpO1xuICAgICAgICBpZiAoaWQgJiYgaWQgIT0gXCJfdG9wXCIpIHtcbiAgICAgICAgICAgIGNvbnN0IGZyYW1lID0gdGhpcy5lbGVtZW50LnF1ZXJ5U2VsZWN0b3IoYCMke2lkfTpub3QoW2Rpc2FibGVkXSlgKTtcbiAgICAgICAgICAgIGlmIChmcmFtZSBpbnN0YW5jZW9mIEZyYW1lRWxlbWVudCkge1xuICAgICAgICAgICAgICAgIHJldHVybiBmcmFtZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbn1cblxuY2xhc3MgSGlzdG9yeSB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5yZXN0b3JhdGlvbklkZW50aWZpZXIgPSB1dWlkKCk7XG4gICAgICAgIHRoaXMucmVzdG9yYXRpb25EYXRhID0ge307XG4gICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLnBhZ2VMb2FkZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5vblBvcFN0YXRlID0gKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBpZiAodGhpcy5zaG91bGRIYW5kbGVQb3BTdGF0ZSgpKSB7XG4gICAgICAgICAgICAgICAgY29uc3QgeyB0dXJibyB9ID0gZXZlbnQuc3RhdGUgfHwge307XG4gICAgICAgICAgICAgICAgaWYgKHR1cmJvKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnN0IGxvY2F0aW9uID0gTG9jYXRpb24uY3VycmVudExvY2F0aW9uO1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmxvY2F0aW9uID0gbG9jYXRpb247XG4gICAgICAgICAgICAgICAgICAgIGNvbnN0IHsgcmVzdG9yYXRpb25JZGVudGlmaWVyIH0gPSB0dXJibztcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5yZXN0b3JhdGlvbklkZW50aWZpZXIgPSByZXN0b3JhdGlvbklkZW50aWZpZXI7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuaGlzdG9yeVBvcHBlZFRvTG9jYXRpb25XaXRoUmVzdG9yYXRpb25JZGVudGlmaWVyKGxvY2F0aW9uLCByZXN0b3JhdGlvbklkZW50aWZpZXIpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5vblBhZ2VMb2FkID0gYXN5bmMgKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBhd2FpdCBuZXh0TWljcm90YXNrKCk7XG4gICAgICAgICAgICB0aGlzLnBhZ2VMb2FkZWQgPSB0cnVlO1xuICAgICAgICB9O1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICBpZiAoIXRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5wcmV2aW91c1Njcm9sbFJlc3RvcmF0aW9uID0gaGlzdG9yeS5zY3JvbGxSZXN0b3JhdGlvbjtcbiAgICAgICAgICAgIGhpc3Rvcnkuc2Nyb2xsUmVzdG9yYXRpb24gPSBcIm1hbnVhbFwiO1xuICAgICAgICAgICAgYWRkRXZlbnRMaXN0ZW5lcihcInBvcHN0YXRlXCIsIHRoaXMub25Qb3BTdGF0ZSwgZmFsc2UpO1xuICAgICAgICAgICAgYWRkRXZlbnRMaXN0ZW5lcihcImxvYWRcIiwgdGhpcy5vblBhZ2VMb2FkLCBmYWxzZSk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICAgICAgdGhpcy5yZXBsYWNlKExvY2F0aW9uLmN1cnJlbnRMb2NhdGlvbik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICBoaXN0b3J5LnNjcm9sbFJlc3RvcmF0aW9uID0gKF9hID0gdGhpcy5wcmV2aW91c1Njcm9sbFJlc3RvcmF0aW9uKSAhPT0gbnVsbCAmJiBfYSAhPT0gdm9pZCAwID8gX2EgOiBcImF1dG9cIjtcbiAgICAgICAgICAgIHJlbW92ZUV2ZW50TGlzdGVuZXIoXCJwb3BzdGF0ZVwiLCB0aGlzLm9uUG9wU3RhdGUsIGZhbHNlKTtcbiAgICAgICAgICAgIHJlbW92ZUV2ZW50TGlzdGVuZXIoXCJsb2FkXCIsIHRoaXMub25QYWdlTG9hZCwgZmFsc2UpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcHVzaChsb2NhdGlvbiwgcmVzdG9yYXRpb25JZGVudGlmaWVyKSB7XG4gICAgICAgIHRoaXMudXBkYXRlKGhpc3RvcnkucHVzaFN0YXRlLCBsb2NhdGlvbiwgcmVzdG9yYXRpb25JZGVudGlmaWVyKTtcbiAgICB9XG4gICAgcmVwbGFjZShsb2NhdGlvbiwgcmVzdG9yYXRpb25JZGVudGlmaWVyKSB7XG4gICAgICAgIHRoaXMudXBkYXRlKGhpc3RvcnkucmVwbGFjZVN0YXRlLCBsb2NhdGlvbiwgcmVzdG9yYXRpb25JZGVudGlmaWVyKTtcbiAgICB9XG4gICAgdXBkYXRlKG1ldGhvZCwgbG9jYXRpb24sIHJlc3RvcmF0aW9uSWRlbnRpZmllciA9IHV1aWQoKSkge1xuICAgICAgICBjb25zdCBzdGF0ZSA9IHsgdHVyYm86IHsgcmVzdG9yYXRpb25JZGVudGlmaWVyIH0gfTtcbiAgICAgICAgbWV0aG9kLmNhbGwoaGlzdG9yeSwgc3RhdGUsIFwiXCIsIGxvY2F0aW9uLmFic29sdXRlVVJMKTtcbiAgICAgICAgdGhpcy5sb2NhdGlvbiA9IGxvY2F0aW9uO1xuICAgICAgICB0aGlzLnJlc3RvcmF0aW9uSWRlbnRpZmllciA9IHJlc3RvcmF0aW9uSWRlbnRpZmllcjtcbiAgICB9XG4gICAgZ2V0UmVzdG9yYXRpb25EYXRhRm9ySWRlbnRpZmllcihyZXN0b3JhdGlvbklkZW50aWZpZXIpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMucmVzdG9yYXRpb25EYXRhW3Jlc3RvcmF0aW9uSWRlbnRpZmllcl0gfHwge307XG4gICAgfVxuICAgIHVwZGF0ZVJlc3RvcmF0aW9uRGF0YShhZGRpdGlvbmFsRGF0YSkge1xuICAgICAgICBjb25zdCB7IHJlc3RvcmF0aW9uSWRlbnRpZmllciB9ID0gdGhpcztcbiAgICAgICAgY29uc3QgcmVzdG9yYXRpb25EYXRhID0gdGhpcy5yZXN0b3JhdGlvbkRhdGFbcmVzdG9yYXRpb25JZGVudGlmaWVyXTtcbiAgICAgICAgdGhpcy5yZXN0b3JhdGlvbkRhdGFbcmVzdG9yYXRpb25JZGVudGlmaWVyXSA9IE9iamVjdC5hc3NpZ24oT2JqZWN0LmFzc2lnbih7fSwgcmVzdG9yYXRpb25EYXRhKSwgYWRkaXRpb25hbERhdGEpO1xuICAgIH1cbiAgICBzaG91bGRIYW5kbGVQb3BTdGF0ZSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMucGFnZUlzTG9hZGVkKCk7XG4gICAgfVxuICAgIHBhZ2VJc0xvYWRlZCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMucGFnZUxvYWRlZCB8fCBkb2N1bWVudC5yZWFkeVN0YXRlID09IFwiY29tcGxldGVcIjtcbiAgICB9XG59XG5cbmNsYXNzIExpbmtDbGlja09ic2VydmVyIHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5jbGlja0NhcHR1cmVkID0gKCkgPT4ge1xuICAgICAgICAgICAgcmVtb3ZlRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsIHRoaXMuY2xpY2tCdWJibGVkLCBmYWxzZSk7XG4gICAgICAgICAgICBhZGRFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgdGhpcy5jbGlja0J1YmJsZWQsIGZhbHNlKTtcbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5jbGlja0J1YmJsZWQgPSAoZXZlbnQpID0+IHtcbiAgICAgICAgICAgIGlmICh0aGlzLmNsaWNrRXZlbnRJc1NpZ25pZmljYW50KGV2ZW50KSkge1xuICAgICAgICAgICAgICAgIGNvbnN0IGxpbmsgPSB0aGlzLmZpbmRMaW5rRnJvbUNsaWNrVGFyZ2V0KGV2ZW50LnRhcmdldCk7XG4gICAgICAgICAgICAgICAgaWYgKGxpbmspIHtcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgbG9jYXRpb24gPSB0aGlzLmdldExvY2F0aW9uRm9yTGluayhsaW5rKTtcbiAgICAgICAgICAgICAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUud2lsbEZvbGxvd0xpbmtUb0xvY2F0aW9uKGxpbmssIGxvY2F0aW9uKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZm9sbG93ZWRMaW5rVG9Mb2NhdGlvbihsaW5rLCBsb2NhdGlvbik7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICB9XG4gICAgc3RhcnQoKSB7XG4gICAgICAgIGlmICghdGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICBhZGRFdmVudExpc3RlbmVyKFwiY2xpY2tcIiwgdGhpcy5jbGlja0NhcHR1cmVkLCB0cnVlKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IHRydWU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgcmVtb3ZlRXZlbnRMaXN0ZW5lcihcImNsaWNrXCIsIHRoaXMuY2xpY2tDYXB0dXJlZCwgdHJ1ZSk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBjbGlja0V2ZW50SXNTaWduaWZpY2FudChldmVudCkge1xuICAgICAgICByZXR1cm4gISgoZXZlbnQudGFyZ2V0ICYmIGV2ZW50LnRhcmdldC5pc0NvbnRlbnRFZGl0YWJsZSlcbiAgICAgICAgICAgIHx8IGV2ZW50LmRlZmF1bHRQcmV2ZW50ZWRcbiAgICAgICAgICAgIHx8IGV2ZW50LndoaWNoID4gMVxuICAgICAgICAgICAgfHwgZXZlbnQuYWx0S2V5XG4gICAgICAgICAgICB8fCBldmVudC5jdHJsS2V5XG4gICAgICAgICAgICB8fCBldmVudC5tZXRhS2V5XG4gICAgICAgICAgICB8fCBldmVudC5zaGlmdEtleSk7XG4gICAgfVxuICAgIGZpbmRMaW5rRnJvbUNsaWNrVGFyZ2V0KHRhcmdldCkge1xuICAgICAgICBpZiAodGFyZ2V0IGluc3RhbmNlb2YgRWxlbWVudCkge1xuICAgICAgICAgICAgcmV0dXJuIHRhcmdldC5jbG9zZXN0KFwiYVtocmVmXTpub3QoW3RhcmdldF49X10pOm5vdChbZG93bmxvYWRdKVwiKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXRMb2NhdGlvbkZvckxpbmsobGluaykge1xuICAgICAgICByZXR1cm4gbmV3IExvY2F0aW9uKGxpbmsuZ2V0QXR0cmlidXRlKFwiaHJlZlwiKSB8fCBcIlwiKTtcbiAgICB9XG59XG5cbmNsYXNzIE5hdmlnYXRvciB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgIH1cbiAgICBwcm9wb3NlVmlzaXQobG9jYXRpb24sIG9wdGlvbnMgPSB7fSkge1xuICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5hbGxvd3NWaXNpdGluZ0xvY2F0aW9uKGxvY2F0aW9uKSkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS52aXNpdFByb3Bvc2VkVG9Mb2NhdGlvbihsb2NhdGlvbiwgb3B0aW9ucyk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RhcnRWaXNpdChsb2NhdGlvbiwgcmVzdG9yYXRpb25JZGVudGlmaWVyLCBvcHRpb25zID0ge30pIHtcbiAgICAgICAgdGhpcy5zdG9wKCk7XG4gICAgICAgIHRoaXMuY3VycmVudFZpc2l0ID0gbmV3IFZpc2l0KHRoaXMsIExvY2F0aW9uLndyYXAobG9jYXRpb24pLCByZXN0b3JhdGlvbklkZW50aWZpZXIsIE9iamVjdC5hc3NpZ24oeyByZWZlcnJlcjogdGhpcy5sb2NhdGlvbiB9LCBvcHRpb25zKSk7XG4gICAgICAgIHRoaXMuY3VycmVudFZpc2l0LnN0YXJ0KCk7XG4gICAgfVxuICAgIHN1Ym1pdEZvcm0oZm9ybSwgc3VibWl0dGVyKSB7XG4gICAgICAgIHRoaXMuc3RvcCgpO1xuICAgICAgICB0aGlzLmZvcm1TdWJtaXNzaW9uID0gbmV3IEZvcm1TdWJtaXNzaW9uKHRoaXMsIGZvcm0sIHN1Ym1pdHRlciwgdHJ1ZSk7XG4gICAgICAgIHRoaXMuZm9ybVN1Ym1pc3Npb24uc3RhcnQoKTtcbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgaWYgKHRoaXMuZm9ybVN1Ym1pc3Npb24pIHtcbiAgICAgICAgICAgIHRoaXMuZm9ybVN1Ym1pc3Npb24uc3RvcCgpO1xuICAgICAgICAgICAgZGVsZXRlIHRoaXMuZm9ybVN1Ym1pc3Npb247XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMuY3VycmVudFZpc2l0KSB7XG4gICAgICAgICAgICB0aGlzLmN1cnJlbnRWaXNpdC5jYW5jZWwoKTtcbiAgICAgICAgICAgIGRlbGV0ZSB0aGlzLmN1cnJlbnRWaXNpdDtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXQgYWRhcHRlcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVsZWdhdGUuYWRhcHRlcjtcbiAgICB9XG4gICAgZ2V0IHZpZXcoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmRlbGVnYXRlLnZpZXc7XG4gICAgfVxuICAgIGdldCBoaXN0b3J5KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5kZWxlZ2F0ZS5oaXN0b3J5O1xuICAgIH1cbiAgICBmb3JtU3VibWlzc2lvblN0YXJ0ZWQoZm9ybVN1Ym1pc3Npb24pIHtcbiAgICB9XG4gICAgYXN5bmMgZm9ybVN1Ym1pc3Npb25TdWNjZWVkZWRXaXRoUmVzcG9uc2UoZm9ybVN1Ym1pc3Npb24sIGZldGNoUmVzcG9uc2UpIHtcbiAgICAgICAgY29uc29sZS5sb2coXCJGb3JtIHN1Ym1pc3Npb24gc3VjY2VlZGVkXCIsIGZvcm1TdWJtaXNzaW9uKTtcbiAgICAgICAgaWYgKGZvcm1TdWJtaXNzaW9uID09IHRoaXMuZm9ybVN1Ym1pc3Npb24pIHtcbiAgICAgICAgICAgIGNvbnN0IHJlc3BvbnNlSFRNTCA9IGF3YWl0IGZldGNoUmVzcG9uc2UucmVzcG9uc2VIVE1MO1xuICAgICAgICAgICAgaWYgKHJlc3BvbnNlSFRNTCkge1xuICAgICAgICAgICAgICAgIGlmIChmb3JtU3VibWlzc2lvbi5tZXRob2QgIT0gRmV0Y2hNZXRob2QuZ2V0KSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnNvbGUubG9nKFwiQ2xlYXJpbmcgc25hcHNob3QgY2FjaGUgYWZ0ZXIgc3VjY2Vzc2Z1bCBmb3JtIHN1Ym1pc3Npb25cIik7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMudmlldy5jbGVhclNuYXBzaG90Q2FjaGUoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgY29uc3QgeyBzdGF0dXNDb2RlIH0gPSBmZXRjaFJlc3BvbnNlO1xuICAgICAgICAgICAgICAgIGNvbnN0IHZpc2l0T3B0aW9ucyA9IHsgcmVzcG9uc2U6IHsgc3RhdHVzQ29kZSwgcmVzcG9uc2VIVE1MIH0gfTtcbiAgICAgICAgICAgICAgICBjb25zb2xlLmxvZyhcIlZpc2l0aW5nXCIsIGZldGNoUmVzcG9uc2UubG9jYXRpb24sIHZpc2l0T3B0aW9ucyk7XG4gICAgICAgICAgICAgICAgdGhpcy5wcm9wb3NlVmlzaXQoZmV0Y2hSZXNwb25zZS5sb2NhdGlvbiwgdmlzaXRPcHRpb25zKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbiAgICBmb3JtU3VibWlzc2lvbkZhaWxlZFdpdGhSZXNwb25zZShmb3JtU3VibWlzc2lvbiwgZmV0Y2hSZXNwb25zZSkge1xuICAgICAgICBjb25zb2xlLmVycm9yKFwiRm9ybSBzdWJtaXNzaW9uIGZhaWxlZFwiLCBmb3JtU3VibWlzc2lvbiwgZmV0Y2hSZXNwb25zZSk7XG4gICAgfVxuICAgIGZvcm1TdWJtaXNzaW9uRXJyb3JlZChmb3JtU3VibWlzc2lvbiwgZXJyb3IpIHtcbiAgICAgICAgY29uc29sZS5lcnJvcihcIkZvcm0gc3VibWlzc2lvbiBmYWlsZWRcIiwgZm9ybVN1Ym1pc3Npb24sIGVycm9yKTtcbiAgICB9XG4gICAgZm9ybVN1Ym1pc3Npb25GaW5pc2hlZChmb3JtU3VibWlzc2lvbikge1xuICAgIH1cbiAgICB2aXNpdFN0YXJ0ZWQodmlzaXQpIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS52aXNpdFN0YXJ0ZWQodmlzaXQpO1xuICAgIH1cbiAgICB2aXNpdENvbXBsZXRlZCh2aXNpdCkge1xuICAgICAgICB0aGlzLmRlbGVnYXRlLnZpc2l0Q29tcGxldGVkKHZpc2l0KTtcbiAgICB9XG4gICAgZ2V0IGxvY2F0aW9uKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5oaXN0b3J5LmxvY2F0aW9uO1xuICAgIH1cbiAgICBnZXQgcmVzdG9yYXRpb25JZGVudGlmaWVyKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5oaXN0b3J5LnJlc3RvcmF0aW9uSWRlbnRpZmllcjtcbiAgICB9XG59XG5cbnZhciBQYWdlU3RhZ2U7XG4oZnVuY3Rpb24gKFBhZ2VTdGFnZSkge1xuICAgIFBhZ2VTdGFnZVtQYWdlU3RhZ2VbXCJpbml0aWFsXCJdID0gMF0gPSBcImluaXRpYWxcIjtcbiAgICBQYWdlU3RhZ2VbUGFnZVN0YWdlW1wibG9hZGluZ1wiXSA9IDFdID0gXCJsb2FkaW5nXCI7XG4gICAgUGFnZVN0YWdlW1BhZ2VTdGFnZVtcImludGVyYWN0aXZlXCJdID0gMl0gPSBcImludGVyYWN0aXZlXCI7XG4gICAgUGFnZVN0YWdlW1BhZ2VTdGFnZVtcImNvbXBsZXRlXCJdID0gM10gPSBcImNvbXBsZXRlXCI7XG4gICAgUGFnZVN0YWdlW1BhZ2VTdGFnZVtcImludmFsaWRhdGVkXCJdID0gNF0gPSBcImludmFsaWRhdGVkXCI7XG59KShQYWdlU3RhZ2UgfHwgKFBhZ2VTdGFnZSA9IHt9KSk7XG5jbGFzcyBQYWdlT2JzZXJ2ZXIge1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuc3RhZ2UgPSBQYWdlU3RhZ2UuaW5pdGlhbDtcbiAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMuaW50ZXJwcmV0UmVhZHlTdGF0ZSA9ICgpID0+IHtcbiAgICAgICAgICAgIGNvbnN0IHsgcmVhZHlTdGF0ZSB9ID0gdGhpcztcbiAgICAgICAgICAgIGlmIChyZWFkeVN0YXRlID09IFwiaW50ZXJhY3RpdmVcIikge1xuICAgICAgICAgICAgICAgIHRoaXMucGFnZUlzSW50ZXJhY3RpdmUoKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2UgaWYgKHJlYWR5U3RhdGUgPT0gXCJjb21wbGV0ZVwiKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5wYWdlSXNDb21wbGV0ZSgpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9O1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICBpZiAoIXRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgaWYgKHRoaXMuc3RhZ2UgPT0gUGFnZVN0YWdlLmluaXRpYWwpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnN0YWdlID0gUGFnZVN0YWdlLmxvYWRpbmc7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKFwicmVhZHlzdGF0ZWNoYW5nZVwiLCB0aGlzLmludGVycHJldFJlYWR5U3RhdGUsIGZhbHNlKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IHRydWU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgZG9jdW1lbnQucmVtb3ZlRXZlbnRMaXN0ZW5lcihcInJlYWR5c3RhdGVjaGFuZ2VcIiwgdGhpcy5pbnRlcnByZXRSZWFkeVN0YXRlLCBmYWxzZSk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBpbnZhbGlkYXRlKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFnZSAhPSBQYWdlU3RhZ2UuaW52YWxpZGF0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhZ2UgPSBQYWdlU3RhZ2UuaW52YWxpZGF0ZWQ7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnBhZ2VJbnZhbGlkYXRlZCgpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHBhZ2VJc0ludGVyYWN0aXZlKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFnZSA9PSBQYWdlU3RhZ2UubG9hZGluZykge1xuICAgICAgICAgICAgdGhpcy5zdGFnZSA9IFBhZ2VTdGFnZS5pbnRlcmFjdGl2ZTtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUucGFnZUJlY2FtZUludGVyYWN0aXZlKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcGFnZUlzQ29tcGxldGUoKSB7XG4gICAgICAgIHRoaXMucGFnZUlzSW50ZXJhY3RpdmUoKTtcbiAgICAgICAgaWYgKHRoaXMuc3RhZ2UgPT0gUGFnZVN0YWdlLmludGVyYWN0aXZlKSB7XG4gICAgICAgICAgICB0aGlzLnN0YWdlID0gUGFnZVN0YWdlLmNvbXBsZXRlO1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5wYWdlTG9hZGVkKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0IHJlYWR5U3RhdGUoKSB7XG4gICAgICAgIHJldHVybiBkb2N1bWVudC5yZWFkeVN0YXRlO1xuICAgIH1cbn1cblxuY2xhc3MgU2Nyb2xsT2JzZXJ2ZXIge1xuICAgIGNvbnN0cnVjdG9yKGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLm9uU2Nyb2xsID0gKCkgPT4ge1xuICAgICAgICAgICAgdGhpcy51cGRhdGVQb3NpdGlvbih7IHg6IHdpbmRvdy5wYWdlWE9mZnNldCwgeTogd2luZG93LnBhZ2VZT2Zmc2V0IH0pO1xuICAgICAgICB9O1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICBpZiAoIXRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgYWRkRXZlbnRMaXN0ZW5lcihcInNjcm9sbFwiLCB0aGlzLm9uU2Nyb2xsLCBmYWxzZSk7XG4gICAgICAgICAgICB0aGlzLm9uU2Nyb2xsKCk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHJlbW92ZUV2ZW50TGlzdGVuZXIoXCJzY3JvbGxcIiwgdGhpcy5vblNjcm9sbCwgZmFsc2UpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9XG4gICAgdXBkYXRlUG9zaXRpb24ocG9zaXRpb24pIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zY3JvbGxQb3NpdGlvbkNoYW5nZWQocG9zaXRpb24pO1xuICAgIH1cbn1cblxuY2xhc3MgU3RyZWFtTWVzc2FnZSB7XG4gICAgY29uc3RydWN0b3IoaHRtbCkge1xuICAgICAgICB0aGlzLnRlbXBsYXRlRWxlbWVudCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJ0ZW1wbGF0ZVwiKTtcbiAgICAgICAgdGhpcy50ZW1wbGF0ZUVsZW1lbnQuaW5uZXJIVE1MID0gaHRtbDtcbiAgICB9XG4gICAgc3RhdGljIHdyYXAobWVzc2FnZSkge1xuICAgICAgICBpZiAodHlwZW9mIG1lc3NhZ2UgPT0gXCJzdHJpbmdcIikge1xuICAgICAgICAgICAgcmV0dXJuIG5ldyB0aGlzKG1lc3NhZ2UpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIG1lc3NhZ2U7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0IGZyYWdtZW50KCkge1xuICAgICAgICBjb25zdCBmcmFnbWVudCA9IGRvY3VtZW50LmNyZWF0ZURvY3VtZW50RnJhZ21lbnQoKTtcbiAgICAgICAgZm9yIChjb25zdCBlbGVtZW50IG9mIHRoaXMuZm9yZWlnbkVsZW1lbnRzKSB7XG4gICAgICAgICAgICBmcmFnbWVudC5hcHBlbmRDaGlsZChkb2N1bWVudC5pbXBvcnROb2RlKGVsZW1lbnQsIHRydWUpKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZnJhZ21lbnQ7XG4gICAgfVxuICAgIGdldCBmb3JlaWduRWxlbWVudHMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnRlbXBsYXRlQ2hpbGRyZW4ucmVkdWNlKChzdHJlYW1FbGVtZW50cywgY2hpbGQpID0+IHtcbiAgICAgICAgICAgIGlmIChjaGlsZC50YWdOYW1lLnRvTG93ZXJDYXNlKCkgPT0gXCJ0dXJiby1zdHJlYW1cIikge1xuICAgICAgICAgICAgICAgIHJldHVybiBbLi4uc3RyZWFtRWxlbWVudHMsIGNoaWxkXTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIHJldHVybiBzdHJlYW1FbGVtZW50cztcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSwgW10pO1xuICAgIH1cbiAgICBnZXQgdGVtcGxhdGVDaGlsZHJlbigpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy50ZW1wbGF0ZUVsZW1lbnQuY29udGVudC5jaGlsZHJlbik7XG4gICAgfVxufVxuXG5jbGFzcyBTdHJlYW1PYnNlcnZlciB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5zb3VyY2VzID0gbmV3IFNldDtcbiAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMucHJlcGFyZUZldGNoUmVxdWVzdCA9ICgoZXZlbnQpID0+IHtcbiAgICAgICAgICAgIHZhciBfYTtcbiAgICAgICAgICAgIGNvbnN0IGZldGNoT3B0aW9ucyA9IChfYSA9IGV2ZW50LmRldGFpbCkgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLmZldGNoT3B0aW9ucztcbiAgICAgICAgICAgIGlmIChmZXRjaE9wdGlvbnMpIHtcbiAgICAgICAgICAgICAgICBjb25zdCB7IGhlYWRlcnMgfSA9IGZldGNoT3B0aW9ucztcbiAgICAgICAgICAgICAgICBoZWFkZXJzLkFjY2VwdCA9IFtcInRleHQvaHRtbDsgdHVyYm8tc3RyZWFtXCIsIGhlYWRlcnMuQWNjZXB0XS5qb2luKFwiLCBcIik7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgICB0aGlzLmluc3BlY3RGZXRjaFJlc3BvbnNlID0gKChldmVudCkgPT4ge1xuICAgICAgICAgICAgY29uc3QgcmVzcG9uc2UgPSBmZXRjaFJlc3BvbnNlRnJvbUV2ZW50KGV2ZW50KTtcbiAgICAgICAgICAgIGlmIChyZXNwb25zZSAmJiBmZXRjaFJlc3BvbnNlSXNTdHJlYW0ocmVzcG9uc2UpKSB7XG4gICAgICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICAgICB0aGlzLnJlY2VpdmVNZXNzYWdlUmVzcG9uc2UocmVzcG9uc2UpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy5yZWNlaXZlTWVzc2FnZUV2ZW50ID0gKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBpZiAodGhpcy5zdGFydGVkICYmIHR5cGVvZiBldmVudC5kYXRhID09IFwic3RyaW5nXCIpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnJlY2VpdmVNZXNzYWdlSFRNTChldmVudC5kYXRhKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IHRydWU7XG4gICAgICAgICAgICBhZGRFdmVudExpc3RlbmVyKFwidHVyYm86YmVmb3JlLWZldGNoLXJlcXVlc3RcIiwgdGhpcy5wcmVwYXJlRmV0Y2hSZXF1ZXN0LCB0cnVlKTtcbiAgICAgICAgICAgIGFkZEV2ZW50TGlzdGVuZXIoXCJ0dXJibzpiZWZvcmUtZmV0Y2gtcmVzcG9uc2VcIiwgdGhpcy5pbnNwZWN0RmV0Y2hSZXNwb25zZSwgZmFsc2UpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICAgICAgcmVtb3ZlRXZlbnRMaXN0ZW5lcihcInR1cmJvOmJlZm9yZS1mZXRjaC1yZXF1ZXN0XCIsIHRoaXMucHJlcGFyZUZldGNoUmVxdWVzdCwgdHJ1ZSk7XG4gICAgICAgICAgICByZW1vdmVFdmVudExpc3RlbmVyKFwidHVyYm86YmVmb3JlLWZldGNoLXJlc3BvbnNlXCIsIHRoaXMuaW5zcGVjdEZldGNoUmVzcG9uc2UsIGZhbHNlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBjb25uZWN0U3RyZWFtU291cmNlKHNvdXJjZSkge1xuICAgICAgICBpZiAoIXRoaXMuc3RyZWFtU291cmNlSXNDb25uZWN0ZWQoc291cmNlKSkge1xuICAgICAgICAgICAgdGhpcy5zb3VyY2VzLmFkZChzb3VyY2UpO1xuICAgICAgICAgICAgc291cmNlLmFkZEV2ZW50TGlzdGVuZXIoXCJtZXNzYWdlXCIsIHRoaXMucmVjZWl2ZU1lc3NhZ2VFdmVudCwgZmFsc2UpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGRpc2Nvbm5lY3RTdHJlYW1Tb3VyY2Uoc291cmNlKSB7XG4gICAgICAgIGlmICh0aGlzLnN0cmVhbVNvdXJjZUlzQ29ubmVjdGVkKHNvdXJjZSkpIHtcbiAgICAgICAgICAgIHRoaXMuc291cmNlcy5kZWxldGUoc291cmNlKTtcbiAgICAgICAgICAgIHNvdXJjZS5yZW1vdmVFdmVudExpc3RlbmVyKFwibWVzc2FnZVwiLCB0aGlzLnJlY2VpdmVNZXNzYWdlRXZlbnQsIGZhbHNlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdHJlYW1Tb3VyY2VJc0Nvbm5lY3RlZChzb3VyY2UpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc291cmNlcy5oYXMoc291cmNlKTtcbiAgICB9XG4gICAgYXN5bmMgcmVjZWl2ZU1lc3NhZ2VSZXNwb25zZShyZXNwb25zZSkge1xuICAgICAgICBjb25zdCBodG1sID0gYXdhaXQgcmVzcG9uc2UucmVzcG9uc2VIVE1MO1xuICAgICAgICBpZiAoaHRtbCkge1xuICAgICAgICAgICAgdGhpcy5yZWNlaXZlTWVzc2FnZUhUTUwoaHRtbCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVjZWl2ZU1lc3NhZ2VIVE1MKGh0bWwpIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS5yZWNlaXZlZE1lc3NhZ2VGcm9tU3RyZWFtKG5ldyBTdHJlYW1NZXNzYWdlKGh0bWwpKTtcbiAgICB9XG59XG5mdW5jdGlvbiBmZXRjaFJlc3BvbnNlRnJvbUV2ZW50KGV2ZW50KSB7XG4gICAgdmFyIF9hO1xuICAgIGNvbnN0IGZldGNoUmVzcG9uc2UgPSAoX2EgPSBldmVudC5kZXRhaWwpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5mZXRjaFJlc3BvbnNlO1xuICAgIGlmIChmZXRjaFJlc3BvbnNlIGluc3RhbmNlb2YgRmV0Y2hSZXNwb25zZSkge1xuICAgICAgICByZXR1cm4gZmV0Y2hSZXNwb25zZTtcbiAgICB9XG59XG5mdW5jdGlvbiBmZXRjaFJlc3BvbnNlSXNTdHJlYW0ocmVzcG9uc2UpIHtcbiAgICB2YXIgX2E7XG4gICAgY29uc3QgY29udGVudFR5cGUgPSAoX2EgPSByZXNwb25zZS5jb250ZW50VHlwZSkgIT09IG51bGwgJiYgX2EgIT09IHZvaWQgMCA/IF9hIDogXCJcIjtcbiAgICByZXR1cm4gL3RleHRcXC9odG1sOy4qXFxidHVyYm8tc3RyZWFtXFxiLy50ZXN0KGNvbnRlbnRUeXBlKTtcbn1cblxuZnVuY3Rpb24gaXNBY3Rpb24oYWN0aW9uKSB7XG4gICAgcmV0dXJuIGFjdGlvbiA9PSBcImFkdmFuY2VcIiB8fCBhY3Rpb24gPT0gXCJyZXBsYWNlXCIgfHwgYWN0aW9uID09IFwicmVzdG9yZVwiO1xufVxuXG5jbGFzcyBSZW5kZXJlciB7XG4gICAgcmVuZGVyVmlldyhjYWxsYmFjaykge1xuICAgICAgICB0aGlzLmRlbGVnYXRlLnZpZXdXaWxsUmVuZGVyKHRoaXMubmV3Qm9keSk7XG4gICAgICAgIGNhbGxiYWNrKCk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUudmlld1JlbmRlcmVkKHRoaXMubmV3Qm9keSk7XG4gICAgfVxuICAgIGludmFsaWRhdGVWaWV3KCkge1xuICAgICAgICB0aGlzLmRlbGVnYXRlLnZpZXdJbnZhbGlkYXRlZCgpO1xuICAgIH1cbiAgICBjcmVhdGVTY3JpcHRFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgaWYgKGVsZW1lbnQuZ2V0QXR0cmlidXRlKFwiZGF0YS10dXJiby1ldmFsXCIpID09IFwiZmFsc2VcIikge1xuICAgICAgICAgICAgcmV0dXJuIGVsZW1lbnQ7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICBjb25zdCBjcmVhdGVkU2NyaXB0RWxlbWVudCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJzY3JpcHRcIik7XG4gICAgICAgICAgICBjcmVhdGVkU2NyaXB0RWxlbWVudC50ZXh0Q29udGVudCA9IGVsZW1lbnQudGV4dENvbnRlbnQ7XG4gICAgICAgICAgICBjcmVhdGVkU2NyaXB0RWxlbWVudC5hc3luYyA9IGZhbHNlO1xuICAgICAgICAgICAgY29weUVsZW1lbnRBdHRyaWJ1dGVzKGNyZWF0ZWRTY3JpcHRFbGVtZW50LCBlbGVtZW50KTtcbiAgICAgICAgICAgIHJldHVybiBjcmVhdGVkU2NyaXB0RWxlbWVudDtcbiAgICAgICAgfVxuICAgIH1cbn1cbmZ1bmN0aW9uIGNvcHlFbGVtZW50QXR0cmlidXRlcyhkZXN0aW5hdGlvbkVsZW1lbnQsIHNvdXJjZUVsZW1lbnQpIHtcbiAgICBmb3IgKGNvbnN0IHsgbmFtZSwgdmFsdWUgfSBvZiBbLi4uc291cmNlRWxlbWVudC5hdHRyaWJ1dGVzXSkge1xuICAgICAgICBkZXN0aW5hdGlvbkVsZW1lbnQuc2V0QXR0cmlidXRlKG5hbWUsIHZhbHVlKTtcbiAgICB9XG59XG5cbmNsYXNzIEVycm9yUmVuZGVyZXIgZXh0ZW5kcyBSZW5kZXJlciB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUsIGh0bWwpIHtcbiAgICAgICAgc3VwZXIoKTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLmh0bWxFbGVtZW50ID0gKCgpID0+IHtcbiAgICAgICAgICAgIGNvbnN0IGh0bWxFbGVtZW50ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudChcImh0bWxcIik7XG4gICAgICAgICAgICBodG1sRWxlbWVudC5pbm5lckhUTUwgPSBodG1sO1xuICAgICAgICAgICAgcmV0dXJuIGh0bWxFbGVtZW50O1xuICAgICAgICB9KSgpO1xuICAgICAgICB0aGlzLm5ld0hlYWQgPSB0aGlzLmh0bWxFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoXCJoZWFkXCIpIHx8IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJoZWFkXCIpO1xuICAgICAgICB0aGlzLm5ld0JvZHkgPSB0aGlzLmh0bWxFbGVtZW50LnF1ZXJ5U2VsZWN0b3IoXCJib2R5XCIpIHx8IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJib2R5XCIpO1xuICAgIH1cbiAgICBzdGF0aWMgcmVuZGVyKGRlbGVnYXRlLCBjYWxsYmFjaywgaHRtbCkge1xuICAgICAgICByZXR1cm4gbmV3IHRoaXMoZGVsZWdhdGUsIGh0bWwpLnJlbmRlcihjYWxsYmFjayk7XG4gICAgfVxuICAgIHJlbmRlcihjYWxsYmFjaykge1xuICAgICAgICB0aGlzLnJlbmRlclZpZXcoKCkgPT4ge1xuICAgICAgICAgICAgdGhpcy5yZXBsYWNlSGVhZEFuZEJvZHkoKTtcbiAgICAgICAgICAgIHRoaXMuYWN0aXZhdGVCb2R5U2NyaXB0RWxlbWVudHMoKTtcbiAgICAgICAgICAgIGNhbGxiYWNrKCk7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICByZXBsYWNlSGVhZEFuZEJvZHkoKSB7XG4gICAgICAgIGNvbnN0IHsgZG9jdW1lbnRFbGVtZW50LCBoZWFkLCBib2R5IH0gPSBkb2N1bWVudDtcbiAgICAgICAgZG9jdW1lbnRFbGVtZW50LnJlcGxhY2VDaGlsZCh0aGlzLm5ld0hlYWQsIGhlYWQpO1xuICAgICAgICBkb2N1bWVudEVsZW1lbnQucmVwbGFjZUNoaWxkKHRoaXMubmV3Qm9keSwgYm9keSk7XG4gICAgfVxuICAgIGFjdGl2YXRlQm9keVNjcmlwdEVsZW1lbnRzKCkge1xuICAgICAgICBmb3IgKGNvbnN0IHJlcGxhY2VhYmxlRWxlbWVudCBvZiB0aGlzLmdldFNjcmlwdEVsZW1lbnRzKCkpIHtcbiAgICAgICAgICAgIGNvbnN0IHBhcmVudE5vZGUgPSByZXBsYWNlYWJsZUVsZW1lbnQucGFyZW50Tm9kZTtcbiAgICAgICAgICAgIGlmIChwYXJlbnROb2RlKSB7XG4gICAgICAgICAgICAgICAgY29uc3QgZWxlbWVudCA9IHRoaXMuY3JlYXRlU2NyaXB0RWxlbWVudChyZXBsYWNlYWJsZUVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIHBhcmVudE5vZGUucmVwbGFjZUNoaWxkKGVsZW1lbnQsIHJlcGxhY2VhYmxlRWxlbWVudCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0U2NyaXB0RWxlbWVudHMoKSB7XG4gICAgICAgIHJldHVybiBbLi4uZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoXCJzY3JpcHRcIildO1xuICAgIH1cbn1cblxuY2xhc3MgU25hcHNob3RDYWNoZSB7XG4gICAgY29uc3RydWN0b3Ioc2l6ZSkge1xuICAgICAgICB0aGlzLmtleXMgPSBbXTtcbiAgICAgICAgdGhpcy5zbmFwc2hvdHMgPSB7fTtcbiAgICAgICAgdGhpcy5zaXplID0gc2l6ZTtcbiAgICB9XG4gICAgaGFzKGxvY2F0aW9uKSB7XG4gICAgICAgIHJldHVybiBsb2NhdGlvbi50b0NhY2hlS2V5KCkgaW4gdGhpcy5zbmFwc2hvdHM7XG4gICAgfVxuICAgIGdldChsb2NhdGlvbikge1xuICAgICAgICBpZiAodGhpcy5oYXMobG9jYXRpb24pKSB7XG4gICAgICAgICAgICBjb25zdCBzbmFwc2hvdCA9IHRoaXMucmVhZChsb2NhdGlvbik7XG4gICAgICAgICAgICB0aGlzLnRvdWNoKGxvY2F0aW9uKTtcbiAgICAgICAgICAgIHJldHVybiBzbmFwc2hvdDtcbiAgICAgICAgfVxuICAgIH1cbiAgICBwdXQobG9jYXRpb24sIHNuYXBzaG90KSB7XG4gICAgICAgIHRoaXMud3JpdGUobG9jYXRpb24sIHNuYXBzaG90KTtcbiAgICAgICAgdGhpcy50b3VjaChsb2NhdGlvbik7XG4gICAgICAgIHJldHVybiBzbmFwc2hvdDtcbiAgICB9XG4gICAgY2xlYXIoKSB7XG4gICAgICAgIHRoaXMuc25hcHNob3RzID0ge307XG4gICAgfVxuICAgIHJlYWQobG9jYXRpb24pIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc25hcHNob3RzW2xvY2F0aW9uLnRvQ2FjaGVLZXkoKV07XG4gICAgfVxuICAgIHdyaXRlKGxvY2F0aW9uLCBzbmFwc2hvdCkge1xuICAgICAgICB0aGlzLnNuYXBzaG90c1tsb2NhdGlvbi50b0NhY2hlS2V5KCldID0gc25hcHNob3Q7XG4gICAgfVxuICAgIHRvdWNoKGxvY2F0aW9uKSB7XG4gICAgICAgIGNvbnN0IGtleSA9IGxvY2F0aW9uLnRvQ2FjaGVLZXkoKTtcbiAgICAgICAgY29uc3QgaW5kZXggPSB0aGlzLmtleXMuaW5kZXhPZihrZXkpO1xuICAgICAgICBpZiAoaW5kZXggPiAtMSlcbiAgICAgICAgICAgIHRoaXMua2V5cy5zcGxpY2UoaW5kZXgsIDEpO1xuICAgICAgICB0aGlzLmtleXMudW5zaGlmdChrZXkpO1xuICAgICAgICB0aGlzLnRyaW0oKTtcbiAgICB9XG4gICAgdHJpbSgpIHtcbiAgICAgICAgZm9yIChjb25zdCBrZXkgb2YgdGhpcy5rZXlzLnNwbGljZSh0aGlzLnNpemUpKSB7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy5zbmFwc2hvdHNba2V5XTtcbiAgICAgICAgfVxuICAgIH1cbn1cblxuY2xhc3MgU25hcHNob3RSZW5kZXJlciBleHRlbmRzIFJlbmRlcmVyIHtcbiAgICBjb25zdHJ1Y3RvcihkZWxlZ2F0ZSwgY3VycmVudFNuYXBzaG90LCBuZXdTbmFwc2hvdCwgaXNQcmV2aWV3KSB7XG4gICAgICAgIHN1cGVyKCk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5jdXJyZW50U25hcHNob3QgPSBjdXJyZW50U25hcHNob3Q7XG4gICAgICAgIHRoaXMuY3VycmVudEhlYWREZXRhaWxzID0gY3VycmVudFNuYXBzaG90LmhlYWREZXRhaWxzO1xuICAgICAgICB0aGlzLm5ld1NuYXBzaG90ID0gbmV3U25hcHNob3Q7XG4gICAgICAgIHRoaXMubmV3SGVhZERldGFpbHMgPSBuZXdTbmFwc2hvdC5oZWFkRGV0YWlscztcbiAgICAgICAgdGhpcy5uZXdCb2R5ID0gbmV3U25hcHNob3QuYm9keUVsZW1lbnQ7XG4gICAgICAgIHRoaXMuaXNQcmV2aWV3ID0gaXNQcmV2aWV3O1xuICAgIH1cbiAgICBzdGF0aWMgcmVuZGVyKGRlbGVnYXRlLCBjYWxsYmFjaywgY3VycmVudFNuYXBzaG90LCBuZXdTbmFwc2hvdCwgaXNQcmV2aWV3KSB7XG4gICAgICAgIHJldHVybiBuZXcgdGhpcyhkZWxlZ2F0ZSwgY3VycmVudFNuYXBzaG90LCBuZXdTbmFwc2hvdCwgaXNQcmV2aWV3KS5yZW5kZXIoY2FsbGJhY2spO1xuICAgIH1cbiAgICByZW5kZXIoY2FsbGJhY2spIHtcbiAgICAgICAgaWYgKHRoaXMuc2hvdWxkUmVuZGVyKCkpIHtcbiAgICAgICAgICAgIHRoaXMubWVyZ2VIZWFkKCk7XG4gICAgICAgICAgICB0aGlzLnJlbmRlclZpZXcoKCkgPT4ge1xuICAgICAgICAgICAgICAgIHRoaXMucmVwbGFjZUJvZHkoKTtcbiAgICAgICAgICAgICAgICBpZiAoIXRoaXMuaXNQcmV2aWV3KSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZm9jdXNGaXJzdEF1dG9mb2N1c2FibGVFbGVtZW50KCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGNhbGxiYWNrKCk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMuaW52YWxpZGF0ZVZpZXcoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBtZXJnZUhlYWQoKSB7XG4gICAgICAgIHRoaXMuY29weU5ld0hlYWRTdHlsZXNoZWV0RWxlbWVudHMoKTtcbiAgICAgICAgdGhpcy5jb3B5TmV3SGVhZFNjcmlwdEVsZW1lbnRzKCk7XG4gICAgICAgIHRoaXMucmVtb3ZlQ3VycmVudEhlYWRQcm92aXNpb25hbEVsZW1lbnRzKCk7XG4gICAgICAgIHRoaXMuY29weU5ld0hlYWRQcm92aXNpb25hbEVsZW1lbnRzKCk7XG4gICAgfVxuICAgIHJlcGxhY2VCb2R5KCkge1xuICAgICAgICBjb25zdCBwbGFjZWhvbGRlcnMgPSB0aGlzLnJlbG9jYXRlQ3VycmVudEJvZHlQZXJtYW5lbnRFbGVtZW50cygpO1xuICAgICAgICB0aGlzLmFjdGl2YXRlTmV3Qm9keSgpO1xuICAgICAgICB0aGlzLmFzc2lnbk5ld0JvZHkoKTtcbiAgICAgICAgdGhpcy5yZXBsYWNlUGxhY2Vob2xkZXJFbGVtZW50c1dpdGhDbG9uZWRQZXJtYW5lbnRFbGVtZW50cyhwbGFjZWhvbGRlcnMpO1xuICAgIH1cbiAgICBzaG91bGRSZW5kZXIoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLm5ld1NuYXBzaG90LmlzVmlzaXRhYmxlKCkgJiYgdGhpcy50cmFja2VkRWxlbWVudHNBcmVJZGVudGljYWwoKTtcbiAgICB9XG4gICAgdHJhY2tlZEVsZW1lbnRzQXJlSWRlbnRpY2FsKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jdXJyZW50SGVhZERldGFpbHMuZ2V0VHJhY2tlZEVsZW1lbnRTaWduYXR1cmUoKSA9PSB0aGlzLm5ld0hlYWREZXRhaWxzLmdldFRyYWNrZWRFbGVtZW50U2lnbmF0dXJlKCk7XG4gICAgfVxuICAgIGNvcHlOZXdIZWFkU3R5bGVzaGVldEVsZW1lbnRzKCkge1xuICAgICAgICBmb3IgKGNvbnN0IGVsZW1lbnQgb2YgdGhpcy5nZXROZXdIZWFkU3R5bGVzaGVldEVsZW1lbnRzKCkpIHtcbiAgICAgICAgICAgIGRvY3VtZW50LmhlYWQuYXBwZW5kQ2hpbGQoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY29weU5ld0hlYWRTY3JpcHRFbGVtZW50cygpIHtcbiAgICAgICAgZm9yIChjb25zdCBlbGVtZW50IG9mIHRoaXMuZ2V0TmV3SGVhZFNjcmlwdEVsZW1lbnRzKCkpIHtcbiAgICAgICAgICAgIGRvY3VtZW50LmhlYWQuYXBwZW5kQ2hpbGQodGhpcy5jcmVhdGVTY3JpcHRFbGVtZW50KGVsZW1lbnQpKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZW1vdmVDdXJyZW50SGVhZFByb3Zpc2lvbmFsRWxlbWVudHMoKSB7XG4gICAgICAgIGZvciAoY29uc3QgZWxlbWVudCBvZiB0aGlzLmdldEN1cnJlbnRIZWFkUHJvdmlzaW9uYWxFbGVtZW50cygpKSB7XG4gICAgICAgICAgICBkb2N1bWVudC5oZWFkLnJlbW92ZUNoaWxkKGVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGNvcHlOZXdIZWFkUHJvdmlzaW9uYWxFbGVtZW50cygpIHtcbiAgICAgICAgZm9yIChjb25zdCBlbGVtZW50IG9mIHRoaXMuZ2V0TmV3SGVhZFByb3Zpc2lvbmFsRWxlbWVudHMoKSkge1xuICAgICAgICAgICAgZG9jdW1lbnQuaGVhZC5hcHBlbmRDaGlsZChlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZWxvY2F0ZUN1cnJlbnRCb2R5UGVybWFuZW50RWxlbWVudHMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldEN1cnJlbnRCb2R5UGVybWFuZW50RWxlbWVudHMoKS5yZWR1Y2UoKHBsYWNlaG9sZGVycywgcGVybWFuZW50RWxlbWVudCkgPT4ge1xuICAgICAgICAgICAgY29uc3QgbmV3RWxlbWVudCA9IHRoaXMubmV3U25hcHNob3QuZ2V0UGVybWFuZW50RWxlbWVudEJ5SWQocGVybWFuZW50RWxlbWVudC5pZCk7XG4gICAgICAgICAgICBpZiAobmV3RWxlbWVudCkge1xuICAgICAgICAgICAgICAgIGNvbnN0IHBsYWNlaG9sZGVyID0gY3JlYXRlUGxhY2Vob2xkZXJGb3JQZXJtYW5lbnRFbGVtZW50KHBlcm1hbmVudEVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIHJlcGxhY2VFbGVtZW50V2l0aEVsZW1lbnQocGVybWFuZW50RWxlbWVudCwgcGxhY2Vob2xkZXIuZWxlbWVudCk7XG4gICAgICAgICAgICAgICAgcmVwbGFjZUVsZW1lbnRXaXRoRWxlbWVudChuZXdFbGVtZW50LCBwZXJtYW5lbnRFbGVtZW50KTtcbiAgICAgICAgICAgICAgICByZXR1cm4gWy4uLnBsYWNlaG9sZGVycywgcGxhY2Vob2xkZXJdO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHBsYWNlaG9sZGVycztcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSwgW10pO1xuICAgIH1cbiAgICByZXBsYWNlUGxhY2Vob2xkZXJFbGVtZW50c1dpdGhDbG9uZWRQZXJtYW5lbnRFbGVtZW50cyhwbGFjZWhvbGRlcnMpIHtcbiAgICAgICAgZm9yIChjb25zdCB7IGVsZW1lbnQsIHBlcm1hbmVudEVsZW1lbnQgfSBvZiBwbGFjZWhvbGRlcnMpIHtcbiAgICAgICAgICAgIGNvbnN0IGNsb25lZEVsZW1lbnQgPSBwZXJtYW5lbnRFbGVtZW50LmNsb25lTm9kZSh0cnVlKTtcbiAgICAgICAgICAgIHJlcGxhY2VFbGVtZW50V2l0aEVsZW1lbnQoZWxlbWVudCwgY2xvbmVkRWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgYWN0aXZhdGVOZXdCb2R5KCkge1xuICAgICAgICBkb2N1bWVudC5hZG9wdE5vZGUodGhpcy5uZXdCb2R5KTtcbiAgICAgICAgdGhpcy5hY3RpdmF0ZU5ld0JvZHlTY3JpcHRFbGVtZW50cygpO1xuICAgIH1cbiAgICBhY3RpdmF0ZU5ld0JvZHlTY3JpcHRFbGVtZW50cygpIHtcbiAgICAgICAgZm9yIChjb25zdCBpbmVydFNjcmlwdEVsZW1lbnQgb2YgdGhpcy5nZXROZXdCb2R5U2NyaXB0RWxlbWVudHMoKSkge1xuICAgICAgICAgICAgY29uc3QgYWN0aXZhdGVkU2NyaXB0RWxlbWVudCA9IHRoaXMuY3JlYXRlU2NyaXB0RWxlbWVudChpbmVydFNjcmlwdEVsZW1lbnQpO1xuICAgICAgICAgICAgcmVwbGFjZUVsZW1lbnRXaXRoRWxlbWVudChpbmVydFNjcmlwdEVsZW1lbnQsIGFjdGl2YXRlZFNjcmlwdEVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGFzc2lnbk5ld0JvZHkoKSB7XG4gICAgICAgIGlmIChkb2N1bWVudC5ib2R5KSB7XG4gICAgICAgICAgICByZXBsYWNlRWxlbWVudFdpdGhFbGVtZW50KGRvY3VtZW50LmJvZHksIHRoaXMubmV3Qm9keSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQuYXBwZW5kQ2hpbGQodGhpcy5uZXdCb2R5KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBmb2N1c0ZpcnN0QXV0b2ZvY3VzYWJsZUVsZW1lbnQoKSB7XG4gICAgICAgIGNvbnN0IGVsZW1lbnQgPSB0aGlzLm5ld1NuYXBzaG90LmZpbmRGaXJzdEF1dG9mb2N1c2FibGVFbGVtZW50KCk7XG4gICAgICAgIGlmIChlbGVtZW50SXNGb2N1c2FibGUoZWxlbWVudCkpIHtcbiAgICAgICAgICAgIGVsZW1lbnQuZm9jdXMoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXROZXdIZWFkU3R5bGVzaGVldEVsZW1lbnRzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5uZXdIZWFkRGV0YWlscy5nZXRTdHlsZXNoZWV0RWxlbWVudHNOb3RJbkRldGFpbHModGhpcy5jdXJyZW50SGVhZERldGFpbHMpO1xuICAgIH1cbiAgICBnZXROZXdIZWFkU2NyaXB0RWxlbWVudHMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLm5ld0hlYWREZXRhaWxzLmdldFNjcmlwdEVsZW1lbnRzTm90SW5EZXRhaWxzKHRoaXMuY3VycmVudEhlYWREZXRhaWxzKTtcbiAgICB9XG4gICAgZ2V0Q3VycmVudEhlYWRQcm92aXNpb25hbEVsZW1lbnRzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jdXJyZW50SGVhZERldGFpbHMuZ2V0UHJvdmlzaW9uYWxFbGVtZW50cygpO1xuICAgIH1cbiAgICBnZXROZXdIZWFkUHJvdmlzaW9uYWxFbGVtZW50cygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMubmV3SGVhZERldGFpbHMuZ2V0UHJvdmlzaW9uYWxFbGVtZW50cygpO1xuICAgIH1cbiAgICBnZXRDdXJyZW50Qm9keVBlcm1hbmVudEVsZW1lbnRzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jdXJyZW50U25hcHNob3QuZ2V0UGVybWFuZW50RWxlbWVudHNQcmVzZW50SW5TbmFwc2hvdCh0aGlzLm5ld1NuYXBzaG90KTtcbiAgICB9XG4gICAgZ2V0TmV3Qm9keVNjcmlwdEVsZW1lbnRzKCkge1xuICAgICAgICByZXR1cm4gWy4uLnRoaXMubmV3Qm9keS5xdWVyeVNlbGVjdG9yQWxsKFwic2NyaXB0XCIpXTtcbiAgICB9XG59XG5mdW5jdGlvbiBjcmVhdGVQbGFjZWhvbGRlckZvclBlcm1hbmVudEVsZW1lbnQocGVybWFuZW50RWxlbWVudCkge1xuICAgIGNvbnN0IGVsZW1lbnQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwibWV0YVwiKTtcbiAgICBlbGVtZW50LnNldEF0dHJpYnV0ZShcIm5hbWVcIiwgXCJ0dXJiby1wZXJtYW5lbnQtcGxhY2Vob2xkZXJcIik7XG4gICAgZWxlbWVudC5zZXRBdHRyaWJ1dGUoXCJjb250ZW50XCIsIHBlcm1hbmVudEVsZW1lbnQuaWQpO1xuICAgIHJldHVybiB7IGVsZW1lbnQsIHBlcm1hbmVudEVsZW1lbnQgfTtcbn1cbmZ1bmN0aW9uIHJlcGxhY2VFbGVtZW50V2l0aEVsZW1lbnQoZnJvbUVsZW1lbnQsIHRvRWxlbWVudCkge1xuICAgIGNvbnN0IHBhcmVudEVsZW1lbnQgPSBmcm9tRWxlbWVudC5wYXJlbnRFbGVtZW50O1xuICAgIGlmIChwYXJlbnRFbGVtZW50KSB7XG4gICAgICAgIHJldHVybiBwYXJlbnRFbGVtZW50LnJlcGxhY2VDaGlsZCh0b0VsZW1lbnQsIGZyb21FbGVtZW50KTtcbiAgICB9XG59XG5mdW5jdGlvbiBlbGVtZW50SXNGb2N1c2FibGUoZWxlbWVudCkge1xuICAgIHJldHVybiBlbGVtZW50ICYmIHR5cGVvZiBlbGVtZW50LmZvY3VzID09IFwiZnVuY3Rpb25cIjtcbn1cblxuY2xhc3MgVmlldyB7XG4gICAgY29uc3RydWN0b3IoZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5odG1sRWxlbWVudCA9IGRvY3VtZW50LmRvY3VtZW50RWxlbWVudDtcbiAgICAgICAgdGhpcy5zbmFwc2hvdENhY2hlID0gbmV3IFNuYXBzaG90Q2FjaGUoMTApO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgfVxuICAgIGdldFJvb3RMb2NhdGlvbigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0U25hcHNob3QoKS5nZXRSb290TG9jYXRpb24oKTtcbiAgICB9XG4gICAgZ2V0RWxlbWVudEZvckFuY2hvcihhbmNob3IpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0U25hcHNob3QoKS5nZXRFbGVtZW50Rm9yQW5jaG9yKGFuY2hvcik7XG4gICAgfVxuICAgIGdldFNuYXBzaG90KCkge1xuICAgICAgICByZXR1cm4gU25hcHNob3QuZnJvbUhUTUxFbGVtZW50KHRoaXMuaHRtbEVsZW1lbnQpO1xuICAgIH1cbiAgICBjbGVhclNuYXBzaG90Q2FjaGUoKSB7XG4gICAgICAgIHRoaXMuc25hcHNob3RDYWNoZS5jbGVhcigpO1xuICAgIH1cbiAgICBzaG91bGRDYWNoZVNuYXBzaG90KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5nZXRTbmFwc2hvdCgpLmlzQ2FjaGVhYmxlKCk7XG4gICAgfVxuICAgIGFzeW5jIGNhY2hlU25hcHNob3QoKSB7XG4gICAgICAgIGlmICh0aGlzLnNob3VsZENhY2hlU25hcHNob3QoKSkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS52aWV3V2lsbENhY2hlU25hcHNob3QoKTtcbiAgICAgICAgICAgIGNvbnN0IHNuYXBzaG90ID0gdGhpcy5nZXRTbmFwc2hvdCgpO1xuICAgICAgICAgICAgY29uc3QgbG9jYXRpb24gPSB0aGlzLmxhc3RSZW5kZXJlZExvY2F0aW9uIHx8IExvY2F0aW9uLmN1cnJlbnRMb2NhdGlvbjtcbiAgICAgICAgICAgIGF3YWl0IG5leHRNaWNyb3Rhc2soKTtcbiAgICAgICAgICAgIHRoaXMuc25hcHNob3RDYWNoZS5wdXQobG9jYXRpb24sIHNuYXBzaG90LmNsb25lKCkpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldENhY2hlZFNuYXBzaG90Rm9yTG9jYXRpb24obG9jYXRpb24pIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc25hcHNob3RDYWNoZS5nZXQobG9jYXRpb24pO1xuICAgIH1cbiAgICByZW5kZXIoeyBzbmFwc2hvdCwgZXJyb3IsIGlzUHJldmlldyB9LCBjYWxsYmFjaykge1xuICAgICAgICB0aGlzLm1hcmtBc1ByZXZpZXcoaXNQcmV2aWV3KTtcbiAgICAgICAgaWYgKHNuYXBzaG90KSB7XG4gICAgICAgICAgICB0aGlzLnJlbmRlclNuYXBzaG90KHNuYXBzaG90LCBpc1ByZXZpZXcsIGNhbGxiYWNrKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMucmVuZGVyRXJyb3IoZXJyb3IsIGNhbGxiYWNrKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzY3JvbGxUb0FuY2hvcihhbmNob3IpIHtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IHRoaXMuZ2V0RWxlbWVudEZvckFuY2hvcihhbmNob3IpO1xuICAgICAgICBpZiAoZWxlbWVudCkge1xuICAgICAgICAgICAgdGhpcy5zY3JvbGxUb0VsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLnNjcm9sbFRvUG9zaXRpb24oeyB4OiAwLCB5OiAwIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIHNjcm9sbFRvRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGVsZW1lbnQuc2Nyb2xsSW50b1ZpZXcoKTtcbiAgICB9XG4gICAgc2Nyb2xsVG9Qb3NpdGlvbih7IHgsIHkgfSkge1xuICAgICAgICB3aW5kb3cuc2Nyb2xsVG8oeCwgeSk7XG4gICAgfVxuICAgIG1hcmtBc1ByZXZpZXcoaXNQcmV2aWV3KSB7XG4gICAgICAgIGlmIChpc1ByZXZpZXcpIHtcbiAgICAgICAgICAgIHRoaXMuaHRtbEVsZW1lbnQuc2V0QXR0cmlidXRlKFwiZGF0YS10dXJiby1wcmV2aWV3XCIsIFwiXCIpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5odG1sRWxlbWVudC5yZW1vdmVBdHRyaWJ1dGUoXCJkYXRhLXR1cmJvLXByZXZpZXdcIik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVuZGVyU25hcHNob3Qoc25hcHNob3QsIGlzUHJldmlldywgY2FsbGJhY2spIHtcbiAgICAgICAgU25hcHNob3RSZW5kZXJlci5yZW5kZXIodGhpcy5kZWxlZ2F0ZSwgY2FsbGJhY2ssIHRoaXMuZ2V0U25hcHNob3QoKSwgc25hcHNob3QsIGlzUHJldmlldyB8fCBmYWxzZSk7XG4gICAgfVxuICAgIHJlbmRlckVycm9yKGVycm9yLCBjYWxsYmFjaykge1xuICAgICAgICBFcnJvclJlbmRlcmVyLnJlbmRlcih0aGlzLmRlbGVnYXRlLCBjYWxsYmFjaywgZXJyb3IgfHwgXCJcIik7XG4gICAgfVxufVxuXG5jbGFzcyBTZXNzaW9uIHtcbiAgICBjb25zdHJ1Y3RvcigpIHtcbiAgICAgICAgdGhpcy5uYXZpZ2F0b3IgPSBuZXcgTmF2aWdhdG9yKHRoaXMpO1xuICAgICAgICB0aGlzLmhpc3RvcnkgPSBuZXcgSGlzdG9yeSh0aGlzKTtcbiAgICAgICAgdGhpcy52aWV3ID0gbmV3IFZpZXcodGhpcyk7XG4gICAgICAgIHRoaXMuYWRhcHRlciA9IG5ldyBCcm93c2VyQWRhcHRlcih0aGlzKTtcbiAgICAgICAgdGhpcy5wYWdlT2JzZXJ2ZXIgPSBuZXcgUGFnZU9ic2VydmVyKHRoaXMpO1xuICAgICAgICB0aGlzLmxpbmtDbGlja09ic2VydmVyID0gbmV3IExpbmtDbGlja09ic2VydmVyKHRoaXMpO1xuICAgICAgICB0aGlzLmZvcm1TdWJtaXRPYnNlcnZlciA9IG5ldyBGb3JtU3VibWl0T2JzZXJ2ZXIodGhpcyk7XG4gICAgICAgIHRoaXMuc2Nyb2xsT2JzZXJ2ZXIgPSBuZXcgU2Nyb2xsT2JzZXJ2ZXIodGhpcyk7XG4gICAgICAgIHRoaXMuc3RyZWFtT2JzZXJ2ZXIgPSBuZXcgU3RyZWFtT2JzZXJ2ZXIodGhpcyk7XG4gICAgICAgIHRoaXMuZnJhbWVSZWRpcmVjdG9yID0gbmV3IEZyYW1lUmVkaXJlY3Rvcihkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQpO1xuICAgICAgICB0aGlzLmVuYWJsZWQgPSB0cnVlO1xuICAgICAgICB0aGlzLnByb2dyZXNzQmFyRGVsYXkgPSA1MDA7XG4gICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMucGFnZU9ic2VydmVyLnN0YXJ0KCk7XG4gICAgICAgICAgICB0aGlzLmxpbmtDbGlja09ic2VydmVyLnN0YXJ0KCk7XG4gICAgICAgICAgICB0aGlzLmZvcm1TdWJtaXRPYnNlcnZlci5zdGFydCgpO1xuICAgICAgICAgICAgdGhpcy5zY3JvbGxPYnNlcnZlci5zdGFydCgpO1xuICAgICAgICAgICAgdGhpcy5zdHJlYW1PYnNlcnZlci5zdGFydCgpO1xuICAgICAgICAgICAgdGhpcy5mcmFtZVJlZGlyZWN0b3Iuc3RhcnQoKTtcbiAgICAgICAgICAgIHRoaXMuaGlzdG9yeS5zdGFydCgpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gdHJ1ZTtcbiAgICAgICAgICAgIHRoaXMuZW5hYmxlZCA9IHRydWU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZGlzYWJsZSgpIHtcbiAgICAgICAgdGhpcy5lbmFibGVkID0gZmFsc2U7XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMucGFnZU9ic2VydmVyLnN0b3AoKTtcbiAgICAgICAgICAgIHRoaXMubGlua0NsaWNrT2JzZXJ2ZXIuc3RvcCgpO1xuICAgICAgICAgICAgdGhpcy5mb3JtU3VibWl0T2JzZXJ2ZXIuc3RvcCgpO1xuICAgICAgICAgICAgdGhpcy5zY3JvbGxPYnNlcnZlci5zdG9wKCk7XG4gICAgICAgICAgICB0aGlzLnN0cmVhbU9ic2VydmVyLnN0b3AoKTtcbiAgICAgICAgICAgIHRoaXMuZnJhbWVSZWRpcmVjdG9yLnN0b3AoKTtcbiAgICAgICAgICAgIHRoaXMuaGlzdG9yeS5zdG9wKCk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZWdpc3RlckFkYXB0ZXIoYWRhcHRlcikge1xuICAgICAgICB0aGlzLmFkYXB0ZXIgPSBhZGFwdGVyO1xuICAgIH1cbiAgICB2aXNpdChsb2NhdGlvbiwgb3B0aW9ucyA9IHt9KSB7XG4gICAgICAgIHRoaXMubmF2aWdhdG9yLnByb3Bvc2VWaXNpdChMb2NhdGlvbi53cmFwKGxvY2F0aW9uKSwgb3B0aW9ucyk7XG4gICAgfVxuICAgIGNvbm5lY3RTdHJlYW1Tb3VyY2Uoc291cmNlKSB7XG4gICAgICAgIHRoaXMuc3RyZWFtT2JzZXJ2ZXIuY29ubmVjdFN0cmVhbVNvdXJjZShzb3VyY2UpO1xuICAgIH1cbiAgICBkaXNjb25uZWN0U3RyZWFtU291cmNlKHNvdXJjZSkge1xuICAgICAgICB0aGlzLnN0cmVhbU9ic2VydmVyLmRpc2Nvbm5lY3RTdHJlYW1Tb3VyY2Uoc291cmNlKTtcbiAgICB9XG4gICAgcmVuZGVyU3RyZWFtTWVzc2FnZShtZXNzYWdlKSB7XG4gICAgICAgIGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5hcHBlbmRDaGlsZChTdHJlYW1NZXNzYWdlLndyYXAobWVzc2FnZSkuZnJhZ21lbnQpO1xuICAgIH1cbiAgICBjbGVhckNhY2hlKCkge1xuICAgICAgICB0aGlzLnZpZXcuY2xlYXJTbmFwc2hvdENhY2hlKCk7XG4gICAgfVxuICAgIHNldFByb2dyZXNzQmFyRGVsYXkoZGVsYXkpIHtcbiAgICAgICAgdGhpcy5wcm9ncmVzc0JhckRlbGF5ID0gZGVsYXk7XG4gICAgfVxuICAgIGdldCBsb2NhdGlvbigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuaGlzdG9yeS5sb2NhdGlvbjtcbiAgICB9XG4gICAgZ2V0IHJlc3RvcmF0aW9uSWRlbnRpZmllcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuaGlzdG9yeS5yZXN0b3JhdGlvbklkZW50aWZpZXI7XG4gICAgfVxuICAgIGhpc3RvcnlQb3BwZWRUb0xvY2F0aW9uV2l0aFJlc3RvcmF0aW9uSWRlbnRpZmllcihsb2NhdGlvbikge1xuICAgICAgICBpZiAodGhpcy5lbmFibGVkKSB7XG4gICAgICAgICAgICB0aGlzLm5hdmlnYXRvci5wcm9wb3NlVmlzaXQobG9jYXRpb24sIHsgYWN0aW9uOiBcInJlc3RvcmVcIiwgaGlzdG9yeUNoYW5nZWQ6IHRydWUgfSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLmFkYXB0ZXIucGFnZUludmFsaWRhdGVkKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc2Nyb2xsUG9zaXRpb25DaGFuZ2VkKHBvc2l0aW9uKSB7XG4gICAgICAgIHRoaXMuaGlzdG9yeS51cGRhdGVSZXN0b3JhdGlvbkRhdGEoeyBzY3JvbGxQb3NpdGlvbjogcG9zaXRpb24gfSk7XG4gICAgfVxuICAgIHdpbGxGb2xsb3dMaW5rVG9Mb2NhdGlvbihsaW5rLCBsb2NhdGlvbikge1xuICAgICAgICByZXR1cm4gdGhpcy5saW5rSXNWaXNpdGFibGUobGluaylcbiAgICAgICAgICAgICYmIHRoaXMubG9jYXRpb25Jc1Zpc2l0YWJsZShsb2NhdGlvbilcbiAgICAgICAgICAgICYmIHRoaXMuYXBwbGljYXRpb25BbGxvd3NGb2xsb3dpbmdMaW5rVG9Mb2NhdGlvbihsaW5rLCBsb2NhdGlvbik7XG4gICAgfVxuICAgIGZvbGxvd2VkTGlua1RvTG9jYXRpb24obGluaywgbG9jYXRpb24pIHtcbiAgICAgICAgY29uc3QgYWN0aW9uID0gdGhpcy5nZXRBY3Rpb25Gb3JMaW5rKGxpbmspO1xuICAgICAgICB0aGlzLnZpc2l0KGxvY2F0aW9uLCB7IGFjdGlvbiB9KTtcbiAgICB9XG4gICAgYWxsb3dzVmlzaXRpbmdMb2NhdGlvbihsb2NhdGlvbikge1xuICAgICAgICByZXR1cm4gdGhpcy5hcHBsaWNhdGlvbkFsbG93c1Zpc2l0aW5nTG9jYXRpb24obG9jYXRpb24pO1xuICAgIH1cbiAgICB2aXNpdFByb3Bvc2VkVG9Mb2NhdGlvbihsb2NhdGlvbiwgb3B0aW9ucykge1xuICAgICAgICB0aGlzLmFkYXB0ZXIudmlzaXRQcm9wb3NlZFRvTG9jYXRpb24obG9jYXRpb24sIG9wdGlvbnMpO1xuICAgIH1cbiAgICB2aXNpdFN0YXJ0ZWQodmlzaXQpIHtcbiAgICAgICAgdGhpcy5ub3RpZnlBcHBsaWNhdGlvbkFmdGVyVmlzaXRpbmdMb2NhdGlvbih2aXNpdC5sb2NhdGlvbik7XG4gICAgfVxuICAgIHZpc2l0Q29tcGxldGVkKHZpc2l0KSB7XG4gICAgICAgIHRoaXMubm90aWZ5QXBwbGljYXRpb25BZnRlclBhZ2VMb2FkKHZpc2l0LmdldFRpbWluZ01ldHJpY3MoKSk7XG4gICAgfVxuICAgIHdpbGxTdWJtaXRGb3JtKGZvcm0sIHN1Ym1pdHRlcikge1xuICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9XG4gICAgZm9ybVN1Ym1pdHRlZChmb3JtLCBzdWJtaXR0ZXIpIHtcbiAgICAgICAgdGhpcy5uYXZpZ2F0b3Iuc3VibWl0Rm9ybShmb3JtLCBzdWJtaXR0ZXIpO1xuICAgIH1cbiAgICBwYWdlQmVjYW1lSW50ZXJhY3RpdmUoKSB7XG4gICAgICAgIHRoaXMudmlldy5sYXN0UmVuZGVyZWRMb2NhdGlvbiA9IHRoaXMubG9jYXRpb247XG4gICAgICAgIHRoaXMubm90aWZ5QXBwbGljYXRpb25BZnRlclBhZ2VMb2FkKCk7XG4gICAgfVxuICAgIHBhZ2VMb2FkZWQoKSB7XG4gICAgfVxuICAgIHBhZ2VJbnZhbGlkYXRlZCgpIHtcbiAgICAgICAgdGhpcy5hZGFwdGVyLnBhZ2VJbnZhbGlkYXRlZCgpO1xuICAgIH1cbiAgICByZWNlaXZlZE1lc3NhZ2VGcm9tU3RyZWFtKG1lc3NhZ2UpIHtcbiAgICAgICAgdGhpcy5yZW5kZXJTdHJlYW1NZXNzYWdlKG1lc3NhZ2UpO1xuICAgIH1cbiAgICB2aWV3V2lsbFJlbmRlcihuZXdCb2R5KSB7XG4gICAgICAgIHRoaXMubm90aWZ5QXBwbGljYXRpb25CZWZvcmVSZW5kZXIobmV3Qm9keSk7XG4gICAgfVxuICAgIHZpZXdSZW5kZXJlZCgpIHtcbiAgICAgICAgdGhpcy52aWV3Lmxhc3RSZW5kZXJlZExvY2F0aW9uID0gdGhpcy5oaXN0b3J5LmxvY2F0aW9uO1xuICAgICAgICB0aGlzLm5vdGlmeUFwcGxpY2F0aW9uQWZ0ZXJSZW5kZXIoKTtcbiAgICB9XG4gICAgdmlld0ludmFsaWRhdGVkKCkge1xuICAgICAgICB0aGlzLnBhZ2VPYnNlcnZlci5pbnZhbGlkYXRlKCk7XG4gICAgfVxuICAgIHZpZXdXaWxsQ2FjaGVTbmFwc2hvdCgpIHtcbiAgICAgICAgdGhpcy5ub3RpZnlBcHBsaWNhdGlvbkJlZm9yZUNhY2hpbmdTbmFwc2hvdCgpO1xuICAgIH1cbiAgICBhcHBsaWNhdGlvbkFsbG93c0ZvbGxvd2luZ0xpbmtUb0xvY2F0aW9uKGxpbmssIGxvY2F0aW9uKSB7XG4gICAgICAgIGNvbnN0IGV2ZW50ID0gdGhpcy5ub3RpZnlBcHBsaWNhdGlvbkFmdGVyQ2xpY2tpbmdMaW5rVG9Mb2NhdGlvbihsaW5rLCBsb2NhdGlvbik7XG4gICAgICAgIHJldHVybiAhZXZlbnQuZGVmYXVsdFByZXZlbnRlZDtcbiAgICB9XG4gICAgYXBwbGljYXRpb25BbGxvd3NWaXNpdGluZ0xvY2F0aW9uKGxvY2F0aW9uKSB7XG4gICAgICAgIGNvbnN0IGV2ZW50ID0gdGhpcy5ub3RpZnlBcHBsaWNhdGlvbkJlZm9yZVZpc2l0aW5nTG9jYXRpb24obG9jYXRpb24pO1xuICAgICAgICByZXR1cm4gIWV2ZW50LmRlZmF1bHRQcmV2ZW50ZWQ7XG4gICAgfVxuICAgIG5vdGlmeUFwcGxpY2F0aW9uQWZ0ZXJDbGlja2luZ0xpbmtUb0xvY2F0aW9uKGxpbmssIGxvY2F0aW9uKSB7XG4gICAgICAgIHJldHVybiBkaXNwYXRjaChcInR1cmJvOmNsaWNrXCIsIHsgdGFyZ2V0OiBsaW5rLCBkZXRhaWw6IHsgdXJsOiBsb2NhdGlvbi5hYnNvbHV0ZVVSTCB9LCBjYW5jZWxhYmxlOiB0cnVlIH0pO1xuICAgIH1cbiAgICBub3RpZnlBcHBsaWNhdGlvbkJlZm9yZVZpc2l0aW5nTG9jYXRpb24obG9jYXRpb24pIHtcbiAgICAgICAgcmV0dXJuIGRpc3BhdGNoKFwidHVyYm86YmVmb3JlLXZpc2l0XCIsIHsgZGV0YWlsOiB7IHVybDogbG9jYXRpb24uYWJzb2x1dGVVUkwgfSwgY2FuY2VsYWJsZTogdHJ1ZSB9KTtcbiAgICB9XG4gICAgbm90aWZ5QXBwbGljYXRpb25BZnRlclZpc2l0aW5nTG9jYXRpb24obG9jYXRpb24pIHtcbiAgICAgICAgcmV0dXJuIGRpc3BhdGNoKFwidHVyYm86dmlzaXRcIiwgeyBkZXRhaWw6IHsgdXJsOiBsb2NhdGlvbi5hYnNvbHV0ZVVSTCB9IH0pO1xuICAgIH1cbiAgICBub3RpZnlBcHBsaWNhdGlvbkJlZm9yZUNhY2hpbmdTbmFwc2hvdCgpIHtcbiAgICAgICAgcmV0dXJuIGRpc3BhdGNoKFwidHVyYm86YmVmb3JlLWNhY2hlXCIpO1xuICAgIH1cbiAgICBub3RpZnlBcHBsaWNhdGlvbkJlZm9yZVJlbmRlcihuZXdCb2R5KSB7XG4gICAgICAgIHJldHVybiBkaXNwYXRjaChcInR1cmJvOmJlZm9yZS1yZW5kZXJcIiwgeyBkZXRhaWw6IHsgbmV3Qm9keSB9IH0pO1xuICAgIH1cbiAgICBub3RpZnlBcHBsaWNhdGlvbkFmdGVyUmVuZGVyKCkge1xuICAgICAgICByZXR1cm4gZGlzcGF0Y2goXCJ0dXJibzpyZW5kZXJcIik7XG4gICAgfVxuICAgIG5vdGlmeUFwcGxpY2F0aW9uQWZ0ZXJQYWdlTG9hZCh0aW1pbmcgPSB7fSkge1xuICAgICAgICByZXR1cm4gZGlzcGF0Y2goXCJ0dXJibzpsb2FkXCIsIHsgZGV0YWlsOiB7IHVybDogdGhpcy5sb2NhdGlvbi5hYnNvbHV0ZVVSTCwgdGltaW5nIH0gfSk7XG4gICAgfVxuICAgIGdldEFjdGlvbkZvckxpbmsobGluaykge1xuICAgICAgICBjb25zdCBhY3Rpb24gPSBsaW5rLmdldEF0dHJpYnV0ZShcImRhdGEtdHVyYm8tYWN0aW9uXCIpO1xuICAgICAgICByZXR1cm4gaXNBY3Rpb24oYWN0aW9uKSA/IGFjdGlvbiA6IFwiYWR2YW5jZVwiO1xuICAgIH1cbiAgICBsaW5rSXNWaXNpdGFibGUobGluaykge1xuICAgICAgICBjb25zdCBjb250YWluZXIgPSBsaW5rLmNsb3Nlc3QoXCJbZGF0YS10dXJib11cIik7XG4gICAgICAgIGlmIChjb250YWluZXIpIHtcbiAgICAgICAgICAgIHJldHVybiBjb250YWluZXIuZ2V0QXR0cmlidXRlKFwiZGF0YS10dXJib1wiKSAhPSBcImZhbHNlXCI7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBsb2NhdGlvbklzVmlzaXRhYmxlKGxvY2F0aW9uKSB7XG4gICAgICAgIHJldHVybiBsb2NhdGlvbi5pc1ByZWZpeGVkQnkodGhpcy52aWV3LmdldFJvb3RMb2NhdGlvbigpKSAmJiBsb2NhdGlvbi5pc0hUTUwoKTtcbiAgICB9XG59XG5cbmNvbnN0IHNlc3Npb24gPSBuZXcgU2Vzc2lvbjtcbmNvbnN0IHsgbmF2aWdhdG9yIH0gPSBzZXNzaW9uO1xuZnVuY3Rpb24gc3RhcnQoKSB7XG4gICAgc2Vzc2lvbi5zdGFydCgpO1xufVxuZnVuY3Rpb24gcmVnaXN0ZXJBZGFwdGVyKGFkYXB0ZXIpIHtcbiAgICBzZXNzaW9uLnJlZ2lzdGVyQWRhcHRlcihhZGFwdGVyKTtcbn1cbmZ1bmN0aW9uIHZpc2l0KGxvY2F0aW9uLCBvcHRpb25zKSB7XG4gICAgc2Vzc2lvbi52aXNpdChsb2NhdGlvbiwgb3B0aW9ucyk7XG59XG5mdW5jdGlvbiBjb25uZWN0U3RyZWFtU291cmNlKHNvdXJjZSkge1xuICAgIHNlc3Npb24uY29ubmVjdFN0cmVhbVNvdXJjZShzb3VyY2UpO1xufVxuZnVuY3Rpb24gZGlzY29ubmVjdFN0cmVhbVNvdXJjZShzb3VyY2UpIHtcbiAgICBzZXNzaW9uLmRpc2Nvbm5lY3RTdHJlYW1Tb3VyY2Uoc291cmNlKTtcbn1cbmZ1bmN0aW9uIHJlbmRlclN0cmVhbU1lc3NhZ2UobWVzc2FnZSkge1xuICAgIHNlc3Npb24ucmVuZGVyU3RyZWFtTWVzc2FnZShtZXNzYWdlKTtcbn1cbmZ1bmN0aW9uIGNsZWFyQ2FjaGUoKSB7XG4gICAgc2Vzc2lvbi5jbGVhckNhY2hlKCk7XG59XG5mdW5jdGlvbiBzZXRQcm9ncmVzc0JhckRlbGF5KGRlbGF5KSB7XG4gICAgc2Vzc2lvbi5zZXRQcm9ncmVzc0JhckRlbGF5KGRlbGF5KTtcbn1cblxuc3RhcnQoKTtcblxuZXhwb3J0IHsgY2xlYXJDYWNoZSwgY29ubmVjdFN0cmVhbVNvdXJjZSwgZGlzY29ubmVjdFN0cmVhbVNvdXJjZSwgbmF2aWdhdG9yLCByZWdpc3RlckFkYXB0ZXIsIHJlbmRlclN0cmVhbU1lc3NhZ2UsIHNldFByb2dyZXNzQmFyRGVsYXksIHN0YXJ0LCB2aXNpdCB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9dHVyYm8uZXMyMDE3LWVzbS5qcy5tYXBcbiIsImltcG9ydCB7IHBhcnNlQWN0aW9uRGVzY3JpcHRvclN0cmluZywgc3RyaW5naWZ5RXZlbnRUYXJnZXQgfSBmcm9tIFwiLi9hY3Rpb25fZGVzY3JpcHRvclwiO1xudmFyIEFjdGlvbiA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBBY3Rpb24oZWxlbWVudCwgaW5kZXgsIGRlc2NyaXB0b3IpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5pbmRleCA9IGluZGV4O1xuICAgICAgICB0aGlzLmV2ZW50VGFyZ2V0ID0gZGVzY3JpcHRvci5ldmVudFRhcmdldCB8fCBlbGVtZW50O1xuICAgICAgICB0aGlzLmV2ZW50TmFtZSA9IGRlc2NyaXB0b3IuZXZlbnROYW1lIHx8IGdldERlZmF1bHRFdmVudE5hbWVGb3JFbGVtZW50KGVsZW1lbnQpIHx8IGVycm9yKFwibWlzc2luZyBldmVudCBuYW1lXCIpO1xuICAgICAgICB0aGlzLmV2ZW50T3B0aW9ucyA9IGRlc2NyaXB0b3IuZXZlbnRPcHRpb25zIHx8IHt9O1xuICAgICAgICB0aGlzLmlkZW50aWZpZXIgPSBkZXNjcmlwdG9yLmlkZW50aWZpZXIgfHwgZXJyb3IoXCJtaXNzaW5nIGlkZW50aWZpZXJcIik7XG4gICAgICAgIHRoaXMubWV0aG9kTmFtZSA9IGRlc2NyaXB0b3IubWV0aG9kTmFtZSB8fCBlcnJvcihcIm1pc3NpbmcgbWV0aG9kIG5hbWVcIik7XG4gICAgfVxuICAgIEFjdGlvbi5mb3JUb2tlbiA9IGZ1bmN0aW9uICh0b2tlbikge1xuICAgICAgICByZXR1cm4gbmV3IHRoaXModG9rZW4uZWxlbWVudCwgdG9rZW4uaW5kZXgsIHBhcnNlQWN0aW9uRGVzY3JpcHRvclN0cmluZyh0b2tlbi5jb250ZW50KSk7XG4gICAgfTtcbiAgICBBY3Rpb24ucHJvdG90eXBlLnRvU3RyaW5nID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIgZXZlbnROYW1lU3VmZml4ID0gdGhpcy5ldmVudFRhcmdldE5hbWUgPyBcIkBcIiArIHRoaXMuZXZlbnRUYXJnZXROYW1lIDogXCJcIjtcbiAgICAgICAgcmV0dXJuIFwiXCIgKyB0aGlzLmV2ZW50TmFtZSArIGV2ZW50TmFtZVN1ZmZpeCArIFwiLT5cIiArIHRoaXMuaWRlbnRpZmllciArIFwiI1wiICsgdGhpcy5tZXRob2ROYW1lO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEFjdGlvbi5wcm90b3R5cGUsIFwiZXZlbnRUYXJnZXROYW1lXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gc3RyaW5naWZ5RXZlbnRUYXJnZXQodGhpcy5ldmVudFRhcmdldCk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICByZXR1cm4gQWN0aW9uO1xufSgpKTtcbmV4cG9ydCB7IEFjdGlvbiB9O1xudmFyIGRlZmF1bHRFdmVudE5hbWVzID0ge1xuICAgIFwiYVwiOiBmdW5jdGlvbiAoZSkgeyByZXR1cm4gXCJjbGlja1wiOyB9LFxuICAgIFwiYnV0dG9uXCI6IGZ1bmN0aW9uIChlKSB7IHJldHVybiBcImNsaWNrXCI7IH0sXG4gICAgXCJmb3JtXCI6IGZ1bmN0aW9uIChlKSB7IHJldHVybiBcInN1Ym1pdFwiOyB9LFxuICAgIFwiaW5wdXRcIjogZnVuY3Rpb24gKGUpIHsgcmV0dXJuIGUuZ2V0QXR0cmlidXRlKFwidHlwZVwiKSA9PSBcInN1Ym1pdFwiID8gXCJjbGlja1wiIDogXCJpbnB1dFwiOyB9LFxuICAgIFwic2VsZWN0XCI6IGZ1bmN0aW9uIChlKSB7IHJldHVybiBcImNoYW5nZVwiOyB9LFxuICAgIFwidGV4dGFyZWFcIjogZnVuY3Rpb24gKGUpIHsgcmV0dXJuIFwiaW5wdXRcIjsgfVxufTtcbmV4cG9ydCBmdW5jdGlvbiBnZXREZWZhdWx0RXZlbnROYW1lRm9yRWxlbWVudChlbGVtZW50KSB7XG4gICAgdmFyIHRhZ05hbWUgPSBlbGVtZW50LnRhZ05hbWUudG9Mb3dlckNhc2UoKTtcbiAgICBpZiAodGFnTmFtZSBpbiBkZWZhdWx0RXZlbnROYW1lcykge1xuICAgICAgICByZXR1cm4gZGVmYXVsdEV2ZW50TmFtZXNbdGFnTmFtZV0oZWxlbWVudCk7XG4gICAgfVxufVxuZnVuY3Rpb24gZXJyb3IobWVzc2FnZSkge1xuICAgIHRocm93IG5ldyBFcnJvcihtZXNzYWdlKTtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWFjdGlvbi5qcy5tYXAiLCIvLyBjYXB0dXJlIG5vcy46ICAgICAgICAgICAgMTIgICAyMyA0ICAgICAgICAgICAgICAgNDMgICAxIDUgICA1NiA3ICAgICAgNzY4IDkgIDk4XG52YXIgZGVzY3JpcHRvclBhdHRlcm4gPSAvXigoLis/KShAKHdpbmRvd3xkb2N1bWVudCkpPy0+KT8oLis/KSgjKFteOl0rPykpKDooLispKT8kLztcbmV4cG9ydCBmdW5jdGlvbiBwYXJzZUFjdGlvbkRlc2NyaXB0b3JTdHJpbmcoZGVzY3JpcHRvclN0cmluZykge1xuICAgIHZhciBzb3VyY2UgPSBkZXNjcmlwdG9yU3RyaW5nLnRyaW0oKTtcbiAgICB2YXIgbWF0Y2hlcyA9IHNvdXJjZS5tYXRjaChkZXNjcmlwdG9yUGF0dGVybikgfHwgW107XG4gICAgcmV0dXJuIHtcbiAgICAgICAgZXZlbnRUYXJnZXQ6IHBhcnNlRXZlbnRUYXJnZXQobWF0Y2hlc1s0XSksXG4gICAgICAgIGV2ZW50TmFtZTogbWF0Y2hlc1syXSxcbiAgICAgICAgZXZlbnRPcHRpb25zOiBtYXRjaGVzWzldID8gcGFyc2VFdmVudE9wdGlvbnMobWF0Y2hlc1s5XSkgOiB7fSxcbiAgICAgICAgaWRlbnRpZmllcjogbWF0Y2hlc1s1XSxcbiAgICAgICAgbWV0aG9kTmFtZTogbWF0Y2hlc1s3XVxuICAgIH07XG59XG5mdW5jdGlvbiBwYXJzZUV2ZW50VGFyZ2V0KGV2ZW50VGFyZ2V0TmFtZSkge1xuICAgIGlmIChldmVudFRhcmdldE5hbWUgPT0gXCJ3aW5kb3dcIikge1xuICAgICAgICByZXR1cm4gd2luZG93O1xuICAgIH1cbiAgICBlbHNlIGlmIChldmVudFRhcmdldE5hbWUgPT0gXCJkb2N1bWVudFwiKSB7XG4gICAgICAgIHJldHVybiBkb2N1bWVudDtcbiAgICB9XG59XG5mdW5jdGlvbiBwYXJzZUV2ZW50T3B0aW9ucyhldmVudE9wdGlvbnMpIHtcbiAgICByZXR1cm4gZXZlbnRPcHRpb25zLnNwbGl0KFwiOlwiKS5yZWR1Y2UoZnVuY3Rpb24gKG9wdGlvbnMsIHRva2VuKSB7XG4gICAgICAgIHZhciBfYTtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24ob3B0aW9ucywgKF9hID0ge30sIF9hW3Rva2VuLnJlcGxhY2UoL14hLywgXCJcIildID0gIS9eIS8udGVzdCh0b2tlbiksIF9hKSk7XG4gICAgfSwge30pO1xufVxuZXhwb3J0IGZ1bmN0aW9uIHN0cmluZ2lmeUV2ZW50VGFyZ2V0KGV2ZW50VGFyZ2V0KSB7XG4gICAgaWYgKGV2ZW50VGFyZ2V0ID09IHdpbmRvdykge1xuICAgICAgICByZXR1cm4gXCJ3aW5kb3dcIjtcbiAgICB9XG4gICAgZWxzZSBpZiAoZXZlbnRUYXJnZXQgPT0gZG9jdW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIFwiZG9jdW1lbnRcIjtcbiAgICB9XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1hY3Rpb25fZGVzY3JpcHRvci5qcy5tYXAiLCJ2YXIgX19hd2FpdGVyID0gKHRoaXMgJiYgdGhpcy5fX2F3YWl0ZXIpIHx8IGZ1bmN0aW9uICh0aGlzQXJnLCBfYXJndW1lbnRzLCBQLCBnZW5lcmF0b3IpIHtcbiAgICBmdW5jdGlvbiBhZG9wdCh2YWx1ZSkgeyByZXR1cm4gdmFsdWUgaW5zdGFuY2VvZiBQID8gdmFsdWUgOiBuZXcgUChmdW5jdGlvbiAocmVzb2x2ZSkgeyByZXNvbHZlKHZhbHVlKTsgfSk7IH1cbiAgICByZXR1cm4gbmV3IChQIHx8IChQID0gUHJvbWlzZSkpKGZ1bmN0aW9uIChyZXNvbHZlLCByZWplY3QpIHtcbiAgICAgICAgZnVuY3Rpb24gZnVsZmlsbGVkKHZhbHVlKSB7IHRyeSB7IHN0ZXAoZ2VuZXJhdG9yLm5leHQodmFsdWUpKTsgfSBjYXRjaCAoZSkgeyByZWplY3QoZSk7IH0gfVxuICAgICAgICBmdW5jdGlvbiByZWplY3RlZCh2YWx1ZSkgeyB0cnkgeyBzdGVwKGdlbmVyYXRvcltcInRocm93XCJdKHZhbHVlKSk7IH0gY2F0Y2ggKGUpIHsgcmVqZWN0KGUpOyB9IH1cbiAgICAgICAgZnVuY3Rpb24gc3RlcChyZXN1bHQpIHsgcmVzdWx0LmRvbmUgPyByZXNvbHZlKHJlc3VsdC52YWx1ZSkgOiBhZG9wdChyZXN1bHQudmFsdWUpLnRoZW4oZnVsZmlsbGVkLCByZWplY3RlZCk7IH1cbiAgICAgICAgc3RlcCgoZ2VuZXJhdG9yID0gZ2VuZXJhdG9yLmFwcGx5KHRoaXNBcmcsIF9hcmd1bWVudHMgfHwgW10pKS5uZXh0KCkpO1xuICAgIH0pO1xufTtcbnZhciBfX2dlbmVyYXRvciA9ICh0aGlzICYmIHRoaXMuX19nZW5lcmF0b3IpIHx8IGZ1bmN0aW9uICh0aGlzQXJnLCBib2R5KSB7XG4gICAgdmFyIF8gPSB7IGxhYmVsOiAwLCBzZW50OiBmdW5jdGlvbigpIHsgaWYgKHRbMF0gJiAxKSB0aHJvdyB0WzFdOyByZXR1cm4gdFsxXTsgfSwgdHJ5czogW10sIG9wczogW10gfSwgZiwgeSwgdCwgZztcbiAgICByZXR1cm4gZyA9IHsgbmV4dDogdmVyYigwKSwgXCJ0aHJvd1wiOiB2ZXJiKDEpLCBcInJldHVyblwiOiB2ZXJiKDIpIH0sIHR5cGVvZiBTeW1ib2wgPT09IFwiZnVuY3Rpb25cIiAmJiAoZ1tTeW1ib2wuaXRlcmF0b3JdID0gZnVuY3Rpb24oKSB7IHJldHVybiB0aGlzOyB9KSwgZztcbiAgICBmdW5jdGlvbiB2ZXJiKG4pIHsgcmV0dXJuIGZ1bmN0aW9uICh2KSB7IHJldHVybiBzdGVwKFtuLCB2XSk7IH07IH1cbiAgICBmdW5jdGlvbiBzdGVwKG9wKSB7XG4gICAgICAgIGlmIChmKSB0aHJvdyBuZXcgVHlwZUVycm9yKFwiR2VuZXJhdG9yIGlzIGFscmVhZHkgZXhlY3V0aW5nLlwiKTtcbiAgICAgICAgd2hpbGUgKF8pIHRyeSB7XG4gICAgICAgICAgICBpZiAoZiA9IDEsIHkgJiYgKHQgPSBvcFswXSAmIDIgPyB5W1wicmV0dXJuXCJdIDogb3BbMF0gPyB5W1widGhyb3dcIl0gfHwgKCh0ID0geVtcInJldHVyblwiXSkgJiYgdC5jYWxsKHkpLCAwKSA6IHkubmV4dCkgJiYgISh0ID0gdC5jYWxsKHksIG9wWzFdKSkuZG9uZSkgcmV0dXJuIHQ7XG4gICAgICAgICAgICBpZiAoeSA9IDAsIHQpIG9wID0gW29wWzBdICYgMiwgdC52YWx1ZV07XG4gICAgICAgICAgICBzd2l0Y2ggKG9wWzBdKSB7XG4gICAgICAgICAgICAgICAgY2FzZSAwOiBjYXNlIDE6IHQgPSBvcDsgYnJlYWs7XG4gICAgICAgICAgICAgICAgY2FzZSA0OiBfLmxhYmVsKys7IHJldHVybiB7IHZhbHVlOiBvcFsxXSwgZG9uZTogZmFsc2UgfTtcbiAgICAgICAgICAgICAgICBjYXNlIDU6IF8ubGFiZWwrKzsgeSA9IG9wWzFdOyBvcCA9IFswXTsgY29udGludWU7XG4gICAgICAgICAgICAgICAgY2FzZSA3OiBvcCA9IF8ub3BzLnBvcCgpOyBfLnRyeXMucG9wKCk7IGNvbnRpbnVlO1xuICAgICAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgICAgICAgIGlmICghKHQgPSBfLnRyeXMsIHQgPSB0Lmxlbmd0aCA+IDAgJiYgdFt0Lmxlbmd0aCAtIDFdKSAmJiAob3BbMF0gPT09IDYgfHwgb3BbMF0gPT09IDIpKSB7IF8gPSAwOyBjb250aW51ZTsgfVxuICAgICAgICAgICAgICAgICAgICBpZiAob3BbMF0gPT09IDMgJiYgKCF0IHx8IChvcFsxXSA+IHRbMF0gJiYgb3BbMV0gPCB0WzNdKSkpIHsgXy5sYWJlbCA9IG9wWzFdOyBicmVhazsgfVxuICAgICAgICAgICAgICAgICAgICBpZiAob3BbMF0gPT09IDYgJiYgXy5sYWJlbCA8IHRbMV0pIHsgXy5sYWJlbCA9IHRbMV07IHQgPSBvcDsgYnJlYWs7IH1cbiAgICAgICAgICAgICAgICAgICAgaWYgKHQgJiYgXy5sYWJlbCA8IHRbMl0pIHsgXy5sYWJlbCA9IHRbMl07IF8ub3BzLnB1c2gob3ApOyBicmVhazsgfVxuICAgICAgICAgICAgICAgICAgICBpZiAodFsyXSkgXy5vcHMucG9wKCk7XG4gICAgICAgICAgICAgICAgICAgIF8udHJ5cy5wb3AoKTsgY29udGludWU7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBvcCA9IGJvZHkuY2FsbCh0aGlzQXJnLCBfKTtcbiAgICAgICAgfSBjYXRjaCAoZSkgeyBvcCA9IFs2LCBlXTsgeSA9IDA7IH0gZmluYWxseSB7IGYgPSB0ID0gMDsgfVxuICAgICAgICBpZiAob3BbMF0gJiA1KSB0aHJvdyBvcFsxXTsgcmV0dXJuIHsgdmFsdWU6IG9wWzBdID8gb3BbMV0gOiB2b2lkIDAsIGRvbmU6IHRydWUgfTtcbiAgICB9XG59O1xudmFyIF9fc3ByZWFkQXJyYXlzID0gKHRoaXMgJiYgdGhpcy5fX3NwcmVhZEFycmF5cykgfHwgZnVuY3Rpb24gKCkge1xuICAgIGZvciAodmFyIHMgPSAwLCBpID0gMCwgaWwgPSBhcmd1bWVudHMubGVuZ3RoOyBpIDwgaWw7IGkrKykgcyArPSBhcmd1bWVudHNbaV0ubGVuZ3RoO1xuICAgIGZvciAodmFyIHIgPSBBcnJheShzKSwgayA9IDAsIGkgPSAwOyBpIDwgaWw7IGkrKylcbiAgICAgICAgZm9yICh2YXIgYSA9IGFyZ3VtZW50c1tpXSwgaiA9IDAsIGpsID0gYS5sZW5ndGg7IGogPCBqbDsgaisrLCBrKyspXG4gICAgICAgICAgICByW2tdID0gYVtqXTtcbiAgICByZXR1cm4gcjtcbn07XG5pbXBvcnQgeyBEaXNwYXRjaGVyIH0gZnJvbSBcIi4vZGlzcGF0Y2hlclwiO1xuaW1wb3J0IHsgUm91dGVyIH0gZnJvbSBcIi4vcm91dGVyXCI7XG5pbXBvcnQgeyBkZWZhdWx0U2NoZW1hIH0gZnJvbSBcIi4vc2NoZW1hXCI7XG52YXIgQXBwbGljYXRpb24gPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gQXBwbGljYXRpb24oZWxlbWVudCwgc2NoZW1hKSB7XG4gICAgICAgIGlmIChlbGVtZW50ID09PSB2b2lkIDApIHsgZWxlbWVudCA9IGRvY3VtZW50LmRvY3VtZW50RWxlbWVudDsgfVxuICAgICAgICBpZiAoc2NoZW1hID09PSB2b2lkIDApIHsgc2NoZW1hID0gZGVmYXVsdFNjaGVtYTsgfVxuICAgICAgICB0aGlzLmxvZ2dlciA9IGNvbnNvbGU7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuc2NoZW1hID0gc2NoZW1hO1xuICAgICAgICB0aGlzLmRpc3BhdGNoZXIgPSBuZXcgRGlzcGF0Y2hlcih0aGlzKTtcbiAgICAgICAgdGhpcy5yb3V0ZXIgPSBuZXcgUm91dGVyKHRoaXMpO1xuICAgIH1cbiAgICBBcHBsaWNhdGlvbi5zdGFydCA9IGZ1bmN0aW9uIChlbGVtZW50LCBzY2hlbWEpIHtcbiAgICAgICAgdmFyIGFwcGxpY2F0aW9uID0gbmV3IEFwcGxpY2F0aW9uKGVsZW1lbnQsIHNjaGVtYSk7XG4gICAgICAgIGFwcGxpY2F0aW9uLnN0YXJ0KCk7XG4gICAgICAgIHJldHVybiBhcHBsaWNhdGlvbjtcbiAgICB9O1xuICAgIEFwcGxpY2F0aW9uLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgcmV0dXJuIF9fYXdhaXRlcih0aGlzLCB2b2lkIDAsIHZvaWQgMCwgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIF9fZ2VuZXJhdG9yKHRoaXMsIGZ1bmN0aW9uIChfYSkge1xuICAgICAgICAgICAgICAgIHN3aXRjaCAoX2EubGFiZWwpIHtcbiAgICAgICAgICAgICAgICAgICAgY2FzZSAwOiByZXR1cm4gWzQgLyp5aWVsZCovLCBkb21SZWFkeSgpXTtcbiAgICAgICAgICAgICAgICAgICAgY2FzZSAxOlxuICAgICAgICAgICAgICAgICAgICAgICAgX2Euc2VudCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5kaXNwYXRjaGVyLnN0YXJ0KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnJvdXRlci5zdGFydCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIFsyIC8qcmV0dXJuKi9dO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9KTtcbiAgICB9O1xuICAgIEFwcGxpY2F0aW9uLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmRpc3BhdGNoZXIuc3RvcCgpO1xuICAgICAgICB0aGlzLnJvdXRlci5zdG9wKCk7XG4gICAgfTtcbiAgICBBcHBsaWNhdGlvbi5wcm90b3R5cGUucmVnaXN0ZXIgPSBmdW5jdGlvbiAoaWRlbnRpZmllciwgY29udHJvbGxlckNvbnN0cnVjdG9yKSB7XG4gICAgICAgIHRoaXMubG9hZCh7IGlkZW50aWZpZXI6IGlkZW50aWZpZXIsIGNvbnRyb2xsZXJDb25zdHJ1Y3RvcjogY29udHJvbGxlckNvbnN0cnVjdG9yIH0pO1xuICAgIH07XG4gICAgQXBwbGljYXRpb24ucHJvdG90eXBlLmxvYWQgPSBmdW5jdGlvbiAoaGVhZCkge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB2YXIgcmVzdCA9IFtdO1xuICAgICAgICBmb3IgKHZhciBfaSA9IDE7IF9pIDwgYXJndW1lbnRzLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgcmVzdFtfaSAtIDFdID0gYXJndW1lbnRzW19pXTtcbiAgICAgICAgfVxuICAgICAgICB2YXIgZGVmaW5pdGlvbnMgPSBBcnJheS5pc0FycmF5KGhlYWQpID8gaGVhZCA6IF9fc3ByZWFkQXJyYXlzKFtoZWFkXSwgcmVzdCk7XG4gICAgICAgIGRlZmluaXRpb25zLmZvckVhY2goZnVuY3Rpb24gKGRlZmluaXRpb24pIHsgcmV0dXJuIF90aGlzLnJvdXRlci5sb2FkRGVmaW5pdGlvbihkZWZpbml0aW9uKTsgfSk7XG4gICAgfTtcbiAgICBBcHBsaWNhdGlvbi5wcm90b3R5cGUudW5sb2FkID0gZnVuY3Rpb24gKGhlYWQpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdmFyIHJlc3QgPSBbXTtcbiAgICAgICAgZm9yICh2YXIgX2kgPSAxOyBfaSA8IGFyZ3VtZW50cy5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgIHJlc3RbX2kgLSAxXSA9IGFyZ3VtZW50c1tfaV07XG4gICAgICAgIH1cbiAgICAgICAgdmFyIGlkZW50aWZpZXJzID0gQXJyYXkuaXNBcnJheShoZWFkKSA/IGhlYWQgOiBfX3NwcmVhZEFycmF5cyhbaGVhZF0sIHJlc3QpO1xuICAgICAgICBpZGVudGlmaWVycy5mb3JFYWNoKGZ1bmN0aW9uIChpZGVudGlmaWVyKSB7IHJldHVybiBfdGhpcy5yb3V0ZXIudW5sb2FkSWRlbnRpZmllcihpZGVudGlmaWVyKTsgfSk7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQXBwbGljYXRpb24ucHJvdG90eXBlLCBcImNvbnRyb2xsZXJzXCIsIHtcbiAgICAgICAgLy8gQ29udHJvbGxlcnNcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5yb3V0ZXIuY29udGV4dHMubWFwKGZ1bmN0aW9uIChjb250ZXh0KSB7IHJldHVybiBjb250ZXh0LmNvbnRyb2xsZXI7IH0pO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgQXBwbGljYXRpb24ucHJvdG90eXBlLmdldENvbnRyb2xsZXJGb3JFbGVtZW50QW5kSWRlbnRpZmllciA9IGZ1bmN0aW9uIChlbGVtZW50LCBpZGVudGlmaWVyKSB7XG4gICAgICAgIHZhciBjb250ZXh0ID0gdGhpcy5yb3V0ZXIuZ2V0Q29udGV4dEZvckVsZW1lbnRBbmRJZGVudGlmaWVyKGVsZW1lbnQsIGlkZW50aWZpZXIpO1xuICAgICAgICByZXR1cm4gY29udGV4dCA/IGNvbnRleHQuY29udHJvbGxlciA6IG51bGw7XG4gICAgfTtcbiAgICAvLyBFcnJvciBoYW5kbGluZ1xuICAgIEFwcGxpY2F0aW9uLnByb3RvdHlwZS5oYW5kbGVFcnJvciA9IGZ1bmN0aW9uIChlcnJvciwgbWVzc2FnZSwgZGV0YWlsKSB7XG4gICAgICAgIHRoaXMubG9nZ2VyLmVycm9yKFwiJXNcXG5cXG4lb1xcblxcbiVvXCIsIG1lc3NhZ2UsIGVycm9yLCBkZXRhaWwpO1xuICAgIH07XG4gICAgcmV0dXJuIEFwcGxpY2F0aW9uO1xufSgpKTtcbmV4cG9ydCB7IEFwcGxpY2F0aW9uIH07XG5mdW5jdGlvbiBkb21SZWFkeSgpIHtcbiAgICByZXR1cm4gbmV3IFByb21pc2UoZnVuY3Rpb24gKHJlc29sdmUpIHtcbiAgICAgICAgaWYgKGRvY3VtZW50LnJlYWR5U3RhdGUgPT0gXCJsb2FkaW5nXCIpIHtcbiAgICAgICAgICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoXCJET01Db250ZW50TG9hZGVkXCIsIHJlc29sdmUpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmVzb2x2ZSgpO1xuICAgICAgICB9XG4gICAgfSk7XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1hcHBsaWNhdGlvbi5qcy5tYXAiLCJ2YXIgQmluZGluZyA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBCaW5kaW5nKGNvbnRleHQsIGFjdGlvbikge1xuICAgICAgICB0aGlzLmNvbnRleHQgPSBjb250ZXh0O1xuICAgICAgICB0aGlzLmFjdGlvbiA9IGFjdGlvbjtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEJpbmRpbmcucHJvdG90eXBlLCBcImluZGV4XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb24uaW5kZXg7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwiZXZlbnRUYXJnZXRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFjdGlvbi5ldmVudFRhcmdldDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJldmVudE9wdGlvbnNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFjdGlvbi5ldmVudE9wdGlvbnM7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwiaWRlbnRpZmllclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5pZGVudGlmaWVyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgQmluZGluZy5wcm90b3R5cGUuaGFuZGxlRXZlbnQgPSBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAgICAgaWYgKHRoaXMud2lsbEJlSW52b2tlZEJ5RXZlbnQoZXZlbnQpKSB7XG4gICAgICAgICAgICB0aGlzLmludm9rZVdpdGhFdmVudChldmVudCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJldmVudE5hbWVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFjdGlvbi5ldmVudE5hbWU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwibWV0aG9kXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgbWV0aG9kID0gdGhpcy5jb250cm9sbGVyW3RoaXMubWV0aG9kTmFtZV07XG4gICAgICAgICAgICBpZiAodHlwZW9mIG1ldGhvZCA9PSBcImZ1bmN0aW9uXCIpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gbWV0aG9kO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKFwiQWN0aW9uIFxcXCJcIiArIHRoaXMuYWN0aW9uICsgXCJcXFwiIHJlZmVyZW5jZXMgdW5kZWZpbmVkIG1ldGhvZCBcXFwiXCIgKyB0aGlzLm1ldGhvZE5hbWUgKyBcIlxcXCJcIik7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBCaW5kaW5nLnByb3RvdHlwZS5pbnZva2VXaXRoRXZlbnQgPSBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHRoaXMubWV0aG9kLmNhbGwodGhpcy5jb250cm9sbGVyLCBldmVudCk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICB2YXIgX2EgPSB0aGlzLCBpZGVudGlmaWVyID0gX2EuaWRlbnRpZmllciwgY29udHJvbGxlciA9IF9hLmNvbnRyb2xsZXIsIGVsZW1lbnQgPSBfYS5lbGVtZW50LCBpbmRleCA9IF9hLmluZGV4O1xuICAgICAgICAgICAgdmFyIGRldGFpbCA9IHsgaWRlbnRpZmllcjogaWRlbnRpZmllciwgY29udHJvbGxlcjogY29udHJvbGxlciwgZWxlbWVudDogZWxlbWVudCwgaW5kZXg6IGluZGV4LCBldmVudDogZXZlbnQgfTtcbiAgICAgICAgICAgIHRoaXMuY29udGV4dC5oYW5kbGVFcnJvcihlcnJvciwgXCJpbnZva2luZyBhY3Rpb24gXFxcIlwiICsgdGhpcy5hY3Rpb24gKyBcIlxcXCJcIiwgZGV0YWlsKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgQmluZGluZy5wcm90b3R5cGUud2lsbEJlSW52b2tlZEJ5RXZlbnQgPSBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAgICAgdmFyIGV2ZW50VGFyZ2V0ID0gZXZlbnQudGFyZ2V0O1xuICAgICAgICBpZiAodGhpcy5lbGVtZW50ID09PSBldmVudFRhcmdldCkge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAoZXZlbnRUYXJnZXQgaW5zdGFuY2VvZiBFbGVtZW50ICYmIHRoaXMuZWxlbWVudC5jb250YWlucyhldmVudFRhcmdldCkpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmNvbnRhaW5zRWxlbWVudChldmVudFRhcmdldCk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5jb250YWluc0VsZW1lbnQodGhpcy5hY3Rpb24uZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJjb250cm9sbGVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmNvbnRyb2xsZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwibWV0aG9kTmFtZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYWN0aW9uLm1ldGhvZE5hbWU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZy5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nLnByb3RvdHlwZSwgXCJzY29wZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5zY29wZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIHJldHVybiBCaW5kaW5nO1xufSgpKTtcbmV4cG9ydCB7IEJpbmRpbmcgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWJpbmRpbmcuanMubWFwIiwiaW1wb3J0IHsgQWN0aW9uIH0gZnJvbSBcIi4vYWN0aW9uXCI7XG5pbXBvcnQgeyBCaW5kaW5nIH0gZnJvbSBcIi4vYmluZGluZ1wiO1xuaW1wb3J0IHsgVmFsdWVMaXN0T2JzZXJ2ZXIgfSBmcm9tIFwiQHN0aW11bHVzL211dGF0aW9uLW9ic2VydmVyc1wiO1xudmFyIEJpbmRpbmdPYnNlcnZlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBCaW5kaW5nT2JzZXJ2ZXIoY29udGV4dCwgZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5jb250ZXh0ID0gY29udGV4dDtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLmJpbmRpbmdzQnlBY3Rpb24gPSBuZXcgTWFwO1xuICAgIH1cbiAgICBCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAoIXRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIpIHtcbiAgICAgICAgICAgIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIgPSBuZXcgVmFsdWVMaXN0T2JzZXJ2ZXIodGhpcy5lbGVtZW50LCB0aGlzLmFjdGlvbkF0dHJpYnV0ZSwgdGhpcyk7XG4gICAgICAgICAgICB0aGlzLnZhbHVlTGlzdE9ic2VydmVyLnN0YXJ0KCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIpIHtcbiAgICAgICAgICAgIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIuc3RvcCgpO1xuICAgICAgICAgICAgZGVsZXRlIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXI7XG4gICAgICAgICAgICB0aGlzLmRpc2Nvbm5lY3RBbGxBY3Rpb25zKCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLCBcImlkZW50aWZpZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuaWRlbnRpZmllcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLCBcImFjdGlvbkF0dHJpYnV0ZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NoZW1hLmFjdGlvbkF0dHJpYnV0ZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLCBcInNjaGVtYVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5zY2hlbWE7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQmluZGluZ09ic2VydmVyLnByb3RvdHlwZSwgXCJiaW5kaW5nc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5iaW5kaW5nc0J5QWN0aW9uLnZhbHVlcygpKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUuY29ubmVjdEFjdGlvbiA9IGZ1bmN0aW9uIChhY3Rpb24pIHtcbiAgICAgICAgdmFyIGJpbmRpbmcgPSBuZXcgQmluZGluZyh0aGlzLmNvbnRleHQsIGFjdGlvbik7XG4gICAgICAgIHRoaXMuYmluZGluZ3NCeUFjdGlvbi5zZXQoYWN0aW9uLCBiaW5kaW5nKTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS5iaW5kaW5nQ29ubmVjdGVkKGJpbmRpbmcpO1xuICAgIH07XG4gICAgQmluZGluZ09ic2VydmVyLnByb3RvdHlwZS5kaXNjb25uZWN0QWN0aW9uID0gZnVuY3Rpb24gKGFjdGlvbikge1xuICAgICAgICB2YXIgYmluZGluZyA9IHRoaXMuYmluZGluZ3NCeUFjdGlvbi5nZXQoYWN0aW9uKTtcbiAgICAgICAgaWYgKGJpbmRpbmcpIHtcbiAgICAgICAgICAgIHRoaXMuYmluZGluZ3NCeUFjdGlvbi5kZWxldGUoYWN0aW9uKTtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuYmluZGluZ0Rpc2Nvbm5lY3RlZChiaW5kaW5nKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgQmluZGluZ09ic2VydmVyLnByb3RvdHlwZS5kaXNjb25uZWN0QWxsQWN0aW9ucyA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdGhpcy5iaW5kaW5ncy5mb3JFYWNoKGZ1bmN0aW9uIChiaW5kaW5nKSB7IHJldHVybiBfdGhpcy5kZWxlZ2F0ZS5iaW5kaW5nRGlzY29ubmVjdGVkKGJpbmRpbmcpOyB9KTtcbiAgICAgICAgdGhpcy5iaW5kaW5nc0J5QWN0aW9uLmNsZWFyKCk7XG4gICAgfTtcbiAgICAvLyBWYWx1ZSBvYnNlcnZlciBkZWxlZ2F0ZVxuICAgIEJpbmRpbmdPYnNlcnZlci5wcm90b3R5cGUucGFyc2VWYWx1ZUZvclRva2VuID0gZnVuY3Rpb24gKHRva2VuKSB7XG4gICAgICAgIHZhciBhY3Rpb24gPSBBY3Rpb24uZm9yVG9rZW4odG9rZW4pO1xuICAgICAgICBpZiAoYWN0aW9uLmlkZW50aWZpZXIgPT0gdGhpcy5pZGVudGlmaWVyKSB7XG4gICAgICAgICAgICByZXR1cm4gYWN0aW9uO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBCaW5kaW5nT2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRNYXRjaGVkVmFsdWUgPSBmdW5jdGlvbiAoZWxlbWVudCwgYWN0aW9uKSB7XG4gICAgICAgIHRoaXMuY29ubmVjdEFjdGlvbihhY3Rpb24pO1xuICAgIH07XG4gICAgQmluZGluZ09ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50VW5tYXRjaGVkVmFsdWUgPSBmdW5jdGlvbiAoZWxlbWVudCwgYWN0aW9uKSB7XG4gICAgICAgIHRoaXMuZGlzY29ubmVjdEFjdGlvbihhY3Rpb24pO1xuICAgIH07XG4gICAgcmV0dXJuIEJpbmRpbmdPYnNlcnZlcjtcbn0oKSk7XG5leHBvcnQgeyBCaW5kaW5nT2JzZXJ2ZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWJpbmRpbmdfb2JzZXJ2ZXIuanMubWFwIiwidmFyIF9fZXh0ZW5kcyA9ICh0aGlzICYmIHRoaXMuX19leHRlbmRzKSB8fCAoZnVuY3Rpb24gKCkge1xuICAgIHZhciBleHRlbmRTdGF0aWNzID0gZnVuY3Rpb24gKGQsIGIpIHtcbiAgICAgICAgZXh0ZW5kU3RhdGljcyA9IE9iamVjdC5zZXRQcm90b3R5cGVPZiB8fFxuICAgICAgICAgICAgKHsgX19wcm90b19fOiBbXSB9IGluc3RhbmNlb2YgQXJyYXkgJiYgZnVuY3Rpb24gKGQsIGIpIHsgZC5fX3Byb3RvX18gPSBiOyB9KSB8fFxuICAgICAgICAgICAgZnVuY3Rpb24gKGQsIGIpIHsgZm9yICh2YXIgcCBpbiBiKSBpZiAoYi5oYXNPd25Qcm9wZXJ0eShwKSkgZFtwXSA9IGJbcF07IH07XG4gICAgICAgIHJldHVybiBleHRlbmRTdGF0aWNzKGQsIGIpO1xuICAgIH07XG4gICAgcmV0dXJuIGZ1bmN0aW9uIChkLCBiKSB7XG4gICAgICAgIGV4dGVuZFN0YXRpY3MoZCwgYik7XG4gICAgICAgIGZ1bmN0aW9uIF9fKCkgeyB0aGlzLmNvbnN0cnVjdG9yID0gZDsgfVxuICAgICAgICBkLnByb3RvdHlwZSA9IGIgPT09IG51bGwgPyBPYmplY3QuY3JlYXRlKGIpIDogKF9fLnByb3RvdHlwZSA9IGIucHJvdG90eXBlLCBuZXcgX18oKSk7XG4gICAgfTtcbn0pKCk7XG52YXIgX19zcHJlYWRBcnJheXMgPSAodGhpcyAmJiB0aGlzLl9fc3ByZWFkQXJyYXlzKSB8fCBmdW5jdGlvbiAoKSB7XG4gICAgZm9yICh2YXIgcyA9IDAsIGkgPSAwLCBpbCA9IGFyZ3VtZW50cy5sZW5ndGg7IGkgPCBpbDsgaSsrKSBzICs9IGFyZ3VtZW50c1tpXS5sZW5ndGg7XG4gICAgZm9yICh2YXIgciA9IEFycmF5KHMpLCBrID0gMCwgaSA9IDA7IGkgPCBpbDsgaSsrKVxuICAgICAgICBmb3IgKHZhciBhID0gYXJndW1lbnRzW2ldLCBqID0gMCwgamwgPSBhLmxlbmd0aDsgaiA8IGpsOyBqKyssIGsrKylcbiAgICAgICAgICAgIHJba10gPSBhW2pdO1xuICAgIHJldHVybiByO1xufTtcbmltcG9ydCB7IHJlYWRJbmhlcml0YWJsZVN0YXRpY0FycmF5VmFsdWVzIH0gZnJvbSBcIi4vaW5oZXJpdGFibGVfc3RhdGljc1wiO1xuLyoqIEBoaWRkZW4gKi9cbmV4cG9ydCBmdW5jdGlvbiBibGVzcyhjb25zdHJ1Y3Rvcikge1xuICAgIHJldHVybiBzaGFkb3coY29uc3RydWN0b3IsIGdldEJsZXNzZWRQcm9wZXJ0aWVzKGNvbnN0cnVjdG9yKSk7XG59XG5mdW5jdGlvbiBzaGFkb3coY29uc3RydWN0b3IsIHByb3BlcnRpZXMpIHtcbiAgICB2YXIgc2hhZG93Q29uc3RydWN0b3IgPSBleHRlbmQoY29uc3RydWN0b3IpO1xuICAgIHZhciBzaGFkb3dQcm9wZXJ0aWVzID0gZ2V0U2hhZG93UHJvcGVydGllcyhjb25zdHJ1Y3Rvci5wcm90b3R5cGUsIHByb3BlcnRpZXMpO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0aWVzKHNoYWRvd0NvbnN0cnVjdG9yLnByb3RvdHlwZSwgc2hhZG93UHJvcGVydGllcyk7XG4gICAgcmV0dXJuIHNoYWRvd0NvbnN0cnVjdG9yO1xufVxuZnVuY3Rpb24gZ2V0Qmxlc3NlZFByb3BlcnRpZXMoY29uc3RydWN0b3IpIHtcbiAgICB2YXIgYmxlc3NpbmdzID0gcmVhZEluaGVyaXRhYmxlU3RhdGljQXJyYXlWYWx1ZXMoY29uc3RydWN0b3IsIFwiYmxlc3NpbmdzXCIpO1xuICAgIHJldHVybiBibGVzc2luZ3MucmVkdWNlKGZ1bmN0aW9uIChibGVzc2VkUHJvcGVydGllcywgYmxlc3NpbmcpIHtcbiAgICAgICAgdmFyIHByb3BlcnRpZXMgPSBibGVzc2luZyhjb25zdHJ1Y3Rvcik7XG4gICAgICAgIGZvciAodmFyIGtleSBpbiBwcm9wZXJ0aWVzKSB7XG4gICAgICAgICAgICB2YXIgZGVzY3JpcHRvciA9IGJsZXNzZWRQcm9wZXJ0aWVzW2tleV0gfHwge307XG4gICAgICAgICAgICBibGVzc2VkUHJvcGVydGllc1trZXldID0gT2JqZWN0LmFzc2lnbihkZXNjcmlwdG9yLCBwcm9wZXJ0aWVzW2tleV0pO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBibGVzc2VkUHJvcGVydGllcztcbiAgICB9LCB7fSk7XG59XG5mdW5jdGlvbiBnZXRTaGFkb3dQcm9wZXJ0aWVzKHByb3RvdHlwZSwgcHJvcGVydGllcykge1xuICAgIHJldHVybiBnZXRPd25LZXlzKHByb3BlcnRpZXMpLnJlZHVjZShmdW5jdGlvbiAoc2hhZG93UHJvcGVydGllcywga2V5KSB7XG4gICAgICAgIHZhciBfYTtcbiAgICAgICAgdmFyIGRlc2NyaXB0b3IgPSBnZXRTaGFkb3dlZERlc2NyaXB0b3IocHJvdG90eXBlLCBwcm9wZXJ0aWVzLCBrZXkpO1xuICAgICAgICBpZiAoZGVzY3JpcHRvcikge1xuICAgICAgICAgICAgT2JqZWN0LmFzc2lnbihzaGFkb3dQcm9wZXJ0aWVzLCAoX2EgPSB7fSwgX2Fba2V5XSA9IGRlc2NyaXB0b3IsIF9hKSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHNoYWRvd1Byb3BlcnRpZXM7XG4gICAgfSwge30pO1xufVxuZnVuY3Rpb24gZ2V0U2hhZG93ZWREZXNjcmlwdG9yKHByb3RvdHlwZSwgcHJvcGVydGllcywga2V5KSB7XG4gICAgdmFyIHNoYWRvd2luZ0Rlc2NyaXB0b3IgPSBPYmplY3QuZ2V0T3duUHJvcGVydHlEZXNjcmlwdG9yKHByb3RvdHlwZSwga2V5KTtcbiAgICB2YXIgc2hhZG93ZWRCeVZhbHVlID0gc2hhZG93aW5nRGVzY3JpcHRvciAmJiBcInZhbHVlXCIgaW4gc2hhZG93aW5nRGVzY3JpcHRvcjtcbiAgICBpZiAoIXNoYWRvd2VkQnlWYWx1ZSkge1xuICAgICAgICB2YXIgZGVzY3JpcHRvciA9IE9iamVjdC5nZXRPd25Qcm9wZXJ0eURlc2NyaXB0b3IocHJvcGVydGllcywga2V5KS52YWx1ZTtcbiAgICAgICAgaWYgKHNoYWRvd2luZ0Rlc2NyaXB0b3IpIHtcbiAgICAgICAgICAgIGRlc2NyaXB0b3IuZ2V0ID0gc2hhZG93aW5nRGVzY3JpcHRvci5nZXQgfHwgZGVzY3JpcHRvci5nZXQ7XG4gICAgICAgICAgICBkZXNjcmlwdG9yLnNldCA9IHNoYWRvd2luZ0Rlc2NyaXB0b3Iuc2V0IHx8IGRlc2NyaXB0b3Iuc2V0O1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBkZXNjcmlwdG9yO1xuICAgIH1cbn1cbnZhciBnZXRPd25LZXlzID0gKGZ1bmN0aW9uICgpIHtcbiAgICBpZiAodHlwZW9mIE9iamVjdC5nZXRPd25Qcm9wZXJ0eVN5bWJvbHMgPT0gXCJmdW5jdGlvblwiKSB7XG4gICAgICAgIHJldHVybiBmdW5jdGlvbiAob2JqZWN0KSB7IHJldHVybiBfX3NwcmVhZEFycmF5cyhPYmplY3QuZ2V0T3duUHJvcGVydHlOYW1lcyhvYmplY3QpLCBPYmplY3QuZ2V0T3duUHJvcGVydHlTeW1ib2xzKG9iamVjdCkpOyB9O1xuICAgIH1cbiAgICBlbHNlIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5nZXRPd25Qcm9wZXJ0eU5hbWVzO1xuICAgIH1cbn0pKCk7XG52YXIgZXh0ZW5kID0gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBleHRlbmRXaXRoUmVmbGVjdChjb25zdHJ1Y3Rvcikge1xuICAgICAgICBmdW5jdGlvbiBleHRlbmRlZCgpIHtcbiAgICAgICAgICAgIHZhciBfbmV3VGFyZ2V0ID0gdGhpcyAmJiB0aGlzIGluc3RhbmNlb2YgZXh0ZW5kZWQgPyB0aGlzLmNvbnN0cnVjdG9yIDogdm9pZCAwO1xuICAgICAgICAgICAgcmV0dXJuIFJlZmxlY3QuY29uc3RydWN0KGNvbnN0cnVjdG9yLCBhcmd1bWVudHMsIF9uZXdUYXJnZXQpO1xuICAgICAgICB9XG4gICAgICAgIGV4dGVuZGVkLnByb3RvdHlwZSA9IE9iamVjdC5jcmVhdGUoY29uc3RydWN0b3IucHJvdG90eXBlLCB7XG4gICAgICAgICAgICBjb25zdHJ1Y3RvcjogeyB2YWx1ZTogZXh0ZW5kZWQgfVxuICAgICAgICB9KTtcbiAgICAgICAgUmVmbGVjdC5zZXRQcm90b3R5cGVPZihleHRlbmRlZCwgY29uc3RydWN0b3IpO1xuICAgICAgICByZXR1cm4gZXh0ZW5kZWQ7XG4gICAgfVxuICAgIGZ1bmN0aW9uIHRlc3RSZWZsZWN0RXh0ZW5zaW9uKCkge1xuICAgICAgICB2YXIgYSA9IGZ1bmN0aW9uICgpIHsgdGhpcy5hLmNhbGwodGhpcyk7IH07XG4gICAgICAgIHZhciBiID0gZXh0ZW5kV2l0aFJlZmxlY3QoYSk7XG4gICAgICAgIGIucHJvdG90eXBlLmEgPSBmdW5jdGlvbiAoKSB7IH07XG4gICAgICAgIHJldHVybiBuZXcgYjtcbiAgICB9XG4gICAgdHJ5IHtcbiAgICAgICAgdGVzdFJlZmxlY3RFeHRlbnNpb24oKTtcbiAgICAgICAgcmV0dXJuIGV4dGVuZFdpdGhSZWZsZWN0O1xuICAgIH1cbiAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgcmV0dXJuIGZ1bmN0aW9uIChjb25zdHJ1Y3RvcikgeyByZXR1cm4gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKF9zdXBlcikge1xuICAgICAgICAgICAgX19leHRlbmRzKGV4dGVuZGVkLCBfc3VwZXIpO1xuICAgICAgICAgICAgZnVuY3Rpb24gZXh0ZW5kZWQoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIF9zdXBlciAhPT0gbnVsbCAmJiBfc3VwZXIuYXBwbHkodGhpcywgYXJndW1lbnRzKSB8fCB0aGlzO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIGV4dGVuZGVkO1xuICAgICAgICB9KGNvbnN0cnVjdG9yKSk7IH07XG4gICAgfVxufSkoKTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWJsZXNzaW5nLmpzLm1hcCIsInZhciBDbGFzc01hcCA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBDbGFzc01hcChzY29wZSkge1xuICAgICAgICB0aGlzLnNjb3BlID0gc2NvcGU7XG4gICAgfVxuICAgIENsYXNzTWFwLnByb3RvdHlwZS5oYXMgPSBmdW5jdGlvbiAobmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5kYXRhLmhhcyh0aGlzLmdldERhdGFLZXkobmFtZSkpO1xuICAgIH07XG4gICAgQ2xhc3NNYXAucHJvdG90eXBlLmdldCA9IGZ1bmN0aW9uIChuYW1lKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmRhdGEuZ2V0KHRoaXMuZ2V0RGF0YUtleShuYW1lKSk7XG4gICAgfTtcbiAgICBDbGFzc01hcC5wcm90b3R5cGUuZ2V0QXR0cmlidXRlTmFtZSA9IGZ1bmN0aW9uIChuYW1lKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmRhdGEuZ2V0QXR0cmlidXRlTmFtZUZvcktleSh0aGlzLmdldERhdGFLZXkobmFtZSkpO1xuICAgIH07XG4gICAgQ2xhc3NNYXAucHJvdG90eXBlLmdldERhdGFLZXkgPSBmdW5jdGlvbiAobmFtZSkge1xuICAgICAgICByZXR1cm4gbmFtZSArIFwiLWNsYXNzXCI7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ2xhc3NNYXAucHJvdG90eXBlLCBcImRhdGFcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmRhdGE7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICByZXR1cm4gQ2xhc3NNYXA7XG59KCkpO1xuZXhwb3J0IHsgQ2xhc3NNYXAgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWNsYXNzX21hcC5qcy5tYXAiLCJpbXBvcnQgeyByZWFkSW5oZXJpdGFibGVTdGF0aWNBcnJheVZhbHVlcyB9IGZyb20gXCIuL2luaGVyaXRhYmxlX3N0YXRpY3NcIjtcbmltcG9ydCB7IGNhcGl0YWxpemUgfSBmcm9tIFwiLi9zdHJpbmdfaGVscGVyc1wiO1xuLyoqIEBoaWRkZW4gKi9cbmV4cG9ydCBmdW5jdGlvbiBDbGFzc1Byb3BlcnRpZXNCbGVzc2luZyhjb25zdHJ1Y3Rvcikge1xuICAgIHZhciBjbGFzc2VzID0gcmVhZEluaGVyaXRhYmxlU3RhdGljQXJyYXlWYWx1ZXMoY29uc3RydWN0b3IsIFwiY2xhc3Nlc1wiKTtcbiAgICByZXR1cm4gY2xhc3Nlcy5yZWR1Y2UoZnVuY3Rpb24gKHByb3BlcnRpZXMsIGNsYXNzRGVmaW5pdGlvbikge1xuICAgICAgICByZXR1cm4gT2JqZWN0LmFzc2lnbihwcm9wZXJ0aWVzLCBwcm9wZXJ0aWVzRm9yQ2xhc3NEZWZpbml0aW9uKGNsYXNzRGVmaW5pdGlvbikpO1xuICAgIH0sIHt9KTtcbn1cbmZ1bmN0aW9uIHByb3BlcnRpZXNGb3JDbGFzc0RlZmluaXRpb24oa2V5KSB7XG4gICAgdmFyIF9hO1xuICAgIHZhciBuYW1lID0ga2V5ICsgXCJDbGFzc1wiO1xuICAgIHJldHVybiBfYSA9IHt9LFxuICAgICAgICBfYVtuYW1lXSA9IHtcbiAgICAgICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHZhciBjbGFzc2VzID0gdGhpcy5jbGFzc2VzO1xuICAgICAgICAgICAgICAgIGlmIChjbGFzc2VzLmhhcyhrZXkpKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBjbGFzc2VzLmdldChrZXkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGF0dHJpYnV0ZSA9IGNsYXNzZXMuZ2V0QXR0cmlidXRlTmFtZShrZXkpO1xuICAgICAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoXCJNaXNzaW5nIGF0dHJpYnV0ZSBcXFwiXCIgKyBhdHRyaWJ1dGUgKyBcIlxcXCJcIik7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBfYVtcImhhc1wiICsgY2FwaXRhbGl6ZShuYW1lKV0gPSB7XG4gICAgICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5jbGFzc2VzLmhhcyhrZXkpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBfYTtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWNsYXNzX3Byb3BlcnRpZXMuanMubWFwIiwiaW1wb3J0IHsgQmluZGluZ09ic2VydmVyIH0gZnJvbSBcIi4vYmluZGluZ19vYnNlcnZlclwiO1xuaW1wb3J0IHsgVmFsdWVPYnNlcnZlciB9IGZyb20gXCIuL3ZhbHVlX29ic2VydmVyXCI7XG52YXIgQ29udGV4dCA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBDb250ZXh0KG1vZHVsZSwgc2NvcGUpIHtcbiAgICAgICAgdGhpcy5tb2R1bGUgPSBtb2R1bGU7XG4gICAgICAgIHRoaXMuc2NvcGUgPSBzY29wZTtcbiAgICAgICAgdGhpcy5jb250cm9sbGVyID0gbmV3IG1vZHVsZS5jb250cm9sbGVyQ29uc3RydWN0b3IodGhpcyk7XG4gICAgICAgIHRoaXMuYmluZGluZ09ic2VydmVyID0gbmV3IEJpbmRpbmdPYnNlcnZlcih0aGlzLCB0aGlzLmRpc3BhdGNoZXIpO1xuICAgICAgICB0aGlzLnZhbHVlT2JzZXJ2ZXIgPSBuZXcgVmFsdWVPYnNlcnZlcih0aGlzLCB0aGlzLmNvbnRyb2xsZXIpO1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgdGhpcy5jb250cm9sbGVyLmluaXRpYWxpemUoKTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHRoaXMuaGFuZGxlRXJyb3IoZXJyb3IsIFwiaW5pdGlhbGl6aW5nIGNvbnRyb2xsZXJcIik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgQ29udGV4dC5wcm90b3R5cGUuY29ubmVjdCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5iaW5kaW5nT2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICAgICAgdGhpcy52YWx1ZU9ic2VydmVyLnN0YXJ0KCk7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICB0aGlzLmNvbnRyb2xsZXIuY29ubmVjdCgpO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgdGhpcy5oYW5kbGVFcnJvcihlcnJvciwgXCJjb25uZWN0aW5nIGNvbnRyb2xsZXJcIik7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIENvbnRleHQucHJvdG90eXBlLmRpc2Nvbm5lY3QgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICB0aGlzLmNvbnRyb2xsZXIuZGlzY29ubmVjdCgpO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgdGhpcy5oYW5kbGVFcnJvcihlcnJvciwgXCJkaXNjb25uZWN0aW5nIGNvbnRyb2xsZXJcIik7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy52YWx1ZU9ic2VydmVyLnN0b3AoKTtcbiAgICAgICAgdGhpcy5iaW5kaW5nT2JzZXJ2ZXIuc3RvcCgpO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRleHQucHJvdG90eXBlLCBcImFwcGxpY2F0aW9uXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5tb2R1bGUuYXBwbGljYXRpb247XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udGV4dC5wcm90b3R5cGUsIFwiaWRlbnRpZmllclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMubW9kdWxlLmlkZW50aWZpZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udGV4dC5wcm90b3R5cGUsIFwic2NoZW1hXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hcHBsaWNhdGlvbi5zY2hlbWE7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udGV4dC5wcm90b3R5cGUsIFwiZGlzcGF0Y2hlclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYXBwbGljYXRpb24uZGlzcGF0Y2hlcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250ZXh0LnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRleHQucHJvdG90eXBlLCBcInBhcmVudEVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnQucGFyZW50RWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIC8vIEVycm9yIGhhbmRsaW5nXG4gICAgQ29udGV4dC5wcm90b3R5cGUuaGFuZGxlRXJyb3IgPSBmdW5jdGlvbiAoZXJyb3IsIG1lc3NhZ2UsIGRldGFpbCkge1xuICAgICAgICBpZiAoZGV0YWlsID09PSB2b2lkIDApIHsgZGV0YWlsID0ge307IH1cbiAgICAgICAgdmFyIF9hID0gdGhpcywgaWRlbnRpZmllciA9IF9hLmlkZW50aWZpZXIsIGNvbnRyb2xsZXIgPSBfYS5jb250cm9sbGVyLCBlbGVtZW50ID0gX2EuZWxlbWVudDtcbiAgICAgICAgZGV0YWlsID0gT2JqZWN0LmFzc2lnbih7IGlkZW50aWZpZXI6IGlkZW50aWZpZXIsIGNvbnRyb2xsZXI6IGNvbnRyb2xsZXIsIGVsZW1lbnQ6IGVsZW1lbnQgfSwgZGV0YWlsKTtcbiAgICAgICAgdGhpcy5hcHBsaWNhdGlvbi5oYW5kbGVFcnJvcihlcnJvciwgXCJFcnJvciBcIiArIG1lc3NhZ2UsIGRldGFpbCk7XG4gICAgfTtcbiAgICByZXR1cm4gQ29udGV4dDtcbn0oKSk7XG5leHBvcnQgeyBDb250ZXh0IH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1jb250ZXh0LmpzLm1hcCIsImltcG9ydCB7IENsYXNzUHJvcGVydGllc0JsZXNzaW5nIH0gZnJvbSBcIi4vY2xhc3NfcHJvcGVydGllc1wiO1xuaW1wb3J0IHsgVGFyZ2V0UHJvcGVydGllc0JsZXNzaW5nIH0gZnJvbSBcIi4vdGFyZ2V0X3Byb3BlcnRpZXNcIjtcbmltcG9ydCB7IFZhbHVlUHJvcGVydGllc0JsZXNzaW5nIH0gZnJvbSBcIi4vdmFsdWVfcHJvcGVydGllc1wiO1xudmFyIENvbnRyb2xsZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gQ29udHJvbGxlcihjb250ZXh0KSB7XG4gICAgICAgIHRoaXMuY29udGV4dCA9IGNvbnRleHQ7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250cm9sbGVyLnByb3RvdHlwZSwgXCJhcHBsaWNhdGlvblwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5hcHBsaWNhdGlvbjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250cm9sbGVyLnByb3RvdHlwZSwgXCJzY29wZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5zY29wZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250cm9sbGVyLnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRyb2xsZXIucHJvdG90eXBlLCBcImlkZW50aWZpZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmlkZW50aWZpZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQ29udHJvbGxlci5wcm90b3R5cGUsIFwidGFyZ2V0c1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUudGFyZ2V0cztcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShDb250cm9sbGVyLnByb3RvdHlwZSwgXCJjbGFzc2VzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5jbGFzc2VzO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KENvbnRyb2xsZXIucHJvdG90eXBlLCBcImRhdGFcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmRhdGE7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBDb250cm9sbGVyLnByb3RvdHlwZS5pbml0aWFsaXplID0gZnVuY3Rpb24gKCkge1xuICAgICAgICAvLyBPdmVycmlkZSBpbiB5b3VyIHN1YmNsYXNzIHRvIHNldCB1cCBpbml0aWFsIGNvbnRyb2xsZXIgc3RhdGVcbiAgICB9O1xuICAgIENvbnRyb2xsZXIucHJvdG90eXBlLmNvbm5lY3QgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIC8vIE92ZXJyaWRlIGluIHlvdXIgc3ViY2xhc3MgdG8gcmVzcG9uZCB3aGVuIHRoZSBjb250cm9sbGVyIGlzIGNvbm5lY3RlZCB0byB0aGUgRE9NXG4gICAgfTtcbiAgICBDb250cm9sbGVyLnByb3RvdHlwZS5kaXNjb25uZWN0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICAvLyBPdmVycmlkZSBpbiB5b3VyIHN1YmNsYXNzIHRvIHJlc3BvbmQgd2hlbiB0aGUgY29udHJvbGxlciBpcyBkaXNjb25uZWN0ZWQgZnJvbSB0aGUgRE9NXG4gICAgfTtcbiAgICBDb250cm9sbGVyLmJsZXNzaW5ncyA9IFtDbGFzc1Byb3BlcnRpZXNCbGVzc2luZywgVGFyZ2V0UHJvcGVydGllc0JsZXNzaW5nLCBWYWx1ZVByb3BlcnRpZXNCbGVzc2luZ107XG4gICAgQ29udHJvbGxlci50YXJnZXRzID0gW107XG4gICAgQ29udHJvbGxlci52YWx1ZXMgPSB7fTtcbiAgICByZXR1cm4gQ29udHJvbGxlcjtcbn0oKSk7XG5leHBvcnQgeyBDb250cm9sbGVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1jb250cm9sbGVyLmpzLm1hcCIsImltcG9ydCB7IGRhc2hlcml6ZSB9IGZyb20gXCIuL3N0cmluZ19oZWxwZXJzXCI7XG52YXIgRGF0YU1hcCA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBEYXRhTWFwKHNjb3BlKSB7XG4gICAgICAgIHRoaXMuc2NvcGUgPSBzY29wZTtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KERhdGFNYXAucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoRGF0YU1hcC5wcm90b3R5cGUsIFwiaWRlbnRpZmllclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuaWRlbnRpZmllcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIERhdGFNYXAucHJvdG90eXBlLmdldCA9IGZ1bmN0aW9uIChrZXkpIHtcbiAgICAgICAgdmFyIG5hbWUgPSB0aGlzLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkoa2V5KTtcbiAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5nZXRBdHRyaWJ1dGUobmFtZSk7XG4gICAgfTtcbiAgICBEYXRhTWFwLnByb3RvdHlwZS5zZXQgPSBmdW5jdGlvbiAoa2V5LCB2YWx1ZSkge1xuICAgICAgICB2YXIgbmFtZSA9IHRoaXMuZ2V0QXR0cmlidXRlTmFtZUZvcktleShrZXkpO1xuICAgICAgICB0aGlzLmVsZW1lbnQuc2V0QXR0cmlidXRlKG5hbWUsIHZhbHVlKTtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0KGtleSk7XG4gICAgfTtcbiAgICBEYXRhTWFwLnByb3RvdHlwZS5oYXMgPSBmdW5jdGlvbiAoa2V5KSB7XG4gICAgICAgIHZhciBuYW1lID0gdGhpcy5nZXRBdHRyaWJ1dGVOYW1lRm9yS2V5KGtleSk7XG4gICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnQuaGFzQXR0cmlidXRlKG5hbWUpO1xuICAgIH07XG4gICAgRGF0YU1hcC5wcm90b3R5cGUuZGVsZXRlID0gZnVuY3Rpb24gKGtleSkge1xuICAgICAgICBpZiAodGhpcy5oYXMoa2V5KSkge1xuICAgICAgICAgICAgdmFyIG5hbWVfMSA9IHRoaXMuZ2V0QXR0cmlidXRlTmFtZUZvcktleShrZXkpO1xuICAgICAgICAgICAgdGhpcy5lbGVtZW50LnJlbW92ZUF0dHJpYnV0ZShuYW1lXzEpO1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIERhdGFNYXAucHJvdG90eXBlLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkgPSBmdW5jdGlvbiAoa2V5KSB7XG4gICAgICAgIHJldHVybiBcImRhdGEtXCIgKyB0aGlzLmlkZW50aWZpZXIgKyBcIi1cIiArIGRhc2hlcml6ZShrZXkpO1xuICAgIH07XG4gICAgcmV0dXJuIERhdGFNYXA7XG59KCkpO1xuZXhwb3J0IHsgRGF0YU1hcCB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9ZGF0YV9tYXAuanMubWFwIiwiaW1wb3J0IHsgYmxlc3MgfSBmcm9tIFwiLi9ibGVzc2luZ1wiO1xuLyoqIEBoaWRkZW4gKi9cbmV4cG9ydCBmdW5jdGlvbiBibGVzc0RlZmluaXRpb24oZGVmaW5pdGlvbikge1xuICAgIHJldHVybiB7XG4gICAgICAgIGlkZW50aWZpZXI6IGRlZmluaXRpb24uaWRlbnRpZmllcixcbiAgICAgICAgY29udHJvbGxlckNvbnN0cnVjdG9yOiBibGVzcyhkZWZpbml0aW9uLmNvbnRyb2xsZXJDb25zdHJ1Y3RvcilcbiAgICB9O1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9ZGVmaW5pdGlvbi5qcy5tYXAiLCJpbXBvcnQgeyBFdmVudExpc3RlbmVyIH0gZnJvbSBcIi4vZXZlbnRfbGlzdGVuZXJcIjtcbnZhciBEaXNwYXRjaGVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIERpc3BhdGNoZXIoYXBwbGljYXRpb24pIHtcbiAgICAgICAgdGhpcy5hcHBsaWNhdGlvbiA9IGFwcGxpY2F0aW9uO1xuICAgICAgICB0aGlzLmV2ZW50TGlzdGVuZXJNYXBzID0gbmV3IE1hcDtcbiAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgfVxuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAoIXRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gdHJ1ZTtcbiAgICAgICAgICAgIHRoaXMuZXZlbnRMaXN0ZW5lcnMuZm9yRWFjaChmdW5jdGlvbiAoZXZlbnRMaXN0ZW5lcikgeyByZXR1cm4gZXZlbnRMaXN0ZW5lci5jb25uZWN0KCk7IH0pO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgICAgIHRoaXMuZXZlbnRMaXN0ZW5lcnMuZm9yRWFjaChmdW5jdGlvbiAoZXZlbnRMaXN0ZW5lcikgeyByZXR1cm4gZXZlbnRMaXN0ZW5lci5kaXNjb25uZWN0KCk7IH0pO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoRGlzcGF0Y2hlci5wcm90b3R5cGUsIFwiZXZlbnRMaXN0ZW5lcnNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMuZXZlbnRMaXN0ZW5lck1hcHMudmFsdWVzKCkpXG4gICAgICAgICAgICAgICAgLnJlZHVjZShmdW5jdGlvbiAobGlzdGVuZXJzLCBtYXApIHsgcmV0dXJuIGxpc3RlbmVycy5jb25jYXQoQXJyYXkuZnJvbShtYXAudmFsdWVzKCkpKTsgfSwgW10pO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgLy8gQmluZGluZyBvYnNlcnZlciBkZWxlZ2F0ZVxuICAgIC8qKiBAaGlkZGVuICovXG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuYmluZGluZ0Nvbm5lY3RlZCA9IGZ1bmN0aW9uIChiaW5kaW5nKSB7XG4gICAgICAgIHRoaXMuZmV0Y2hFdmVudExpc3RlbmVyRm9yQmluZGluZyhiaW5kaW5nKS5iaW5kaW5nQ29ubmVjdGVkKGJpbmRpbmcpO1xuICAgIH07XG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5iaW5kaW5nRGlzY29ubmVjdGVkID0gZnVuY3Rpb24gKGJpbmRpbmcpIHtcbiAgICAgICAgdGhpcy5mZXRjaEV2ZW50TGlzdGVuZXJGb3JCaW5kaW5nKGJpbmRpbmcpLmJpbmRpbmdEaXNjb25uZWN0ZWQoYmluZGluZyk7XG4gICAgfTtcbiAgICAvLyBFcnJvciBoYW5kbGluZ1xuICAgIERpc3BhdGNoZXIucHJvdG90eXBlLmhhbmRsZUVycm9yID0gZnVuY3Rpb24gKGVycm9yLCBtZXNzYWdlLCBkZXRhaWwpIHtcbiAgICAgICAgaWYgKGRldGFpbCA9PT0gdm9pZCAwKSB7IGRldGFpbCA9IHt9OyB9XG4gICAgICAgIHRoaXMuYXBwbGljYXRpb24uaGFuZGxlRXJyb3IoZXJyb3IsIFwiRXJyb3IgXCIgKyBtZXNzYWdlLCBkZXRhaWwpO1xuICAgIH07XG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuZmV0Y2hFdmVudExpc3RlbmVyRm9yQmluZGluZyA9IGZ1bmN0aW9uIChiaW5kaW5nKSB7XG4gICAgICAgIHZhciBldmVudFRhcmdldCA9IGJpbmRpbmcuZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSA9IGJpbmRpbmcuZXZlbnROYW1lLCBldmVudE9wdGlvbnMgPSBiaW5kaW5nLmV2ZW50T3B0aW9ucztcbiAgICAgICAgcmV0dXJuIHRoaXMuZmV0Y2hFdmVudExpc3RlbmVyKGV2ZW50VGFyZ2V0LCBldmVudE5hbWUsIGV2ZW50T3B0aW9ucyk7XG4gICAgfTtcbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5mZXRjaEV2ZW50TGlzdGVuZXIgPSBmdW5jdGlvbiAoZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKSB7XG4gICAgICAgIHZhciBldmVudExpc3RlbmVyTWFwID0gdGhpcy5mZXRjaEV2ZW50TGlzdGVuZXJNYXBGb3JFdmVudFRhcmdldChldmVudFRhcmdldCk7XG4gICAgICAgIHZhciBjYWNoZUtleSA9IHRoaXMuY2FjaGVLZXkoZXZlbnROYW1lLCBldmVudE9wdGlvbnMpO1xuICAgICAgICB2YXIgZXZlbnRMaXN0ZW5lciA9IGV2ZW50TGlzdGVuZXJNYXAuZ2V0KGNhY2hlS2V5KTtcbiAgICAgICAgaWYgKCFldmVudExpc3RlbmVyKSB7XG4gICAgICAgICAgICBldmVudExpc3RlbmVyID0gdGhpcy5jcmVhdGVFdmVudExpc3RlbmVyKGV2ZW50VGFyZ2V0LCBldmVudE5hbWUsIGV2ZW50T3B0aW9ucyk7XG4gICAgICAgICAgICBldmVudExpc3RlbmVyTWFwLnNldChjYWNoZUtleSwgZXZlbnRMaXN0ZW5lcik7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGV2ZW50TGlzdGVuZXI7XG4gICAgfTtcbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5jcmVhdGVFdmVudExpc3RlbmVyID0gZnVuY3Rpb24gKGV2ZW50VGFyZ2V0LCBldmVudE5hbWUsIGV2ZW50T3B0aW9ucykge1xuICAgICAgICB2YXIgZXZlbnRMaXN0ZW5lciA9IG5ldyBFdmVudExpc3RlbmVyKGV2ZW50VGFyZ2V0LCBldmVudE5hbWUsIGV2ZW50T3B0aW9ucyk7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGV2ZW50TGlzdGVuZXIuY29ubmVjdCgpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBldmVudExpc3RlbmVyO1xuICAgIH07XG4gICAgRGlzcGF0Y2hlci5wcm90b3R5cGUuZmV0Y2hFdmVudExpc3RlbmVyTWFwRm9yRXZlbnRUYXJnZXQgPSBmdW5jdGlvbiAoZXZlbnRUYXJnZXQpIHtcbiAgICAgICAgdmFyIGV2ZW50TGlzdGVuZXJNYXAgPSB0aGlzLmV2ZW50TGlzdGVuZXJNYXBzLmdldChldmVudFRhcmdldCk7XG4gICAgICAgIGlmICghZXZlbnRMaXN0ZW5lck1hcCkge1xuICAgICAgICAgICAgZXZlbnRMaXN0ZW5lck1hcCA9IG5ldyBNYXA7XG4gICAgICAgICAgICB0aGlzLmV2ZW50TGlzdGVuZXJNYXBzLnNldChldmVudFRhcmdldCwgZXZlbnRMaXN0ZW5lck1hcCk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGV2ZW50TGlzdGVuZXJNYXA7XG4gICAgfTtcbiAgICBEaXNwYXRjaGVyLnByb3RvdHlwZS5jYWNoZUtleSA9IGZ1bmN0aW9uIChldmVudE5hbWUsIGV2ZW50T3B0aW9ucykge1xuICAgICAgICB2YXIgcGFydHMgPSBbZXZlbnROYW1lXTtcbiAgICAgICAgT2JqZWN0LmtleXMoZXZlbnRPcHRpb25zKS5zb3J0KCkuZm9yRWFjaChmdW5jdGlvbiAoa2V5KSB7XG4gICAgICAgICAgICBwYXJ0cy5wdXNoKFwiXCIgKyAoZXZlbnRPcHRpb25zW2tleV0gPyBcIlwiIDogXCIhXCIpICsga2V5KTtcbiAgICAgICAgfSk7XG4gICAgICAgIHJldHVybiBwYXJ0cy5qb2luKFwiOlwiKTtcbiAgICB9O1xuICAgIHJldHVybiBEaXNwYXRjaGVyO1xufSgpKTtcbmV4cG9ydCB7IERpc3BhdGNoZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWRpc3BhdGNoZXIuanMubWFwIiwidmFyIEV2ZW50TGlzdGVuZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gRXZlbnRMaXN0ZW5lcihldmVudFRhcmdldCwgZXZlbnROYW1lLCBldmVudE9wdGlvbnMpIHtcbiAgICAgICAgdGhpcy5ldmVudFRhcmdldCA9IGV2ZW50VGFyZ2V0O1xuICAgICAgICB0aGlzLmV2ZW50TmFtZSA9IGV2ZW50TmFtZTtcbiAgICAgICAgdGhpcy5ldmVudE9wdGlvbnMgPSBldmVudE9wdGlvbnM7XG4gICAgICAgIHRoaXMudW5vcmRlcmVkQmluZGluZ3MgPSBuZXcgU2V0KCk7XG4gICAgfVxuICAgIEV2ZW50TGlzdGVuZXIucHJvdG90eXBlLmNvbm5lY3QgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuZXZlbnRUYXJnZXQuYWRkRXZlbnRMaXN0ZW5lcih0aGlzLmV2ZW50TmFtZSwgdGhpcywgdGhpcy5ldmVudE9wdGlvbnMpO1xuICAgIH07XG4gICAgRXZlbnRMaXN0ZW5lci5wcm90b3R5cGUuZGlzY29ubmVjdCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5ldmVudFRhcmdldC5yZW1vdmVFdmVudExpc3RlbmVyKHRoaXMuZXZlbnROYW1lLCB0aGlzLCB0aGlzLmV2ZW50T3B0aW9ucyk7XG4gICAgfTtcbiAgICAvLyBCaW5kaW5nIG9ic2VydmVyIGRlbGVnYXRlXG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBFdmVudExpc3RlbmVyLnByb3RvdHlwZS5iaW5kaW5nQ29ubmVjdGVkID0gZnVuY3Rpb24gKGJpbmRpbmcpIHtcbiAgICAgICAgdGhpcy51bm9yZGVyZWRCaW5kaW5ncy5hZGQoYmluZGluZyk7XG4gICAgfTtcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIEV2ZW50TGlzdGVuZXIucHJvdG90eXBlLmJpbmRpbmdEaXNjb25uZWN0ZWQgPSBmdW5jdGlvbiAoYmluZGluZykge1xuICAgICAgICB0aGlzLnVub3JkZXJlZEJpbmRpbmdzLmRlbGV0ZShiaW5kaW5nKTtcbiAgICB9O1xuICAgIEV2ZW50TGlzdGVuZXIucHJvdG90eXBlLmhhbmRsZUV2ZW50ID0gZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgIHZhciBleHRlbmRlZEV2ZW50ID0gZXh0ZW5kRXZlbnQoZXZlbnQpO1xuICAgICAgICBmb3IgKHZhciBfaSA9IDAsIF9hID0gdGhpcy5iaW5kaW5nczsgX2kgPCBfYS5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgIHZhciBiaW5kaW5nID0gX2FbX2ldO1xuICAgICAgICAgICAgaWYgKGV4dGVuZGVkRXZlbnQuaW1tZWRpYXRlUHJvcGFnYXRpb25TdG9wcGVkKSB7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICBiaW5kaW5nLmhhbmRsZUV2ZW50KGV4dGVuZGVkRXZlbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoRXZlbnRMaXN0ZW5lci5wcm90b3R5cGUsIFwiYmluZGluZ3NcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMudW5vcmRlcmVkQmluZGluZ3MpLnNvcnQoZnVuY3Rpb24gKGxlZnQsIHJpZ2h0KSB7XG4gICAgICAgICAgICAgICAgdmFyIGxlZnRJbmRleCA9IGxlZnQuaW5kZXgsIHJpZ2h0SW5kZXggPSByaWdodC5pbmRleDtcbiAgICAgICAgICAgICAgICByZXR1cm4gbGVmdEluZGV4IDwgcmlnaHRJbmRleCA/IC0xIDogbGVmdEluZGV4ID4gcmlnaHRJbmRleCA/IDEgOiAwO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICByZXR1cm4gRXZlbnRMaXN0ZW5lcjtcbn0oKSk7XG5leHBvcnQgeyBFdmVudExpc3RlbmVyIH07XG5mdW5jdGlvbiBleHRlbmRFdmVudChldmVudCkge1xuICAgIGlmIChcImltbWVkaWF0ZVByb3BhZ2F0aW9uU3RvcHBlZFwiIGluIGV2ZW50KSB7XG4gICAgICAgIHJldHVybiBldmVudDtcbiAgICB9XG4gICAgZWxzZSB7XG4gICAgICAgIHZhciBzdG9wSW1tZWRpYXRlUHJvcGFnYXRpb25fMSA9IGV2ZW50LnN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbjtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24oZXZlbnQsIHtcbiAgICAgICAgICAgIGltbWVkaWF0ZVByb3BhZ2F0aW9uU3RvcHBlZDogZmFsc2UsXG4gICAgICAgICAgICBzdG9wSW1tZWRpYXRlUHJvcGFnYXRpb246IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmltbWVkaWF0ZVByb3BhZ2F0aW9uU3RvcHBlZCA9IHRydWU7XG4gICAgICAgICAgICAgICAgc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uXzEuY2FsbCh0aGlzKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgfVxufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9ZXZlbnRfbGlzdGVuZXIuanMubWFwIiwidmFyIEd1aWRlID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIEd1aWRlKGxvZ2dlcikge1xuICAgICAgICB0aGlzLndhcm5lZEtleXNCeU9iamVjdCA9IG5ldyBXZWFrTWFwO1xuICAgICAgICB0aGlzLmxvZ2dlciA9IGxvZ2dlcjtcbiAgICB9XG4gICAgR3VpZGUucHJvdG90eXBlLndhcm4gPSBmdW5jdGlvbiAob2JqZWN0LCBrZXksIG1lc3NhZ2UpIHtcbiAgICAgICAgdmFyIHdhcm5lZEtleXMgPSB0aGlzLndhcm5lZEtleXNCeU9iamVjdC5nZXQob2JqZWN0KTtcbiAgICAgICAgaWYgKCF3YXJuZWRLZXlzKSB7XG4gICAgICAgICAgICB3YXJuZWRLZXlzID0gbmV3IFNldDtcbiAgICAgICAgICAgIHRoaXMud2FybmVkS2V5c0J5T2JqZWN0LnNldChvYmplY3QsIHdhcm5lZEtleXMpO1xuICAgICAgICB9XG4gICAgICAgIGlmICghd2FybmVkS2V5cy5oYXMoa2V5KSkge1xuICAgICAgICAgICAgd2FybmVkS2V5cy5hZGQoa2V5KTtcbiAgICAgICAgICAgIHRoaXMubG9nZ2VyLndhcm4obWVzc2FnZSwgb2JqZWN0KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgcmV0dXJuIEd1aWRlO1xufSgpKTtcbmV4cG9ydCB7IEd1aWRlIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1ndWlkZS5qcy5tYXAiLCJleHBvcnQgeyBBcHBsaWNhdGlvbiB9IGZyb20gXCIuL2FwcGxpY2F0aW9uXCI7XG5leHBvcnQgeyBDb250ZXh0IH0gZnJvbSBcIi4vY29udGV4dFwiO1xuZXhwb3J0IHsgQ29udHJvbGxlciB9IGZyb20gXCIuL2NvbnRyb2xsZXJcIjtcbmV4cG9ydCB7IGRlZmF1bHRTY2hlbWEgfSBmcm9tIFwiLi9zY2hlbWFcIjtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPWluZGV4LmpzLm1hcCIsImV4cG9ydCBmdW5jdGlvbiByZWFkSW5oZXJpdGFibGVTdGF0aWNBcnJheVZhbHVlcyhjb25zdHJ1Y3RvciwgcHJvcGVydHlOYW1lKSB7XG4gICAgdmFyIGFuY2VzdG9ycyA9IGdldEFuY2VzdG9yc0ZvckNvbnN0cnVjdG9yKGNvbnN0cnVjdG9yKTtcbiAgICByZXR1cm4gQXJyYXkuZnJvbShhbmNlc3RvcnMucmVkdWNlKGZ1bmN0aW9uICh2YWx1ZXMsIGNvbnN0cnVjdG9yKSB7XG4gICAgICAgIGdldE93blN0YXRpY0FycmF5VmFsdWVzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpLmZvckVhY2goZnVuY3Rpb24gKG5hbWUpIHsgcmV0dXJuIHZhbHVlcy5hZGQobmFtZSk7IH0pO1xuICAgICAgICByZXR1cm4gdmFsdWVzO1xuICAgIH0sIG5ldyBTZXQpKTtcbn1cbmV4cG9ydCBmdW5jdGlvbiByZWFkSW5oZXJpdGFibGVTdGF0aWNPYmplY3RQYWlycyhjb25zdHJ1Y3RvciwgcHJvcGVydHlOYW1lKSB7XG4gICAgdmFyIGFuY2VzdG9ycyA9IGdldEFuY2VzdG9yc0ZvckNvbnN0cnVjdG9yKGNvbnN0cnVjdG9yKTtcbiAgICByZXR1cm4gYW5jZXN0b3JzLnJlZHVjZShmdW5jdGlvbiAocGFpcnMsIGNvbnN0cnVjdG9yKSB7XG4gICAgICAgIHBhaXJzLnB1c2guYXBwbHkocGFpcnMsIGdldE93blN0YXRpY09iamVjdFBhaXJzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpKTtcbiAgICAgICAgcmV0dXJuIHBhaXJzO1xuICAgIH0sIFtdKTtcbn1cbmZ1bmN0aW9uIGdldEFuY2VzdG9yc0ZvckNvbnN0cnVjdG9yKGNvbnN0cnVjdG9yKSB7XG4gICAgdmFyIGFuY2VzdG9ycyA9IFtdO1xuICAgIHdoaWxlIChjb25zdHJ1Y3Rvcikge1xuICAgICAgICBhbmNlc3RvcnMucHVzaChjb25zdHJ1Y3Rvcik7XG4gICAgICAgIGNvbnN0cnVjdG9yID0gT2JqZWN0LmdldFByb3RvdHlwZU9mKGNvbnN0cnVjdG9yKTtcbiAgICB9XG4gICAgcmV0dXJuIGFuY2VzdG9ycy5yZXZlcnNlKCk7XG59XG5mdW5jdGlvbiBnZXRPd25TdGF0aWNBcnJheVZhbHVlcyhjb25zdHJ1Y3RvciwgcHJvcGVydHlOYW1lKSB7XG4gICAgdmFyIGRlZmluaXRpb24gPSBjb25zdHJ1Y3Rvcltwcm9wZXJ0eU5hbWVdO1xuICAgIHJldHVybiBBcnJheS5pc0FycmF5KGRlZmluaXRpb24pID8gZGVmaW5pdGlvbiA6IFtdO1xufVxuZnVuY3Rpb24gZ2V0T3duU3RhdGljT2JqZWN0UGFpcnMoY29uc3RydWN0b3IsIHByb3BlcnR5TmFtZSkge1xuICAgIHZhciBkZWZpbml0aW9uID0gY29uc3RydWN0b3JbcHJvcGVydHlOYW1lXTtcbiAgICByZXR1cm4gZGVmaW5pdGlvbiA/IE9iamVjdC5rZXlzKGRlZmluaXRpb24pLm1hcChmdW5jdGlvbiAoa2V5KSB7IHJldHVybiBba2V5LCBkZWZpbml0aW9uW2tleV1dOyB9KSA6IFtdO1xufVxuLy8jIHNvdXJjZU1hcHBpbmdVUkw9aW5oZXJpdGFibGVfc3RhdGljcy5qcy5tYXAiLCJpbXBvcnQgeyBDb250ZXh0IH0gZnJvbSBcIi4vY29udGV4dFwiO1xuaW1wb3J0IHsgYmxlc3NEZWZpbml0aW9uIH0gZnJvbSBcIi4vZGVmaW5pdGlvblwiO1xudmFyIE1vZHVsZSA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBNb2R1bGUoYXBwbGljYXRpb24sIGRlZmluaXRpb24pIHtcbiAgICAgICAgdGhpcy5hcHBsaWNhdGlvbiA9IGFwcGxpY2F0aW9uO1xuICAgICAgICB0aGlzLmRlZmluaXRpb24gPSBibGVzc0RlZmluaXRpb24oZGVmaW5pdGlvbik7XG4gICAgICAgIHRoaXMuY29udGV4dHNCeVNjb3BlID0gbmV3IFdlYWtNYXA7XG4gICAgICAgIHRoaXMuY29ubmVjdGVkQ29udGV4dHMgPSBuZXcgU2V0O1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoTW9kdWxlLnByb3RvdHlwZSwgXCJpZGVudGlmaWVyXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5kZWZpbml0aW9uLmlkZW50aWZpZXI7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoTW9kdWxlLnByb3RvdHlwZSwgXCJjb250cm9sbGVyQ29uc3RydWN0b3JcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmRlZmluaXRpb24uY29udHJvbGxlckNvbnN0cnVjdG9yO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KE1vZHVsZS5wcm90b3R5cGUsIFwiY29udGV4dHNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMuY29ubmVjdGVkQ29udGV4dHMpO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgTW9kdWxlLnByb3RvdHlwZS5jb25uZWN0Q29udGV4dEZvclNjb3BlID0gZnVuY3Rpb24gKHNjb3BlKSB7XG4gICAgICAgIHZhciBjb250ZXh0ID0gdGhpcy5mZXRjaENvbnRleHRGb3JTY29wZShzY29wZSk7XG4gICAgICAgIHRoaXMuY29ubmVjdGVkQ29udGV4dHMuYWRkKGNvbnRleHQpO1xuICAgICAgICBjb250ZXh0LmNvbm5lY3QoKTtcbiAgICB9O1xuICAgIE1vZHVsZS5wcm90b3R5cGUuZGlzY29ubmVjdENvbnRleHRGb3JTY29wZSA9IGZ1bmN0aW9uIChzY29wZSkge1xuICAgICAgICB2YXIgY29udGV4dCA9IHRoaXMuY29udGV4dHNCeVNjb3BlLmdldChzY29wZSk7XG4gICAgICAgIGlmIChjb250ZXh0KSB7XG4gICAgICAgICAgICB0aGlzLmNvbm5lY3RlZENvbnRleHRzLmRlbGV0ZShjb250ZXh0KTtcbiAgICAgICAgICAgIGNvbnRleHQuZGlzY29ubmVjdCgpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBNb2R1bGUucHJvdG90eXBlLmZldGNoQ29udGV4dEZvclNjb3BlID0gZnVuY3Rpb24gKHNjb3BlKSB7XG4gICAgICAgIHZhciBjb250ZXh0ID0gdGhpcy5jb250ZXh0c0J5U2NvcGUuZ2V0KHNjb3BlKTtcbiAgICAgICAgaWYgKCFjb250ZXh0KSB7XG4gICAgICAgICAgICBjb250ZXh0ID0gbmV3IENvbnRleHQodGhpcywgc2NvcGUpO1xuICAgICAgICAgICAgdGhpcy5jb250ZXh0c0J5U2NvcGUuc2V0KHNjb3BlLCBjb250ZXh0KTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gY29udGV4dDtcbiAgICB9O1xuICAgIHJldHVybiBNb2R1bGU7XG59KCkpO1xuZXhwb3J0IHsgTW9kdWxlIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1tb2R1bGUuanMubWFwIiwiaW1wb3J0IHsgTW9kdWxlIH0gZnJvbSBcIi4vbW9kdWxlXCI7XG5pbXBvcnQgeyBNdWx0aW1hcCB9IGZyb20gXCJAc3RpbXVsdXMvbXVsdGltYXBcIjtcbmltcG9ydCB7IFNjb3BlIH0gZnJvbSBcIi4vc2NvcGVcIjtcbmltcG9ydCB7IFNjb3BlT2JzZXJ2ZXIgfSBmcm9tIFwiLi9zY29wZV9vYnNlcnZlclwiO1xudmFyIFJvdXRlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBSb3V0ZXIoYXBwbGljYXRpb24pIHtcbiAgICAgICAgdGhpcy5hcHBsaWNhdGlvbiA9IGFwcGxpY2F0aW9uO1xuICAgICAgICB0aGlzLnNjb3BlT2JzZXJ2ZXIgPSBuZXcgU2NvcGVPYnNlcnZlcih0aGlzLmVsZW1lbnQsIHRoaXMuc2NoZW1hLCB0aGlzKTtcbiAgICAgICAgdGhpcy5zY29wZXNCeUlkZW50aWZpZXIgPSBuZXcgTXVsdGltYXA7XG4gICAgICAgIHRoaXMubW9kdWxlc0J5SWRlbnRpZmllciA9IG5ldyBNYXA7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShSb3V0ZXIucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFwcGxpY2F0aW9uLmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoUm91dGVyLnByb3RvdHlwZSwgXCJzY2hlbWFcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmFwcGxpY2F0aW9uLnNjaGVtYTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShSb3V0ZXIucHJvdG90eXBlLCBcImxvZ2dlclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuYXBwbGljYXRpb24ubG9nZ2VyO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFJvdXRlci5wcm90b3R5cGUsIFwiY29udHJvbGxlckF0dHJpYnV0ZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NoZW1hLmNvbnRyb2xsZXJBdHRyaWJ1dGU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoUm91dGVyLnByb3RvdHlwZSwgXCJtb2R1bGVzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIudmFsdWVzKCkpO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFJvdXRlci5wcm90b3R5cGUsIFwiY29udGV4dHNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLm1vZHVsZXMucmVkdWNlKGZ1bmN0aW9uIChjb250ZXh0cywgbW9kdWxlKSB7IHJldHVybiBjb250ZXh0cy5jb25jYXQobW9kdWxlLmNvbnRleHRzKTsgfSwgW10pO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgUm91dGVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5zY29wZU9ic2VydmVyLnN0YXJ0KCk7XG4gICAgfTtcbiAgICBSb3V0ZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuc2NvcGVPYnNlcnZlci5zdG9wKCk7XG4gICAgfTtcbiAgICBSb3V0ZXIucHJvdG90eXBlLmxvYWREZWZpbml0aW9uID0gZnVuY3Rpb24gKGRlZmluaXRpb24pIHtcbiAgICAgICAgdGhpcy51bmxvYWRJZGVudGlmaWVyKGRlZmluaXRpb24uaWRlbnRpZmllcik7XG4gICAgICAgIHZhciBtb2R1bGUgPSBuZXcgTW9kdWxlKHRoaXMuYXBwbGljYXRpb24sIGRlZmluaXRpb24pO1xuICAgICAgICB0aGlzLmNvbm5lY3RNb2R1bGUobW9kdWxlKTtcbiAgICB9O1xuICAgIFJvdXRlci5wcm90b3R5cGUudW5sb2FkSWRlbnRpZmllciA9IGZ1bmN0aW9uIChpZGVudGlmaWVyKSB7XG4gICAgICAgIHZhciBtb2R1bGUgPSB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIuZ2V0KGlkZW50aWZpZXIpO1xuICAgICAgICBpZiAobW9kdWxlKSB7XG4gICAgICAgICAgICB0aGlzLmRpc2Nvbm5lY3RNb2R1bGUobW9kdWxlKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgUm91dGVyLnByb3RvdHlwZS5nZXRDb250ZXh0Rm9yRWxlbWVudEFuZElkZW50aWZpZXIgPSBmdW5jdGlvbiAoZWxlbWVudCwgaWRlbnRpZmllcikge1xuICAgICAgICB2YXIgbW9kdWxlID0gdGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyLmdldChpZGVudGlmaWVyKTtcbiAgICAgICAgaWYgKG1vZHVsZSkge1xuICAgICAgICAgICAgcmV0dXJuIG1vZHVsZS5jb250ZXh0cy5maW5kKGZ1bmN0aW9uIChjb250ZXh0KSB7IHJldHVybiBjb250ZXh0LmVsZW1lbnQgPT0gZWxlbWVudDsgfSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIC8vIEVycm9yIGhhbmRsZXIgZGVsZWdhdGVcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIFJvdXRlci5wcm90b3R5cGUuaGFuZGxlRXJyb3IgPSBmdW5jdGlvbiAoZXJyb3IsIG1lc3NhZ2UsIGRldGFpbCkge1xuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uLmhhbmRsZUVycm9yKGVycm9yLCBtZXNzYWdlLCBkZXRhaWwpO1xuICAgIH07XG4gICAgLy8gU2NvcGUgb2JzZXJ2ZXIgZGVsZWdhdGVcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIFJvdXRlci5wcm90b3R5cGUuY3JlYXRlU2NvcGVGb3JFbGVtZW50QW5kSWRlbnRpZmllciA9IGZ1bmN0aW9uIChlbGVtZW50LCBpZGVudGlmaWVyKSB7XG4gICAgICAgIHJldHVybiBuZXcgU2NvcGUodGhpcy5zY2hlbWEsIGVsZW1lbnQsIGlkZW50aWZpZXIsIHRoaXMubG9nZ2VyKTtcbiAgICB9O1xuICAgIC8qKiBAaGlkZGVuICovXG4gICAgUm91dGVyLnByb3RvdHlwZS5zY29wZUNvbm5lY3RlZCA9IGZ1bmN0aW9uIChzY29wZSkge1xuICAgICAgICB0aGlzLnNjb3Blc0J5SWRlbnRpZmllci5hZGQoc2NvcGUuaWRlbnRpZmllciwgc2NvcGUpO1xuICAgICAgICB2YXIgbW9kdWxlID0gdGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyLmdldChzY29wZS5pZGVudGlmaWVyKTtcbiAgICAgICAgaWYgKG1vZHVsZSkge1xuICAgICAgICAgICAgbW9kdWxlLmNvbm5lY3RDb250ZXh0Rm9yU2NvcGUoc2NvcGUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIFJvdXRlci5wcm90b3R5cGUuc2NvcGVEaXNjb25uZWN0ZWQgPSBmdW5jdGlvbiAoc2NvcGUpIHtcbiAgICAgICAgdGhpcy5zY29wZXNCeUlkZW50aWZpZXIuZGVsZXRlKHNjb3BlLmlkZW50aWZpZXIsIHNjb3BlKTtcbiAgICAgICAgdmFyIG1vZHVsZSA9IHRoaXMubW9kdWxlc0J5SWRlbnRpZmllci5nZXQoc2NvcGUuaWRlbnRpZmllcik7XG4gICAgICAgIGlmIChtb2R1bGUpIHtcbiAgICAgICAgICAgIG1vZHVsZS5kaXNjb25uZWN0Q29udGV4dEZvclNjb3BlKHNjb3BlKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgLy8gTW9kdWxlc1xuICAgIFJvdXRlci5wcm90b3R5cGUuY29ubmVjdE1vZHVsZSA9IGZ1bmN0aW9uIChtb2R1bGUpIHtcbiAgICAgICAgdGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyLnNldChtb2R1bGUuaWRlbnRpZmllciwgbW9kdWxlKTtcbiAgICAgICAgdmFyIHNjb3BlcyA9IHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyLmdldFZhbHVlc0ZvcktleShtb2R1bGUuaWRlbnRpZmllcik7XG4gICAgICAgIHNjb3Blcy5mb3JFYWNoKGZ1bmN0aW9uIChzY29wZSkgeyByZXR1cm4gbW9kdWxlLmNvbm5lY3RDb250ZXh0Rm9yU2NvcGUoc2NvcGUpOyB9KTtcbiAgICB9O1xuICAgIFJvdXRlci5wcm90b3R5cGUuZGlzY29ubmVjdE1vZHVsZSA9IGZ1bmN0aW9uIChtb2R1bGUpIHtcbiAgICAgICAgdGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyLmRlbGV0ZShtb2R1bGUuaWRlbnRpZmllcik7XG4gICAgICAgIHZhciBzY29wZXMgPSB0aGlzLnNjb3Blc0J5SWRlbnRpZmllci5nZXRWYWx1ZXNGb3JLZXkobW9kdWxlLmlkZW50aWZpZXIpO1xuICAgICAgICBzY29wZXMuZm9yRWFjaChmdW5jdGlvbiAoc2NvcGUpIHsgcmV0dXJuIG1vZHVsZS5kaXNjb25uZWN0Q29udGV4dEZvclNjb3BlKHNjb3BlKTsgfSk7XG4gICAgfTtcbiAgICByZXR1cm4gUm91dGVyO1xufSgpKTtcbmV4cG9ydCB7IFJvdXRlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9cm91dGVyLmpzLm1hcCIsImV4cG9ydCB2YXIgZGVmYXVsdFNjaGVtYSA9IHtcbiAgICBjb250cm9sbGVyQXR0cmlidXRlOiBcImRhdGEtY29udHJvbGxlclwiLFxuICAgIGFjdGlvbkF0dHJpYnV0ZTogXCJkYXRhLWFjdGlvblwiLFxuICAgIHRhcmdldEF0dHJpYnV0ZTogXCJkYXRhLXRhcmdldFwiXG59O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9c2NoZW1hLmpzLm1hcCIsInZhciBfX3NwcmVhZEFycmF5cyA9ICh0aGlzICYmIHRoaXMuX19zcHJlYWRBcnJheXMpIHx8IGZ1bmN0aW9uICgpIHtcbiAgICBmb3IgKHZhciBzID0gMCwgaSA9IDAsIGlsID0gYXJndW1lbnRzLmxlbmd0aDsgaSA8IGlsOyBpKyspIHMgKz0gYXJndW1lbnRzW2ldLmxlbmd0aDtcbiAgICBmb3IgKHZhciByID0gQXJyYXkocyksIGsgPSAwLCBpID0gMDsgaSA8IGlsOyBpKyspXG4gICAgICAgIGZvciAodmFyIGEgPSBhcmd1bWVudHNbaV0sIGogPSAwLCBqbCA9IGEubGVuZ3RoOyBqIDwgamw7IGorKywgaysrKVxuICAgICAgICAgICAgcltrXSA9IGFbal07XG4gICAgcmV0dXJuIHI7XG59O1xuaW1wb3J0IHsgQ2xhc3NNYXAgfSBmcm9tIFwiLi9jbGFzc19tYXBcIjtcbmltcG9ydCB7IERhdGFNYXAgfSBmcm9tIFwiLi9kYXRhX21hcFwiO1xuaW1wb3J0IHsgR3VpZGUgfSBmcm9tIFwiLi9ndWlkZVwiO1xuaW1wb3J0IHsgYXR0cmlidXRlVmFsdWVDb250YWluc1Rva2VuIH0gZnJvbSBcIi4vc2VsZWN0b3JzXCI7XG5pbXBvcnQgeyBUYXJnZXRTZXQgfSBmcm9tIFwiLi90YXJnZXRfc2V0XCI7XG52YXIgU2NvcGUgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gU2NvcGUoc2NoZW1hLCBlbGVtZW50LCBpZGVudGlmaWVyLCBsb2dnZXIpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdGhpcy50YXJnZXRzID0gbmV3IFRhcmdldFNldCh0aGlzKTtcbiAgICAgICAgdGhpcy5jbGFzc2VzID0gbmV3IENsYXNzTWFwKHRoaXMpO1xuICAgICAgICB0aGlzLmRhdGEgPSBuZXcgRGF0YU1hcCh0aGlzKTtcbiAgICAgICAgdGhpcy5jb250YWluc0VsZW1lbnQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICAgICAgcmV0dXJuIGVsZW1lbnQuY2xvc2VzdChfdGhpcy5jb250cm9sbGVyU2VsZWN0b3IpID09PSBfdGhpcy5lbGVtZW50O1xuICAgICAgICB9O1xuICAgICAgICB0aGlzLnNjaGVtYSA9IHNjaGVtYTtcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5pZGVudGlmaWVyID0gaWRlbnRpZmllcjtcbiAgICAgICAgdGhpcy5ndWlkZSA9IG5ldyBHdWlkZShsb2dnZXIpO1xuICAgIH1cbiAgICBTY29wZS5wcm90b3R5cGUuZmluZEVsZW1lbnQgPSBmdW5jdGlvbiAoc2VsZWN0b3IpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5tYXRjaGVzKHNlbGVjdG9yKVxuICAgICAgICAgICAgPyB0aGlzLmVsZW1lbnRcbiAgICAgICAgICAgIDogdGhpcy5xdWVyeUVsZW1lbnRzKHNlbGVjdG9yKS5maW5kKHRoaXMuY29udGFpbnNFbGVtZW50KTtcbiAgICB9O1xuICAgIFNjb3BlLnByb3RvdHlwZS5maW5kQWxsRWxlbWVudHMgPSBmdW5jdGlvbiAoc2VsZWN0b3IpIHtcbiAgICAgICAgcmV0dXJuIF9fc3ByZWFkQXJyYXlzKHRoaXMuZWxlbWVudC5tYXRjaGVzKHNlbGVjdG9yKSA/IFt0aGlzLmVsZW1lbnRdIDogW10sIHRoaXMucXVlcnlFbGVtZW50cyhzZWxlY3RvcikuZmlsdGVyKHRoaXMuY29udGFpbnNFbGVtZW50KSk7XG4gICAgfTtcbiAgICBTY29wZS5wcm90b3R5cGUucXVlcnlFbGVtZW50cyA9IGZ1bmN0aW9uIChzZWxlY3Rvcikge1xuICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLmVsZW1lbnQucXVlcnlTZWxlY3RvckFsbChzZWxlY3RvcikpO1xuICAgIH07XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFNjb3BlLnByb3RvdHlwZSwgXCJjb250cm9sbGVyU2VsZWN0b3JcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBhdHRyaWJ1dGVWYWx1ZUNvbnRhaW5zVG9rZW4odGhpcy5zY2hlbWEuY29udHJvbGxlckF0dHJpYnV0ZSwgdGhpcy5pZGVudGlmaWVyKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIHJldHVybiBTY29wZTtcbn0oKSk7XG5leHBvcnQgeyBTY29wZSB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9c2NvcGUuanMubWFwIiwiaW1wb3J0IHsgVmFsdWVMaXN0T2JzZXJ2ZXIgfSBmcm9tIFwiQHN0aW11bHVzL211dGF0aW9uLW9ic2VydmVyc1wiO1xudmFyIFNjb3BlT2JzZXJ2ZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gU2NvcGVPYnNlcnZlcihlbGVtZW50LCBzY2hlbWEsIGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuc2NoZW1hID0gc2NoZW1hO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIgPSBuZXcgVmFsdWVMaXN0T2JzZXJ2ZXIodGhpcy5lbGVtZW50LCB0aGlzLmNvbnRyb2xsZXJBdHRyaWJ1dGUsIHRoaXMpO1xuICAgICAgICB0aGlzLnNjb3Blc0J5SWRlbnRpZmllckJ5RWxlbWVudCA9IG5ldyBXZWFrTWFwO1xuICAgICAgICB0aGlzLnNjb3BlUmVmZXJlbmNlQ291bnRzID0gbmV3IFdlYWtNYXA7XG4gICAgfVxuICAgIFNjb3BlT2JzZXJ2ZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnZhbHVlTGlzdE9ic2VydmVyLnN0YXJ0KCk7XG4gICAgfTtcbiAgICBTY29wZU9ic2VydmVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnZhbHVlTGlzdE9ic2VydmVyLnN0b3AoKTtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShTY29wZU9ic2VydmVyLnByb3RvdHlwZSwgXCJjb250cm9sbGVyQXR0cmlidXRlXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY2hlbWEuY29udHJvbGxlckF0dHJpYnV0ZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIC8vIFZhbHVlIG9ic2VydmVyIGRlbGVnYXRlXG4gICAgLyoqIEBoaWRkZW4gKi9cbiAgICBTY29wZU9ic2VydmVyLnByb3RvdHlwZS5wYXJzZVZhbHVlRm9yVG9rZW4gPSBmdW5jdGlvbiAodG9rZW4pIHtcbiAgICAgICAgdmFyIGVsZW1lbnQgPSB0b2tlbi5lbGVtZW50LCBpZGVudGlmaWVyID0gdG9rZW4uY29udGVudDtcbiAgICAgICAgdmFyIHNjb3Blc0J5SWRlbnRpZmllciA9IHRoaXMuZmV0Y2hTY29wZXNCeUlkZW50aWZpZXJGb3JFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICB2YXIgc2NvcGUgPSBzY29wZXNCeUlkZW50aWZpZXIuZ2V0KGlkZW50aWZpZXIpO1xuICAgICAgICBpZiAoIXNjb3BlKSB7XG4gICAgICAgICAgICBzY29wZSA9IHRoaXMuZGVsZWdhdGUuY3JlYXRlU2NvcGVGb3JFbGVtZW50QW5kSWRlbnRpZmllcihlbGVtZW50LCBpZGVudGlmaWVyKTtcbiAgICAgICAgICAgIHNjb3Blc0J5SWRlbnRpZmllci5zZXQoaWRlbnRpZmllciwgc2NvcGUpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBzY29wZTtcbiAgICB9O1xuICAgIC8qKiBAaGlkZGVuICovXG4gICAgU2NvcGVPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudE1hdGNoZWRWYWx1ZSA9IGZ1bmN0aW9uIChlbGVtZW50LCB2YWx1ZSkge1xuICAgICAgICB2YXIgcmVmZXJlbmNlQ291bnQgPSAodGhpcy5zY29wZVJlZmVyZW5jZUNvdW50cy5nZXQodmFsdWUpIHx8IDApICsgMTtcbiAgICAgICAgdGhpcy5zY29wZVJlZmVyZW5jZUNvdW50cy5zZXQodmFsdWUsIHJlZmVyZW5jZUNvdW50KTtcbiAgICAgICAgaWYgKHJlZmVyZW5jZUNvdW50ID09IDEpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuc2NvcGVDb25uZWN0ZWQodmFsdWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICAvKiogQGhpZGRlbiAqL1xuICAgIFNjb3BlT2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRVbm1hdGNoZWRWYWx1ZSA9IGZ1bmN0aW9uIChlbGVtZW50LCB2YWx1ZSkge1xuICAgICAgICB2YXIgcmVmZXJlbmNlQ291bnQgPSB0aGlzLnNjb3BlUmVmZXJlbmNlQ291bnRzLmdldCh2YWx1ZSk7XG4gICAgICAgIGlmIChyZWZlcmVuY2VDb3VudCkge1xuICAgICAgICAgICAgdGhpcy5zY29wZVJlZmVyZW5jZUNvdW50cy5zZXQodmFsdWUsIHJlZmVyZW5jZUNvdW50IC0gMSk7XG4gICAgICAgICAgICBpZiAocmVmZXJlbmNlQ291bnQgPT0gMSkge1xuICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuc2NvcGVEaXNjb25uZWN0ZWQodmFsdWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICBTY29wZU9ic2VydmVyLnByb3RvdHlwZS5mZXRjaFNjb3Blc0J5SWRlbnRpZmllckZvckVsZW1lbnQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICB2YXIgc2NvcGVzQnlJZGVudGlmaWVyID0gdGhpcy5zY29wZXNCeUlkZW50aWZpZXJCeUVsZW1lbnQuZ2V0KGVsZW1lbnQpO1xuICAgICAgICBpZiAoIXNjb3Blc0J5SWRlbnRpZmllcikge1xuICAgICAgICAgICAgc2NvcGVzQnlJZGVudGlmaWVyID0gbmV3IE1hcDtcbiAgICAgICAgICAgIHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyQnlFbGVtZW50LnNldChlbGVtZW50LCBzY29wZXNCeUlkZW50aWZpZXIpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBzY29wZXNCeUlkZW50aWZpZXI7XG4gICAgfTtcbiAgICByZXR1cm4gU2NvcGVPYnNlcnZlcjtcbn0oKSk7XG5leHBvcnQgeyBTY29wZU9ic2VydmVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1zY29wZV9vYnNlcnZlci5qcy5tYXAiLCIvKiogQGhpZGRlbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGF0dHJpYnV0ZVZhbHVlQ29udGFpbnNUb2tlbihhdHRyaWJ1dGVOYW1lLCB0b2tlbikge1xuICAgIHJldHVybiBcIltcIiArIGF0dHJpYnV0ZU5hbWUgKyBcIn49XFxcIlwiICsgdG9rZW4gKyBcIlxcXCJdXCI7XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD1zZWxlY3RvcnMuanMubWFwIiwiZXhwb3J0IGZ1bmN0aW9uIGNhbWVsaXplKHZhbHVlKSB7XG4gICAgcmV0dXJuIHZhbHVlLnJlcGxhY2UoLyg/OltfLV0pKFthLXowLTldKS9nLCBmdW5jdGlvbiAoXywgY2hhcikgeyByZXR1cm4gY2hhci50b1VwcGVyQ2FzZSgpOyB9KTtcbn1cbmV4cG9ydCBmdW5jdGlvbiBjYXBpdGFsaXplKHZhbHVlKSB7XG4gICAgcmV0dXJuIHZhbHVlLmNoYXJBdCgwKS50b1VwcGVyQ2FzZSgpICsgdmFsdWUuc2xpY2UoMSk7XG59XG5leHBvcnQgZnVuY3Rpb24gZGFzaGVyaXplKHZhbHVlKSB7XG4gICAgcmV0dXJuIHZhbHVlLnJlcGxhY2UoLyhbQS1aXSkvZywgZnVuY3Rpb24gKF8sIGNoYXIpIHsgcmV0dXJuIFwiLVwiICsgY2hhci50b0xvd2VyQ2FzZSgpOyB9KTtcbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXN0cmluZ19oZWxwZXJzLmpzLm1hcCIsImltcG9ydCB7IHJlYWRJbmhlcml0YWJsZVN0YXRpY0FycmF5VmFsdWVzIH0gZnJvbSBcIi4vaW5oZXJpdGFibGVfc3RhdGljc1wiO1xuaW1wb3J0IHsgY2FwaXRhbGl6ZSB9IGZyb20gXCIuL3N0cmluZ19oZWxwZXJzXCI7XG4vKiogQGhpZGRlbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIFRhcmdldFByb3BlcnRpZXNCbGVzc2luZyhjb25zdHJ1Y3Rvcikge1xuICAgIHZhciB0YXJnZXRzID0gcmVhZEluaGVyaXRhYmxlU3RhdGljQXJyYXlWYWx1ZXMoY29uc3RydWN0b3IsIFwidGFyZ2V0c1wiKTtcbiAgICByZXR1cm4gdGFyZ2V0cy5yZWR1Y2UoZnVuY3Rpb24gKHByb3BlcnRpZXMsIHRhcmdldERlZmluaXRpb24pIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24ocHJvcGVydGllcywgcHJvcGVydGllc0ZvclRhcmdldERlZmluaXRpb24odGFyZ2V0RGVmaW5pdGlvbikpO1xuICAgIH0sIHt9KTtcbn1cbmZ1bmN0aW9uIHByb3BlcnRpZXNGb3JUYXJnZXREZWZpbml0aW9uKG5hbWUpIHtcbiAgICB2YXIgX2E7XG4gICAgcmV0dXJuIF9hID0ge30sXG4gICAgICAgIF9hW25hbWUgKyBcIlRhcmdldFwiXSA9IHtcbiAgICAgICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHZhciB0YXJnZXQgPSB0aGlzLnRhcmdldHMuZmluZChuYW1lKTtcbiAgICAgICAgICAgICAgICBpZiAodGFyZ2V0KSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiB0YXJnZXQ7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoXCJNaXNzaW5nIHRhcmdldCBlbGVtZW50IFxcXCJcIiArIHRoaXMuaWRlbnRpZmllciArIFwiLlwiICsgbmFtZSArIFwiXFxcIlwiKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIF9hW25hbWUgKyBcIlRhcmdldHNcIl0gPSB7XG4gICAgICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy50YXJnZXRzLmZpbmRBbGwobmFtZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIF9hW1wiaGFzXCIgKyBjYXBpdGFsaXplKG5hbWUpICsgXCJUYXJnZXRcIl0gPSB7XG4gICAgICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy50YXJnZXRzLmhhcyhuYW1lKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgX2E7XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD10YXJnZXRfcHJvcGVydGllcy5qcy5tYXAiLCJ2YXIgX19zcHJlYWRBcnJheXMgPSAodGhpcyAmJiB0aGlzLl9fc3ByZWFkQXJyYXlzKSB8fCBmdW5jdGlvbiAoKSB7XG4gICAgZm9yICh2YXIgcyA9IDAsIGkgPSAwLCBpbCA9IGFyZ3VtZW50cy5sZW5ndGg7IGkgPCBpbDsgaSsrKSBzICs9IGFyZ3VtZW50c1tpXS5sZW5ndGg7XG4gICAgZm9yICh2YXIgciA9IEFycmF5KHMpLCBrID0gMCwgaSA9IDA7IGkgPCBpbDsgaSsrKVxuICAgICAgICBmb3IgKHZhciBhID0gYXJndW1lbnRzW2ldLCBqID0gMCwgamwgPSBhLmxlbmd0aDsgaiA8IGpsOyBqKyssIGsrKylcbiAgICAgICAgICAgIHJba10gPSBhW2pdO1xuICAgIHJldHVybiByO1xufTtcbmltcG9ydCB7IGF0dHJpYnV0ZVZhbHVlQ29udGFpbnNUb2tlbiB9IGZyb20gXCIuL3NlbGVjdG9yc1wiO1xudmFyIFRhcmdldFNldCA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBUYXJnZXRTZXQoc2NvcGUpIHtcbiAgICAgICAgdGhpcy5zY29wZSA9IHNjb3BlO1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVGFyZ2V0U2V0LnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFRhcmdldFNldC5wcm90b3R5cGUsIFwiaWRlbnRpZmllclwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuaWRlbnRpZmllcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShUYXJnZXRTZXQucHJvdG90eXBlLCBcInNjaGVtYVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuc2NoZW1hO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgVGFyZ2V0U2V0LnByb3RvdHlwZS5oYXMgPSBmdW5jdGlvbiAodGFyZ2V0TmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5maW5kKHRhcmdldE5hbWUpICE9IG51bGw7XG4gICAgfTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmZpbmQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHZhciB0YXJnZXROYW1lcyA9IFtdO1xuICAgICAgICBmb3IgKHZhciBfaSA9IDA7IF9pIDwgYXJndW1lbnRzLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgdGFyZ2V0TmFtZXNbX2ldID0gYXJndW1lbnRzW19pXTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gdGFyZ2V0TmFtZXMucmVkdWNlKGZ1bmN0aW9uICh0YXJnZXQsIHRhcmdldE5hbWUpIHtcbiAgICAgICAgICAgIHJldHVybiB0YXJnZXRcbiAgICAgICAgICAgICAgICB8fCBfdGhpcy5maW5kVGFyZ2V0KHRhcmdldE5hbWUpXG4gICAgICAgICAgICAgICAgfHwgX3RoaXMuZmluZExlZ2FjeVRhcmdldCh0YXJnZXROYW1lKTtcbiAgICAgICAgfSwgdW5kZWZpbmVkKTtcbiAgICB9O1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuZmluZEFsbCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdmFyIHRhcmdldE5hbWVzID0gW107XG4gICAgICAgIGZvciAodmFyIF9pID0gMDsgX2kgPCBhcmd1bWVudHMubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICB0YXJnZXROYW1lc1tfaV0gPSBhcmd1bWVudHNbX2ldO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiB0YXJnZXROYW1lcy5yZWR1Y2UoZnVuY3Rpb24gKHRhcmdldHMsIHRhcmdldE5hbWUpIHsgcmV0dXJuIF9fc3ByZWFkQXJyYXlzKHRhcmdldHMsIF90aGlzLmZpbmRBbGxUYXJnZXRzKHRhcmdldE5hbWUpLCBfdGhpcy5maW5kQWxsTGVnYWN5VGFyZ2V0cyh0YXJnZXROYW1lKSk7IH0sIFtdKTtcbiAgICB9O1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuZmluZFRhcmdldCA9IGZ1bmN0aW9uICh0YXJnZXROYW1lKSB7XG4gICAgICAgIHZhciBzZWxlY3RvciA9IHRoaXMuZ2V0U2VsZWN0b3JGb3JUYXJnZXROYW1lKHRhcmdldE5hbWUpO1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5maW5kRWxlbWVudChzZWxlY3Rvcik7XG4gICAgfTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmZpbmRBbGxUYXJnZXRzID0gZnVuY3Rpb24gKHRhcmdldE5hbWUpIHtcbiAgICAgICAgdmFyIHNlbGVjdG9yID0gdGhpcy5nZXRTZWxlY3RvckZvclRhcmdldE5hbWUodGFyZ2V0TmFtZSk7XG4gICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmZpbmRBbGxFbGVtZW50cyhzZWxlY3Rvcik7XG4gICAgfTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmdldFNlbGVjdG9yRm9yVGFyZ2V0TmFtZSA9IGZ1bmN0aW9uICh0YXJnZXROYW1lKSB7XG4gICAgICAgIHZhciBhdHRyaWJ1dGVOYW1lID0gXCJkYXRhLVwiICsgdGhpcy5pZGVudGlmaWVyICsgXCItdGFyZ2V0XCI7XG4gICAgICAgIHJldHVybiBhdHRyaWJ1dGVWYWx1ZUNvbnRhaW5zVG9rZW4oYXR0cmlidXRlTmFtZSwgdGFyZ2V0TmFtZSk7XG4gICAgfTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmZpbmRMZWdhY3lUYXJnZXQgPSBmdW5jdGlvbiAodGFyZ2V0TmFtZSkge1xuICAgICAgICB2YXIgc2VsZWN0b3IgPSB0aGlzLmdldExlZ2FjeVNlbGVjdG9yRm9yVGFyZ2V0TmFtZSh0YXJnZXROYW1lKTtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVwcmVjYXRlKHRoaXMuc2NvcGUuZmluZEVsZW1lbnQoc2VsZWN0b3IpLCB0YXJnZXROYW1lKTtcbiAgICB9O1xuICAgIFRhcmdldFNldC5wcm90b3R5cGUuZmluZEFsbExlZ2FjeVRhcmdldHMgPSBmdW5jdGlvbiAodGFyZ2V0TmFtZSkge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB2YXIgc2VsZWN0b3IgPSB0aGlzLmdldExlZ2FjeVNlbGVjdG9yRm9yVGFyZ2V0TmFtZSh0YXJnZXROYW1lKTtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZmluZEFsbEVsZW1lbnRzKHNlbGVjdG9yKS5tYXAoZnVuY3Rpb24gKGVsZW1lbnQpIHsgcmV0dXJuIF90aGlzLmRlcHJlY2F0ZShlbGVtZW50LCB0YXJnZXROYW1lKTsgfSk7XG4gICAgfTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmdldExlZ2FjeVNlbGVjdG9yRm9yVGFyZ2V0TmFtZSA9IGZ1bmN0aW9uICh0YXJnZXROYW1lKSB7XG4gICAgICAgIHZhciB0YXJnZXREZXNjcmlwdG9yID0gdGhpcy5pZGVudGlmaWVyICsgXCIuXCIgKyB0YXJnZXROYW1lO1xuICAgICAgICByZXR1cm4gYXR0cmlidXRlVmFsdWVDb250YWluc1Rva2VuKHRoaXMuc2NoZW1hLnRhcmdldEF0dHJpYnV0ZSwgdGFyZ2V0RGVzY3JpcHRvcik7XG4gICAgfTtcbiAgICBUYXJnZXRTZXQucHJvdG90eXBlLmRlcHJlY2F0ZSA9IGZ1bmN0aW9uIChlbGVtZW50LCB0YXJnZXROYW1lKSB7XG4gICAgICAgIGlmIChlbGVtZW50KSB7XG4gICAgICAgICAgICB2YXIgaWRlbnRpZmllciA9IHRoaXMuaWRlbnRpZmllcjtcbiAgICAgICAgICAgIHZhciBhdHRyaWJ1dGVOYW1lID0gdGhpcy5zY2hlbWEudGFyZ2V0QXR0cmlidXRlO1xuICAgICAgICAgICAgdGhpcy5ndWlkZS53YXJuKGVsZW1lbnQsIFwidGFyZ2V0OlwiICsgdGFyZ2V0TmFtZSwgXCJQbGVhc2UgcmVwbGFjZSBcIiArIGF0dHJpYnV0ZU5hbWUgKyBcIj1cXFwiXCIgKyBpZGVudGlmaWVyICsgXCIuXCIgKyB0YXJnZXROYW1lICsgXCJcXFwiIHdpdGggZGF0YS1cIiArIGlkZW50aWZpZXIgKyBcIi10YXJnZXQ9XFxcIlwiICsgdGFyZ2V0TmFtZSArIFwiXFxcIi4gXCIgK1xuICAgICAgICAgICAgICAgIChcIlRoZSBcIiArIGF0dHJpYnV0ZU5hbWUgKyBcIiBhdHRyaWJ1dGUgaXMgZGVwcmVjYXRlZCBhbmQgd2lsbCBiZSByZW1vdmVkIGluIGEgZnV0dXJlIHZlcnNpb24gb2YgU3RpbXVsdXMuXCIpKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZWxlbWVudDtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShUYXJnZXRTZXQucHJvdG90eXBlLCBcImd1aWRlXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5ndWlkZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIHJldHVybiBUYXJnZXRTZXQ7XG59KCkpO1xuZXhwb3J0IHsgVGFyZ2V0U2V0IH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD10YXJnZXRfc2V0LmpzLm1hcCIsImltcG9ydCB7IFN0cmluZ01hcE9ic2VydmVyIH0gZnJvbSBcIkBzdGltdWx1cy9tdXRhdGlvbi1vYnNlcnZlcnNcIjtcbnZhciBWYWx1ZU9ic2VydmVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIFZhbHVlT2JzZXJ2ZXIoY29udGV4dCwgcmVjZWl2ZXIpIHtcbiAgICAgICAgdGhpcy5jb250ZXh0ID0gY29udGV4dDtcbiAgICAgICAgdGhpcy5yZWNlaXZlciA9IHJlY2VpdmVyO1xuICAgICAgICB0aGlzLnN0cmluZ01hcE9ic2VydmVyID0gbmV3IFN0cmluZ01hcE9ic2VydmVyKHRoaXMuZWxlbWVudCwgdGhpcyk7XG4gICAgICAgIHRoaXMudmFsdWVEZXNjcmlwdG9yTWFwID0gdGhpcy5jb250cm9sbGVyLnZhbHVlRGVzY3JpcHRvck1hcDtcbiAgICAgICAgdGhpcy5pbnZva2VDaGFuZ2VkQ2FsbGJhY2tzRm9yRGVmYXVsdFZhbHVlcygpO1xuICAgIH1cbiAgICBWYWx1ZU9ic2VydmVyLnByb3RvdHlwZS5zdGFydCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5zdHJpbmdNYXBPYnNlcnZlci5zdGFydCgpO1xuICAgIH07XG4gICAgVmFsdWVPYnNlcnZlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5zdHJpbmdNYXBPYnNlcnZlci5zdG9wKCk7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVmFsdWVPYnNlcnZlci5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5lbGVtZW50O1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFZhbHVlT2JzZXJ2ZXIucHJvdG90eXBlLCBcImNvbnRyb2xsZXJcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuY29udHJvbGxlcjtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIC8vIFN0cmluZyBtYXAgb2JzZXJ2ZXIgZGVsZWdhdGVcbiAgICBWYWx1ZU9ic2VydmVyLnByb3RvdHlwZS5nZXRTdHJpbmdNYXBLZXlGb3JBdHRyaWJ1dGUgPSBmdW5jdGlvbiAoYXR0cmlidXRlTmFtZSkge1xuICAgICAgICBpZiAoYXR0cmlidXRlTmFtZSBpbiB0aGlzLnZhbHVlRGVzY3JpcHRvck1hcCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMudmFsdWVEZXNjcmlwdG9yTWFwW2F0dHJpYnV0ZU5hbWVdLm5hbWU7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFZhbHVlT2JzZXJ2ZXIucHJvdG90eXBlLnN0cmluZ01hcFZhbHVlQ2hhbmdlZCA9IGZ1bmN0aW9uIChhdHRyaWJ1dGVWYWx1ZSwgbmFtZSkge1xuICAgICAgICB0aGlzLmludm9rZUNoYW5nZWRDYWxsYmFja0ZvclZhbHVlKG5hbWUpO1xuICAgIH07XG4gICAgVmFsdWVPYnNlcnZlci5wcm90b3R5cGUuaW52b2tlQ2hhbmdlZENhbGxiYWNrc0ZvckRlZmF1bHRWYWx1ZXMgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGZvciAodmFyIF9pID0gMCwgX2EgPSB0aGlzLnZhbHVlRGVzY3JpcHRvcnM7IF9pIDwgX2EubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICB2YXIgX2IgPSBfYVtfaV0sIGtleSA9IF9iLmtleSwgbmFtZV8xID0gX2IubmFtZSwgZGVmYXVsdFZhbHVlID0gX2IuZGVmYXVsdFZhbHVlO1xuICAgICAgICAgICAgaWYgKGRlZmF1bHRWYWx1ZSAhPSB1bmRlZmluZWQgJiYgIXRoaXMuY29udHJvbGxlci5kYXRhLmhhcyhrZXkpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5pbnZva2VDaGFuZ2VkQ2FsbGJhY2tGb3JWYWx1ZShuYW1lXzEpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICBWYWx1ZU9ic2VydmVyLnByb3RvdHlwZS5pbnZva2VDaGFuZ2VkQ2FsbGJhY2tGb3JWYWx1ZSA9IGZ1bmN0aW9uIChuYW1lKSB7XG4gICAgICAgIHZhciBtZXRob2ROYW1lID0gbmFtZSArIFwiQ2hhbmdlZFwiO1xuICAgICAgICB2YXIgbWV0aG9kID0gdGhpcy5yZWNlaXZlclttZXRob2ROYW1lXTtcbiAgICAgICAgaWYgKHR5cGVvZiBtZXRob2QgPT0gXCJmdW5jdGlvblwiKSB7XG4gICAgICAgICAgICB2YXIgdmFsdWUgPSB0aGlzLnJlY2VpdmVyW25hbWVdO1xuICAgICAgICAgICAgbWV0aG9kLmNhbGwodGhpcy5yZWNlaXZlciwgdmFsdWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVmFsdWVPYnNlcnZlci5wcm90b3R5cGUsIFwidmFsdWVEZXNjcmlwdG9yc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgdmFyIHZhbHVlRGVzY3JpcHRvck1hcCA9IHRoaXMudmFsdWVEZXNjcmlwdG9yTWFwO1xuICAgICAgICAgICAgcmV0dXJuIE9iamVjdC5rZXlzKHZhbHVlRGVzY3JpcHRvck1hcCkubWFwKGZ1bmN0aW9uIChrZXkpIHsgcmV0dXJuIHZhbHVlRGVzY3JpcHRvck1hcFtrZXldOyB9KTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIHJldHVybiBWYWx1ZU9ic2VydmVyO1xufSgpKTtcbmV4cG9ydCB7IFZhbHVlT2JzZXJ2ZXIgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXZhbHVlX29ic2VydmVyLmpzLm1hcCIsImltcG9ydCB7IHJlYWRJbmhlcml0YWJsZVN0YXRpY09iamVjdFBhaXJzIH0gZnJvbSBcIi4vaW5oZXJpdGFibGVfc3RhdGljc1wiO1xuaW1wb3J0IHsgY2FtZWxpemUsIGNhcGl0YWxpemUsIGRhc2hlcml6ZSB9IGZyb20gXCIuL3N0cmluZ19oZWxwZXJzXCI7XG4vKiogQGhpZGRlbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIFZhbHVlUHJvcGVydGllc0JsZXNzaW5nKGNvbnN0cnVjdG9yKSB7XG4gICAgdmFyIHZhbHVlRGVmaW5pdGlvblBhaXJzID0gcmVhZEluaGVyaXRhYmxlU3RhdGljT2JqZWN0UGFpcnMoY29uc3RydWN0b3IsIFwidmFsdWVzXCIpO1xuICAgIHZhciBwcm9wZXJ0eURlc2NyaXB0b3JNYXAgPSB7XG4gICAgICAgIHZhbHVlRGVzY3JpcHRvck1hcDoge1xuICAgICAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgICAgICAgICByZXR1cm4gdmFsdWVEZWZpbml0aW9uUGFpcnMucmVkdWNlKGZ1bmN0aW9uIChyZXN1bHQsIHZhbHVlRGVmaW5pdGlvblBhaXIpIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIF9hO1xuICAgICAgICAgICAgICAgICAgICB2YXIgdmFsdWVEZXNjcmlwdG9yID0gcGFyc2VWYWx1ZURlZmluaXRpb25QYWlyKHZhbHVlRGVmaW5pdGlvblBhaXIpO1xuICAgICAgICAgICAgICAgICAgICB2YXIgYXR0cmlidXRlTmFtZSA9IF90aGlzLmRhdGEuZ2V0QXR0cmlidXRlTmFtZUZvcktleSh2YWx1ZURlc2NyaXB0b3Iua2V5KTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24ocmVzdWx0LCAoX2EgPSB7fSwgX2FbYXR0cmlidXRlTmFtZV0gPSB2YWx1ZURlc2NyaXB0b3IsIF9hKSk7XG4gICAgICAgICAgICAgICAgfSwge30pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICByZXR1cm4gdmFsdWVEZWZpbml0aW9uUGFpcnMucmVkdWNlKGZ1bmN0aW9uIChwcm9wZXJ0aWVzLCB2YWx1ZURlZmluaXRpb25QYWlyKSB7XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKHByb3BlcnRpZXMsIHByb3BlcnRpZXNGb3JWYWx1ZURlZmluaXRpb25QYWlyKHZhbHVlRGVmaW5pdGlvblBhaXIpKTtcbiAgICB9LCBwcm9wZXJ0eURlc2NyaXB0b3JNYXApO1xufVxuLyoqIEBoaWRkZW4gKi9cbmV4cG9ydCBmdW5jdGlvbiBwcm9wZXJ0aWVzRm9yVmFsdWVEZWZpbml0aW9uUGFpcih2YWx1ZURlZmluaXRpb25QYWlyKSB7XG4gICAgdmFyIF9hO1xuICAgIHZhciBkZWZpbml0aW9uID0gcGFyc2VWYWx1ZURlZmluaXRpb25QYWlyKHZhbHVlRGVmaW5pdGlvblBhaXIpO1xuICAgIHZhciB0eXBlID0gZGVmaW5pdGlvbi50eXBlLCBrZXkgPSBkZWZpbml0aW9uLmtleSwgbmFtZSA9IGRlZmluaXRpb24ubmFtZTtcbiAgICB2YXIgcmVhZCA9IHJlYWRlcnNbdHlwZV0sIHdyaXRlID0gd3JpdGVyc1t0eXBlXSB8fCB3cml0ZXJzLmRlZmF1bHQ7XG4gICAgcmV0dXJuIF9hID0ge30sXG4gICAgICAgIF9hW25hbWVdID0ge1xuICAgICAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgdmFyIHZhbHVlID0gdGhpcy5kYXRhLmdldChrZXkpO1xuICAgICAgICAgICAgICAgIGlmICh2YWx1ZSAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gcmVhZCh2YWx1ZSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gZGVmaW5pdGlvbi5kZWZhdWx0VmFsdWU7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIHNldDogZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgICAgICAgICAgaWYgKHZhbHVlID09PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5kYXRhLmRlbGV0ZShrZXkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5kYXRhLnNldChrZXksIHdyaXRlKHZhbHVlKSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBfYVtcImhhc1wiICsgY2FwaXRhbGl6ZShuYW1lKV0gPSB7XG4gICAgICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5kYXRhLmhhcyhrZXkpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBfYTtcbn1cbmZ1bmN0aW9uIHBhcnNlVmFsdWVEZWZpbml0aW9uUGFpcihfYSkge1xuICAgIHZhciB0b2tlbiA9IF9hWzBdLCB0eXBlQ29uc3RhbnQgPSBfYVsxXTtcbiAgICB2YXIgdHlwZSA9IHBhcnNlVmFsdWVUeXBlQ29uc3RhbnQodHlwZUNvbnN0YW50KTtcbiAgICByZXR1cm4gdmFsdWVEZXNjcmlwdG9yRm9yVG9rZW5BbmRUeXBlKHRva2VuLCB0eXBlKTtcbn1cbmZ1bmN0aW9uIHBhcnNlVmFsdWVUeXBlQ29uc3RhbnQodHlwZUNvbnN0YW50KSB7XG4gICAgc3dpdGNoICh0eXBlQ29uc3RhbnQpIHtcbiAgICAgICAgY2FzZSBBcnJheTogcmV0dXJuIFwiYXJyYXlcIjtcbiAgICAgICAgY2FzZSBCb29sZWFuOiByZXR1cm4gXCJib29sZWFuXCI7XG4gICAgICAgIGNhc2UgTnVtYmVyOiByZXR1cm4gXCJudW1iZXJcIjtcbiAgICAgICAgY2FzZSBPYmplY3Q6IHJldHVybiBcIm9iamVjdFwiO1xuICAgICAgICBjYXNlIFN0cmluZzogcmV0dXJuIFwic3RyaW5nXCI7XG4gICAgfVxuICAgIHRocm93IG5ldyBFcnJvcihcIlVua25vd24gdmFsdWUgdHlwZSBjb25zdGFudCBcXFwiXCIgKyB0eXBlQ29uc3RhbnQgKyBcIlxcXCJcIik7XG59XG5mdW5jdGlvbiB2YWx1ZURlc2NyaXB0b3JGb3JUb2tlbkFuZFR5cGUodG9rZW4sIHR5cGUpIHtcbiAgICB2YXIga2V5ID0gZGFzaGVyaXplKHRva2VuKSArIFwiLXZhbHVlXCI7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgdHlwZTogdHlwZSxcbiAgICAgICAga2V5OiBrZXksXG4gICAgICAgIG5hbWU6IGNhbWVsaXplKGtleSksXG4gICAgICAgIGdldCBkZWZhdWx0VmFsdWUoKSB7IHJldHVybiBkZWZhdWx0VmFsdWVzQnlUeXBlW3R5cGVdOyB9XG4gICAgfTtcbn1cbnZhciBkZWZhdWx0VmFsdWVzQnlUeXBlID0ge1xuICAgIGdldCBhcnJheSgpIHsgcmV0dXJuIFtdOyB9LFxuICAgIGJvb2xlYW46IGZhbHNlLFxuICAgIG51bWJlcjogMCxcbiAgICBnZXQgb2JqZWN0KCkgeyByZXR1cm4ge307IH0sXG4gICAgc3RyaW5nOiBcIlwiXG59O1xudmFyIHJlYWRlcnMgPSB7XG4gICAgYXJyYXk6IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICB2YXIgYXJyYXkgPSBKU09OLnBhcnNlKHZhbHVlKTtcbiAgICAgICAgaWYgKCFBcnJheS5pc0FycmF5KGFycmF5KSkge1xuICAgICAgICAgICAgdGhyb3cgbmV3IFR5cGVFcnJvcihcIkV4cGVjdGVkIGFycmF5XCIpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBhcnJheTtcbiAgICB9LFxuICAgIGJvb2xlYW46IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICByZXR1cm4gISh2YWx1ZSA9PSBcIjBcIiB8fCB2YWx1ZSA9PSBcImZhbHNlXCIpO1xuICAgIH0sXG4gICAgbnVtYmVyOiBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgcmV0dXJuIHBhcnNlRmxvYXQodmFsdWUpO1xuICAgIH0sXG4gICAgb2JqZWN0OiBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgdmFyIG9iamVjdCA9IEpTT04ucGFyc2UodmFsdWUpO1xuICAgICAgICBpZiAob2JqZWN0ID09PSBudWxsIHx8IHR5cGVvZiBvYmplY3QgIT0gXCJvYmplY3RcIiB8fCBBcnJheS5pc0FycmF5KG9iamVjdCkpIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBUeXBlRXJyb3IoXCJFeHBlY3RlZCBvYmplY3RcIik7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIG9iamVjdDtcbiAgICB9LFxuICAgIHN0cmluZzogZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgIHJldHVybiB2YWx1ZTtcbiAgICB9XG59O1xudmFyIHdyaXRlcnMgPSB7XG4gICAgZGVmYXVsdDogd3JpdGVTdHJpbmcsXG4gICAgYXJyYXk6IHdyaXRlSlNPTixcbiAgICBvYmplY3Q6IHdyaXRlSlNPTlxufTtcbmZ1bmN0aW9uIHdyaXRlSlNPTih2YWx1ZSkge1xuICAgIHJldHVybiBKU09OLnN0cmluZ2lmeSh2YWx1ZSk7XG59XG5mdW5jdGlvbiB3cml0ZVN0cmluZyh2YWx1ZSkge1xuICAgIHJldHVybiBcIlwiICsgdmFsdWU7XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD12YWx1ZV9wcm9wZXJ0aWVzLmpzLm1hcCIsImV4cG9ydCAqIGZyb20gXCIuL2luZGV4ZWRfbXVsdGltYXBcIjtcbmV4cG9ydCAqIGZyb20gXCIuL211bHRpbWFwXCI7XG5leHBvcnQgKiBmcm9tIFwiLi9zZXRfb3BlcmF0aW9uc1wiO1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9aW5kZXguanMubWFwIiwidmFyIF9fZXh0ZW5kcyA9ICh0aGlzICYmIHRoaXMuX19leHRlbmRzKSB8fCAoZnVuY3Rpb24gKCkge1xuICAgIHZhciBleHRlbmRTdGF0aWNzID0gZnVuY3Rpb24gKGQsIGIpIHtcbiAgICAgICAgZXh0ZW5kU3RhdGljcyA9IE9iamVjdC5zZXRQcm90b3R5cGVPZiB8fFxuICAgICAgICAgICAgKHsgX19wcm90b19fOiBbXSB9IGluc3RhbmNlb2YgQXJyYXkgJiYgZnVuY3Rpb24gKGQsIGIpIHsgZC5fX3Byb3RvX18gPSBiOyB9KSB8fFxuICAgICAgICAgICAgZnVuY3Rpb24gKGQsIGIpIHsgZm9yICh2YXIgcCBpbiBiKSBpZiAoYi5oYXNPd25Qcm9wZXJ0eShwKSkgZFtwXSA9IGJbcF07IH07XG4gICAgICAgIHJldHVybiBleHRlbmRTdGF0aWNzKGQsIGIpO1xuICAgIH07XG4gICAgcmV0dXJuIGZ1bmN0aW9uIChkLCBiKSB7XG4gICAgICAgIGV4dGVuZFN0YXRpY3MoZCwgYik7XG4gICAgICAgIGZ1bmN0aW9uIF9fKCkgeyB0aGlzLmNvbnN0cnVjdG9yID0gZDsgfVxuICAgICAgICBkLnByb3RvdHlwZSA9IGIgPT09IG51bGwgPyBPYmplY3QuY3JlYXRlKGIpIDogKF9fLnByb3RvdHlwZSA9IGIucHJvdG90eXBlLCBuZXcgX18oKSk7XG4gICAgfTtcbn0pKCk7XG5pbXBvcnQgeyBNdWx0aW1hcCB9IGZyb20gXCIuL211bHRpbWFwXCI7XG5pbXBvcnQgeyBhZGQsIGRlbCB9IGZyb20gXCIuL3NldF9vcGVyYXRpb25zXCI7XG52YXIgSW5kZXhlZE11bHRpbWFwID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKF9zdXBlcikge1xuICAgIF9fZXh0ZW5kcyhJbmRleGVkTXVsdGltYXAsIF9zdXBlcik7XG4gICAgZnVuY3Rpb24gSW5kZXhlZE11bHRpbWFwKCkge1xuICAgICAgICB2YXIgX3RoaXMgPSBfc3VwZXIuY2FsbCh0aGlzKSB8fCB0aGlzO1xuICAgICAgICBfdGhpcy5rZXlzQnlWYWx1ZSA9IG5ldyBNYXA7XG4gICAgICAgIHJldHVybiBfdGhpcztcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEluZGV4ZWRNdWx0aW1hcC5wcm90b3R5cGUsIFwidmFsdWVzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLmtleXNCeVZhbHVlLmtleXMoKSk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBJbmRleGVkTXVsdGltYXAucHJvdG90eXBlLmFkZCA9IGZ1bmN0aW9uIChrZXksIHZhbHVlKSB7XG4gICAgICAgIF9zdXBlci5wcm90b3R5cGUuYWRkLmNhbGwodGhpcywga2V5LCB2YWx1ZSk7XG4gICAgICAgIGFkZCh0aGlzLmtleXNCeVZhbHVlLCB2YWx1ZSwga2V5KTtcbiAgICB9O1xuICAgIEluZGV4ZWRNdWx0aW1hcC5wcm90b3R5cGUuZGVsZXRlID0gZnVuY3Rpb24gKGtleSwgdmFsdWUpIHtcbiAgICAgICAgX3N1cGVyLnByb3RvdHlwZS5kZWxldGUuY2FsbCh0aGlzLCBrZXksIHZhbHVlKTtcbiAgICAgICAgZGVsKHRoaXMua2V5c0J5VmFsdWUsIHZhbHVlLCBrZXkpO1xuICAgIH07XG4gICAgSW5kZXhlZE11bHRpbWFwLnByb3RvdHlwZS5oYXNWYWx1ZSA9IGZ1bmN0aW9uICh2YWx1ZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5rZXlzQnlWYWx1ZS5oYXModmFsdWUpO1xuICAgIH07XG4gICAgSW5kZXhlZE11bHRpbWFwLnByb3RvdHlwZS5nZXRLZXlzRm9yVmFsdWUgPSBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgdmFyIHNldCA9IHRoaXMua2V5c0J5VmFsdWUuZ2V0KHZhbHVlKTtcbiAgICAgICAgcmV0dXJuIHNldCA/IEFycmF5LmZyb20oc2V0KSA6IFtdO1xuICAgIH07XG4gICAgcmV0dXJuIEluZGV4ZWRNdWx0aW1hcDtcbn0oTXVsdGltYXApKTtcbmV4cG9ydCB7IEluZGV4ZWRNdWx0aW1hcCB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9aW5kZXhlZF9tdWx0aW1hcC5qcy5tYXAiLCJpbXBvcnQgeyBhZGQsIGRlbCB9IGZyb20gXCIuL3NldF9vcGVyYXRpb25zXCI7XG52YXIgTXVsdGltYXAgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gTXVsdGltYXAoKSB7XG4gICAgICAgIHRoaXMudmFsdWVzQnlLZXkgPSBuZXcgTWFwKCk7XG4gICAgfVxuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShNdWx0aW1hcC5wcm90b3R5cGUsIFwidmFsdWVzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICB2YXIgc2V0cyA9IEFycmF5LmZyb20odGhpcy52YWx1ZXNCeUtleS52YWx1ZXMoKSk7XG4gICAgICAgICAgICByZXR1cm4gc2V0cy5yZWR1Y2UoZnVuY3Rpb24gKHZhbHVlcywgc2V0KSB7IHJldHVybiB2YWx1ZXMuY29uY2F0KEFycmF5LmZyb20oc2V0KSk7IH0sIFtdKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShNdWx0aW1hcC5wcm90b3R5cGUsIFwic2l6ZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgdmFyIHNldHMgPSBBcnJheS5mcm9tKHRoaXMudmFsdWVzQnlLZXkudmFsdWVzKCkpO1xuICAgICAgICAgICAgcmV0dXJuIHNldHMucmVkdWNlKGZ1bmN0aW9uIChzaXplLCBzZXQpIHsgcmV0dXJuIHNpemUgKyBzZXQuc2l6ZTsgfSwgMCk7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBNdWx0aW1hcC5wcm90b3R5cGUuYWRkID0gZnVuY3Rpb24gKGtleSwgdmFsdWUpIHtcbiAgICAgICAgYWRkKHRoaXMudmFsdWVzQnlLZXksIGtleSwgdmFsdWUpO1xuICAgIH07XG4gICAgTXVsdGltYXAucHJvdG90eXBlLmRlbGV0ZSA9IGZ1bmN0aW9uIChrZXksIHZhbHVlKSB7XG4gICAgICAgIGRlbCh0aGlzLnZhbHVlc0J5S2V5LCBrZXksIHZhbHVlKTtcbiAgICB9O1xuICAgIE11bHRpbWFwLnByb3RvdHlwZS5oYXMgPSBmdW5jdGlvbiAoa2V5LCB2YWx1ZSkge1xuICAgICAgICB2YXIgdmFsdWVzID0gdGhpcy52YWx1ZXNCeUtleS5nZXQoa2V5KTtcbiAgICAgICAgcmV0dXJuIHZhbHVlcyAhPSBudWxsICYmIHZhbHVlcy5oYXModmFsdWUpO1xuICAgIH07XG4gICAgTXVsdGltYXAucHJvdG90eXBlLmhhc0tleSA9IGZ1bmN0aW9uIChrZXkpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMudmFsdWVzQnlLZXkuaGFzKGtleSk7XG4gICAgfTtcbiAgICBNdWx0aW1hcC5wcm90b3R5cGUuaGFzVmFsdWUgPSBmdW5jdGlvbiAodmFsdWUpIHtcbiAgICAgICAgdmFyIHNldHMgPSBBcnJheS5mcm9tKHRoaXMudmFsdWVzQnlLZXkudmFsdWVzKCkpO1xuICAgICAgICByZXR1cm4gc2V0cy5zb21lKGZ1bmN0aW9uIChzZXQpIHsgcmV0dXJuIHNldC5oYXModmFsdWUpOyB9KTtcbiAgICB9O1xuICAgIE11bHRpbWFwLnByb3RvdHlwZS5nZXRWYWx1ZXNGb3JLZXkgPSBmdW5jdGlvbiAoa2V5KSB7XG4gICAgICAgIHZhciB2YWx1ZXMgPSB0aGlzLnZhbHVlc0J5S2V5LmdldChrZXkpO1xuICAgICAgICByZXR1cm4gdmFsdWVzID8gQXJyYXkuZnJvbSh2YWx1ZXMpIDogW107XG4gICAgfTtcbiAgICBNdWx0aW1hcC5wcm90b3R5cGUuZ2V0S2V5c0ZvclZhbHVlID0gZnVuY3Rpb24gKHZhbHVlKSB7XG4gICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMudmFsdWVzQnlLZXkpXG4gICAgICAgICAgICAuZmlsdGVyKGZ1bmN0aW9uIChfYSkge1xuICAgICAgICAgICAgdmFyIGtleSA9IF9hWzBdLCB2YWx1ZXMgPSBfYVsxXTtcbiAgICAgICAgICAgIHJldHVybiB2YWx1ZXMuaGFzKHZhbHVlKTtcbiAgICAgICAgfSlcbiAgICAgICAgICAgIC5tYXAoZnVuY3Rpb24gKF9hKSB7XG4gICAgICAgICAgICB2YXIga2V5ID0gX2FbMF0sIHZhbHVlcyA9IF9hWzFdO1xuICAgICAgICAgICAgcmV0dXJuIGtleTtcbiAgICAgICAgfSk7XG4gICAgfTtcbiAgICByZXR1cm4gTXVsdGltYXA7XG59KCkpO1xuZXhwb3J0IHsgTXVsdGltYXAgfTtcbi8vIyBzb3VyY2VNYXBwaW5nVVJMPW11bHRpbWFwLmpzLm1hcCIsImV4cG9ydCBmdW5jdGlvbiBhZGQobWFwLCBrZXksIHZhbHVlKSB7XG4gICAgZmV0Y2gobWFwLCBrZXkpLmFkZCh2YWx1ZSk7XG59XG5leHBvcnQgZnVuY3Rpb24gZGVsKG1hcCwga2V5LCB2YWx1ZSkge1xuICAgIGZldGNoKG1hcCwga2V5KS5kZWxldGUodmFsdWUpO1xuICAgIHBydW5lKG1hcCwga2V5KTtcbn1cbmV4cG9ydCBmdW5jdGlvbiBmZXRjaChtYXAsIGtleSkge1xuICAgIHZhciB2YWx1ZXMgPSBtYXAuZ2V0KGtleSk7XG4gICAgaWYgKCF2YWx1ZXMpIHtcbiAgICAgICAgdmFsdWVzID0gbmV3IFNldCgpO1xuICAgICAgICBtYXAuc2V0KGtleSwgdmFsdWVzKTtcbiAgICB9XG4gICAgcmV0dXJuIHZhbHVlcztcbn1cbmV4cG9ydCBmdW5jdGlvbiBwcnVuZShtYXAsIGtleSkge1xuICAgIHZhciB2YWx1ZXMgPSBtYXAuZ2V0KGtleSk7XG4gICAgaWYgKHZhbHVlcyAhPSBudWxsICYmIHZhbHVlcy5zaXplID09IDApIHtcbiAgICAgICAgbWFwLmRlbGV0ZShrZXkpO1xuICAgIH1cbn1cbi8vIyBzb3VyY2VNYXBwaW5nVVJMPXNldF9vcGVyYXRpb25zLmpzLm1hcCIsImltcG9ydCB7IEVsZW1lbnRPYnNlcnZlciB9IGZyb20gXCIuL2VsZW1lbnRfb2JzZXJ2ZXJcIjtcbnZhciBBdHRyaWJ1dGVPYnNlcnZlciA9IC8qKiBAY2xhc3MgKi8gKGZ1bmN0aW9uICgpIHtcbiAgICBmdW5jdGlvbiBBdHRyaWJ1dGVPYnNlcnZlcihlbGVtZW50LCBhdHRyaWJ1dGVOYW1lLCBkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZU5hbWUgPSBhdHRyaWJ1dGVOYW1lO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMuZWxlbWVudE9ic2VydmVyID0gbmV3IEVsZW1lbnRPYnNlcnZlcihlbGVtZW50LCB0aGlzKTtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZSwgXCJlbGVtZW50XCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50T2JzZXJ2ZXIuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUsIFwic2VsZWN0b3JcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBcIltcIiArIHRoaXMuYXR0cmlidXRlTmFtZSArIFwiXVwiO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmVsZW1lbnRPYnNlcnZlci5zdGFydCgpO1xuICAgIH07XG4gICAgQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLnN0b3AgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudE9ic2VydmVyLnN0b3AoKTtcbiAgICB9O1xuICAgIEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZS5yZWZyZXNoID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmVsZW1lbnRPYnNlcnZlci5yZWZyZXNoKCk7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoQXR0cmlidXRlT2JzZXJ2ZXIucHJvdG90eXBlLCBcInN0YXJ0ZWRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnRPYnNlcnZlci5zdGFydGVkO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgLy8gRWxlbWVudCBvYnNlcnZlciBkZWxlZ2F0ZVxuICAgIEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZS5tYXRjaEVsZW1lbnQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICByZXR1cm4gZWxlbWVudC5oYXNBdHRyaWJ1dGUodGhpcy5hdHRyaWJ1dGVOYW1lKTtcbiAgICB9O1xuICAgIEF0dHJpYnV0ZU9ic2VydmVyLnByb3RvdHlwZS5tYXRjaEVsZW1lbnRzSW5UcmVlID0gZnVuY3Rpb24gKHRyZWUpIHtcbiAgICAgICAgdmFyIG1hdGNoID0gdGhpcy5tYXRjaEVsZW1lbnQodHJlZSkgPyBbdHJlZV0gOiBbXTtcbiAgICAgICAgdmFyIG1hdGNoZXMgPSBBcnJheS5mcm9tKHRyZWUucXVlcnlTZWxlY3RvckFsbCh0aGlzLnNlbGVjdG9yKSk7XG4gICAgICAgIHJldHVybiBtYXRjaC5jb25jYXQobWF0Y2hlcyk7XG4gICAgfTtcbiAgICBBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudE1hdGNoZWQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5lbGVtZW50TWF0Y2hlZEF0dHJpYnV0ZSkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5lbGVtZW50TWF0Y2hlZEF0dHJpYnV0ZShlbGVtZW50LCB0aGlzLmF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudFVubWF0Y2hlZCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLmVsZW1lbnRVbm1hdGNoZWRBdHRyaWJ1dGUpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudFVubWF0Y2hlZEF0dHJpYnV0ZShlbGVtZW50LCB0aGlzLmF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBBdHRyaWJ1dGVPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudEF0dHJpYnV0ZUNoYW5nZWQgPSBmdW5jdGlvbiAoZWxlbWVudCwgYXR0cmlidXRlTmFtZSkge1xuICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5lbGVtZW50QXR0cmlidXRlVmFsdWVDaGFuZ2VkICYmIHRoaXMuYXR0cmlidXRlTmFtZSA9PSBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRBdHRyaWJ1dGVWYWx1ZUNoYW5nZWQoZWxlbWVudCwgYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIHJldHVybiBBdHRyaWJ1dGVPYnNlcnZlcjtcbn0oKSk7XG5leHBvcnQgeyBBdHRyaWJ1dGVPYnNlcnZlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9YXR0cmlidXRlX29ic2VydmVyLmpzLm1hcCIsInZhciBFbGVtZW50T2JzZXJ2ZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gRWxlbWVudE9ic2VydmVyKGVsZW1lbnQsIGRlbGVnYXRlKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMuZWxlbWVudHMgPSBuZXcgU2V0O1xuICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIgPSBuZXcgTXV0YXRpb25PYnNlcnZlcihmdW5jdGlvbiAobXV0YXRpb25zKSB7IHJldHVybiBfdGhpcy5wcm9jZXNzTXV0YXRpb25zKG11dGF0aW9ucyk7IH0pO1xuICAgIH1cbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAoIXRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gdHJ1ZTtcbiAgICAgICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlci5vYnNlcnZlKHRoaXMuZWxlbWVudCwgeyBhdHRyaWJ1dGVzOiB0cnVlLCBjaGlsZExpc3Q6IHRydWUsIHN1YnRyZWU6IHRydWUgfSk7XG4gICAgICAgICAgICB0aGlzLnJlZnJlc2goKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIudGFrZVJlY29yZHMoKTtcbiAgICAgICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlci5kaXNjb25uZWN0KCk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5yZWZyZXNoID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB2YXIgbWF0Y2hlcyA9IG5ldyBTZXQodGhpcy5tYXRjaEVsZW1lbnRzSW5UcmVlKCkpO1xuICAgICAgICAgICAgZm9yICh2YXIgX2kgPSAwLCBfYSA9IEFycmF5LmZyb20odGhpcy5lbGVtZW50cyk7IF9pIDwgX2EubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICAgICAgdmFyIGVsZW1lbnQgPSBfYVtfaV07XG4gICAgICAgICAgICAgICAgaWYgKCFtYXRjaGVzLmhhcyhlbGVtZW50KSkge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLnJlbW92ZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZm9yICh2YXIgX2IgPSAwLCBfYyA9IEFycmF5LmZyb20obWF0Y2hlcyk7IF9iIDwgX2MubGVuZ3RoOyBfYisrKSB7XG4gICAgICAgICAgICAgICAgdmFyIGVsZW1lbnQgPSBfY1tfYl07XG4gICAgICAgICAgICAgICAgdGhpcy5hZGRFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICAvLyBNdXRhdGlvbiByZWNvcmQgcHJvY2Vzc2luZ1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUucHJvY2Vzc011dGF0aW9ucyA9IGZ1bmN0aW9uIChtdXRhdGlvbnMpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgZm9yICh2YXIgX2kgPSAwLCBtdXRhdGlvbnNfMSA9IG11dGF0aW9uczsgX2kgPCBtdXRhdGlvbnNfMS5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgICAgICB2YXIgbXV0YXRpb24gPSBtdXRhdGlvbnNfMVtfaV07XG4gICAgICAgICAgICAgICAgdGhpcy5wcm9jZXNzTXV0YXRpb24obXV0YXRpb24pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnByb2Nlc3NNdXRhdGlvbiA9IGZ1bmN0aW9uIChtdXRhdGlvbikge1xuICAgICAgICBpZiAobXV0YXRpb24udHlwZSA9PSBcImF0dHJpYnV0ZXNcIikge1xuICAgICAgICAgICAgdGhpcy5wcm9jZXNzQXR0cmlidXRlQ2hhbmdlKG11dGF0aW9uLnRhcmdldCwgbXV0YXRpb24uYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAobXV0YXRpb24udHlwZSA9PSBcImNoaWxkTGlzdFwiKSB7XG4gICAgICAgICAgICB0aGlzLnByb2Nlc3NSZW1vdmVkTm9kZXMobXV0YXRpb24ucmVtb3ZlZE5vZGVzKTtcbiAgICAgICAgICAgIHRoaXMucHJvY2Vzc0FkZGVkTm9kZXMobXV0YXRpb24uYWRkZWROb2Rlcyk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUucHJvY2Vzc0F0dHJpYnV0ZUNoYW5nZSA9IGZ1bmN0aW9uIChub2RlLCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIHZhciBlbGVtZW50ID0gbm9kZTtcbiAgICAgICAgaWYgKHRoaXMuZWxlbWVudHMuaGFzKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5lbGVtZW50QXR0cmlidXRlQ2hhbmdlZCAmJiB0aGlzLm1hdGNoRWxlbWVudChlbGVtZW50KSkge1xuICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudEF0dHJpYnV0ZUNoYW5nZWQoZWxlbWVudCwgYXR0cmlidXRlTmFtZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aGlzLnJlbW92ZUVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAodGhpcy5tYXRjaEVsZW1lbnQoZWxlbWVudCkpIHtcbiAgICAgICAgICAgIHRoaXMuYWRkRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5wcm9jZXNzUmVtb3ZlZE5vZGVzID0gZnVuY3Rpb24gKG5vZGVzKSB7XG4gICAgICAgIGZvciAodmFyIF9pID0gMCwgX2EgPSBBcnJheS5mcm9tKG5vZGVzKTsgX2kgPCBfYS5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgIHZhciBub2RlID0gX2FbX2ldO1xuICAgICAgICAgICAgdmFyIGVsZW1lbnQgPSB0aGlzLmVsZW1lbnRGcm9tTm9kZShub2RlKTtcbiAgICAgICAgICAgIGlmIChlbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgdGhpcy5wcm9jZXNzVHJlZShlbGVtZW50LCB0aGlzLnJlbW92ZUVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLnByb2Nlc3NBZGRlZE5vZGVzID0gZnVuY3Rpb24gKG5vZGVzKSB7XG4gICAgICAgIGZvciAodmFyIF9pID0gMCwgX2EgPSBBcnJheS5mcm9tKG5vZGVzKTsgX2kgPCBfYS5sZW5ndGg7IF9pKyspIHtcbiAgICAgICAgICAgIHZhciBub2RlID0gX2FbX2ldO1xuICAgICAgICAgICAgdmFyIGVsZW1lbnQgPSB0aGlzLmVsZW1lbnRGcm9tTm9kZShub2RlKTtcbiAgICAgICAgICAgIGlmIChlbGVtZW50ICYmIHRoaXMuZWxlbWVudElzQWN0aXZlKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5wcm9jZXNzVHJlZShlbGVtZW50LCB0aGlzLmFkZEVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICAvLyBFbGVtZW50IG1hdGNoaW5nXG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5tYXRjaEVsZW1lbnQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICByZXR1cm4gdGhpcy5kZWxlZ2F0ZS5tYXRjaEVsZW1lbnQoZWxlbWVudCk7XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLm1hdGNoRWxlbWVudHNJblRyZWUgPSBmdW5jdGlvbiAodHJlZSkge1xuICAgICAgICBpZiAodHJlZSA9PT0gdm9pZCAwKSB7IHRyZWUgPSB0aGlzLmVsZW1lbnQ7IH1cbiAgICAgICAgcmV0dXJuIHRoaXMuZGVsZWdhdGUubWF0Y2hFbGVtZW50c0luVHJlZSh0cmVlKTtcbiAgICB9O1xuICAgIEVsZW1lbnRPYnNlcnZlci5wcm90b3R5cGUucHJvY2Vzc1RyZWUgPSBmdW5jdGlvbiAodHJlZSwgcHJvY2Vzc29yKSB7XG4gICAgICAgIGZvciAodmFyIF9pID0gMCwgX2EgPSB0aGlzLm1hdGNoRWxlbWVudHNJblRyZWUodHJlZSk7IF9pIDwgX2EubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICB2YXIgZWxlbWVudCA9IF9hW19pXTtcbiAgICAgICAgICAgIHByb2Nlc3Nvci5jYWxsKHRoaXMsIGVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLmVsZW1lbnRGcm9tTm9kZSA9IGZ1bmN0aW9uIChub2RlKSB7XG4gICAgICAgIGlmIChub2RlLm5vZGVUeXBlID09IE5vZGUuRUxFTUVOVF9OT0RFKSB7XG4gICAgICAgICAgICByZXR1cm4gbm9kZTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50SXNBY3RpdmUgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICBpZiAoZWxlbWVudC5pc0Nvbm5lY3RlZCAhPSB0aGlzLmVsZW1lbnQuaXNDb25uZWN0ZWQpIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnQuY29udGFpbnMoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIC8vIEVsZW1lbnQgdHJhY2tpbmdcbiAgICBFbGVtZW50T2JzZXJ2ZXIucHJvdG90eXBlLmFkZEVsZW1lbnQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICBpZiAoIXRoaXMuZWxlbWVudHMuaGFzKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICBpZiAodGhpcy5lbGVtZW50SXNBY3RpdmUoZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmVsZW1lbnRzLmFkZChlbGVtZW50KTtcbiAgICAgICAgICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5lbGVtZW50TWF0Y2hlZCkge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRNYXRjaGVkKGVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgRWxlbWVudE9ic2VydmVyLnByb3RvdHlwZS5yZW1vdmVFbGVtZW50ID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgaWYgKHRoaXMuZWxlbWVudHMuaGFzKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICB0aGlzLmVsZW1lbnRzLmRlbGV0ZShlbGVtZW50KTtcbiAgICAgICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLmVsZW1lbnRVbm1hdGNoZWQpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRVbm1hdGNoZWQoZWxlbWVudCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIHJldHVybiBFbGVtZW50T2JzZXJ2ZXI7XG59KCkpO1xuZXhwb3J0IHsgRWxlbWVudE9ic2VydmVyIH07XG4vLyMgc291cmNlTWFwcGluZ1VSTD1lbGVtZW50X29ic2VydmVyLmpzLm1hcCIsImV4cG9ydCAqIGZyb20gXCIuL2F0dHJpYnV0ZV9vYnNlcnZlclwiO1xuZXhwb3J0ICogZnJvbSBcIi4vZWxlbWVudF9vYnNlcnZlclwiO1xuZXhwb3J0ICogZnJvbSBcIi4vc3RyaW5nX21hcF9vYnNlcnZlclwiO1xuZXhwb3J0ICogZnJvbSBcIi4vdG9rZW5fbGlzdF9vYnNlcnZlclwiO1xuZXhwb3J0ICogZnJvbSBcIi4vdmFsdWVfbGlzdF9vYnNlcnZlclwiO1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9aW5kZXguanMubWFwIiwidmFyIFN0cmluZ01hcE9ic2VydmVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIFN0cmluZ01hcE9ic2VydmVyKGVsZW1lbnQsIGRlbGVnYXRlKSB7XG4gICAgICAgIHZhciBfdGhpcyA9IHRoaXM7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMuc3RyaW5nTWFwID0gbmV3IE1hcDtcbiAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyID0gbmV3IE11dGF0aW9uT2JzZXJ2ZXIoZnVuY3Rpb24gKG11dGF0aW9ucykgeyByZXR1cm4gX3RoaXMucHJvY2Vzc011dGF0aW9ucyhtdXRhdGlvbnMpOyB9KTtcbiAgICB9XG4gICAgU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAoIXRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gdHJ1ZTtcbiAgICAgICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlci5vYnNlcnZlKHRoaXMuZWxlbWVudCwgeyBhdHRyaWJ1dGVzOiB0cnVlIH0pO1xuICAgICAgICAgICAgdGhpcy5yZWZyZXNoKCk7XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIudGFrZVJlY29yZHMoKTtcbiAgICAgICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlci5kaXNjb25uZWN0KCk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLnJlZnJlc2ggPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGZvciAodmFyIF9pID0gMCwgX2EgPSB0aGlzLmtub3duQXR0cmlidXRlTmFtZXM7IF9pIDwgX2EubGVuZ3RoOyBfaSsrKSB7XG4gICAgICAgICAgICAgICAgdmFyIGF0dHJpYnV0ZU5hbWUgPSBfYVtfaV07XG4gICAgICAgICAgICAgICAgdGhpcy5yZWZyZXNoQXR0cmlidXRlKGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfTtcbiAgICAvLyBNdXRhdGlvbiByZWNvcmQgcHJvY2Vzc2luZ1xuICAgIFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZS5wcm9jZXNzTXV0YXRpb25zID0gZnVuY3Rpb24gKG11dGF0aW9ucykge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICBmb3IgKHZhciBfaSA9IDAsIG11dGF0aW9uc18xID0gbXV0YXRpb25zOyBfaSA8IG11dGF0aW9uc18xLmxlbmd0aDsgX2krKykge1xuICAgICAgICAgICAgICAgIHZhciBtdXRhdGlvbiA9IG11dGF0aW9uc18xW19pXTtcbiAgICAgICAgICAgICAgICB0aGlzLnByb2Nlc3NNdXRhdGlvbihtdXRhdGlvbik7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZS5wcm9jZXNzTXV0YXRpb24gPSBmdW5jdGlvbiAobXV0YXRpb24pIHtcbiAgICAgICAgdmFyIGF0dHJpYnV0ZU5hbWUgPSBtdXRhdGlvbi5hdHRyaWJ1dGVOYW1lO1xuICAgICAgICBpZiAoYXR0cmlidXRlTmFtZSkge1xuICAgICAgICAgICAgdGhpcy5yZWZyZXNoQXR0cmlidXRlKGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICAvLyBTdGF0ZSB0cmFja2luZ1xuICAgIFN0cmluZ01hcE9ic2VydmVyLnByb3RvdHlwZS5yZWZyZXNoQXR0cmlidXRlID0gZnVuY3Rpb24gKGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgdmFyIGtleSA9IHRoaXMuZGVsZWdhdGUuZ2V0U3RyaW5nTWFwS2V5Rm9yQXR0cmlidXRlKGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICBpZiAoa2V5ICE9IG51bGwpIHtcbiAgICAgICAgICAgIGlmICghdGhpcy5zdHJpbmdNYXAuaGFzKGF0dHJpYnV0ZU5hbWUpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5zdHJpbmdNYXBLZXlBZGRlZChrZXksIGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgdmFyIHZhbHVlID0gdGhpcy5lbGVtZW50LmdldEF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgICAgIGlmICh0aGlzLnN0cmluZ01hcC5nZXQoYXR0cmlidXRlTmFtZSkgIT0gdmFsdWUpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnN0cmluZ01hcFZhbHVlQ2hhbmdlZCh2YWx1ZSwga2V5KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmICh2YWx1ZSA9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5zdHJpbmdNYXAuZGVsZXRlKGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICAgICAgICAgIHRoaXMuc3RyaW5nTWFwS2V5UmVtb3ZlZChrZXksIGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhpcy5zdHJpbmdNYXAuc2V0KGF0dHJpYnV0ZU5hbWUsIHZhbHVlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG4gICAgU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLnN0cmluZ01hcEtleUFkZGVkID0gZnVuY3Rpb24gKGtleSwgYXR0cmlidXRlTmFtZSkge1xuICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBLZXlBZGRlZCkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBLZXlBZGRlZChrZXksIGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUuc3RyaW5nTWFwVmFsdWVDaGFuZ2VkID0gZnVuY3Rpb24gKHZhbHVlLCBrZXkpIHtcbiAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuc3RyaW5nTWFwVmFsdWVDaGFuZ2VkKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLnN0cmluZ01hcFZhbHVlQ2hhbmdlZCh2YWx1ZSwga2V5KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLnN0cmluZ01hcEtleVJlbW92ZWQgPSBmdW5jdGlvbiAoa2V5LCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLnN0cmluZ01hcEtleVJlbW92ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuc3RyaW5nTWFwS2V5UmVtb3ZlZChrZXksIGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoU3RyaW5nTWFwT2JzZXJ2ZXIucHJvdG90eXBlLCBcImtub3duQXR0cmlidXRlTmFtZXNcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiBBcnJheS5mcm9tKG5ldyBTZXQodGhpcy5jdXJyZW50QXR0cmlidXRlTmFtZXMuY29uY2F0KHRoaXMucmVjb3JkZWRBdHRyaWJ1dGVOYW1lcykpKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUsIFwiY3VycmVudEF0dHJpYnV0ZU5hbWVzXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLmVsZW1lbnQuYXR0cmlidXRlcykubWFwKGZ1bmN0aW9uIChhdHRyaWJ1dGUpIHsgcmV0dXJuIGF0dHJpYnV0ZS5uYW1lOyB9KTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShTdHJpbmdNYXBPYnNlcnZlci5wcm90b3R5cGUsIFwicmVjb3JkZWRBdHRyaWJ1dGVOYW1lc1wiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5zdHJpbmdNYXAua2V5cygpKTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIHJldHVybiBTdHJpbmdNYXBPYnNlcnZlcjtcbn0oKSk7XG5leHBvcnQgeyBTdHJpbmdNYXBPYnNlcnZlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9c3RyaW5nX21hcF9vYnNlcnZlci5qcy5tYXAiLCJpbXBvcnQgeyBBdHRyaWJ1dGVPYnNlcnZlciB9IGZyb20gXCIuL2F0dHJpYnV0ZV9vYnNlcnZlclwiO1xuaW1wb3J0IHsgTXVsdGltYXAgfSBmcm9tIFwiQHN0aW11bHVzL211bHRpbWFwXCI7XG52YXIgVG9rZW5MaXN0T2JzZXJ2ZXIgPSAvKiogQGNsYXNzICovIChmdW5jdGlvbiAoKSB7XG4gICAgZnVuY3Rpb24gVG9rZW5MaXN0T2JzZXJ2ZXIoZWxlbWVudCwgYXR0cmlidXRlTmFtZSwgZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5hdHRyaWJ1dGVPYnNlcnZlciA9IG5ldyBBdHRyaWJ1dGVPYnNlcnZlcihlbGVtZW50LCBhdHRyaWJ1dGVOYW1lLCB0aGlzKTtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLnRva2Vuc0J5RWxlbWVudCA9IG5ldyBNdWx0aW1hcDtcbiAgICB9XG4gICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZSwgXCJzdGFydGVkXCIsIHtcbiAgICAgICAgZ2V0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5hdHRyaWJ1dGVPYnNlcnZlci5zdGFydGVkO1xuICAgICAgICB9LFxuICAgICAgICBlbnVtZXJhYmxlOiBmYWxzZSxcbiAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlXG4gICAgfSk7XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnN0YXJ0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyLnN0YXJ0KCk7XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUuc3RvcCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5hdHRyaWJ1dGVPYnNlcnZlci5zdG9wKCk7XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUucmVmcmVzaCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5hdHRyaWJ1dGVPYnNlcnZlci5yZWZyZXNoKCk7XG4gICAgfTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLCBcImVsZW1lbnRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyLmVsZW1lbnQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLCBcImF0dHJpYnV0ZU5hbWVcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyLmF0dHJpYnV0ZU5hbWU7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICAvLyBBdHRyaWJ1dGUgb2JzZXJ2ZXIgZGVsZWdhdGVcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudE1hdGNoZWRBdHRyaWJ1dGUgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICB0aGlzLnRva2Vuc01hdGNoZWQodGhpcy5yZWFkVG9rZW5zRm9yRWxlbWVudChlbGVtZW50KSk7XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUuZWxlbWVudEF0dHJpYnV0ZVZhbHVlQ2hhbmdlZCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHZhciBfYSA9IHRoaXMucmVmcmVzaFRva2Vuc0ZvckVsZW1lbnQoZWxlbWVudCksIHVubWF0Y2hlZFRva2VucyA9IF9hWzBdLCBtYXRjaGVkVG9rZW5zID0gX2FbMV07XG4gICAgICAgIHRoaXMudG9rZW5zVW5tYXRjaGVkKHVubWF0Y2hlZFRva2Vucyk7XG4gICAgICAgIHRoaXMudG9rZW5zTWF0Y2hlZChtYXRjaGVkVG9rZW5zKTtcbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS5lbGVtZW50VW5tYXRjaGVkQXR0cmlidXRlID0gZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgdGhpcy50b2tlbnNVbm1hdGNoZWQodGhpcy50b2tlbnNCeUVsZW1lbnQuZ2V0VmFsdWVzRm9yS2V5KGVsZW1lbnQpKTtcbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS50b2tlbnNNYXRjaGVkID0gZnVuY3Rpb24gKHRva2Vucykge1xuICAgICAgICB2YXIgX3RoaXMgPSB0aGlzO1xuICAgICAgICB0b2tlbnMuZm9yRWFjaChmdW5jdGlvbiAodG9rZW4pIHsgcmV0dXJuIF90aGlzLnRva2VuTWF0Y2hlZCh0b2tlbik7IH0pO1xuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnRva2Vuc1VubWF0Y2hlZCA9IGZ1bmN0aW9uICh0b2tlbnMpIHtcbiAgICAgICAgdmFyIF90aGlzID0gdGhpcztcbiAgICAgICAgdG9rZW5zLmZvckVhY2goZnVuY3Rpb24gKHRva2VuKSB7IHJldHVybiBfdGhpcy50b2tlblVubWF0Y2hlZCh0b2tlbik7IH0pO1xuICAgIH07XG4gICAgVG9rZW5MaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnRva2VuTWF0Y2hlZCA9IGZ1bmN0aW9uICh0b2tlbikge1xuICAgICAgICB0aGlzLmRlbGVnYXRlLnRva2VuTWF0Y2hlZCh0b2tlbik7XG4gICAgICAgIHRoaXMudG9rZW5zQnlFbGVtZW50LmFkZCh0b2tlbi5lbGVtZW50LCB0b2tlbik7XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUudG9rZW5Vbm1hdGNoZWQgPSBmdW5jdGlvbiAodG9rZW4pIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS50b2tlblVubWF0Y2hlZCh0b2tlbik7XG4gICAgICAgIHRoaXMudG9rZW5zQnlFbGVtZW50LmRlbGV0ZSh0b2tlbi5lbGVtZW50LCB0b2tlbik7XG4gICAgfTtcbiAgICBUb2tlbkxpc3RPYnNlcnZlci5wcm90b3R5cGUucmVmcmVzaFRva2Vuc0ZvckVsZW1lbnQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICB2YXIgcHJldmlvdXNUb2tlbnMgPSB0aGlzLnRva2Vuc0J5RWxlbWVudC5nZXRWYWx1ZXNGb3JLZXkoZWxlbWVudCk7XG4gICAgICAgIHZhciBjdXJyZW50VG9rZW5zID0gdGhpcy5yZWFkVG9rZW5zRm9yRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgdmFyIGZpcnN0RGlmZmVyaW5nSW5kZXggPSB6aXAocHJldmlvdXNUb2tlbnMsIGN1cnJlbnRUb2tlbnMpXG4gICAgICAgICAgICAuZmluZEluZGV4KGZ1bmN0aW9uIChfYSkge1xuICAgICAgICAgICAgdmFyIHByZXZpb3VzVG9rZW4gPSBfYVswXSwgY3VycmVudFRva2VuID0gX2FbMV07XG4gICAgICAgICAgICByZXR1cm4gIXRva2Vuc0FyZUVxdWFsKHByZXZpb3VzVG9rZW4sIGN1cnJlbnRUb2tlbik7XG4gICAgICAgIH0pO1xuICAgICAgICBpZiAoZmlyc3REaWZmZXJpbmdJbmRleCA9PSAtMSkge1xuICAgICAgICAgICAgcmV0dXJuIFtbXSwgW11dO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIFtwcmV2aW91c1Rva2Vucy5zbGljZShmaXJzdERpZmZlcmluZ0luZGV4KSwgY3VycmVudFRva2Vucy5zbGljZShmaXJzdERpZmZlcmluZ0luZGV4KV07XG4gICAgICAgIH1cbiAgICB9O1xuICAgIFRva2VuTGlzdE9ic2VydmVyLnByb3RvdHlwZS5yZWFkVG9rZW5zRm9yRWxlbWVudCA9IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHZhciBhdHRyaWJ1dGVOYW1lID0gdGhpcy5hdHRyaWJ1dGVOYW1lO1xuICAgICAgICB2YXIgdG9rZW5TdHJpbmcgPSBlbGVtZW50LmdldEF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lKSB8fCBcIlwiO1xuICAgICAgICByZXR1cm4gcGFyc2VUb2tlblN0cmluZyh0b2tlblN0cmluZywgZWxlbWVudCwgYXR0cmlidXRlTmFtZSk7XG4gICAgfTtcbiAgICByZXR1cm4gVG9rZW5MaXN0T2JzZXJ2ZXI7XG59KCkpO1xuZXhwb3J0IHsgVG9rZW5MaXN0T2JzZXJ2ZXIgfTtcbmZ1bmN0aW9uIHBhcnNlVG9rZW5TdHJpbmcodG9rZW5TdHJpbmcsIGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUpIHtcbiAgICByZXR1cm4gdG9rZW5TdHJpbmcudHJpbSgpLnNwbGl0KC9cXHMrLykuZmlsdGVyKGZ1bmN0aW9uIChjb250ZW50KSB7IHJldHVybiBjb250ZW50Lmxlbmd0aDsgfSlcbiAgICAgICAgLm1hcChmdW5jdGlvbiAoY29udGVudCwgaW5kZXgpIHsgcmV0dXJuICh7IGVsZW1lbnQ6IGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWU6IGF0dHJpYnV0ZU5hbWUsIGNvbnRlbnQ6IGNvbnRlbnQsIGluZGV4OiBpbmRleCB9KTsgfSk7XG59XG5mdW5jdGlvbiB6aXAobGVmdCwgcmlnaHQpIHtcbiAgICB2YXIgbGVuZ3RoID0gTWF0aC5tYXgobGVmdC5sZW5ndGgsIHJpZ2h0Lmxlbmd0aCk7XG4gICAgcmV0dXJuIEFycmF5LmZyb20oeyBsZW5ndGg6IGxlbmd0aCB9LCBmdW5jdGlvbiAoXywgaW5kZXgpIHsgcmV0dXJuIFtsZWZ0W2luZGV4XSwgcmlnaHRbaW5kZXhdXTsgfSk7XG59XG5mdW5jdGlvbiB0b2tlbnNBcmVFcXVhbChsZWZ0LCByaWdodCkge1xuICAgIHJldHVybiBsZWZ0ICYmIHJpZ2h0ICYmIGxlZnQuaW5kZXggPT0gcmlnaHQuaW5kZXggJiYgbGVmdC5jb250ZW50ID09IHJpZ2h0LmNvbnRlbnQ7XG59XG4vLyMgc291cmNlTWFwcGluZ1VSTD10b2tlbl9saXN0X29ic2VydmVyLmpzLm1hcCIsImltcG9ydCB7IFRva2VuTGlzdE9ic2VydmVyIH0gZnJvbSBcIi4vdG9rZW5fbGlzdF9vYnNlcnZlclwiO1xudmFyIFZhbHVlTGlzdE9ic2VydmVyID0gLyoqIEBjbGFzcyAqLyAoZnVuY3Rpb24gKCkge1xuICAgIGZ1bmN0aW9uIFZhbHVlTGlzdE9ic2VydmVyKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUsIGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMudG9rZW5MaXN0T2JzZXJ2ZXIgPSBuZXcgVG9rZW5MaXN0T2JzZXJ2ZXIoZWxlbWVudCwgYXR0cmlidXRlTmFtZSwgdGhpcyk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5wYXJzZVJlc3VsdHNCeVRva2VuID0gbmV3IFdlYWtNYXA7XG4gICAgICAgIHRoaXMudmFsdWVzQnlUb2tlbkJ5RWxlbWVudCA9IG5ldyBXZWFrTWFwO1xuICAgIH1cbiAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkoVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLCBcInN0YXJ0ZWRcIiwge1xuICAgICAgICBnZXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnRva2VuTGlzdE9ic2VydmVyLnN0YXJ0ZWQ7XG4gICAgICAgIH0sXG4gICAgICAgIGVudW1lcmFibGU6IGZhbHNlLFxuICAgICAgICBjb25maWd1cmFibGU6IHRydWVcbiAgICB9KTtcbiAgICBWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUuc3RhcnQgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHRoaXMudG9rZW5MaXN0T2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICB9O1xuICAgIFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZS5zdG9wID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnRva2VuTGlzdE9ic2VydmVyLnN0b3AoKTtcbiAgICB9O1xuICAgIFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZS5yZWZyZXNoID0gZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnRva2VuTGlzdE9ic2VydmVyLnJlZnJlc2goKTtcbiAgICB9O1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUsIFwiZWxlbWVudFwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMudG9rZW5MaXN0T2JzZXJ2ZXIuZWxlbWVudDtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eShWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUsIFwiYXR0cmlidXRlTmFtZVwiLCB7XG4gICAgICAgIGdldDogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMudG9rZW5MaXN0T2JzZXJ2ZXIuYXR0cmlidXRlTmFtZTtcbiAgICAgICAgfSxcbiAgICAgICAgZW51bWVyYWJsZTogZmFsc2UsXG4gICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZVxuICAgIH0pO1xuICAgIFZhbHVlTGlzdE9ic2VydmVyLnByb3RvdHlwZS50b2tlbk1hdGNoZWQgPSBmdW5jdGlvbiAodG9rZW4pIHtcbiAgICAgICAgdmFyIGVsZW1lbnQgPSB0b2tlbi5lbGVtZW50O1xuICAgICAgICB2YXIgdmFsdWUgPSB0aGlzLmZldGNoUGFyc2VSZXN1bHRGb3JUb2tlbih0b2tlbikudmFsdWU7XG4gICAgICAgIGlmICh2YWx1ZSkge1xuICAgICAgICAgICAgdGhpcy5mZXRjaFZhbHVlc0J5VG9rZW5Gb3JFbGVtZW50KGVsZW1lbnQpLnNldCh0b2tlbiwgdmFsdWUpO1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5lbGVtZW50TWF0Y2hlZFZhbHVlKGVsZW1lbnQsIHZhbHVlKTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnRva2VuVW5tYXRjaGVkID0gZnVuY3Rpb24gKHRva2VuKSB7XG4gICAgICAgIHZhciBlbGVtZW50ID0gdG9rZW4uZWxlbWVudDtcbiAgICAgICAgdmFyIHZhbHVlID0gdGhpcy5mZXRjaFBhcnNlUmVzdWx0Rm9yVG9rZW4odG9rZW4pLnZhbHVlO1xuICAgICAgICBpZiAodmFsdWUpIHtcbiAgICAgICAgICAgIHRoaXMuZmV0Y2hWYWx1ZXNCeVRva2VuRm9yRWxlbWVudChlbGVtZW50KS5kZWxldGUodG9rZW4pO1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5lbGVtZW50VW5tYXRjaGVkVmFsdWUoZWxlbWVudCwgdmFsdWUpO1xuICAgICAgICB9XG4gICAgfTtcbiAgICBWYWx1ZUxpc3RPYnNlcnZlci5wcm90b3R5cGUuZmV0Y2hQYXJzZVJlc3VsdEZvclRva2VuID0gZnVuY3Rpb24gKHRva2VuKSB7XG4gICAgICAgIHZhciBwYXJzZVJlc3VsdCA9IHRoaXMucGFyc2VSZXN1bHRzQnlUb2tlbi5nZXQodG9rZW4pO1xuICAgICAgICBpZiAoIXBhcnNlUmVzdWx0KSB7XG4gICAgICAgICAgICBwYXJzZVJlc3VsdCA9IHRoaXMucGFyc2VUb2tlbih0b2tlbik7XG4gICAgICAgICAgICB0aGlzLnBhcnNlUmVzdWx0c0J5VG9rZW4uc2V0KHRva2VuLCBwYXJzZVJlc3VsdCk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHBhcnNlUmVzdWx0O1xuICAgIH07XG4gICAgVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLmZldGNoVmFsdWVzQnlUb2tlbkZvckVsZW1lbnQgPSBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICB2YXIgdmFsdWVzQnlUb2tlbiA9IHRoaXMudmFsdWVzQnlUb2tlbkJ5RWxlbWVudC5nZXQoZWxlbWVudCk7XG4gICAgICAgIGlmICghdmFsdWVzQnlUb2tlbikge1xuICAgICAgICAgICAgdmFsdWVzQnlUb2tlbiA9IG5ldyBNYXA7XG4gICAgICAgICAgICB0aGlzLnZhbHVlc0J5VG9rZW5CeUVsZW1lbnQuc2V0KGVsZW1lbnQsIHZhbHVlc0J5VG9rZW4pO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiB2YWx1ZXNCeVRva2VuO1xuICAgIH07XG4gICAgVmFsdWVMaXN0T2JzZXJ2ZXIucHJvdG90eXBlLnBhcnNlVG9rZW4gPSBmdW5jdGlvbiAodG9rZW4pIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHZhciB2YWx1ZSA9IHRoaXMuZGVsZWdhdGUucGFyc2VWYWx1ZUZvclRva2VuKHRva2VuKTtcbiAgICAgICAgICAgIHJldHVybiB7IHZhbHVlOiB2YWx1ZSB9O1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgcmV0dXJuIHsgZXJyb3I6IGVycm9yIH07XG4gICAgICAgIH1cbiAgICB9O1xuICAgIHJldHVybiBWYWx1ZUxpc3RPYnNlcnZlcjtcbn0oKSk7XG5leHBvcnQgeyBWYWx1ZUxpc3RPYnNlcnZlciB9O1xuLy8jIHNvdXJjZU1hcHBpbmdVUkw9dmFsdWVfbGlzdF9vYnNlcnZlci5qcy5tYXAiLCIvKlxuICogVGhpcyBmaWxlIGlzIHBhcnQgb2YgdGhlIFN5bWZvbnkgVVggVHVyYm8gcGFja2FnZS5cbiAqXG4gKiAoYykgS8OpdmluIER1bmdsYXMgPGtldmluQGR1bmdsYXMuZnI+XG4gKlxuICogRm9yIHRoZSBmdWxsIGNvcHlyaWdodCBhbmQgbGljZW5zZSBpbmZvcm1hdGlvbiwgcGxlYXNlIHZpZXcgdGhlIExJQ0VOU0VcbiAqIGZpbGUgdGhhdCB3YXMgZGlzdHJpYnV0ZWQgd2l0aCB0aGlzIHNvdXJjZSBjb2RlLlxuICovXG5cbmltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tIFwic3RpbXVsdXNcIjtcbmltcG9ydCB7IGNvbm5lY3RTdHJlYW1Tb3VyY2UsIGRpc2Nvbm5lY3RTdHJlYW1Tb3VyY2UgfSBmcm9tIFwiQGhvdHdpcmVkL3R1cmJvXCI7XG5cbi8qKlxuICogQGF1dGhvciBLw6l2aW4gRHVuZ2xhcyA8a2V2aW5AZHVuZ2xhcy5mcj5cbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbiAgaW5pdGlhbGl6ZSgpIHtcbiAgICBjb25zdCB0b3BpYyA9IHRoaXMuZWxlbWVudC5nZXRBdHRyaWJ1dGUoXCJkYXRhLXRvcGljXCIpO1xuICAgIGlmICghdG9waWMpIHtcbiAgICAgIGNvbnNvbGUuZXJyb3IoYFRoZSBlbGVtZW50IG11c3QgaGF2ZSBhIFwiZGF0YS10b3BpY1wiIGF0dHJpYnV0ZS5gKTtcbiAgICAgIHJldHVybjtcbiAgICB9XG5cbiAgICBjb25zdCBodWIgPSB0aGlzLmVsZW1lbnQuZ2V0QXR0cmlidXRlKFwiZGF0YS1odWJcIik7XG4gICAgaWYgKCFodWIpIHtcbiAgICAgIGNvbnNvbGUuZXJyb3IoYFRoZSBlbGVtZW50IG11c3QgaGF2ZSBhIFwiZGF0YS1odWJcIiBhdHRyaWJ1dGUgcG9pbnRpbmcgdG8gdGhlIE1lcmN1cmUgaHViLmApO1xuICAgICAgcmV0dXJuO1xuICAgIH1cblxuICAgIGNvbnN0IHUgPSBuZXcgVVJMKGh1Yik7XG4gICAgdS5zZWFyY2hQYXJhbXMuYXBwZW5kKFwidG9waWNcIiwgdG9waWMpO1xuXG4gICAgdGhpcy51cmwgPSB1LnRvU3RyaW5nKCk7XG4gIH1cblxuICBjb25uZWN0KCkge1xuICAgIHRoaXMuZXMgPSBuZXcgRXZlbnRTb3VyY2UodGhpcy51cmwpO1xuICAgIGNvbm5lY3RTdHJlYW1Tb3VyY2UodGhpcy5lcyk7XG4gIH1cblxuICBkaXNjb25uZWN0KCkge1xuICAgIHRoaXMuZXMuY2xvc2UoKTtcbiAgICBkaXNjb25uZWN0U3RyZWFtU291cmNlKHRoaXMuZXMpO1xuICB9XG59XG4iLCJleHBvcnQgKiBmcm9tIFwiQHN0aW11bHVzL2NvcmVcIlxuIl0sInNvdXJjZVJvb3QiOiIifQ==