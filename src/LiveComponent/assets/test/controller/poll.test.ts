/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it } from 'vitest';
import { createTest, initComponent, shutdownTests } from '../tools';

describe('LiveController polling Tests', () => {
    afterEach(() => {
        shutdownTests();
    });

    it('starts a poll', async () => {
        const test = await createTest(
            { renderCount: 0 },
            (data: any) => `
            <div ${initComponent(data)} data-poll>
                <span>Render count: ${data.renderCount}</span>
            </div>
        `
        );

        // poll 1
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderCount = 1;
        });
        // poll 2
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderCount = 2;
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 2100,
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 2100,
        });
    });

    it('is controllable via modifiers', async () => {
        const test = await createTest(
            { renderCount: 0 },
            (data: any) => `
            <div ${initComponent(data)} data-poll="delay(250)|$render">
                <span>Render count: ${data.renderCount}</span>
            </div>
        `
        );

        // poll 1
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderCount = 1;
        });
        // poll 2
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderCount = 2;
        });

        // only wait for about 250ms this time
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 300,
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 300,
        });
    });

    it('can also call a live action', async () => {
        const test = await createTest(
            { renderCount: 0 },
            (data: any) => `
            <div ${initComponent(data)} data-poll="delay(250)|saveAction">
                <span>Render count: ${data.renderCount}</span>
            </div>
        `
        );

        // poll 1
        test.expectsAjaxCall()
            .expectActionCalled('saveAction')
            .serverWillChangeProps((data: any) => {
                data.renderCount = 1;
            });
        // poll 2
        test.expectsAjaxCall()
            .expectActionCalled('saveAction')
            .serverWillChangeProps((data: any) => {
                data.renderCount = 2;
            });

        // only wait for about 250ms this time
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 300,
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 300,
        });
    });

    it('polling should stop if data-poll is removed', async () => {
        const test = await createTest(
            { keepPolling: true, renderCount: 0 },
            (data: any) => `
            <div ${initComponent(data)} ${data.keepPolling ? 'data-poll="delay(250)|$render"' : ''}>
                <span>Render count: ${data.renderCount}</span>
            </div>
        `
        );

        test.expectsAjaxCall('$render').serverWillChangeProps((data: any) => {
            data.renderCount = 1;
        });

        await waitFor(
            () => {
                expect(test.element).toHaveTextContent('Render count: 1');
            },
            { timeout: 500 }
        );

        test.expectsAjaxCall('$render').serverWillChangeProps((data: any) => {
            data.renderCount = 2;
            data.keepPolling = false;
        });

        await waitFor(
            () => {
                expect(test.element).toHaveTextContent('Render count: 2');
            },
            { timeout: 500 }
        );

        expect(test.component.pollingDirector.pollingConfigs.get('$render')).toBeUndefined();

        await new Promise((resolve) => setTimeout(resolve, 500));
    });

    it('stops polling after it disconnects', async () => {
        const test = await createTest(
            { renderCount: 0 },
            (data: any) => `
           <div ${initComponent(data)} data-poll="delay(250)|$render">
               <span>Render count: ${data.renderCount}</span>
           </div>
       `
        );

        // poll 1
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderCount = 1;
        });

        // only wait for about 250ms this time
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 300,
        });
        // "remove" our controller from the page
        document.body.innerHTML = '<div>something else</div>';
        // wait 500ms more second... no more Ajax calls should be made
        const timeoutPromise = new Promise((resolve) => {
            setTimeout(() => {
                resolve(true);
            }, 500);
        });
        await waitFor(() => timeoutPromise, {
            timeout: 750,
        });
    });

    it('waits to send the request if request is already happening', async () => {
        const test = await createTest(
            { renderCount: 0, name: 'Ryan' },
            (data: any) => `
            <div ${initComponent(data, { debounce: 1 })} data-poll="delay(50)|$render">
                <input
                    data-model="name"
                    value="${data.name}"
                >

                <span>Name: ${data.name}</span>
                <span>Render count: ${data.renderCount}</span>
            </div>
        `
        );

        // First request, from typing (debouncing is set to 1ms)
        test.expectsAjaxCall()
            .expectUpdatedData({ name: 'Ryan Weaver' })
            .serverWillChangeProps((data: any) => {
                data.renderCount = 1;
            })
            .delayResponse(100);

        await userEvent.type(test.queryByDataModel('name'), ' Weaver');

        setTimeout(() => {
            // first poll, should happen after 50 ms, but needs to wait the full 100
            test.expectsAjaxCall().serverWillChangeProps((data: any) => {
                data.renderCount = 2;
            });
        }, 75);

        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'));
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'));
    });

    it('polls stop after limit reached', async () => {
        const test = await createTest(
            { renderCount: 0 },
            (data: any) => `
        <div ${initComponent(data)} data-poll="delay(100)|limit(3)|$render">
            <span>Render count: ${data.renderCount}</span>
        </div>
    `
        );

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderCount = 1;
        });
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderCount = 2;
        });
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.renderCount = 3;
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), { timeout: 500 });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), { timeout: 500 });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 3'), { timeout: 500 });

        // Add a small delay to ensure no more renders happen
        await new Promise((r) => setTimeout(r, 200));
        expect(test.element).toHaveTextContent('Render count: 3');
    });

    it('respects polling limit correctly and stops polling after limit is hit', async () => {
        const test = await createTest(
            { count: 0 },
            (data) => `
                <div ${initComponent(data)} data-poll="delay(50)|limit(2)|$render">
                    Count: ${data.count}
                </div>
            `
        );

        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.count = 1;
        });
        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.count = 2;
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Count: 2'));

        await new Promise((r) => setTimeout(r, 200));
        expect(test.element).toHaveTextContent('Count: 2'); // still 2 after limit
    });

    it('can pause and resume polling manually', async () => {
        const test = await createTest(
            { count: 0 },
            (data) => `
            <div ${initComponent(data)} data-poll="delay(100)|$render">
                Count: ${data.count}
            </div>
        `
        );

        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.count = 1;
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Count: 1'));

        test.component.pollingDirector.pause('$render');

        await new Promise((r) => setTimeout(r, 300));
        expect(test.element).toHaveTextContent('Count: 1');

        test.component.pollingDirector.resume('$render');

        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.count = 2;
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Count: 2'));
    });

    it('can restart polling after stopping', async () => {
        const test = await createTest(
            { a: 0 },
            (data) => `
        <div ${initComponent(data)} data-poll="delay(30)|limit(2)|$render">
            <span>A: ${data.a}</span>
        </div>
        `
        );

        test.expectsAjaxCall('$render').serverWillChangeProps((data) => {
            data.a = 1;
            return data;
        });
        await waitFor(() => {
            expect(test.element).toHaveTextContent('A: 1');
        });

        test.component.pollingDirector.stop('$render');
        const countStopped = test.component.pollingDirector.pollingCounts.get('$render') ?? 0;

        test.component.pollingDirector.start('$render');
        test.expectsAjaxCall('$render').serverWillChangeProps((data) => {
            data.a = 2;
            return data;
        });

        await waitFor(
            () => {
                expect(test.element).toHaveTextContent('A: 2');
                expect(test.component.pollingDirector.pollingCounts.get('$render')).toBeGreaterThan(countStopped);
            },
            { timeout: 2000 }
        );
    });

    it('should respect polling limits', async () => {
        const test = await createTest(
            { count: 0 },
            (data) => `
            <div ${initComponent(data)} data-poll="delay(100)|limit(2)|$render">
                Count: ${data.count}
            </div>
        `
        );

        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.count = 1;
        });
        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.count = 2;
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Count: 2'));

        const count = test.component.pollingDirector.pollingCounts.get('$render');
        expect(count).toBe(2);
    });

    it('does not crash if poll limit is non-numeric or malformed', async () => {
        const test = await createTest(
            { count: 0 },
            (data) => `
                <div ${initComponent(data)} data-poll="delay(100)|limit(bad)|$render">
                    Count: ${data.count}
                </div>
            `
        );

        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.count = 999;
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Count: 999'));
    });

    it('should handle invalid poll configs gracefully', async () => {
        const test = await createTest(
            { count: 0 },
            (data) => `
            <div ${initComponent(data)} data-poll="delay(invalid)|$render">
                Count: ${data.count}
            </div>
        `
        );

        test.expectsAjaxCall().serverWillChangeProps((data) => {
            data.count = 1;
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Count: 1'), {
            timeout: 2500,
        });
    });

    it('polling triggers poll hooks correctly', async () => {
        const test = await createTest(
            { count: 0 },
            (data) => `
            <div ${initComponent(data)} data-poll="delay(100)|limit(2)|$render">
                <span>Count: ${data.count}</span>
            </div>
        `
        );

        // An array to track all triggered hooks and their arguments
        const calledHooks: Array<{ name: string; args: any }> = [];
        const originalTriggerHook = test.component.triggerPollHook.bind(test.component);
        test.component.triggerPollHook = (hookName, args) => {
            calledHooks.push({ name: hookName, args });
            return originalTriggerHook(hookName, args);
        };

        // Expect two Ajax calls triggered by polling
        test.expectsAjaxCall('$render').serverWillChangeProps((props) => {
            props.count = 1;
        });
        test.expectsAjaxCall('$render').serverWillChangeProps((props) => {
            props.count = 2;
        });

        // Wait for the polling to finish and assert hook behavior and DOM updates
        await waitFor(
            () => {
                expect(test.element).toHaveTextContent('Count: 2');
                expect(calledHooks.filter((h) => h.name === 'poll:running').length).toBe(2);
                expect(calledHooks.map((h) => h.name)).toContain('poll:stopped');
            },
            {
                timeout: 5000,
            }
        );
    });

    it('triggers poll:error hook when polling action throws', async () => {
        const test = await createTest(
            { count: 0 },
            (data) => `
        <div ${initComponent(data)} data-poll="delay(100)|limit(1)|failPoll">
            Count: ${data.count}
        </div>
    `
        );

        const calledHooks: Array<{ name: string; args: any }> = [];
        const originalTriggerHook = test.component.triggerPollHook.bind(test.component);
        test.component.triggerPollHook = (hookName, args) => {
            console.log(`triggerPollHook called: ${hookName}`, args);
            calledHooks.push({ name: hookName, args });
            return originalTriggerHook(hookName, args);
        };

        const originalAction = test.component.action.bind(test.component);
        test.component.action = async (actionName, args, delay) => {
            if (actionName === 'failPoll') {
                throw new Error('Forced failure for testing');
            }
            return originalAction(actionName, args, delay);
        };

        await waitFor(
            () => {
                const errorHook = calledHooks.find((h) => h.name === 'poll:error');
                expect(errorHook).toBeDefined();
                expect(errorHook?.args.actionName).toBe('failPoll');
                expect(errorHook?.args.errorMessage).toBe('Forced failure for testing');
            },
            {
                timeout: 5000,
            }
        );
    });
});
