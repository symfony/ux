import { cloneHTMLElement, setValueOnElement } from './dom_utils';
// @ts-ignore
import { Idiomorph } from 'idiomorph/dist/idiomorph.esm.js';
import { normalizeAttributesForComparison } from './normalize_attributes_for_comparison';
import ExternalMutationTracker from './Rendering/ExternalMutationTracker';

const syncAttributes = function (fromEl: Element, toEl: Element): void {
    for (let i = 0; i < fromEl.attributes.length; i++) {
        const attr = fromEl.attributes[i];
        toEl.setAttribute(attr.name, attr.value);
    }
};

export function executeMorphdom(
    rootFromElement: HTMLElement,
    rootToElement: HTMLElement,
    modifiedFieldElements: Array<HTMLElement>,
    getElementValue: (element: HTMLElement) => any,
    externalMutationTracker: ExternalMutationTracker
) {
    /*
     * Handle "data-live-preserve" elements.
     *
     * These are elements that are empty and have requested that their
     * content be preserved from the matching element of the existing HTML.
     *
     * To handle them, we:
     *  1) Create an array of the "current" HTMLElements that match each
     *     "data-live-preserve" element.
     *  2) Replace the "current" elements with clones so that the originals
     *     aren't modified during the morphing process.
     *  3) After the morphing is complete, we find the preserved elements and
     *     replace them with the originals.
     */
    const preservedOriginalElements: HTMLElement[] = [];
    rootToElement.querySelectorAll('[data-live-preserve]').forEach((newElement) => {
        const id = newElement.id;
        if (!id) {
            throw new Error('The data-live-preserve attribute requires an id attribute to be set on the element');
        }

        const oldElement = rootFromElement.querySelector(`#${id}`);
        if (!(oldElement instanceof HTMLElement)) {
            throw new Error(`The element with id "${id}" was not found in the original HTML`);
        }

        const clonedOldElement = cloneHTMLElement(oldElement);
        preservedOriginalElements.push(oldElement);
        oldElement.replaceWith(clonedOldElement);

        newElement.removeAttribute('data-live-preserve');
        syncAttributes(newElement, oldElement);
    });

    Idiomorph.morph(rootFromElement, rootToElement, {
        callbacks: {
            beforeNodeMorphed: (fromEl: Element, toEl: Element) => {
                // Idiomorph loop also over Text node
                if (!(fromEl instanceof Element) || !(toEl instanceof Element)) {
                    return true;
                }

                if (fromEl === rootFromElement) {
                    return true;
                }

                // skip special checking if this is, for example, an SVG
                if (fromEl instanceof HTMLElement && toEl instanceof HTMLElement) {
                    // We assume fromEl is an Alpine component if it has `__x` property.
                    // If it's the case, then we should morph `fromEl` to `ToEl` (thanks to https://alpinejs.dev/plugins/morph)
                    // in order to keep the component state and UI in sync.
                    // @ts-ignore
                    if (typeof fromEl.__x !== 'undefined') {
                        // @ts-ignore
                        if (!window.Alpine) {
                            throw new Error(
                                'Unable to access Alpine.js though the global window.Alpine variable. Please make sure Alpine.js is loaded before Symfony UX LiveComponent.'
                            );
                        }

                        // @ts-ignore
                        if (typeof window.Alpine.morph !== 'function') {
                            throw new Error(
                                'Unable to access Alpine.js morph function. Please make sure the Alpine.js Morph plugin is installed and loaded, see https://alpinejs.dev/plugins/morph for more information.'
                            );
                        }

                        // @ts-ignore
                        window.Alpine.morph(fromEl.__x, toEl);
                    }

                    if (externalMutationTracker.wasElementAdded(fromEl)) {
                        fromEl.insertAdjacentElement('afterend', toEl);
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
                    if (fromEl.nodeName.toUpperCase() !== 'OPTION' && fromEl.isEqualNode(toEl)) {
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

                // data-skip-morph implies you want this element's innerHTML to be
                // replaced, not morphed. We add the same behavior to elements where
                // the id has changed. So, even if a <div id="foo"> appears on the
                // same place as a <div id="bar">, we replace the content to get
                // totally fresh internals.
                if (fromEl.hasAttribute('data-skip-morph') || (fromEl.id && fromEl.id !== toEl.id)) {
                    fromEl.innerHTML = toEl.innerHTML;

                    return true;
                }
                // if parent's innerHTML was replaced, skip morphing on child
                if (fromEl.parentElement && fromEl.parentElement.hasAttribute('data-skip-morph')) {
                    return false;
                }

                // look for data-live-ignore, and don't update
                return !fromEl.hasAttribute('data-live-ignore');
            },

            beforeNodeRemoved(node: Node) {
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
        },
    });

    preservedOriginalElements.forEach((oldElement) => {
        const newElement = rootFromElement.querySelector(`#${oldElement.id}`);
        if (!(newElement instanceof HTMLElement)) {
            // should not happen, as preservedOriginalElements is built from
            // the new HTML
            throw new Error('Missing preserved element');
        }

        newElement.replaceWith(oldElement);
    });
}
