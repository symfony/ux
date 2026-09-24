import { describe, it, expect, vi } from 'vitest';
import {
    initResponse,
    mockSuccessfulRemove,
    mockSuccessfulUpload,
    mount,
    okJson,
    pendingUntilAborted,
    queueResponses,
    statusResponse,
    tick,
    triggerFileSelect,
    waitForCompletedItems,
} from './upload_controller_harness';

const EVENTS = [
    'add',
    'start',
    'init',
    'chunk',
    'pause',
    'resume',
    'cancel',
    'remove',
    'retry',
    'error',
    'validation-error',
];

const recordEvents = (element: HTMLElement): Record<string, unknown[]> => {
    const recorded: Record<string, unknown[]> = Object.fromEntries(EVENTS.map((name) => [name, []]));
    for (const name of EVENTS) {
        element.addEventListener(`ux-upload:${name}`, (event) => recorded[name].push((event as CustomEvent).detail));
    }

    return recorded;
};

const fileIdOf = (element: HTMLElement, index = 0): string | undefined =>
    (element.querySelectorAll('.ux-upload__item')[index] as HTMLElement | undefined)?.dataset.fileId;

const click = (element: HTMLElement, action: string): void => {
    (element.querySelector(`.ux-upload__${action}`) as HTMLButtonElement).click();
};

describe('UxUploadController: events', () => {
    it('dispatches add, start, init and chunk with their detail during an upload', async () => {
        const element = await mount();
        const events = recordEvents(element);
        mockSuccessfulUpload('up-events', 'notes.txt', 4, 'text/plain', 'tok-events');

        triggerFileSelect(element, [new File(['data'], 'notes.txt', { type: 'text/plain' })]);
        await waitForCompletedItems(element);

        const fileId = fileIdOf(element);
        const file = { name: 'notes.txt', size: 4, type: 'text/plain' };
        expect(events.add).toEqual([{ fileId, file }]);
        expect(events.start).toEqual([{ fileId, file }]);
        expect(events.init).toEqual([{ uploadId: 'up-events', fileId, resumable: true }]);
        expect(events.chunk).toEqual([{ uploadId: 'up-events', chunkIndex: 0, totalChunks: 1 }]);
    });

    it('dispatches pause, resume and cancel for an upload in flight', async () => {
        const element = await mount();
        const events = recordEvents(element);
        queueResponses(vi.mocked(fetch), [okJson(initResponse('up-flight')), statusResponse()]).mockImplementation(
            pendingUntilAborted
        );

        triggerFileSelect(element, [new File(['data'], 'flight.txt', { type: 'text/plain' })]);
        await vi.waitFor(() => expect(events.init).toHaveLength(1));
        const fileId = fileIdOf(element);

        click(element, 'pause');
        click(element, 'resume');
        click(element, 'cancel');
        await tick();

        expect(events.pause).toEqual([{ fileId }]);
        expect(events.resume).toEqual([{ fileId }]);
        expect(events.cancel).toEqual([{ fileId }]);
        expect(events.remove).toEqual([]);
    });

    it('dispatches cancel then remove when a pending file is cancelled', async () => {
        const element = await mount({ autoUpload: false });
        const events = recordEvents(element);
        triggerFileSelect(element, [new File(['data'], 'pending.txt', { type: 'text/plain' })]);
        // Stimulus binds the actions of the new item on its next mutation callback
        await tick();
        const fileId = fileIdOf(element);

        click(element, 'cancel');

        expect(events.cancel).toEqual([{ fileId }]);
        expect(events.remove).toEqual([{ fileId }]);
        expect(events.start).toEqual([]);
    });

    it('dispatches remove when a completed file is removed', async () => {
        const element = await mount();
        const events = recordEvents(element);
        mockSuccessfulUpload('up-remove-event', 'done.txt', 4, 'text/plain', 'tok-remove-event');
        triggerFileSelect(element, [new File(['data'], 'done.txt', { type: 'text/plain' })]);
        await waitForCompletedItems(element);
        const fileId = fileIdOf(element);

        mockSuccessfulRemove();
        click(element, 'remove');
        await vi.waitFor(() => expect(events.remove).toEqual([{ fileId }]));
    });

    it('dispatches error with the message, then retry', async () => {
        const element = await mount();
        const events = recordEvents(element);
        vi.mocked(fetch).mockResolvedValueOnce({
            ok: false,
            json: () => Promise.resolve({ error: 'Upload initialization failed' }),
        } as Response);

        triggerFileSelect(element, [new File(['data'], 'failing.txt', { type: 'text/plain' })]);
        await vi.waitFor(() => expect(events.error).toHaveLength(1));
        const fileId = fileIdOf(element);
        expect(events.error).toEqual([{ fileId, error: 'Upload initialization failed' }]);

        mockSuccessfulUpload('up-retried', 'failing.txt', 4, 'text/plain', 'tok-retried');
        click(element, 'retry');
        await waitForCompletedItems(element);

        expect(events.retry).toEqual([{ fileId }]);
        expect(events.start).toHaveLength(2);
    });

    it('dispatches validation-error with the rejection message', async () => {
        const element = await mount({ maxSize: 10 });
        const events = recordEvents(element);

        triggerFileSelect(element, [new File(['x'.repeat(100)], 'big.txt', { type: 'text/plain' })]);

        expect(events['validation-error']).toEqual([{ message: 'File too large (max 10 B)' }]);
        expect(events.add).toEqual([]);
    });
});
