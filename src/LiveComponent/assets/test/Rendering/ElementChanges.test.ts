import ElementChanges from '../../src/Rendering/ElementChanges';

describe('ElementChanges', () => {
    it('tracks class additions and removals', async () => {
        const changes = new ElementChanges();
        changes.addClass('new-class1');

        changes.addClass('new-class2');
        changes.removeClass('new-class2');

        changes.addClass('new-class3');
        changes.removeClass('removed-class')

        expect(changes.getAddedClasses()).toHaveLength(2);
        expect(changes.getAddedClasses()).toContain('new-class1');
        expect(changes.getAddedClasses()).toContain('new-class3');
        expect(changes.getRemovedClasses()).toHaveLength(1);
        expect(changes.getRemovedClasses()).toContain('removed-class');
    });

    it('tracks style additions and removals', async () => {
        // red -> blue -> green
        const changes = new ElementChanges();
        changes.addStyle('color', 'blue', 'red');
        changes.addStyle('color', 'green', 'blue');

        changes.addStyle('display', 'none', null);

        changes.removeStyle('margin', '10px');

        expect(changes.getChangedStyles()).toHaveLength(2);
        expect(changes.getChangedStyles()).toContainEqual({ name: 'color', value: 'green'});
        expect(changes.getChangedStyles()).toContainEqual({ name: 'display', value: 'none'});
        expect(changes.getRemovedStyles()).toHaveLength(1);
        expect(changes.getRemovedStyles()).toContain('margin');
    });

    it('tracks attribute additions and removals', async () => {
        const changes = new ElementChanges();
        // bar -> baz -> qux
        changes.addAttribute('data-foo', 'baz', 'bar');
        changes.addAttribute('data-foo', 'qux', 'baz');

        changes.addAttribute('align', 'none', 'block');

        changes.removeAttribute('data-bar', 'some-bar-balue');

        // added, then removed
        changes.addAttribute('disabled', '', null);
        changes.removeAttribute('disabled', '');

        expect(changes.getChangedAttributes()).toHaveLength(2);
        expect(changes.getChangedAttributes()).toContainEqual({ name: 'data-foo', value: 'qux'});
        expect(changes.getChangedAttributes()).toContainEqual({ name: 'align', value: 'none'});
        expect(changes.getRemovedAttributes()).toHaveLength(1);
        expect(changes.getRemovedAttributes()).toContain('data-bar');
    });

    it('will apply changes to an element', async () => {
        const element = document.createElement('div');
        element.className = 'original-class';
        element.style.color = 'red';
        element.setAttribute('data-foo', 'bar');

        const changes = new ElementChanges();
        changes.addClass('new-class');
        changes.addStyle('color', 'blue', 'red');
        changes.addAttribute('data-foo', 'baz', 'bar');

        changes.applyToElement(element);

        expect(element.className).toBe('original-class new-class');
        expect(element.style.color).toBe('blue');
        expect(element.getAttribute('data-foo')).toBe('baz');
    });
});
