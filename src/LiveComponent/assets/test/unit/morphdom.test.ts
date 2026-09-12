import { afterEach, describe, expect, it } from 'vitest';
import { getValueFromElement, htmlToElement } from '../../src/dom_utils';
import { executeMorphdom } from '../../src/morphdom';
import ExternalMutationTracker from '../../src/Rendering/ExternalMutationTracker';

describe('executeMorphdom', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('restores client IDs when rendering fails', () => {
        const from = htmlToElement('<div><input id="server"></div>');
        document.body.append(from);
        const tracker = new ExternalMutationTracker(from, () => true);
        tracker.start();
        const field = from.firstElementChild as HTMLElement;
        field.id = 'client';
        tracker.handlePendingChanges();
        tracker.stop();
        const to = htmlToElement('<div><div data-live-preserve></div></div>');
        expect(() => executeMorphdom(from, to, [], getValueFromElement, tracker)).toThrow('requires an id');
        expect(field.id).toBe('client');
    });
});
