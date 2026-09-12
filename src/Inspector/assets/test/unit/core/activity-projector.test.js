import { describe, expect, it } from 'vitest';
import { activityCounts, normalizeTurboFetchEntry, projectActivity } from '../../../src/core/activity-projector';

function entry(event, time, detail = {}, target = null) {
    return { type: 'turbo', event, time, detail, target, owner: null, relatedElements: [] };
}

function request(time, url = 'https://example.com/live', target = null, overrides = {}) {
    return entry(
        'turbo:before-fetch-request',
        time,
        {
            url,
            fetchOptions: {
                method: 'GET',
                headers: { Accept: 'text/html', 'x-SeC-pUrPoSe': 'prefetch' },
                priority: 'low',
                ...overrides,
            },
        },
        target
    );
}

function response(time, url = 'https://example.com/live', status = 200, target = null) {
    return entry(
        'turbo:before-fetch-response',
        time,
        {
            fetchResponse: { response: { url, status } },
        },
        target
    );
}

describe('activity projector', () => {
    it('projects a Turbo fetch pair while retaining both raw hooks', () => {
        const target = document.createElement('a');
        const raw = [request(10, undefined, target), response(42, undefined, 204, target)];

        const [operation] = projectActivity(raw);

        expect(operation).toMatchObject({
            type: 'turbo',
            event: 'turbo:fetch',
            activityKind: 'turbo-fetch',
            occurrences: 1,
            fetch: {
                intent: 'prefetch',
                method: 'GET',
                url: 'https://example.com/live',
                priority: 'low',
                status: 204,
                duration: 32,
                pending: false,
            },
        });
        expect(operation.rawEntries).toEqual(raw);
        expect(operation.rawCount).toBe(2);
    });

    it('recognizes the prefetch header case-insensitively', () => {
        expect(normalizeTurboFetchEntry(request(0)).intent).toBe('prefetch');
    });

    it('keeps an unmatched request as a pending operation', () => {
        const [operation] = projectActivity([request(10)]);

        expect(operation.fetch.pending).toBe(true);
        expect(operation.fetch.status).toBeNull();
        expect(operation.rawEntries).toHaveLength(1);
    });

    it('matches concurrent requests in FIFO order without consuming interleaved hooks', () => {
        const target = document.createElement('a');
        const render = entry('turbo:render', 15, { renderMethod: 'replace' });
        const rows = projectActivity([
            request(10, undefined, target),
            render,
            request(20, undefined, target),
            response(40, undefined, 201, target),
            response(70, undefined, 202, target),
        ]);

        expect(rows).toHaveLength(3);
        expect(rows[0].fetch).toMatchObject({ status: 201, duration: 30 });
        expect(rows[1].event).toBe('turbo:render');
        expect(rows[1]).toBe(render);
        expect(rows[2].fetch).toMatchObject({ status: 202, duration: 50 });
    });

    it('compresses only complete identical consecutive operations', () => {
        const target = document.createElement('a');
        const raw = [
            request(10, undefined, target),
            response(20, undefined, 200, target),
            request(30, undefined, target),
            response(45, undefined, 200, target),
        ];

        const rows = projectActivity(raw);

        expect(rows).toHaveLength(1);
        expect(rows[0].occurrences).toBe(2);
        expect(rows[0].rawEntries).toEqual(raw);
        expect(rows[0].rawCount).toBe(4);
    });

    it('is deterministic when projecting the same snapshot again', () => {
        const target = document.createElement('a');
        const raw = [request(10, undefined, target), response(20, undefined, 200, target)];
        const summarize = (rows) =>
            rows.map((row) => ({
                event: row.event,
                label: row.label,
                fetch: row.fetch,
                rawEvents: row.rawEntries.map((item) => item.event),
            }));

        expect(summarize(projectActivity(raw))).toEqual(summarize(projectActivity(raw)));
        expect(activityCounts(raw)).toEqual({ turbo: 1 });
    });

    it('leaves an unmatched response as a raw event', () => {
        const raw = response(10);
        const [row] = projectActivity([raw]);

        expect(row.event).toBe('turbo:before-fetch-response');
        expect(row.activityKind).toBeUndefined();
        expect(row).toBe(raw);
    });

    it('projects one LiveComponent rerender with its trigger, changes and lifecycle', () => {
        const target = document.createElement('div');
        const entries = [
            {
                type: 'livecomponent',
                event: 'live:model:set',
                time: 10,
                target,
                detail: { model: 'message', value: 'Hello' },
            },
            {
                type: 'livecomponent',
                event: 'live:request',
                time: 20,
                target,
                detail: { actions: ['send'], models: ['message'] },
            },
            { type: 'livecomponent', event: 'live:render:started', time: 30, target },
            { type: 'livecomponent', event: 'live:render:finished', time: 50, target },
        ];

        const [rerender] = projectActivity(entries);

        expect(rerender).toMatchObject({
            event: 'live:rerender',
            activityKind: 'live-rerender',
            label: 'rerender · send()',
            live: {
                trigger: 'send()',
                actions: ['send'],
                models: ['message'],
                changes: [{ model: 'message', value: 'Hello' }],
                hooks: ['request', 'render:started', 'render:finished'],
                status: 'complete',
                duration: 30,
            },
        });
        expect(rerender.rawEntries).toEqual(entries);
        expect(activityCounts(entries)).toEqual({ livecomponent: 1 });
    });

    it('keeps a model change visible when no rerender follows', () => {
        const change = {
            type: 'livecomponent',
            event: 'live:model:set',
            time: 10,
            detail: { model: 'query', value: 'ux' },
        };

        expect(projectActivity([change])).toEqual([change]);
    });
});

it('pairs and groups requests by DOM identity, with missing targets sharing the unscoped history', () => {
    const first = document.createElement('a');
    const second = first.cloneNode();
    const rows = projectActivity([
        request(1, undefined, first),
        request(2, undefined, second),
        response(3, undefined, 201, second),
        response(4, undefined, 202, first),
        { ...request(5), target: undefined },
        response(6),
        request(7),
        { ...response(8), target: undefined },
    ]);
    expect(rows).toHaveLength(3);
    expect(rows[0].fetch).toMatchObject({ status: 202, duration: 3 });
    expect(rows[1].fetch).toMatchObject({ status: 201, duration: 1 });
    expect(rows[2].occurrences).toBe(2);
});
