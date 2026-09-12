import { afterEach, expect, it, vi } from 'vitest';
import { createPullTab } from '../../../src/ui/pull-tab';

const lifetime = new AbortController();
afterEach(() => document.body.replaceChildren());

it('uses a non-blocking proximity listener and releases it with the runtime', () => {
    const host = document.createElement('div');
    host.isOpen = false;
    document.body.append(host);
    const open = vi.fn();
    const tab = createPullTab(host, open, lifetime.signal);
    host.append(tab);
    const move = (x, y) =>
        document.dispatchEvent(
            new MouseEvent('pointermove', {
                clientX: x,
                clientY: y,
                bubbles: true,
                cancelable: true,
            })
        );
    expect(move(window.innerWidth - 39, window.innerHeight / 2 + 51)).toBe(true);
    expect(tab.hasAttribute('data-near')).toBe(true);
    move(window.innerWidth - 41, window.innerHeight / 2);
    expect(tab.hasAttribute('data-near')).toBe(false);
    move(window.innerWidth, window.innerHeight / 2 + 53);
    expect(tab.hasAttribute('data-near')).toBe(false);
    move(window.innerWidth, window.innerHeight / 2);
    tab.querySelector('button').click();
    expect(open).toHaveBeenCalledOnce();
    expect(tab.hasAttribute('data-near')).toBe(false);
    host.isOpen = true;
    move(window.innerWidth, window.innerHeight / 2);
    expect(tab.hasAttribute('data-near')).toBe(false);
    lifetime.abort();
    host.isOpen = false;
    move(window.innerWidth, window.innerHeight / 2);
    tab.querySelector('button').click();
    expect(open).toHaveBeenCalledOnce();
    expect(tab.hasAttribute('data-near')).toBe(false);
});
