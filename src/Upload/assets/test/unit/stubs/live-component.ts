/**
 * Test stub for the optional `@symfony/ux-live-component` peer.
 *
 * Aliased in place of the real package (vitest.config.mjs) so the live-upload
 * bridge can be tested without installing LiveComponent. Exposes the same spies
 * the bridge calls, so tests can assert on them.
 */
import { vi } from 'vitest';

export const action = vi.fn();

export const getComponent = vi.fn(async (_element: HTMLElement) => ({
    action,
    set: vi.fn(),
    render: vi.fn(),
}));
