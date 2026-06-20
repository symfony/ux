import { describe, it, expect, vi } from 'vitest';
import { setupAutosave } from '../src/live/live-editor.js';

describe('setupAutosave', () => {
    it('debounces ux:editor:change, dispatches with field + last value', async () => {
        vi.useFakeTimers();
        const dispatched: any[] = [];
        const root = document.createElement('div');
        document.body.append(root);
        setupAutosave(root, {
            field: 'body',
            debounceMs: 100,
            dispatch: (field, content) => {
                dispatched.push({ field, content });
                return Promise.resolve();
            },
        });

        root.dispatchEvent(new CustomEvent('ux:editor:change', { bubbles: true, detail: { value: 'a' } }));
        root.dispatchEvent(new CustomEvent('ux:editor:change', { bubbles: true, detail: { value: 'b' } }));
        expect(dispatched).toEqual([]);
        vi.advanceTimersByTime(99);
        expect(dispatched).toEqual([]);
        vi.advanceTimersByTime(1);
        await Promise.resolve();
        expect(dispatched).toEqual([{ field: 'body', content: 'b' }]);
        vi.useRealTimers();
        root.remove();
    });
});
