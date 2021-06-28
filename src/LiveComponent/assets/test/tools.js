import { Application } from 'stimulus';
import LiveController from '../src/live_controller';
import { waitFor } from '@testing-library/dom';
import fetchMock from 'fetch-mock-jest';
import { buildSearchParams } from '../src/http_data_helper';

const TestData = class {
    constructor(controller, element) {
        this.controller = controller;
        this.element = element;
    }
}

let application;

const startStimulus = async (html, container = null) => {
    // start the Stimulus app just once per test suite
    if (!application) {
        application = Application.start();
        application.register('live', LiveController);
    }

    if (!container) {
        container = document.createElement('div');
    }

    container.innerHTML = html;
    document.body.appendChild(container);

    const element = getControllerElement(container);
    await waitFor(() => application.getControllerForElementAndIdentifier(element, 'live'));
    const controller = application.getControllerForElementAndIdentifier(element, 'live');

    return new TestData(controller, element);
};

const getControllerElement = (container) => {
    return container.querySelector('[data-controller="live"]');
};

const dataToJsonAttribute = (data) => {
    const container = document.createElement('div');
    container.dataset.foo = JSON.stringify(data);

    // returns the now-escaped string, ready to be used in an HTML attribute
    return container.outerHTML.match(/data\-foo="(.+)\"/)[1]
}

const initLiveComponent = (url, data) => {
    return `
        data-controller="live"
        data-live-url-value="http://localhost${url}"
        data-live-data-value="${dataToJsonAttribute(data)}"
    `;
}

/**
 * Allows you to easily mock a re-render Ajax request.
 *
 * The renderCallback() is called and passed the "sentData" as the only
 * argument. If you want to change the "sentData" slightly before the
 * template is rendered, pass the changeDataCallback and modify it there.
 *
 * @param {Object} sentData The *expected* data that should be sent to the server
 * @param {function} renderCallback Function that will render the component
 * @param {function} changeDataCallback Specify if you want to change the data before rendering
 */
const mockRerender = (sentData, renderCallback, changeDataCallback = (data) => {}) => {
    const params = new URLSearchParams('');

    const url = `end:?${buildSearchParams(params, sentData).toString()}`;

    changeDataCallback(sentData);

    fetchMock.mock(url, {
        html: renderCallback(sentData),
        data: sentData
    });
}

export { startStimulus, getControllerElement, initLiveComponent, mockRerender };
