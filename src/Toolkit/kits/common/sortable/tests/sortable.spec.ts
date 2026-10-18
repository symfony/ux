import type { Locator, Page, Request, Route } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const formData = async (request: Request) => {
    const contentType = (await request.headerValue('content-type'))!;

    return Object.fromEntries(
        await new Response(request.postDataBuffer(), { headers: { 'content-type': contentType } }).formData()
    );
};

const center = async (locator: Locator) => {
    const box = (await locator.boundingBox())!;

    return { x: box.x + box.width / 2, y: box.y + box.height / 2 };
};

const drag = async (page: Page, source: Locator, ...path: Locator[]) => {
    const start = await center(source);
    const points = await Promise.all(path.map(center));

    await page.mouse.move(start.x, start.y);
    await page.mouse.down();
    for (const { x, y } of points) {
        await page.mouse.move(x, y, { steps: 10 });
        // SortableJS ignores the `dragover` events of the 30ms after it moves an item, and those over an item
        // still sliding into place: keep hovering past the 150ms animation, so the item settles under the pointer.
        for (const offset of [1, -1, 1]) {
            await page.waitForTimeout(200);
            await page.mouse.move(x, y + offset);
        }
    }
    await page.mouse.up();
};

describeRecipe('common/sortable', () => {
    testState('moves an item to where it is dropped', {
        example: 'default',
        state: 'sorted',
        act: async (page) => {
            await drag(page, page.getByText('Pet the cat'), page.getByText('Water the plants'));
            await page.mouse.move(0, 0);

            await expect(page.getByRole('listitem')).toHaveText([
                'Get the groceries',
                'Water the plants',
                'Pet the cat',
                'Call the plumber',
            ]);
        },
    });

    test('moves an item up the list', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/default');

        await drag(page, page.getByText('Call the plumber'), page.getByText('Get the groceries'));

        await expect(page.getByRole('listitem')).toHaveText([
            'Pet the cat',
            'Call the plumber',
            'Get the groceries',
            'Water the plants',
        ]);
    });

    test('moves an item along both axes of a grid', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/grid');

        await drag(page, page.getByText('A', { exact: true }), page.getByText('E', { exact: true }));

        await expect(page.locator('[data-controller="sortable"] > *')).toHaveText(['B', 'C', 'D', 'E', 'A', 'F']);
    });

    test('drags an item only by its handle', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/drag-handle');
        const items = page.getByRole('listitem');

        await drag(page, page.getByText('Pet the cat'), page.getByText('Water the plants'));

        await expect(items).toHaveText(['Pet the cat', 'Get the groceries', 'Water the plants']);

        await drag(page, items.first().locator('[data-handle]'), page.getByText('Water the plants'));

        await expect(items).toHaveText(['Get the groceries', 'Water the plants', 'Pet the cat']);
    });

    test('keeps the items left out by the draggable selector in place', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/fixed-items');
        const items = page.locator('[data-controller="sortable"] > *');
        const upload = page.getByText('Add a photo');

        await drag(page, upload, page.getByText('A', { exact: true }));

        await expect(items).toHaveText(['A', 'B', 'C', 'D', 'E', 'Add a photo']);

        await drag(page, page.getByText('A', { exact: true }), upload);

        await expect(items).toHaveText(['B', 'C', 'D', 'E', 'A', 'Add a photo']);
    });

    test('does not pick up an item from an element matching the filter selector', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/ignored-elements');
        const items = page.getByRole('listitem');

        await drag(page, items.first().getByRole('button'), page.getByText('Water the plants'));

        await expect(items).toHaveText(['Pet the cat Edit', 'Get the groceries Edit', 'Water the plants Edit']);

        await drag(page, page.getByText('Pet the cat'), page.getByText('Water the plants'));

        await expect(items).toHaveText(['Get the groceries Edit', 'Water the plants Edit', 'Pet the cat Edit']);
    });

    test('counts only the draggable items in the position it sends', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/fixed-items');
        // Puts the fixed slot first, so it would shift the position if it were counted.
        await page
            .locator('[data-controller="sortable"]')
            .evaluate((grid) => grid.prepend(grid.querySelector('[data-upload]')!));
        const item = page.getByText('C', { exact: true });
        await item.evaluate((element) => {
            element.dataset.sortableUpdateUrl = '/link/photos/3/position';
        });
        await page.route('**/link/photos/3/position', (route) => route.fulfill({ status: 204 }));
        const request = page.waitForRequest('**/link/photos/3/position');

        await drag(page, item, page.getByText('A', { exact: true }));

        expect(await formData(await request)).toEqual({ position: '1' });
    });

    test('sends the new position of the moved item to its update URL', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/save-the-new-position');
        await page.route('**/link/tasks/*/position', (route) => route.fulfill({ status: 204 }));
        const request = page.waitForRequest('**/link/tasks/*/position');

        await drag(page, page.getByText('Write the release notes'), page.getByText('Tag the new version'));

        expect((await request).url()).toMatch(/\/link\/tasks\/1\/position$/);
        expect((await request).method()).toBe('POST');
        expect(await formData(await request)).toEqual({ position: '3', _token: 'csrf-token-for-reorder-tasks' });
        expect(await (await request).allHeaders()).toMatchObject({
            accept: 'text/html, application/xhtml+xml',
            'content-type': expect.stringMatching(/^multipart\/form-data; boundary=/),
            'x-requested-with': 'XMLHttpRequest',
        });
    });

    test('sends no request when the item is dropped back in place', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/save-the-new-position');
        const urls: string[] = [];
        await page.route('**/link/tasks/*/position', (route) => {
            urls.push(route.request().url());

            return route.fulfill({ status: 204 });
        });
        const item = page.getByText('Review the pull requests');
        const next = page.getByText('Tag the new version');

        await drag(page, item, next, item);

        await expect(page.getByRole('listitem')).toHaveText([
            'Write the release notes',
            'Review the pull requests',
            'Tag the new version',
        ]);

        // Requests go out in order: by the time the next move is saved, a request for the first drop would be in.
        const request = page.waitForRequest('**/link/tasks/*/position');
        await drag(page, item, next);
        await request;

        expect(urls).toHaveLength(1);
    });

    test('sends the position under the given parameter name', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/save-the-new-position');
        await page.getByRole('list').evaluate((list) => {
            list.dataset.sortableParamNameValue = 'task[rank]';
        });
        await page.route('**/link/tasks/*/position', (route) => route.fulfill({ status: 204 }));
        const request = page.waitForRequest('**/link/tasks/*/position');

        await drag(page, page.getByText('Tag the new version'), page.getByText('Write the release notes'));

        expect((await request).url()).toMatch(/\/link\/tasks\/3\/position$/);
        expect(await formData(await request)).toEqual({ 'task[rank]': '1', _token: 'csrf-token-for-reorder-tasks' });
    });

    test('sends a save only once the previous one is done', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/save-the-new-position');
        const routes: Route[] = [];
        await page.route('**/link/tasks/*/position', (route) => {
            routes.push(route);
        });

        await drag(page, page.getByText('Write the release notes'), page.getByText('Tag the new version'));
        await expect.poll(() => routes.length).toBe(1);

        await drag(page, page.getByText('Tag the new version'), page.getByText('Review the pull requests'));
        // Leaves time for the second save to reach the route, if it was sent without waiting for the first one.
        await page.waitForTimeout(200);
        expect(routes).toHaveLength(1);

        await routes[0].fulfill({ status: 204 });
        await expect.poll(() => routes.length).toBe(2);
        expect(routes[1].request().url()).toMatch(/\/link\/tasks\/3\/position$/);
    });

    test.describe(() => {
        // The aborted request logs a network error on purpose.
        test.use({ failOnPageErrors: [async ({}, use) => use(), { auto: true }] });

        test('keeps sending saves after one fails', async ({ page, gotoExample }) => {
            await gotoExample('common/sortable/save-the-new-position');
            const urls: string[] = [];
            await page.route('**/link/tasks/*/position', (route) => {
                urls.push(route.request().url());

                return 1 === urls.length ? route.abort() : route.fulfill({ status: 204 });
            });

            await drag(page, page.getByText('Write the release notes'), page.getByText('Tag the new version'));
            await expect.poll(() => urls.length).toBe(1);
            await drag(page, page.getByText('Tag the new version'), page.getByText('Review the pull requests'));

            await expect.poll(() => urls.length).toBe(2);
            expect(urls[1]).toMatch(/\/link\/tasks\/3\/position$/);
        });
    });

    test('renders a Turbo Stream response', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/save-the-new-position');
        const stream = '<turbo-stream action="remove" target="flash"></turbo-stream>';
        await page.evaluate(() => {
            // The previews do not load Turbo: stand in for the function the controller calls on it.
            Object.assign(window, {
                Turbo: {
                    renderStreamMessage: (message: string) => (document.body.dataset.renderedStream = message),
                },
            });
        });
        await page.route('**/link/tasks/*/position', (route) =>
            route.fulfill({ status: 200, contentType: 'text/vnd.turbo-stream.html', body: stream })
        );
        const request = page.waitForRequest('**/link/tasks/*/position');

        await drag(page, page.getByText('Write the release notes'), page.getByText('Review the pull requests'));

        expect(await (await request).headerValue('accept')).toBe(
            'text/vnd.turbo-stream.html, text/html, application/xhtml+xml'
        );
        await expect(page.locator('body')).toHaveAttribute('data-rendered-stream', stream);
    });

    test('submits the items of a form in their new order', async ({ page, gotoExample }) => {
        await gotoExample('common/sortable/submit-the-order-with-a-form');
        await page.route('**/link/tasks/order', (route) =>
            route.fulfill({ status: 200, contentType: 'text/html', body: '<p>Saved</p>' })
        );

        await drag(page, page.getByText('Write the release notes'), page.getByText('Tag the new version'));
        const request = page.waitForRequest('**/link/tasks/order');
        await page.getByRole('button', { name: 'Save the order' }).click();

        const body = new URLSearchParams((await request).postData()!);
        expect(body.getAll('tasks[]')).toEqual(['2', '3', '1']);
        expect(body.get('_token')).toBe('csrf-token-for-reorder-tasks');
    });
});
