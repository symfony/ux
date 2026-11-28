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

    it('keeps the value typed into an input that the new HTML renders without a value attribute', () => {
        const from = htmlToElement(
            '<div><input name="title" value="before" class="pristine"><input name="slug" value="before"></div>'
        );
        document.body.append(from);
        const title = from.firstElementChild as HTMLInputElement;
        title.value = 'typed';
        const to = htmlToElement('<div><input name="title" aria-invalid="true"><input name="slug"></div>');

        executeMorphdom(from, to, [title], getValueFromElement, new ExternalMutationTracker(from, () => true));

        expect(title.value).toBe('typed');
        expect(title).toHaveAttribute('aria-invalid', 'true');
        expect(title).not.toHaveAttribute('class');
        expect((from.lastElementChild as HTMLInputElement).value).toBe('');
    });

    it('removes the value attribute of a changed checkbox that the new HTML renders without it', () => {
        const from = htmlToElement('<div><input type="checkbox" name="agree" value="1"></div>');
        document.body.append(from);
        const agree = from.firstElementChild as HTMLInputElement;
        agree.checked = true;
        const to = htmlToElement('<div><input type="checkbox" name="agree" aria-invalid="true"></div>');

        executeMorphdom(from, to, [agree], getValueFromElement, new ExternalMutationTracker(from, () => true));

        expect(agree.checked).toBe(true);
        expect(agree).not.toHaveAttribute('value');
    });
});
