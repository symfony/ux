/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { StreamActions, visit } from '@hotwired/turbo';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import '../../src/stream_actions';

vi.mock('@hotwired/turbo', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@hotwired/turbo')>()),
    visit: vi.fn(),
}));

const absoluteUrl = (url: string) => new URL(url, document.baseURI).href;

const redirect = (attributes: Record<string, string>) => {
    const element = document.createElement('turbo-stream');
    for (const [name, value] of Object.entries(attributes)) {
        element.setAttribute(name, value);
    }

    StreamActions.redirect.call(element as never);
};

describe('redirect stream action', () => {
    const originalLocation = window.location;
    let assign: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        assign = vi.fn();
        Object.defineProperty(window, 'location', {
            value: { origin: new URL(document.baseURI).origin, assign },
            writable: true,
            configurable: true,
        });
    });

    afterEach(() => {
        Object.defineProperty(window, 'location', {
            value: originalLocation,
            writable: true,
            configurable: true,
        });
        vi.clearAllMocks();
    });

    it('is registered', () => {
        expect(StreamActions.redirect).toBeTypeOf('function');
    });

    it('performs a Turbo visit replacing the history entry by default', () => {
        redirect({ url: '/tasks' });

        expect(visit).toHaveBeenCalledWith(absoluteUrl('/tasks'), { action: 'replace' });
        expect(assign).not.toHaveBeenCalled();
    });

    it('performs a Turbo visit advancing the history with [advance]', () => {
        redirect({ url: '/tasks', advance: '' });

        expect(visit).toHaveBeenCalledWith(absoluteUrl('/tasks'), { action: 'advance' });
    });

    it('performs a Turbo visit on an absolute same-origin [url]', () => {
        redirect({ url: absoluteUrl('/tasks') });

        expect(visit).toHaveBeenCalledWith(absoluteUrl('/tasks'), { action: 'replace' });
        expect(assign).not.toHaveBeenCalled();
    });

    it('performs a full page load on a cross-origin [url]', () => {
        redirect({ url: 'https://example.com/tasks' });

        expect(assign).toHaveBeenCalledWith('https://example.com/tasks');
        expect(visit).not.toHaveBeenCalled();
    });

    it.each([
        'javascript:alert(1)',
        'JaVaScRiPt:alert(1)',
        'java\nscript:alert(1)',
        'data:text/html,<script>alert(1)</script>',
    ])('throws on the non-http(s) [url] %j, without navigating', (url) => {
        expect(() => redirect({ url })).toThrowError(/must use the http or https scheme/);
        expect(assign).not.toHaveBeenCalled();
        expect(visit).not.toHaveBeenCalled();
    });

    it('throws without an [url]', () => {
        expect(() => redirect({})).toThrowError('The "url" attribute is required on <turbo-stream action="redirect">.');
        expect(visit).not.toHaveBeenCalled();
    });
});
