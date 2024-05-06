import ExternalMutationTracker from '../../src/Rendering/ExternalMutationTracker';
import { htmlToElement } from '../../src/dom_utils';

const mountElement = (html: string): HTMLElement => {
    const element = htmlToElement(html);
    document.body.innerHTML = '';
    document.body.appendChild(element);

    return element;
}

const createTracker = (html: string): { element: HTMLElement, tracker: ExternalMutationTracker } => {
    const element = mountElement(html);
    const tracker = new ExternalMutationTracker(element, () => true);
    tracker.start();

    return { element, tracker };
}

/*
 * This is a hack to get around the fact that MutationObserver doesn't fire synchronously.
 */
const shortTimeout = (): Promise<void> => {
    return new Promise((resolve) => {
        setTimeout(resolve, 10);
    });
}

describe('ExternalMutationTracker', () => {
    it('can track generic attribute changes', async () => {
        const { element, tracker } = createTracker(`
            <div id="original-id" title="I'm a div!" data-changing="original">Text inside!</div>
        `)

        // change x2
        element.setAttribute('id', 'middle-id');
        element.setAttribute('id', 'new-id');
        // add new
        element.setAttribute('data-foo', 'bar');
        // remove
        element.removeAttribute('title');
        // add then remove
        element.setAttribute('disabled', '');
        element.removeAttribute('disabled');
        // change, then change back
        element.setAttribute('data-changing', 'changed');
        element.setAttribute('data-changing', 'original');
        // throw some unrelated changes in there
        element.innerHTML = 'New text! <span>And a span!</span>';
        await shortTimeout();

        expect(tracker.changedElementsCount).toBe(1);
        const changes = tracker.getChangedElement(element);
        if (!changes) {
            throw new Error('Expected changes to be present');
        }

        expect(changes.getAddedClasses()).toHaveLength(0);
        expect(changes.getRemovedClasses()).toHaveLength(0);
        expect(changes.getChangedStyles()).toHaveLength(0);
        expect(changes.getRemovedStyles()).toHaveLength(0);
        expect(changes.getChangedAttributes()).toEqual([
            { name: 'id', value: 'new-id' },
            { name: 'data-foo', value: 'bar' },
        ]);
        expect(changes.getRemovedAttributes()).toEqual(['title']);
    });

    it('can track style changes', async () => {
        const { element, tracker } = createTracker(`
            <div id="original-id" style="display: none; margin: 10px; flex-basis: auto">Text inside!</div>
        `)

        // change x2
        element.style.display = 'block';
        element.style.display = 'inline';
        // add new
        element.style.color = 'red';
        // add new with a ":" in it
        element.style.backgroundImage = 'url(https://example.com/image.png)';
        // remove
        element.style.removeProperty('margin');
        // add then remove
        element.style.setProperty('padding', '10px');
        element.style.removeProperty('padding');
        // change, then change back
        element.style.flexBasis = 'length';
        element.style.flexBasis = 'auto';
        await shortTimeout();

        const changes = tracker.getChangedElement(element);
        if (!changes) {
            throw new Error('Expected changes to be present');
        }
        expect(changes.getAddedClasses()).toHaveLength(0);
        expect(changes.getRemovedClasses()).toHaveLength(0);
        expect(changes.getChangedAttributes()).toHaveLength(0);
        expect(changes.getRemovedAttributes()).toHaveLength(0);

        expect(changes.getChangedStyles()).toEqual([
            { name: 'display', value: 'inline' },
            { name: 'color', value: 'red' },
            { name: 'background-image', value: 'url(https://example.com/image.png)' },
        ]);
        expect(changes.getRemovedStyles()).toEqual(['margin']);
    });

    it('can track class changes', async () => {
        const { element, tracker } = createTracker(`
            <div class="first-class second-class">Text inside!</div>
        `)

        // remove then add back
        element.classList.remove('second-class');
        element.classList.add('second-class');
        // add new (with some whitespace to be sneaky)
        element.setAttribute('class', ` ${element.getAttribute('class')} \n    new-class `)
        // remove
        element.classList.remove('first-class');
        // add then remove
        element.classList.add('tmp-class');
        element.classList.remove('tmp-class');
        await shortTimeout();

        const changes = tracker.getChangedElement(element);
        if (!changes) {
            throw new Error('Expected changes to be present');
        }
        expect(changes.getChangedStyles()).toHaveLength(0);
        expect(changes.getRemovedStyles()).toHaveLength(0);
        expect(changes.getChangedAttributes()).toHaveLength(0);
        expect(changes.getRemovedAttributes()).toHaveLength(0);

        expect(changes.getAddedClasses()).toEqual(['new-class']);
        expect(changes.getRemovedClasses()).toEqual(['first-class']);
    });

    it('can track class changes with whitespaces', async () => {
        const { element, tracker } = createTracker(`
            <div 
                class="
                    first-class
                    second-class
                    third-class
                "
            >Text inside!</div>
        `)

        element.classList.remove('second-class');
        element.classList.add('new-class');
        await shortTimeout();

        const changes = tracker.getChangedElement(element);
        if (!changes) {
            throw new Error('Expected changes to be present');
        }
        expect(changes.getChangedStyles()).toHaveLength(0);
        expect(changes.getRemovedStyles()).toHaveLength(0);
        expect(changes.getChangedAttributes()).toHaveLength(0);
        expect(changes.getRemovedAttributes()).toHaveLength(0);

        expect(changes.getAddedClasses()).toEqual(['new-class']);
        expect(changes.getRemovedClasses()).toEqual(['second-class']);
    });

    it('can track added element', async () => {
        const { element, tracker } = createTracker(`
            <div>
                Text inside!
                <span id="original-span1">the span 1</span>
                <span id="original-span2">the span 2</span>
            </div>
        `)

        const span1 = document.getElementById('original-span1') as HTMLElement;
        const span2 = document.getElementById('original-span2') as HTMLElement;

        // remove then add back
        element.removeChild(span1);
        element.appendChild(span1);
        // add new
        element.appendChild(htmlToElement('<span id="new-span">the new span</span>'));
        // remove
        element.removeChild(span2);
        // add then remove
        const tmpSpan = htmlToElement('<span id="tmp-span">the tmp span</span>');
        element.appendChild(tmpSpan);
        element.removeChild(tmpSpan);
        await shortTimeout();

        const addedElements = tracker.getAddedElements();
        expect(addedElements).toHaveLength(1);
        expect(addedElements[0]).toBe(document.getElementById('new-span'));
    });

    it('ignores changes inside added elements', async () => {
        const { element, tracker } = createTracker(`
           <div>Text inside!</div>
       `);

        const span = document.createElement('span');
        element.appendChild(span);
        await shortTimeout();

        expect(tracker.getAddedElements()).toHaveLength(1);

        // just a sanity check to make sure we're tracking
        element.classList.add('new-class');
        span.classList.add('new-span-class');
        span.setAttribute('disabled', '');
        span.appendChild(document.createElement('div'));
        await shortTimeout();

        // still just 1 element added
        expect(tracker.getAddedElements()).toHaveLength(1);
        expect(tracker.changedElementsCount).toBe(1);
        expect(tracker.getChangedElement(element)).not.toBeNull()
    });

    it('ignores changes based on the callback', async () => {
        const element = mountElement(`
            <div>Text inside</div>
        `);
        // callback now always returns false
        const tracker = new ExternalMutationTracker(element, () => false);
        tracker.start();

        const span = document.createElement('span');
        element.appendChild(span);
        element.classList.add('new-class');

        await shortTimeout();

        // all changes are ignored
        expect(tracker.getAddedElements()).toHaveLength(0);
        expect(tracker.changedElementsCount).toBe(0);
    });

    it('ignores disabled -> disabled="disabled" non-changes', async () => {
        const { element, tracker } = createTracker(`
           <button disabled>Text inside!</button>
       `);

        element.setAttribute('disabled', 'disabled');
        await shortTimeout();

        expect(tracker.changedElementsCount).toBe(0);
    });
});
