import { afterEach, describe, expect, it, vi } from 'vitest';
import { bindOpenShortcut } from '../../../src/ui/open-shortcut';

afterEach(() => document.body.replaceChildren());

describe('open shortcut', () => {
    it('ignores typing in editable descendants and shadow roots', () => {
        const lifetime = new AbortController();
        const open = vi.fn();
        bindOpenShortcut(open, lifetime.signal);
        const editor = document.createElement('div');
        editor.contentEditable = 'true';
        editor.setAttribute('contenteditable', 'true');
        const span = editor.appendChild(document.createElement('span'));
        const host = document.createElement('div');
        const input = host.attachShadow({ mode: 'open' }).appendChild(document.createElement('input'));
        document.body.append(editor, host);
        for (const target of [span, input]) {
            for (const key of ['u', 'x'])
                target.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true, composed: true }));
        }
        expect(open).not.toHaveBeenCalled();
        lifetime.abort();
    });

    it('resets on modified or composed input and detaches on abort', () => {
        const lifetime = new AbortController();
        const open = vi.fn();
        bindOpenShortcut(open, lifetime.signal);
        for (const options of [{ ctrlKey: true }, { isComposing: true }, { repeat: true }]) {
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'u' }));
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'x', ...options }));
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'x' }));
        }
        expect(open).not.toHaveBeenCalled();
        lifetime.abort();
        for (const key of ['u', 'x']) document.dispatchEvent(new KeyboardEvent('keydown', { key }));
        expect(open).not.toHaveBeenCalled();
    });
});
