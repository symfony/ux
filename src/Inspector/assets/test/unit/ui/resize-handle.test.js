import { describe, expect, it, vi } from 'vitest';
import { ResizeHandle } from '../../../src/ui/resize-handle';

function fixture(axis = 'width') {
    let size = 300;
    const target = document.createElement('div');
    const write = vi.fn((value) => (size = Math.min(500, Math.max(200, value))));
    const handle = new ResizeHandle({ target, axis, label: 'Resize', className: 'resize', read: () => size, write });
    target.append(handle.element);
    const pointer = (type, coordinate = 0) =>
        handle.element.dispatchEvent(
            new MouseEvent(type, {
                cancelable: true,
                button: 0,
                clientX: coordinate,
                clientY: coordinate,
            })
        );
    return { handle, target, write, pointer };
}

describe('ResizeHandle', () => {
    it.each([
        ['width', 'ArrowLeft', 'ArrowRight'],
        ['height', 'ArrowUp', 'ArrowDown'],
    ])('resizes %s with the keyboard and reports the clamped size', (axis, increase, decrease) => {
        const { handle, write } = fixture(axis);
        handle.element.dispatchEvent(new KeyboardEvent('keydown', { key: increase }));
        expect(write).toHaveBeenLastCalledWith(316);
        handle.element.dispatchEvent(new KeyboardEvent('keydown', { key: decrease }));
        expect(write).toHaveBeenLastCalledWith(300);
        expect(handle.element.getAttribute('aria-valuenow')).toBe('300');
        handle.destroy();
    });

    it.each(['pointerup', 'pointercancel', 'lostpointercapture'])('ends the drag on %s', (type) => {
        const { handle, target, write, pointer } = fixture();
        pointer('pointerdown', 300);
        pointer('pointermove', 100);
        expect(write).toHaveBeenLastCalledWith(500);
        expect(target.hasAttribute('data-resizing')).toBe(true);
        pointer(type);
        write.mockClear();
        pointer('pointermove', 80);
        expect(write).not.toHaveBeenCalled();
        expect(target.hasAttribute('data-resizing')).toBe(false);
        handle.destroy();
    });

    it('releases pointer and keyboard listeners when destroyed during a drag', () => {
        const { handle, target, write, pointer } = fixture();
        pointer('pointerdown');
        handle.destroy();
        pointer('pointermove', 50);
        handle.element.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowLeft' }));
        expect(write).not.toHaveBeenCalled();
        expect(target.hasAttribute('data-resizing')).toBe(false);
    });
});
