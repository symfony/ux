import { cloneElementWithNewTagName, cloneHTMLElement, setValueOnElement } from './dom_utils';
import morphdom from 'morphdom';
import { normalizeAttributesForComparison } from './normalize_attributes_for_comparison';
import Component from './Component';
import ExternalMutationTracker from './Rendering/ExternalMutationTracker';

export function executeMorphdom(
    rootFromElement: HTMLElement,
    rootToElement: HTMLElement,
    modifiedFieldElements: Array<HTMLElement>,
    getElementValue: (element: HTMLElement) => any,
    childComponents: Component[],
    findChildComponent: (id: string, element: HTMLElement) => HTMLElement | null,
    getKeyFromElement: (element: HTMLElement) => string | null,
    externalMutationTracker: ExternalMutationTracker
) {
    const childComponentMap: Map<HTMLElement, Component> = new Map();
    childComponents.forEach((childComponent) => {
        childComponentMap.set(childComponent.element, childComponent);
        if (!childComponent.id) {
            throw new Error('Child is missing id.');
        }
        const childComponentToElement = findChildComponent(childComponent.id, rootToElement);
        if (childComponentToElement && childComponentToElement.tagName !== childComponent.element.tagName) {
            // we need to "correct" the tag name for the child to match the "from"
            // so that we always get a "diff", not a remove/add
            const newTag = cloneElementWithNewTagName(childComponentToElement, childComponent.element.tagName);
            childComponentToElement.replaceWith(newTag);
        }
    });

    morphdom(rootFromElement, rootToElement, {
        getNodeKey: (node: Node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            // Pretend an added element has a unique id so that morphdom treats
            // it like a unique element, causing it to always attempt to remove
            // it (which we can then prevent) instead of potentially updating
            // it from an element that was added by the server in the same location.
            if (externalMutationTracker.wasElementAdded(node)) {
                return 'added_element_' + Math.random();
            }

            return getKeyFromElement(node);
        },
        onBeforeElUpdated: (fromEl: Element, toEl: Element) => {
            if (fromEl === rootFromElement) {
                return true;
            }

            // skip special checking if this is, for example, an SVG
            if (fromEl instanceof HTMLElement && toEl instanceof HTMLElement) {
                if (childComponentMap.has(fromEl)) {
                    const childComponent = childComponentMap.get(fromEl) as Component;

                    return childComponent.updateFromNewElement(toEl);
                }

                // if this field's value has been modified since this HTML was
                // requested, set the toEl's value to match the fromEl
                if (modifiedFieldElements.includes(fromEl)) {
                    setValueOnElement(toEl, getElementValue(fromEl));
                }

                // handle any external changes to this element
                const elementChanges = externalMutationTracker.getChangedElement(fromEl);
                if (elementChanges) {
                    // apply the changes to the "to" element so it looks like the
                    // external changes were already part of it
                    elementChanges.applyToElement(toEl);
                }

                // https://github.com/patrick-steele-idem/morphdom#can-i-make-morphdom-blaze-through-the-dom-tree-even-faster-yes
                if (fromEl.isEqualNode(toEl)) {
                    // the nodes are equal, but the "value" on some might differ
                    // lets try to quickly compare a bit more deeply
                    const normalizedFromEl = cloneHTMLElement(fromEl);
                    normalizeAttributesForComparison(normalizedFromEl);

                    const normalizedToEl = cloneHTMLElement(toEl);
                    normalizeAttributesForComparison(normalizedToEl);

                    if (normalizedFromEl.isEqualNode(normalizedToEl)) {
                        // don't bother updating
                        return false;
                    }
                }
            }

            // look for data-live-ignore, and don't update
            return !fromEl.hasAttribute('data-live-ignore');
        },

        onBeforeNodeDiscarded(node) {
            if (!(node instanceof HTMLElement)) {
                // text element
                return true;
            }

            if (externalMutationTracker.wasElementAdded(node)) {
                // this element was added by an external mutation, so we don't want to discard it
                return false;
            }

            return !node.hasAttribute('data-live-ignore');
        },
    });
}
