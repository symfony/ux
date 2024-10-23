import { Application } from '@hotwired/stimulus';
import LiveController from '../src/live_controller';
import { waitFor } from '@testing-library/dom';
import { htmlToElement } from '../src/dom_utils';
import Component from '../src/Component';
import type { BackendAction, BackendInterface, ChildrenFingerprints } from '../src/Backend/Backend';
import BackendRequest from '../src/Backend/BackendRequest';
import { Response } from 'node-fetch';
import { setDeepData } from '../src/data_manipulation_utils';
import LiveControllerDefault from '../src/live_controller';
import type { ElementDriver } from '../src/Component/ElementDriver';

let activeTests: FunctionalTest[] = [];

let application: Application;

export function shutdownTests() {
    if (application) {
        application.stop();
    }

    if (activeTests.length === 0) {
        // no test was run, apparently
        return;
    }

    const tests = activeTests;
    activeTests = [];
    tests.forEach((test) => {
        shutdownTest(test);
    });
}

const shutdownTest = (test: FunctionalTest) => {
    test.pendingAjaxCallsThatAreStillExpected().forEach((mock) => {
        const requestInfo = mock.getVisualSummary();
        throw new Error(`EXPECTED request was never made matching the following info: \n${requestInfo.join('\n')}`);
    });
};

class FunctionalTest {
    component: Component;
    element: HTMLElement;
    template: (props: any) => string;
    mockedBackend: MockedBackend;

    constructor(
        component: Component,
        element: HTMLElement,
        mockedBackend: MockedBackend,
        template: (props: any) => string
    ) {
        this.component = component;
        this.element = element;
        this.mockedBackend = mockedBackend;
        this.template = template;
    }

    expectsAjaxCall = (): MockedAjaxCall => {
        const mock = new MockedAjaxCall(this);
        this.mockedBackend.addMockedAjaxCall(mock);

        return mock;
    };

    queryByDataModel(modelName: string): HTMLElement {
        const elements = this.element.querySelectorAll(`[data-model$="${modelName}"]`);
        let matchedElement: null | Element = null;

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

    pendingAjaxCallsThatAreStillExpected(): Array<MockedAjaxCall> {
        return this.mockedBackend.getExpectedMockedAjaxCalls();
    }
}
class MockedBackend implements BackendInterface {
    private expectedMockedAjaxCalls: Array<MockedAjaxCall> = [];

    addMockedAjaxCall(mock: MockedAjaxCall) {
        this.expectedMockedAjaxCalls.push(mock);
    }

    makeRequest(
        props: any,
        actions: BackendAction[],
        updated: { [key: string]: any },
        children: ChildrenFingerprints,
        updatedPropsFromParent: { [key: string]: any }
    ): BackendRequest {
        const matchedMock = this.findMatchingMock(props, actions, updated, children, updatedPropsFromParent);

        if (!matchedMock) {
            const requestInfo = [];
            requestInfo.push('ACTUAL REQUEST INFO: ');
            requestInfo.push(`  PROPS: ${JSON.stringify(props)}`);
            requestInfo.push(`  ACTIONS: ${JSON.stringify(actions)}`);
            requestInfo.push(`  UPDATED: ${JSON.stringify(updated)}`);
            requestInfo.push(`  CHILDREN: ${JSON.stringify(children)}`);
            requestInfo.push(`  UPDATED PROPS FROM PARENT: ${JSON.stringify(updatedPropsFromParent)}`);

            requestInfo.push('');
            if (this.expectedMockedAjaxCalls.length === 0) {
                requestInfo.push('No mocked Ajax calls were expected.');
            } else {
                this.expectedMockedAjaxCalls.forEach((mock) => {
                    requestInfo.push(`EXPECTED REQUEST #${this.expectedMockedAjaxCalls.indexOf(mock) + 1}:`);
                    requestInfo.push(...mock.getVisualSummary());
                });
            }

            throw new Error(`An AJAX call was made that was not expected: \n${requestInfo.join('\n')}`);
        }

        // remove the matched mock from the list of expected calls
        this.expectedMockedAjaxCalls.splice(this.expectedMockedAjaxCalls.indexOf(matchedMock), 1);

        return matchedMock.createBackendRequest();
    }

    getExpectedMockedAjaxCalls(): Array<MockedAjaxCall> {
        return this.expectedMockedAjaxCalls;
    }

    private findMatchingMock(
        props: any,
        actions: BackendAction[],
        updated: { [key: string]: any },
        children: ChildrenFingerprints,
        updatedPropsFromParent: { [key: string]: any }
    ): MockedAjaxCall | null {
        for (let i = 0; i < this.expectedMockedAjaxCalls.length; i++) {
            const mock = this.expectedMockedAjaxCalls[i];
            if (mock.matches(props, actions, updated, children, updatedPropsFromParent)) {
                return mock;
            }
        }

        return null;
    }
}

class MockedAjaxCall {
    private test: FunctionalTest;

    /* Matcher properties */
    private expectedActions: Array<{ name: string; args: any }> = [];
    private expectedSentUpdatedData: { [key: string]: any } = {};
    private expectedChildFingerprints: ChildrenFingerprints | null = null;
    private expectedUpdatedPropsFromParent: { [key: string]: any } | null = null;

    /* Response properties */
    private changePropsCallback?: (props: any) => void;
    private template?: (props: any) => string;
    private delayResponseTime?: number = 0;
    private customResponseStatusCode?: number;
    private customResponseHTML?: string;

    constructor(test: FunctionalTest) {
        this.test = test;
    }

    getVisualSummary(): string[] {
        const requestInfo = [];
        requestInfo.push(`  PROPS: ${JSON.stringify(this.test.component.valueStore.getOriginalProps())}`);
        requestInfo.push(`  ACTIONS: ${JSON.stringify(this.expectedActions)}`);
        requestInfo.push(`  UPDATED: ${JSON.stringify(this.expectedSentUpdatedData)}`);
        requestInfo.push(`  CHILDREN: ${JSON.stringify(this.expectedChildFingerprints)}`);
        requestInfo.push(`  UPDATED PROPS FROM PARENT: ${JSON.stringify(this.expectedUpdatedPropsFromParent)}`);

        return requestInfo;
    }

    matches(
        props: any,
        actions: BackendAction[],
        updated: { [key: string]: any },
        children: ChildrenFingerprints,
        updatedPropsFromParent: { [key: string]: any }
    ): boolean {
        if (!this.isEqual(this.test.component.valueStore.getOriginalProps(), props)) {
            return false;
        }

        const normalizedBackendActions = actions.map((action) => {
            return {
                name: action.name,
                args: action.args,
            };
        });

        if (!this.isEqual(normalizedBackendActions, this.expectedActions)) {
            return false;
        }

        if (!this.isEqual(updated, this.expectedSentUpdatedData)) {
            return false;
        }

        if (null !== this.expectedChildFingerprints && !this.isEqual(children, this.expectedChildFingerprints)) {
            return false;
        }

        if (
            (null !== this.expectedUpdatedPropsFromParent || Object.keys(updatedPropsFromParent).length > 0) &&
            !this.isEqual(updatedPropsFromParent, this.expectedUpdatedPropsFromParent)
        ) {
            return false;
        }

        return true;
    }

    createBackendRequest(): BackendRequest {
        const promise: Promise<Response> = new Promise((resolve) => {
            setTimeout(() => {
                let newProps = JSON.parse(JSON.stringify(this.test.component.valueStore.getOriginalProps()));

                // this should be a simple, top-level property update
                if (null !== this.expectedUpdatedPropsFromParent) {
                    Object.keys(this.expectedUpdatedPropsFromParent).forEach((key) => {
                        // @ts-ignore
                        newProps[key] = this.expectedUpdatedPropsFromParent[key];
                    });
                }

                // imitate the server by updating the props with the updated data
                Object.keys(this.expectedSentUpdatedData).forEach((key) => {
                    // hopefully this is close enough to the server
                    // if this is a nested key, but that expect nested prop
                    // doesn't exist, then we're actually setting a specific
                    // field on a top-level property. For example, the property
                    // is an array called `options` and we're setting the
                    // `options.label` field... which means we want to set the
                    // "label" field onto the existing "options" array.
                    if (key.includes('.') && newProps[key] === undefined) {
                        newProps = setDeepData(newProps, key, this.expectedSentUpdatedData[key]);
                    } else {
                        newProps[key] = this.expectedSentUpdatedData[key];
                    }
                });

                if (this.changePropsCallback) {
                    this.changePropsCallback(newProps);
                }

                const template = this.template ? this.template : this.test.template;
                const html = this.customResponseHTML ? this.customResponseHTML : template(newProps);

                // assume a normal, live-component response unless it's totally custom
                const headers = { 'Content-Type': 'application/vnd.live-component+html' };
                if (this.customResponseHTML) {
                    headers['Content-Type'] = 'text/html';
                }

                const response = new Response(html, {
                    status: this.customResponseStatusCode || 200,
                    headers,
                });

                resolve(response);
            }, this.delayResponseTime);
        });

        return new BackendRequest(
            // @ts-ignore Response doesn't quite match the underlying interface
            promise,
            this.expectedActions.map((action) => action.name),
            Object.keys(this.expectedSentUpdatedData)
        );
    }

    /**
     * Pass any updated data that is expected to be sent on the Ajax request
     */
    expectUpdatedData = (updated: { [key: string]: any }): MockedAjaxCall => {
        this.expectedSentUpdatedData = updated;

        return this;
    };

    expectChildFingerprints = (fingerprints: any): MockedAjaxCall => {
        this.expectedChildFingerprints = fingerprints;

        return this;
    };

    expectUpdatedPropsFromParent = (updatedProps: any): MockedAjaxCall => {
        this.expectedUpdatedPropsFromParent = updatedProps;

        return this;
    };

    /**
     * Call if the "server" will change the props before it re-renders.
     */
    serverWillChangeProps = (callback: (data: any) => void): MockedAjaxCall => {
        this.changePropsCallback = callback;

        return this;
    };

    delayResponse = (milliseconds: number): MockedAjaxCall => {
        this.delayResponseTime = milliseconds;

        return this;
    };

    expectActionCalled(actionName: string, args: any = {}): MockedAjaxCall {
        this.expectedActions.push({
            name: actionName,
            args: args,
        });

        return this;
    }

    willReturn(template: (data: any) => string): MockedAjaxCall {
        this.template = template;

        return this;
    }

    serverWillReturnCustomResponse(statusCode: number, responseHTML: string): MockedAjaxCall {
        this.customResponseStatusCode = statusCode;
        this.customResponseHTML = responseHTML;

        return this;
    }

    private isEqual(a: any, b: any): boolean {
        if (a === null || b === null) {
            return a === b;
        }

        if (typeof a !== 'object' || typeof b !== 'object') {
            return a === b;
        }

        const sortedA = Object.keys(a)
            .sort()
            .reduce((obj: any, key) => {
                obj[key] = a[key];
                return obj;
            }, {});

        const sortedB = Object.keys(b)
            .sort()
            .reduce((obj: any, key) => {
                obj[key] = b[key];
                return obj;
            }, {});

        return JSON.stringify(sortedA) === JSON.stringify(sortedB);
    }
}

const mockBackend = new MockedBackend();

export async function createTest(props: any, template: (props: any) => string): Promise<FunctionalTest> {
    LiveControllerDefault.backendFactory = () => mockBackend;

    const testData = await startStimulus(template(props));

    const test = new FunctionalTest(testData.controller.component, testData.element, mockBackend, template);
    activeTests.push(test);

    return test;
}
/**
 * An internal way to create a FunctionalTest: useful for child components
 */
export function createTestForExistingComponent(component: Component): FunctionalTest {
    const test = new FunctionalTest(component, component.element, mockBackend, () => '');
    activeTests.push(test);

    return test;
}

export async function startStimulus(element: string | HTMLElement) {
    // start the Stimulus app just once per test suite
    if (application) {
        await application.start();
    } else {
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
        element: controllerElement,
    };
}

export const getStimulusApplication = (): Application => {
    return application;
};

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

export const dataToJsonAttribute = (data: any): string => {
    const container = document.createElement('div');
    container.dataset.foo = JSON.stringify(data);

    const matches = container.outerHTML.match(/data-foo="(.+)"/);

    if (!matches) {
        throw new Error('Match is missing');
    }

    // returns the now-escaped string, ready to be used in an HTML attribute
    return matches[1];
};

export function initComponent(props: any = {}, controllerValues: any = {}) {
    return `
        data-controller="live"
        data-live-name-value="${controllerValues.name || 'testing-component'}"
        data-live-url-value="http://localhost/components/_test_component_${Math.round(Math.random() * 1000)}"
        data-live-props-value="${dataToJsonAttribute(props)}"
        ${controllerValues.debounce ? `data-live-debounce-value="${controllerValues.debounce}"` : ''}
        ${controllerValues.id ? `id="${controllerValues.id}"` : ''}
        ${controllerValues.fingerprint ? `data-live-fingerprint-value="${controllerValues.fingerprint}"` : ''}
        ${controllerValues.listeners ? `data-live-listeners-value="${dataToJsonAttribute(controllerValues.listeners)}"` : ''}
        ${controllerValues.eventEmit ? `data-live-events-to-emit-value="${dataToJsonAttribute(controllerValues.eventEmit)}"` : ''}
        ${controllerValues.browserDispatch ? `data-live-events-to-dispatch-value="${dataToJsonAttribute(controllerValues.browserDispatch)}"` : ''}
        ${controllerValues.queryMapping ? `data-live-query-mapping-value="${dataToJsonAttribute(controllerValues.queryMapping)}"` : ''}
    `;
}

export function getComponent(element: HTMLElement | null) {
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

export function setCurrentSearch(search: string) {
    history.replaceState(history.state, '', window.location.origin + window.location.pathname + search);
}

export function expectCurrentSearch() {
    return expect(decodeURIComponent(window.location.search));
}

export class noopElementDriver implements ElementDriver {
    getBrowserEventsToDispatch(): Array<{ event: string; payload: any }> {
        throw new Error('Method not implemented.');
    }

    getComponentProps(): any {
        throw new Error('Method not implemented.');
    }

    getEventsToEmit(): Array<{
        event: string;
        data: any;
        target: string | null;
        componentName: string | null;
    }> {
        throw new Error('Method not implemented.');
    }

    getModelName(): string | null {
        throw new Error('Method not implemented.');
    }
}
