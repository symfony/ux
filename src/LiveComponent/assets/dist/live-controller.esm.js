import _slicedToArray from '@babel/runtime/helpers/slicedToArray';
import _toConsumableArray from '@babel/runtime/helpers/toConsumableArray';
import _classCallCheck from '@babel/runtime/helpers/classCallCheck';
import _createClass from '@babel/runtime/helpers/createClass';
import _assertThisInitialized from '@babel/runtime/helpers/assertThisInitialized';
import _inherits from '@babel/runtime/helpers/inherits';
import _possibleConstructorReturn from '@babel/runtime/helpers/possibleConstructorReturn';
import _getPrototypeOf from '@babel/runtime/helpers/getPrototypeOf';
import _defineProperty from '@babel/runtime/helpers/defineProperty';
import { Controller } from '@hotwired/stimulus';
import _typeof from '@babel/runtime/helpers/typeof';
import 'core-js/web/url';
import 'core-js/es/promise';
import 'core-js/es/array/find-index';

var DOCUMENT_FRAGMENT_NODE = 11;

function morphAttrs(fromNode, toNode) {
  var toNodeAttrs = toNode.attributes;
  var attr;
  var attrName;
  var attrNamespaceURI;
  var attrValue;
  var fromValue; // document-fragments dont have attributes so lets not do anything

  if (toNode.nodeType === DOCUMENT_FRAGMENT_NODE || fromNode.nodeType === DOCUMENT_FRAGMENT_NODE) {
    return;
  } // update attributes on original DOM element


  for (var i = toNodeAttrs.length - 1; i >= 0; i--) {
    attr = toNodeAttrs[i];
    attrName = attr.name;
    attrNamespaceURI = attr.namespaceURI;
    attrValue = attr.value;

    if (attrNamespaceURI) {
      attrName = attr.localName || attrName;
      fromValue = fromNode.getAttributeNS(attrNamespaceURI, attrName);

      if (fromValue !== attrValue) {
        if (attr.prefix === 'xmlns') {
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
  } // Remove any extra attributes found on the original DOM element that
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
  toCodeStart = toNodeName.charCodeAt(0); // If the target element is a virtual DOM node or SVG node then we may
  // need to normalize the tag name before comparing. Normal HTML elements that are
  // in the "http://www.w3.org/1999/xhtml"
  // are converted to upper case

  if (fromCodeStart <= 90 && toCodeStart >= 97) {
    // from is upper and to is lower
    return fromNodeName === toNodeName.toUpperCase();
  } else if (toCodeStart <= 90 && fromCodeStart >= 97) {
    // to is upper and from is lower
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
  return !namespaceURI || namespaceURI === NS_XHTML ? doc.createElement(name) : doc.createElementNS(namespaceURI, name);
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
  OPTION: function OPTION(fromEl, toEl) {
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
        } // We have to reset select element's selectedIndex to -1, otherwise setting
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
  INPUT: function INPUT(fromEl, toEl) {
    syncBooleanAttrProp(fromEl, toEl, 'checked');
    syncBooleanAttrProp(fromEl, toEl, 'disabled');

    if (fromEl.value !== toEl.value) {
      fromEl.value = toEl.value;
    }

    if (!toEl.hasAttribute('value')) {
      fromEl.removeAttribute('value');
    }
  },
  TEXTAREA: function TEXTAREA(fromEl, toEl) {
    var newValue = toEl.value;

    if (fromEl.value !== newValue) {
      fromEl.value = newValue;
    }

    var firstChild = fromEl.firstChild;

    if (firstChild) {
      // Needed for IE. Apparently IE sets the placeholder as the
      // node value and vise versa. This ignores an empty update.
      var oldValue = firstChild.nodeValue;

      if (oldValue == newValue || !newValue && oldValue == fromEl.placeholder) {
        return;
      }

      firstChild.nodeValue = newValue;
    }
  },
  SELECT: function SELECT(fromEl, toEl) {
    if (!toEl.hasAttribute('multiple')) {
      var selectedIndex = -1;
      var i = 0; // We have to loop through children of fromEl, not toEl since nodes can be moved
      // from toEl to fromEl directly when morphing.
      // At the time this special handler is invoked, all children have already been morphed
      // and appended to / removed from fromEl, so using fromEl here is safe and correct.

      var curChild = fromEl.firstChild;
      var optgroup;
      var nodeName;

      while (curChild) {
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
    return node.getAttribute && node.getAttribute('id') || node.id;
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
    var childrenOnly = options.childrenOnly === true; // This object is used as a lookup to quickly find all keyed elements in the original DOM tree.

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
    } // // TreeWalker implementation is no faster, but keeping this around in case this changes in the future
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
          } // Walk recursively


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
          var unmatchedFromEl = fromNodesLookup[key]; // if we find a duplicate #id node in cache, replace `el` with cache value
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

        if (curFromNodeKey = getNodeKey(curFromNodeChild)) {
          // Since the node is keyed it might be matched up later so we defer
          // the actual removal to later
          addKeyedRemoval(curFromNodeKey);
        } else {
          // NOTE: we skip nested keyed nodes from being removed since there is
          //       still a chance they will be matched up later
          removeNode(curFromNodeChild, fromEl, true
          /* skip keyed nodes */
          );
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
        } // update attributes on original DOM element first


        morphAttrs(fromEl, toEl); // optional

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
      var matchingFromEl; // walk the children

      outer: while (curToNodeChild) {
        toNextSibling = curToNodeChild.nextSibling;
        curToNodeKey = getNodeKey(curToNodeChild); // walk the fromNode children all the way through

        while (curFromNodeChild) {
          fromNextSibling = curFromNodeChild.nextSibling;

          if (curToNodeChild.isSameNode && curToNodeChild.isSameNode(curFromNodeChild)) {
            curToNodeChild = toNextSibling;
            curFromNodeChild = fromNextSibling;
            continue outer;
          }

          curFromNodeKey = getNodeKey(curFromNodeChild);
          var curFromNodeType = curFromNodeChild.nodeType; // this means if the curFromNodeChild doesnt have a match with the curToNodeChild

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
                  if (matchingFromEl = fromNodesLookup[curToNodeKey]) {
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
                      fromEl.insertBefore(matchingFromEl, curFromNodeChild); // fromNextSibling = curFromNodeChild.nextSibling;

                      if (curFromNodeKey) {
                        // Since the node is keyed it might be matched up later so we defer
                        // the actual removal to later
                        addKeyedRemoval(curFromNodeKey);
                      } else {
                        // NOTE: we skip nested keyed nodes from being removed since there is
                        //       still a chance they will be matched up later
                        removeNode(curFromNodeChild, fromEl, true
                        /* skip keyed nodes */
                        );
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
              isCompatible = true; // Simply update nodeValue on the original node to
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
          } // No compatible match so remove the old node from the DOM and continue trying to find a
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
            removeNode(curFromNodeChild, fromEl, true
            /* skip keyed nodes */
            );
          }

          curFromNodeChild = fromNextSibling;
        } // END: while(curFromNodeChild) {}
        // If we got this far then we did not find a candidate match for
        // our "to node" and we exhausted all of the children "from"
        // nodes. Therefore, we will just append the current "to" node
        // to the end


        if (curToNodeKey && (matchingFromEl = fromNodesLookup[curToNodeKey]) && compareNodeNames(matchingFromEl, curToNodeChild)) {
          fromEl.appendChild(matchingFromEl); // MORPH

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
      } else if (morphedNodeType === TEXT_NODE || morphedNodeType === COMMENT_NODE) {
        // Text or comment node
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

      morphEl(morphedNode, toNode, childrenOnly); // We now need to loop over any keyed nodes that might need to be
      // removed. We only do the removal if we know that the keyed node
      // never found a match. When a keyed node is matched up we remove
      // it out of fromNodesLookup and we use fromNodesLookup to determine
      // if a keyed node has been matched up or not

      if (keyedRemovalList) {
        for (var i = 0, len = keyedRemovalList.length; i < len; i++) {
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
      } // If we had to swap out the from node with a new node because the old
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

/**
 * A modifier for a directive
 *
 * @typedef {Object} DirectiveModifier
 * @property {string} name The name of the modifier (e.g. delay)
 * @property {string|null} value The value of the single argument or null if no argument
 */

/**
 * A directive with action, args and modifiers.
 *
 * @typedef {Object} Directive
 * @property {string} action The name of the action (e.g. addClass)
 * @property {string[]} args An array of unnamed arguments passed to the action
 * @property {Object} named An object of named arguments
 * @property {DirectiveModifier[]} modifiers Any modifiers applied to the action
 * @property {function} getString()
 */

/**
 * Parses strings like "addClass(foo) removeAttribute(bar)"
 * into an array of directives, with this format:
 *
 *      [
 *          { action: 'addClass', args: ['foo'], named: {}, modifiers: [] },
 *          { action: 'removeAttribute', args: ['bar'], named: {}, modifiers: [] }
 *      ]
 *
 * This also handles named arguments
 *
 *      save(foo=bar, baz=bazzles)
 *
 * Which would return:
 *      [
 *          { action: 'save', args: [], named: { foo: 'bar', baz: 'bazzles }, modifiers: [] }
 *      ]
 *
 * @param {string} content The value of the attribute
 * @return {Directive[]}
 */
function parseDirectives(content) {
  var directives = [];

  if (!content) {
    return directives;
  }

  var currentActionName = '';
  var currentArgumentName = '';
  var currentArgumentValue = '';
  var currentArguments = [];
  var currentNamedArguments = {};
  var currentModifiers = [];
  var state = 'action';

  var getLastActionName = function getLastActionName() {
    if (currentActionName) {
      return currentActionName;
    }

    if (directives.length === 0) {
      throw new Error('Could not find any directives');
    }

    return directives[directives.length - 1].action;
  };

  var pushInstruction = function pushInstruction() {
    directives.push({
      action: currentActionName,
      args: currentArguments,
      named: currentNamedArguments,
      modifiers: currentModifiers,
      getString: function getString() {
        // TODO - make a string representation of JUST this directive
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

  var pushArgument = function pushArgument() {
    var mixedArgTypesError = function mixedArgTypesError() {
      throw new Error("Normal and named arguments cannot be mixed inside \"".concat(currentActionName, "()\""));
    };

    if (currentArgumentName) {
      if (currentArguments.length > 0) {
        mixedArgTypesError();
      } // argument names are also trimmed to avoid space after ","
      // "foo=bar, baz=bazzles"


      currentNamedArguments[currentArgumentName.trim()] = currentArgumentValue;
    } else {
      if (Object.keys(currentNamedArguments).length > 0) {
        mixedArgTypesError();
      } // value is trimmed to avoid space after ","
      // "foo, bar"


      currentArguments.push(currentArgumentValue.trim());
    }

    currentArgumentName = '';
    currentArgumentValue = '';
  };

  var pushModifier = function pushModifier() {
    if (currentArguments.length > 1) {
      throw new Error("The modifier \"".concat(currentActionName, "()\" does not support multiple arguments."));
    }

    if (Object.keys(currentNamedArguments).length > 0) {
      throw new Error("The modifier \"".concat(currentActionName, "()\" does not support named arguments."));
    }

    currentModifiers.push({
      name: currentActionName,
      value: currentArguments.length > 0 ? currentArguments[0] : null
    });
    currentActionName = '';
    currentArgumentName = '';
    currentArguments = [];
    state = 'action';
  };

  for (var i = 0; i < content.length; i++) {
    var char = content[i];

    switch (state) {
      case 'action':
        if (char === '(') {
          state = 'arguments';
          break;
        }

        if (char === ' ') {
          // this is the end of the action and it has no arguments
          // if the action had args(), it was already recorded
          if (currentActionName) {
            pushInstruction();
          }

          break;
        }

        if (char === '|') {
          // ah, this was a modifier (with no arguments)
          pushModifier();
          break;
        } // we're expecting more characters for an action name


        currentActionName += char;
        break;

      case 'arguments':
        if (char === ')') {
          // end of the arguments for a modifier or the action
          pushArgument();
          state = 'after_arguments';
          break;
        }

        if (char === ',') {
          // end of current argument
          pushArgument();
          break;
        }

        if (char === '=') {
          // this is a named argument!
          currentArgumentName = currentArgumentValue;
          currentArgumentValue = '';
          break;
        } // add next character to argument


        currentArgumentValue += char;
        break;

      case 'after_arguments':
        // the previous character was a ")" to end arguments
        // ah, this was actually the end of a modifier!
        if (char === '|') {
          pushModifier();
          break;
        } // we just finished an action(), and now we need a space


        if (char !== ' ') {
          throw new Error("Missing space after ".concat(getLastActionName(), "()"));
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
      throw new Error("Did you forget to add a closing \")\" after \"".concat(currentActionName, "\"?"));
  }

  return directives;
}

/**
 * Splits each string in an array containing a space into an extra array item:
 *
 * Input:
 *      [
 *          'foo',
 *          'bar baz',
 *      ]
 *
 * Output:
 *      ['foo', 'bar', 'baz']
 *
 * @param {string[]} parts
 * @return {string[]}
 */
function combineSpacedArray(parts) {
  var finalParts = [];
  parts.forEach(function (part) {
    finalParts.push.apply(finalParts, _toConsumableArray(part.split(' ')));
  });
  return finalParts;
}

/*
 * Helper to convert a deep object of data into a format
 * that can be transmitted as GET or POST data.
 *
 * Likely there is an easier way to do this with no duplication.
 */
var buildFormKey = function buildFormKey(key, parentKeys) {
  var fieldName = '';
  [].concat(_toConsumableArray(parentKeys), [key]).forEach(function (name) {
    fieldName += fieldName ? "[".concat(name, "]") : name;
  });
  return fieldName;
};
/**
 * @param {FormData} formData
 * @param {Object} data
 * @param {Array} parentKeys
 */


var addObjectToFormData = function addObjectToFormData(formData, data, parentKeys) {
  // todo - handles files
  Object.keys(data).forEach(function (key) {
    var value = data[key]; // TODO: there is probably a better way to normalize this

    if (value === true) {
      value = 1;
    }

    if (value === false) {
      value = 0;
    } // don't send null values at all


    if (value === null) {
      return;
    } // handle embedded objects


    if (_typeof(value) === 'object' && value !== null) {
      addObjectToFormData(formData, value, [].concat(_toConsumableArray(parentKeys), [key]));
      return;
    }

    formData.append(buildFormKey(key, parentKeys), value);
  });
};
/**
 * @param {URLSearchParams} searchParams
 * @param {Object} data
 * @param {Array} parentKeys
 */


var addObjectToSearchParams = function addObjectToSearchParams(searchParams, data, parentKeys) {
  Object.keys(data).forEach(function (key) {
    var value = data[key]; // TODO: there is probably a better way to normalize this
    // TODO: duplication

    if (value === true) {
      value = 1;
    }

    if (value === false) {
      value = 0;
    } // don't send null values at all


    if (value === null) {
      return;
    } // handle embedded objects


    if (_typeof(value) === 'object' && value !== null) {
      addObjectToSearchParams(searchParams, value, [].concat(_toConsumableArray(parentKeys), [key]));
      return;
    }

    searchParams.set(buildFormKey(key, parentKeys), value);
  });
};
/**
 * @param {Object} data
 * @return {FormData}
 */


function buildFormData(data) {
  var formData = new FormData();
  addObjectToFormData(formData, data, []);
  return formData;
}
/**
 * @param {URLSearchParams} searchParams
 * @param {Object} data
 * @return {URLSearchParams}
 */

function buildSearchParams(searchParams, data) {
  addObjectToSearchParams(searchParams, data, []);
  return searchParams;
}

// post.user.username
function setDeepData(data, propertyPath, value) {
  // cheap way to deep clone simple data
  var finalData = JSON.parse(JSON.stringify(data));
  var currentLevelData = finalData;
  var parts = propertyPath.split('.'); // change currentLevelData to the final depth object

  for (var i = 0; i < parts.length - 1; i++) {
    currentLevelData = currentLevelData[parts[i]];
  } // now finally change the key on that deeper object


  var finalKey = parts[parts.length - 1]; // make sure the currentLevelData is an object, not a scalar
  // if it is, it means the initial data didn't know that sub-properties
  // could be exposed. Or, you're just trying to set some deep
  // path - e.g. post.title - onto some property that is, for example,
  // an integer (2).

  if (_typeof(currentLevelData) !== 'object') {
    var lastPart = parts.pop();
    throw new Error("Cannot set data-model=\"".concat(propertyPath, "\". They parent \"").concat(parts.join(','), "\" data does not appear to be an object (it's \"").concat(currentLevelData, "\"). Did you forget to add exposed={\"").concat(lastPart, "\"} to its LiveProp?"));
  } // represents a situation where the key you're setting *is* an object,
  // but the key we're setting is a new key. Currently, all keys should
  // be initialized with the initial data.


  if (currentLevelData[finalKey] === undefined) {
    var _lastPart = parts.pop();

    if (parts.length > 0) {
      throw new Error("The property used in data-model=\"".concat(propertyPath, "\" was never initialized. Did you forget to add exposed={\"").concat(_lastPart, "\"} to its LiveProp?"));
    } else {
      throw new Error("The property used in data-model=\"".concat(propertyPath, "\" was never initialized. Did you forget to expose \"").concat(_lastPart, "\" as a LiveProp? Available models values are: ").concat(Object.keys(data).length > 0 ? Object.keys(data).join(', ') : '(none)'));
    }
  }

  currentLevelData[finalKey] = value;
  return finalData;
}
/**
 * Checks if the given propertyPath is for a valid top-level key.
 *
 * @param {Object} data
 * @param {string} propertyPath
 * @return {boolean}
 */

function doesDeepPropertyExist(data, propertyPath) {
  var parts = propertyPath.split('.');
  return data[parts[0]] !== undefined;
}
/**
 * Normalizes model names with [] into the "." syntax.
 *
 * For example: "user[firstName]" becomes "user.firstName"
 *
 * @param {string} model
 * @return {string}
 */

function normalizeModelName(model) {
  return model.split('[') // ['object', 'foo', 'bar', 'ya']
  .map(function (s) {
    return s.replace(']', '');
  }).join('.');
}

function haveRenderedValuesChanged(originalDataJson, currentDataJson, newDataJson) {
  /*
   * Right now, if the "data" on the new value is different than
   * the "original data" on the child element, then we force re-render
   * the child component. There may be some other cases that we
   * add later if they come up. Either way, this is probably the
   * behavior we want most of the time, but it's not perfect. For
   * example, if the child component has some a writable prop that
   * has changed since the initial render, re-rendering the child
   * component from the parent component will "eliminate" that
   * change.
   */
  // if the original data matches the new data, then the parent
  // hasn't changed how they render the child.
  if (originalDataJson === newDataJson) {
    return false;
  } // The child component IS now being rendered in a "new way".
  // This means that at least one of the "data" pieces used
  // to render the child component has changed.
  // However, the piece of data that changed might simply
  // match the "current data" of that child component. In that case,
  // there is no point to re-rendering.
  // And, to be safe (in case the child has some "private LiveProp"
  // that has been modified), we want to avoid rendering.
  // if the current data exactly matches the new data, then definitely
  // do not re-render.


  if (currentDataJson === newDataJson) {
    return false;
  } // here, we will compare the original data for the child component
  // with the new data. What we're looking for are they keys that
  // have changed between the original "child rendering" and the
  // new "child rendering".


  var originalData = JSON.parse(originalDataJson);
  var newData = JSON.parse(newDataJson);
  var changedKeys = Object.keys(newData);
  Object.entries(originalData).forEach(function (_ref) {
    var _ref2 = _slicedToArray(_ref, 2),
        key = _ref2[0],
        value = _ref2[1];

    // if any key in the new data is different than a key in the
    // current data, then we *should* re-render. But if all the
    // keys in the new data equal
    if (value === newData[key]) {
      // value is equal, remove from changedKeys
      changedKeys.splice(changedKeys.indexOf(key), 1);
    }
  }); // now that we know which keys have changed between originally
  // rendering the child component and this latest render, we
  // can check to see if the the child component *already* has
  // the latest value for those keys.

  var currentData = JSON.parse(currentDataJson);
  var keyHasChanged = false;
  changedKeys.forEach(function (key) {
    // if any key in the new data is different than a key in the
    // current data, then we *should* re-render. But if all the
    // keys in the new data equal
    if (currentData[key] !== newData[key]) {
      keyHasChanged = true;
    }
  });
  return keyHasChanged;
}

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }
var DEFAULT_DEBOUNCE = '150';

var _default = /*#__PURE__*/function (_Controller) {
  _inherits(_default, _Controller);

  var _super = _createSuper(_default);

  function _default() {
    var _this;

    _classCallCheck(this, _default);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _super.call.apply(_super, [this].concat(args));

    _defineProperty(_assertThisInitialized(_this), "renderDebounceTimeout", null);

    _defineProperty(_assertThisInitialized(_this), "actionDebounceTimeout", null);

    _defineProperty(_assertThisInitialized(_this), "renderPromiseStack", new PromiseStack());

    _defineProperty(_assertThisInitialized(_this), "pollingIntervals", []);

    _defineProperty(_assertThisInitialized(_this), "isWindowUnloaded", false);

    _defineProperty(_assertThisInitialized(_this), "originalDataJSON", void 0);

    _defineProperty(_assertThisInitialized(_this), "markAsWindowUnloaded", function () {
      _this.isWindowUnloaded = true;
    });

    return _this;
  }

  _createClass(_default, [{
    key: "initialize",
    value: function initialize() {
      this.markAsWindowUnloaded = this.markAsWindowUnloaded.bind(this);
      this.originalDataJSON = JSON.stringify(this.dataValue);

      this._exposeOriginalData();
    }
  }, {
    key: "connect",
    value: function connect() {
      var _this2 = this;

      // hide "loading" elements to begin with
      // This is done with CSS, but only for the most basic cases
      this._onLoadingFinish();

      if (this.element.dataset.poll !== undefined) {
        this._initiatePolling(this.element.dataset.poll);
      }

      window.addEventListener('beforeunload', this.markAsWindowUnloaded);
      this.element.addEventListener('live:update-model', function (event) {
        // ignore events that we dispatched
        if (event.target === _this2.element) {
          return;
        }

        _this2._handleChildComponentUpdateModel(event);
      });

      this._dispatchEvent('live:connect');
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      this.pollingIntervals.forEach(function (interval) {
        clearInterval(interval);
      });
      window.removeEventListener('beforeunload', this.markAsWindowUnloaded);
    }
    /**
     * Called to update one piece of the model
     */

  }, {
    key: "update",
    value: function update(event) {
      var value = event.target.value;

      this._updateModelFromElement(event.target, value, true);
    }
  }, {
    key: "updateDefer",
    value: function updateDefer(event) {
      var value = event.target.value;

      this._updateModelFromElement(event.target, value, false);
    }
  }, {
    key: "action",
    value: function action(event) {
      var _this3 = this;

      // using currentTarget means that the data-action and data-action-name
      // must live on the same element: you can't add
      // data-action="click->live#action" on a parent element and
      // expect it to use the data-action-name from the child element
      // that actually received the click
      var rawAction = event.currentTarget.dataset.actionName; // data-action-name="prevent|debounce(1000)|save"

      var directives = parseDirectives(rawAction);
      directives.forEach(function (directive) {
        // set here so it can be delayed with debouncing below
        var _executeAction = function _executeAction() {
          // if any normal renders are waiting to start, cancel them
          // allow the action to start and finish
          // this covers a case where you "blur" a field to click "save"
          // the "change" event will trigger first & schedule a re-render
          // then the action Ajax will start. We want to avoid the
          // re-render request from starting after the debounce and
          // taking precedence
          _this3._clearWaitingDebouncedRenders();

          _this3._makeRequest(directive.action);
        };

        var handled = false;
        directive.modifiers.forEach(function (modifier) {
          switch (modifier.name) {
            case 'prevent':
              event.preventDefault();
              break;

            case 'stop':
              event.stopPropagation();
              break;

            case 'self':
              if (event.target !== event.currentTarget) {
                return;
              }

              break;

            case 'debounce':
              {
                var length = modifier.value ? modifier.value : DEFAULT_DEBOUNCE; // clear any pending renders

                if (_this3.actionDebounceTimeout) {
                  clearTimeout(_this3.actionDebounceTimeout);
                  _this3.actionDebounceTimeout = null;
                }

                _this3.actionDebounceTimeout = setTimeout(function () {
                  _this3.actionDebounceTimeout = null;

                  _executeAction();
                }, length);
                handled = true;
                break;
              }

            default:
              console.warn("Unknown modifier ".concat(modifier.name, " in action ").concat(rawAction));
          }
        });

        if (!handled) {
          _executeAction();
        }
      });
    }
  }, {
    key: "$render",
    value: function $render() {
      this._makeRequest(null);
    }
  }, {
    key: "_updateModelFromElement",
    value: function _updateModelFromElement(element, value, shouldRender) {
      var model = element.dataset.model || element.getAttribute('name');

      if (!model) {
        var clonedElement = element.cloneNode();
        clonedElement.innerHTML = '';
        throw new Error("The update() method could not be called for \"".concat(clonedElement.outerHTML, "\": the element must either have a \"data-model\" or \"name\" attribute set to the model name."));
      }

      this.$updateModel(model, value, shouldRender, element.hasAttribute('name') ? element.getAttribute('name') : null);
    }
    /**
     * Update a model value.
     *
     * The extraModelName should be set to the "name" attribute of an element
     * if it has one. This is only important in a parent/child component,
     * where, in the child, you might be updating a "foo" model, but you
     * also want this update to "sync" to the parent component's "bar" model.
     * Typically, setup on a field like this:
     *
     *      <input data-model="foo" name="bar">
     *
     * @param {string} model The model update, which could include modifiers
     * @param {any} value The new value
     * @param {boolean} shouldRender Whether a re-render should be triggered
     * @param {string|null} extraModelName Another model name that this might go by in a parent component.
     * @param {Object} options Options include: {bool} dispatch
     */

  }, {
    key: "$updateModel",
    value: function $updateModel(model, value) {
      var _this4 = this;

      var shouldRender = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
      var extraModelName = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : null;
      var options = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : {};
      var directives = parseDirectives(model);

      if (directives.length > 1) {
        throw new Error("The data-model=\"".concat(model, "\" format is invalid: it does not support multiple directives (i.e. remove any spaces)."));
      }

      var directive = directives[0];

      if (directive.args.length > 0 || directive.named.length > 0) {
        throw new Error("The data-model=\"".concat(model, "\" format is invalid: it does not support passing arguments to the model."));
      }

      var modelName = normalizeModelName(directive.action);
      var normalizedExtraModelName = extraModelName ? normalizeModelName(extraModelName) : null; // if there is a "validatedFields" data, it means this component wants
      // to track which fields have been / should be validated.
      // in that case, when the model is updated, mark that it should be validated

      if (this.dataValue.validatedFields !== undefined) {
        var validatedFields = _toConsumableArray(this.dataValue.validatedFields);

        if (validatedFields.indexOf(modelName) === -1) {
          validatedFields.push(modelName);
        }

        this.dataValue = setDeepData(this.dataValue, 'validatedFields', validatedFields);
      }

      if (options.dispatch !== false) {
        this._dispatchEvent('live:update-model', {
          modelName: modelName,
          extraModelName: normalizedExtraModelName,
          value: value
        });
      } // we do not send old and new data to the server
      // we merge in the new data now
      // TODO: handle edge case for top-level of a model with "exposed" props
      // For example, suppose there is a "post" field but "post.title" is exposed.
      // If there is a data-model="post", then the "post" data - which was
      // previously an array with "id" and "title" fields - will now be set
      // directly to the new post id (e.g. 4). From a saving standpoint,
      // that is fine: the server sees the "4" and uses it for the post data.
      // However, there is an edge case where the user changes data-model="post"
      // and then, for some reason, they don't want an immediate re-render.
      // Then, then modify the data-model="post.title" field. In theory,
      // we should be smart enough to convert the post data - which is now
      // the string "4" - back into an array with [id=4, title=new_title].


      this.dataValue = setDeepData(this.dataValue, modelName, value);
      directive.modifiers.forEach(function (modifier) {
        switch (modifier.name) {
          // there are currently no data-model modifiers
          default:
            throw new Error("Unknown modifier ".concat(modifier.name, " used in data-model=\"").concat(model, "\""));
        }
      });

      if (shouldRender) {
        // clear any pending renders
        this._clearWaitingDebouncedRenders();

        this.renderDebounceTimeout = setTimeout(function () {
          _this4.renderDebounceTimeout = null;

          _this4.$render();
        }, this.debounceValue || DEFAULT_DEBOUNCE);
      }
    }
  }, {
    key: "_makeRequest",
    value: function _makeRequest(action) {
      var _this5 = this;

      var _this$urlValue$split = this.urlValue.split('?'),
          _this$urlValue$split2 = _slicedToArray(_this$urlValue$split, 2),
          url = _this$urlValue$split2[0],
          queryString = _this$urlValue$split2[1];

      var params = new URLSearchParams(queryString || '');
      var fetchOptions = {
        headers: {
          'Accept': 'application/vnd.live-component+json'
        }
      };

      if (action) {
        url += "/".concat(encodeURIComponent(action));

        if (this.csrfValue) {
          fetchOptions.headers['X-CSRF-TOKEN'] = this.csrfValue;
        }
      }

      if (!action && this._willDataFitInUrl()) {
        buildSearchParams(params, this.dataValue);
        fetchOptions.method = 'GET';
      } else {
        fetchOptions.method = 'POST';
        fetchOptions.body = buildFormData(this.dataValue);
      }

      this._onLoadingStart();

      var paramsString = params.toString();
      var thisPromise = fetch("".concat(url).concat(paramsString.length > 0 ? "?".concat(paramsString) : ''), fetchOptions);
      this.renderPromiseStack.addPromise(thisPromise);
      thisPromise.then(function (response) {
        // if another re-render is scheduled, do not "run it over"
        if (_this5.renderDebounceTimeout) {
          return;
        }

        var isMostRecent = _this5.renderPromiseStack.removePromise(thisPromise);

        if (isMostRecent) {
          response.json().then(function (data) {
            _this5._processRerender(data);
          });
        }
      });
    }
    /**
     * Processes the response from an AJAX call and uses it to re-render.
     *
     * @private
     */

  }, {
    key: "_processRerender",
    value: function _processRerender(data) {
      // check if the page is navigating away
      if (this.isWindowUnloaded) {
        return;
      }

      if (data.redirect_url) {
        // action returned a redirect

        /* global Turbo */
        if (typeof Turbo !== 'undefined') {
          Turbo.visit(data.redirect_url);
        } else {
          window.location = data.redirect_url;
        }

        return;
      }

      if (!this._dispatchEvent('live:render', data, true, true)) {
        // preventDefault() was called
        return;
      } // remove the loading behavior now so that when we morphdom
      // "diffs" the elements, any loading differences will not cause
      // elements to appear different unnecessarily


      this._onLoadingFinish(); // merge/patch in the new HTML


      this._executeMorphdom(data.html); // "data" holds the new, updated data


      this.dataValue = data.data;
    }
  }, {
    key: "_clearWaitingDebouncedRenders",
    value: function _clearWaitingDebouncedRenders() {
      if (this.renderDebounceTimeout) {
        clearTimeout(this.renderDebounceTimeout);
        this.renderDebounceTimeout = null;
      }
    }
  }, {
    key: "_onLoadingStart",
    value: function _onLoadingStart() {
      this._handleLoadingToggle(true);
    }
  }, {
    key: "_onLoadingFinish",
    value: function _onLoadingFinish() {
      this._handleLoadingToggle(false);
    }
  }, {
    key: "_handleLoadingToggle",
    value: function _handleLoadingToggle(isLoading) {
      var _this6 = this;

      this._getLoadingDirectives().forEach(function (_ref) {
        var element = _ref.element,
            directives = _ref.directives;

        // so we can track, at any point, if an element is in a "loading" state
        if (isLoading) {
          _this6._addAttributes(element, ['data-live-is-loading']);
        } else {
          _this6._removeAttributes(element, ['data-live-is-loading']);
        }

        directives.forEach(function (directive) {
          _this6._handleLoadingDirective(element, isLoading, directive);
        });
      });
    }
    /**
     * @param {Element} element
     * @param {boolean} isLoading
     * @param {Directive} directive
     * @private
     */

  }, {
    key: "_handleLoadingDirective",
    value: function _handleLoadingDirective(element, isLoading, directive) {
      var _this7 = this;

      var finalAction = parseLoadingAction(directive.action, isLoading);
      var loadingDirective = null;

      switch (finalAction) {
        case 'show':
          loadingDirective = function loadingDirective() {
            _this7._showElement(element);
          };

          break;

        case 'hide':
          loadingDirective = function loadingDirective() {
            return _this7._hideElement(element);
          };

          break;

        case 'addClass':
          loadingDirective = function loadingDirective() {
            return _this7._addClass(element, directive.args);
          };

          break;

        case 'removeClass':
          loadingDirective = function loadingDirective() {
            return _this7._removeClass(element, directive.args);
          };

          break;

        case 'addAttribute':
          loadingDirective = function loadingDirective() {
            return _this7._addAttributes(element, directive.args);
          };

          break;

        case 'removeAttribute':
          loadingDirective = function loadingDirective() {
            return _this7._removeAttributes(element, directive.args);
          };

          break;

        default:
          throw new Error("Unknown data-loading action \"".concat(finalAction, "\""));
      }

      var isHandled = false;
      directive.modifiers.forEach(function (modifier) {
        switch (modifier.name) {
          case 'delay':
            {
              // if loading has *stopped*, the delay modifier has no effect
              if (!isLoading) {
                break;
              }

              var delayLength = modifier.value || 200;
              setTimeout(function () {
                if (element.hasAttribute('data-live-is-loading')) {
                  loadingDirective();
                }
              }, delayLength);
              isHandled = true;
              break;
            }

          default:
            throw new Error("Unknown modifier ".concat(modifier.name, " used in the loading directive ").concat(directive.getString()));
        }
      }); // execute the loading directive

      if (!isHandled) {
        loadingDirective();
      }
    }
  }, {
    key: "_getLoadingDirectives",
    value: function _getLoadingDirectives() {
      var loadingDirectives = [];
      this.element.querySelectorAll('[data-loading]').forEach(function (element) {
        // use "show" if the attribute is empty
        var directives = parseDirectives(element.dataset.loading || 'show');
        loadingDirectives.push({
          element: element,
          directives: directives
        });
      });
      return loadingDirectives;
    }
  }, {
    key: "_showElement",
    value: function _showElement(element) {
      element.style.display = 'inline-block';
    }
  }, {
    key: "_hideElement",
    value: function _hideElement(element) {
      element.style.display = 'none';
    }
  }, {
    key: "_addClass",
    value: function _addClass(element, classes) {
      var _element$classList;

      (_element$classList = element.classList).add.apply(_element$classList, _toConsumableArray(combineSpacedArray(classes)));
    }
  }, {
    key: "_removeClass",
    value: function _removeClass(element, classes) {
      var _element$classList2;

      (_element$classList2 = element.classList).remove.apply(_element$classList2, _toConsumableArray(combineSpacedArray(classes))); // remove empty class="" to avoid morphdom "diff" problem


      if (element.classList.length === 0) {
        this._removeAttributes(element, ['class']);
      }
    }
  }, {
    key: "_addAttributes",
    value: function _addAttributes(element, attributes) {
      attributes.forEach(function (attribute) {
        element.setAttribute(attribute, '');
      });
    }
  }, {
    key: "_removeAttributes",
    value: function _removeAttributes(element, attributes) {
      attributes.forEach(function (attribute) {
        element.removeAttribute(attribute);
      });
    }
  }, {
    key: "_willDataFitInUrl",
    value: function _willDataFitInUrl() {
      // if the URL gets remotely close to 2000 chars, it may not fit
      return Object.values(this.dataValue).join(',').length < 1500;
    }
  }, {
    key: "_executeMorphdom",
    value: function _executeMorphdom(newHtml) {
      var _this8 = this;

      // https://stackoverflow.com/questions/494143/creating-a-new-dom-element-from-an-html-string-using-built-in-dom-methods-or-pro#answer-35385518
      function htmlToElement(html) {
        var template = document.createElement('template');
        html = html.trim();
        template.innerHTML = html;
        return template.content.firstChild;
      }

      var newElement = htmlToElement(newHtml);
      morphdom(this.element, newElement, {
        onBeforeElUpdated: function onBeforeElUpdated(fromEl, toEl) {
          // https://github.com/patrick-steele-idem/morphdom#can-i-make-morphdom-blaze-through-the-dom-tree-even-faster-yes
          if (fromEl.isEqualNode(toEl)) {
            return false;
          } // avoid updating child components: they will handle themselves


          if (fromEl.hasAttribute('data-controller') && fromEl.getAttribute('data-controller').split(' ').indexOf('live') !== -1 && fromEl !== _this8.element && !_this8._shouldChildLiveElementUpdate(fromEl, toEl)) {
            return false;
          }

          return true;
        }
      }); // restore the data-original-data attribute

      this._exposeOriginalData();
    }
  }, {
    key: "_initiatePolling",
    value: function _initiatePolling(rawPollConfig) {
      var _this9 = this;

      var directives = parseDirectives(rawPollConfig || '$render');
      directives.forEach(function (directive) {
        var duration = 2000;
        directive.modifiers.forEach(function (modifier) {
          switch (modifier.name) {
            case 'delay':
              if (modifier.value) {
                duration = modifier.value;
              }

              break;

            default:
              console.warn("Unknown modifier \"".concat(modifier.name, "\" in data-poll \"").concat(rawPollConfig, "\"."));
          }
        });

        _this9._startPoll(directive.action, duration);
      });
    }
  }, {
    key: "_startPoll",
    value: function _startPoll(actionName, duration) {
      var _this10 = this;

      var callback;

      if (actionName.charAt(0) === '$') {
        callback = function callback() {
          _this10[actionName]();
        };
      } else {
        callback = function callback() {
          _this10._makeRequest(actionName);
        };
      }

      this.pollingIntervals.push(setInterval(function () {
        callback();
      }, duration));
    }
  }, {
    key: "_dispatchEvent",
    value: function _dispatchEvent(name) {
      var payload = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
      var canBubble = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
      var cancelable = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
      var userEvent = new CustomEvent(name, {
        bubbles: canBubble,
        cancelable: cancelable,
        detail: payload
      });
      return this.element.dispatchEvent(userEvent);
    }
  }, {
    key: "_handleChildComponentUpdateModel",
    value: function _handleChildComponentUpdateModel(event) {
      var _this11 = this;

      var mainModelName = event.detail.modelName;
      var potentialModelNames = [{
        name: mainModelName,
        required: false
      }, {
        name: event.detail.extraModelName,
        required: false
      }];
      var modelMapElement = event.target.closest('[data-model-map]');

      if (this.element.contains(modelMapElement)) {
        var directives = parseDirectives(modelMapElement.dataset.modelMap);
        directives.forEach(function (directive) {
          var from = null;
          directive.modifiers.forEach(function (modifier) {
            switch (modifier.name) {
              case 'from':
                if (!modifier.value) {
                  throw new Error("The from() modifier requires a model name in data-model-map=\"".concat(modelMapElement.dataset.modelMap, "\""));
                }

                from = modifier.value;
                break;

              default:
                console.warn("Unknown modifier \"".concat(modifier.name, "\" in data-model-map=\"").concat(modelMapElement.dataset.modelMap, "\"."));
            }
          });

          if (!from) {
            throw new Error("Missing from() modifier in data-model-map=\"".concat(modelMapElement.dataset.modelMap, "\". The format should be \"from(childModelName)|parentModelName\""));
          } // only look maps for the model currently being updated


          if (from !== mainModelName) {
            return;
          }

          potentialModelNames.push({
            name: directive.action,
            required: true
          });
        });
      }

      potentialModelNames.reverse();
      var foundModelName = null;
      potentialModelNames.forEach(function (potentialModel) {
        if (foundModelName) {
          return;
        }

        if (doesDeepPropertyExist(_this11.dataValue, potentialModel.name)) {
          foundModelName = potentialModel.name;
          return;
        }

        if (potentialModel.required) {
          throw new Error("The model name \"".concat(potentialModel.name, "\" does not exist! Found in data-model-map=\"from(").concat(mainModelName, ")|").concat(potentialModel.name, "\""));
        }
      });

      if (!foundModelName) {
        return;
      }

      this.$updateModel(foundModelName, event.detail.value, false, null, {
        dispatch: false
      });
    }
    /**
     * Determines of a child live element should be re-rendered.
     *
     * This is called when this element re-renders and detects that
     * a child element is inside. Normally, in that case, we do not
     * re-render the child element. However, if we detect that the
     * "data" on the child element has changed from its initial data,
     * then this will trigger a re-render.
     *
     * @param {Element} fromEl
     * @param {Element} toEl
     * @return {boolean}
     */

  }, {
    key: "_shouldChildLiveElementUpdate",
    value: function _shouldChildLiveElementUpdate(fromEl, toEl) {
      return haveRenderedValuesChanged(fromEl.dataset.originalData, fromEl.dataset.liveDataValue, toEl.dataset.liveDataValue);
    }
  }, {
    key: "_exposeOriginalData",
    value: function _exposeOriginalData() {
      this.element.dataset.originalData = this.originalDataJSON;
    }
  }]);

  return _default;
}(Controller);
/**
 * Tracks the current "re-render" promises.
 */


_defineProperty(_default, "values", {
  url: String,
  data: Object,
  csrf: String,

  /**
   * The Debounce timeout.
   *
   * Default: 150
   */
  debounce: Number
});

var PromiseStack = /*#__PURE__*/function () {
  function PromiseStack() {
    _classCallCheck(this, PromiseStack);

    _defineProperty(this, "stack", []);
  }

  _createClass(PromiseStack, [{
    key: "addPromise",
    value: function addPromise(promise) {
      this.stack.push(promise);
    }
    /**
     * Removes the promise AND returns `true` if it is the most recent.
     *
     * @param {Promise} promise
     * @return {boolean}
     */

  }, {
    key: "removePromise",
    value: function removePromise(promise) {
      var index = this.findPromiseIndex(promise); // promise was not found - it was removed because a new Promise
      // already resolved before it

      if (index === -1) {
        return false;
      } // "save" whether this is the most recent or not


      var isMostRecent = this.stack.length === index + 1; // remove all promises starting from the oldest up through this one

      this.stack.splice(0, index + 1);
      return isMostRecent;
    }
  }, {
    key: "findPromiseIndex",
    value: function findPromiseIndex(promise) {
      return this.stack.findIndex(function (item) {
        return item === promise;
      });
    }
  }]);

  return PromiseStack;
}();

var parseLoadingAction = function parseLoadingAction(action, isLoading) {
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

  throw new Error("Unknown data-loading action \"".concat(action, "\""));
};

export default _default;
