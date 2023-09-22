import { cloneHTMLElement, setValueOnElement } from './dom_utils';
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
                // We assume fromEl is an Alpine component if it has `__x` property.
                // If it's the case, then we should morph `fromEl` to `ToEl` (thanks to https://alpinejs.dev/plugins/morph)
                // in order to keep the component state and UI in sync.
                if (typeof fromEl.__x !== 'undefined') {
                    if (!window.Alpine) {
                        throw new Error(
                            'Unable to access Alpine.js though the global window.Alpine variable. Please make sure Alpine.js is loaded before Symfony UX LiveComponent.'
                        );
                    }

                    if (typeof window.Alpine.morph !== 'function') {
                        throw new Error(
                            'Unable to access Alpine.js morph function. Please make sure the Alpine.js Morph plugin is installed and loaded, see https://alpinejs.dev/plugins/morph for more information.'
                        );
                    }

                    window.Alpine.morph(fromEl.__x, toEl);
                }

                if (childComponentMap.has(fromEl)) {
                    const childComponent = childComponentMap.get(fromEl) as Component;

                    childComponent.updateFromNewElementFromParentRender(toEl);

                    return false;
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
