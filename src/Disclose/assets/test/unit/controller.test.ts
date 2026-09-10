/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application } from '@hotwired/stimulus';
import { waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import createFetchMock from 'vitest-fetch-mock';
import DiscloseController from '../../src/controller';

const fetchMocker = createFetchMock(vi);

const componentHtml = (url: string): string => `
    <div data-controller="disclose" data-disclose-url-value="${url}">
        <button type="button" data-disclose-target="button" data-action="disclose#reveal">••••••</button>
        <div data-disclose-target="content" hidden>
            <span data-disclose-target="value"></span>
            <button type="button" data-disclose-target="hideButton" data-action="disclose#hide" hidden>Hide</button>
        </div>
        <div data-disclose-target="error" role="alert" hidden></div>
    </div>`;

const toggleComponentHtml = (url: string): string => `
    <div data-controller="disclose" data-disclose-url-value="${url}" data-disclose-toggle-value="true">
        <button type="button" data-disclose-target="button" data-action="disclose#toggle" aria-label="Reveal">••••••</button>
        <div data-disclose-target="content" hidden>
            <span data-disclose-target="value"></span>
        </div>
        <div data-disclose-target="error" role="alert" hidden></div>
    </div>`;

describe('DiscloseController', () => {
    beforeAll(() => {
        Application.start().register('disclose', DiscloseController);
        fetchMocker.enableMocks();
    });

    beforeEach(() => {
        fetchMocker.resetMocks();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    const mount = (url = 'http://localhost/disclose'): HTMLElement => {
        const container = document.createElement('div');
        container.innerHTML = componentHtml(url);
        document.body.innerHTML = '';
        document.body.appendChild(container);

        return container;
    };

    const button = (container: HTMLElement): HTMLButtonElement =>
        container.querySelector('[data-disclose-target="button"]') as HTMLButtonElement;
    const content = (container: HTMLElement): HTMLElement =>
        container.querySelector('[data-disclose-target="content"]') as HTMLElement;
    const value = (container: HTMLElement): HTMLElement =>
        container.querySelector('[data-disclose-target="value"]') as HTMLElement;
    const error = (container: HTMLElement): HTMLElement =>
        container.querySelector('[data-disclose-target="error"]') as HTMLElement;

    it('reveals the value on click', async () => {
        const container = mount();
        fetchMocker.mockResponse(JSON.stringify({ value: 'the-secret' }));

        await userEvent.click(button(container));

        await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));
        expect(content(container)).not.toHaveAttribute('hidden');
        expect(button(container)).toHaveAttribute('hidden');
    });

    it('marks the button as loading while disabled during the request', async () => {
        const container = mount();
        let resolveFetch!: (response: Response) => void;
        fetchMocker.mockResponse(
            () =>
                new Promise<Response>((resolve) => {
                    resolveFetch = resolve;
                })
        );

        await userEvent.click(button(container));

        await waitFor(() => expect(button(container)).toBeDisabled());
        expect(button(container)).toHaveAttribute('aria-busy', 'true');

        resolveFetch(
            new Response(JSON.stringify({ value: 'the-secret' }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            })
        );

        await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));
        expect(button(container)).toBeEnabled();
        expect(button(container)).toHaveAttribute('aria-busy', 'false');
    });

    it('shows the rate-limited state on a 429 response', async () => {
        const container = mount();
        fetchMocker.mockResponse('', { status: 429 });

        await userEvent.click(button(container));

        await waitFor(() => expect(error(container)).not.toHaveAttribute('hidden'));
        expect(error(container)).toHaveTextContent('Rate limit exceeded. Try again later.');
        expect(value(container)).not.toHaveTextContent('the-secret');
        expect(button(container)).toBeEnabled();
    });

    it('shows a generic error on a failing request', async () => {
        const container = mount();
        fetchMocker.mockResponse('', { status: 500 });

        await userEvent.click(button(container));

        await waitFor(() => expect(error(container)).not.toHaveAttribute('hidden'));
        expect(error(container)).toHaveTextContent('Unable to disclose.');
    });

    it('renders a server-side HTML block when renderHtml is enabled', async () => {
        const container = document.createElement('div');
        container.innerHTML = componentHtml('http://localhost/disclose').replace(
            /url-value/,
            'url-value data-disclose-render-html-value="true" '
        );
        document.body.innerHTML = '';
        document.body.appendChild(container);

        fetchMocker.mockResponse(
            JSON.stringify({ html: '<table><tbody><tr><td>the-secret</td></tr></tbody></table>' })
        );

        await userEvent.click(button(container));

        await waitFor(() => expect(value(container).querySelector('td')).toHaveTextContent('the-secret'));
        expect(content(container)).not.toHaveAttribute('hidden');
    });

    it('scrubs a revealed HTML block on hide', async () => {
        const container = document.createElement('div');
        container.innerHTML = componentHtml('http://localhost/disclose').replace(
            /url-value/,
            'url-value data-disclose-render-html-value="true" '
        );
        document.body.innerHTML = '';
        document.body.appendChild(container);

        fetchMocker.mockResponse(
            JSON.stringify({ html: '<table><tbody><tr><td>the-secret</td></tr></tbody></table>' })
        );

        await userEvent.click(button(container));
        await waitFor(() => expect(value(container).querySelector('td')).toHaveTextContent('the-secret'));

        const hideButton = container.querySelector('[data-disclose-target="hideButton"]') as HTMLButtonElement;
        await userEvent.click(hideButton);

        expect(value(container).querySelector('td')).toBeNull();
        expect(content(container)).toHaveAttribute('hidden');
    });

    it('inserts the revealed value as plain text, never as HTML', async () => {
        const container = mount();
        fetchMocker.mockResponse(JSON.stringify({ value: '<img src=x onerror="document.body.dataset.pwned=1">' }));

        await userEvent.click(button(container));

        await waitFor(() =>
            expect(value(container)).toHaveTextContent('<img src=x onerror="document.body.dataset.pwned=1">')
        );
        expect(value(container).querySelector('img')).toBeNull();
        expect(document.body.dataset.pwned).toBeUndefined();
    });

    it('caches the value: hiding and re-showing does not fetch again', async () => {
        const container = mount();
        fetchMocker.mockResponse(JSON.stringify({ value: 'the-secret' }));

        await userEvent.click(button(container));
        await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));

        const hideButton = container.querySelector('[data-disclose-target="hideButton"]') as HTMLButtonElement;
        await userEvent.click(hideButton);
        expect(value(container)).not.toHaveTextContent('the-secret');

        // Clear the fetch mock: a second request would now fail loudly.
        fetchMocker.resetMocks();

        await userEvent.click(button(container));

        await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));
        expect(error(container)).toHaveAttribute('hidden');
    });

    it('hides the value again and scrubs it from the DOM', async () => {
        const container = mount();
        fetchMocker.mockResponse(JSON.stringify({ value: 'the-secret' }));

        await userEvent.click(button(container));
        await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));

        const hideButton = container.querySelector('[data-disclose-target="hideButton"]') as HTMLButtonElement;
        await userEvent.click(hideButton);

        expect(value(container)).not.toHaveTextContent('the-secret');
        expect(content(container)).toHaveAttribute('hidden');
        expect(button(container)).not.toHaveAttribute('hidden');
    });

    describe('toggle mode', () => {
        const mountToggle = (url = 'http://localhost/disclose'): HTMLElement => {
            const container = document.createElement('div');
            container.innerHTML = toggleComponentHtml(url);
            document.body.innerHTML = '';
            document.body.appendChild(container);

            return container;
        };

        it('keeps the trigger in place and marks it revealed on click', async () => {
            const container = mountToggle();
            fetchMocker.mockResponse(JSON.stringify({ value: 'the-secret' }));

            await userEvent.click(button(container));

            await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));
            expect(content(container)).not.toHaveAttribute('hidden');
            // The single trigger never hides...
            expect(button(container)).not.toHaveAttribute('hidden');
            // ...and flips to the "hide" state.
            expect(button(container)).toHaveAttribute('data-disclose-revealed', 'true');
            expect(button(container)).toHaveAttribute('aria-label', 'Hide');
        });

        it('reveals then hides with the same button', async () => {
            const container = mountToggle();
            fetchMocker.mockResponse(JSON.stringify({ value: 'the-secret' }));

            await userEvent.click(button(container));
            await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));

            await userEvent.click(button(container));

            expect(value(container)).not.toHaveTextContent('the-secret');
            expect(content(container)).toHaveAttribute('hidden');
            expect(button(container)).not.toHaveAttribute('hidden');
            expect(button(container)).not.toHaveAttribute('data-disclose-revealed');
            expect(button(container)).toHaveAttribute('aria-label', 'Reveal');
        });

        it('does not wipe the trigger content while loading', async () => {
            const container = mountToggle();
            let resolveFetch!: (response: Response) => void;
            fetchMocker.mockResponse(
                () =>
                    new Promise<Response>((resolve) => {
                        resolveFetch = resolve;
                    })
            );

            await userEvent.click(button(container));

            await waitFor(() => expect(button(container)).toBeDisabled());
            expect(button(container)).toHaveAttribute('aria-busy', 'true');
            // The icon markup is preserved, not replaced by the loading label.
            expect(button(container)).toHaveTextContent('••••••');

            resolveFetch(
                new Response(JSON.stringify({ value: 'the-secret' }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' },
                })
            );

            await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));
            expect(button(container)).toBeEnabled();
            expect(button(container)).toHaveAttribute('aria-busy', 'false');
        });

        it('caches the value: toggling hide then reveal does not fetch again', async () => {
            const container = mountToggle();
            fetchMocker.mockResponse(JSON.stringify({ value: 'the-secret' }));

            await userEvent.click(button(container));
            await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));

            await userEvent.click(button(container));
            expect(value(container)).not.toHaveTextContent('the-secret');

            // A second request would now fail loudly.
            fetchMocker.resetMocks();

            await userEvent.click(button(container));

            await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));
            expect(error(container)).toHaveAttribute('hidden');
        });

        it('restores the masked placeholder when the value is hidden again', async () => {
            const container = document.createElement('div');
            container.innerHTML = toggleComponentHtml('http://localhost/disclose').replace(
                /<span data-disclose-target="value"><\/span>/,
                '<span data-disclose-target="value">••••••••</span>'
            );
            document.body.innerHTML = '';
            document.body.appendChild(container);

            fetchMocker.mockResponse(JSON.stringify({ value: 'the-secret' }));

            await userEvent.click(button(container));
            await waitFor(() => expect(value(container)).toHaveTextContent('the-secret'));

            await userEvent.click(button(container));

            // The original masked placeholder comes back instead of a blank slot.
            expect(value(container)).toHaveTextContent('••••••••');
            expect(value(container)).not.toHaveTextContent('the-secret');
        });
    });
});
