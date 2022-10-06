import {
    cloneElementWithNewTagName,
    cloneHTMLElement,
    setValueOnElement
} from './dom_utils';
import morphdom from 'morphdom';
import {
    normalizeAttributesForComparison
} from './normalize_attributes_for_comparison';
import Component from './Component';

export function executeMorphdom(
    rootFromElement: HTMLElement,
    rootToElement: HTMLElement,
    modifiedElements: Array<HTMLElement>,
    getElementValue: (element: HTMLElement) => any,
    childComponents: Component[],
) {
    const childComponentMap: Map<HTMLElement, Component> = new Map();
    childComponents.forEach((childComponent) => {
        childComponentMap.set(childComponent.element, childComponent);
        // TODO: add driver to make this agnostic
        const childComponentToElement = rootToElement.querySelector(`[data-live-id=${childComponent.id}]`)
        if (childComponentToElement && childComponentToElement.tagName !== childComponent.element.tagName) {
            // we need to "correct" the tag name for the child to match the "from"
            // so that we always get a "diff", not a remove/add
            const newTag = cloneElementWithNewTagName(childComponentToElement, childComponent.element.tagName);
            rootToElement.replaceChild(newTag, childComponentToElement);
        }
    });

    morphdom(rootFromElement, rootToElement, {
        getNodeKey: (node: Node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            // TODO: abstract out to make this function agnostic of markup
            return node.dataset.liveId;
        },
        onBeforeElUpdated: (fromEl, toEl) => {
            if (fromEl === rootFromElement) {
                return true;
            }

            if (!(fromEl instanceof HTMLElement) || !(toEl instanceof HTMLElement)) {
                return false;
            }

            const childComponent = childComponentMap.get(fromEl) || false
            if (childComponent) {
                return childComponent.updateFromNewElement(toEl);
            }

            // if this field's value has been modified since this HTML was
            // requested, set the toEl's value to match the fromEl
            if (modifiedElements.includes(fromEl)) {
                setValueOnElement(toEl, getElementValue(fromEl))
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

            // look for data-live-ignore, and don't update
            return !fromEl.hasAttribute('data-live-ignore');
        },

        onBeforeNodeDiscarded(node) {
            if (!(node instanceof HTMLElement)) {
                // text element
                return true;
            }

            return !node.hasAttribute('data-live-ignore');
        }
    });
}
