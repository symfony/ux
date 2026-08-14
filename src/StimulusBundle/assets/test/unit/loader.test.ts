import { Application, Controller } from '@hotwired/stimulus';
import { waitFor } from '@testing-library/dom';
import { describe, expect, it, vi } from 'vitest';
// load from dist because the source TypeScript file points directly to controllers.js,
// which does not actually exist in the source code
import { loadControllers } from '../../dist/loader';
import type { EagerControllersCollection, LazyControllersCollection } from '../../src/controllers';

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
            controller1,
            controller2,
        };
        const lazyControllers: LazyControllersCollection = {
            controller3: () => Promise.resolve({ default: controller3 }),
        };

        loadControllers(application, eagerControllers, lazyControllers);

        await waitFor(() => expect(isController1Initialized).toBe(true));
        expect(isController2Initialized).toBe(true);
        expect(isController3Initialized).toBe(false);

        document.body.innerHTML = '<div data-controller="controller3"></div>';
        // wait a moment for the MutationObserver to fire
        await new Promise((resolve) => setTimeout(resolve, 10));
        expect(isController3Initialized).toBe(true);

        application.stop();
    });

    it('stops watching the DOM once every lazy controller is loaded', async () => {
        document.body.innerHTML = '';

        const disconnect = vi.spyOn(MutationObserver.prototype, 'disconnect');
        const application = Application.start();
        const lazyControllers: LazyControllersCollection = {
            controller4: () => Promise.resolve({ default: class extends Controller {} }),
        };

        loadControllers(application, {}, lazyControllers);

        document.body.innerHTML = '<div data-controller="controller4"></div>';
        await new Promise((resolve) => setTimeout(resolve, 10));

        expect(Object.keys(lazyControllers)).toHaveLength(0);
        expect(disconnect).toHaveBeenCalled();

        disconnect.mockRestore();
        application.stop();
    });
});
