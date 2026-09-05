/**
 * Tests for the LiveComponent upload bridge controller.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { Application } from '@hotwired/stimulus';
import { action, getComponent } from './stubs/live-component';
import LiveUploadController from '../../src/live_upload_controller';

describe('LiveUploadController', () => {
    let application: Application;
    let container: HTMLElement;

    const start = (property = 'picture'): HTMLElement => {
        container = document.createElement('div');
        container.setAttribute('data-controller', 'symfony--ux-upload--live-upload');
        container.setAttribute('data-symfony--ux-upload--live-upload-property-value', property);
        document.body.appendChild(container);

        application = Application.start();
        application.register('symfony--ux-upload--live-upload', LiveUploadController);

        return container;
    };

    beforeEach(() => {
        action.mockClear();
        getComponent.mockClear();
    });

    afterEach(() => {
        application?.stop();
        container?.remove();
    });

    it('resolves the nearest live component on connect', async () => {
        const element = start();
        await vi.waitFor(() => expect(getComponent).toHaveBeenCalledWith(element));
    });

    it('forwards the upload token to the live action on completion', async () => {
        const element = start();
        await vi.waitFor(() => expect(getComponent).toHaveBeenCalled());

        element.dispatchEvent(
            new CustomEvent('symfony--ux-upload--upload:complete', {
                detail: { fileId: 'file-1', result: { token: 'signed-token', metadata: {} } },
            })
        );

        await vi.waitFor(() =>
            expect(action).toHaveBeenCalledWith('applyUpload', { property: 'picture', token: 'signed-token' })
        );
    });

    it('ignores completion events that carry no token', async () => {
        const element = start();
        await vi.waitFor(() => expect(getComponent).toHaveBeenCalled());

        element.dispatchEvent(new CustomEvent('symfony--ux-upload--upload:complete', { detail: { fileId: 'file-1' } }));

        await new Promise((resolve) => setTimeout(resolve, 10));
        expect(action).not.toHaveBeenCalled();
    });

    it('stops listening after disconnect', async () => {
        const element = start();
        await vi.waitFor(() => expect(getComponent).toHaveBeenCalled());

        const controller = application.getControllerForElementAndIdentifier(element, 'symfony--ux-upload--live-upload');
        (controller as unknown as { disconnect(): void }).disconnect();

        element.dispatchEvent(
            new CustomEvent('symfony--ux-upload--upload:complete', {
                detail: { result: { token: 'signed-token' } },
            })
        );

        await new Promise((resolve) => setTimeout(resolve, 10));
        expect(action).not.toHaveBeenCalled();
    });
});
