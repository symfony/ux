import { parseActionParameters } from '../../../src/stimulus/attributes';
import { parseActionDescriptor } from '../../../src/stimulus/attributes';
import { describe, expect, it, vi } from 'vitest';
import {
    makeElementField,
    makeEmptyField,
    makeField,
    makeGroup,
    makeKeyValueList,
    safeUrl,
    safeValue,
} from '../../../src/ui/fields';

describe('plugin helpers', () => {
    it('redacts adjacent sensitive query parameters even when keys are repeated', () => {
        const url = new URL(safeUrl('/path?token=a&token=b&secret=c&q=public'), document.baseURI);
        expect([...url.searchParams]).toEqual([
            ['token', '[redacted]'],
            ['secret', '[redacted]'],
            ['q', 'public'],
        ]);
    });

    it('parses scoped parameters while retaining strings, exclusions, and redaction', () => {
        const button = document.createElement('button');
        button.setAttribute('data-live-count-param', '2');
        button.setAttribute('data-live-label-param', 'hello');
        button.setAttribute('data-live-options-param', '{"enabled":true}');
        button.setAttribute('data-live-action-param', 'save');
        button.setAttribute('data-live-token-param', 'secret');
        button.setAttribute('data-other-count-param', '9');
        expect(parseActionParameters(button, 'live', ['action'])).toEqual({
            count: 2,
            label: 'hello',
            options: { enabled: true },
            token: '[redacted]',
        });
    });

    it('renders reusable semantic key/value lists inside native disclosure groups', () => {
        const list = makeKeyValueList([makeField('step', 1)]);
        const group = makeGroup('Values', [list], { key: 'stimulus-values' });

        expect(list.tagName).toBe('DL');
        expect(list.querySelector('dt').textContent).toBe('step');
        expect(list.querySelector('dd').textContent).toBe('1');
        expect(group.tagName).toBe('DETAILS');
        expect(group.open).toBe(true);
        expect(group.dataset.group).toBe('stimulus-values');
        expect(group.querySelector(':scope > summary.title > .name').textContent).toBe('Values');
        expect(group.querySelector(':scope > summary > .badge')).toBeNull();
        expect(group.querySelector(':scope > summary b')).toBeNull();
        expect(group.querySelector(':scope > .content > dl')).toBe(list);
    });

    it('does not turn row counts into section metadata', () => {
        const group = makeGroup('Values', [makeKeyValueList([makeField('step', 1), makeField('count', 2)])]);

        expect(group.querySelector('.badge')).toBeNull();
    });

    it('renders an optional semantic icon and never title metadata', () => {
        const group = makeGroup('Connections', [makeKeyValueList([makeField('outlet', 'greeter')])], {
            icon: 'overlay',
            meta: '1 outlet',
        });

        expect(group.querySelector(':scope > .title > .icon svg')).not.toBeNull();
        expect(group.querySelector(':scope > .title > .name').textContent).toBe('Connections');
        expect(group.querySelector(':scope > .title > .badge')).toBeNull();
        expect(group.hasAttribute('data-tab')).toBe(false);
    });

    it('renders a static group as a non-collapsible flat section', () => {
        const group = makeGroup('Props', [makeKeyValueList([makeField('query', 'ux')])], {
            key: 'livecomponent-props',
            static: true,
        });

        expect(group.tagName).toBe('SECTION');
        expect(group.className).toBe('group');
        expect(group.hasAttribute('data-static')).toBe(true);
        expect(group.querySelector(':scope > summary')).toBeNull();
        expect(group.querySelector(':scope > .title').textContent).toBe('Props');
        expect(group.querySelector(':scope > .content').textContent).toContain('queryux');
    });

    it('renders an explicitly disabled empty group', () => {
        const group = makeGroup('Parent component', [], { empty: true });
        const summary = group.querySelector('summary');

        expect(group.hasAttribute('data-empty')).toBe(true);
        expect(group.open).toBe(false);
        expect(summary.getAttribute('aria-disabled')).toBe('true');
        expect(summary.getAttribute('aria-label')).toBe('Parent component, none');
        summary.click();
        expect(group.open).toBe(false);
    });

    it('preserves action filters, global scope and options', () => {
        expect(parseActionDescriptor('keydown.esc@window->modal#close:prevent:stop')).toEqual({
            event: 'keydown',
            trigger: 'keydown.esc@window',
            scope: 'window',
            filters: ['esc'],
            controller: 'modal',
            method: 'close',
            options: ['prevent', 'stop'],
        });
    });

    it('redacts and bounds values before rendering storage', () => {
        expect(safeValue({ csrfToken: 'private', title: 'x'.repeat(600) })).toEqual({
            csrfToken: '[redacted]',
            title: 'x'.repeat(500),
        });
    });

    it('redacts previous secret values in change tooltips', () => {
        const field = makeField('password', 'new-private', { changed: true, previous: 'old-private' });

        expect(field.textContent).toContain('[redacted]');
        expect(field.title).toBe('Changed from [redacted]');
        expect(field.outerHTML).not.toContain('private');
    });

    it('does not invoke accessors and handles cycles', () => {
        const value = {
            get privateValue() {
                throw new Error('must not run');
            },
        };
        value.self = value;

        expect(safeValue(value)).toEqual({ privateValue: '[accessor]', self: '[circular]' });
    });

    it('redacts secret-bearing URL parameters before storage', () => {
        expect(safeUrl('/component?page=2&token=private')).toContain('page=2');
        expect(safeUrl('/component?page=2&token=private')).toContain('token=%5Bredacted%5D');
        expect(safeUrl('/component?page=2&token=private')).not.toContain('private');
    });

    it('previews on pointer and focus and pins on activation', () => {
        const target = document.createElement('button');
        target.scrollIntoView = vi.fn();
        document.body.appendChild(target);
        const field = makeElementField('save', target, { framework: 'livecomponent', badge: 'action: save' });
        const pill = field.querySelector('.target-pill');
        const previews = [];
        const clears = [];
        const selections = [];
        field.addEventListener('preview-element', (event) => previews.push(event.detail));
        field.addEventListener('clear-element-preview', (event) => clears.push(event.detail));
        field.addEventListener('select-element', (event) => selections.push(event.detail));

        pill.dispatchEvent(new Event('pointerenter'));
        pill.dispatchEvent(new FocusEvent('focus'));
        pill.dispatchEvent(new FocusEvent('blur'));
        pill.click();

        expect(previews).toHaveLength(2);
        expect(previews[0]).toMatchObject({ element: target, framework: 'livecomponent', label: 'action: save' });
        expect(clears).toHaveLength(1);
        expect(selections).toHaveLength(1);
        expect(target.scrollIntoView).toHaveBeenCalled();
        expect(field.querySelector('.act')).toBeNull();
    });

    it('does not add a redundant copy action to value rows', () => {
        const field = makeField('count', 42);
        expect(field.querySelector('.icon-btn')).toBeNull();
        expect(field.querySelector('.key-value-action')).toBeNull();
    });

    it('lets long primitive keys, values and element labels expand inline', () => {
        const field = makeField('component.selector.with.context', 'div#counter-with-a-very-long-generated-selector');
        const value = field.querySelector('.value');
        const key = field.querySelector('dt');

        expect(key.classList.contains('expandable-text')).toBe(true);
        expect(value.classList.contains('expandable-text')).toBe(true);
        value.click();
        expect(value.classList.contains('expanded')).toBe(true);

        const target = document.createElement('div');
        target.id = 'counter-with-a-long-selector';
        target.scrollIntoView = vi.fn();
        const elementField = makeElementField('target', target, { detail: 'no matching element' });
        expect(elementField.querySelector('.target-pill').classList.contains('expandable-text')).toBe(true);
        expect(elementField.querySelector('.value-note').classList.contains('expandable-text')).toBe(true);
    });

    it('renders missing data as a muted key without an invented value', () => {
        const field = makeEmptyField('optional');

        expect(field.className).toBe('key-value');
        expect(field.hasAttribute('data-empty')).toBe(true);
        expect(field.querySelector('dt').textContent).toBe('optional');
        expect(field.querySelector('.value').textContent).toBe('');
        expect(field.querySelector('.key-value-action')).toBeNull();
    });

    it('renders an empty string explicitly', () => {
        const field = makeField('query', '');

        expect(field.querySelector('.value').textContent).toBe('(empty)');
        expect(field.querySelector('.value .nul')).not.toBeNull();
    });

    it('renders explicitly multiple values on separate lines', () => {
        const field = makeField('counter outlet', '#counter-1, #counter-2', { multiline: true });
        const values = [...field.querySelectorAll('.value-list__item')];

        expect(values.map((item) => item.textContent)).toEqual(['#counter-1', '#counter-2']);
        expect(field.querySelector('.value').classList.contains('expandable-text')).toBe(false);
    });

    it('marks vertical fields without turning their value into an inline disclosure', () => {
        const field = makeField('url', '/_components/SearchDashboard', { vertical: true });

        expect(field.hasAttribute('data-vertical')).toBe(true);
        expect(field.querySelector('.value').textContent).toBe('/_components/SearchDashboard');
        expect(field.querySelector('.value').classList.contains('expandable-text')).toBe(false);
    });

    it('renders structured values inside the value column with collapsed nested disclosures', () => {
        const field = makeField('Detail', {
            method: 'GET',
            headers: { Accept: 'text/html', nested: { enabled: true } },
        });

        expect(field.className).toBe('key-value');
        expect(field.hasAttribute('data-structured')).toBe(true);
        expect(field.children[0].tagName).toBe('DT');
        expect(field.children[1].className).toBe('value-meta');
        expect(field.children[2].classList.contains('value')).toBe(true);
        expect(field.querySelector('.value-meta').textContent).toBe('Object · 2');
        expect(field.querySelector('.value .tree')).not.toBeNull();
        expect([...field.querySelectorAll('.tree details')].every((details) => details.open === false)).toBe(true);
        expect(field.querySelector('.tree .key').textContent).toBe('method');
    });

    it('bounds tree depth, handles cycles, and keeps untrusted text inert', () => {
        const value = { html: '<img src=x onerror=alert(1)>' };
        value.self = value;
        const field = makeField('Detail', value);

        expect(field.textContent).toContain('<img src=x onerror=alert(1)>');
        expect(field.textContent).toContain('[circular]');
        expect(field.querySelector('img')).toBeNull();
    });
});
