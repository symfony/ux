import { afterEach, describe, expect, it } from 'vitest';
import { getValueFromElement, htmlToElement } from '../../src/dom_utils';
import { executeMorphdom } from '../../src/morphdom';
import ExternalMutationTracker from '../../src/Rendering/ExternalMutationTracker';
import { shadowIdWithNamedControl } from '../tools';

describe('executeMorphdom', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('restores a child client ID when parent rendering fails', () => {
        const from = htmlToElement('<div><div id="client-child"></div></div>');
        document.body.append(from);
        const child = from.firstElementChild as HTMLElement;
        const tracker = new ExternalMutationTracker(from, () => true);
        const to = htmlToElement('<div><div data-live-preserve></div></div>');

        expect(() =>
            executeMorphdom(from, to, [], getValueFromElement, tracker, [{ element: child, id: 'server-child' }])
        ).toThrow('requires an id');
        expect(child.id).toBe('client-child');
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

    it('morphs a form that has a control named id', () => {
        const html = (title: string) =>
            `<div><form id="edit-form"><input type="hidden" name="id" value="5"><input name="title" value="${title}"></form><p>${title}</p></div>`;
        const from = htmlToElement(html('before'));
        document.body.append(from);
        const form = from.querySelector('form') as HTMLFormElement;
        const title = form.querySelector('[name="title"]') as HTMLInputElement;
        title.focus();
        const to = htmlToElement(html('after'));
        shadowIdWithNamedControl(form);
        shadowIdWithNamedControl(to.querySelector('form') as HTMLFormElement);

        executeMorphdom(from, to, [], getValueFromElement, new ExternalMutationTracker(from, () => true));

        expect(from.querySelector('[name="title"]')).toBe(title);
        expect(title.value).toBe('after');
        expect(document.activeElement).toBe(title);
        expect(from.querySelector('p')?.textContent).toBe('after');
    });
});
