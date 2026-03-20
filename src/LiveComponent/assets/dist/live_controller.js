import { Controller } from "@hotwired/stimulus";
var BackendRequest_default = class {
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
};
var RequestBuilder_default = class {
	constructor(url, method = "post", credentials = "same-origin") {
		this.url = url;
		this.method = method;
		this.credentials = credentials;
	}
	buildRequest(props, actions, updated, children, updatedPropsFromParent, files) {
		const splitUrl = this.url.split("?");
		let [url] = splitUrl;
		const [, queryString] = splitUrl;
		const params = new URLSearchParams(queryString || "");
		const fetchOptions = {};
		fetchOptions.credentials = this.credentials;
		fetchOptions.headers = {
			Accept: "application/vnd.live-component+html",
			"X-Requested-With": "XMLHttpRequest",
			"X-Live-Url": window.location.pathname + window.location.search
		};
		const totalFiles = Object.entries(files).reduce((total, current) => total + current.length, 0);
		const hasFingerprints = Object.keys(children).length > 0;
		if (actions.length === 0 && totalFiles === 0 && this.method === "get" && this.willDataFitInUrl(JSON.stringify(props), JSON.stringify(updated), params, JSON.stringify(children), JSON.stringify(updatedPropsFromParent))) {
			params.set("props", JSON.stringify(props));
			params.set("updated", JSON.stringify(updated));
			if (Object.keys(updatedPropsFromParent).length > 0) params.set("propsFromParent", JSON.stringify(updatedPropsFromParent));
			if (hasFingerprints) params.set("children", JSON.stringify(children));
			fetchOptions.method = "GET";
		} else {
			fetchOptions.method = "POST";
			const requestData = {
				props,
				updated
			};
			if (Object.keys(updatedPropsFromParent).length > 0) requestData.propsFromParent = updatedPropsFromParent;
			if (hasFingerprints) requestData.children = children;
			if (actions.length > 0) if (actions.length === 1) {
				requestData.args = actions[0].args;
				url += `/${encodeURIComponent(actions[0].name)}`;
			} else {
				url += "/_batch";
				requestData.actions = actions;
			}
			const formData = new FormData();
			formData.append("data", JSON.stringify(requestData));
			for (const [key, value] of Object.entries(files)) {
				const length = value.length;
				for (let i = 0; i < length; ++i) formData.append(key, value[i]);
			}
			fetchOptions.body = formData;
		}
		const paramsString = params.toString();
		return {
			url: `${url}${paramsString.length > 0 ? `?${paramsString}` : ""}`,
			fetchOptions
		};
	}
	willDataFitInUrl(propsJson, updatedJson, params, childrenJson, propsFromParentJson) {
		return (new URLSearchParams(propsJson + updatedJson + childrenJson + propsFromParentJson).toString() + params.toString()).length < 1500;
	}
};
var Backend_default = class {
	constructor(url, method = "post", credentials = "same-origin") {
		this.requestBuilder = new RequestBuilder_default(url, method, credentials);
	}
	makeRequest(props, actions, updated, children, updatedPropsFromParent, files) {
		const { url, fetchOptions } = this.requestBuilder.buildRequest(props, actions, updated, children, updatedPropsFromParent, files);
		return new BackendRequest_default(fetch(url, fetchOptions), actions.map((backendAction) => backendAction.name), Object.keys(updated));
	}
};
var BackendResponse_default = class {
	constructor(response) {
		this.response = response;
	}
	async getBody() {
		if (!this.body) this.body = await this.response.text();
		return this.body;
	}
	getLiveUrl() {
		if (void 0 === this.liveUrl) this.liveUrl = this.response.headers.get("X-Live-Url");
		return this.liveUrl;
	}
};
function getElementAsTagText(element) {
	return element.innerHTML ? element.outerHTML.slice(0, element.outerHTML.indexOf(element.innerHTML)) : element.outerHTML;
}
let componentMapByElement = /* @__PURE__ */ new WeakMap();
let componentMapByComponent = /* @__PURE__ */ new Map();
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
			reject(/* @__PURE__ */ new Error(`Component not found for element ${getElementAsTagText(element)}`));
		}
	}, 5);
});
const findComponents = (currentComponent, onlyParents, onlyMatchName) => {
	const components = [];
	componentMapByComponent.forEach((componentName, component) => {
		if (onlyParents && (currentComponent === component || !component.element.contains(currentComponent.element))) return;
		if (onlyMatchName && componentName !== onlyMatchName) return;
		components.push(component);
	});
	return components;
};
const findChildren = (currentComponent) => {
	const children = [];
	componentMapByComponent.forEach((componentName, component) => {
		if (currentComponent === component) return;
		if (!currentComponent.element.contains(component.element)) return;
		let foundChildComponent = false;
		componentMapByComponent.forEach((childComponentName, childComponent) => {
			if (foundChildComponent) return;
			if (childComponent === component) return;
			if (childComponent.element.contains(component.element)) foundChildComponent = true;
		});
		children.push(component);
	});
	return children;
};
const findParent = (currentComponent) => {
	let parentElement = currentComponent.element.parentElement;
	while (parentElement) {
		const component = componentMapByElement.get(parentElement);
		if (component) return component;
		parentElement = parentElement.parentElement;
	}
	return null;
};
function parseDirectives(content) {
	const directives = [];
	if (!content) return directives;
	let currentActionName = "";
	let currentArgumentValue = "";
	let currentArguments = [];
	let currentModifiers = [];
	let state = "action";
	const getLastActionName = () => {
		if (currentActionName) return currentActionName;
		if (directives.length === 0) throw new Error("Could not find any directives");
		return directives[directives.length - 1].action;
	};
	const pushInstruction = () => {
		directives.push({
			action: currentActionName,
			args: currentArguments,
			modifiers: currentModifiers,
			getString: () => {
				return content;
			}
		});
		currentActionName = "";
		currentArgumentValue = "";
		currentArguments = [];
		currentModifiers = [];
		state = "action";
	};
	const pushArgument = () => {
		currentArguments.push(currentArgumentValue.trim());
		currentArgumentValue = "";
	};
	const pushModifier = () => {
		if (currentArguments.length > 1) throw new Error(`The modifier "${currentActionName}()" does not support multiple arguments.`);
		currentModifiers.push({
			name: currentActionName,
			value: currentArguments.length > 0 ? currentArguments[0] : null
		});
		currentActionName = "";
		currentArguments = [];
		state = "action";
	};
	for (let i = 0; i < content.length; i++) {
		const char = content[i];
		switch (state) {
			case "action":
				if (char === "(") {
					state = "arguments";
					break;
				}
				if (char === " ") {
					if (currentActionName) pushInstruction();
					break;
				}
				if (char === "|") {
					pushModifier();
					break;
				}
				currentActionName += char;
				break;
			case "arguments":
				if (char === ")") {
					pushArgument();
					state = "after_arguments";
					break;
				}
				if (char === ",") {
					pushArgument();
					break;
				}
				currentArgumentValue += char;
				break;
			case "after_arguments":
				if (char === "|") {
					pushModifier();
					break;
				}
				if (char !== " ") throw new Error(`Missing space after ${getLastActionName()}()`);
				pushInstruction();
				break;
		}
	}
	switch (state) {
		case "action":
		case "after_arguments":
			if (currentActionName) pushInstruction();
			break;
		default: throw new Error(`Did you forget to add a closing ")" after "${currentActionName}"?`);
	}
	return directives;
}
function combineSpacedArray(parts) {
	const finalParts = [];
	parts.forEach((part) => {
		finalParts.push(...trimAll(part).split(" "));
	});
	return finalParts;
}
function trimAll(str) {
	return str.replace(/[\s]+/g, " ").trim();
}
function normalizeModelName(model) {
	return model.replace(/\[]$/, "").split("[").map((s) => s.replace("]", "")).join(".");
}
function getValueFromElement(element, valueStore) {
	if (element instanceof HTMLInputElement) {
		if (element.type === "checkbox") {
			const modelNameData = getModelDirectiveFromElement(element, false);
			if (modelNameData !== null) {
				const modelValue = valueStore.get(modelNameData.action);
				if (Array.isArray(modelValue)) return getMultipleCheckboxValue(element, modelValue);
				if (Object(modelValue) === modelValue) return getMultipleCheckboxValue(element, Object.values(modelValue));
			}
			if (element.hasAttribute("value")) return element.checked ? element.getAttribute("value") : null;
			return element.checked;
		}
		return inputValue(element);
	}
	if (element instanceof HTMLSelectElement) {
		if (element.multiple) return Array.from(element.selectedOptions).map((el) => el.value);
		return element.value;
	}
	if (element.hasAttribute("data-value")) return element.dataset.value;
	if ("value" in element) return element.value;
	if (element.hasAttribute("value")) return element.getAttribute("value");
	return null;
}
function setValueOnElement(element, value) {
	if (element instanceof HTMLInputElement) {
		if (element.type === "file") return;
		if (element.type === "radio") {
			element.checked = element.value == value;
			return;
		}
		if (element.type === "checkbox") {
			if (Array.isArray(value)) element.checked = value.some((val) => val == element.value);
			else if (element.hasAttribute("value")) element.checked = element.value == value;
			else element.checked = value;
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
	value = value === void 0 ? "" : value;
	element.value = value;
}
function getAllModelDirectiveFromElements(element) {
	if (!element.dataset.model) return [];
	const directives = parseDirectives(element.dataset.model);
	directives.forEach((directive) => {
		if (directive.args.length > 0) throw new Error(`The data-model="${element.dataset.model}" format is invalid: it does not support passing arguments to the model.`);
		directive.action = normalizeModelName(directive.action);
	});
	return directives;
}
function getModelDirectiveFromElement(element, throwOnMissing = true) {
	const dataModelDirectives = getAllModelDirectiveFromElements(element);
	if (dataModelDirectives.length > 0) return dataModelDirectives[0];
	if (element.getAttribute("name")) {
		const formElement = element.closest("form");
		if (formElement && "model" in formElement.dataset) {
			const directive = parseDirectives(formElement.dataset.model || "*")[0];
			if (directive.args.length > 0) throw new Error(`The data-model="${formElement.dataset.model}" format is invalid: it does not support passing arguments to the model.`);
			directive.action = normalizeModelName(element.getAttribute("name"));
			return directive;
		}
	}
	if (!throwOnMissing) return null;
	throw new Error(`Cannot determine the model name for "${getElementAsTagText(element)}": the element must either have a "data-model" (or "name" attribute living inside a <form data-model="*">).`);
}
function elementBelongsToThisComponent(element, component) {
	if (component.element === element) return true;
	if (!component.element.contains(element)) return false;
	return element.closest("[data-controller~=\"live\"]") === component.element;
}
function cloneHTMLElement(element) {
	const newElement = element.cloneNode(true);
	if (!(newElement instanceof HTMLElement)) throw new Error("Could not clone element");
	return newElement;
}
function htmlToElement(html) {
	const template = document.createElement("template");
	html = html.trim();
	template.innerHTML = html;
	if (template.content.childElementCount > 1) throw new Error(`Component HTML contains ${template.content.childElementCount} elements, but only 1 root element is allowed.`);
	const child = template.content.firstElementChild;
	if (!child) throw new Error("Child not found");
	if (!(child instanceof HTMLElement)) throw new Error(`Created element is not an HTMLElement: ${html.trim()}`);
	return child;
}
const getMultipleCheckboxValue = (element, currentValues) => {
	const finalValues = [...currentValues];
	const value = inputValue(element);
	const index = currentValues.indexOf(value);
	if (element.checked) {
		if (index === -1) finalValues.push(value);
		return finalValues;
	}
	if (index > -1) finalValues.splice(index, 1);
	return finalValues;
};
const inputValue = (element) => element.dataset.value ? element.dataset.value : element.value;
function isTextualInputElement(el) {
	return el instanceof HTMLInputElement && [
		"text",
		"email",
		"password",
		"search",
		"tel",
		"url"
	].includes(el.type);
}
function isTextareaElement(el) {
	return el instanceof HTMLTextAreaElement;
}
function isNumericalInputElement(element) {
	return element instanceof HTMLInputElement && ["number", "range"].includes(element.type);
}
var HookManager_default = class {
	constructor() {
		this.hooks = /* @__PURE__ */ new Map();
	}
	register(hookName, callback) {
		const hooks = this.hooks.get(hookName) || [];
		hooks.push(callback);
		this.hooks.set(hookName, hooks);
	}
	unregister(hookName, callback) {
		const hooks = this.hooks.get(hookName) || [];
		const index = hooks.indexOf(callback);
		if (index === -1) return;
		hooks.splice(index, 1);
		this.hooks.set(hookName, hooks);
	}
	triggerHook(hookName, ...args) {
		(this.hooks.get(hookName) || []).forEach((callback) => {
			callback(...args);
		});
	}
};
var Idiomorph = (function() {
	"use strict";
	let EMPTY_SET = /* @__PURE__ */ new Set();
	let defaults = {
		morphStyle: "outerHTML",
		callbacks: {
			beforeNodeAdded: noOp,
			afterNodeAdded: noOp,
			beforeNodeMorphed: noOp,
			afterNodeMorphed: noOp,
			beforeNodeRemoved: noOp,
			afterNodeRemoved: noOp,
			beforeAttributeUpdated: noOp
		},
		head: {
			style: "merge",
			shouldPreserve: function(elt) {
				return elt.getAttribute("im-preserve") === "true";
			},
			shouldReAppend: function(elt) {
				return elt.getAttribute("im-re-append") === "true";
			},
			shouldRemove: noOp,
			afterHeadMorphed: noOp
		}
	};
	function morph(oldNode, newContent, config = {}) {
		if (oldNode instanceof Document) oldNode = oldNode.documentElement;
		if (typeof newContent === "string") newContent = parseContent(newContent);
		let normalizedContent = normalizeContent(newContent);
		let ctx = createMorphContext(oldNode, normalizedContent, config);
		return morphNormalizedContent(oldNode, normalizedContent, ctx);
	}
	function morphNormalizedContent(oldNode, normalizedNewContent, ctx) {
		if (ctx.head.block) {
			let oldHead = oldNode.querySelector("head");
			let newHead = normalizedNewContent.querySelector("head");
			if (oldHead && newHead) {
				let promises = handleHeadElement(newHead, oldHead, ctx);
				Promise.all(promises).then(function() {
					morphNormalizedContent(oldNode, normalizedNewContent, Object.assign(ctx, { head: {
						block: false,
						ignore: true
					} }));
				});
				return;
			}
		}
		if (ctx.morphStyle === "innerHTML") {
			morphChildren(normalizedNewContent, oldNode, ctx);
			return oldNode.children;
		} else if (ctx.morphStyle === "outerHTML" || ctx.morphStyle == null) {
			let bestMatch = findBestNodeMatch(normalizedNewContent, oldNode, ctx);
			let previousSibling = bestMatch?.previousSibling;
			let nextSibling = bestMatch?.nextSibling;
			let morphedNode = morphOldNodeTo(oldNode, bestMatch, ctx);
			if (bestMatch) return insertSiblings(previousSibling, morphedNode, nextSibling);
			else return [];
		} else throw "Do not understand how to morph style " + ctx.morphStyle;
	}
	function ignoreValueOfActiveElement(possibleActiveElement, ctx) {
		return ctx.ignoreActiveValue && possibleActiveElement === document.activeElement;
	}
	function morphOldNodeTo(oldNode, newContent, ctx) {
		if (ctx.ignoreActive && oldNode === document.activeElement) {} else if (newContent == null) {
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
			if (oldNode instanceof HTMLHeadElement && ctx.head.ignore) {} else if (oldNode instanceof HTMLHeadElement && ctx.head.style !== "morph") handleHeadElement(newContent, oldNode, ctx);
			else {
				syncNodeFrom(newContent, oldNode, ctx);
				if (!ignoreValueOfActiveElement(oldNode, ctx)) morphChildren(newContent, oldNode, ctx);
			}
			ctx.callbacks.afterNodeMorphed(oldNode, newContent);
			return oldNode;
		}
	}
	function morphChildren(newParent, oldParent, ctx) {
		let nextNewChild = newParent.firstChild;
		let insertionPoint = oldParent.firstChild;
		let newChild;
		while (nextNewChild) {
			newChild = nextNewChild;
			nextNewChild = newChild.nextSibling;
			if (insertionPoint == null) {
				if (ctx.callbacks.beforeNodeAdded(newChild) === false) return;
				oldParent.appendChild(newChild);
				ctx.callbacks.afterNodeAdded(newChild);
				removeIdsFromConsideration(ctx, newChild);
				continue;
			}
			if (isIdSetMatch(newChild, insertionPoint, ctx)) {
				morphOldNodeTo(insertionPoint, newChild, ctx);
				insertionPoint = insertionPoint.nextSibling;
				removeIdsFromConsideration(ctx, newChild);
				continue;
			}
			let idSetMatch = findIdSetMatch(newParent, oldParent, newChild, insertionPoint, ctx);
			if (idSetMatch) {
				insertionPoint = removeNodesBetween(insertionPoint, idSetMatch, ctx);
				morphOldNodeTo(idSetMatch, newChild, ctx);
				removeIdsFromConsideration(ctx, newChild);
				continue;
			}
			let softMatch = findSoftMatch(newParent, oldParent, newChild, insertionPoint, ctx);
			if (softMatch) {
				insertionPoint = removeNodesBetween(insertionPoint, softMatch, ctx);
				morphOldNodeTo(softMatch, newChild, ctx);
				removeIdsFromConsideration(ctx, newChild);
				continue;
			}
			if (ctx.callbacks.beforeNodeAdded(newChild) === false) return;
			oldParent.insertBefore(newChild, insertionPoint);
			ctx.callbacks.afterNodeAdded(newChild);
			removeIdsFromConsideration(ctx, newChild);
		}
		while (insertionPoint !== null) {
			let tempNode = insertionPoint;
			insertionPoint = insertionPoint.nextSibling;
			removeNode(tempNode, ctx);
		}
	}
	function ignoreAttribute(attr, to, updateType, ctx) {
		if (attr === "value" && ctx.ignoreActiveValue && to === document.activeElement) return true;
		return ctx.callbacks.beforeAttributeUpdated(attr, to, updateType) === false;
	}
	function syncNodeFrom(from, to, ctx) {
		let type = from.nodeType;
		if (type === 1) {
			const fromAttributes = from.attributes;
			const toAttributes = to.attributes;
			for (const fromAttribute of fromAttributes) {
				if (ignoreAttribute(fromAttribute.name, to, "update", ctx)) continue;
				if (to.getAttribute(fromAttribute.name) !== fromAttribute.value) to.setAttribute(fromAttribute.name, fromAttribute.value);
			}
			for (let i = toAttributes.length - 1; 0 <= i; i--) {
				const toAttribute = toAttributes[i];
				if (ignoreAttribute(toAttribute.name, to, "remove", ctx)) continue;
				if (!from.hasAttribute(toAttribute.name)) to.removeAttribute(toAttribute.name);
			}
		}
		if (type === 8 || type === 3) {
			if (to.nodeValue !== from.nodeValue) to.nodeValue = from.nodeValue;
		}
		if (!ignoreValueOfActiveElement(to, ctx)) syncInputValue(from, to, ctx);
	}
	function syncBooleanAttribute(from, to, attributeName, ctx) {
		if (from[attributeName] !== to[attributeName]) {
			let ignoreUpdate = ignoreAttribute(attributeName, to, "update", ctx);
			if (!ignoreUpdate) to[attributeName] = from[attributeName];
			if (from[attributeName]) {
				if (!ignoreUpdate) to.setAttribute(attributeName, from[attributeName]);
			} else if (!ignoreAttribute(attributeName, to, "remove", ctx)) to.removeAttribute(attributeName);
		}
	}
	function syncInputValue(from, to, ctx) {
		if (from instanceof HTMLInputElement && to instanceof HTMLInputElement && from.type !== "file") {
			let fromValue = from.value;
			let toValue = to.value;
			syncBooleanAttribute(from, to, "checked", ctx);
			syncBooleanAttribute(from, to, "disabled", ctx);
			if (!from.hasAttribute("value")) {
				if (!ignoreAttribute("value", to, "remove", ctx)) {
					to.value = "";
					to.removeAttribute("value");
				}
			} else if (fromValue !== toValue) {
				if (!ignoreAttribute("value", to, "update", ctx)) {
					to.setAttribute("value", fromValue);
					to.value = fromValue;
				}
			}
		} else if (from instanceof HTMLOptionElement) syncBooleanAttribute(from, to, "selected", ctx);
		else if (from instanceof HTMLTextAreaElement && to instanceof HTMLTextAreaElement) {
			let fromValue = from.value;
			let toValue = to.value;
			if (ignoreAttribute("value", to, "update", ctx)) return;
			if (fromValue !== toValue) to.value = fromValue;
			if (to.firstChild && to.firstChild.nodeValue !== fromValue) to.firstChild.nodeValue = fromValue;
		}
	}
	function handleHeadElement(newHeadTag, currentHead, ctx) {
		let added = [];
		let removed = [];
		let preserved = [];
		let nodesToAppend = [];
		let headMergeStyle = ctx.head.style;
		let srcToNewHeadNodes = /* @__PURE__ */ new Map();
		for (const newHeadChild of newHeadTag.children) srcToNewHeadNodes.set(newHeadChild.outerHTML, newHeadChild);
		for (const currentHeadElt of currentHead.children) {
			let inNewContent = srcToNewHeadNodes.has(currentHeadElt.outerHTML);
			let isReAppended = ctx.head.shouldReAppend(currentHeadElt);
			let isPreserved = ctx.head.shouldPreserve(currentHeadElt);
			if (inNewContent || isPreserved) if (isReAppended) removed.push(currentHeadElt);
			else {
				srcToNewHeadNodes.delete(currentHeadElt.outerHTML);
				preserved.push(currentHeadElt);
			}
			else if (headMergeStyle === "append") {
				if (isReAppended) {
					removed.push(currentHeadElt);
					nodesToAppend.push(currentHeadElt);
				}
			} else if (ctx.head.shouldRemove(currentHeadElt) !== false) removed.push(currentHeadElt);
		}
		nodesToAppend.push(...srcToNewHeadNodes.values());
		log("to append: ", nodesToAppend);
		let promises = [];
		for (const newNode of nodesToAppend) {
			log("adding: ", newNode);
			let newElt = document.createRange().createContextualFragment(newNode.outerHTML).firstChild;
			log(newElt);
			if (ctx.callbacks.beforeNodeAdded(newElt) !== false) {
				if (newElt.href || newElt.src) {
					let resolve = null;
					let promise = new Promise(function(_resolve) {
						resolve = _resolve;
					});
					newElt.addEventListener("load", function() {
						resolve();
					});
					promises.push(promise);
				}
				currentHead.appendChild(newElt);
				ctx.callbacks.afterNodeAdded(newElt);
				added.push(newElt);
			}
		}
		for (const removedElement of removed) if (ctx.callbacks.beforeNodeRemoved(removedElement) !== false) {
			currentHead.removeChild(removedElement);
			ctx.callbacks.afterNodeRemoved(removedElement);
		}
		ctx.head.afterHeadMorphed(currentHead, {
			added,
			kept: preserved,
			removed
		});
		return promises;
	}
	function log() {}
	function noOp() {}
	function mergeDefaults(config) {
		let finalConfig = {};
		Object.assign(finalConfig, defaults);
		Object.assign(finalConfig, config);
		finalConfig.callbacks = {};
		Object.assign(finalConfig.callbacks, defaults.callbacks);
		Object.assign(finalConfig.callbacks, config.callbacks);
		finalConfig.head = {};
		Object.assign(finalConfig.head, defaults.head);
		Object.assign(finalConfig.head, config.head);
		return finalConfig;
	}
	function createMorphContext(oldNode, newContent, config) {
		config = mergeDefaults(config);
		return {
			target: oldNode,
			newContent,
			config,
			morphStyle: config.morphStyle,
			ignoreActive: config.ignoreActive,
			ignoreActiveValue: config.ignoreActiveValue,
			idMap: createIdMap(oldNode, newContent),
			deadIds: /* @__PURE__ */ new Set(),
			callbacks: config.callbacks,
			head: config.head
		};
	}
	function isIdSetMatch(node1, node2, ctx) {
		if (node1 == null || node2 == null) return false;
		if (node1.nodeType === node2.nodeType && node1.tagName === node2.tagName) if (node1.id !== "" && node1.id === node2.id) return true;
		else return getIdIntersectionCount(ctx, node1, node2) > 0;
		return false;
	}
	function isSoftMatch(node1, node2) {
		if (node1 == null || node2 == null) return false;
		return node1.nodeType === node2.nodeType && node1.tagName === node2.tagName;
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
	function findIdSetMatch(newContent, oldParent, newChild, insertionPoint, ctx) {
		let newChildPotentialIdCount = getIdIntersectionCount(ctx, newChild, oldParent);
		let potentialMatch = null;
		if (newChildPotentialIdCount > 0) {
			let potentialMatch = insertionPoint;
			let otherMatchCount = 0;
			while (potentialMatch != null) {
				if (isIdSetMatch(newChild, potentialMatch, ctx)) return potentialMatch;
				otherMatchCount += getIdIntersectionCount(ctx, potentialMatch, newContent);
				if (otherMatchCount > newChildPotentialIdCount) return null;
				potentialMatch = potentialMatch.nextSibling;
			}
		}
		return potentialMatch;
	}
	function findSoftMatch(newContent, oldParent, newChild, insertionPoint, ctx) {
		let potentialSoftMatch = insertionPoint;
		let nextSibling = newChild.nextSibling;
		let siblingSoftMatchCount = 0;
		while (potentialSoftMatch != null) {
			if (getIdIntersectionCount(ctx, potentialSoftMatch, newContent) > 0) return null;
			if (isSoftMatch(newChild, potentialSoftMatch)) return potentialSoftMatch;
			if (isSoftMatch(nextSibling, potentialSoftMatch)) {
				siblingSoftMatchCount++;
				nextSibling = nextSibling.nextSibling;
				if (siblingSoftMatchCount >= 2) return null;
			}
			potentialSoftMatch = potentialSoftMatch.nextSibling;
		}
		return potentialSoftMatch;
	}
	function parseContent(newContent) {
		let parser = new DOMParser();
		let contentWithSvgsRemoved = newContent.replace(/<svg(\s[^>]*>|>)([\s\S]*?)<\/svg>/gim, "");
		if (contentWithSvgsRemoved.match(/<\/html>/) || contentWithSvgsRemoved.match(/<\/head>/) || contentWithSvgsRemoved.match(/<\/body>/)) {
			let content = parser.parseFromString(newContent, "text/html");
			if (contentWithSvgsRemoved.match(/<\/html>/)) {
				content.generatedByIdiomorph = true;
				return content;
			} else {
				let htmlElement = content.firstChild;
				if (htmlElement) {
					htmlElement.generatedByIdiomorph = true;
					return htmlElement;
				} else return null;
			}
		} else {
			let content = parser.parseFromString("<body><template>" + newContent + "</template></body>", "text/html").body.querySelector("template").content;
			content.generatedByIdiomorph = true;
			return content;
		}
	}
	function normalizeContent(newContent) {
		if (newContent == null) return document.createElement("div");
		else if (newContent.generatedByIdiomorph) return newContent;
		else if (newContent instanceof Node) {
			const dummyParent = document.createElement("div");
			dummyParent.append(newContent);
			return dummyParent;
		} else {
			const dummyParent = document.createElement("div");
			for (const elt of [...newContent]) dummyParent.append(elt);
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
			added.push(node);
			morphedNode.parentElement.insertBefore(node, morphedNode);
		}
		added.push(morphedNode);
		while (nextSibling != null) {
			stack.push(nextSibling);
			added.push(nextSibling);
			nextSibling = nextSibling.nextSibling;
		}
		while (stack.length > 0) morphedNode.parentElement.insertBefore(stack.pop(), morphedNode.nextSibling);
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
		if (isSoftMatch(node1, node2)) return .5 + getIdIntersectionCount(ctx, node1, node2);
		return 0;
	}
	function removeNode(tempNode, ctx) {
		removeIdsFromConsideration(ctx, tempNode);
		if (ctx.callbacks.beforeNodeRemoved(tempNode) === false) return;
		tempNode.remove();
		ctx.callbacks.afterNodeRemoved(tempNode);
	}
	function isIdInConsideration(ctx, id) {
		return !ctx.deadIds.has(id);
	}
	function idIsWithinNode(ctx, id, targetNode) {
		return (ctx.idMap.get(targetNode) || EMPTY_SET).has(id);
	}
	function removeIdsFromConsideration(ctx, node) {
		let idSet = ctx.idMap.get(node) || EMPTY_SET;
		for (const id of idSet) ctx.deadIds.add(id);
	}
	function getIdIntersectionCount(ctx, node1, node2) {
		let sourceSet = ctx.idMap.get(node1) || EMPTY_SET;
		let matchCount = 0;
		for (const id of sourceSet) if (isIdInConsideration(ctx, id) && idIsWithinNode(ctx, id, node2)) ++matchCount;
		return matchCount;
	}
	function populateIdMapForNode(node, idMap) {
		let nodeParent = node.parentElement;
		let idElements = node.querySelectorAll("[id]");
		for (const elt of idElements) {
			let current = elt;
			while (current !== nodeParent && current != null) {
				let idSet = idMap.get(current);
				if (idSet == null) {
					idSet = /* @__PURE__ */ new Set();
					idMap.set(current, idSet);
				}
				idSet.add(elt.id);
				current = current.parentElement;
			}
		}
	}
	function createIdMap(oldContent, newContent) {
		let idMap = /* @__PURE__ */ new Map();
		populateIdMapForNode(oldContent, idMap);
		populateIdMapForNode(newContent, idMap);
		return idMap;
	}
	return {
		morph,
		defaults
	};
})();
function normalizeAttributesForComparison(element) {
	if (!(element instanceof HTMLInputElement && element.type === "file")) {
		if ("value" in element) element.setAttribute("value", element.value);
		else if (element.hasAttribute("value")) element.setAttribute("value", "");
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
	const originalElementsToPreserve = /* @__PURE__ */ new Map();
	const markElementAsNeedingPostMorphSwap = (id, replaceWithClone) => {
		const oldElement = originalElementsToPreserve.get(id);
		if (!(oldElement instanceof HTMLElement)) throw new Error(`Original element with id ${id} not found`);
		originalElementIdsToSwapAfter.push(id);
		if (!replaceWithClone) return null;
		const clonedOldElement = cloneHTMLElement(oldElement);
		oldElement.replaceWith(clonedOldElement);
		return clonedOldElement;
	};
	rootToElement.querySelectorAll("[data-live-preserve]").forEach((newElement) => {
		const id = newElement.id;
		if (!id) throw new Error("The data-live-preserve attribute requires an id attribute to be set on the element");
		const oldElement = rootFromElement.querySelector(`#${id}`);
		if (!(oldElement instanceof HTMLElement)) throw new Error(`The element with id "${id}" was not found in the original HTML`);
		newElement.removeAttribute("data-live-preserve");
		originalElementsToPreserve.set(id, oldElement);
		syncAttributes(newElement, oldElement);
	});
	Idiomorph.morph(rootFromElement, rootToElement, { callbacks: {
		beforeNodeMorphed: (fromEl, toEl) => {
			if (!(fromEl instanceof Element) || !(toEl instanceof Element)) return true;
			if (fromEl === rootFromElement) return true;
			if (fromEl.id && originalElementsToPreserve.has(fromEl.id)) {
				if (fromEl.id === toEl.id) return false;
				const clonedFromEl = markElementAsNeedingPostMorphSwap(fromEl.id, true);
				if (!clonedFromEl) throw new Error("missing clone");
				Idiomorph.morph(clonedFromEl, toEl);
				return false;
			}
			if (fromEl instanceof HTMLElement && toEl instanceof HTMLElement) {
				if (typeof fromEl.__x !== "undefined") {
					if (!window.Alpine) throw new Error("Unable to access Alpine.js though the global window.Alpine variable. Please make sure Alpine.js is loaded before Symfony UX LiveComponent.");
					if (typeof window.Alpine.morph !== "function") throw new Error("Unable to access Alpine.js morph function. Please make sure the Alpine.js Morph plugin is installed and loaded, see https://alpinejs.dev/plugins/morph for more information.");
					window.Alpine.morph(fromEl.__x, toEl);
				}
				if (externalMutationTracker.wasElementAdded(fromEl)) {
					fromEl.insertAdjacentElement("afterend", toEl);
					return false;
				}
				if (modifiedFieldElements.includes(fromEl)) setValueOnElement(toEl, getElementValue(fromEl));
				if (fromEl === document.activeElement && fromEl !== document.body && null !== getModelDirectiveFromElement(fromEl, false)) setValueOnElement(toEl, getElementValue(fromEl));
				const elementChanges = externalMutationTracker.getChangedElement(fromEl);
				if (elementChanges) elementChanges.applyToElement(toEl);
				if (fromEl.nodeName.toUpperCase() !== "OPTION" && fromEl.isEqualNode(toEl)) {
					const normalizedFromEl = cloneHTMLElement(fromEl);
					normalizeAttributesForComparison(normalizedFromEl);
					const normalizedToEl = cloneHTMLElement(toEl);
					normalizeAttributesForComparison(normalizedToEl);
					if (normalizedFromEl.isEqualNode(normalizedToEl)) return false;
				}
			}
			if (fromEl.hasAttribute("data-skip-morph") || fromEl.id && fromEl.id !== toEl.id) {
				fromEl.innerHTML = toEl.innerHTML;
				return true;
			}
			if (fromEl.parentElement?.hasAttribute("data-skip-morph")) return false;
			return !fromEl.hasAttribute("data-live-ignore");
		},
		beforeNodeRemoved(node) {
			if (!(node instanceof HTMLElement)) return true;
			if (node.id && originalElementsToPreserve.has(node.id)) {
				markElementAsNeedingPostMorphSwap(node.id, false);
				return true;
			}
			if (externalMutationTracker.wasElementAdded(node)) return false;
			return !node.hasAttribute("data-live-ignore");
		}
	} });
	originalElementIdsToSwapAfter.forEach((id) => {
		const newElement = rootFromElement.querySelector(`#${id}`);
		const originalElement = originalElementsToPreserve.get(id);
		if (!(newElement instanceof HTMLElement) || !(originalElement instanceof HTMLElement)) throw new Error("Missing elements.");
		newElement.replaceWith(originalElement);
	});
}
var ChangingItemsTracker_default = class {
	constructor() {
		this.changedItems = /* @__PURE__ */ new Map();
		this.removedItems = /* @__PURE__ */ new Map();
	}
	setItem(itemName, newValue, previousValue) {
		if (this.removedItems.has(itemName)) {
			const removedRecord = this.removedItems.get(itemName);
			this.removedItems.delete(itemName);
			if (removedRecord.original === newValue) return;
		}
		if (this.changedItems.has(itemName)) {
			const originalRecord = this.changedItems.get(itemName);
			if (originalRecord.original === newValue) {
				this.changedItems.delete(itemName);
				return;
			}
			this.changedItems.set(itemName, {
				original: originalRecord.original,
				new: newValue
			});
			return;
		}
		this.changedItems.set(itemName, {
			original: previousValue,
			new: newValue
		});
	}
	removeItem(itemName, currentValue) {
		let trueOriginalValue = currentValue;
		if (this.changedItems.has(itemName)) {
			trueOriginalValue = this.changedItems.get(itemName).original;
			this.changedItems.delete(itemName);
			if (trueOriginalValue === null) return;
		}
		if (!this.removedItems.has(itemName)) this.removedItems.set(itemName, { original: trueOriginalValue });
	}
	getChangedItems() {
		return Array.from(this.changedItems, ([name, { new: value }]) => ({
			name,
			value
		}));
	}
	getRemovedItems() {
		return Array.from(this.removedItems.keys());
	}
	isEmpty() {
		return this.changedItems.size === 0 && this.removedItems.size === 0;
	}
};
var ElementChanges = class {
	constructor() {
		this.addedClasses = /* @__PURE__ */ new Set();
		this.removedClasses = /* @__PURE__ */ new Set();
		this.styleChanges = new ChangingItemsTracker_default();
		this.attributeChanges = new ChangingItemsTracker_default();
	}
	addClass(className) {
		if (!this.removedClasses.delete(className)) this.addedClasses.add(className);
	}
	removeClass(className) {
		if (!this.addedClasses.delete(className)) this.removedClasses.add(className);
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
			if (/!\s*important/i.test(change.value)) element.style.setProperty(change.name, change.value.replace(/!\s*important/i, "").trim(), "important");
			else element.style.setProperty(change.name, change.value);
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
		return this.addedClasses.size === 0 && this.removedClasses.size === 0 && this.styleChanges.isEmpty() && this.attributeChanges.isEmpty();
	}
};
var ExternalMutationTracker_default = class {
	constructor(element, shouldTrackChangeCallback) {
		this.changedElements = /* @__PURE__ */ new WeakMap();
		this.changedElementsCount = 0;
		this.addedElements = [];
		this.removedElements = [];
		this.isStarted = false;
		this.element = element;
		this.shouldTrackChangeCallback = shouldTrackChangeCallback;
		this.mutationObserver = new MutationObserver(this.onMutations.bind(this));
	}
	start() {
		if (this.isStarted) return;
		this.mutationObserver.observe(this.element, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeOldValue: true
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
		const handledAttributeMutations = /* @__PURE__ */ new WeakMap();
		for (const mutation of mutations) {
			const element = mutation.target;
			if (!this.shouldTrackChangeCallback(element)) continue;
			if (this.isElementAddedByTranslation(element)) continue;
			let isChangeInAddedElement = false;
			for (const addedElement of this.addedElements) if (addedElement.contains(element)) {
				isChangeInAddedElement = true;
				break;
			}
			if (isChangeInAddedElement) continue;
			switch (mutation.type) {
				case "childList":
					this.handleChildListMutation(mutation);
					break;
				case "attributes":
					if (!handledAttributeMutations.has(element)) handledAttributeMutations.set(element, []);
					if (!handledAttributeMutations.get(element).includes(mutation.attributeName)) {
						this.handleAttributeMutation(mutation);
						handledAttributeMutations.set(element, [...handledAttributeMutations.get(element), mutation.attributeName]);
					}
					break;
			}
		}
	}
	handleChildListMutation(mutation) {
		mutation.addedNodes.forEach((node) => {
			if (!(node instanceof Element)) return;
			if (this.removedElements.includes(node)) {
				this.removedElements.splice(this.removedElements.indexOf(node), 1);
				return;
			}
			if (this.isElementAddedByTranslation(node)) return;
			this.addedElements.push(node);
		});
		mutation.removedNodes.forEach((node) => {
			if (!(node instanceof Element)) return;
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
			case "class":
				this.handleClassAttributeMutation(mutation, changedElement);
				break;
			case "style":
				this.handleStyleAttributeMutation(mutation, changedElement);
				break;
			default: this.handleGenericAttributeMutation(mutation, changedElement);
		}
		if (changedElement.isEmpty()) {
			this.changedElements.delete(element);
			this.changedElementsCount--;
		}
	}
	handleClassAttributeMutation(mutation, elementChanges) {
		const element = mutation.target;
		const previousValues = (mutation.oldValue || "").match(/(\S+)/gu) || [];
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
		const previousValue = mutation.oldValue || "";
		const previousStyles = this.extractStyles(previousValue);
		const newValue = element.getAttribute("style") || "";
		const newStyles = this.extractStyles(newValue);
		const addedOrChangedStyles = Object.keys(newStyles).filter((key) => previousStyles[key] === void 0 || previousStyles[key] !== newStyles[key]);
		const removedStyles = Object.keys(previousStyles).filter((key) => !newStyles[key]);
		addedOrChangedStyles.forEach((style) => {
			elementChanges.addStyle(style, newStyles[style], previousStyles[style] === void 0 ? null : previousStyles[style]);
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
		if (oldValue === attributeName) oldValue = "";
		if (newValue === attributeName) newValue = "";
		if (!element.hasAttribute(attributeName)) {
			if (oldValue === null) return;
			elementChanges.removeAttribute(attributeName, mutation.oldValue);
			return;
		}
		if (newValue === oldValue) return;
		elementChanges.addAttribute(attributeName, element.getAttribute(attributeName), mutation.oldValue);
	}
	extractStyles(styles) {
		const styleObject = {};
		styles.split(";").forEach((style) => {
			const parts = style.split(":");
			if (parts.length === 1) return;
			const property = parts[0].trim();
			styleObject[property] = parts.slice(1).join(":").trim();
		});
		return styleObject;
	}
	isElementAddedByTranslation(element) {
		return element.tagName === "FONT" && element.getAttribute("style") === "vertical-align: inherit;";
	}
};
var UnsyncedInputsTracker_default = class {
	constructor(component, modelElementResolver) {
		this.elementEventListeners = [{
			event: "input",
			callback: (event) => this.handleInputEvent(event)
		}];
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
		if (!target) return;
		this.updateModelFromElement(target);
	}
	updateModelFromElement(element) {
		if (!elementBelongsToThisComponent(element, this.component)) return;
		if (!(element instanceof HTMLElement)) throw new Error("Could not update model for non HTMLElement");
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
};
var UnsyncedInputContainer = class {
	constructor() {
		this.unsyncedNonModelFields = [];
		this.unsyncedModelNames = [];
		this.unsyncedModelFields = /* @__PURE__ */ new Map();
	}
	add(element, modelName = null) {
		if (modelName) {
			this.unsyncedModelFields.set(modelName, element);
			if (!this.unsyncedModelNames.includes(modelName)) this.unsyncedModelNames.push(modelName);
			return;
		}
		this.unsyncedNonModelFields.push(element);
	}
	resetUnsyncedFields() {
		this.unsyncedModelFields.forEach((value, key) => {
			if (!this.unsyncedModelNames.includes(key)) this.unsyncedModelFields.delete(key);
		});
	}
	allUnsyncedInputs() {
		return [...this.unsyncedNonModelFields, ...this.unsyncedModelFields.values()];
	}
	markModelAsSynced(modelName) {
		const index = this.unsyncedModelNames.indexOf(modelName);
		if (index !== -1) this.unsyncedModelNames.splice(index, 1);
	}
	getUnsyncedModelNames() {
		return this.unsyncedModelNames;
	}
};
function getDeepData(data, propertyPath) {
	const { currentLevelData, finalKey } = parseDeepData(data, propertyPath);
	if (currentLevelData === void 0) return;
	return currentLevelData[finalKey];
}
const parseDeepData = (data, propertyPath) => {
	const finalData = JSON.parse(JSON.stringify(data));
	let currentLevelData = finalData;
	const parts = propertyPath.split(".");
	for (let i = 0; i < parts.length - 1; i++) currentLevelData = currentLevelData[parts[i]];
	const finalKey = parts[parts.length - 1];
	return {
		currentLevelData,
		finalData,
		finalKey,
		parts
	};
};
var ValueStore_default = class {
	constructor(props) {
		this.props = {};
		this.dirtyProps = {};
		this.pendingProps = {};
		this.updatedPropsFromParent = {};
		this.props = props;
	}
	get(name) {
		const normalizedName = normalizeModelName(name);
		if (this.dirtyProps[normalizedName] !== void 0) return this.dirtyProps[normalizedName];
		if (this.pendingProps[normalizedName] !== void 0) return this.pendingProps[normalizedName];
		if (this.props[normalizedName] !== void 0) return this.props[normalizedName];
		return getDeepData(this.props, normalizedName);
	}
	has(name) {
		return this.get(name) !== void 0;
	}
	set(name, value) {
		const normalizedName = normalizeModelName(name);
		if (this.get(normalizedName) === value) return false;
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
		this.dirtyProps = {
			...this.pendingProps,
			...this.dirtyProps
		};
		this.pendingProps = {};
	}
	storeNewPropsFromParent(props) {
		let changed = false;
		for (const [key, value] of Object.entries(props)) if (this.get(key) !== value) changed = true;
		if (changed) this.updatedPropsFromParent = props;
		return changed;
	}
};
var Component = class {
	constructor(element, name, props, listeners, id, backend, elementDriver) {
		this.fingerprint = "";
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
		this.listeners = /* @__PURE__ */ new Map();
		listeners.forEach((listener) => {
			if (!this.listeners.has(listener.event)) this.listeners.set(listener.event, []);
			this.listeners.get(listener.event)?.push(listener.action);
		});
		this.valueStore = new ValueStore_default(props);
		this.unsyncedInputsTracker = new UnsyncedInputsTracker_default(this, elementDriver);
		this.hooks = new HookManager_default();
		this.resetPromise();
		this.externalMutationTracker = new ExternalMutationTracker_default(this.element, (element) => elementBelongsToThisComponent(element, this));
		this.externalMutationTracker.start();
	}
	addPlugin(plugin) {
		plugin.attachToComponent(this);
	}
	connect() {
		registerComponent(this);
		this.hooks.triggerHook("connect", this);
		this.unsyncedInputsTracker.activate();
		this.externalMutationTracker.start();
	}
	disconnect() {
		unregisterComponent(this);
		this.hooks.triggerHook("disconnect", this);
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
		if (!this.valueStore.has(modelName)) throw new Error(`Invalid model name "${model}".`);
		const isChanged = this.valueStore.set(modelName, value);
		this.hooks.triggerHook("model:set", model, value, this);
		this.unsyncedInputsTracker.markModelAsSynced(modelName);
		if (reRender && isChanged) this.debouncedStartRequest(debounce);
		return promise;
	}
	getData(model) {
		const modelName = normalizeModelName(model);
		if (!this.valueStore.has(modelName)) throw new Error(`Invalid model "${model}".`);
		return this.valueStore.get(modelName);
	}
	action(name, args = {}, debounce = false) {
		const promise = this.nextRequestPromise;
		this.pendingActions.push({
			name,
			args
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
		findComponents(this, emitUp, matchingName).forEach((component) => {
			component.doEmit(name, data);
		});
	}
	doEmit(name, data) {
		if (!this.listeners.has(name)) return;
		(this.listeners.get(name) || []).forEach((action) => {
			this.action(action, data, 1);
		});
	}
	isTurboEnabled() {
		return typeof Turbo !== "undefined" && !this.element.closest("[data-turbo=\"false\"]");
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
		for (const [key, value] of Object.entries(this.pendingFiles)) if (value.files) filesToSend[key] = value.files;
		const requestConfig = {
			props: this.valueStore.getOriginalProps(),
			actions: this.pendingActions,
			updated: this.valueStore.getDirtyProps(),
			children: {},
			updatedPropsFromParent: this.valueStore.getUpdatedPropsFromParent(),
			files: filesToSend
		};
		this.hooks.triggerHook("request:started", requestConfig);
		this.backendRequest = this.backend.makeRequest(requestConfig.props, requestConfig.actions, requestConfig.updated, requestConfig.children, requestConfig.updatedPropsFromParent, requestConfig.files);
		this.hooks.triggerHook("loading.state:started", this.element, this.backendRequest);
		this.pendingActions = [];
		this.valueStore.flushDirtyPropsToPending();
		this.isRequestPending = false;
		this.backendRequest.promise.then(async (response) => {
			const backendResponse = new BackendResponse_default(response);
			const html = await backendResponse.getBody();
			for (const input of Object.values(this.pendingFiles)) input.value = "";
			const headers = backendResponse.response.headers;
			if (!headers.get("Content-Type")?.includes("application/vnd.live-component+html") && !headers.get("X-Live-Redirect")) {
				const controls = { displayError: true };
				this.valueStore.pushPendingPropsBackToDirty();
				this.hooks.triggerHook("response:error", backendResponse, controls);
				if (controls.displayError) this.renderError(html);
				this.backendRequest = null;
				thisPromiseResolve(backendResponse);
				return response;
			}
			const liveUrl = backendResponse.getLiveUrl();
			if (liveUrl) history.replaceState(history.state, "", new URL(liveUrl + window.location.hash, window.location.origin));
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
		this.hooks.triggerHook("render:started", html, backendResponse, controls);
		if (!controls.shouldRender) return;
		if (backendResponse.response.headers.get("Location")) {
			if (this.isTurboEnabled()) Turbo.visit(backendResponse.response.headers.get("Location"));
			else window.location.href = backendResponse.response.headers.get("Location") || "";
			return;
		}
		this.hooks.triggerHook("loading.state:finished", this.element);
		const modifiedModelValues = {};
		Object.keys(this.valueStore.getDirtyProps()).forEach((modelName) => {
			modifiedModelValues[modelName] = this.valueStore.get(modelName);
		});
		let newElement;
		try {
			newElement = htmlToElement(html);
			if (!newElement.matches("[data-controller~=live]")) throw new Error("A live component template must contain a single root controller element.");
		} catch (error) {
			console.error(`There was a problem with the '${this.name}' component HTML returned:`, { id: this.id });
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
			if (target === "up") {
				this.emitUp(event, data, componentName);
				return;
			}
			if (target === "self") {
				this.emitSelf(event, data);
				return;
			}
			this.emit(event, data, componentName);
		});
		browserEventsToDispatch.forEach(({ event, payload }) => {
			this.element.dispatchEvent(new CustomEvent(event, {
				detail: payload,
				bubbles: true
			}));
		});
		this.hooks.triggerHook("render:finished", this);
	}
	calculateDebounce(debounce) {
		if (debounce === true) return this.defaultDebounce;
		if (debounce === false) return 0;
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
		let modal = document.getElementById("live-component-error");
		if (modal) modal.innerHTML = "";
		else {
			modal = document.createElement("div");
			modal.id = "live-component-error";
			modal.style.padding = "50px";
			modal.style.backgroundColor = "rgba(0, 0, 0, .5)";
			modal.style.zIndex = "100000";
			modal.style.position = "fixed";
			modal.style.top = "0px";
			modal.style.bottom = "0px";
			modal.style.left = "0px";
			modal.style.right = "0px";
			modal.style.display = "flex";
			modal.style.flexDirection = "column";
		}
		const iframe = document.createElement("iframe");
		iframe.style.borderRadius = "5px";
		iframe.style.flexGrow = "1";
		modal.appendChild(iframe);
		document.body.prepend(modal);
		document.body.style.overflow = "hidden";
		if (iframe.contentWindow) {
			iframe.contentWindow.document.open();
			iframe.contentWindow.document.write(html);
			iframe.contentWindow.document.close();
		}
		const closeModal = (modal) => {
			if (modal) modal.outerHTML = "";
			document.body.style.overflow = "visible";
		};
		modal.addEventListener("click", () => closeModal(modal));
		modal.setAttribute("tabindex", "0");
		modal.addEventListener("keydown", (e) => {
			if (e.key === "Escape") closeModal(modal);
		});
		modal.focus();
	}
	resetPromise() {
		this.nextRequestPromise = new Promise((resolve) => {
			this.nextRequestPromiseResolve = resolve;
		});
	}
	_updateFromParentProps(props) {
		if (this.valueStore.storeNewPropsFromParent(props)) this.render();
	}
};
function proxifyComponent(component) {
	return new Proxy(component, {
		get(component, prop) {
			if (prop in component || typeof prop !== "string") {
				if (typeof component[prop] === "function") {
					const callable = component[prop];
					return (...args) => {
						return callable.apply(component, args);
					};
				}
				return Reflect.get(component, prop);
			}
			if (component.valueStore.has(prop)) return component.getData(prop);
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
		}
	});
}
var StimulusElementDriver = class {
	constructor(controller) {
		this.controller = controller;
	}
	getModelName(element) {
		const modelDirective = getModelDirectiveFromElement(element, false);
		if (!modelDirective) return null;
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
};
function get_model_binding_default(modelDirective) {
	let shouldRender = true;
	let targetEventName = null;
	let debounce = false;
	let minLength = null;
	let maxLength = null;
	let minValue = null;
	let maxValue = null;
	modelDirective.modifiers.forEach((modifier) => {
		switch (modifier.name) {
			case "on":
				if (!modifier.value) throw new Error(`The "on" modifier in ${modelDirective.getString()} requires a value - e.g. on(change).`);
				if (!["input", "change"].includes(modifier.value)) throw new Error(`The "on" modifier in ${modelDirective.getString()} only accepts the arguments "input" or "change".`);
				targetEventName = modifier.value;
				break;
			case "norender":
				shouldRender = false;
				break;
			case "debounce":
				debounce = modifier.value ? Number.parseInt(modifier.value) : true;
				break;
			case "min_length":
				minLength = modifier.value ? Number.parseInt(modifier.value) : null;
				break;
			case "max_length":
				maxLength = modifier.value ? Number.parseInt(modifier.value) : null;
				break;
			case "min_value":
				minValue = modifier.value ? Number.parseFloat(modifier.value) : null;
				break;
			case "max_value":
				maxValue = modifier.value ? Number.parseFloat(modifier.value) : null;
				break;
			default: throw new Error(`Unknown modifier "${modifier.name}" in data-model="${modelDirective.getString()}".`);
		}
	});
	const [modelName, innerModelName] = modelDirective.action.split(":");
	return {
		modelName,
		innerModelName: innerModelName || null,
		shouldRender,
		debounce,
		targetEventName,
		minLength,
		maxLength,
		minValue,
		maxValue
	};
}
var ChildComponentPlugin_default = class {
	constructor(component) {
		this.parentModelBindings = [];
		this.component = component;
		this.parentModelBindings = getAllModelDirectiveFromElements(this.component.element).map(get_model_binding_default);
	}
	attachToComponent(component) {
		component.on("request:started", (requestData) => {
			requestData.children = this.getChildrenFingerprints();
		});
		component.on("model:set", (model, value) => {
			this.notifyParentModelChange(model, value);
		});
	}
	getChildrenFingerprints() {
		const fingerprints = {};
		this.getChildren().forEach((child) => {
			if (!child.id) throw new Error("missing id");
			fingerprints[child.id] = {
				fingerprint: child.fingerprint,
				tag: child.element.tagName.toLowerCase()
			};
		});
		return fingerprints;
	}
	notifyParentModelChange(modelName, value) {
		const parentComponent = findParent(this.component);
		if (!parentComponent) return;
		this.parentModelBindings.forEach((modelBinding) => {
			if ((modelBinding.innerModelName || "value") !== modelName) return;
			parentComponent.set(modelBinding.modelName, value, modelBinding.shouldRender, modelBinding.debounce);
		});
	}
	getChildren() {
		return findChildren(this.component);
	}
};
var LazyPlugin_default = class {
	constructor() {
		this.intersectionObserver = null;
	}
	attachToComponent(component) {
		if ("lazy" !== component.element.attributes.getNamedItem("loading")?.value) return;
		component.on("connect", () => {
			this.getObserver().observe(component.element);
		});
		component.on("disconnect", () => {
			this.intersectionObserver?.unobserve(component.element);
		});
	}
	getObserver() {
		if (!this.intersectionObserver) this.intersectionObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.dispatchEvent(new CustomEvent("live:appear"));
					observer.unobserve(entry.target);
				}
			});
		});
		return this.intersectionObserver;
	}
};
var LoadingPlugin_default = class {
	attachToComponent(component) {
		component.on("loading.state:started", (element, request) => {
			this.startLoading(component, element, request);
		});
		component.on("loading.state:finished", (element) => {
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
		if (isLoading) this.addAttributes(targetElement, ["busy"]);
		else this.removeAttributes(targetElement, ["busy"]);
		this.getLoadingDirectives(component, targetElement).forEach(({ element, directives }) => {
			if (isLoading) this.addAttributes(element, ["data-live-is-loading"]);
			else this.removeAttributes(element, ["data-live-is-loading"]);
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
		const validModifiers = /* @__PURE__ */ new Map();
		validModifiers.set("delay", (modifier) => {
			if (!isLoading) return;
			delay = modifier.value ? Number.parseInt(modifier.value) : 200;
		});
		validModifiers.set("action", (modifier) => {
			if (!modifier.value) throw new Error(`The "action" in data-loading must have an action name - e.g. action(foo). It's missing for "${directive.getString()}"`);
			targetedActions.push(modifier.value);
		});
		validModifiers.set("model", (modifier) => {
			if (!modifier.value) throw new Error(`The "model" in data-loading must have an action name - e.g. model(foo). It's missing for "${directive.getString()}"`);
			targetedModels.push(modifier.value);
		});
		directive.modifiers.forEach((modifier) => {
			if (validModifiers.has(modifier.name)) {
				(validModifiers.get(modifier.name) ?? (() => {}))(modifier);
				return;
			}
			throw new Error(`Unknown modifier "${modifier.name}" used in data-loading="${directive.getString()}". Available modifiers are: ${Array.from(validModifiers.keys()).join(", ")}.`);
		});
		if (isLoading && targetedActions.length > 0 && backendRequest && !backendRequest.containsOneOfActions(targetedActions)) return;
		if (isLoading && targetedModels.length > 0 && backendRequest && !backendRequest.areAnyModelsUpdated(targetedModels)) return;
		let loadingDirective;
		switch (finalAction) {
			case "show":
				loadingDirective = () => this.showElement(element);
				break;
			case "hide":
				loadingDirective = () => this.hideElement(element);
				break;
			case "addClass":
				loadingDirective = () => this.addClass(element, directive.args);
				break;
			case "removeClass":
				loadingDirective = () => this.removeClass(element, directive.args);
				break;
			case "addAttribute":
				loadingDirective = () => this.addAttributes(element, directive.args);
				break;
			case "removeAttribute":
				loadingDirective = () => this.removeAttributes(element, directive.args);
				break;
			default: throw new Error(`Unknown data-loading action "${finalAction}"`);
		}
		if (delay) {
			window.setTimeout(() => {
				if (backendRequest && !backendRequest.isResolved) loadingDirective();
			}, delay);
			return;
		}
		loadingDirective();
	}
	getLoadingDirectives(component, element) {
		const loadingDirectives = [];
		let matchingElements = Array.from(element.querySelectorAll("[data-loading]"));
		matchingElements = matchingElements.filter((elt) => elementBelongsToThisComponent(elt, component));
		if (element.hasAttribute("data-loading")) matchingElements = [element, ...matchingElements];
		matchingElements.forEach((element) => {
			if (!(element instanceof HTMLElement) && !(element instanceof SVGElement)) throw new Error("Invalid Element Type");
			const directives = parseDirectives(element.dataset.loading || "show");
			loadingDirectives.push({
				element,
				directives
			});
		});
		return loadingDirectives;
	}
	showElement(element) {
		element.style.display = "revert";
	}
	hideElement(element) {
		element.style.display = "none";
	}
	addClass(element, classes) {
		element.classList.add(...combineSpacedArray(classes));
	}
	removeClass(element, classes) {
		element.classList.remove(...combineSpacedArray(classes));
		if (element.classList.length === 0) element.removeAttribute("class");
	}
	addAttributes(element, attributes) {
		attributes.forEach((attribute) => {
			element.setAttribute(attribute, "");
		});
	}
	removeAttributes(element, attributes) {
		attributes.forEach((attribute) => {
			element.removeAttribute(attribute);
		});
	}
};
const parseLoadingAction = (action, isLoading) => {
	switch (action) {
		case "show": return isLoading ? "show" : "hide";
		case "hide": return isLoading ? "hide" : "show";
		case "addClass": return isLoading ? "addClass" : "removeClass";
		case "removeClass": return isLoading ? "removeClass" : "addClass";
		case "addAttribute": return isLoading ? "addAttribute" : "removeAttribute";
		case "removeAttribute": return isLoading ? "removeAttribute" : "addAttribute";
	}
	throw new Error(`Unknown data-loading action "${action}"`);
};
var PageUnloadingPlugin_default = class {
	constructor() {
		this.isConnected = false;
	}
	attachToComponent(component) {
		component.on("render:started", (html, response, controls) => {
			if (!this.isConnected) controls.shouldRender = false;
		});
		component.on("connect", () => {
			this.isConnected = true;
		});
		component.on("disconnect", () => {
			this.isConnected = false;
		});
	}
};
var PollingDirector_default = class {
	constructor(component) {
		this.isPollingActive = true;
		this.pollingIntervals = [];
		this.component = component;
	}
	addPoll(actionName, duration) {
		this.polls.push({
			actionName,
			duration
		});
		if (this.isPollingActive) this.initiatePoll(actionName, duration);
	}
	startAllPolling() {
		if (this.isPollingActive) return;
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
		if (actionName === "$render") callback = () => {
			this.component.render();
		};
		else callback = () => {
			this.component.action(actionName, {}, 0);
		};
		const timer = window.setInterval(() => {
			callback();
		}, duration);
		this.pollingIntervals.push(timer);
	}
};
var PollingPlugin_default = class {
	attachToComponent(component) {
		this.element = component.element;
		this.pollingDirector = new PollingDirector_default(component);
		this.initializePolling();
		component.on("connect", () => {
			this.pollingDirector.startAllPolling();
		});
		component.on("disconnect", () => {
			this.pollingDirector.stopAllPolling();
		});
		component.on("render:finished", () => {
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
		if (this.element.dataset.poll === void 0) return;
		const rawPollConfig = this.element.dataset.poll;
		parseDirectives(rawPollConfig || "$render").forEach((directive) => {
			let duration = 2e3;
			directive.modifiers.forEach((modifier) => {
				switch (modifier.name) {
					case "delay":
						if (modifier.value) duration = Number.parseInt(modifier.value);
						break;
					default: console.warn(`Unknown modifier "${modifier.name}" in data-poll "${rawPollConfig}".`);
				}
			});
			this.addPoll(directive.action, duration);
		});
	}
};
var SetValueOntoModelFieldsPlugin_default = class {
	attachToComponent(component) {
		this.synchronizeValueOfModelFields(component);
		component.on("render:finished", () => {
			this.synchronizeValueOfModelFields(component);
		});
	}
	synchronizeValueOfModelFields(component) {
		component.element.querySelectorAll("[data-model]").forEach((element) => {
			if (!(element instanceof HTMLElement)) throw new Error("Invalid element using data-model.");
			if (element instanceof HTMLFormElement) return;
			if (!elementBelongsToThisComponent(element, component)) return;
			const modelDirective = getModelDirectiveFromElement(element);
			if (!modelDirective) return;
			const modelName = modelDirective.action;
			if (component.getUnsyncedModels().includes(modelName)) return;
			if (component.valueStore.has(modelName)) setValueOnElement(element, component.valueStore.get(modelName));
			if (element instanceof HTMLSelectElement && !element.multiple) component.valueStore.set(modelName, getValueFromElement(element, component.valueStore));
		});
	}
};
var ValidatedFieldsPlugin_default = class {
	attachToComponent(component) {
		component.on("model:set", (modelName) => {
			this.handleModelSet(modelName, component.valueStore);
		});
	}
	handleModelSet(modelName, valueStore) {
		if (valueStore.has("validatedFields")) {
			const validatedFields = [...valueStore.get("validatedFields")];
			if (!validatedFields.includes(modelName)) validatedFields.push(modelName);
			valueStore.set("validatedFields", validatedFields);
		}
	}
};
var LiveControllerDefault = class LiveControllerDefault extends Controller {
	constructor(..._args) {
		super(..._args);
		this.pendingActionTriggerModelElement = null;
		this.elementEventListeners = [{
			event: "input",
			callback: (event) => this.handleInputEvent(event)
		}, {
			event: "change",
			callback: (event) => this.handleChangeEvent(event)
		}];
		this.pendingFiles = {};
	}
	initialize() {
		this.mutationObserver = new MutationObserver(this.onMutations.bind(this));
		this.createComponent();
	}
	connect() {
		this.connectComponent();
		this.mutationObserver.observe(this.element, { attributes: true });
	}
	disconnect() {
		this.disconnectComponent();
		this.mutationObserver.disconnect();
	}
	update(event) {
		if (event.type === "input" || event.type === "change") throw new Error(`Since LiveComponents 2.3, you no longer need data-action="live#update" on form elements. Found on element: ${getElementAsTagText(event.currentTarget)}`);
		this.updateModelFromElementEvent(event.currentTarget, null);
	}
	action(event) {
		const params = event.params;
		if (!params.action) throw new Error(`No action name provided on element: ${getElementAsTagText(event.currentTarget)}. Did you forget to add the "data-live-action-param" attribute?`);
		const rawAction = params.action;
		const actionArgs = { ...params };
		delete actionArgs.action;
		const directives = parseDirectives(rawAction);
		let debounce = false;
		directives.forEach((directive) => {
			let pendingFiles = {};
			const validModifiers = /* @__PURE__ */ new Map();
			validModifiers.set("stop", () => {
				event.stopPropagation();
			});
			validModifiers.set("self", () => {
				if (event.target !== event.currentTarget) return;
			});
			validModifiers.set("debounce", (modifier) => {
				debounce = modifier.value ? Number.parseInt(modifier.value) : true;
			});
			validModifiers.set("files", (modifier) => {
				if (!modifier.value) pendingFiles = this.pendingFiles;
				else if (this.pendingFiles[modifier.value]) pendingFiles[modifier.value] = this.pendingFiles[modifier.value];
			});
			directive.modifiers.forEach((modifier) => {
				if (validModifiers.has(modifier.name)) {
					(validModifiers.get(modifier.name) ?? (() => {}))(modifier);
					return;
				}
				console.warn(`Unknown modifier ${modifier.name} in action "${rawAction}". Available modifiers are: ${Array.from(validModifiers.keys()).join(", ")}.`);
			});
			for (const [key, input] of Object.entries(pendingFiles)) {
				if (input.files) this.component.files(key, input);
				delete this.pendingFiles[key];
			}
			this.component.action(directive.action, actionArgs, debounce);
			if (getModelDirectiveFromElement(event.currentTarget, false)) this.pendingActionTriggerModelElement = event.currentTarget;
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
		if (!params.event) throw new Error(`No event name provided on element: ${getElementAsTagText(event.currentTarget)}. Did you forget to add the "data-live-event-param" attribute?`);
		const eventInfo = params.event;
		const eventArgs = { ...params };
		delete eventArgs.event;
		const directives = parseDirectives(eventInfo);
		const emits = [];
		directives.forEach((directive) => {
			let nameMatch = null;
			directive.modifiers.forEach((modifier) => {
				switch (modifier.name) {
					case "name":
						nameMatch = modifier.value;
						break;
					default: throw new Error(`Unknown modifier ${modifier.name} in event "${eventInfo}".`);
				}
			});
			emits.push({
				name: directive.action,
				data: eventArgs,
				nameMatch
			});
		});
		return emits;
	}
	createComponent() {
		const id = this.element.id || null;
		this.component = new Component(this.element, this.nameValue, this.propsValue, this.listenersValue, id, LiveControllerDefault.backendFactory(this), new StimulusElementDriver(this));
		this.proxiedComponent = proxifyComponent(this.component);
		Object.defineProperty(this.element, "__component", {
			value: this.proxiedComponent,
			writable: true
		});
		if (this.hasDebounceValue) this.component.defaultDebounce = this.debounceValue;
		[
			new LoadingPlugin_default(),
			new LazyPlugin_default(),
			new ValidatedFieldsPlugin_default(),
			new PageUnloadingPlugin_default(),
			new PollingPlugin_default(),
			new SetValueOntoModelFieldsPlugin_default(),
			new ChildComponentPlugin_default(this.component)
		].forEach((plugin) => {
			this.component.addPlugin(plugin);
		});
	}
	connectComponent() {
		this.component.connect();
		this.mutationObserver.observe(this.element, { attributes: true });
		this.elementEventListeners.forEach(({ event, callback }) => {
			this.component.element.addEventListener(event, callback);
		});
		this.dispatchEvent("connect");
	}
	disconnectComponent() {
		this.component.disconnect();
		this.elementEventListeners.forEach(({ event, callback }) => {
			this.component.element.removeEventListener(event, callback);
		});
		this.dispatchEvent("disconnect");
	}
	handleInputEvent(event) {
		const target = event.target;
		if (!target) return;
		this.updateModelFromElementEvent(target, "input");
	}
	handleChangeEvent(event) {
		const target = event.target;
		if (!target) return;
		this.updateModelFromElementEvent(target, "change");
	}
	updateModelFromElementEvent(element, eventName) {
		if (!elementBelongsToThisComponent(element, this.component)) return;
		if (!(element instanceof HTMLElement)) throw new Error("Could not update model for non HTMLElement");
		if (element instanceof HTMLInputElement && element.type === "file") {
			const key = element.name;
			if (element.files?.length) this.pendingFiles[key] = element;
			else if (this.pendingFiles[key]) delete this.pendingFiles[key];
		}
		const modelDirective = getModelDirectiveFromElement(element, false);
		if (!modelDirective) return;
		const modelBinding = get_model_binding_default(modelDirective);
		if (!modelBinding.targetEventName) modelBinding.targetEventName = "input";
		if (this.pendingActionTriggerModelElement === element) modelBinding.shouldRender = false;
		if (eventName === "change" && modelBinding.targetEventName === "input") modelBinding.targetEventName = "change";
		if (eventName && modelBinding.targetEventName !== eventName) return;
		if (false === modelBinding.debounce) if (modelBinding.targetEventName === "input") modelBinding.debounce = true;
		else modelBinding.debounce = 0;
		const finalValue = getValueFromElement(element, this.component.valueStore);
		const finalValueIsEmpty = finalValue === "" || finalValue === null || finalValue === void 0;
		if (isTextualInputElement(element) || isTextareaElement(element)) {
			if (!finalValueIsEmpty && modelBinding.minLength !== null && typeof finalValue === "string" && finalValue.length < modelBinding.minLength) return;
			if (!finalValueIsEmpty && modelBinding.maxLength !== null && typeof finalValue === "string" && finalValue.length > modelBinding.maxLength) return;
		}
		if (isNumericalInputElement(element)) {
			if (!finalValueIsEmpty) {
				const numericValue = Number(finalValue);
				if (modelBinding.minValue !== null && numericValue < modelBinding.minValue) return;
				if (modelBinding.maxValue !== null && numericValue > modelBinding.maxValue) return;
			}
		}
		this.component.set(modelBinding.modelName, finalValue, modelBinding.shouldRender, modelBinding.debounce);
	}
	dispatchEvent(name, detail = {}, canBubble = true, cancelable = false) {
		detail.controller = this;
		detail.component = this.proxiedComponent;
		this.dispatch(name, {
			detail,
			prefix: "live",
			cancelable,
			bubbles: canBubble
		});
	}
	onMutations(mutations) {
		mutations.forEach((mutation) => {
			if (mutation.type === "attributes" && mutation.attributeName === "id" && this.element.id !== this.component.id) {
				this.disconnectComponent();
				this.createComponent();
				this.connectComponent();
			}
		});
	}
};
LiveControllerDefault.values = {
	name: String,
	url: String,
	props: {
		type: Object,
		default: {}
	},
	propsUpdatedFromParent: {
		type: Object,
		default: {}
	},
	listeners: {
		type: Array,
		default: []
	},
	eventsToEmit: {
		type: Array,
		default: []
	},
	eventsToDispatch: {
		type: Array,
		default: []
	},
	debounce: {
		type: Number,
		default: 150
	},
	fingerprint: {
		type: String,
		default: ""
	},
	requestMethod: {
		type: String,
		default: "post"
	},
	fetchCredentials: {
		type: String,
		default: "same-origin"
	}
};
LiveControllerDefault.backendFactory = (controller) => new Backend_default(controller.urlValue, controller.requestMethodValue, controller.fetchCredentialsValue);
export { Component, LiveControllerDefault as default, getComponent };
