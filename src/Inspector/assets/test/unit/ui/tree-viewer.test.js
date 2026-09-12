import { TreeViewer } from '../../../src/ui/tree-viewer';
import { describe, it, expect } from 'vitest';
import { makeField } from '../../../src/ui/fields';

describe('TreeViewer', () => {
    describe('render()', () => {
        it('renders primitive string value', () => {
            const tree = TreeViewer.render('hello');
            expect(tree.className).toBe('tree');
            expect(tree.textContent).toContain('"hello"');
            expect(tree.querySelector('.v-str')).not.toBeNull();
        });

        it('renders primitive number value', () => {
            const tree = TreeViewer.render(42);
            expect(tree.querySelector('.v-num')).not.toBeNull();
            expect(tree.textContent).toContain('42');
        });

        it('renders primitive boolean value', () => {
            const tree = TreeViewer.render(true);
            expect(tree.querySelector('.v-bool')).not.toBeNull();
            expect(tree.textContent).toContain('true');
        });

        it('renders null value', () => {
            const tree = TreeViewer.render(null);
            expect(tree.querySelector('.v-nul')).not.toBeNull();
            expect(tree.textContent).toContain('null');
        });

        it('renders flat object with keys and values', () => {
            const tree = TreeViewer.render({ name: 'test', count: 5, active: true });
            expect(tree.querySelectorAll('.key').length).toBe(3);
            expect(tree.querySelector('.v-str').textContent).toContain('"test"');
            expect(tree.querySelector('.v-num').textContent).toContain('5');
            expect(tree.querySelector('.v-bool').textContent).toContain('true');
        });

        it('renders nested objects with expandable nodes', () => {
            const tree = TreeViewer.render({ outer: { inner: 'value' } });
            const details = tree.querySelectorAll('details');
            expect(details.length).toBeGreaterThan(0);
            expect([...details].every((item) => item.open === false)).toBe(true);
            const nests = tree.querySelectorAll('.nest');
            expect(nests.length).toBeGreaterThan(0);
        });

        it('renders array items without inventing numeric keys', () => {
            const tree = TreeViewer.render(['a', 'b', 'c']);
            expect(tree.classList.contains('array')).toBe(true);
            expect(tree.querySelectorAll('.key')).toHaveLength(0);
            expect([...tree.querySelectorAll('.array-item')].map((item) => item.textContent)).toEqual([
                '"a"',
                '"b"',
                '"c"',
            ]);
        });

        it('uses native details disclosure for nested nodes', () => {
            const tree = TreeViewer.render({ data: { value: 1 } });
            document.body.appendChild(tree);
            const details = tree.querySelector('details');
            expect(details.open).toBe(false);
            details.querySelector('summary').click();
            expect(details.open).toBe(true);
            details.querySelector('summary').click();
            expect(details.open).toBe(false);
            document.body.removeChild(tree);
        });

        it('respects maxDepth limit', () => {
            const deep = { a: { b: { c: { d: { e: 'deep' } } } } };
            const tree = TreeViewer.render(deep, 2);
            // At depth 2, the inner objects should be shown as JSON instead of expanded
            const types = tree.querySelectorAll('.type');
            expect(types.length).toBeGreaterThan(0);
        });

        it('redacts secret-like object keys at every rendered level', () => {
            const tree = TreeViewer.render({ apiToken: 'visible-token', nested: { password: 'visible-password' } });

            expect(tree.textContent).toContain('[redacted]');
            expect(tree.textContent).not.toContain('visible-token');
            expect(tree.textContent).not.toContain('visible-password');
        });

        it('redacts secret-like component fields', () => {
            const field = makeField('csrfToken', 'visible-secret');

            expect(field.textContent).toContain('[redacted]');
            expect(field.textContent).not.toContain('visible-secret');
        });
    });
});
