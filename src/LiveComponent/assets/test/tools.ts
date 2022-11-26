import { Application } from '@hotwired/stimulus';
import LiveController from '../src/live_controller';
import { waitFor } from '@testing-library/dom';
import fetchMock from 'fetch-mock-jest';
import { htmlToElement } from '../src/dom_utils';
import Component from '../src/Component';

let activeTests: FunctionalTest[] = [];
let unmatchedFetchErrors: Array<{url: string, method: string, body: any, headers: any}> = [];

// manually error on unmatched request for readability
fetchMock.config.warnOnFallback = false;
fetchMock.catch((url: string, response: any) => {
    unmatchedFetchErrors.push({
        url,
        method: response.method,
        body: response.body,
        headers: response.headers,
    });

    return response;
});

let application: Application;

export function shutdownTests() {
    if (activeTests.length === 0) {
        // no test was run, apparently
        return;
    }

    const tests = activeTests;
    activeTests = [];
    tests.forEach((test) => {
        shutdownTest(test);
    });

    // only possible if someone uses fetchMock directly, but here just in case
    if (!fetchMock.done()) {
        fetchMock.reset();

        throw new Error('Some mocked requests were never called.');
    }

    fetchMock.reset();
}

const shutdownTest = function(test: FunctionalTest) {
    unmatchedFetchErrors.forEach((unmatchedFetchError) => {
        const urlParams = new URLSearchParams(unmatchedFetchError.url.substring(unmatchedFetchError.url.indexOf('?')));
        const requestInfo = [];
        requestInfo.push(` URL: ${unmatchedFetchError.url}`)
        requestInfo.push(`  METHOD: ${unmatchedFetchError.method}`);
        requestInfo.push(`  HEADERS: ${JSON.stringify(unmatchedFetchError.headers)}`);
        requestInfo.push(`  DATA: ${unmatchedFetchError.method === 'GET' ? urlParams.get('data') : unmatchedFetchError.body}`);

        console.log('UNMATCHED request was made with the following info:', '\n', requestInfo.join('\n'));
    });
    unmatchedFetchErrors = [];

    let allMocksRequestsCalled = true;
    test.mockedAjaxCalls.forEach((mock => {
        if (!mock.fetchMock) {
            throw new Error('You must call .init() after calling expectsAjaxCall() to fully initialize the mock Ajax call.')
        }

        if (!fetchMock.called(mock.routeName)) {
            console.log('EXPECTED request was never made matching the following info:', '\n', mock.getVisualSummary());
            allMocksRequestsCalled = false;
        }
    }));

    // this + the above warnings - has a nicer output than using "fetchMock.done()".
    if (!allMocksRequestsCalled || unmatchedFetchErrors.length > 0) {
        fetchMock.reset();

        throw new Error('Some mocked requests were never called or unexpected calls were made.');
    }
}

class FunctionalTest {
    component: Component;
    element: HTMLElement;
    initialData: any;
    props: any = {};
    template: (data: any) => string;
    mockedAjaxCalls: Array<MockedAjaxCall> = [];
    id: number;

    constructor(component: Component, element: HTMLElement, initialData: any, template: (data: any) => string) {
        this.component = component;
        this.element = element;
        this.initialData = initialData;
        this.template = template;
        this.id = Math.floor(1000*Math.random());
    }

    expectsAjaxCall = (method: string): MockedAjaxCall => {
        const mock = new MockedAjaxCall(method, this);
        this.mockedAjaxCalls.push(mock);

        return mock;
    }

    queryByDataModel(modelName: string): HTMLElement {
        const elements = this.element.querySelectorAll(`[data-model$="${modelName}"]`);
        let matchedElement: null|Element = null;

        // skip any elements that are actually controllers
        // these are child component bindings, not real fields
        elements.forEach((element) => {
            if (!element.hasAttribute('data-controller')) {
                matchedElement = element;
            }
        });

        if (!matchedElement) {
            throw new Error(`Could not find element with data-model="${modelName}" inside ${this.element.outerHTML}`);
        }

        return matchedElement as HTMLElement;
    }

    queryByNameAttribute(modelName: string): HTMLElement {
        const element = this.element.querySelector(`[name="${modelName}"]`);
        if (!element) {
            throw new Error(`Could not find element with name="${modelName}"`);
        }

        return element as HTMLElement;
    }
}
class MockedAjaxCall {
    method: string;
    test: FunctionalTest;
    private expectedSentData?: any;
    private expectedActions: Array<{ name: string, args: any }> = [];
    private expectedHeaders: any = {};
    private expectedChildFingerprints: any = null;
    private changeDataCallback?: (data: any) => void;
    private template?: (data: any) => string
    options: any = {};
    fetchMock?: typeof fetchMock;
    routeName?: string;
    customResponseStatusCode?: number;
    customResponseHTML?: string;

    constructor(method: string, test: FunctionalTest) {
        this.method = method.toUpperCase();
        this.test = test;
    }

    /**
     * Pass the "data" that is expected to be sent on the Ajax request
     */
    expectSentData = (data: any): MockedAjaxCall => {
        this.checkInitialization('expectSentData');

        this.expectedSentData = data;

        return this;
    }

    expectChildFingerprints = (fingerprints: any): MockedAjaxCall => {
        this.checkInitialization('expectSentData');

        this.expectedChildFingerprints = fingerprints;

        return this;
    }

    /**
     * Call if the "server" will change the data before it re-renders
     */
    serverWillChangeData = (callback: (data: any) => void): MockedAjaxCall => {
        this.checkInitialization('serverWillChangeData');
        this.changeDataCallback = callback;

        return this;
    }

    delayResponse = (milliseconds: number): MockedAjaxCall => {
        this.checkInitialization('delayResponse');
        this.options.delay = milliseconds;

        return this;
    }

    expectActionCalled(actionName: string, args: any = {}): MockedAjaxCall {
        this.checkInitialization('expectActionName');
        this.expectedActions.push({
            name: actionName,
            args: args
        })

        return this;
    }

    init = (): void => {
        if (this.fetchMock) {
            throw new Error('Cannot call call init() multiple times.');
        }

        if (!this.expectedSentData) {
            throw new Error('expectSentData() must be called before init().')
        }

        const finalServerData = JSON.parse(JSON.stringify(this.expectedSentData));

        if (this.changeDataCallback) {
            this.changeDataCallback(finalServerData);
        }

        // use custom template, or the main one
        const template = this.template ? this.template : this.test.template;

        let response;
        if (this.customResponseStatusCode) {
            response = {
                body: this.customResponseHTML,
                status: this.customResponseStatusCode
            }
        } else {
            response = {
                body: template(finalServerData),
                headers: {
                    'Content-Type': 'application/vnd.live-component+html'
                }
            }
        }

        this.fetchMock = fetchMock.mock(
            this.getMockMatcher(),
            response,
            this.options
        );
    }

    willReturn(template: (data: any) => string): MockedAjaxCall {
        this.checkInitialization('willReturn');
        this.template = template;

        return this;
    }

    expectHeader(headerName: string, value: string): MockedAjaxCall {
        this.checkInitialization('expectHeader');
        this.expectedHeaders[headerName] = value;

        return this;
    }

    serverWillReturnCustomResponse(statusCode: number, responseHTML: string): MockedAjaxCall {
        this.checkInitialization('serverWillReturnAnError');
        this.customResponseStatusCode = statusCode;
        this.customResponseHTML = responseHTML;

        return this;
    }

    getVisualSummary(): string {
        const requestInfo = [];
        if (this.method === 'GET') {
            requestInfo.push(` URL MATCH: end:${this.getMockMatcher(true).url}`);
        }
        requestInfo.push(`  METHOD: ${this.method}`);
        if (Object.keys(this.expectedHeaders).length > 0) {
            requestInfo.push(`  HEADERS: ${JSON.stringify(this.expectedHeaders)}`);
        }
        if (this.method === 'GET') {
            requestInfo.push(`  DATA: ${JSON.stringify(this.expectedSentData)}`);
        } else {
            requestInfo.push(`  DATA: ${JSON.stringify(this.getRequestBody())}`);
        }

        if (this.expectedChildFingerprints) {
            requestInfo.push(`  CHILD FINGERPRINTS: ${JSON.stringify(this.expectedChildFingerprints)}`)
        }

        if (this.expectedActions.length === 1) {
            requestInfo.push(`  Expected URL to contain action /${this.expectedActions[0].name}`)
        }

        return requestInfo.join('\n');
    }

    // https://www.wheresrhys.co.uk/fetch-mock/#api-mockingmock_matcher
    private getMockMatcher(createMatchForShowingError = false): any {
        if (!this.expectedSentData) {
            throw new Error('expectedSentData not set yet');
        }

        const matcherObject: any = { method: this.method };

        if (Object.keys(this.expectedHeaders).length > 0) {
            matcherObject.headers = this.expectedHeaders;
        }

        if (this.method === 'GET') {
            const paramsData: any = {
                data: JSON.stringify(this.expectedSentData)
            };
            if (this.expectedChildFingerprints) {
                paramsData.childrenFingerprints = JSON.stringify(this.expectedChildFingerprints);
            }
            const params = new URLSearchParams(paramsData);
            if (createMatchForShowingError) {
                // simplified version for error reporting
                matcherObject.url = `(approximation) ?${params.toString()}`;
            } else {
                matcherObject.functionMatcher = (url: string) => {
                    const actualUrl = new URL(url);
                    const actualParams = new URLSearchParams(actualUrl.search);
                    actualParams.delete('updatedModels[]');
                    // if we're not expecting specific child fingerprints, ignore them
                    if (!paramsData.childrenFingerprints) {
                        actualParams.delete('childrenFingerprints');
                    }

                    return actualParams.toString() === params.toString();
                };
            }
        } else {
            // match the body, by without "updatedModels" which is not important
            // and also difficult/tedious to always assert
            matcherObject.functionMatcher = (url: string, request: any) => {
                const body = JSON.parse(request.body);
                delete body.updatedModels;

                return JSON.stringify(body) === JSON.stringify(this.getRequestBody());
            };

            if (this.expectedActions.length === 1) {
                matcherObject.url = `end:/${this.expectedActions[0].name}`;
            } else if (this.expectedActions.length > 1) {
                matcherObject.url = 'end:/_batch';
            }
        }

        this.routeName = `route-${this.test.id}-${this.test.mockedAjaxCalls.length}`;
        matcherObject.name = this.routeName;

        return matcherObject;
    }

    private getRequestBody(): any {
        if (this.method === 'GET') {
            return null;
        }

        const body: any = {
            data: this.expectedSentData
        };

        if (this.expectedChildFingerprints) {
            body.childrenFingerprints = this.expectedChildFingerprints;
        }

        if (this.expectedActions.length === 1) {
            body.args = this.expectedActions[0].args;
        } else if (this.expectedActions.length > 1) {
            body.actions = this.expectedActions;
        }

        return body;
    }

    private checkInitialization = (method: string): void => {
        if (this.fetchMock) {
            throw new Error(`Cannot call ${method}() after MockedAjaxCall is initialized`);
        }
    }
}

export async function createTest(data: any, template: (data: any) => string): Promise<FunctionalTest> {
    const testData = await startStimulus(template(data));

    const test = new FunctionalTest(testData.controller.component, testData.element, data, template);
    activeTests.push(test);

    return test;
}

/**
 * An internal way to create a FunctionalTest: useful for child components
 */
export function createTestForExistingComponent(component: Component): FunctionalTest {
    const test = new FunctionalTest(component, component.element, {}, () => '');
    activeTests.push(test);

    return test;
}

export async function startStimulus(element: string|HTMLElement) {
    // start the Stimulus app just once per test suite
    if (!application) {
        application = Application.start();
        application.register('live', LiveController);
    }

    if (!(element instanceof HTMLElement)) {
        element = htmlToElement(element);
    }
    document.body.innerHTML = '';
    document.body.appendChild(element);

    const controllerElement = getControllerElement(element);

    await waitFor(() => application.getControllerForElementAndIdentifier(controllerElement, 'live'));
    const controller = application.getControllerForElementAndIdentifier(controllerElement, 'live') as LiveController;

    return {
        controller,
        element: controllerElement
    }
}

const getControllerElement = (container: HTMLElement): HTMLElement => {
    if (container.dataset.controller === 'live') {
        return container;
    }

    const element = container.querySelector('[data-controller="live"]');

    if (!element || !(element instanceof HTMLElement)) {
        throw new Error('Could not find controller element');
    }

    return element;
};

const dataToJsonAttribute = (data: any): string => {
    const container = document.createElement('div');
    container.dataset.foo = JSON.stringify(data);

    const matches = container.outerHTML.match(/data-foo="(.+)"/);

    if (!matches) {
        throw new Error('Match is missing');
    }

    // returns the now-escaped string, ready to be used in an HTML attribute
    return matches[1]
}

export function initComponent(data: any, props: any = {}, controllerValues: any = {}) {
    return `
        data-controller="live"
        data-live-url-value="http://localhost/components/_test_component_${Math.round(Math.random() * 1000)}"
        data-live-data-value="${dataToJsonAttribute(data)}"
        data-live-props-value="${dataToJsonAttribute(props)}"
        ${controllerValues.debounce ? `data-live-debounce-value="${controllerValues.debounce}"` : ''}
        ${controllerValues.csrf ? `data-live-csrf-value="${controllerValues.csrf}"` : ''}
        ${controllerValues.id ? `data-live-id="${controllerValues.id}"` : ''}
        ${controllerValues.fingerprint ? `data-live-fingerprint-value="${controllerValues.fingerprint}"` : ''}
    `;
}

export function getComponent(element: HTMLElement|null) {
    if (!element) {
        throw new Error('could not find element');
    }

    // @ts-ignore
    const component = element.__component;
    if (!(component instanceof Component)) {
        throw new Error('missing component');
    }

    return component;
}
