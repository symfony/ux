import { Controller } from '@hotwired/stimulus';

function parseDirectives(content) {
    const directives = [];
    if (!content) {
        return directives;
    }
    let currentActionName = '';
    let currentArgumentValue = '';
    let currentArguments = [];
    let currentModifiers = [];
    let state = 'action';
    const getLastActionName = () => {
        if (currentActionName) {
            return currentActionName;
        }
        if (directives.length === 0) {
            throw new Error('Could not find any directives');
        }
        return directives[directives.length - 1].action;
    };
    const pushInstruction = () => {
        directives.push({
            action: currentActionName,
            args: currentArguments,
            modifiers: currentModifiers,
            getString: () => {
                return content;
            },
        });
        currentActionName = '';
        currentArgumentValue = '';
        currentArguments = [];
        currentModifiers = [];
        state = 'action';
    };
    const pushArgument = () => {
        currentArguments.push(currentArgumentValue.trim());
        currentArgumentValue = '';
    };
    const pushModifier = () => {
        if (currentArguments.length > 1) {
            throw new Error(`The modifier "${currentActionName}()" does not support multiple arguments.`);
        }
        currentModifiers.push({
            name: currentActionName,
            value: currentArguments.length > 0 ? currentArguments[0] : null,
        });
        currentActionName = '';
        currentArguments = [];
        state = 'action';
    };
    for (let i = 0; i < content.length; i++) {
        const char = content[i];
        switch (state) {
            case 'action':
                if (char === '(') {
                    state = 'arguments';
                    break;
                }
                if (char === ' ') {
                    if (currentActionName) {
                        pushInstruction();
                    }
                    break;
                }
                if (char === '|') {
                    pushModifier();
                    break;
                }
                currentActionName += char;
                break;
            case 'arguments':
                if (char === ')') {
                    pushArgument();
                    state = 'after_arguments';
                    break;
                }
                if (char === ',') {
                    pushArgument();
                    break;
                }
                currentArgumentValue += char;
                break;
            case 'after_arguments':
                if (char === '|') {
                    pushModifier();
                    break;
                }
                if (char !== ' ') {
                    throw new Error(`Missing space after ${getLastActionName()}()`);
                }
                pushInstruction();
                break;
        }
    }
    switch (state) {
        case 'action':
        case 'after_arguments':
            if (currentActionName) {
                pushInstruction();
            }
            break;
        default:
            throw new Error(`Did you forget to add a closing ")" after "${currentActionName}"?`);
    }
    return directives;
}

function combineSpacedArray(parts) {
    const finalParts = [];
    parts.forEach((part) => {
        finalParts.push(...trimAll(part).split(' '));
    });
    return finalParts;
}
function trimAll(str) {
    return str.replace(/[\s]+/g, ' ').trim();
}
function normalizeModelName(model) {
    return (model
        .replace(/\[]$/, '')
        .split('[')
        .map((s) => s.replace(']', ''))
        .join('.'));
}

function getElementAsTagText(element) {
    return element.innerHTML
        ? element.outerHTML.slice(0, element.outerHTML.indexOf(element.innerHTML))
        : element.outerHTML;
}

let componentMapByElement = new WeakMap();
let componentMapByComponent = new Map();
const registerComponent = (component) => {
    componentMapByElement.set(component.element, component);
    componentMapByComponent.set(component, component.name);
};
const unregisterComponent = (component) => {
    componentMapByElement.delete(component.element);
    componentMapByComponent.delete(component);
};
const getComponent = (element) => new Promise((resolve, reject) => {
    let count = 0;
    const maxCount = 10;
    const interval = setInterval(() => {
        const component = componentMapByElement.get(element);
        if (component) {
            clearInterval(interval);
            resolve(component);
        }
        count++;
        if (count > maxCount) {
            clearInterval(interval);
            reject(new Error(`Component not found for element ${getElementAsTagText(element)}`));
        }
    }, 5);
});
const findComponents = (currentComponent, onlyParents, onlyMatchName) => {
    const components = [];
    componentMapByComponent.forEach((componentName, component) => {
        if (onlyParents && (currentComponent === component || !component.element.contains(currentComponent.element))) {
            return;
        }
        if (onlyMatchName && componentName !== onlyMatchName) {
            return;
        }
        components.push(component);
    });
    return components;
};
const findChildren = (currentComponent) => {
    const children = [];
    componentMapByComponent.forEach((componentName, component) => {
        if (currentComponent === component) {
            return;
        }
        if (!currentComponent.element.contains(component.element)) {
            return;
        }
        let foundChildComponent = false;
        componentMapByComponent.forEach((childComponentName, childComponent) => {
            if (foundChildComponent) {
                return;
            }
            if (childComponent === component) {
                return;
            }
            if (childComponent.element.contains(component.element)) {
                foundChildComponent = true;
            }
        });
        children.push(component);
    });
    return children;
};
const findParent = (currentComponent) => {
    let parentElement = currentComponent.element.parentElement;
    while (parentElement) {
        const component = componentMapByElement.get(parentElement);
        if (component) {
            return component;
        }
        parentElement = parentElement.parentElement;
    }
    return null;
};

function getValueFromElement(element, valueStore) {
    if (element instanceof HTMLInputElement) {
        if (element.type === 'checkbox') {
            const modelNameData = getModelDirectiveFromElement(element, false);
            if (modelNameData !== null) {
                const modelValue = valueStore.get(modelNameData.action);
                if (Array.isArray(modelValue)) {
                    return getMultipleCheckboxValue(element, modelValue);
                }
                if (Object(modelValue) === modelValue) {
                    return getMultipleCheckboxValue(element, Object.values(modelValue));
                }
            }
            if (element.hasAttribute('value')) {
                return element.checked ? element.getAttribute('value') : null;
            }
            return element.checked;
        }
        return inputValue(element);
    }
    if (element instanceof HTMLSelectElement) {
        if (element.multiple) {
            return Array.from(element.selectedOptions).map((el) => el.value);
        }
        return element.value;
    }
    if (element.dataset.value) {
        return element.dataset.value;
    }
    if ('value' in element) {
        return element.value;
    }
    if (element.hasAttribute('value')) {
        return element.getAttribute('value');
    }
    return null;
}
function setValueOnElement(element, value) {
    if (element instanceof HTMLInputElement) {
        if (element.type === 'file') {
            return;
        }
        if (element.type === 'radio') {
            element.checked = element.value === value;
            return;
        }
        if (element.type === 'checkbox') {
            if (Array.isArray(value)) {
                let valueFound = false;
                value.forEach((val) => {
                    if (val === element.value) {
                        valueFound = true;
                    }
                });
                element.checked = valueFound;
            }
            else {
                if (element.hasAttribute('value')) {
                    element.checked = element.value === value;
                }
                else {
                    element.checked = value;
                }
            }
            return;
        }
    }
    if (element instanceof HTMLSelectElement) {
        const arrayWrappedValue = [].concat(value).map((value) => {
            return `${value}`;
        });
        Array.from(element.options).forEach((option) => {
            option.selected = arrayWrappedValue.includes(option.value);
        });
        return;
    }
    value = value === undefined ? '' : value;
    element.value = value;
}
function getAllModelDirectiveFromElements(element) {
    if (!element.dataset.model) {
        return [];
    }
    const directives = parseDirectives(element.dataset.model);
    directives.forEach((directive) => {
        if (directive.args.length > 0) {
            throw new Error(`The data-model="${element.dataset.model}" format is invalid: it does not support passing arguments to the model.`);
        }
        directive.action = normalizeModelName(directive.action);
    });
    return directives;
}
function getModelDirectiveFromElement(element, throwOnMissing = true) {
    const dataModelDirectives = getAllModelDirectiveFromElements(element);
    if (dataModelDirectives.length > 0) {
        return dataModelDirectives[0];
    }
    if (element.getAttribute('name')) {
        const formElement = element.closest('form');
        if (formElement && 'model' in formElement.dataset) {
            const directives = parseDirectives(formElement.dataset.model || '*');
            const directive = directives[0];
            if (directive.args.length > 0) {
                throw new Error(`The data-model="${formElement.dataset.model}" format is invalid: it does not support passing arguments to the model.`);
            }
            directive.action = normalizeModelName(element.getAttribute('name'));
            return directive;
        }
    }
    if (!throwOnMissing) {
        return null;
    }
    throw new Error(`Cannot determine the model name for "${getElementAsTagText(element)}": the element must either have a "data-model" (or "name" attribute living inside a <form data-model="*">).`);
}
function elementBelongsToThisComponent(element, component) {
    if (component.element === element) {
        return true;
    }
    if (!component.element.contains(element)) {
        return false;
    }
    let foundChildComponent = false;
    findChildren(component).forEach((childComponent) => {
        if (foundChildComponent) {
            return;
        }
        if (childComponent.element === element || childComponent.element.contains(element)) {
            foundChildComponent = true;
        }
    });
    return !foundChildComponent;
}
function cloneHTMLElement(element) {
    const newElement = element.cloneNode(true);
    if (!(newElement instanceof HTMLElement)) {
        throw new Error('Could not clone element');
    }
    return newElement;
}
function htmlToElement(html) {
    const template = document.createElement('template');
    html = html.trim();
    template.innerHTML = html;
    if (template.content.childElementCount > 1) {
        throw new Error(`Component HTML contains ${template.content.childElementCount} elements, but only 1 root element is allowed.`);
    }
    const child = template.content.firstElementChild;
    if (!child) {
        throw new Error('Child not found');
    }
    if (!(child instanceof HTMLElement)) {
        throw new Error(`Created element is not an HTMLElement: ${html.trim()}`);
    }
    return child;
}
const getMultipleCheckboxValue = (element, currentValues) => {
    const finalValues = [...currentValues];
    const value = inputValue(element);
    const index = currentValues.indexOf(value);
    if (element.checked) {
        if (index === -1) {
            finalValues.push(value);
        }
        return finalValues;
    }
    if (index > -1) {
        finalValues.splice(index, 1);
    }
    return finalValues;
};
const inputValue = (element) => element.dataset.value ? element.dataset.value : element.value;

function getDeepData(data, propertyPath) {
    const { currentLevelData, finalKey } = parseDeepData(data, propertyPath);
    if (currentLevelData === undefined) {
        return undefined;
    }
    return currentLevelData[finalKey];
}
const parseDeepData = (data, propertyPath) => {
    const finalData = JSON.parse(JSON.stringify(data));
    let currentLevelData = finalData;
    const parts = propertyPath.split('.');
    for (let i = 0; i < parts.length - 1; i++) {
        currentLevelData = currentLevelData[parts[i]];
    }
    const finalKey = parts[parts.length - 1];
    return {
        currentLevelData,
        finalData,
        finalKey,
        parts,
    };
};

class ValueStore {
    constructor(props) {
        this.props = {};
        this.dirtyProps = {};
        this.pendingProps = {};
        this.updatedPropsFromParent = {};
        this.props = props;
    }
    get(name) {
        const normalizedName = normalizeModelName(name);
        if (this.dirtyProps[normalizedName] !== undefined) {
            return this.dirtyProps[normalizedName];
        }
        if (this.pendingProps[normalizedName] !== undefined) {
            return this.pendingProps[normalizedName];
        }
        if (this.props[normalizedName] !== undefined) {
            return this.props[normalizedName];
        }
        return getDeepData(this.props, normalizedName);
    }
    has(name) {
        return this.get(name) !== undefined;
    }
    set(name, value) {
        const normalizedName = normalizeModelName(name);
        if (this.get(normalizedName) === value) {
            return false;
        }
        this.dirtyProps[normalizedName] = value;
        return true;
    }
    getOriginalProps() {
        return { ...this.props };
    }
    getDirtyProps() {
        return { ...this.dirtyProps };
    }
    getUpdatedPropsFromParent() {
        return { ...this.updatedPropsFromParent };
    }
    flushDirtyPropsToPending() {
        this.pendingProps = { ...this.dirtyProps };
        this.dirtyProps = {};
    }
    reinitializeAllProps(props) {
        this.props = props;
        this.updatedPropsFromParent = {};
        this.pendingProps = {};
    }
    pushPendingPropsBackToDirty() {
        this.dirtyProps = { ...this.pendingProps, ...this.dirtyProps };
        this.pendingProps = {};
    }
    storeNewPropsFromParent(props) {
        let changed = false;
        for (const [key, value] of Object.entries(props)) {
            const currentValue = this.get(key);
            if (currentValue !== value) {
                changed = true;
            }
        }
        if (changed) {
            this.updatedPropsFromParent = props;
        }
        return changed;
    }
}

// base IIFE to define idiomorph
var Idiomorph = (function () {

        //=============================================================================
        // AND NOW IT BEGINS...
        //=============================================================================
        let EMPTY_SET = new Set();

        // default configuration values, updatable by users now
        let defaults = {
            morphStyle: "outerHTML",
            callbacks : {
                beforeNodeAdded: noOp,
                afterNodeAdded: noOp,
                beforeNodeMorphed: noOp,
                afterNodeMorphed: noOp,
                beforeNodeRemoved: noOp,
                afterNodeRemoved: noOp,
                beforeAttributeUpdated: noOp,

            },
            head: {
                style: 'merge',
                shouldPreserve: function (elt) {
                    return elt.getAttribute("im-preserve") === "true";
                },
                shouldReAppend: function (elt) {
                    return elt.getAttribute("im-re-append") === "true";
                },
                shouldRemove: noOp,
                afterHeadMorphed: noOp,
            }
        };

        //=============================================================================
        // Core Morphing Algorithm - morph, morphNormalizedContent, morphOldNodeTo, morphChildren
        //=============================================================================
        function morph(oldNode, newContent, config = {}) {

            if (oldNode instanceof Document) {
                oldNode = oldNode.documentElement;
            }

            if (typeof newContent === 'string') {
                newContent = parseContent(newContent);
            }

            let normalizedContent = normalizeContent(newContent);

            let ctx = createMorphContext(oldNode, normalizedContent, config);

            return morphNormalizedContent(oldNode, normalizedContent, ctx);
        }

        function morphNormalizedContent(oldNode, normalizedNewContent, ctx) {
            if (ctx.head.block) {
                let oldHead = oldNode.querySelector('head');
                let newHead = normalizedNewContent.querySelector('head');
                if (oldHead && newHead) {
                    let promises = handleHeadElement(newHead, oldHead, ctx);
                    // when head promises resolve, call morph again, ignoring the head tag
                    Promise.all(promises).then(function () {
                        morphNormalizedContent(oldNode, normalizedNewContent, Object.assign(ctx, {
                            head: {
                                block: false,
                                ignore: true
                            }
                        }));
                    });
                    return;
                }
            }

            if (ctx.morphStyle === "innerHTML") {

                // innerHTML, so we are only updating the children
                morphChildren(normalizedNewContent, oldNode, ctx);
                return oldNode.children;

            } else if (ctx.morphStyle === "outerHTML" || ctx.morphStyle == null) {
                // otherwise find the best element match in the new content, morph that, and merge its siblings
                // into either side of the best match
                let bestMatch = findBestNodeMatch(normalizedNewContent, oldNode, ctx);

                // stash the siblings that will need to be inserted on either side of the best match
                let previousSibling = bestMatch?.previousSibling;
                let nextSibling = bestMatch?.nextSibling;

                // morph it
                let morphedNode = morphOldNodeTo(oldNode, bestMatch, ctx);

                if (bestMatch) {
                    // if there was a best match, merge the siblings in too and return the
                    // whole bunch
                    return insertSiblings(previousSibling, morphedNode, nextSibling);
                } else {
                    // otherwise nothing was added to the DOM
                    return []
                }
            } else {
                throw "Do not understand how to morph style " + ctx.morphStyle;
            }
        }


        /**
         * @param possibleActiveElement
         * @param ctx
         * @returns {boolean}
         */
        function ignoreValueOfActiveElement(possibleActiveElement, ctx) {
            return ctx.ignoreActiveValue && possibleActiveElement === document.activeElement;
        }

        /**
         * @param oldNode root node to merge content into
         * @param newContent new content to merge
         * @param ctx the merge context
         * @returns {Element} the element that ended up in the DOM
         */
        function morphOldNodeTo(oldNode, newContent, ctx) {
            if (ctx.ignoreActive && oldNode === document.activeElement) ; else if (newContent == null) {
                if (ctx.callbacks.beforeNodeRemoved(oldNode) === false) return oldNode;

                oldNode.remove();
                ctx.callbacks.afterNodeRemoved(oldNode);
                return null;
            } else if (!isSoftMatch(oldNode, newContent)) {
                if (ctx.callbacks.beforeNodeRemoved(oldNode) === false) return oldNode;
                if (ctx.callbacks.beforeNodeAdded(newContent) === false) return oldNode;

                oldNode.parentElement.replaceChild(newContent, oldNode);
                ctx.callbacks.afterNodeAdded(newContent);
                ctx.callbacks.afterNodeRemoved(oldNode);
                return newContent;
            } else {
                if (ctx.callbacks.beforeNodeMorphed(oldNode, newContent) === false) return oldNode;

                if (oldNode instanceof HTMLHeadElement && ctx.head.ignore) ; else if (oldNode instanceof HTMLHeadElement && ctx.head.style !== "morph") {
                    handleHeadElement(newContent, oldNode, ctx);
                } else {
                    syncNodeFrom(newContent, oldNode, ctx);
                    if (!ignoreValueOfActiveElement(oldNode, ctx)) {
                        morphChildren(newContent, oldNode, ctx);
                    }
                }
                ctx.callbacks.afterNodeMorphed(oldNode, newContent);
                return oldNode;
            }
        }

        /**
         * This is the core algorithm for matching up children.  The idea is to use id sets to try to match up
         * nodes as faithfully as possible.  We greedily match, which allows us to keep the algorithm fast, but
         * by using id sets, we are able to better match up with content deeper in the DOM.
         *
         * Basic algorithm is, for each node in the new content:
         *
         * - if we have reached the end of the old parent, append the new content
         * - if the new content has an id set match with the current insertion point, morph
         * - search for an id set match
         * - if id set match found, morph
         * - otherwise search for a "soft" match
         * - if a soft match is found, morph
         * - otherwise, prepend the new node before the current insertion point
         *
         * The two search algorithms terminate if competing node matches appear to outweigh what can be achieved
         * with the current node.  See findIdSetMatch() and findSoftMatch() for details.
         *
         * @param {Element} newParent the parent element of the new content
         * @param {Element } oldParent the old content that we are merging the new content into
         * @param ctx the merge context
         */
        function morphChildren(newParent, oldParent, ctx) {

            let nextNewChild = newParent.firstChild;
            let insertionPoint = oldParent.firstChild;
            let newChild;

            // run through all the new content
            while (nextNewChild) {

                newChild = nextNewChild;
                nextNewChild = newChild.nextSibling;

                // if we are at the end of the exiting parent's children, just append
                if (insertionPoint == null) {
                    if (ctx.callbacks.beforeNodeAdded(newChild) === false) return;

                    oldParent.appendChild(newChild);
                    ctx.callbacks.afterNodeAdded(newChild);
                    removeIdsFromConsideration(ctx, newChild);
                    continue;
                }

                // if the current node has an id set match then morph
                if (isIdSetMatch(newChild, insertionPoint, ctx)) {
                    morphOldNodeTo(insertionPoint, newChild, ctx);
                    insertionPoint = insertionPoint.nextSibling;
                    removeIdsFromConsideration(ctx, newChild);
                    continue;
                }

                // otherwise search forward in the existing old children for an id set match
                let idSetMatch = findIdSetMatch(newParent, oldParent, newChild, insertionPoint, ctx);

                // if we found a potential match, remove the nodes until that point and morph
                if (idSetMatch) {
                    insertionPoint = removeNodesBetween(insertionPoint, idSetMatch, ctx);
                    morphOldNodeTo(idSetMatch, newChild, ctx);
                    removeIdsFromConsideration(ctx, newChild);
                    continue;
                }

                // no id set match found, so scan forward for a soft match for the current node
                let softMatch = findSoftMatch(newParent, oldParent, newChild, insertionPoint, ctx);

                // if we found a soft match for the current node, morph
                if (softMatch) {
                    insertionPoint = removeNodesBetween(insertionPoint, softMatch, ctx);
                    morphOldNodeTo(softMatch, newChild, ctx);
                    removeIdsFromConsideration(ctx, newChild);
                    continue;
                }

                // abandon all hope of morphing, just insert the new child before the insertion point
                // and move on
                if (ctx.callbacks.beforeNodeAdded(newChild) === false) return;

                oldParent.insertBefore(newChild, insertionPoint);
                ctx.callbacks.afterNodeAdded(newChild);
                removeIdsFromConsideration(ctx, newChild);
            }

            // remove any remaining old nodes that didn't match up with new content
            while (insertionPoint !== null) {

                let tempNode = insertionPoint;
                insertionPoint = insertionPoint.nextSibling;
                removeNode(tempNode, ctx);
            }
        }

        //=============================================================================
        // Attribute Syncing Code
        //=============================================================================

        /**
         * @param attr {String} the attribute to be mutated
         * @param to {Element} the element that is going to be updated
         * @param updateType {("update"|"remove")}
         * @param ctx the merge context
         * @returns {boolean} true if the attribute should be ignored, false otherwise
         */
        function ignoreAttribute(attr, to, updateType, ctx) {
            if(attr === 'value' && ctx.ignoreActiveValue && to === document.activeElement){
                return true;
            }
            return ctx.callbacks.beforeAttributeUpdated(attr, to, updateType) === false;
        }

        /**
         * syncs a given node with another node, copying over all attributes and
         * inner element state from the 'from' node to the 'to' node
         *
         * @param {Element} from the element to copy attributes & state from
         * @param {Element} to the element to copy attributes & state to
         * @param ctx the merge context
         */
        function syncNodeFrom(from, to, ctx) {
            let type = from.nodeType;

            // if is an element type, sync the attributes from the
            // new node into the new node
            if (type === 1 /* element type */) {
                const fromAttributes = from.attributes;
                const toAttributes = to.attributes;
                for (const fromAttribute of fromAttributes) {
                    if (ignoreAttribute(fromAttribute.name, to, 'update', ctx)) {
                        continue;
                    }
                    if (to.getAttribute(fromAttribute.name) !== fromAttribute.value) {
                        to.setAttribute(fromAttribute.name, fromAttribute.value);
                    }
                }
                // iterate backwards to avoid skipping over items when a delete occurs
                for (let i = toAttributes.length - 1; 0 <= i; i--) {
                    const toAttribute = toAttributes[i];
                    if (ignoreAttribute(toAttribute.name, to, 'remove', ctx)) {
                        continue;
                    }
                    if (!from.hasAttribute(toAttribute.name)) {
                        to.removeAttribute(toAttribute.name);
                    }
                }
            }

            // sync text nodes
            if (type === 8 /* comment */ || type === 3 /* text */) {
                if (to.nodeValue !== from.nodeValue) {
                    to.nodeValue = from.nodeValue;
                }
            }

            if (!ignoreValueOfActiveElement(to, ctx)) {
                // sync input values
                syncInputValue(from, to, ctx);
            }
        }

        /**
         * @param from {Element} element to sync the value from
         * @param to {Element} element to sync the value to
         * @param attributeName {String} the attribute name
         * @param ctx the merge context
         */
        function syncBooleanAttribute(from, to, attributeName, ctx) {
            if (from[attributeName] !== to[attributeName]) {
                let ignoreUpdate = ignoreAttribute(attributeName, to, 'update', ctx);
                if (!ignoreUpdate) {
                    to[attributeName] = from[attributeName];
                }
                if (from[attributeName]) {
                    if (!ignoreUpdate) {
                        to.setAttribute(attributeName, from[attributeName]);
                    }
                } else {
                    if (!ignoreAttribute(attributeName, to, 'remove', ctx)) {
                        to.removeAttribute(attributeName);
                    }
                }
            }
        }

        /**
         * NB: many bothans died to bring us information:
         *
         *  https://github.com/patrick-steele-idem/morphdom/blob/master/src/specialElHandlers.js
         *  https://github.com/choojs/nanomorph/blob/master/lib/morph.jsL113
         *
         * @param from {Element} the element to sync the input value from
         * @param to {Element} the element to sync the input value to
         * @param ctx the merge context
         */
        function syncInputValue(from, to, ctx) {
            if (from instanceof HTMLInputElement &&
                to instanceof HTMLInputElement &&
                from.type !== 'file') {

                let fromValue = from.value;
                let toValue = to.value;

                // sync boolean attributes
                syncBooleanAttribute(from, to, 'checked', ctx);
                syncBooleanAttribute(from, to, 'disabled', ctx);

                if (!from.hasAttribute('value')) {
                    if (!ignoreAttribute('value', to, 'remove', ctx)) {
                        to.value = '';
                        to.removeAttribute('value');
                    }
                } else if (fromValue !== toValue) {
                    if (!ignoreAttribute('value', to, 'update', ctx)) {
                        to.setAttribute('value', fromValue);
                        to.value = fromValue;
                    }
                }
            } else if (from instanceof HTMLOptionElement) {
                syncBooleanAttribute(from, to, 'selected', ctx);
            } else if (from instanceof HTMLTextAreaElement && to instanceof HTMLTextAreaElement) {
                let fromValue = from.value;
                let toValue = to.value;
                if (ignoreAttribute('value', to, 'update', ctx)) {
                    return;
                }
                if (fromValue !== toValue) {
                    to.value = fromValue;
                }
                if (to.firstChild && to.firstChild.nodeValue !== fromValue) {
                    to.firstChild.nodeValue = fromValue;
                }
            }
        }

        //=============================================================================
        // the HEAD tag can be handled specially, either w/ a 'merge' or 'append' style
        //=============================================================================
        function handleHeadElement(newHeadTag, currentHead, ctx) {

            let added = [];
            let removed = [];
            let preserved = [];
            let nodesToAppend = [];

            let headMergeStyle = ctx.head.style;

            // put all new head elements into a Map, by their outerHTML
            let srcToNewHeadNodes = new Map();
            for (const newHeadChild of newHeadTag.children) {
                srcToNewHeadNodes.set(newHeadChild.outerHTML, newHeadChild);
            }

            // for each elt in the current head
            for (const currentHeadElt of currentHead.children) {

                // If the current head element is in the map
                let inNewContent = srcToNewHeadNodes.has(currentHeadElt.outerHTML);
                let isReAppended = ctx.head.shouldReAppend(currentHeadElt);
                let isPreserved = ctx.head.shouldPreserve(currentHeadElt);
                if (inNewContent || isPreserved) {
                    if (isReAppended) {
                        // remove the current version and let the new version replace it and re-execute
                        removed.push(currentHeadElt);
                    } else {
                        // this element already exists and should not be re-appended, so remove it from
                        // the new content map, preserving it in the DOM
                        srcToNewHeadNodes.delete(currentHeadElt.outerHTML);
                        preserved.push(currentHeadElt);
                    }
                } else {
                    if (headMergeStyle === "append") {
                        // we are appending and this existing element is not new content
                        // so if and only if it is marked for re-append do we do anything
                        if (isReAppended) {
                            removed.push(currentHeadElt);
                            nodesToAppend.push(currentHeadElt);
                        }
                    } else {
                        // if this is a merge, we remove this content since it is not in the new head
                        if (ctx.head.shouldRemove(currentHeadElt) !== false) {
                            removed.push(currentHeadElt);
                        }
                    }
                }
            }

            // Push the remaining new head elements in the Map into the
            // nodes to append to the head tag
            nodesToAppend.push(...srcToNewHeadNodes.values());

            let promises = [];
            for (const newNode of nodesToAppend) {
                let newElt = document.createRange().createContextualFragment(newNode.outerHTML).firstChild;
                if (ctx.callbacks.beforeNodeAdded(newElt) !== false) {
                    if (newElt.href || newElt.src) {
                        let resolve = null;
                        let promise = new Promise(function (_resolve) {
                            resolve = _resolve;
                        });
                        newElt.addEventListener('load', function () {
                            resolve();
                        });
                        promises.push(promise);
                    }
                    currentHead.appendChild(newElt);
                    ctx.callbacks.afterNodeAdded(newElt);
                    added.push(newElt);
                }
            }

            // remove all removed elements, after we have appended the new elements to avoid
            // additional network requests for things like style sheets
            for (const removedElement of removed) {
                if (ctx.callbacks.beforeNodeRemoved(removedElement) !== false) {
                    currentHead.removeChild(removedElement);
                    ctx.callbacks.afterNodeRemoved(removedElement);
                }
            }

            ctx.head.afterHeadMorphed(currentHead, {added: added, kept: preserved, removed: removed});
            return promises;
        }

        function noOp() {
        }

        /*
          Deep merges the config object and the Idiomoroph.defaults object to
          produce a final configuration object
         */
        function mergeDefaults(config) {
            let finalConfig = {};
            // copy top level stuff into final config
            Object.assign(finalConfig, defaults);
            Object.assign(finalConfig, config);

            // copy callbacks into final config (do this to deep merge the callbacks)
            finalConfig.callbacks = {};
            Object.assign(finalConfig.callbacks, defaults.callbacks);
            Object.assign(finalConfig.callbacks, config.callbacks);

            // copy head config into final config  (do this to deep merge the head)
            finalConfig.head = {};
            Object.assign(finalConfig.head, defaults.head);
            Object.assign(finalConfig.head, config.head);
            return finalConfig;
        }

        function createMorphContext(oldNode, newContent, config) {
            config = mergeDefaults(config);
            return {
                target: oldNode,
                newContent: newContent,
                config: config,
                morphStyle: config.morphStyle,
                ignoreActive: config.ignoreActive,
                ignoreActiveValue: config.ignoreActiveValue,
                idMap: createIdMap(oldNode, newContent),
                deadIds: new Set(),
                callbacks: config.callbacks,
                head: config.head
            }
        }

        function isIdSetMatch(node1, node2, ctx) {
            if (node1 == null || node2 == null) {
                return false;
            }
            if (node1.nodeType === node2.nodeType && node1.tagName === node2.tagName) {
                if (node1.id !== "" && node1.id === node2.id) {
                    return true;
                } else {
                    return getIdIntersectionCount(ctx, node1, node2) > 0;
                }
            }
            return false;
        }

        function isSoftMatch(node1, node2) {
            if (node1 == null || node2 == null) {
                return false;
            }
            return node1.nodeType === node2.nodeType && node1.tagName === node2.tagName
        }

        function removeNodesBetween(startInclusive, endExclusive, ctx) {
            while (startInclusive !== endExclusive) {
                let tempNode = startInclusive;
                startInclusive = startInclusive.nextSibling;
                removeNode(tempNode, ctx);
            }
            removeIdsFromConsideration(ctx, endExclusive);
            return endExclusive.nextSibling;
        }

        //=============================================================================
        // Scans forward from the insertionPoint in the old parent looking for a potential id match
        // for the newChild.  We stop if we find a potential id match for the new child OR
        // if the number of potential id matches we are discarding is greater than the
        // potential id matches for the new child
        //=============================================================================
        function findIdSetMatch(newContent, oldParent, newChild, insertionPoint, ctx) {

            // max id matches we are willing to discard in our search
            let newChildPotentialIdCount = getIdIntersectionCount(ctx, newChild, oldParent);

            let potentialMatch = null;

            // only search forward if there is a possibility of an id match
            if (newChildPotentialIdCount > 0) {
                let potentialMatch = insertionPoint;
                // if there is a possibility of an id match, scan forward
                // keep track of the potential id match count we are discarding (the
                // newChildPotentialIdCount must be greater than this to make it likely
                // worth it)
                let otherMatchCount = 0;
                while (potentialMatch != null) {

                    // If we have an id match, return the current potential match
                    if (isIdSetMatch(newChild, potentialMatch, ctx)) {
                        return potentialMatch;
                    }

                    // computer the other potential matches of this new content
                    otherMatchCount += getIdIntersectionCount(ctx, potentialMatch, newContent);
                    if (otherMatchCount > newChildPotentialIdCount) {
                        // if we have more potential id matches in _other_ content, we
                        // do not have a good candidate for an id match, so return null
                        return null;
                    }

                    // advanced to the next old content child
                    potentialMatch = potentialMatch.nextSibling;
                }
            }
            return potentialMatch;
        }

        //=============================================================================
        // Scans forward from the insertionPoint in the old parent looking for a potential soft match
        // for the newChild.  We stop if we find a potential soft match for the new child OR
        // if we find a potential id match in the old parents children OR if we find two
        // potential soft matches for the next two pieces of new content
        //=============================================================================
        function findSoftMatch(newContent, oldParent, newChild, insertionPoint, ctx) {

            let potentialSoftMatch = insertionPoint;
            let nextSibling = newChild.nextSibling;
            let siblingSoftMatchCount = 0;

            while (potentialSoftMatch != null) {

                if (getIdIntersectionCount(ctx, potentialSoftMatch, newContent) > 0) {
                    // the current potential soft match has a potential id set match with the remaining new
                    // content so bail out of looking
                    return null;
                }

                // if we have a soft match with the current node, return it
                if (isSoftMatch(newChild, potentialSoftMatch)) {
                    return potentialSoftMatch;
                }

                if (isSoftMatch(nextSibling, potentialSoftMatch)) {
                    // the next new node has a soft match with this node, so
                    // increment the count of future soft matches
                    siblingSoftMatchCount++;
                    nextSibling = nextSibling.nextSibling;

                    // If there are two future soft matches, bail to allow the siblings to soft match
                    // so that we don't consume future soft matches for the sake of the current node
                    if (siblingSoftMatchCount >= 2) {
                        return null;
                    }
                }

                // advanced to the next old content child
                potentialSoftMatch = potentialSoftMatch.nextSibling;
            }

            return potentialSoftMatch;
        }

        function parseContent(newContent) {
            let parser = new DOMParser();

            // remove svgs to avoid false-positive matches on head, etc.
            let contentWithSvgsRemoved = newContent.replace(/<svg(\s[^>]*>|>)([\s\S]*?)<\/svg>/gim, '');

            // if the newContent contains a html, head or body tag, we can simply parse it w/o wrapping
            if (contentWithSvgsRemoved.match(/<\/html>/) || contentWithSvgsRemoved.match(/<\/head>/) || contentWithSvgsRemoved.match(/<\/body>/)) {
                let content = parser.parseFromString(newContent, "text/html");
                // if it is a full HTML document, return the document itself as the parent container
                if (contentWithSvgsRemoved.match(/<\/html>/)) {
                    content.generatedByIdiomorph = true;
                    return content;
                } else {
                    // otherwise return the html element as the parent container
                    let htmlElement = content.firstChild;
                    if (htmlElement) {
                        htmlElement.generatedByIdiomorph = true;
                        return htmlElement;
                    } else {
                        return null;
                    }
                }
            } else {
                // if it is partial HTML, wrap it in a template tag to provide a parent element and also to help
                // deal with touchy tags like tr, tbody, etc.
                let responseDoc = parser.parseFromString("<body><template>" + newContent + "</template></body>", "text/html");
                let content = responseDoc.body.querySelector('template').content;
                content.generatedByIdiomorph = true;
                return content
            }
        }

        function normalizeContent(newContent) {
            if (newContent == null) {
                // noinspection UnnecessaryLocalVariableJS
                const dummyParent = document.createElement('div');
                return dummyParent;
            } else if (newContent.generatedByIdiomorph) {
                // the template tag created by idiomorph parsing can serve as a dummy parent
                return newContent;
            } else if (newContent instanceof Node) {
                // a single node is added as a child to a dummy parent
                const dummyParent = document.createElement('div');
                dummyParent.append(newContent);
                return dummyParent;
            } else {
                // all nodes in the array or HTMLElement collection are consolidated under
                // a single dummy parent element
                const dummyParent = document.createElement('div');
                for (const elt of [...newContent]) {
                    dummyParent.append(elt);
                }
                return dummyParent;
            }
        }

        function insertSiblings(previousSibling, morphedNode, nextSibling) {
            let stack = [];
            let added = [];
            while (previousSibling != null) {
                stack.push(previousSibling);
                previousSibling = previousSibling.previousSibling;
            }
            while (stack.length > 0) {
                let node = stack.pop();
                added.push(node); // push added preceding siblings on in order and insert
                morphedNode.parentElement.insertBefore(node, morphedNode);
            }
            added.push(morphedNode);
            while (nextSibling != null) {
                stack.push(nextSibling);
                added.push(nextSibling); // here we are going in order, so push on as we scan, rather than add
                nextSibling = nextSibling.nextSibling;
            }
            while (stack.length > 0) {
                morphedNode.parentElement.insertBefore(stack.pop(), morphedNode.nextSibling);
            }
            return added;
        }

        function findBestNodeMatch(newContent, oldNode, ctx) {
            let currentElement;
            currentElement = newContent.firstChild;
            let bestElement = currentElement;
            let score = 0;
            while (currentElement) {
                let newScore = scoreElement(currentElement, oldNode, ctx);
                if (newScore > score) {
                    bestElement = currentElement;
                    score = newScore;
                }
                currentElement = currentElement.nextSibling;
            }
            return bestElement;
        }

        function scoreElement(node1, node2, ctx) {
            if (isSoftMatch(node1, node2)) {
                return .5 + getIdIntersectionCount(ctx, node1, node2);
            }
            return 0;
        }

        function removeNode(tempNode, ctx) {
            removeIdsFromConsideration(ctx, tempNode);
            if (ctx.callbacks.beforeNodeRemoved(tempNode) === false) return;

            tempNode.remove();
            ctx.callbacks.afterNodeRemoved(tempNode);
        }

        //=============================================================================
        // ID Set Functions
        //=============================================================================

        function isIdInConsideration(ctx, id) {
            return !ctx.deadIds.has(id);
        }

        function idIsWithinNode(ctx, id, targetNode) {
            let idSet = ctx.idMap.get(targetNode) || EMPTY_SET;
            return idSet.has(id);
        }

        function removeIdsFromConsideration(ctx, node) {
            let idSet = ctx.idMap.get(node) || EMPTY_SET;
            for (const id of idSet) {
                ctx.deadIds.add(id);
            }
        }

        function getIdIntersectionCount(ctx, node1, node2) {
            let sourceSet = ctx.idMap.get(node1) || EMPTY_SET;
            let matchCount = 0;
            for (const id of sourceSet) {
                // a potential match is an id in the source and potentialIdsSet, but
                // that has not already been merged into the DOM
                if (isIdInConsideration(ctx, id) && idIsWithinNode(ctx, id, node2)) {
                    ++matchCount;
                }
            }
            return matchCount;
        }

        /**
         * A bottom up algorithm that finds all elements with ids inside of the node
         * argument and populates id sets for those nodes and all their parents, generating
         * a set of ids contained within all nodes for the entire hierarchy in the DOM
         *
         * @param node {Element}
         * @param {Map<Node, Set<String>>} idMap
         */
        function populateIdMapForNode(node, idMap) {
            let nodeParent = node.parentElement;
            // find all elements with an id property
            let idElements = node.querySelectorAll('[id]');
            for (const elt of idElements) {
                let current = elt;
                // walk up the parent hierarchy of that element, adding the id
                // of element to the parent's id set
                while (current !== nodeParent && current != null) {
                    let idSet = idMap.get(current);
                    // if the id set doesn't exist, create it and insert it in the  map
                    if (idSet == null) {
                        idSet = new Set();
                        idMap.set(current, idSet);
                    }
                    idSet.add(elt.id);
                    current = current.parentElement;
                }
            }
        }

        /**
         * This function computes a map of nodes to all ids contained within that node (inclusive of the
         * node).  This map can be used to ask if two nodes have intersecting sets of ids, which allows
         * for a looser definition of "matching" than tradition id matching, and allows child nodes
         * to contribute to a parent nodes matching.
         *
         * @param {Element} oldContent  the old content that will be morphed
         * @param {Element} newContent  the new content to morph to
         * @returns {Map<Node, Set<String>>} a map of nodes to id sets for the
         */
        function createIdMap(oldContent, newContent) {
            let idMap = new Map();
            populateIdMapForNode(oldContent, idMap);
            populateIdMapForNode(newContent, idMap);
            return idMap;
        }

        //=============================================================================
        // This is what ends up becoming the Idiomorph global object
        //=============================================================================
        return {
            morph,
            defaults
        }
    })();

function normalizeAttributesForComparison(element) {
    const isFileInput = element instanceof HTMLInputElement && element.type === 'file';
    if (!isFileInput) {
        if ('value' in element) {
            element.setAttribute('value', element.value);
        }
        else if (element.hasAttribute('value')) {
            element.setAttribute('value', '');
        }
    }
    Array.from(element.children).forEach((child) => {
        normalizeAttributesForComparison(child);
    });
}

const syncAttributes = (fromEl, toEl) => {
    for (let i = 0; i < fromEl.attributes.length; i++) {
        const attr = fromEl.attributes[i];
        toEl.setAttribute(attr.name, attr.value);
    }
};
function executeMorphdom(rootFromElement, rootToElement, modifiedFieldElements, getElementValue, externalMutationTracker) {
    const originalElementIdsToSwapAfter = [];
    const originalElementsToPreserve = new Map();
    const markElementAsNeedingPostMorphSwap = (id, replaceWithClone) => {
        const oldElement = originalElementsToPreserve.get(id);
        if (!(oldElement instanceof HTMLElement)) {
            throw new Error(`Original element with id ${id} not found`);
        }
        originalElementIdsToSwapAfter.push(id);
        if (!replaceWithClone) {
            return null;
        }
        const clonedOldElement = cloneHTMLElement(oldElement);
        oldElement.replaceWith(clonedOldElement);
        return clonedOldElement;
    };
    rootToElement.querySelectorAll('[data-live-preserve]').forEach((newElement) => {
        const id = newElement.id;
        if (!id) {
            throw new Error('The data-live-preserve attribute requires an id attribute to be set on the element');
        }
        const oldElement = rootFromElement.querySelector(`#${id}`);
        if (!(oldElement instanceof HTMLElement)) {
            throw new Error(`The element with id "${id}" was not found in the original HTML`);
        }
        newElement.removeAttribute('data-live-preserve');
        originalElementsToPreserve.set(id, oldElement);
        syncAttributes(newElement, oldElement);
    });
    Idiomorph.morph(rootFromElement, rootToElement, {
        callbacks: {
            beforeNodeMorphed: (fromEl, toEl) => {
                if (!(fromEl instanceof Element) || !(toEl instanceof Element)) {
                    return true;
                }
                if (fromEl === rootFromElement) {
                    return true;
                }
                if (fromEl.id && originalElementsToPreserve.has(fromEl.id)) {
                    if (fromEl.id === toEl.id) {
                        return false;
                    }
                    const clonedFromEl = markElementAsNeedingPostMorphSwap(fromEl.id, true);
                    if (!clonedFromEl) {
                        throw new Error('missing clone');
                    }
                    Idiomorph.morph(clonedFromEl, toEl);
                    return false;
                }
                if (fromEl instanceof HTMLElement && toEl instanceof HTMLElement) {
                    if (typeof fromEl.__x !== 'undefined') {
                        if (!window.Alpine) {
                            throw new Error('Unable to access Alpine.js though the global window.Alpine variable. Please make sure Alpine.js is loaded before Symfony UX LiveComponent.');
                        }
                        if (typeof window.Alpine.morph !== 'function') {
                            throw new Error('Unable to access Alpine.js morph function. Please make sure the Alpine.js Morph plugin is installed and loaded, see https://alpinejs.dev/plugins/morph for more information.');
                        }
                        window.Alpine.morph(fromEl.__x, toEl);
                    }
                    if (externalMutationTracker.wasElementAdded(fromEl)) {
                        fromEl.insertAdjacentElement('afterend', toEl);
                        return false;
                    }
                    if (modifiedFieldElements.includes(fromEl)) {
                        setValueOnElement(toEl, getElementValue(fromEl));
                    }
                    if (fromEl === document.activeElement &&
                        fromEl !== document.body &&
                        null !== getModelDirectiveFromElement(fromEl, false)) {
                        setValueOnElement(toEl, getElementValue(fromEl));
                    }
                    const elementChanges = externalMutationTracker.getChangedElement(fromEl);
                    if (elementChanges) {
                        elementChanges.applyToElement(toEl);
                    }
                    if (fromEl.nodeName.toUpperCase() !== 'OPTION' && fromEl.isEqualNode(toEl)) {
                        const normalizedFromEl = cloneHTMLElement(fromEl);
                        normalizeAttributesForComparison(normalizedFromEl);
                        const normalizedToEl = cloneHTMLElement(toEl);
                        normalizeAttributesForComparison(normalizedToEl);
                        if (normalizedFromEl.isEqualNode(normalizedToEl)) {
                            return false;
                        }
                    }
                }
                if (fromEl.hasAttribute('data-skip-morph') || (fromEl.id && fromEl.id !== toEl.id)) {
                    fromEl.innerHTML = toEl.innerHTML;
                    return true;
                }
                if (fromEl.parentElement?.hasAttribute('data-skip-morph')) {
                    return false;
                }
                return !fromEl.hasAttribute('data-live-ignore');
            },
            beforeNodeRemoved(node) {
                if (!(node instanceof HTMLElement)) {
                    return true;
                }
                if (node.id && originalElementsToPreserve.has(node.id)) {
                    markElementAsNeedingPostMorphSwap(node.id, false);
                    return true;
                }
                if (externalMutationTracker.wasElementAdded(node)) {
                    return false;
                }
                return !node.hasAttribute('data-live-ignore');
            },
        },
    });
    originalElementIdsToSwapAfter.forEach((id) => {
        const newElement = rootFromElement.querySelector(`#${id}`);
        const originalElement = originalElementsToPreserve.get(id);
        if (!(newElement instanceof HTMLElement) || !(originalElement instanceof HTMLElement)) {
            throw new Error('Missing elements.');
        }
        newElement.replaceWith(originalElement);
    });
}

class UnsyncedInputsTracker {
    constructor(component, modelElementResolver) {
        this.elementEventListeners = [
            { event: 'input', callback: (event) => this.handleInputEvent(event) },
        ];
        this.component = component;
        this.modelElementResolver = modelElementResolver;
        this.unsyncedInputs = new UnsyncedInputContainer();
    }
    activate() {
        this.elementEventListeners.forEach(({ event, callback }) => {
            this.component.element.addEventListener(event, callback);
        });
    }
    deactivate() {
        this.elementEventListeners.forEach(({ event, callback }) => {
            this.component.element.removeEventListener(event, callback);
        });
    }
    markModelAsSynced(modelName) {
        this.unsyncedInputs.markModelAsSynced(modelName);
    }
    handleInputEvent(event) {
        const target = event.target;
        if (!target) {
            return;
        }
        this.updateModelFromElement(target);
    }
    updateModelFromElement(element) {
        if (!elementBelongsToThisComponent(element, this.component)) {
            return;
        }
        if (!(element instanceof HTMLElement)) {
            throw new Error('Could not update model for non HTMLElement');
        }
        const modelName = this.modelElementResolver.getModelName(element);
        this.unsyncedInputs.add(element, modelName);
    }
    getUnsyncedInputs() {
        return this.unsyncedInputs.allUnsyncedInputs();
    }
    getUnsyncedModels() {
        return Array.from(this.unsyncedInputs.getUnsyncedModelNames());
    }
    resetUnsyncedFields() {
        this.unsyncedInputs.resetUnsyncedFields();
    }
}
class UnsyncedInputContainer {
    constructor() {
        this.unsyncedNonModelFields = [];
        this.unsyncedModelNames = [];
        this.unsyncedModelFields = new Map();
    }
    add(element, modelName = null) {
        if (modelName) {
            this.unsyncedModelFields.set(modelName, element);
            if (!this.unsyncedModelNames.includes(modelName)) {
                this.unsyncedModelNames.push(modelName);
            }
            return;
        }
        this.unsyncedNonModelFields.push(element);
    }
    resetUnsyncedFields() {
        this.unsyncedModelFields.forEach((value, key) => {
            if (!this.unsyncedModelNames.includes(key)) {
                this.unsyncedModelFields.delete(key);
            }
        });
    }
    allUnsyncedInputs() {
        return [...this.unsyncedNonModelFields, ...this.unsyncedModelFields.values()];
    }
    markModelAsSynced(modelName) {
        const index = this.unsyncedModelNames.indexOf(modelName);
        if (index !== -1) {
            this.unsyncedModelNames.splice(index, 1);
        }
    }
    getUnsyncedModelNames() {
        return this.unsyncedModelNames;
    }
}

class HookManager {
    constructor() {
        this.hooks = new Map();
    }
    register(hookName, callback) {
        const hooks = this.hooks.get(hookName) || [];
        hooks.push(callback);
        this.hooks.set(hookName, hooks);
    }
    unregister(hookName, callback) {
        const hooks = this.hooks.get(hookName) || [];
        const index = hooks.indexOf(callback);
        if (index === -1) {
            return;
        }
        hooks.splice(index, 1);
        this.hooks.set(hookName, hooks);
    }
    triggerHook(hookName, ...args) {
        const hooks = this.hooks.get(hookName) || [];
        hooks.forEach((callback) => callback(...args));
    }
}

class BackendResponse {
    constructor(response) {
        this.response = response;
    }
    async getBody() {
        if (!this.body) {
            this.body = await this.response.text();
        }
        return this.body;
    }
}

class ChangingItemsTracker {
    constructor() {
        this.changedItems = new Map();
        this.removedItems = new Map();
    }
    setItem(itemName, newValue, previousValue) {
        if (this.removedItems.has(itemName)) {
            const removedRecord = this.removedItems.get(itemName);
            this.removedItems.delete(itemName);
            if (removedRecord.original === newValue) {
                return;
            }
        }
        if (this.changedItems.has(itemName)) {
            const originalRecord = this.changedItems.get(itemName);
            if (originalRecord.original === newValue) {
                this.changedItems.delete(itemName);
                return;
            }
            this.changedItems.set(itemName, { original: originalRecord.original, new: newValue });
            return;
        }
        this.changedItems.set(itemName, { original: previousValue, new: newValue });
    }
    removeItem(itemName, currentValue) {
        let trueOriginalValue = currentValue;
        if (this.changedItems.has(itemName)) {
            const originalRecord = this.changedItems.get(itemName);
            trueOriginalValue = originalRecord.original;
            this.changedItems.delete(itemName);
            if (trueOriginalValue === null) {
                return;
            }
        }
        if (!this.removedItems.has(itemName)) {
            this.removedItems.set(itemName, { original: trueOriginalValue });
        }
    }
    getChangedItems() {
        return Array.from(this.changedItems, ([name, { new: value }]) => ({ name, value }));
    }
    getRemovedItems() {
        return Array.from(this.removedItems.keys());
    }
    isEmpty() {
        return this.changedItems.size === 0 && this.removedItems.size === 0;
    }
}

class ElementChanges {
    constructor() {
        this.addedClasses = new Set();
        this.removedClasses = new Set();
        this.styleChanges = new ChangingItemsTracker();
        this.attributeChanges = new ChangingItemsTracker();
    }
    addClass(className) {
        if (!this.removedClasses.delete(className)) {
            this.addedClasses.add(className);
        }
    }
    removeClass(className) {
        if (!this.addedClasses.delete(className)) {
            this.removedClasses.add(className);
        }
    }
    addStyle(styleName, newValue, originalValue) {
        this.styleChanges.setItem(styleName, newValue, originalValue);
    }
    removeStyle(styleName, originalValue) {
        this.styleChanges.removeItem(styleName, originalValue);
    }
    addAttribute(attributeName, newValue, originalValue) {
        this.attributeChanges.setItem(attributeName, newValue, originalValue);
    }
    removeAttribute(attributeName, originalValue) {
        this.attributeChanges.removeItem(attributeName, originalValue);
    }
    getAddedClasses() {
        return [...this.addedClasses];
    }
    getRemovedClasses() {
        return [...this.removedClasses];
    }
    getChangedStyles() {
        return this.styleChanges.getChangedItems();
    }
    getRemovedStyles() {
        return this.styleChanges.getRemovedItems();
    }
    getChangedAttributes() {
        return this.attributeChanges.getChangedItems();
    }
    getRemovedAttributes() {
        return this.attributeChanges.getRemovedItems();
    }
    applyToElement(element) {
        element.classList.add(...this.addedClasses);
        element.classList.remove(...this.removedClasses);
        this.styleChanges.getChangedItems().forEach((change) => {
            element.style.setProperty(change.name, change.value);
            return;
        });
        this.styleChanges.getRemovedItems().forEach((styleName) => {
            element.style.removeProperty(styleName);
        });
        this.attributeChanges.getChangedItems().forEach((change) => {
            element.setAttribute(change.name, change.value);
        });
        this.attributeChanges.getRemovedItems().forEach((attributeName) => {
            element.removeAttribute(attributeName);
        });
    }
    isEmpty() {
        return (this.addedClasses.size === 0 &&
            this.removedClasses.size === 0 &&
            this.styleChanges.isEmpty() &&
            this.attributeChanges.isEmpty());
    }
}

class ExternalMutationTracker {
    constructor(element, shouldTrackChangeCallback) {
        this.changedElements = new WeakMap();
        this.changedElementsCount = 0;
        this.addedElements = [];
        this.removedElements = [];
        this.isStarted = false;
        this.element = element;
        this.shouldTrackChangeCallback = shouldTrackChangeCallback;
        this.mutationObserver = new MutationObserver(this.onMutations.bind(this));
    }
    start() {
        if (this.isStarted) {
            return;
        }
        this.mutationObserver.observe(this.element, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeOldValue: true,
        });
        this.isStarted = true;
    }
    stop() {
        if (this.isStarted) {
            this.mutationObserver.disconnect();
            this.isStarted = false;
        }
    }
    getChangedElement(element) {
        return this.changedElements.has(element) ? this.changedElements.get(element) : null;
    }
    getAddedElements() {
        return this.addedElements;
    }
    wasElementAdded(element) {
        return this.addedElements.includes(element);
    }
    handlePendingChanges() {
        this.onMutations(this.mutationObserver.takeRecords());
    }
    onMutations(mutations) {
        const handledAttributeMutations = new WeakMap();
        for (const mutation of mutations) {
            const element = mutation.target;
            if (!this.shouldTrackChangeCallback(element)) {
                continue;
            }
            if (this.isElementAddedByTranslation(element)) {
                continue;
            }
            let isChangeInAddedElement = false;
            for (const addedElement of this.addedElements) {
                if (addedElement.contains(element)) {
                    isChangeInAddedElement = true;
                    break;
                }
            }
            if (isChangeInAddedElement) {
                continue;
            }
            switch (mutation.type) {
                case 'childList':
                    this.handleChildListMutation(mutation);
                    break;
                case 'attributes':
                    if (!handledAttributeMutations.has(element)) {
                        handledAttributeMutations.set(element, []);
                    }
                    if (!handledAttributeMutations.get(element).includes(mutation.attributeName)) {
                        this.handleAttributeMutation(mutation);
                        handledAttributeMutations.set(element, [
                            ...handledAttributeMutations.get(element),
                            mutation.attributeName,
                        ]);
                    }
                    break;
            }
        }
    }
    handleChildListMutation(mutation) {
        mutation.addedNodes.forEach((node) => {
            if (!(node instanceof Element)) {
                return;
            }
            if (this.removedElements.includes(node)) {
                this.removedElements.splice(this.removedElements.indexOf(node), 1);
                return;
            }
            if (this.isElementAddedByTranslation(node)) {
                return;
            }
            this.addedElements.push(node);
        });
        mutation.removedNodes.forEach((node) => {
            if (!(node instanceof Element)) {
                return;
            }
            if (this.addedElements.includes(node)) {
                this.addedElements.splice(this.addedElements.indexOf(node), 1);
                return;
            }
            this.removedElements.push(node);
        });
    }
    handleAttributeMutation(mutation) {
        const element = mutation.target;
        if (!this.changedElements.has(element)) {
            this.changedElements.set(element, new ElementChanges());
            this.changedElementsCount++;
        }
        const changedElement = this.changedElements.get(element);
        switch (mutation.attributeName) {
            case 'class':
                this.handleClassAttributeMutation(mutation, changedElement);
                break;
            case 'style':
                this.handleStyleAttributeMutation(mutation, changedElement);
                break;
            default:
                this.handleGenericAttributeMutation(mutation, changedElement);
        }
        if (changedElement.isEmpty()) {
            this.changedElements.delete(element);
            this.changedElementsCount--;
        }
    }
    handleClassAttributeMutation(mutation, elementChanges) {
        const element = mutation.target;
        const previousValue = mutation.oldValue || '';
        const previousValues = previousValue.match(/(\S+)/gu) || [];
        const newValues = [].slice.call(element.classList);
        const addedValues = newValues.filter((value) => !previousValues.includes(value));
        const removedValues = previousValues.filter((value) => !newValues.includes(value));
        addedValues.forEach((value) => {
            elementChanges.addClass(value);
        });
        removedValues.forEach((value) => {
            elementChanges.removeClass(value);
        });
    }
    handleStyleAttributeMutation(mutation, elementChanges) {
        const element = mutation.target;
        const previousValue = mutation.oldValue || '';
        const previousStyles = this.extractStyles(previousValue);
        const newValue = element.getAttribute('style') || '';
        const newStyles = this.extractStyles(newValue);
        const addedOrChangedStyles = Object.keys(newStyles).filter((key) => previousStyles[key] === undefined || previousStyles[key] !== newStyles[key]);
        const removedStyles = Object.keys(previousStyles).filter((key) => !newStyles[key]);
        addedOrChangedStyles.forEach((style) => {
            elementChanges.addStyle(style, newStyles[style], previousStyles[style] === undefined ? null : previousStyles[style]);
        });
        removedStyles.forEach((style) => {
            elementChanges.removeStyle(style, previousStyles[style]);
        });
    }
    handleGenericAttributeMutation(mutation, elementChanges) {
        const attributeName = mutation.attributeName;
        const element = mutation.target;
        let oldValue = mutation.oldValue;
        let newValue = element.getAttribute(attributeName);
        if (oldValue === attributeName) {
            oldValue = '';
        }
        if (newValue === attributeName) {
            newValue = '';
        }
        if (!element.hasAttribute(attributeName)) {
            if (oldValue === null) {
                return;
            }
            elementChanges.removeAttribute(attributeName, mutation.oldValue);
            return;
        }
        if (newValue === oldValue) {
            return;
        }
        elementChanges.addAttribute(attributeName, element.getAttribute(attributeName), mutation.oldValue);
    }
    extractStyles(styles) {
        const styleObject = {};
        styles.split(';').forEach((style) => {
            const parts = style.split(':');
            if (parts.length === 1) {
                return;
            }
            const property = parts[0].trim();
            styleObject[property] = parts.slice(1).join(':').trim();
        });
        return styleObject;
    }
    isElementAddedByTranslation(element) {
        return element.tagName === 'FONT' && element.getAttribute('style') === 'vertical-align: inherit;';
    }
}

class Component {
    constructor(element, name, props, listeners, id, backend, elementDriver) {
        this.fingerprint = '';
        this.defaultDebounce = 150;
        this.backendRequest = null;
        this.pendingActions = [];
        this.pendingFiles = {};
        this.isRequestPending = false;
        this.requestDebounceTimeout = null;
        this.element = element;
        this.name = name;
        this.backend = backend;
        this.elementDriver = elementDriver;
        this.id = id;
        this.listeners = new Map();
        listeners.forEach((listener) => {
            if (!this.listeners.has(listener.event)) {
                this.listeners.set(listener.event, []);
            }
            this.listeners.get(listener.event)?.push(listener.action);
        });
        this.valueStore = new ValueStore(props);
        this.unsyncedInputsTracker = new UnsyncedInputsTracker(this, elementDriver);
        this.hooks = new HookManager();
        this.resetPromise();
        this.externalMutationTracker = new ExternalMutationTracker(this.element, (element) => elementBelongsToThisComponent(element, this));
        this.externalMutationTracker.start();
    }
    addPlugin(plugin) {
        plugin.attachToComponent(this);
    }
    connect() {
        registerComponent(this);
        this.hooks.triggerHook('connect', this);
        this.unsyncedInputsTracker.activate();
        this.externalMutationTracker.start();
    }
    disconnect() {
        unregisterComponent(this);
        this.hooks.triggerHook('disconnect', this);
        this.clearRequestDebounceTimeout();
        this.unsyncedInputsTracker.deactivate();
        this.externalMutationTracker.stop();
    }
    on(hookName, callback) {
        this.hooks.register(hookName, callback);
    }
    off(hookName, callback) {
        this.hooks.unregister(hookName, callback);
    }
    set(model, value, reRender = false, debounce = false) {
        const promise = this.nextRequestPromise;
        const modelName = normalizeModelName(model);
        if (!this.valueStore.has(modelName)) {
            throw new Error(`Invalid model name "${model}".`);
        }
        const isChanged = this.valueStore.set(modelName, value);
        this.hooks.triggerHook('model:set', model, value, this);
        this.unsyncedInputsTracker.markModelAsSynced(modelName);
        if (reRender && isChanged) {
            this.debouncedStartRequest(debounce);
        }
        return promise;
    }
    getData(model) {
        const modelName = normalizeModelName(model);
        if (!this.valueStore.has(modelName)) {
            throw new Error(`Invalid model "${model}".`);
        }
        return this.valueStore.get(modelName);
    }
    action(name, args = {}, debounce = false) {
        const promise = this.nextRequestPromise;
        this.pendingActions.push({
            name,
            args,
        });
        this.debouncedStartRequest(debounce);
        return promise;
    }
    files(key, input) {
        this.pendingFiles[key] = input;
    }
    render() {
        const promise = this.nextRequestPromise;
        this.tryStartingRequest();
        return promise;
    }
    getUnsyncedModels() {
        return this.unsyncedInputsTracker.getUnsyncedModels();
    }
    emit(name, data, onlyMatchingComponentsNamed = null) {
        this.performEmit(name, data, false, onlyMatchingComponentsNamed);
    }
    emitUp(name, data, onlyMatchingComponentsNamed = null) {
        this.performEmit(name, data, true, onlyMatchingComponentsNamed);
    }
    emitSelf(name, data) {
        this.doEmit(name, data);
    }
    performEmit(name, data, emitUp, matchingName) {
        const components = findComponents(this, emitUp, matchingName);
        components.forEach((component) => {
            component.doEmit(name, data);
        });
    }
    doEmit(name, data) {
        if (!this.listeners.has(name)) {
            return;
        }
        const actions = this.listeners.get(name) || [];
        actions.forEach((action) => {
            this.action(action, data, 1);
        });
    }
    isTurboEnabled() {
        return typeof Turbo !== 'undefined' && !this.element.closest('[data-turbo="false"]');
    }
    tryStartingRequest() {
        if (!this.backendRequest) {
            this.performRequest();
            return;
        }
        this.isRequestPending = true;
    }
    performRequest() {
        const thisPromiseResolve = this.nextRequestPromiseResolve;
        this.resetPromise();
        this.unsyncedInputsTracker.resetUnsyncedFields();
        const filesToSend = {};
        for (const [key, value] of Object.entries(this.pendingFiles)) {
            if (value.files) {
                filesToSend[key] = value.files;
            }
        }
        const requestConfig = {
            props: this.valueStore.getOriginalProps(),
            actions: this.pendingActions,
            updated: this.valueStore.getDirtyProps(),
            children: {},
            updatedPropsFromParent: this.valueStore.getUpdatedPropsFromParent(),
            files: filesToSend,
        };
        this.hooks.triggerHook('request:started', requestConfig);
        this.backendRequest = this.backend.makeRequest(requestConfig.props, requestConfig.actions, requestConfig.updated, requestConfig.children, requestConfig.updatedPropsFromParent, requestConfig.files);
        this.hooks.triggerHook('loading.state:started', this.element, this.backendRequest);
        this.pendingActions = [];
        this.valueStore.flushDirtyPropsToPending();
        this.isRequestPending = false;
        this.backendRequest.promise.then(async (response) => {
            const backendResponse = new BackendResponse(response);
            const html = await backendResponse.getBody();
            for (const input of Object.values(this.pendingFiles)) {
                input.value = '';
            }
            const headers = backendResponse.response.headers;
            if (!headers.get('Content-Type')?.includes('application/vnd.live-component+html') &&
                !headers.get('X-Live-Redirect')) {
                const controls = { displayError: true };
                this.valueStore.pushPendingPropsBackToDirty();
                this.hooks.triggerHook('response:error', backendResponse, controls);
                if (controls.displayError) {
                    this.renderError(html);
                }
                this.backendRequest = null;
                thisPromiseResolve(backendResponse);
                return response;
            }
            this.processRerender(html, backendResponse);
            this.backendRequest = null;
            thisPromiseResolve(backendResponse);
            if (this.isRequestPending) {
                this.isRequestPending = false;
                this.performRequest();
            }
            return response;
        });
    }
    processRerender(html, backendResponse) {
        const controls = { shouldRender: true };
        this.hooks.triggerHook('render:started', html, backendResponse, controls);
        if (!controls.shouldRender) {
            return;
        }
        if (backendResponse.response.headers.get('Location')) {
            if (this.isTurboEnabled()) {
                Turbo.visit(backendResponse.response.headers.get('Location'));
            }
            else {
                window.location.href = backendResponse.response.headers.get('Location') || '';
            }
            return;
        }
        this.hooks.triggerHook('loading.state:finished', this.element);
        const modifiedModelValues = {};
        Object.keys(this.valueStore.getDirtyProps()).forEach((modelName) => {
            modifiedModelValues[modelName] = this.valueStore.get(modelName);
        });
        let newElement;
        try {
            newElement = htmlToElement(html);
            if (!newElement.matches('[data-controller~=live]')) {
                throw new Error('A live component template must contain a single root controller element.');
            }
        }
        catch (error) {
            console.error(`There was a problem with the '${this.name}' component HTML returned:`, {
                id: this.id,
            });
            throw error;
        }
        this.externalMutationTracker.handlePendingChanges();
        this.externalMutationTracker.stop();
        executeMorphdom(this.element, newElement, this.unsyncedInputsTracker.getUnsyncedInputs(), (element) => getValueFromElement(element, this.valueStore), this.externalMutationTracker);
        this.externalMutationTracker.start();
        const newProps = this.elementDriver.getComponentProps();
        this.valueStore.reinitializeAllProps(newProps);
        const eventsToEmit = this.elementDriver.getEventsToEmit();
        const browserEventsToDispatch = this.elementDriver.getBrowserEventsToDispatch();
        Object.keys(modifiedModelValues).forEach((modelName) => {
            this.valueStore.set(modelName, modifiedModelValues[modelName]);
        });
        eventsToEmit.forEach(({ event, data, target, componentName }) => {
            if (target === 'up') {
                this.emitUp(event, data, componentName);
                return;
            }
            if (target === 'self') {
                this.emitSelf(event, data);
                return;
            }
            this.emit(event, data, componentName);
        });
        browserEventsToDispatch.forEach(({ event, payload }) => {
            this.element.dispatchEvent(new CustomEvent(event, {
                detail: payload,
                bubbles: true,
            }));
        });
        this.hooks.triggerHook('render:finished', this);
    }
    calculateDebounce(debounce) {
        if (debounce === true) {
            return this.defaultDebounce;
        }
        if (debounce === false) {
            return 0;
        }
        return debounce;
    }
    clearRequestDebounceTimeout() {
        if (this.requestDebounceTimeout) {
            clearTimeout(this.requestDebounceTimeout);
            this.requestDebounceTimeout = null;
        }
    }
    debouncedStartRequest(debounce) {
        this.clearRequestDebounceTimeout();
        this.requestDebounceTimeout = window.setTimeout(() => {
            this.render();
        }, this.calculateDebounce(debounce));
    }
    renderError(html) {
        let modal = document.getElementById('live-component-error');
        if (modal) {
            modal.innerHTML = '';
        }
        else {
            modal = document.createElement('div');
            modal.id = 'live-component-error';
            modal.style.padding = '50px';
            modal.style.backgroundColor = 'rgba(0, 0, 0, .5)';
            modal.style.zIndex = '100000';
            modal.style.position = 'fixed';
            modal.style.top = '0px';
            modal.style.bottom = '0px';
            modal.style.left = '0px';
            modal.style.right = '0px';
            modal.style.display = 'flex';
            modal.style.flexDirection = 'column';
        }
        const iframe = document.createElement('iframe');
        iframe.style.borderRadius = '5px';
        iframe.style.flexGrow = '1';
        modal.appendChild(iframe);
        document.body.prepend(modal);
        document.body.style.overflow = 'hidden';
        if (iframe.contentWindow) {
            iframe.contentWindow.document.open();
            iframe.contentWindow.document.write(html);
            iframe.contentWindow.document.close();
        }
        const closeModal = (modal) => {
            if (modal) {
                modal.outerHTML = '';
            }
            document.body.style.overflow = 'visible';
        };
        modal.addEventListener('click', () => closeModal(modal));
        modal.setAttribute('tabindex', '0');
        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal(modal);
            }
        });
        modal.focus();
    }
    resetPromise() {
        this.nextRequestPromise = new Promise((resolve) => {
            this.nextRequestPromiseResolve = resolve;
        });
    }
    _updateFromParentProps(props) {
        const isChanged = this.valueStore.storeNewPropsFromParent(props);
        if (isChanged) {
            this.render();
        }
    }
}
function proxifyComponent(component) {
    return new Proxy(component, {
        get(component, prop) {
            if (prop in component || typeof prop !== 'string') {
                if (typeof component[prop] === 'function') {
                    const callable = component[prop];
                    return (...args) => {
                        return callable.apply(component, args);
                    };
                }
                return Reflect.get(component, prop);
            }
            if (component.valueStore.has(prop)) {
                return component.getData(prop);
            }
            return (args) => {
                return component.action.apply(component, [prop, args]);
            };
        },
        set(target, property, value) {
            if (property in target) {
                target[property] = value;
                return true;
            }
            target.set(property, value);
            return true;
        },
    });
}

class BackendRequest {
    constructor(promise, actions, updateModels) {
        this.isResolved = false;
        this.promise = promise;
        this.promise.then((response) => {
            this.isResolved = true;
            return response;
        });
        this.actions = actions;
        this.updatedModels = updateModels;
    }
    containsOneOfActions(targetedActions) {
        return this.actions.filter((action) => targetedActions.includes(action)).length > 0;
    }
    areAnyModelsUpdated(targetedModels) {
        return this.updatedModels.filter((model) => targetedModels.includes(model)).length > 0;
    }
}

class RequestBuilder {
    constructor(url, method = 'post', csrfToken = null) {
        this.url = url;
        this.method = method;
        this.csrfToken = csrfToken;
    }
    buildRequest(props, actions, updated, children, updatedPropsFromParent, files) {
        const splitUrl = this.url.split('?');
        let [url] = splitUrl;
        const [, queryString] = splitUrl;
        const params = new URLSearchParams(queryString || '');
        const fetchOptions = {};
        fetchOptions.headers = {
            Accept: 'application/vnd.live-component+html',
            'X-Requested-With': 'XMLHttpRequest',
        };
        const totalFiles = Object.entries(files).reduce((total, current) => total + current.length, 0);
        const hasFingerprints = Object.keys(children).length > 0;
        if (actions.length === 0 &&
            totalFiles === 0 &&
            this.method === 'get' &&
            this.willDataFitInUrl(JSON.stringify(props), JSON.stringify(updated), params, JSON.stringify(children), JSON.stringify(updatedPropsFromParent))) {
            params.set('props', JSON.stringify(props));
            params.set('updated', JSON.stringify(updated));
            if (Object.keys(updatedPropsFromParent).length > 0) {
                params.set('propsFromParent', JSON.stringify(updatedPropsFromParent));
            }
            if (hasFingerprints) {
                params.set('children', JSON.stringify(children));
            }
            fetchOptions.method = 'GET';
        }
        else {
            fetchOptions.method = 'POST';
            const requestData = { props, updated };
            if (Object.keys(updatedPropsFromParent).length > 0) {
                requestData.propsFromParent = updatedPropsFromParent;
            }
            if (hasFingerprints) {
                requestData.children = children;
            }
            if (this.csrfToken && (actions.length || totalFiles)) {
                fetchOptions.headers['X-CSRF-TOKEN'] = this.csrfToken;
            }
            if (actions.length > 0) {
                if (actions.length === 1) {
                    requestData.args = actions[0].args;
                    url += `/${encodeURIComponent(actions[0].name)}`;
                }
                else {
                    url += '/_batch';
                    requestData.actions = actions;
                }
            }
            const formData = new FormData();
            formData.append('data', JSON.stringify(requestData));
            for (const [key, value] of Object.entries(files)) {
                const length = value.length;
                for (let i = 0; i < length; ++i) {
                    formData.append(key, value[i]);
                }
            }
            fetchOptions.body = formData;
        }
        const paramsString = params.toString();
        return {
            url: `${url}${paramsString.length > 0 ? `?${paramsString}` : ''}`,
            fetchOptions,
        };
    }
    willDataFitInUrl(propsJson, updatedJson, params, childrenJson, propsFromParentJson) {
        const urlEncodedJsonData = new URLSearchParams(propsJson + updatedJson + childrenJson + propsFromParentJson).toString();
        return (urlEncodedJsonData + params.toString()).length < 1500;
    }
}

class Backend {
    constructor(url, method = 'post', csrfToken = null) {
        this.requestBuilder = new RequestBuilder(url, method, csrfToken);
    }
    makeRequest(props, actions, updated, children, updatedPropsFromParent, files) {
        const { url, fetchOptions } = this.requestBuilder.buildRequest(props, actions, updated, children, updatedPropsFromParent, files);
        return new BackendRequest(fetch(url, fetchOptions), actions.map((backendAction) => backendAction.name), Object.keys(updated));
    }
}

class StimulusElementDriver {
    constructor(controller) {
        this.controller = controller;
    }
    getModelName(element) {
        const modelDirective = getModelDirectiveFromElement(element, false);
        if (!modelDirective) {
            return null;
        }
        return modelDirective.action;
    }
    getComponentProps() {
        return this.controller.propsValue;
    }
    getEventsToEmit() {
        return this.controller.eventsToEmitValue;
    }
    getBrowserEventsToDispatch() {
        return this.controller.eventsToDispatchValue;
    }
}

class LoadingPlugin {
    attachToComponent(component) {
        component.on('loading.state:started', (element, request) => {
            this.startLoading(component, element, request);
        });
        component.on('loading.state:finished', (element) => {
            this.finishLoading(component, element);
        });
        this.finishLoading(component, component.element);
    }
    startLoading(component, targetElement, backendRequest) {
        this.handleLoadingToggle(component, true, targetElement, backendRequest);
    }
    finishLoading(component, targetElement) {
        this.handleLoadingToggle(component, false, targetElement, null);
    }
    handleLoadingToggle(component, isLoading, targetElement, backendRequest) {
        if (isLoading) {
            this.addAttributes(targetElement, ['busy']);
        }
        else {
            this.removeAttributes(targetElement, ['busy']);
        }
        this.getLoadingDirectives(component, targetElement).forEach(({ element, directives }) => {
            if (isLoading) {
                this.addAttributes(element, ['data-live-is-loading']);
            }
            else {
                this.removeAttributes(element, ['data-live-is-loading']);
            }
            directives.forEach((directive) => {
                this.handleLoadingDirective(element, isLoading, directive, backendRequest);
            });
        });
    }
    handleLoadingDirective(element, isLoading, directive, backendRequest) {
        const finalAction = parseLoadingAction(directive.action, isLoading);
        const targetedActions = [];
        const targetedModels = [];
        let delay = 0;
        const validModifiers = new Map();
        validModifiers.set('delay', (modifier) => {
            if (!isLoading) {
                return;
            }
            delay = modifier.value ? Number.parseInt(modifier.value) : 200;
        });
        validModifiers.set('action', (modifier) => {
            if (!modifier.value) {
                throw new Error(`The "action" in data-loading must have an action name - e.g. action(foo). It's missing for "${directive.getString()}"`);
            }
            targetedActions.push(modifier.value);
        });
        validModifiers.set('model', (modifier) => {
            if (!modifier.value) {
                throw new Error(`The "model" in data-loading must have an action name - e.g. model(foo). It's missing for "${directive.getString()}"`);
            }
            targetedModels.push(modifier.value);
        });
        directive.modifiers.forEach((modifier) => {
            if (validModifiers.has(modifier.name)) {
                const callable = validModifiers.get(modifier.name) ?? (() => { });
                callable(modifier);
                return;
            }
            throw new Error(`Unknown modifier "${modifier.name}" used in data-loading="${directive.getString()}". Available modifiers are: ${Array.from(validModifiers.keys()).join(', ')}.`);
        });
        if (isLoading &&
            targetedActions.length > 0 &&
            backendRequest &&
            !backendRequest.containsOneOfActions(targetedActions)) {
            return;
        }
        if (isLoading &&
            targetedModels.length > 0 &&
            backendRequest &&
            !backendRequest.areAnyModelsUpdated(targetedModels)) {
            return;
        }
        let loadingDirective;
        switch (finalAction) {
            case 'show':
                loadingDirective = () => this.showElement(element);
                break;
            case 'hide':
                loadingDirective = () => this.hideElement(element);
                break;
            case 'addClass':
                loadingDirective = () => this.addClass(element, directive.args);
                break;
            case 'removeClass':
                loadingDirective = () => this.removeClass(element, directive.args);
                break;
            case 'addAttribute':
                loadingDirective = () => this.addAttributes(element, directive.args);
                break;
            case 'removeAttribute':
                loadingDirective = () => this.removeAttributes(element, directive.args);
                break;
            default:
                throw new Error(`Unknown data-loading action "${finalAction}"`);
        }
        if (delay) {
            window.setTimeout(() => {
                if (backendRequest && !backendRequest.isResolved) {
                    loadingDirective();
                }
            }, delay);
            return;
        }
        loadingDirective();
    }
    getLoadingDirectives(component, element) {
        const loadingDirectives = [];
        let matchingElements = [...Array.from(element.querySelectorAll('[data-loading]'))];
        matchingElements = matchingElements.filter((elt) => elementBelongsToThisComponent(elt, component));
        if (element.hasAttribute('data-loading')) {
            matchingElements = [element, ...matchingElements];
        }
        matchingElements.forEach((element) => {
            if (!(element instanceof HTMLElement) && !(element instanceof SVGElement)) {
                throw new Error('Invalid Element Type');
            }
            const directives = parseDirectives(element.dataset.loading || 'show');
            loadingDirectives.push({
                element,
                directives,
            });
        });
        return loadingDirectives;
    }
    showElement(element) {
        element.style.display = 'revert';
    }
    hideElement(element) {
        element.style.display = 'none';
    }
    addClass(element, classes) {
        element.classList.add(...combineSpacedArray(classes));
    }
    removeClass(element, classes) {
        element.classList.remove(...combineSpacedArray(classes));
        if (element.classList.length === 0) {
            element.removeAttribute('class');
        }
    }
    addAttributes(element, attributes) {
        attributes.forEach((attribute) => {
            element.setAttribute(attribute, '');
        });
    }
    removeAttributes(element, attributes) {
        attributes.forEach((attribute) => {
            element.removeAttribute(attribute);
        });
    }
}
const parseLoadingAction = (action, isLoading) => {
    switch (action) {
        case 'show':
            return isLoading ? 'show' : 'hide';
        case 'hide':
            return isLoading ? 'hide' : 'show';
        case 'addClass':
            return isLoading ? 'addClass' : 'removeClass';
        case 'removeClass':
            return isLoading ? 'removeClass' : 'addClass';
        case 'addAttribute':
            return isLoading ? 'addAttribute' : 'removeAttribute';
        case 'removeAttribute':
            return isLoading ? 'removeAttribute' : 'addAttribute';
    }
    throw new Error(`Unknown data-loading action "${action}"`);
};

class ValidatedFieldsPlugin {
    attachToComponent(component) {
        component.on('model:set', (modelName) => {
            this.handleModelSet(modelName, component.valueStore);
        });
    }
    handleModelSet(modelName, valueStore) {
        if (valueStore.has('validatedFields')) {
            const validatedFields = [...valueStore.get('validatedFields')];
            if (!validatedFields.includes(modelName)) {
                validatedFields.push(modelName);
            }
            valueStore.set('validatedFields', validatedFields);
        }
    }
}

class PageUnloadingPlugin {
    constructor() {
        this.isConnected = false;
    }
    attachToComponent(component) {
        component.on('render:started', (html, response, controls) => {
            if (!this.isConnected) {
                controls.shouldRender = false;
            }
        });
        component.on('connect', () => {
            this.isConnected = true;
        });
        component.on('disconnect', () => {
            this.isConnected = false;
        });
    }
}

class PollingDirector {
    constructor(component) {
        this.isPollingActive = true;
        this.pollingIntervals = [];
        this.component = component;
    }
    addPoll(actionName, duration) {
        this.polls.push({ actionName, duration });
        if (this.isPollingActive) {
            this.initiatePoll(actionName, duration);
        }
    }
    startAllPolling() {
        if (this.isPollingActive) {
            return;
        }
        this.isPollingActive = true;
        this.polls.forEach(({ actionName, duration }) => {
            this.initiatePoll(actionName, duration);
        });
    }
    stopAllPolling() {
        this.isPollingActive = false;
        this.pollingIntervals.forEach((interval) => {
            clearInterval(interval);
        });
    }
    clearPolling() {
        this.stopAllPolling();
        this.polls = [];
        this.startAllPolling();
    }
    initiatePoll(actionName, duration) {
        let callback;
        if (actionName === '$render') {
            callback = () => {
                this.component.render();
            };
        }
        else {
            callback = () => {
                this.component.action(actionName, {}, 0);
            };
        }
        const timer = window.setInterval(() => {
            callback();
        }, duration);
        this.pollingIntervals.push(timer);
    }
}

class PollingPlugin {
    attachToComponent(component) {
        this.element = component.element;
        this.pollingDirector = new PollingDirector(component);
        this.initializePolling();
        component.on('connect', () => {
            this.pollingDirector.startAllPolling();
        });
        component.on('disconnect', () => {
            this.pollingDirector.stopAllPolling();
        });
        component.on('render:finished', () => {
            this.initializePolling();
        });
    }
    addPoll(actionName, duration) {
        this.pollingDirector.addPoll(actionName, duration);
    }
    clearPolling() {
        this.pollingDirector.clearPolling();
    }
    initializePolling() {
        this.clearPolling();
        if (this.element.dataset.poll === undefined) {
            return;
        }
        const rawPollConfig = this.element.dataset.poll;
        const directives = parseDirectives(rawPollConfig || '$render');
        directives.forEach((directive) => {
            let duration = 2000;
            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'delay':
                        if (modifier.value) {
                            duration = Number.parseInt(modifier.value);
                        }
                        break;
                    default:
                        console.warn(`Unknown modifier "${modifier.name}" in data-poll "${rawPollConfig}".`);
                }
            });
            this.addPoll(directive.action, duration);
        });
    }
}

class SetValueOntoModelFieldsPlugin {
    attachToComponent(component) {
        this.synchronizeValueOfModelFields(component);
        component.on('render:finished', () => {
            this.synchronizeValueOfModelFields(component);
        });
    }
    synchronizeValueOfModelFields(component) {
        component.element.querySelectorAll('[data-model]').forEach((element) => {
            if (!(element instanceof HTMLElement)) {
                throw new Error('Invalid element using data-model.');
            }
            if (element instanceof HTMLFormElement) {
                return;
            }
            if (!elementBelongsToThisComponent(element, component)) {
                return;
            }
            const modelDirective = getModelDirectiveFromElement(element);
            if (!modelDirective) {
                return;
            }
            const modelName = modelDirective.action;
            if (component.getUnsyncedModels().includes(modelName)) {
                return;
            }
            if (component.valueStore.has(modelName)) {
                setValueOnElement(element, component.valueStore.get(modelName));
            }
            if (element instanceof HTMLSelectElement && !element.multiple) {
                component.valueStore.set(modelName, getValueFromElement(element, component.valueStore));
            }
        });
    }
}

function getModelBinding (modelDirective) {
    let shouldRender = true;
    let targetEventName = null;
    let debounce = false;
    modelDirective.modifiers.forEach((modifier) => {
        switch (modifier.name) {
            case 'on':
                if (!modifier.value) {
                    throw new Error(`The "on" modifier in ${modelDirective.getString()} requires a value - e.g. on(change).`);
                }
                if (!['input', 'change'].includes(modifier.value)) {
                    throw new Error(`The "on" modifier in ${modelDirective.getString()} only accepts the arguments "input" or "change".`);
                }
                targetEventName = modifier.value;
                break;
            case 'norender':
                shouldRender = false;
                break;
            case 'debounce':
                debounce = modifier.value ? Number.parseInt(modifier.value) : true;
                break;
            default:
                throw new Error(`Unknown modifier "${modifier.name}" in data-model="${modelDirective.getString()}".`);
        }
    });
    const [modelName, innerModelName] = modelDirective.action.split(':');
    return {
        modelName,
        innerModelName: innerModelName || null,
        shouldRender,
        debounce,
        targetEventName,
    };
}

function isValueEmpty(value) {
    if (null === value || value === '' || undefined === value || (Array.isArray(value) && value.length === 0)) {
        return true;
    }
    if (typeof value !== 'object') {
        return false;
    }
    for (const key of Object.keys(value)) {
        if (!isValueEmpty(value[key])) {
            return false;
        }
    }
    return true;
}
function toQueryString(data) {
    const buildQueryStringEntries = (data, entries = {}, baseKey = '') => {
        Object.entries(data).forEach(([iKey, iValue]) => {
            const key = baseKey === '' ? iKey : `${baseKey}[${iKey}]`;
            if ('' === baseKey && isValueEmpty(iValue)) {
                entries[key] = '';
            }
            else if (null !== iValue) {
                if (typeof iValue === 'object') {
                    entries = { ...entries, ...buildQueryStringEntries(iValue, entries, key) };
                }
                else {
                    entries[key] = encodeURIComponent(iValue)
                        .replace(/%20/g, '+')
                        .replace(/%2C/g, ',');
                }
            }
        });
        return entries;
    };
    const entries = buildQueryStringEntries(data);
    return Object.entries(entries)
        .map(([key, value]) => `${key}=${value}`)
        .join('&');
}
function fromQueryString(search) {
    search = search.replace('?', '');
    if (search === '')
        return {};
    const insertDotNotatedValueIntoData = (key, value, data) => {
        const [first, second, ...rest] = key.split('.');
        if (!second) {
            data[key] = value;
            return value;
        }
        if (data[first] === undefined) {
            data[first] = Number.isNaN(Number.parseInt(second)) ? {} : [];
        }
        insertDotNotatedValueIntoData([second, ...rest].join('.'), value, data[first]);
    };
    const entries = search.split('&').map((i) => i.split('='));
    const data = {};
    entries.forEach(([key, value]) => {
        value = decodeURIComponent(value.replace(/\+/g, '%20'));
        if (!key.includes('[')) {
            data[key] = value;
        }
        else {
            if ('' === value)
                return;
            const dotNotatedKey = key.replace(/\[/g, '.').replace(/]/g, '');
            insertDotNotatedValueIntoData(dotNotatedKey, value, data);
        }
    });
    return data;
}
class UrlUtils extends URL {
    has(key) {
        const data = this.getData();
        return Object.keys(data).includes(key);
    }
    set(key, value) {
        const data = this.getData();
        data[key] = value;
        this.setData(data);
    }
    get(key) {
        return this.getData()[key];
    }
    remove(key) {
        const data = this.getData();
        delete data[key];
        this.setData(data);
    }
    getData() {
        if (!this.search) {
            return {};
        }
        return fromQueryString(this.search);
    }
    setData(data) {
        this.search = toQueryString(data);
    }
}
class HistoryStrategy {
    static replace(url) {
        history.replaceState(history.state, '', url);
    }
}

class QueryStringPlugin {
    constructor(mapping) {
        this.mapping = mapping;
    }
    attachToComponent(component) {
        component.on('render:finished', (component) => {
            const urlUtils = new UrlUtils(window.location.href);
            const currentUrl = urlUtils.toString();
            Object.entries(this.mapping).forEach(([prop, mapping]) => {
                const value = component.valueStore.get(prop);
                urlUtils.set(mapping.name, value);
            });
            if (currentUrl !== urlUtils.toString()) {
                HistoryStrategy.replace(urlUtils);
            }
        });
    }
}

class ChildComponentPlugin {
    constructor(component) {
        this.parentModelBindings = [];
        this.component = component;
        const modelDirectives = getAllModelDirectiveFromElements(this.component.element);
        this.parentModelBindings = modelDirectives.map(getModelBinding);
    }
    attachToComponent(component) {
        component.on('request:started', (requestData) => {
            requestData.children = this.getChildrenFingerprints();
        });
        component.on('model:set', (model, value) => {
            this.notifyParentModelChange(model, value);
        });
    }
    getChildrenFingerprints() {
        const fingerprints = {};
        this.getChildren().forEach((child) => {
            if (!child.id) {
                throw new Error('missing id');
            }
            fingerprints[child.id] = {
                fingerprint: child.fingerprint,
                tag: child.element.tagName.toLowerCase(),
            };
        });
        return fingerprints;
    }
    notifyParentModelChange(modelName, value) {
        const parentComponent = findParent(this.component);
        if (!parentComponent) {
            return;
        }
        this.parentModelBindings.forEach((modelBinding) => {
            const childModelName = modelBinding.innerModelName || 'value';
            if (childModelName !== modelName) {
                return;
            }
            parentComponent.set(modelBinding.modelName, value, modelBinding.shouldRender, modelBinding.debounce);
        });
    }
    getChildren() {
        return findChildren(this.component);
    }
}

class LazyPlugin {
    constructor() {
        this.intersectionObserver = null;
    }
    attachToComponent(component) {
        if ('lazy' !== component.element.attributes.getNamedItem('loading')?.value) {
            return;
        }
        component.on('connect', () => {
            this.getObserver().observe(component.element);
        });
        component.on('disconnect', () => {
            this.intersectionObserver?.unobserve(component.element);
        });
    }
    getObserver() {
        if (!this.intersectionObserver) {
            this.intersectionObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.dispatchEvent(new CustomEvent('live:appear'));
                        observer.unobserve(entry.target);
                    }
                });
            });
        }
        return this.intersectionObserver;
    }
}

class LiveControllerDefault extends Controller {
    constructor() {
        super(...arguments);
        this.pendingActionTriggerModelElement = null;
        this.elementEventListeners = [
            { event: 'input', callback: (event) => this.handleInputEvent(event) },
            { event: 'change', callback: (event) => this.handleChangeEvent(event) },
        ];
        this.pendingFiles = {};
    }
    initialize() {
        this.mutationObserver = new MutationObserver(this.onMutations.bind(this));
        this.createComponent();
    }
    connect() {
        this.connectComponent();
        this.mutationObserver.observe(this.element, {
            attributes: true,
        });
    }
    disconnect() {
        this.disconnectComponent();
        this.mutationObserver.disconnect();
    }
    update(event) {
        if (event.type === 'input' || event.type === 'change') {
            throw new Error(`Since LiveComponents 2.3, you no longer need data-action="live#update" on form elements. Found on element: ${getElementAsTagText(event.currentTarget)}`);
        }
        this.updateModelFromElementEvent(event.currentTarget, null);
    }
    action(event) {
        const params = event.params;
        if (!params.action) {
            throw new Error(`No action name provided on element: ${getElementAsTagText(event.currentTarget)}. Did you forget to add the "data-live-action-param" attribute?`);
        }
        const rawAction = params.action;
        const actionArgs = { ...params };
        delete actionArgs.action;
        const directives = parseDirectives(rawAction);
        let debounce = false;
        directives.forEach((directive) => {
            let pendingFiles = {};
            const validModifiers = new Map();
            validModifiers.set('stop', () => {
                event.stopPropagation();
            });
            validModifiers.set('self', () => {
                if (event.target !== event.currentTarget) {
                    return;
                }
            });
            validModifiers.set('debounce', (modifier) => {
                debounce = modifier.value ? Number.parseInt(modifier.value) : true;
            });
            validModifiers.set('files', (modifier) => {
                if (!modifier.value) {
                    pendingFiles = this.pendingFiles;
                }
                else if (this.pendingFiles[modifier.value]) {
                    pendingFiles[modifier.value] = this.pendingFiles[modifier.value];
                }
            });
            directive.modifiers.forEach((modifier) => {
                if (validModifiers.has(modifier.name)) {
                    const callable = validModifiers.get(modifier.name) ?? (() => { });
                    callable(modifier);
                    return;
                }
                console.warn(`Unknown modifier ${modifier.name} in action "${rawAction}". Available modifiers are: ${Array.from(validModifiers.keys()).join(', ')}.`);
            });
            for (const [key, input] of Object.entries(pendingFiles)) {
                if (input.files) {
                    this.component.files(key, input);
                }
                delete this.pendingFiles[key];
            }
            this.component.action(directive.action, actionArgs, debounce);
            if (getModelDirectiveFromElement(event.currentTarget, false)) {
                this.pendingActionTriggerModelElement = event.currentTarget;
            }
        });
    }
    $render() {
        return this.component.render();
    }
    emit(event) {
        this.getEmitDirectives(event).forEach(({ name, data, nameMatch }) => {
            this.component.emit(name, data, nameMatch);
        });
    }
    emitUp(event) {
        this.getEmitDirectives(event).forEach(({ name, data, nameMatch }) => {
            this.component.emitUp(name, data, nameMatch);
        });
    }
    emitSelf(event) {
        this.getEmitDirectives(event).forEach(({ name, data }) => {
            this.component.emitSelf(name, data);
        });
    }
    $updateModel(model, value, shouldRender = true, debounce = true) {
        return this.component.set(model, value, shouldRender, debounce);
    }
    propsUpdatedFromParentValueChanged() {
        this.component._updateFromParentProps(this.propsUpdatedFromParentValue);
    }
    fingerprintValueChanged() {
        this.component.fingerprint = this.fingerprintValue;
    }
    getEmitDirectives(event) {
        const params = event.params;
        if (!params.event) {
            throw new Error(`No event name provided on element: ${getElementAsTagText(event.currentTarget)}. Did you forget to add the "data-live-event-param" attribute?`);
        }
        const eventInfo = params.event;
        const eventArgs = { ...params };
        delete eventArgs.event;
        const directives = parseDirectives(eventInfo);
        const emits = [];
        directives.forEach((directive) => {
            let nameMatch = null;
            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'name':
                        nameMatch = modifier.value;
                        break;
                    default:
                        throw new Error(`Unknown modifier ${modifier.name} in event "${eventInfo}".`);
                }
            });
            emits.push({
                name: directive.action,
                data: eventArgs,
                nameMatch,
            });
        });
        return emits;
    }
    createComponent() {
        const id = this.element.id || null;
        this.component = new Component(this.element, this.nameValue, this.propsValue, this.listenersValue, id, LiveControllerDefault.backendFactory(this), new StimulusElementDriver(this));
        this.proxiedComponent = proxifyComponent(this.component);
        this.element.__component = this.proxiedComponent;
        if (this.hasDebounceValue) {
            this.component.defaultDebounce = this.debounceValue;
        }
        const plugins = [
            new LoadingPlugin(),
            new LazyPlugin(),
            new ValidatedFieldsPlugin(),
            new PageUnloadingPlugin(),
            new PollingPlugin(),
            new SetValueOntoModelFieldsPlugin(),
            new QueryStringPlugin(this.queryMappingValue),
            new ChildComponentPlugin(this.component),
        ];
        plugins.forEach((plugin) => {
            this.component.addPlugin(plugin);
        });
    }
    connectComponent() {
        this.component.connect();
        this.mutationObserver.observe(this.element, {
            attributes: true,
        });
        this.elementEventListeners.forEach(({ event, callback }) => {
            this.component.element.addEventListener(event, callback);
        });
        this.dispatchEvent('connect');
    }
    disconnectComponent() {
        this.component.disconnect();
        this.elementEventListeners.forEach(({ event, callback }) => {
            this.component.element.removeEventListener(event, callback);
        });
        this.dispatchEvent('disconnect');
    }
    handleInputEvent(event) {
        const target = event.target;
        if (!target) {
            return;
        }
        this.updateModelFromElementEvent(target, 'input');
    }
    handleChangeEvent(event) {
        const target = event.target;
        if (!target) {
            return;
        }
        this.updateModelFromElementEvent(target, 'change');
    }
    updateModelFromElementEvent(element, eventName) {
        if (!elementBelongsToThisComponent(element, this.component)) {
            return;
        }
        if (!(element instanceof HTMLElement)) {
            throw new Error('Could not update model for non HTMLElement');
        }
        if (element instanceof HTMLInputElement && element.type === 'file') {
            const key = element.name;
            if (element.files?.length) {
                this.pendingFiles[key] = element;
            }
            else if (this.pendingFiles[key]) {
                delete this.pendingFiles[key];
            }
        }
        const modelDirective = getModelDirectiveFromElement(element, false);
        if (!modelDirective) {
            return;
        }
        const modelBinding = getModelBinding(modelDirective);
        if (!modelBinding.targetEventName) {
            modelBinding.targetEventName = 'input';
        }
        if (this.pendingActionTriggerModelElement === element) {
            modelBinding.shouldRender = false;
        }
        if (eventName === 'change' && modelBinding.targetEventName === 'input') {
            modelBinding.targetEventName = 'change';
        }
        if (eventName && modelBinding.targetEventName !== eventName) {
            return;
        }
        if (false === modelBinding.debounce) {
            if (modelBinding.targetEventName === 'input') {
                modelBinding.debounce = true;
            }
            else {
                modelBinding.debounce = 0;
            }
        }
        const finalValue = getValueFromElement(element, this.component.valueStore);
        this.component.set(modelBinding.modelName, finalValue, modelBinding.shouldRender, modelBinding.debounce);
    }
    dispatchEvent(name, detail = {}, canBubble = true, cancelable = false) {
        detail.controller = this;
        detail.component = this.proxiedComponent;
        this.dispatch(name, { detail, prefix: 'live', cancelable, bubbles: canBubble });
    }
    onMutations(mutations) {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' &&
                mutation.attributeName === 'id' &&
                this.element.id !== this.component.id) {
                this.disconnectComponent();
                this.createComponent();
                this.connectComponent();
            }
        });
    }
}
LiveControllerDefault.values = {
    name: String,
    url: String,
    props: { type: Object, default: {} },
    propsUpdatedFromParent: { type: Object, default: {} },
    csrf: String,
    listeners: { type: Array, default: [] },
    eventsToEmit: { type: Array, default: [] },
    eventsToDispatch: { type: Array, default: [] },
    debounce: { type: Number, default: 150 },
    fingerprint: { type: String, default: '' },
    requestMethod: { type: String, default: 'post' },
    queryMapping: { type: Object, default: {} },
};
LiveControllerDefault.backendFactory = (controller) => new Backend(controller.urlValue, controller.requestMethodValue, controller.csrfValue);

export { Component, LiveControllerDefault as default, getComponent };
