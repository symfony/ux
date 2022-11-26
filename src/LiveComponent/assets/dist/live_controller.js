import { Controller } from '@hotwired/stimulus';

function parseDirectives(content) {
    const directives = [];
    if (!content) {
        return directives;
    }
    let currentActionName = '';
    let currentArgumentName = '';
    let currentArgumentValue = '';
    let currentArguments = [];
    let currentNamedArguments = {};
    let currentModifiers = [];
    let state = 'action';
    const getLastActionName = function () {
        if (currentActionName) {
            return currentActionName;
        }
        if (directives.length === 0) {
            throw new Error('Could not find any directives');
        }
        return directives[directives.length - 1].action;
    };
    const pushInstruction = function () {
        directives.push({
            action: currentActionName,
            args: currentArguments,
            named: currentNamedArguments,
            modifiers: currentModifiers,
            getString: () => {
                return content;
            }
        });
        currentActionName = '';
        currentArgumentName = '';
        currentArgumentValue = '';
        currentArguments = [];
        currentNamedArguments = {};
        currentModifiers = [];
        state = 'action';
    };
    const pushArgument = function () {
        const mixedArgTypesError = () => {
            throw new Error(`Normal and named arguments cannot be mixed inside "${currentActionName}()"`);
        };
        if (currentArgumentName) {
            if (currentArguments.length > 0) {
                mixedArgTypesError();
            }
            currentNamedArguments[currentArgumentName.trim()] = currentArgumentValue;
        }
        else {
            if (Object.keys(currentNamedArguments).length > 0) {
                mixedArgTypesError();
            }
            currentArguments.push(currentArgumentValue.trim());
        }
        currentArgumentName = '';
        currentArgumentValue = '';
    };
    const pushModifier = function () {
        if (currentArguments.length > 1) {
            throw new Error(`The modifier "${currentActionName}()" does not support multiple arguments.`);
        }
        if (Object.keys(currentNamedArguments).length > 0) {
            throw new Error(`The modifier "${currentActionName}()" does not support named arguments.`);
        }
        currentModifiers.push({
            name: currentActionName,
            value: currentArguments.length > 0 ? currentArguments[0] : null,
        });
        currentActionName = '';
        currentArgumentName = '';
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
                if (char === '=') {
                    currentArgumentName = currentArgumentValue;
                    currentArgumentValue = '';
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
        finalParts.push(...part.split(' '));
    });
    return finalParts;
}
function normalizeModelName(model) {
    return model
        .replace(/\[]$/, '')
        .split('[')
        .map(function (s) {
        return s.replace(']', '');
    })
        .join('.');
}

function getValueFromElement(element, valueStore) {
    if (element instanceof HTMLInputElement) {
        if (element.type === 'checkbox') {
            const modelNameData = getModelDirectiveFromElement(element);
            if (modelNameData === null) {
                return null;
            }
            const modelValue = valueStore.get(modelNameData.action);
            if (Array.isArray(modelValue)) {
                return getMultipleCheckboxValue(element, modelValue);
            }
            return element.checked ? inputValue(element) : null;
        }
        return inputValue(element);
    }
    if (element instanceof HTMLSelectElement) {
        if (element.multiple) {
            return Array.from(element.selectedOptions).map(el => el.value);
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
            element.checked = element.value == value;
            return;
        }
        if (element.type === 'checkbox') {
            if (Array.isArray(value)) {
                let valueFound = false;
                value.forEach(val => {
                    if (val == element.value) {
                        valueFound = true;
                    }
                });
                element.checked = valueFound;
            }
            else {
                element.checked = element.value == value;
            }
            return;
        }
    }
    if (element instanceof HTMLSelectElement) {
        const arrayWrappedValue = [].concat(value).map(value => {
            return value + '';
        });
        Array.from(element.options).forEach(option => {
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
        if (directive.args.length > 0 || directive.named.length > 0) {
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
        if (formElement && ('model' in formElement.dataset)) {
            const directives = parseDirectives(formElement.dataset.model || '*');
            const directive = directives[0];
            if (directive.args.length > 0 || directive.named.length > 0) {
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
    component.getChildren().forEach((childComponent) => {
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
    const child = template.content.firstChild;
    if (!child) {
        throw new Error('Child not found');
    }
    if (!(child instanceof HTMLElement)) {
        throw new Error(`Created element is not an Element from HTML: ${html.trim()}`);
    }
    return child;
}
function cloneElementWithNewTagName(element, newTag) {
    const originalTag = element.tagName;
    const startRX = new RegExp('^<' + originalTag, 'i');
    const endRX = new RegExp(originalTag + '>$', 'i');
    const startSubst = '<' + newTag;
    const endSubst = newTag + '>';
    const newHTML = element.outerHTML
        .replace(startRX, startSubst)
        .replace(endRX, endSubst);
    return htmlToElement(newHTML);
}
function getElementAsTagText(element) {
    return element.innerHTML ? element.outerHTML.slice(0, element.outerHTML.indexOf(element.innerHTML)) : element.outerHTML;
}
const getMultipleCheckboxValue = function (element, currentValues) {
    const value = inputValue(element);
    const index = currentValues.indexOf(value);
    if (element.checked) {
        if (index === -1) {
            currentValues.push(value);
        }
        return currentValues;
    }
    if (index > -1) {
        currentValues.splice(index, 1);
    }
    return currentValues;
};
const inputValue = function (element) {
    return element.dataset.value ? element.dataset.value : element.value;
};

function getDeepData(data, propertyPath) {
    const { currentLevelData, finalKey } = parseDeepData(data, propertyPath);
    if (currentLevelData === undefined) {
        return undefined;
    }
    return currentLevelData[finalKey];
}
const parseDeepData = function (data, propertyPath) {
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
        parts
    };
};
function setDeepData(data, propertyPath, value) {
    const { currentLevelData, finalData, finalKey, parts } = parseDeepData(data, propertyPath);
    if (typeof currentLevelData !== 'object') {
        const lastPart = parts.pop();
        if (typeof currentLevelData === 'undefined') {
            throw new Error(`Cannot set data-model="${propertyPath}". The parent "${parts.join('.')}" data does not exist. Did you forget to expose "${parts[0]}" as a LiveProp?`);
        }
        throw new Error(`Cannot set data-model="${propertyPath}". The parent "${parts.join('.')}" data does not appear to be an object (it's "${currentLevelData}"). Did you forget to add exposed={"${lastPart}"} to its LiveProp?`);
    }
    if (currentLevelData[finalKey] === undefined) {
        const lastPart = parts.pop();
        if (parts.length > 0) {
            throw new Error(`The model name ${propertyPath} was never initialized. Did you forget to add exposed={"${lastPart}"} to its LiveProp?`);
        }
        else {
            throw new Error(`The model name "${propertyPath}" was never initialized. Did you forget to expose "${lastPart}" as a LiveProp? Available models values are: ${Object.keys(data).length > 0 ? Object.keys(data).join(', ') : '(none)'}`);
        }
    }
    currentLevelData[finalKey] = value;
    return finalData;
}

class ValueStore {
    constructor(props, data) {
        this.updatedModels = [];
        this.props = {};
        this.data = {};
        this.props = props;
        this.data = data;
    }
    get(name) {
        const normalizedName = normalizeModelName(name);
        const result = getDeepData(this.data, normalizedName);
        if (result !== undefined) {
            return result;
        }
        return getDeepData(this.props, normalizedName);
    }
    has(name) {
        return this.get(name) !== undefined;
    }
    set(name, value) {
        const normalizedName = normalizeModelName(name);
        const currentValue = this.get(name);
        if (currentValue !== value && !this.updatedModels.includes(normalizedName)) {
            this.updatedModels.push(normalizedName);
        }
        this.data = setDeepData(this.data, normalizedName, value);
        return currentValue !== value;
    }
    all() {
        return Object.assign(Object.assign({}, this.props), this.data);
    }
    reinitializeData(data) {
        this.updatedModels = [];
        this.data = data;
    }
    reinitializeProps(props) {
        if (JSON.stringify(props) == JSON.stringify(this.props)) {
            return false;
        }
        this.props = props;
        return true;
    }
}

var DOCUMENT_FRAGMENT_NODE = 11;

function morphAttrs(fromNode, toNode) {
    var toNodeAttrs = toNode.attributes;
    var attr;
    var attrName;
    var attrNamespaceURI;
    var attrValue;
    var fromValue;

    // document-fragments dont have attributes so lets not do anything
    if (toNode.nodeType === DOCUMENT_FRAGMENT_NODE || fromNode.nodeType === DOCUMENT_FRAGMENT_NODE) {
      return;
    }

    // update attributes on original DOM element
    for (var i = toNodeAttrs.length - 1; i >= 0; i--) {
        attr = toNodeAttrs[i];
        attrName = attr.name;
        attrNamespaceURI = attr.namespaceURI;
        attrValue = attr.value;

        if (attrNamespaceURI) {
            attrName = attr.localName || attrName;
            fromValue = fromNode.getAttributeNS(attrNamespaceURI, attrName);

            if (fromValue !== attrValue) {
                if (attr.prefix === 'xmlns'){
                    attrName = attr.name; // It's not allowed to set an attribute with the XMLNS namespace without specifying the `xmlns` prefix
                }
                fromNode.setAttributeNS(attrNamespaceURI, attrName, attrValue);
            }
        } else {
            fromValue = fromNode.getAttribute(attrName);

            if (fromValue !== attrValue) {
                fromNode.setAttribute(attrName, attrValue);
            }
        }
    }

    // Remove any extra attributes found on the original DOM element that
    // weren't found on the target element.
    var fromNodeAttrs = fromNode.attributes;

    for (var d = fromNodeAttrs.length - 1; d >= 0; d--) {
        attr = fromNodeAttrs[d];
        attrName = attr.name;
        attrNamespaceURI = attr.namespaceURI;

        if (attrNamespaceURI) {
            attrName = attr.localName || attrName;

            if (!toNode.hasAttributeNS(attrNamespaceURI, attrName)) {
                fromNode.removeAttributeNS(attrNamespaceURI, attrName);
            }
        } else {
            if (!toNode.hasAttribute(attrName)) {
                fromNode.removeAttribute(attrName);
            }
        }
    }
}

var range; // Create a range object for efficently rendering strings to elements.
var NS_XHTML = 'http://www.w3.org/1999/xhtml';

var doc = typeof document === 'undefined' ? undefined : document;
var HAS_TEMPLATE_SUPPORT = !!doc && 'content' in doc.createElement('template');
var HAS_RANGE_SUPPORT = !!doc && doc.createRange && 'createContextualFragment' in doc.createRange();

function createFragmentFromTemplate(str) {
    var template = doc.createElement('template');
    template.innerHTML = str;
    return template.content.childNodes[0];
}

function createFragmentFromRange(str) {
    if (!range) {
        range = doc.createRange();
        range.selectNode(doc.body);
    }

    var fragment = range.createContextualFragment(str);
    return fragment.childNodes[0];
}

function createFragmentFromWrap(str) {
    var fragment = doc.createElement('body');
    fragment.innerHTML = str;
    return fragment.childNodes[0];
}

/**
 * This is about the same
 * var html = new DOMParser().parseFromString(str, 'text/html');
 * return html.body.firstChild;
 *
 * @method toElement
 * @param {String} str
 */
function toElement(str) {
    str = str.trim();
    if (HAS_TEMPLATE_SUPPORT) {
      // avoid restrictions on content for things like `<tr><th>Hi</th></tr>` which
      // createContextualFragment doesn't support
      // <template> support not available in IE
      return createFragmentFromTemplate(str);
    } else if (HAS_RANGE_SUPPORT) {
      return createFragmentFromRange(str);
    }

    return createFragmentFromWrap(str);
}

/**
 * Returns true if two node's names are the same.
 *
 * NOTE: We don't bother checking `namespaceURI` because you will never find two HTML elements with the same
 *       nodeName and different namespace URIs.
 *
 * @param {Element} a
 * @param {Element} b The target element
 * @return {boolean}
 */
function compareNodeNames(fromEl, toEl) {
    var fromNodeName = fromEl.nodeName;
    var toNodeName = toEl.nodeName;
    var fromCodeStart, toCodeStart;

    if (fromNodeName === toNodeName) {
        return true;
    }

    fromCodeStart = fromNodeName.charCodeAt(0);
    toCodeStart = toNodeName.charCodeAt(0);

    // If the target element is a virtual DOM node or SVG node then we may
    // need to normalize the tag name before comparing. Normal HTML elements that are
    // in the "http://www.w3.org/1999/xhtml"
    // are converted to upper case
    if (fromCodeStart <= 90 && toCodeStart >= 97) { // from is upper and to is lower
        return fromNodeName === toNodeName.toUpperCase();
    } else if (toCodeStart <= 90 && fromCodeStart >= 97) { // to is upper and from is lower
        return toNodeName === fromNodeName.toUpperCase();
    } else {
        return false;
    }
}

/**
 * Create an element, optionally with a known namespace URI.
 *
 * @param {string} name the element name, e.g. 'div' or 'svg'
 * @param {string} [namespaceURI] the element's namespace URI, i.e. the value of
 * its `xmlns` attribute or its inferred namespace.
 *
 * @return {Element}
 */
function createElementNS(name, namespaceURI) {
    return !namespaceURI || namespaceURI === NS_XHTML ?
        doc.createElement(name) :
        doc.createElementNS(namespaceURI, name);
}

/**
 * Copies the children of one DOM element to another DOM element
 */
function moveChildren(fromEl, toEl) {
    var curChild = fromEl.firstChild;
    while (curChild) {
        var nextChild = curChild.nextSibling;
        toEl.appendChild(curChild);
        curChild = nextChild;
    }
    return toEl;
}

function syncBooleanAttrProp(fromEl, toEl, name) {
    if (fromEl[name] !== toEl[name]) {
        fromEl[name] = toEl[name];
        if (fromEl[name]) {
            fromEl.setAttribute(name, '');
        } else {
            fromEl.removeAttribute(name);
        }
    }
}

var specialElHandlers = {
    OPTION: function(fromEl, toEl) {
        var parentNode = fromEl.parentNode;
        if (parentNode) {
            var parentName = parentNode.nodeName.toUpperCase();
            if (parentName === 'OPTGROUP') {
                parentNode = parentNode.parentNode;
                parentName = parentNode && parentNode.nodeName.toUpperCase();
            }
            if (parentName === 'SELECT' && !parentNode.hasAttribute('multiple')) {
                if (fromEl.hasAttribute('selected') && !toEl.selected) {
                    // Workaround for MS Edge bug where the 'selected' attribute can only be
                    // removed if set to a non-empty value:
                    // https://developer.microsoft.com/en-us/microsoft-edge/platform/issues/12087679/
                    fromEl.setAttribute('selected', 'selected');
                    fromEl.removeAttribute('selected');
                }
                // We have to reset select element's selectedIndex to -1, otherwise setting
                // fromEl.selected using the syncBooleanAttrProp below has no effect.
                // The correct selectedIndex will be set in the SELECT special handler below.
                parentNode.selectedIndex = -1;
            }
        }
        syncBooleanAttrProp(fromEl, toEl, 'selected');
    },
    /**
     * The "value" attribute is special for the <input> element since it sets
     * the initial value. Changing the "value" attribute without changing the
     * "value" property will have no effect since it is only used to the set the
     * initial value.  Similar for the "checked" attribute, and "disabled".
     */
    INPUT: function(fromEl, toEl) {
        syncBooleanAttrProp(fromEl, toEl, 'checked');
        syncBooleanAttrProp(fromEl, toEl, 'disabled');

        if (fromEl.value !== toEl.value) {
            fromEl.value = toEl.value;
        }

        if (!toEl.hasAttribute('value')) {
            fromEl.removeAttribute('value');
        }
    },

    TEXTAREA: function(fromEl, toEl) {
        var newValue = toEl.value;
        if (fromEl.value !== newValue) {
            fromEl.value = newValue;
        }

        var firstChild = fromEl.firstChild;
        if (firstChild) {
            // Needed for IE. Apparently IE sets the placeholder as the
            // node value and vise versa. This ignores an empty update.
            var oldValue = firstChild.nodeValue;

            if (oldValue == newValue || (!newValue && oldValue == fromEl.placeholder)) {
                return;
            }

            firstChild.nodeValue = newValue;
        }
    },
    SELECT: function(fromEl, toEl) {
        if (!toEl.hasAttribute('multiple')) {
            var selectedIndex = -1;
            var i = 0;
            // We have to loop through children of fromEl, not toEl since nodes can be moved
            // from toEl to fromEl directly when morphing.
            // At the time this special handler is invoked, all children have already been morphed
            // and appended to / removed from fromEl, so using fromEl here is safe and correct.
            var curChild = fromEl.firstChild;
            var optgroup;
            var nodeName;
            while(curChild) {
                nodeName = curChild.nodeName && curChild.nodeName.toUpperCase();
                if (nodeName === 'OPTGROUP') {
                    optgroup = curChild;
                    curChild = optgroup.firstChild;
                } else {
                    if (nodeName === 'OPTION') {
                        if (curChild.hasAttribute('selected')) {
                            selectedIndex = i;
                            break;
                        }
                        i++;
                    }
                    curChild = curChild.nextSibling;
                    if (!curChild && optgroup) {
                        curChild = optgroup.nextSibling;
                        optgroup = null;
                    }
                }
            }

            fromEl.selectedIndex = selectedIndex;
        }
    }
};

var ELEMENT_NODE = 1;
var DOCUMENT_FRAGMENT_NODE$1 = 11;
var TEXT_NODE = 3;
var COMMENT_NODE = 8;

function noop() {}

function defaultGetNodeKey(node) {
  if (node) {
      return (node.getAttribute && node.getAttribute('id')) || node.id;
  }
}

function morphdomFactory(morphAttrs) {

    return function morphdom(fromNode, toNode, options) {
        if (!options) {
            options = {};
        }

        if (typeof toNode === 'string') {
            if (fromNode.nodeName === '#document' || fromNode.nodeName === 'HTML' || fromNode.nodeName === 'BODY') {
                var toNodeHtml = toNode;
                toNode = doc.createElement('html');
                toNode.innerHTML = toNodeHtml;
            } else {
                toNode = toElement(toNode);
            }
        }

        var getNodeKey = options.getNodeKey || defaultGetNodeKey;
        var onBeforeNodeAdded = options.onBeforeNodeAdded || noop;
        var onNodeAdded = options.onNodeAdded || noop;
        var onBeforeElUpdated = options.onBeforeElUpdated || noop;
        var onElUpdated = options.onElUpdated || noop;
        var onBeforeNodeDiscarded = options.onBeforeNodeDiscarded || noop;
        var onNodeDiscarded = options.onNodeDiscarded || noop;
        var onBeforeElChildrenUpdated = options.onBeforeElChildrenUpdated || noop;
        var childrenOnly = options.childrenOnly === true;

        // This object is used as a lookup to quickly find all keyed elements in the original DOM tree.
        var fromNodesLookup = Object.create(null);
        var keyedRemovalList = [];

        function addKeyedRemoval(key) {
            keyedRemovalList.push(key);
        }

        function walkDiscardedChildNodes(node, skipKeyedNodes) {
            if (node.nodeType === ELEMENT_NODE) {
                var curChild = node.firstChild;
                while (curChild) {

                    var key = undefined;

                    if (skipKeyedNodes && (key = getNodeKey(curChild))) {
                        // If we are skipping keyed nodes then we add the key
                        // to a list so that it can be handled at the very end.
                        addKeyedRemoval(key);
                    } else {
                        // Only report the node as discarded if it is not keyed. We do this because
                        // at the end we loop through all keyed elements that were unmatched
                        // and then discard them in one final pass.
                        onNodeDiscarded(curChild);
                        if (curChild.firstChild) {
                            walkDiscardedChildNodes(curChild, skipKeyedNodes);
                        }
                    }

                    curChild = curChild.nextSibling;
                }
            }
        }

        /**
         * Removes a DOM node out of the original DOM
         *
         * @param  {Node} node The node to remove
         * @param  {Node} parentNode The nodes parent
         * @param  {Boolean} skipKeyedNodes If true then elements with keys will be skipped and not discarded.
         * @return {undefined}
         */
        function removeNode(node, parentNode, skipKeyedNodes) {
            if (onBeforeNodeDiscarded(node) === false) {
                return;
            }

            if (parentNode) {
                parentNode.removeChild(node);
            }

            onNodeDiscarded(node);
            walkDiscardedChildNodes(node, skipKeyedNodes);
        }

        // // TreeWalker implementation is no faster, but keeping this around in case this changes in the future
        // function indexTree(root) {
        //     var treeWalker = document.createTreeWalker(
        //         root,
        //         NodeFilter.SHOW_ELEMENT);
        //
        //     var el;
        //     while((el = treeWalker.nextNode())) {
        //         var key = getNodeKey(el);
        //         if (key) {
        //             fromNodesLookup[key] = el;
        //         }
        //     }
        // }

        // // NodeIterator implementation is no faster, but keeping this around in case this changes in the future
        //
        // function indexTree(node) {
        //     var nodeIterator = document.createNodeIterator(node, NodeFilter.SHOW_ELEMENT);
        //     var el;
        //     while((el = nodeIterator.nextNode())) {
        //         var key = getNodeKey(el);
        //         if (key) {
        //             fromNodesLookup[key] = el;
        //         }
        //     }
        // }

        function indexTree(node) {
            if (node.nodeType === ELEMENT_NODE || node.nodeType === DOCUMENT_FRAGMENT_NODE$1) {
                var curChild = node.firstChild;
                while (curChild) {
                    var key = getNodeKey(curChild);
                    if (key) {
                        fromNodesLookup[key] = curChild;
                    }

                    // Walk recursively
                    indexTree(curChild);

                    curChild = curChild.nextSibling;
                }
            }
        }

        indexTree(fromNode);

        function handleNodeAdded(el) {
            onNodeAdded(el);

            var curChild = el.firstChild;
            while (curChild) {
                var nextSibling = curChild.nextSibling;

                var key = getNodeKey(curChild);
                if (key) {
                    var unmatchedFromEl = fromNodesLookup[key];
                    // if we find a duplicate #id node in cache, replace `el` with cache value
                    // and morph it to the child node.
                    if (unmatchedFromEl && compareNodeNames(curChild, unmatchedFromEl)) {
                        curChild.parentNode.replaceChild(unmatchedFromEl, curChild);
                        morphEl(unmatchedFromEl, curChild);
                    } else {
                      handleNodeAdded(curChild);
                    }
                } else {
                  // recursively call for curChild and it's children to see if we find something in
                  // fromNodesLookup
                  handleNodeAdded(curChild);
                }

                curChild = nextSibling;
            }
        }

        function cleanupFromEl(fromEl, curFromNodeChild, curFromNodeKey) {
            // We have processed all of the "to nodes". If curFromNodeChild is
            // non-null then we still have some from nodes left over that need
            // to be removed
            while (curFromNodeChild) {
                var fromNextSibling = curFromNodeChild.nextSibling;
                if ((curFromNodeKey = getNodeKey(curFromNodeChild))) {
                    // Since the node is keyed it might be matched up later so we defer
                    // the actual removal to later
                    addKeyedRemoval(curFromNodeKey);
                } else {
                    // NOTE: we skip nested keyed nodes from being removed since there is
                    //       still a chance they will be matched up later
                    removeNode(curFromNodeChild, fromEl, true /* skip keyed nodes */);
                }
                curFromNodeChild = fromNextSibling;
            }
        }

        function morphEl(fromEl, toEl, childrenOnly) {
            var toElKey = getNodeKey(toEl);

            if (toElKey) {
                // If an element with an ID is being morphed then it will be in the final
                // DOM so clear it out of the saved elements collection
                delete fromNodesLookup[toElKey];
            }

            if (!childrenOnly) {
                // optional
                if (onBeforeElUpdated(fromEl, toEl) === false) {
                    return;
                }

                // update attributes on original DOM element first
                morphAttrs(fromEl, toEl);
                // optional
                onElUpdated(fromEl);

                if (onBeforeElChildrenUpdated(fromEl, toEl) === false) {
                    return;
                }
            }

            if (fromEl.nodeName !== 'TEXTAREA') {
              morphChildren(fromEl, toEl);
            } else {
              specialElHandlers.TEXTAREA(fromEl, toEl);
            }
        }

        function morphChildren(fromEl, toEl) {
            var curToNodeChild = toEl.firstChild;
            var curFromNodeChild = fromEl.firstChild;
            var curToNodeKey;
            var curFromNodeKey;

            var fromNextSibling;
            var toNextSibling;
            var matchingFromEl;

            // walk the children
            outer: while (curToNodeChild) {
                toNextSibling = curToNodeChild.nextSibling;
                curToNodeKey = getNodeKey(curToNodeChild);

                // walk the fromNode children all the way through
                while (curFromNodeChild) {
                    fromNextSibling = curFromNodeChild.nextSibling;

                    if (curToNodeChild.isSameNode && curToNodeChild.isSameNode(curFromNodeChild)) {
                        curToNodeChild = toNextSibling;
                        curFromNodeChild = fromNextSibling;
                        continue outer;
                    }

                    curFromNodeKey = getNodeKey(curFromNodeChild);

                    var curFromNodeType = curFromNodeChild.nodeType;

                    // this means if the curFromNodeChild doesnt have a match with the curToNodeChild
                    var isCompatible = undefined;

                    if (curFromNodeType === curToNodeChild.nodeType) {
                        if (curFromNodeType === ELEMENT_NODE) {
                            // Both nodes being compared are Element nodes

                            if (curToNodeKey) {
                                // The target node has a key so we want to match it up with the correct element
                                // in the original DOM tree
                                if (curToNodeKey !== curFromNodeKey) {
                                    // The current element in the original DOM tree does not have a matching key so
                                    // let's check our lookup to see if there is a matching element in the original
                                    // DOM tree
                                    if ((matchingFromEl = fromNodesLookup[curToNodeKey])) {
                                        if (fromNextSibling === matchingFromEl) {
                                            // Special case for single element removals. To avoid removing the original
                                            // DOM node out of the tree (since that can break CSS transitions, etc.),
                                            // we will instead discard the current node and wait until the next
                                            // iteration to properly match up the keyed target element with its matching
                                            // element in the original tree
                                            isCompatible = false;
                                        } else {
                                            // We found a matching keyed element somewhere in the original DOM tree.
                                            // Let's move the original DOM node into the current position and morph
                                            // it.

                                            // NOTE: We use insertBefore instead of replaceChild because we want to go through
                                            // the `removeNode()` function for the node that is being discarded so that
                                            // all lifecycle hooks are correctly invoked
                                            fromEl.insertBefore(matchingFromEl, curFromNodeChild);

                                            // fromNextSibling = curFromNodeChild.nextSibling;

                                            if (curFromNodeKey) {
                                                // Since the node is keyed it might be matched up later so we defer
                                                // the actual removal to later
                                                addKeyedRemoval(curFromNodeKey);
                                            } else {
                                                // NOTE: we skip nested keyed nodes from being removed since there is
                                                //       still a chance they will be matched up later
                                                removeNode(curFromNodeChild, fromEl, true /* skip keyed nodes */);
                                            }

                                            curFromNodeChild = matchingFromEl;
                                        }
                                    } else {
                                        // The nodes are not compatible since the "to" node has a key and there
                                        // is no matching keyed node in the source tree
                                        isCompatible = false;
                                    }
                                }
                            } else if (curFromNodeKey) {
                                // The original has a key
                                isCompatible = false;
                            }

                            isCompatible = isCompatible !== false && compareNodeNames(curFromNodeChild, curToNodeChild);
                            if (isCompatible) {
                                // We found compatible DOM elements so transform
                                // the current "from" node to match the current
                                // target DOM node.
                                // MORPH
                                morphEl(curFromNodeChild, curToNodeChild);
                            }

                        } else if (curFromNodeType === TEXT_NODE || curFromNodeType == COMMENT_NODE) {
                            // Both nodes being compared are Text or Comment nodes
                            isCompatible = true;
                            // Simply update nodeValue on the original node to
                            // change the text value
                            if (curFromNodeChild.nodeValue !== curToNodeChild.nodeValue) {
                                curFromNodeChild.nodeValue = curToNodeChild.nodeValue;
                            }

                        }
                    }

                    if (isCompatible) {
                        // Advance both the "to" child and the "from" child since we found a match
                        // Nothing else to do as we already recursively called morphChildren above
                        curToNodeChild = toNextSibling;
                        curFromNodeChild = fromNextSibling;
                        continue outer;
                    }

                    // No compatible match so remove the old node from the DOM and continue trying to find a
                    // match in the original DOM. However, we only do this if the from node is not keyed
                    // since it is possible that a keyed node might match up with a node somewhere else in the
                    // target tree and we don't want to discard it just yet since it still might find a
                    // home in the final DOM tree. After everything is done we will remove any keyed nodes
                    // that didn't find a home
                    if (curFromNodeKey) {
                        // Since the node is keyed it might be matched up later so we defer
                        // the actual removal to later
                        addKeyedRemoval(curFromNodeKey);
                    } else {
                        // NOTE: we skip nested keyed nodes from being removed since there is
                        //       still a chance they will be matched up later
                        removeNode(curFromNodeChild, fromEl, true /* skip keyed nodes */);
                    }

                    curFromNodeChild = fromNextSibling;
                } // END: while(curFromNodeChild) {}

                // If we got this far then we did not find a candidate match for
                // our "to node" and we exhausted all of the children "from"
                // nodes. Therefore, we will just append the current "to" node
                // to the end
                if (curToNodeKey && (matchingFromEl = fromNodesLookup[curToNodeKey]) && compareNodeNames(matchingFromEl, curToNodeChild)) {
                    fromEl.appendChild(matchingFromEl);
                    // MORPH
                    morphEl(matchingFromEl, curToNodeChild);
                } else {
                    var onBeforeNodeAddedResult = onBeforeNodeAdded(curToNodeChild);
                    if (onBeforeNodeAddedResult !== false) {
                        if (onBeforeNodeAddedResult) {
                            curToNodeChild = onBeforeNodeAddedResult;
                        }

                        if (curToNodeChild.actualize) {
                            curToNodeChild = curToNodeChild.actualize(fromEl.ownerDocument || doc);
                        }
                        fromEl.appendChild(curToNodeChild);
                        handleNodeAdded(curToNodeChild);
                    }
                }

                curToNodeChild = toNextSibling;
                curFromNodeChild = fromNextSibling;
            }

            cleanupFromEl(fromEl, curFromNodeChild, curFromNodeKey);

            var specialElHandler = specialElHandlers[fromEl.nodeName];
            if (specialElHandler) {
                specialElHandler(fromEl, toEl);
            }
        } // END: morphChildren(...)

        var morphedNode = fromNode;
        var morphedNodeType = morphedNode.nodeType;
        var toNodeType = toNode.nodeType;

        if (!childrenOnly) {
            // Handle the case where we are given two DOM nodes that are not
            // compatible (e.g. <div> --> <span> or <div> --> TEXT)
            if (morphedNodeType === ELEMENT_NODE) {
                if (toNodeType === ELEMENT_NODE) {
                    if (!compareNodeNames(fromNode, toNode)) {
                        onNodeDiscarded(fromNode);
                        morphedNode = moveChildren(fromNode, createElementNS(toNode.nodeName, toNode.namespaceURI));
                    }
                } else {
                    // Going from an element node to a text node
                    morphedNode = toNode;
                }
            } else if (morphedNodeType === TEXT_NODE || morphedNodeType === COMMENT_NODE) { // Text or comment node
                if (toNodeType === morphedNodeType) {
                    if (morphedNode.nodeValue !== toNode.nodeValue) {
                        morphedNode.nodeValue = toNode.nodeValue;
                    }

                    return morphedNode;
                } else {
                    // Text node to something else
                    morphedNode = toNode;
                }
            }
        }

        if (morphedNode === toNode) {
            // The "to node" was not compatible with the "from node" so we had to
            // toss out the "from node" and use the "to node"
            onNodeDiscarded(fromNode);
        } else {
            if (toNode.isSameNode && toNode.isSameNode(morphedNode)) {
                return;
            }

            morphEl(morphedNode, toNode, childrenOnly);

            // We now need to loop over any keyed nodes that might need to be
            // removed. We only do the removal if we know that the keyed node
            // never found a match. When a keyed node is matched up we remove
            // it out of fromNodesLookup and we use fromNodesLookup to determine
            // if a keyed node has been matched up or not
            if (keyedRemovalList) {
                for (var i=0, len=keyedRemovalList.length; i<len; i++) {
                    var elToRemove = fromNodesLookup[keyedRemovalList[i]];
                    if (elToRemove) {
                        removeNode(elToRemove, elToRemove.parentNode, false);
                    }
                }
            }
        }

        if (!childrenOnly && morphedNode !== fromNode && fromNode.parentNode) {
            if (morphedNode.actualize) {
                morphedNode = morphedNode.actualize(fromNode.ownerDocument || doc);
            }
            // If we had to swap out the from node with a new node because the old
            // node was not compatible with the target node then we need to
            // replace the old DOM node in the original DOM tree. This is only
            // possible if the original DOM node was part of a DOM tree which
            // we know is the case if it has a parent node.
            fromNode.parentNode.replaceChild(morphedNode, fromNode);
        }

        return morphedNode;
    };
}

var morphdom = morphdomFactory(morphAttrs);

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

function executeMorphdom(rootFromElement, rootToElement, modifiedFieldElements, getElementValue, childComponents, findChildComponent, getKeyFromElement) {
    const childComponentMap = new Map();
    childComponents.forEach((childComponent) => {
        childComponentMap.set(childComponent.element, childComponent);
        if (!childComponent.id) {
            throw new Error('Child is missing id.');
        }
        const childComponentToElement = findChildComponent(childComponent.id, rootToElement);
        if (childComponentToElement && childComponentToElement.tagName !== childComponent.element.tagName) {
            const newTag = cloneElementWithNewTagName(childComponentToElement, childComponent.element.tagName);
            rootToElement.replaceChild(newTag, childComponentToElement);
        }
    });
    morphdom(rootFromElement, rootToElement, {
        getNodeKey: (node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }
            return getKeyFromElement(node);
        },
        onBeforeElUpdated: (fromEl, toEl) => {
            if (fromEl === rootFromElement) {
                return true;
            }
            if (!(fromEl instanceof HTMLElement) || !(toEl instanceof HTMLElement)) {
                return false;
            }
            const childComponent = childComponentMap.get(fromEl) || false;
            if (childComponent) {
                return childComponent.updateFromNewElement(toEl);
            }
            if (modifiedFieldElements.includes(fromEl)) {
                setValueOnElement(toEl, getElementValue(fromEl));
            }
            if (fromEl.isEqualNode(toEl)) {
                const normalizedFromEl = cloneHTMLElement(fromEl);
                normalizeAttributesForComparison(normalizedFromEl);
                const normalizedToEl = cloneHTMLElement(toEl);
                normalizeAttributesForComparison(normalizedToEl);
                if (normalizedFromEl.isEqualNode(normalizedToEl)) {
                    return false;
                }
            }
            return !fromEl.hasAttribute('data-live-ignore');
        },
        onBeforeNodeDiscarded(node) {
            if (!(node instanceof HTMLElement)) {
                return true;
            }
            return !node.hasAttribute('data-live-ignore');
        }
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
        hooks.forEach((callback) => {
            callback(...args);
        });
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

class ChildComponentWrapper {
    constructor(component, modelBindings) {
        this.component = component;
        this.modelBindings = modelBindings;
    }
}
class Component {
    constructor(element, props, data, fingerprint, id, backend, elementDriver) {
        this.defaultDebounce = 150;
        this.pendingActions = [];
        this.isRequestPending = false;
        this.requestDebounceTimeout = null;
        this.children = new Map();
        this.parent = null;
        this.element = element;
        this.backend = backend;
        this.elementDriver = elementDriver;
        this.id = id;
        this.fingerprint = fingerprint;
        this.valueStore = new ValueStore(props, data);
        this.unsyncedInputsTracker = new UnsyncedInputsTracker(this, elementDriver);
        this.hooks = new HookManager();
        this.resetPromise();
        this.onChildComponentModelUpdate = this.onChildComponentModelUpdate.bind(this);
    }
    addPlugin(plugin) {
        plugin.attachToComponent(this);
    }
    connect() {
        this.hooks.triggerHook('connect', this);
        this.unsyncedInputsTracker.activate();
    }
    disconnect() {
        this.hooks.triggerHook('disconnect', this);
        this.clearRequestDebounceTimeout();
        this.unsyncedInputsTracker.deactivate();
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
    action(name, args, debounce = false) {
        const promise = this.nextRequestPromise;
        this.pendingActions.push({
            name,
            args
        });
        this.debouncedStartRequest(debounce);
        return promise;
    }
    render() {
        const promise = this.nextRequestPromise;
        this.tryStartingRequest();
        return promise;
    }
    getUnsyncedModels() {
        return this.unsyncedInputsTracker.getUnsyncedModels();
    }
    addChild(child, modelBindings = []) {
        if (!child.id) {
            throw new Error('Children components must have an id.');
        }
        this.children.set(child.id, new ChildComponentWrapper(child, modelBindings));
        child.parent = this;
        child.on('model:set', this.onChildComponentModelUpdate);
    }
    removeChild(child) {
        if (!child.id) {
            throw new Error('Children components must have an id.');
        }
        this.children.delete(child.id);
        child.parent = null;
        child.off('model:set', this.onChildComponentModelUpdate);
    }
    getParent() {
        return this.parent;
    }
    getChildren() {
        const children = new Map();
        this.children.forEach((childComponent, id) => {
            children.set(id, childComponent.component);
        });
        return children;
    }
    updateFromNewElement(toEl) {
        const props = this.elementDriver.getComponentProps(toEl);
        if (props === null) {
            return false;
        }
        const isChanged = this.valueStore.reinitializeProps(props);
        const fingerprint = toEl.dataset.liveFingerprintValue;
        if (fingerprint !== undefined) {
            this.fingerprint = fingerprint;
        }
        if (isChanged) {
            this.render();
        }
        return false;
    }
    onChildComponentModelUpdate(modelName, value, childComponent) {
        if (!childComponent.id) {
            throw new Error('Missing id');
        }
        const childWrapper = this.children.get(childComponent.id);
        if (!childWrapper) {
            throw new Error('Missing child');
        }
        childWrapper.modelBindings.forEach((modelBinding) => {
            const childModelName = modelBinding.innerModelName || 'value';
            if (childModelName !== modelName) {
                return;
            }
            this.set(modelBinding.modelName, value, modelBinding.shouldRender, modelBinding.debounce);
        });
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
        this.backendRequest = this.backend.makeRequest(this.valueStore.all(), this.pendingActions, this.valueStore.updatedModels, this.getChildrenFingerprints());
        this.hooks.triggerHook('loading.state:started', this.element, this.backendRequest);
        this.pendingActions = [];
        this.valueStore.updatedModels = [];
        this.isRequestPending = false;
        this.backendRequest.promise.then(async (response) => {
            const backendResponse = new BackendResponse(response);
            thisPromiseResolve(backendResponse);
            const html = await backendResponse.getBody();
            const headers = backendResponse.response.headers;
            if (headers.get('Content-Type') !== 'application/vnd.live-component+html' && !headers.get('X-Live-Redirect')) {
                this.renderError(html);
                return response;
            }
            this.processRerender(html, backendResponse);
            this.backendRequest = null;
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
            if (typeof Turbo !== 'undefined') {
                Turbo.visit(backendResponse.response.headers.get('Location'));
            }
            else {
                window.location.href = backendResponse.response.headers.get('Location') || '';
            }
            return;
        }
        this.hooks.triggerHook('loading.state:finished', this.element);
        const modifiedModelValues = {};
        this.valueStore.updatedModels.forEach((modelName) => {
            modifiedModelValues[modelName] = this.valueStore.get(modelName);
        });
        const newElement = htmlToElement(html);
        this.hooks.triggerHook('loading.state:finished', newElement);
        this.valueStore.reinitializeData(this.elementDriver.getComponentData(newElement));
        executeMorphdom(this.element, newElement, this.unsyncedInputsTracker.getUnsyncedInputs(), (element) => getValueFromElement(element, this.valueStore), Array.from(this.getChildren().values()), this.elementDriver.findChildComponentElement, this.elementDriver.getKeyFromElement);
        Object.keys(modifiedModelValues).forEach((modelName) => {
            this.valueStore.set(modelName, modifiedModelValues[modelName]);
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
            modal.style.width = '100vw';
            modal.style.height = '100vh';
        }
        const iframe = document.createElement('iframe');
        iframe.style.borderRadius = '5px';
        iframe.style.width = '100%';
        iframe.style.height = '100%';
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
        modal.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeModal(modal);
            }
        });
        modal.focus();
    }
    getChildrenFingerprints() {
        const fingerprints = {};
        this.children.forEach((childComponent) => {
            const child = childComponent.component;
            if (!child.id) {
                throw new Error('missing id');
            }
            fingerprints[child.id] = child.fingerprint;
        });
        return fingerprints;
    }
    resetPromise() {
        this.nextRequestPromise = new Promise((resolve) => {
            this.nextRequestPromiseResolve = resolve;
        });
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
        return (this.actions.filter(action => targetedActions.includes(action))).length > 0;
    }
    areAnyModelsUpdated(targetedModels) {
        return (this.updatedModels.filter(model => targetedModels.includes(model))).length > 0;
    }
}

class Backend {
    constructor(url, csrfToken = null) {
        this.url = url;
        this.csrfToken = csrfToken;
    }
    makeRequest(data, actions, updatedModels, childrenFingerprints) {
        const splitUrl = this.url.split('?');
        let [url] = splitUrl;
        const [, queryString] = splitUrl;
        const params = new URLSearchParams(queryString || '');
        const fetchOptions = {};
        fetchOptions.headers = {
            'Accept': 'application/vnd.live-component+html',
        };
        const hasFingerprints = Object.keys(childrenFingerprints).length > 0;
        const hasUpdatedModels = Object.keys(updatedModels).length > 0;
        if (actions.length === 0 && this.willDataFitInUrl(JSON.stringify(data), params, JSON.stringify(childrenFingerprints))) {
            params.set('data', JSON.stringify(data));
            if (hasFingerprints) {
                params.set('childrenFingerprints', JSON.stringify(childrenFingerprints));
            }
            updatedModels.forEach((model) => {
                params.append('updatedModels[]', model);
            });
            fetchOptions.method = 'GET';
        }
        else {
            fetchOptions.method = 'POST';
            fetchOptions.headers['Content-Type'] = 'application/json';
            const requestData = { data };
            if (hasUpdatedModels) {
                requestData.updatedModels = updatedModels;
            }
            if (hasFingerprints) {
                requestData.childrenFingerprints = childrenFingerprints;
            }
            if (actions.length > 0) {
                if (this.csrfToken) {
                    fetchOptions.headers['X-CSRF-TOKEN'] = this.csrfToken;
                }
                if (actions.length === 1) {
                    requestData.args = actions[0].args;
                    url += `/${encodeURIComponent(actions[0].name)}`;
                }
                else {
                    url += '/_batch';
                    requestData.actions = actions;
                }
            }
            fetchOptions.body = JSON.stringify(requestData);
        }
        const paramsString = params.toString();
        return new BackendRequest(fetch(`${url}${paramsString.length > 0 ? `?${paramsString}` : ''}`, fetchOptions), actions.map((backendAction) => backendAction.name), updatedModels);
    }
    willDataFitInUrl(dataJson, params, childrenFingerprintsJson) {
        const urlEncodedJsonData = new URLSearchParams(dataJson + childrenFingerprintsJson).toString();
        return (urlEncodedJsonData + params.toString()).length < 1500;
    }
}

class StandardElementDriver {
    getModelName(element) {
        const modelDirective = getModelDirectiveFromElement(element, false);
        if (!modelDirective) {
            return null;
        }
        return modelDirective.action;
    }
    getComponentData(rootElement) {
        if (!rootElement.dataset.liveDataValue) {
            return null;
        }
        return JSON.parse(rootElement.dataset.liveDataValue);
    }
    getComponentProps(rootElement) {
        if (!rootElement.dataset.livePropsValue) {
            return null;
        }
        return JSON.parse(rootElement.dataset.livePropsValue);
    }
    findChildComponentElement(id, element) {
        return element.querySelector(`[data-live-id=${id}]`);
    }
    getKeyFromElement(element) {
        return element.dataset.liveId || null;
    }
}

class LoadingPlugin {
    attachToComponent(component) {
        component.on('loading.state:started', (element, request) => {
            this.startLoading(element, request);
        });
        component.on('loading.state:finished', (element) => {
            this.finishLoading(element);
        });
        this.finishLoading(component.element);
    }
    startLoading(targetElement, backendRequest) {
        this.handleLoadingToggle(true, targetElement, backendRequest);
    }
    finishLoading(targetElement) {
        this.handleLoadingToggle(false, targetElement, null);
    }
    handleLoadingToggle(isLoading, targetElement, backendRequest) {
        if (isLoading) {
            this.addAttributes(targetElement, ['busy']);
        }
        else {
            this.removeAttributes(targetElement, ['busy']);
        }
        this.getLoadingDirectives(targetElement).forEach(({ element, directives }) => {
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
            delay = modifier.value ? parseInt(modifier.value) : 200;
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
            var _a;
            if (validModifiers.has(modifier.name)) {
                const callable = (_a = validModifiers.get(modifier.name)) !== null && _a !== void 0 ? _a : (() => { });
                callable(modifier);
                return;
            }
            throw new Error(`Unknown modifier "${modifier.name}" used in data-loading="${directive.getString()}". Available modifiers are: ${Array.from(validModifiers.keys()).join(', ')}.`);
        });
        if (isLoading && targetedActions.length > 0 && backendRequest && !backendRequest.containsOneOfActions(targetedActions)) {
            return;
        }
        if (isLoading && targetedModels.length > 0 && backendRequest && !backendRequest.areAnyModelsUpdated(targetedModels)) {
            return;
        }
        let loadingDirective;
        switch (finalAction) {
            case 'show':
                loadingDirective = () => {
                    this.showElement(element);
                };
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
    getLoadingDirectives(element) {
        const loadingDirectives = [];
        element.querySelectorAll('[data-loading]').forEach((element => {
            if (!(element instanceof HTMLElement) && !(element instanceof SVGElement)) {
                throw new Error('Invalid Element Type');
            }
            const directives = parseDirectives(element.dataset.loading || 'show');
            loadingDirectives.push({
                element,
                directives,
            });
        }));
        return loadingDirectives;
    }
    showElement(element) {
        element.style.display = 'inline-block';
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
            this.removeAttributes(element, ['class']);
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
const parseLoadingAction = function (action, isLoading) {
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
        const timer = setInterval(() => {
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
                            duration = parseInt(modifier.value);
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
                debounce = modifier.value ? parseInt(modifier.value) : true;
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
        targetEventName
    };
}

class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.pendingActionTriggerModelElement = null;
        this.elementEventListeners = [
            { event: 'input', callback: (event) => this.handleInputEvent(event) },
            { event: 'change', callback: (event) => this.handleChangeEvent(event) },
            { event: 'live:connect', callback: (event) => this.handleConnectedControllerEvent(event) },
        ];
    }
    initialize() {
        this.handleDisconnectedChildControllerEvent = this.handleDisconnectedChildControllerEvent.bind(this);
        const id = this.element.dataset.liveId || null;
        this.component = new Component(this.element, this.propsValue, this.dataValue, this.fingerprintValue, id, new Backend(this.urlValue, this.csrfValue), new StandardElementDriver());
        this.proxiedComponent = proxifyComponent(this.component);
        this.element.__component = this.proxiedComponent;
        if (this.hasDebounceValue) {
            this.component.defaultDebounce = this.debounceValue;
        }
        const plugins = [
            new LoadingPlugin(),
            new ValidatedFieldsPlugin(),
            new PageUnloadingPlugin(),
            new PollingPlugin(),
            new SetValueOntoModelFieldsPlugin(),
        ];
        plugins.forEach((plugin) => {
            this.component.addPlugin(plugin);
        });
    }
    connect() {
        this.component.connect();
        this.elementEventListeners.forEach(({ event, callback }) => {
            this.component.element.addEventListener(event, callback);
        });
        this._dispatchEvent('live:connect');
    }
    disconnect() {
        this.component.disconnect();
        this.elementEventListeners.forEach(({ event, callback }) => {
            this.component.element.removeEventListener(event, callback);
        });
        this._dispatchEvent('live:disconnect');
    }
    update(event) {
        if (event.type === 'input' || event.type === 'change') {
            throw new Error(`Since LiveComponents 2.3, you no longer need data-action="live#update" on form elements. Found on element: ${getElementAsTagText(event.target)}`);
        }
        this.updateModelFromElementEvent(event.target, null);
    }
    action(event) {
        const rawAction = event.currentTarget.dataset.actionName;
        const directives = parseDirectives(rawAction);
        let debounce = false;
        directives.forEach((directive) => {
            const validModifiers = new Map();
            validModifiers.set('prevent', () => {
                event.preventDefault();
            });
            validModifiers.set('stop', () => {
                event.stopPropagation();
            });
            validModifiers.set('self', () => {
                if (event.target !== event.currentTarget) {
                    return;
                }
            });
            validModifiers.set('debounce', (modifier) => {
                debounce = modifier.value ? parseInt(modifier.value) : true;
            });
            directive.modifiers.forEach((modifier) => {
                var _a;
                if (validModifiers.has(modifier.name)) {
                    const callable = (_a = validModifiers.get(modifier.name)) !== null && _a !== void 0 ? _a : (() => { });
                    callable(modifier);
                    return;
                }
                console.warn(`Unknown modifier ${modifier.name} in action "${rawAction}". Available modifiers are: ${Array.from(validModifiers.keys()).join(', ')}.`);
            });
            this.component.action(directive.action, directive.named, debounce);
            if (getModelDirectiveFromElement(event.currentTarget, false)) {
                this.pendingActionTriggerModelElement = event.currentTarget;
            }
        });
    }
    $render() {
        this.component.render();
    }
    $updateModel(model, value, shouldRender = true, debounce = true) {
        this.component.set(model, value, shouldRender, debounce);
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
    handleConnectedControllerEvent(event) {
        if (event.target === this.element) {
            return;
        }
        const childController = event.detail.controller;
        if (childController.component.getParent()) {
            return;
        }
        const modelDirectives = getAllModelDirectiveFromElements(childController.element);
        const modelBindings = modelDirectives.map(getModelBinding);
        this.component.addChild(childController.component, modelBindings);
        childController.element.addEventListener('live:disconnect', this.handleDisconnectedChildControllerEvent);
    }
    handleDisconnectedChildControllerEvent(event) {
        const childController = event.detail.controller;
        childController.element.removeEventListener('live:disconnect', this.handleDisconnectedChildControllerEvent);
        if (childController.component.getParent() !== this.component) {
            return;
        }
        this.component.removeChild(childController.component);
    }
    _dispatchEvent(name, detail = {}, canBubble = true, cancelable = false) {
        detail.controller = this;
        detail.component = this.proxiedComponent;
        return this.element.dispatchEvent(new CustomEvent(name, {
            bubbles: canBubble,
            cancelable,
            detail
        }));
    }
}
default_1.values = {
    url: String,
    data: Object,
    props: Object,
    csrf: String,
    debounce: { type: Number, default: 150 },
    id: String,
    fingerprint: String,
};

export { default_1 as default };
