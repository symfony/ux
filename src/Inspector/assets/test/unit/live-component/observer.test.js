import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { LiveObserver } from '../../../src/live-component/observer';

function runtime() {
    const handlers = new Map();
    return { handlers, on: vi.fn((event, callback) => handlers.set(event, callback)), off: vi.fn() };
}

describe('LiveObserver lifecycle', () => {
    let observer, element, record;
    beforeEach(() => {
        observer = new LiveObserver();
        element = document.createElement('div');
        record = vi.fn();
        observer.setEventRecorder(record);
    });
    afterEach(() => {
        observer.destroy();
        vi.restoreAllMocks();
    });

    it('ignores queued callbacks from a replaced or detached runtime, even if off is unavailable', () => {
        const first = runtime();
        delete first.off;
        element.__component = first;
        observer.observe(element);
        const queued = first.handlers.get('request:started');
        element.__component = runtime();
        queued({});
        expect(record).not.toHaveBeenCalled();
        observer.observe(element);
        queued({});
        expect(record).not.toHaveBeenCalled();
        element.__component.handlers.get('request:started')({});
        expect(record).toHaveBeenCalledOnce();
        observer.remove(element);
        element.__component.handlers.get('request:started')({});
        expect(record).toHaveBeenCalledOnce();
    });

    it('releases the old runtime when its element loses the runtime reference', () => {
        const first = runtime();
        element.__component = first;
        observer.observe(element);
        delete element.__component;
        observer.observe(element);
        expect(first.off).toHaveBeenCalledTimes(5);
        first.handlers.get('render:finished')();
        expect(record).not.toHaveBeenCalled();
    });

    it('cleans partial subscriptions and can retry after an installation failure', () => {
        const component = runtime();
        element.__component = component;
        const warning = vi.spyOn(console, 'warn').mockImplementation(() => {});
        component.on
            .mockImplementationOnce(() => {})
            .mockImplementationOnce(() => {
                throw new Error('failed');
            });
        expect(() => observer.observe(element)).not.toThrow();
        expect(component.off).toHaveBeenCalledTimes(2);
        expect(warning).toHaveBeenCalledOnce();
        observer.observe(element);
        expect(component.on).toHaveBeenCalledTimes(7);
    });

    it('isolates inspector failures from application hooks and measures requests starting at zero', () => {
        element.__component = runtime();
        vi.spyOn(performance, 'now').mockReturnValueOnce(0).mockReturnValueOnce(25);
        const warning = vi.spyOn(console, 'warn').mockImplementation(() => {});
        record.mockImplementationOnce(() => {
            throw new Error('diagnostic failed');
        });
        observer.observe(element);
        expect(() => element.__component.handlers.get('request:started')({})).not.toThrow();
        element.__component.handlers.get('render:finished')();
        expect(observer.read(element).duration).toBe(25);
        expect(warning).toHaveBeenCalledOnce();
    });
});
