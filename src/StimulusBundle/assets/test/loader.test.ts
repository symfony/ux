// load from dist because the source TypeScript file points directly to controllers.js,
// which does not actually exist in the source code
import { loadControllers } from '../dist/loader';
import { Application, Controller } from '@hotwired/stimulus';
import {
    EagerControllersCollection,
    LazyControllersCollection,
} from '../src/controllers';
import { waitFor } from '@testing-library/dom';

let isController1Initialized = false;
let isController2Initialized = false;
let isController3Initialized = false;

const controller1 = class extends Controller {
    initialize() {
        isController1Initialized = true;
    }
};
const controller2 = class extends Controller {
    initialize() {
        isController2Initialized = true;
    }
};
const controller3 = class extends Controller {
    initialize() {
        isController3Initialized = true;
    }
};

describe('loader', () => {
    it('loads controllers', async () => {
        document.body.innerHTML = `
            <div data-controller="controller1"></div>
            <div data-controller="controller2"></div>
        `;

        const application = Application.start();
        const eagerControllers: EagerControllersCollection = {
            'controller1': controller1,
            'controller2': controller2,
        };
        const lazyControllers: LazyControllersCollection = {
            'controller3': () => Promise.resolve({ default: controller3 }),
        };

        loadControllers(application, eagerControllers, lazyControllers);

        await waitFor(() => expect(isController1Initialized).toBe(true));
        expect(isController2Initialized).toBe(true);
        expect(isController3Initialized).toBe(false);

        document.body.innerHTML = '<div data-controller="controller3"></div>';
        // wait a moment for the MutationObserver to fire
        await new Promise(resolve => setTimeout(resolve, 10));
        expect(isController3Initialized).toBe(true);
    });
});
