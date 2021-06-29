import { Application } from 'stimulus';
import LiveController from '../src/live_controller';
import { waitFor } from '@testing-library/dom';

const TestData = class {
    constructor(controller, element) {
        this.controller = controller;
        this.element = element;
    }
}

let application;

const startStimulus = async (html, data, container = null) => {
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
    element.dataset.liveDataValue = JSON.stringify(data);

    await waitFor(() => application.getControllerForElementAndIdentifier(element, 'live'));
    const controller = application.getControllerForElementAndIdentifier(element, 'live');

    return new TestData(controller, element);
};

const getControllerElement = (container) => {
    return container.querySelector('[data-controller="live"]');
};

export { startStimulus, getControllerElement };
