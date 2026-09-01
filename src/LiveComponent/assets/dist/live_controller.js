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
		this.download = null;
		this.parsePromise = null;
		this.response = response;
	}
	async getBody() {
		if (!this.parsePromise) this.parsePromise = this.parse();
		await this.parsePromise;
		return this.body;
	}
	getDownload() {
		return this.download;
	}
	getLiveUrl() {
		if (void 0 === this.liveUrl) this.liveUrl = this.response.headers.get("X-Live-Url");
		return this.liveUrl;
	}
	getDownloadUrl() {
		return this.response.headers.get("X-Live-Download-Url");
	}
	isRemoved() {
		return this.response.headers.has("X-Live-Remove");
	}
	async parse() {
		const htmlLength = this.response.headers.get("X-Live-Html-Length");
		if (null === htmlLength) {
			this.body = await this.response.text();
			return;
		}
		const buffer = await this.response.arrayBuffer();
		const splitAt = Number.parseInt(htmlLength, 10);
		this.body = new TextDecoder().decode(buffer.slice(0, splitAt));
		this.download = {
			filename: decodeFilename(this.response.headers.get("X-Live-Download-Filename")),
			blob: new Blob([buffer.slice(splitAt)], { type: this.response.headers.get("X-Live-Download-Type") ?? "application/octet-stream" })
		};
	}
};
function decodeFilename(value) {
	if (!value) return "download";
	try {
		return decodeURIComponent(value) || "download";
	} catch {
		return "download";
	}
}
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
	if (!model.includes("[") && !model.includes("]")) return model;
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
const numericRegex = /^-?(?:(?:[0-9]*\.[0-9]+)|[0-9]+)$/;
const identRegex = /^[a-zA-Zа-яА-Я_\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u00FF$][a-zA-Zа-яА-Я0-9_\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u00FF$]*$/;
const escEscRegex = /\\\\/;
const whitespaceRegex = /^\s*$/;
const preOpRegexElements = [
	"'(?:(?:\\\\')|[^'])*'",
	"\"(?:(?:\\\\\")|[^\"])*\"",
	"`(?:(?:\\\\`)|[^`])*`",
	"\\s+",
	"\\btrue\\b",
	"\\bfalse\\b",
	"\\bnull\\b",
	"\\bundefined\\b",
	"(?:[0-9]+(?:\\.[0-9]+)?|\\.[0-9]+)"
];
const postOpRegexElements = ["[a-zA-Zа-яА-Я_À-ÖØ-öø-ÿ\\$][a-zA-Z0-9а-яА-Я_À-ÖØ-öø-ÿ\\$]*"];
const unaryOpsAfter = [
	"binaryOp",
	"unaryOp",
	"openParen",
	"openBracket",
	"question",
	"colon",
	"comma"
];
var Lexer = class {
	constructor(grammar) {
		this._grammar = grammar;
	}
	getElements(str) {
		const regex = this._getSplitRegex();
		return str.split(regex).filter((elem) => {
			return elem;
		});
	}
	getTokens(elements) {
		const tokens = [];
		let negate = false;
		for (let i = 0; i < elements.length; i++) {
			const element = elements[i];
			if (!element) continue;
			if (this._isWhitespace(element)) {
				if (tokens.length > 0) tokens[tokens.length - 1].raw += element;
			} else if ((element === "+" || element === "-") && this._isUnary(tokens)) {
				const lastToken = tokens.length > 0 ? tokens[tokens.length - 1] : null;
				if (lastToken && lastToken.type === "binaryOp" && (lastToken.value === "+" || lastToken.value === "-") && !lastToken.raw.match(/\s$/)) throw new Error(`Unexpected token '${element}' after operator '${lastToken.value}'`);
				let nextElement = "";
				for (let j = i + 1; j < elements.length; j++) if (!this._isWhitespace(elements[j])) {
					nextElement = elements[j];
					break;
				}
				if (element === "-") if (nextElement.match(numericRegex)) negate = true;
				else {
					const token = this._createToken(element);
					token.type = "unaryOp";
					tokens.push(token);
				}
				else if (!nextElement.match(numericRegex)) {
					const token = this._createToken(element);
					token.type = "unaryOp";
					tokens.push(token);
				}
			} else {
				if (negate) {
					elements[i] = "-" + element;
					negate = false;
				}
				tokens.push(this._createToken(elements[i]));
			}
		}
		if (negate) tokens.push(this._createToken("-"));
		return tokens;
	}
	tokenize(str) {
		const elements = this.getElements(str);
		return this.getTokens(elements);
	}
	_createToken(element) {
		const token = {
			type: "literal",
			value: element,
			raw: element
		};
		if (element[0] === "\"" || element[0] === "'") token.value = this._unquote(element);
		else if (element[0] === "`") {
			token.type = "template";
			token.value = element;
		} else if (element.match(numericRegex)) token.value = parseFloat(element);
		else if (element === "true" || element === "false") token.value = element === "true";
		else if (element === "null") token.value = null;
		else if (element === "undefined") token.value = void 0;
		else if (this._grammar.elements[element]) token.type = this._grammar.elements[element].type;
		else if (element.match(identRegex)) token.type = "identifier";
		else throw new Error(`Invalid expression token: ${element}`);
		return token;
	}
	_escapeRegExp(str) {
		str = str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
		if (str.match(identRegex)) str = "\\b" + str + "\\b";
		return str;
	}
	_getSplitRegex() {
		if (!this._splitRegex) {
			const elemArray = Object.keys(this._grammar.elements).sort((a, b) => {
				return b.length - a.length;
			}).map((elem) => {
				return this._escapeRegExp(elem);
			}, this);
			this._splitRegex = new RegExp("(" + [
				preOpRegexElements.join("|"),
				elemArray.join("|"),
				postOpRegexElements.join("|")
			].join("|") + ")");
		}
		return this._splitRegex;
	}
	_isUnary(tokens) {
		if (!tokens.length) return true;
		const lastToken = tokens[tokens.length - 1];
		if (!lastToken) return true;
		return unaryOpsAfter.some((type) => type === lastToken.type);
	}
	_isWhitespace(str) {
		return !!str.match(whitespaceRegex);
	}
	_unquote(str) {
		const quote = str[0];
		if (!quote) throw new Error("Cannot unquote empty string");
		const escQuoteRegex = new RegExp("\\\\" + quote, "g");
		return str.substr(1, str.length - 2).replace(escQuoteRegex, quote).replace(escEscRegex, "\\");
	}
};
const states = {
	expectOperand: { tokenTypes: {
		literal: { toState: "expectBinOp" },
		template: { toState: "expectBinOp" },
		identifier: { toState: "identifier" },
		unaryOp: {},
		openParen: { toState: "subExpression" },
		openCurl: {
			toState: "expectObjKey",
			handler: "objStart"
		},
		dot: { toState: "traverse" },
		openBracket: {
			toState: "arrayVal",
			handler: "arrayStart"
		}
	} },
	expectBinOp: {
		tokenTypes: {
			binaryOp: { toState: "expectOperand" },
			pipe: { toState: "expectTransform" },
			dot: { toState: "traverse" },
			question: {
				toState: "ternaryMid",
				handler: "ternaryStart"
			}
		},
		completable: true
	},
	expectTransform: { tokenTypes: { identifier: {
		toState: "postTransform",
		handler: "transform"
	} } },
	expectObjKey: { tokenTypes: {
		literal: {
			toState: "expectKeyValSep",
			handler: "objKey"
		},
		identifier: {
			toState: "expectKeyValSep",
			handler: "objKey"
		},
		unaryOp: {
			toState: "objSpread",
			handler: "noop"
		},
		closeCurl: { toState: "expectBinOp" }
	} },
	expectKeyValSep: { tokenTypes: {
		colon: { toState: "objVal" },
		unaryOp: { toState: "objSpread" }
	} },
	postTransform: {
		tokenTypes: {
			openParen: { toState: "argVal" },
			binaryOp: { toState: "expectOperand" },
			dot: { toState: "traverse" },
			openBracket: { toState: "filter" },
			pipe: { toState: "expectTransform" }
		},
		completable: true
	},
	postArgs: {
		tokenTypes: {
			binaryOp: { toState: "expectOperand" },
			dot: { toState: "traverse" },
			openBracket: { toState: "filter" },
			question: {
				toState: "ternaryMid",
				handler: "ternaryStart"
			},
			pipe: { toState: "expectTransform" }
		},
		completable: true
	},
	identifier: {
		tokenTypes: {
			binaryOp: { toState: "expectOperand" },
			dot: { toState: "traverse" },
			openBracket: { toState: "filter" },
			openParen: {
				toState: "argVal",
				handler: "functionCall"
			},
			pipe: { toState: "expectTransform" },
			question: {
				toState: "ternaryMid",
				handler: "ternaryStart"
			}
		},
		completable: true
	},
	traverse: { tokenTypes: {
		identifier: { toState: "identifier" },
		pipe: { toState: "expectTransform" }
	} },
	filter: {
		subHandler: "filter",
		endStates: { closeBracket: "identifier" }
	},
	subExpression: {
		subHandler: "subExpression",
		endStates: { closeParen: "expectBinOp" }
	},
	argVal: {
		subHandler: "argVal",
		endStates: {
			comma: "argVal",
			closeParen: "postArgs"
		}
	},
	objVal: {
		subHandler: "objVal",
		endStates: {
			comma: "expectObjKey",
			closeCurl: "expectBinOp"
		}
	},
	objSpread: {
		subHandler: "objSpread",
		endStates: {
			comma: "expectObjKey",
			closeCurl: "expectBinOp"
		}
	},
	arrayVal: {
		subHandler: "arrayVal",
		endStates: {
			comma: "arrayVal",
			closeBracket: "expectBinOp"
		},
		tokenTypes: { unaryOp: {
			toState: "arraySpread",
			handler: "noop"
		} }
	},
	arraySpread: {
		subHandler: "arraySpread",
		endStates: {
			comma: "arrayVal",
			closeBracket: "expectBinOp"
		}
	},
	ternaryMid: {
		subHandler: "ternaryMid",
		endStates: { colon: "ternaryEnd" }
	},
	ternaryEnd: {
		subHandler: "ternaryEnd",
		completable: true
	}
};
var Parser = class Parser {
	constructor(grammar, prefix, stopMap = {}) {
		this._grammar = grammar;
		this._state = "expectOperand";
		this._tree = null;
		this._exprStr = prefix || "";
		this._relative = false;
		this._stopMap = stopMap;
	}
	addToken(token) {
		if (this._state === "complete") throw new Error("Cannot add a new token to a completed Parser");
		const state = states[this._state];
		if (!state) throw new Error(`Invalid parser state: ${this._state}`);
		const startExpr = this._exprStr;
		this._exprStr += token.raw;
		if (this._state === "traverse" && token.type === "binaryOp" && typeof token.value === "string") {
			if ([
				"and",
				"or",
				"xor",
				"in"
			].includes(token.value)) token = {
				type: "identifier",
				value: token.value,
				raw: token.raw
			};
		}
		if ((this._state === "identifier" || this._state === "expectBinOp") && token.type === "unaryOp" && (token.value === "+" || token.value === "-")) token = {
			type: "binaryOp",
			value: token.value,
			raw: token.raw
		};
		if (state.subHandler) {
			if (!this._subParser) this._startSubExpression(startExpr);
			const stopState = this._subParser.addToken(token);
			if (stopState) {
				this._endSubExpression();
				if (this._parentStop) return stopState;
				this._state = stopState;
			}
		} else if (state.tokenTypes && state.tokenTypes[token.type]) {
			const typeOpts = state.tokenTypes[token.type];
			if (!typeOpts) throw new Error(`No type options for token ${token.type}`);
			if (typeOpts.handler) {
				const handlerMethod = this._getTokenHandlerMethod(typeOpts.handler);
				if (handlerMethod) handlerMethod(token);
			} else {
				const handlerMethod = this._getHandlerMethod(token.type);
				if (handlerMethod) handlerMethod(token);
			}
			if (typeOpts.toState) this._state = typeOpts.toState;
		} else if (this._stopMap[token.type]) return this._stopMap[token.type];
		else throw new Error(`Token ${token.raw} (${token.type}) unexpected in expression: ${this._exprStr}`);
		return false;
	}
	addTokens(tokens) {
		tokens.forEach(this.addToken, this);
	}
	complete() {
		const currentState = states[this._state];
		if (this._cursor && (!currentState || !currentState.completable)) throw new Error(`Unexpected end of expression: ${this._exprStr}`);
		if (this._subParser) this._endSubExpression();
		this._state = "complete";
		return this._cursor ? this._tree : null;
	}
	isRelative() {
		return this._relative;
	}
	_endSubExpression() {
		const currentState = states[this._state];
		if (!currentState || !currentState.subHandler) throw new Error(`Invalid state for ending sub expression: ${this._state}`);
		if (this._subParser.isRelative()) this._relative = true;
		const subHandlerName = currentState.subHandler;
		const handlerMethod = this._getSubHandlerMethod(subHandlerName);
		if (handlerMethod) handlerMethod(this._subParser.complete());
		this._subParser = null;
	}
	_placeAtCursor(node) {
		if (!this._cursor) this._tree = node;
		else {
			this._cursor.right = node;
			this._setParent(node, this._cursor);
		}
		this._cursor = node;
	}
	_placeBeforeCursor(node) {
		this._cursor = this._cursor?._parent;
		this._placeAtCursor(node);
	}
	_setParent(node, parent) {
		Object.defineProperty(node, "_parent", {
			value: parent,
			writable: true
		});
	}
	_startSubExpression(exprStr) {
		let endStates = states[this._state].endStates;
		if (!endStates) {
			this._parentStop = true;
			endStates = this._stopMap;
		}
		this._subParser = new Parser(this._grammar, exprStr, endStates);
	}
	argVal(ast) {
		if (ast) this._cursor?.args?.push(ast);
	}
	arrayStart() {
		this._placeAtCursor({
			type: "ArrayLiteral",
			value: []
		});
	}
	arrayVal(ast) {
		const { _cursor } = this;
		if (ast && _cursor && Array.isArray(_cursor.value)) if (ast.type === "UnaryExpression" && ast.operator === "..." && ast.right) {
			const right = ast.right;
			if (!Array.isArray(_cursor.entries)) _cursor.entries = [..._cursor.value];
			_cursor.value.push(right);
			if (Array.isArray(_cursor.entries)) _cursor.entries.push({
				type: "SpreadElement",
				expr: right
			});
		} else {
			_cursor.value.push(ast);
			if (Array.isArray(_cursor.entries)) _cursor.entries.push(ast);
		}
	}
	arraySpread(ast) {
		const { _cursor } = this;
		if (ast && _cursor) {
			if (!Array.isArray(_cursor.entries)) _cursor.entries = [..._cursor.value];
			_cursor.entries.push({
				type: "SpreadElement",
				expr: ast
			});
		}
	}
	binaryOp(token) {
		const precedence = this._grammar.elements[token.value]?.precedence || 0;
		let parent = this._cursor?._parent;
		while (parent && parent.operator && this._grammar.elements[parent.operator]?.precedence >= precedence) {
			this._cursor = parent;
			parent = parent._parent;
		}
		const node = {
			type: "BinaryExpression",
			operator: token.value,
			left: this._cursor
		};
		if (this._cursor) this._setParent(this._cursor, node);
		this._cursor = parent;
		this._placeAtCursor(node);
	}
	dot() {
		this._nextIdentEncapsulate = Boolean(this._cursor && this._cursor.type !== "UnaryExpression" && (this._cursor.type !== "BinaryExpression" || this._cursor.type === "BinaryExpression" && this._cursor.right));
		this._nextIdentRelative = !this._cursor || this._cursor && !this._nextIdentEncapsulate;
		if (this._nextIdentRelative) this._relative = true;
	}
	filter(ast) {
		this._placeBeforeCursor({
			type: "FilterExpression",
			expr: ast,
			relative: this._subParser.isRelative(),
			subject: this._cursor
		});
	}
	functionCall() {
		if (this._cursor && this._cursor.type === "FunctionCall" && this._cursor.pool === "transforms") return;
		const functionName = this._buildFullIdentifierPath(this._cursor || null);
		this._placeBeforeCursor({
			type: "FunctionCall",
			name: functionName,
			args: [],
			pool: "functions"
		});
	}
	_buildFullIdentifierPath(node) {
		if (!node || node.type !== "Identifier") return node?.value || "";
		const parts = [];
		let current = node;
		while (current && current.type === "Identifier") {
			parts.unshift(current.value);
			current = current.from || null;
		}
		return parts.join(".");
	}
	identifier(token) {
		const node = {
			type: "Identifier",
			value: token.value
		};
		if (this._nextIdentEncapsulate && this._cursor && this._cursor.type === "FunctionCall" && this._cursor.pool === "transforms") {
			const namespaceParts = [];
			namespaceParts.push(this._cursor.name);
			namespaceParts.push(token.value);
			const namespacedTransformName = namespaceParts.join(".");
			this._cursor.name = namespacedTransformName;
			this._nextIdentEncapsulate = false;
			return;
		}
		if (this._nextIdentEncapsulate) {
			node.from = this._cursor;
			this._placeBeforeCursor(node);
			this._nextIdentEncapsulate = false;
		} else {
			if (this._nextIdentRelative) {
				node.relative = true;
				this._nextIdentRelative = false;
			}
			this._placeAtCursor(node);
		}
	}
	literal(token) {
		this._placeAtCursor({
			type: "Literal",
			value: token.value
		});
	}
	template(token) {
		const raw = token.value || token.raw || "";
		if (!raw || raw[0] !== "`" || raw[raw.length - 1] !== "`") throw new Error("Invalid template literal");
		const content = raw.slice(1, -1);
		const parts = [];
		let buffer = "";
		let i = 0;
		const pushBufferLiteral = () => {
			if (buffer.length > 0) {
				parts.push({
					type: "Literal",
					value: buffer
				});
				buffer = "";
			}
		};
		const readInterpolated = () => {
			let j = i + 2;
			let inSingle = false;
			let inDouble = false;
			let escaped = false;
			let depthParen = 0;
			let depthBracket = 0;
			let depthBrace = 0;
			for (; j < content.length; j++) {
				const ch = content[j];
				if (escaped) {
					escaped = false;
					continue;
				}
				if (ch === "\\") {
					escaped = true;
					continue;
				}
				if (!inSingle && !inDouble) {
					if (ch === "'") inSingle = true;
					else if (ch === "\"") inDouble = true;
					else if (ch === "(") depthParen++;
					else if (ch === ")") depthParen = Math.max(0, depthParen - 1);
					else if (ch === "[") depthBracket++;
					else if (ch === "]") depthBracket = Math.max(0, depthBracket - 1);
					else if (ch === "{") depthBrace++;
					else if (ch === "}") {
						if (depthParen === 0 && depthBracket === 0 && depthBrace === 0 && content[j - 1] === "}") {
							const inner = content.slice(i + 2, j - 1);
							i = j + 1;
							return inner;
						}
						if (depthBrace > 0) depthBrace = Math.max(0, depthBrace - 1);
					}
					continue;
				}
				if (inSingle && ch === "'") inSingle = false;
				if (inDouble && ch === "\"") inDouble = false;
			}
			throw new Error("Unclosed interpolation in template literal");
		};
		while (i < content.length) {
			const ch = content[i];
			if (ch === "\\") {
				const next = content[i + 1];
				if (next === "`" || next === "{" || next === "\\") {
					buffer += next;
					i += 2;
					continue;
				}
				buffer += ch;
				i++;
				continue;
			}
			if (ch === "{" && content[i + 1] === "{") {
				pushBufferLiteral();
				const inner = readInterpolated();
				const nestedLexer = new Lexer(this._grammar);
				const nestedParser = new Parser(this._grammar);
				const nestedTokens = nestedLexer.tokenize(inner);
				nestedParser.addTokens(nestedTokens);
				const nestedAst = nestedParser.complete();
				if (!nestedAst) parts.push({
					type: "Literal",
					value: ""
				});
				else parts.push(nestedAst);
				continue;
			}
			buffer += ch;
			i++;
		}
		pushBufferLiteral();
		this._placeAtCursor({
			type: "TemplateLiteral",
			value: void 0,
			parts
		});
	}
	objKey(token) {
		this._curObjKey = token.value;
	}
	objStart() {
		this._placeAtCursor({
			type: "ObjectLiteral",
			value: {}
		});
	}
	objVal(ast) {
		if (this._cursor && this._curObjKey) if (ast.type === "UnaryExpression" && ast.operator === "..." && ast.right) {
			const right = ast.right;
			this._cursor.value[this._curObjKey] = right;
			if (Array.isArray(this._cursor.entries)) this._cursor.entries.push({
				type: "ObjectProperty",
				key: this._curObjKey,
				value: right
			});
		} else {
			this._cursor.value[this._curObjKey] = ast;
			if (Array.isArray(this._cursor.entries)) this._cursor.entries.push({
				type: "ObjectProperty",
				key: this._curObjKey,
				value: ast
			});
		}
	}
	objSpread(ast) {
		if (this._cursor && Array.isArray(this._cursor.entries)) {
			let expr = ast;
			if (ast.type === "UnaryExpression" && ast.operator === "..." && ast.right) expr = ast.right;
			this._cursor.entries.push({
				type: "SpreadElement",
				expr
			});
			return;
		}
		if (this._cursor) {
			const cur = this._cursor;
			if (!Array.isArray(cur.entries)) {
				cur.entries = [];
				const obj = cur.value || {};
				for (const key of Object.keys(obj)) cur.entries.push({
					type: "ObjectProperty",
					key,
					value: obj[key]
				});
			}
			let expr = ast;
			if (ast.type === "UnaryExpression" && ast.operator === "..." && ast.right) expr = ast.right;
			cur.entries.push({
				type: "SpreadElement",
				expr
			});
		}
	}
	subExpression(ast) {
		this._placeAtCursor(ast);
	}
	ternaryEnd(ast) {
		if (this._cursor) this._cursor.alternate = ast;
	}
	ternaryMid(ast) {
		if (this._cursor) this._cursor.consequent = ast;
	}
	ternaryStart() {
		this._tree = {
			type: "ConditionalExpression",
			test: this._tree || void 0
		};
		this._cursor = this._tree;
	}
	transform(token) {
		const transformName = token.value;
		this._placeBeforeCursor({
			type: "FunctionCall",
			name: transformName,
			args: this._cursor ? [this._cursor] : [],
			pool: "transforms"
		});
	}
	unaryOp(token) {
		this._placeAtCursor({
			type: "UnaryExpression",
			operator: token.value
		});
	}
	_getHandlerMethod(tokenType) {
		switch (tokenType) {
			case "binaryOp": return this.binaryOp.bind(this);
			case "dot": return () => this.dot();
			case "identifier": return this.identifier.bind(this);
			case "literal": return this.literal.bind(this);
			case "template": return this.template.bind(this);
			case "unaryOp": return this.unaryOp.bind(this);
			case "pipe": return () => this.pipe();
			default: return;
		}
	}
	pipe() {
		if (this._state === "traverse") {
			this._placeAtCursor({
				type: "Identifier",
				value: ".",
				relative: true
			});
			this._relative = true;
		}
	}
	_getTokenHandlerMethod(handlerName) {
		switch (handlerName) {
			case "arrayStart": return () => this.arrayStart();
			case "noop": return () => {};
			case "functionCall": return () => this.functionCall();
			case "objKey": return this.objKey.bind(this);
			case "objStart": return () => this.objStart();
			case "ternaryStart": return () => this.ternaryStart();
			case "transform": return this.transform.bind(this);
			default: return;
		}
	}
	_getSubHandlerMethod(handlerName) {
		switch (handlerName) {
			case "argVal": return this.argVal.bind(this);
			case "arrayVal": return this.arrayVal.bind(this);
			case "arraySpread": return this.arraySpread.bind(this);
			case "filter": return this.filter.bind(this);
			case "objVal": return this.objVal.bind(this);
			case "objSpread": return this.objSpread.bind(this);
			case "subExpression": return this.subExpression.bind(this);
			case "ternaryEnd": return this.ternaryEnd.bind(this);
			case "ternaryMid": return this.ternaryMid.bind(this);
			default: return;
		}
	}
};
var ValidationSeverity;
(function(ValidationSeverity) {
	ValidationSeverity["ERROR"] = "error";
	ValidationSeverity["WARNING"] = "warning";
	ValidationSeverity["INFO"] = "info";
})(ValidationSeverity || (ValidationSeverity = {}));
var Autocomplete = class {
	constructor(grammar) {
		this._grammar = grammar;
		this._lexer = new Lexer(grammar);
	}
	getSuggestions(expression, cursorPosition, context) {
		try {
			if (cursorPosition < 0 || cursorPosition > expression.length) return { suggestions: [] };
			if (expression.length === 0) return { suggestions: this._getGeneralSuggestions(context) };
			const analysis = this._analyzeContext(expression, cursorPosition, context);
			const suggestions = this._getSuggestionsForContext(analysis, context);
			const filteredSuggestions = this._filterSuggestions(suggestions, analysis.partialIdentifier);
			const replaceRange = this._getReplaceRange(expression, cursorPosition, analysis);
			return {
				suggestions: filteredSuggestions,
				triggerCharacter: this._getTriggerCharacter(analysis),
				replaceRange
			};
		} catch {
			return { suggestions: [] };
		}
	}
	_analyzeContext(expression, cursorPosition, context) {
		const beforeCursor = expression.substring(0, cursorPosition);
		if (this._isInsideString(beforeCursor)) return {
			contextType: "general",
			insideString: true
		};
		if (this._isInsideTemplate(beforeCursor)) return {
			contextType: "general",
			insideTemplate: true
		};
		const tokens = this._lexer.tokenize(beforeCursor);
		if (tokens.length === 0) return { contextType: "general" };
		const lastToken = tokens[tokens.length - 1];
		const arrayFilterContext = this._analyzeArrayFilterContext(tokens, context, expression, cursorPosition);
		if (arrayFilterContext) return arrayFilterContext;
		switch (lastToken.type) {
			case "dot": return this._analyzeDotContext(tokens, context);
			case "pipe": return { contextType: "pipe" };
			case "openParen": return { contextType: "paren" };
			case "openBracket": return { contextType: "bracket" };
			case "comma": {
				const beforeCursor = expression.substring(0, cursorPosition);
				if (beforeCursor.endsWith(", ") || beforeCursor.endsWith(",")) return { contextType: "general" };
				return { contextType: "paren" };
			}
			case "identifier": {
				const partialId = this._getPartialIdentifier(expression, cursorPosition);
				if (partialId) {
					const dotContext = this._analyzeDotContext(tokens, context);
					if (dotContext.contextType === "dot" && dotContext.targetObject) return {
						contextType: "dot",
						targetObject: dotContext.targetObject,
						partialIdentifier: partialId
					};
					return {
						contextType: "identifier",
						partialIdentifier: partialId
					};
				}
				const afterCursor = expression.substring(cursorPosition);
				if (afterCursor.trim() === "" || afterCursor.startsWith(" ")) return { contextType: "operator" };
				return { contextType: "identifier" };
			}
			case "binaryOp":
			case "unaryOp": {
				const beforeCursor = expression.substring(0, cursorPosition);
				if (beforeCursor.endsWith(" && ") || beforeCursor.endsWith(" || ") || beforeCursor.endsWith(" > ") || beforeCursor.endsWith(" < ") || beforeCursor.endsWith(" >= ") || beforeCursor.endsWith(" <= ") || beforeCursor.endsWith(" == ") || beforeCursor.endsWith(" != ") || beforeCursor.endsWith(" + ") || beforeCursor.endsWith(" - ") || beforeCursor.endsWith(" * ") || beforeCursor.endsWith(" / ") || beforeCursor.endsWith(" % ")) return { contextType: "general" };
				return { contextType: "operator" };
			}
			default: return { contextType: "general" };
		}
	}
	_analyzeDotContext(tokens, context) {
		let targetObject = context;
		let namespace;
		const pathComponents = [];
		for (let i = tokens.length - 2; i >= 0; i--) {
			const token = tokens[i];
			if (token.type === "identifier") pathComponents.push(token.value);
			else if (token.type === "dot") continue;
			else if (token.type === "number" || token.type === "literal") pathComponents.push(typeof token.value === "number" ? token.value : parseInt(token.value, 10));
			else if (token.type === "closeBracket") continue;
			else if (token.type === "openBracket") continue;
			else break;
		}
		for (const component of pathComponents.reverse()) if (targetObject && typeof targetObject === "object") if (typeof component === "number" && Array.isArray(targetObject)) targetObject = targetObject[component];
		else if (typeof component === "string") targetObject = targetObject[component];
		else {
			targetObject = void 0;
			break;
		}
		else {
			targetObject = void 0;
			break;
		}
		const lastIdentifier = this._findLastIdentifier(tokens);
		if (lastIdentifier && this._isNamespace(lastIdentifier)) namespace = lastIdentifier;
		return {
			contextType: "dot",
			targetObject,
			namespace
		};
	}
	_analyzeArrayFilterContext(tokens, context, expression, cursorPosition) {
		let firstBracketIndex = -1;
		let targetArray = null;
		let arrayElement = null;
		for (let i = 0; i < tokens.length; i++) if (tokens[i].type === "openBracket") {
			if (i + 1 < tokens.length && tokens[i + 1].type === "dot") {
				firstBracketIndex = i;
				let tempTargetArray = context;
				const identifiers = [];
				for (let j = i - 1; j >= 0; j--) {
					const token = tokens[j];
					if (token.type === "identifier") identifiers.push(token.value);
					else if (token.type === "dot") continue;
					else break;
				}
				for (const identifier of identifiers.reverse()) if (tempTargetArray && typeof tempTargetArray === "object") tempTargetArray = tempTargetArray[identifier];
				else return null;
				if (Array.isArray(tempTargetArray) && tempTargetArray.length > 0) {
					targetArray = tempTargetArray;
					arrayElement = tempTargetArray[0];
					break;
				}
			}
		}
		if (firstBracketIndex === -1 || !targetArray || !arrayElement) return null;
		let currentBracketIndex = -1;
		let dotAfterCurrentBracket = false;
		for (let i = tokens.length - 1; i >= 0; i--) if (tokens[i].type === "openBracket") {
			if (i + 1 < tokens.length && tokens[i + 1].type === "dot") {
				currentBracketIndex = i;
				dotAfterCurrentBracket = true;
				break;
			}
		}
		if (currentBracketIndex === -1 || !dotAfterCurrentBracket) {
			for (let i = tokens.length - 1; i >= 0; i--) if (tokens[i].type === "closeBracket") {
				if (i + 1 < tokens.length && tokens[i + 1].type === "dot") {
					let bracketCount = 1;
					for (let j = i - 1; j >= 0; j--) if (tokens[j].type === "closeBracket") bracketCount++;
					else if (tokens[j].type === "openBracket") {
						bracketCount--;
						if (bracketCount === 0) {
							currentBracketIndex = j;
							dotAfterCurrentBracket = true;
							break;
						}
					}
					break;
				}
			}
		}
		if (currentBracketIndex === -1 || !dotAfterCurrentBracket) return null;
		if (tokens.length === currentBracketIndex + 2 && tokens[tokens.length - 1].type === "dot") {
			let elementTarget = arrayElement;
			const elementIdentifiers = [];
			for (let i = firstBracketIndex + 2; i < currentBracketIndex; i++) {
				const token = tokens[i];
				if (token.type === "identifier") elementIdentifiers.push(token.value);
				else if (token.type === "dot") continue;
				else if (token.type === "openBracket") {
					if (i + 1 < tokens.length && tokens[i + 1].type === "dot") {
						for (const identifier of elementIdentifiers) if (elementTarget && typeof elementTarget === "object") elementTarget = elementTarget[identifier];
						else return null;
						if (Array.isArray(elementTarget) && elementTarget.length > 0) {
							elementTarget = elementTarget[0];
							elementIdentifiers.length = 0;
						} else return null;
					}
					continue;
				}
			}
			for (const identifier of elementIdentifiers) if (elementTarget && typeof elementTarget === "object") {
				elementTarget = elementTarget[identifier];
				if (Array.isArray(elementTarget) && elementTarget.length > 0) elementTarget = elementTarget[0];
			} else return null;
			return {
				contextType: "arrayFilter",
				targetObject: elementTarget,
				arrayElementType: arrayElement,
				relativeContext: true,
				partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
			};
		}
		if (tokens.length > 2 && (tokens[tokens.length - 1].type === "dot" || tokens[tokens.length - 1].type === "identifier")) {
			const prevToken = tokens[tokens.length - 2];
			if (prevToken.type === "closeBracket" || prevToken.type === "dot" && tokens[tokens.length - 3]?.type === "closeBracket") {
				let bracketCount = 1;
				let foundNumber = false;
				const startIndex = prevToken.type === "closeBracket" ? tokens.length - 3 : tokens.length - 4;
				for (let i = startIndex; i >= 0; i--) if (tokens[i].type === "closeBracket") bracketCount++;
				else if (tokens[i].type === "openBracket") {
					bracketCount--;
					if (bracketCount === 0) {
						if (i + 1 < tokens.length && (tokens[i + 1].type === "number" || tokens[i + 1].type === "literal")) {
							foundNumber = true;
							currentBracketIndex = i;
							dotAfterCurrentBracket = true;
						}
						break;
					}
				}
				if (foundNumber) {
					let elementTarget = arrayElement;
					const elementIdentifiers = [];
					for (let i = firstBracketIndex + 2; i < currentBracketIndex; i++) {
						const token = tokens[i];
						if (token.type === "identifier") elementIdentifiers.push(token.value);
						else if (token.type === "dot") continue;
						else if (token.type === "openBracket") continue;
					}
					for (const identifier of elementIdentifiers) if (elementTarget && typeof elementTarget === "object") elementTarget = elementTarget[identifier];
					else return null;
					return {
						contextType: "arrayFilter",
						targetObject: elementTarget,
						arrayElementType: arrayElement,
						relativeContext: true,
						partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
					};
				}
			}
		}
		let elementTarget = arrayElement;
		const elementIdentifiers = [];
		for (let i = firstBracketIndex + 2; i < currentBracketIndex; i++) {
			const token = tokens[i];
			if (token.type === "identifier") elementIdentifiers.push(token.value);
			else if (token.type === "dot") continue;
			else if (token.type === "openBracket") {
				if (i + 1 < tokens.length && tokens[i + 1].type === "dot") {
					for (const identifier of elementIdentifiers) if (elementTarget && typeof elementTarget === "object") elementTarget = elementTarget[identifier];
					else return null;
					if (Array.isArray(elementTarget) && elementTarget.length > 0) {
						elementTarget = elementTarget[0];
						elementIdentifiers.length = 0;
					} else return null;
				}
				continue;
			}
		}
		for (let i = currentBracketIndex + 2; i < tokens.length; i++) {
			const token = tokens[i];
			if (token.type === "identifier") elementIdentifiers.push(token.value);
			else if (token.type === "dot") continue;
			else if (token.type === "openBracket") break;
			else continue;
		}
		const lastToken = tokens[tokens.length - 1];
		if (lastToken.type === "identifier" && lastToken.value.length > 0 && expression && cursorPosition !== void 0) {
			const partialIdentifier = this._getPartialIdentifier(expression, cursorPosition);
			if (partialIdentifier && partialIdentifier.length < lastToken.value.length) elementIdentifiers.pop();
			else if (partialIdentifier && partialIdentifier === lastToken.value) {
				if (cursorPosition === expression.length || cursorPosition < expression.length && /[a-zA-Z0-9_]/.test(expression[cursorPosition])) elementIdentifiers.pop();
			}
		}
		for (const identifier of elementIdentifiers) if (elementTarget && typeof elementTarget === "object") {
			elementTarget = elementTarget[identifier];
			if (Array.isArray(elementTarget) && elementTarget.length > 0) elementTarget = elementTarget[0];
		} else return null;
		if (tokens.length > 0 && tokens[tokens.length - 1].type === "dot") return {
			contextType: "arrayFilter",
			targetObject: elementTarget,
			arrayElementType: arrayElement,
			relativeContext: true,
			partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
		};
		if (tokens.length > 0 && tokens[tokens.length - 1].type === "identifier" && expression && cursorPosition !== void 0) {
			const partialIdentifier = this._getPartialIdentifier(expression, cursorPosition);
			if (partialIdentifier && partialIdentifier.length < tokens[tokens.length - 1].value.length) return {
				contextType: "arrayFilter",
				targetObject: elementTarget,
				arrayElementType: arrayElement,
				relativeContext: true,
				partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
			};
			else if (partialIdentifier && partialIdentifier === tokens[tokens.length - 1].value) if (cursorPosition === expression.length || cursorPosition < expression.length && /[a-zA-Z0-9_]/.test(expression[cursorPosition])) {
				if (elementTarget && typeof elementTarget === "object") {
					let hasPartialMatch = false;
					for (const key of Object.keys(elementTarget)) if (key.toLowerCase().startsWith(partialIdentifier.toLowerCase()) && key.toLowerCase() !== partialIdentifier.toLowerCase()) {
						hasPartialMatch = true;
						break;
					}
					if (hasPartialMatch) return {
						contextType: "arrayFilter",
						targetObject: elementTarget,
						arrayElementType: arrayElement,
						relativeContext: true,
						partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
					};
				}
				return {
					contextType: "arrayFilter",
					targetObject: null,
					arrayElementType: arrayElement,
					relativeContext: true,
					partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
				};
			} else return {
				contextType: "arrayFilter",
				targetObject: elementTarget,
				arrayElementType: arrayElement,
				relativeContext: true,
				partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
			};
			else {
				if (elementTarget && typeof elementTarget === "object") {
					const partialIdentifier = this._getPartialIdentifier(expression, cursorPosition);
					if (partialIdentifier && partialIdentifier.length > 0) {
						let hasPartialMatch = false;
						for (const key of Object.keys(elementTarget)) if (key.toLowerCase().startsWith(partialIdentifier.toLowerCase()) && key.toLowerCase() !== partialIdentifier.toLowerCase()) {
							hasPartialMatch = true;
							break;
						}
						if (hasPartialMatch) return {
							contextType: "arrayFilter",
							targetObject: elementTarget,
							arrayElementType: arrayElement,
							relativeContext: true,
							partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
						};
					}
				}
				return {
					contextType: "arrayFilter",
					targetObject: null,
					arrayElementType: arrayElement,
					relativeContext: true,
					partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
				};
			}
		}
		return {
			contextType: "arrayFilter",
			targetObject: elementTarget,
			arrayElementType: arrayElement,
			relativeContext: true,
			partialIdentifier: expression && cursorPosition !== void 0 ? this._getPartialIdentifier(expression, cursorPosition) : void 0
		};
	}
	_getSuggestionsForContext(analysis, context) {
		if (analysis.insideString || analysis.insideTemplate) return [];
		switch (analysis.contextType) {
			case "dot": return this._getPropertySuggestions(analysis.targetObject, analysis.namespace);
			case "arrayFilter": return this._getArrayFilterSuggestions(analysis);
			case "pipe": return this._getTransformSuggestions(analysis.namespace);
			case "function": return this._getFunctionSuggestions(analysis.namespace);
			case "identifier": return this._getIdentifierSuggestions(context);
			case "operator": return this._getOperatorSuggestions();
			case "bracket":
			case "paren": return this._getGeneralSuggestions(context);
			default: return this._getGeneralSuggestions(context);
		}
	}
	_getPropertySuggestions(targetObject, namespace) {
		const suggestions = [];
		if (namespace) {
			const transformSuggestions = this._getNamespaceTransformSuggestions(namespace);
			const functionSuggestions = this._getNamespaceFunctionSuggestions(namespace);
			return [...transformSuggestions, ...functionSuggestions];
		}
		if (targetObject && typeof targetObject === "object") for (const key of Object.keys(targetObject)) {
			const value = targetObject[key];
			const type = this._getValueType(value);
			suggestions.push({
				label: key,
				value: key,
				type: "property",
				description: `Property of type ${type}`,
				detail: type
			});
		}
		return suggestions;
	}
	_getArrayFilterSuggestions(analysis) {
		const suggestions = [];
		if (analysis.targetObject === null) return suggestions;
		if (analysis.targetObject && typeof analysis.targetObject === "object") for (const key of Object.keys(analysis.targetObject)) {
			const value = analysis.targetObject[key];
			const type = this._getValueType(value);
			if (analysis.partialIdentifier && !key.toLowerCase().startsWith(analysis.partialIdentifier.toLowerCase())) continue;
			suggestions.push({
				label: key,
				value: key,
				type: "property",
				description: `Property of type ${type}`,
				detail: type
			});
		}
		else if (analysis.arrayElementType && typeof analysis.arrayElementType === "object") for (const key of Object.keys(analysis.arrayElementType)) {
			const value = analysis.arrayElementType[key];
			const type = this._getValueType(value);
			if (analysis.partialIdentifier && !key.toLowerCase().startsWith(analysis.partialIdentifier.toLowerCase())) continue;
			suggestions.push({
				label: key,
				value: key,
				type: "property",
				description: `Property of type ${type}`,
				detail: type
			});
		}
		return suggestions;
	}
	_getTransformSuggestions(namespace) {
		const suggestions = [];
		if (namespace) return this._getNamespaceTransformSuggestions(namespace);
		for (const [name, transform] of Object.entries(this._grammar.transforms)) suggestions.push({
			label: name,
			value: name,
			type: "transform",
			description: "Transform function",
			signature: this._getFunctionSignature(transform)
		});
		return suggestions;
	}
	_getNamespaceTransformSuggestions(namespace) {
		const suggestions = [];
		const prefix = `${namespace}.`;
		for (const [name, transform] of Object.entries(this._grammar.transforms)) if (name.startsWith(prefix)) {
			const shortName = name.substring(prefix.length);
			suggestions.push({
				label: shortName,
				value: shortName,
				type: "transform",
				description: `Transform function in ${namespace} namespace`,
				signature: this._getFunctionSignature(transform)
			});
		}
		return suggestions;
	}
	_getFunctionSuggestions(namespace) {
		const suggestions = [];
		if (namespace) return this._getNamespaceFunctionSuggestions(namespace);
		for (const [name, func] of Object.entries(this._grammar.functions)) suggestions.push({
			label: name,
			value: name,
			type: "function",
			description: "Function",
			signature: this._getFunctionSignature(func)
		});
		return suggestions;
	}
	_getNamespaceFunctionSuggestions(namespace) {
		const suggestions = [];
		const prefix = `${namespace}.`;
		for (const [name, func] of Object.entries(this._grammar.functions)) if (name.startsWith(prefix)) {
			const shortName = name.substring(prefix.length);
			suggestions.push({
				label: shortName,
				value: shortName,
				type: "function",
				description: `Function in ${namespace} namespace`,
				signature: this._getFunctionSignature(func)
			});
		}
		return suggestions;
	}
	_getIdentifierSuggestions(context) {
		const suggestions = [];
		if (context) for (const [key, value] of Object.entries(context)) {
			const type = this._getValueType(value);
			suggestions.push({
				label: key,
				value: key,
				type: "identifier",
				description: `Variable of type ${type}`,
				detail: type
			});
		}
		for (const [name, func] of Object.entries(this._grammar.functions)) suggestions.push({
			label: name,
			value: name,
			type: "function",
			description: "Function",
			signature: this._getFunctionSignature(func)
		});
		suggestions.push(...[
			{
				label: "true",
				value: "true",
				type: "keyword",
				description: "Boolean true"
			},
			{
				label: "false",
				value: "false",
				type: "keyword",
				description: "Boolean false"
			},
			{
				label: "null",
				value: "null",
				type: "keyword",
				description: "Null value"
			},
			{
				label: "undefined",
				value: "undefined",
				type: "keyword",
				description: "Undefined value"
			}
		]);
		return suggestions;
	}
	_getOperatorSuggestions() {
		const suggestions = [];
		for (const [op, element] of Object.entries(this._grammar.elements)) if (element.type === "binaryOp" || element.type === "unaryOp") suggestions.push({
			label: op,
			value: op,
			type: "operator",
			description: `${element.type === "binaryOp" ? "Binary" : "Unary"} operator`
		});
		return suggestions;
	}
	_getGeneralSuggestions(context) {
		const suggestions = [];
		if (context) for (const [key, value] of Object.entries(context)) {
			const type = this._getValueType(value);
			suggestions.push({
				label: key,
				value: key,
				type: "identifier",
				description: `Variable of type ${type}`,
				detail: type
			});
		}
		for (const [name, func] of Object.entries(this._grammar.functions)) suggestions.push({
			label: name,
			value: name,
			type: "function",
			description: "Function",
			signature: this._getFunctionSignature(func)
		});
		for (const [name, transform] of Object.entries(this._grammar.transforms)) suggestions.push({
			label: name,
			value: name,
			type: "transform",
			description: "Transform function",
			signature: this._getFunctionSignature(transform)
		});
		suggestions.push(...[
			{
				label: "true",
				value: "true",
				type: "keyword",
				description: "Boolean true"
			},
			{
				label: "false",
				value: "false",
				type: "keyword",
				description: "Boolean false"
			},
			{
				label: "null",
				value: "null",
				type: "keyword",
				description: "Null value"
			},
			{
				label: "undefined",
				value: "undefined",
				type: "keyword",
				description: "Undefined value"
			}
		]);
		return suggestions;
	}
	_filterSuggestions(suggestions, partialIdentifier) {
		if (!partialIdentifier) return suggestions;
		const lowerPartial = partialIdentifier.toLowerCase();
		return suggestions.filter((suggestion) => {
			return (suggestion.filterText || suggestion.label).toLowerCase().includes(lowerPartial);
		});
	}
	_getReplaceRange(expression, cursorPosition, analysis) {
		if (analysis.partialIdentifier) {
			const match = expression.substring(0, cursorPosition).match(/([a-zA-Z_$][a-zA-Z0-9_$]*)$/);
			if (match) return {
				start: cursorPosition - match[1].length,
				end: cursorPosition
			};
		}
		return {
			start: cursorPosition,
			end: cursorPosition
		};
	}
	_getTriggerCharacter(analysis) {
		switch (analysis.contextType) {
			case "dot": return ".";
			case "pipe": return "|";
			case "paren": return "(";
			case "bracket": return "[";
			default: return;
		}
	}
	_isInsideString(text) {
		let inString = false;
		let escapeNext = false;
		let quoteChar = "";
		for (const char of text) {
			if (escapeNext) {
				escapeNext = false;
				continue;
			}
			if (char === "\\") {
				escapeNext = true;
				continue;
			}
			if (!inString && (char === "\"" || char === "'")) {
				inString = true;
				quoteChar = char;
				continue;
			}
			if (inString && char === quoteChar) {
				inString = false;
				quoteChar = "";
			}
		}
		return inString;
	}
	_isInsideTemplate(text) {
		let inTemplate = false;
		let escapeNext = false;
		for (const char of text) {
			if (escapeNext) {
				escapeNext = false;
				continue;
			}
			if (char === "\\") {
				escapeNext = true;
				continue;
			}
			if (char === "`") inTemplate = !inTemplate;
		}
		return inTemplate;
	}
	_getPartialIdentifier(expression, cursorPosition) {
		const match = expression.substring(0, cursorPosition).match(/([a-zA-Z_$][a-zA-Z0-9_$]*)$/);
		return match ? match[1] : void 0;
	}
	_findLastIdentifier(tokens) {
		for (let i = tokens.length - 1; i >= 0; i--) if (tokens[i].type === "identifier") return tokens[i].value;
	}
	_isNamespace(identifier) {
		const prefix = `${identifier}.`;
		for (const name of Object.keys(this._grammar.transforms)) if (name.startsWith(prefix)) return true;
		for (const name of Object.keys(this._grammar.functions)) if (name.startsWith(prefix)) return true;
		return false;
	}
	_getValueType(value) {
		if (value === null) return "null";
		if (value === void 0) return "undefined";
		if (Array.isArray(value)) return "array";
		if (value instanceof Date) return "date";
		return typeof value;
	}
	_getFunctionSignature(func) {
		const match = func.toString().match(/\([^)]*\)/);
		return match ? match[0] : "()";
	}
};
const poolNames = {
	functions: "Jexl Function",
	transforms: "Transform"
};
var Evaluator = class Evaluator {
	constructor(grammar, context, relativeContext) {
		this._grammar = grammar;
		this._context = context || {};
		this._relContext = relativeContext || this._context;
	}
	async eval(ast) {
		switch (ast.type) {
			case "ArrayLiteral": return this._handleArrayLiteral(ast);
			case "BinaryExpression": return this._handleBinaryExpression(ast);
			case "ConditionalExpression": return this._handleConditionalExpression(ast);
			case "FilterExpression": return this._handleFilterExpression(ast);
			case "Identifier": return this._handleIdentifier(ast);
			case "Literal": return this._handleLiteral(ast);
			case "TemplateLiteral": return this._handleTemplateLiteral(ast);
			case "ObjectLiteral": return this._handleObjectLiteral(ast);
			case "FunctionCall": return this._handleFunctionCall(ast);
			case "UnaryExpression": return this._handleUnaryExpression(ast);
			default: throw new Error(`Unknown AST node type: ${ast.type}`);
		}
	}
	evalArray(arr) {
		return Promise.all(arr.map((elem) => this.eval(elem)));
	}
	async evalMap(map) {
		const keys = Object.keys(map);
		const result = {};
		const asts = keys.map((key) => {
			const ast = map[key];
			if (!ast) throw new Error(`No AST found for key: ${key}`);
			return this.eval(ast);
		});
		(await Promise.all(asts)).forEach((val, idx) => {
			const key = keys[idx];
			if (key !== void 0) result[key] = val;
		});
		return result;
	}
	async _filterRelative(subject, expr) {
		const promises = [];
		let subjectArray;
		if (!Array.isArray(subject)) subjectArray = subject === void 0 ? [] : [subject];
		else subjectArray = subject;
		subjectArray.forEach((elem) => {
			const evalInst = new Evaluator(this._grammar, this._context, elem);
			promises.push(evalInst.eval(expr));
		});
		const values = await Promise.all(promises);
		if (values.every((v) => typeof v === "boolean")) {
			const filtered = [];
			values.forEach((value, idx) => {
				if (value) filtered.push(subjectArray[idx]);
			});
			return filtered;
		}
		return values;
	}
	async _filterStatic(subject, expr) {
		const res = await this.eval(expr);
		if (typeof res === "boolean") return res ? subject : void 0;
		if (subject === void 0) return;
		if (subject === null) return null;
		if (typeof subject === "object" || Array.isArray(subject)) return subject[res];
	}
	async _handleArrayLiteral(ast) {
		if (Array.isArray(ast.entries) && ast.entries.length > 0) {
			const result = [];
			for (const entry of ast.entries) if (entry.type === "SpreadElement") {
				const spreadVal = await this.eval(entry.expr);
				if (spreadVal == null) continue;
				if (typeof spreadVal[Symbol.iterator] !== "function") throw new TypeError("Spread value is not iterable");
				for (const item of spreadVal) result.push(item);
			} else result.push(await this.eval(entry));
			return result;
		}
		return this.evalArray(ast.value);
	}
	async _handleBinaryExpression(ast) {
		const grammarOp = this._grammar.elements[ast.operator];
		if (!grammarOp) throw new Error(`Unknown binary operator: ${ast.operator}`);
		if ("evalOnDemand" in grammarOp && grammarOp.evalOnDemand) {
			const wrap = (subAst) => ({ eval: () => this.eval(subAst) });
			return grammarOp.evalOnDemand(wrap(ast.left), wrap(ast.right));
		}
		if ("eval" in grammarOp && grammarOp.eval) {
			const [leftVal, rightVal] = await Promise.all([this.eval(ast.left), this.eval(ast.right)]);
			return grammarOp.eval(leftVal, rightVal);
		}
		throw new Error(`Binary operator ${ast.operator} has no eval function`);
	}
	async _handleConditionalExpression(ast) {
		const res = await this.eval(ast.test);
		if (res) {
			if (ast.consequent) return this.eval(ast.consequent);
			return res;
		}
		return this.eval(ast.alternate);
	}
	async _handleFilterExpression(ast) {
		const subject = await this.eval(ast.subject);
		if (ast.relative) return this._filterRelative(subject, ast.expr);
		return this._filterStatic(subject, ast.expr);
	}
	async _handleIdentifier(ast) {
		if (!ast.from) return ast.relative ? this._relContext[ast.value] : this._context[ast.value];
		const context = await this.eval(ast.from);
		if (context === void 0) return;
		if (context === null) return null;
		let targetContext = context;
		if (Array.isArray(context)) targetContext = context[0];
		return targetContext?.[ast.value];
	}
	_handleLiteral(ast) {
		return ast.value;
	}
	async _handleTemplateLiteral(ast) {
		const parts = ast.parts || [];
		const out = [];
		for (const part of parts) if (part.type === "Literal") out.push(String(part.value ?? ""));
		else {
			const val = await this.eval(part);
			out.push(val === void 0 || val === null ? "" : String(val));
		}
		return out.join("");
	}
	async _handleObjectLiteral(ast) {
		if (Array.isArray(ast.entries) && ast.entries.length > 0) {
			const out = {};
			for (const entry of ast.entries) if (entry.type === "SpreadElement") {
				const spreadVal = await this.eval(entry.expr);
				if (spreadVal != null) Object.assign(out, spreadVal);
			} else if (entry.type === "ObjectProperty") out[entry.key] = await this.eval(entry.value);
			return out;
		}
		return this.evalMap(ast.value);
	}
	async _handleFunctionCall(ast) {
		const poolName = poolNames[ast.pool];
		if (!poolName) throw new Error(`Corrupt AST: Pool '${ast.pool}' not found`);
		const func = this._grammar[ast.pool][ast.name];
		if (!func) throw new Error(`${poolName} ${ast.name} is not defined.`);
		const args = await this.evalArray(ast.args || []);
		return func.bind({ context: this._context })(...args);
	}
	async _handleUnaryExpression(ast) {
		const right = await this.eval(ast.right);
		const grammarOp = this._grammar.elements[ast.operator];
		if (!grammarOp) throw new Error(`Unknown unary operator: ${ast.operator}`);
		if ("eval" in grammarOp && grammarOp.eval) return grammarOp.eval(right);
		throw new Error(`Unary operator ${ast.operator} has no eval function`);
	}
};
var Expression = class {
	constructor(grammar, exprStr) {
		this._grammar = grammar;
		this._exprStr = exprStr;
		this._ast = null;
	}
	compile() {
		const lexer = new Lexer(this._grammar);
		const parser = new Parser(this._grammar);
		parser.addTokens(lexer.tokenize(this._exprStr));
		this._ast = parser.complete();
		return this;
	}
	eval(context = {}) {
		return this._eval(context);
	}
	async evalAsString(context = {}) {
		const result = await this.eval(context);
		if (result === null) return "null";
		if (result === void 0) return "undefined";
		return String(result);
	}
	async evalAsNumber(context = {}) {
		const result = await this.eval(context);
		if (result === null || result === void 0) return NaN;
		return Number(result);
	}
	async evalAsBoolean(context = {}) {
		return !!await this.eval(context);
	}
	async evalAsArray(context = {}) {
		const result = await this.eval(context);
		if (result === null || result === void 0) return [];
		if (Array.isArray(result)) return result;
		return [result];
	}
	async evalAsEnum(context = {}, allowedValues) {
		const result = await this.eval(context);
		if (allowedValues.includes(result)) return result;
	}
	async evalWithDefault(context = {}, defaultValue) {
		const result = await this.eval(context);
		if (result === null || result === void 0) return defaultValue;
		return result;
	}
	async _eval(context) {
		const ast = this._getAst();
		if (!ast) throw new Error("No AST available for evaluation. Expression may not be compiled.");
		return new Evaluator(this._grammar, context, context).eval(ast);
	}
	_getAst() {
		if (!this._ast) this.compile();
		return this._ast;
	}
};
const getGrammar = () => ({
	elements: {
		".": { type: "dot" },
		"[": { type: "openBracket" },
		"]": { type: "closeBracket" },
		"|": { type: "pipe" },
		"{": { type: "openCurl" },
		"}": { type: "closeCurl" },
		":": { type: "colon" },
		",": { type: "comma" },
		"(": { type: "openParen" },
		")": { type: "closeParen" },
		"?": { type: "question" },
		"...": { type: "unaryOp" },
		"+": {
			type: "binaryOp",
			precedence: 30,
			eval: function(left, right) {
				if (arguments.length === 1) return +left;
				return left + right;
			}
		},
		"-": {
			type: "binaryOp",
			precedence: 30,
			eval: function(left, right) {
				if (arguments.length === 1) return -left;
				return left - right;
			}
		},
		"*": {
			type: "binaryOp",
			precedence: 40,
			eval: (left, right) => left * right
		},
		"/": {
			type: "binaryOp",
			precedence: 40,
			eval: (left, right) => left / right
		},
		"//": {
			type: "binaryOp",
			precedence: 40,
			eval: (left, right) => Math.floor(left / right)
		},
		"%": {
			type: "binaryOp",
			precedence: 50,
			eval: (left, right) => left % right
		},
		"^": {
			type: "binaryOp",
			precedence: 50,
			eval: (left, right) => Math.pow(left, right)
		},
		"**": {
			type: "binaryOp",
			precedence: 70,
			eval: (left, right) => left ** right
		},
		"==": {
			type: "binaryOp",
			precedence: 20,
			eval: (left, right) => left === right
		},
		"!=": {
			type: "binaryOp",
			precedence: 20,
			eval: (left, right) => left !== right
		},
		"~=": {
			type: "binaryOp",
			precedence: 20,
			eval: (left, right) => Math.abs(left - right) <= .01
		},
		"!~=": {
			type: "binaryOp",
			precedence: 20,
			eval: (left, right) => Math.abs(left - right) > .01
		},
		">": {
			type: "binaryOp",
			precedence: 20,
			eval: (left, right) => left > right
		},
		">=": {
			type: "binaryOp",
			precedence: 20,
			eval: (left, right) => left >= right
		},
		"<": {
			type: "binaryOp",
			precedence: 20,
			eval: (left, right) => left < right
		},
		"<=": {
			type: "binaryOp",
			precedence: 20,
			eval: (left, right) => left <= right
		},
		"&&": {
			type: "binaryOp",
			precedence: 10,
			evalOnDemand: (left, right) => {
				return left.eval().then((leftVal) => {
					if (!leftVal) return leftVal;
					return right.eval();
				});
			}
		},
		"||": {
			type: "binaryOp",
			precedence: 5,
			evalOnDemand: (left, right) => {
				return left.eval().then((leftVal) => {
					if (leftVal) return leftVal;
					return right.eval();
				});
			}
		},
		"and": {
			type: "binaryOp",
			precedence: 10,
			eval: (left, right) => {
				const isValidationError = (val) => {
					if (typeof val === "string") return true;
					if (typeof val === "object" && val !== null && !Array.isArray(val)) return Object.values(val).every((v) => typeof v === "string");
					return false;
				};
				const isValidationErrorArray = (val) => {
					return Array.isArray(val) && val.length > 0 && val.every((item) => isValidationError(item));
				};
				const leftIsArray = isValidationErrorArray(left);
				const rightIsArray = isValidationErrorArray(right);
				const leftIsError = isValidationError(left);
				const rightIsError = isValidationError(right);
				if (!left && !leftIsError && !leftIsArray) return false;
				const errors = [];
				if (leftIsArray) errors.push(...left);
				else if (leftIsError) errors.push(left);
				if (rightIsArray) errors.push(...right);
				else if (rightIsError) errors.push(right);
				if (errors.length > 0) {
					if (!right && !rightIsError && !rightIsArray) return false;
					return errors;
				}
				if (left && right) return true;
				return false;
			}
		},
		"or": {
			type: "binaryOp",
			precedence: 10,
			eval: (left, right) => {
				const isValidationError = (val) => {
					if (typeof val === "string") return true;
					if (typeof val === "object" && val !== null && !Array.isArray(val)) return Object.values(val).every((v) => typeof v === "string");
					return false;
				};
				const isValidationErrorArray = (val) => {
					return Array.isArray(val) && val.length > 0 && val.every((item) => isValidationError(item));
				};
				const leftIsArray = isValidationErrorArray(left);
				const leftIsError = isValidationError(left);
				const rightIsError = isValidationError(right);
				if ((leftIsError || leftIsArray) && right && !rightIsError) return right;
				if ((leftIsError || leftIsArray) && rightIsError) return [right];
				if (left && !leftIsError && !leftIsArray) return left;
				return right;
			}
		},
		"xor": {
			type: "binaryOp",
			precedence: 10,
			eval: (left, right) => {
				const isValidationError = (val) => {
					if (typeof val === "string") return true;
					if (typeof val === "object" && val !== null && !Array.isArray(val)) return Object.values(val).every((v) => typeof v === "string");
					return false;
				};
				const isValidationErrorArray = (val) => {
					return Array.isArray(val) && val.length > 0 && val.every((item) => isValidationError(item));
				};
				const leftIsArray = isValidationErrorArray(left);
				const rightIsArray = isValidationErrorArray(right);
				const leftIsError = isValidationError(left);
				const rightIsError = isValidationError(right);
				const errors = [];
				if (leftIsArray) errors.push(...left);
				else if (leftIsError) errors.push(left);
				if (rightIsArray) errors.push(...right);
				else if (rightIsError) errors.push(right);
				if (errors.length > 0) {
					if (errors.length === 1 && (leftIsError || leftIsArray) !== (rightIsError || rightIsArray)) return errors;
					return errors.length === 1 ? errors[0] : errors;
				}
				return Boolean(left) !== Boolean(right);
			}
		},
		"in": {
			type: "binaryOp",
			precedence: 20,
			eval: (left, right) => {
				const isObjectLike = (v) => typeof v === "object" && v !== null;
				const deepEqual = (a, b) => {
					if (a === b) return true;
					if (a instanceof Date && b instanceof Date) return a.getTime() === b.getTime();
					if (Array.isArray(a) && Array.isArray(b)) {
						if (a.length !== b.length) return false;
						for (let i = 0; i < a.length; i++) if (!deepEqual(a[i], b[i])) return false;
						return true;
					}
					if (isObjectLike(a) && isObjectLike(b)) {
						const aKeys = Object.keys(a);
						const bKeys = Object.keys(b);
						if (aKeys.length !== bKeys.length) return false;
						for (const key of aKeys) {
							if (!Object.prototype.hasOwnProperty.call(b, key)) return false;
							if (!deepEqual(a[key], b[key])) return false;
						}
						return true;
					}
					return false;
				};
				if (typeof right === "string") return right.indexOf(left) !== -1;
				if (Array.isArray(right)) {
					if (isObjectLike(left) && "id" in left) {
						const leftId = left.id;
						return right.some((elem) => isObjectLike(elem) && "id" in elem ? elem.id === leftId : elem === leftId);
					}
					return right.some((elem) => deepEqual(elem, left));
				}
				return false;
			}
		},
		"!": {
			type: "unaryOp",
			precedence: Infinity,
			eval: (right) => !right
		}
	},
	functions: {},
	transforms: {}
});
var Jexl = class {
	constructor() {
		this.expr = this.expr.bind(this);
		this.grammar = getGrammar();
	}
	addBinaryOp(operator, precedence, fn, manualEval) {
		const element = {
			type: "binaryOp",
			precedence
		};
		if (manualEval) element.evalOnDemand = fn;
		else element.eval = fn;
		this._addGrammarElement(operator, element);
	}
	addFunction(name, fn) {
		this.grammar.functions[name] = fn;
	}
	addFunctions(map) {
		for (const key in map) if (Object.prototype.hasOwnProperty.call(map, key)) {
			const fn = map[key];
			if (fn) this.grammar.functions[key] = fn;
		}
	}
	addUnaryOp(operator, fn) {
		const element = {
			type: "unaryOp",
			weight: Infinity,
			eval: fn
		};
		this._addGrammarElement(operator, element);
	}
	addTransform(name, fn) {
		this.grammar.transforms[name] = fn;
	}
	addTransforms(map) {
		for (const key in map) if (Object.prototype.hasOwnProperty.call(map, key)) {
			const fn = map[key];
			if (fn) this.grammar.transforms[key] = fn;
		}
	}
	compile(expression) {
		return this.createExpression(expression).compile();
	}
	createExpression(expression) {
		return new Expression(this.grammar, expression);
	}
	getFunction(name) {
		const fn = this.grammar.functions[name];
		if (!fn) throw new Error(`Function '${name}' is not defined`);
		return fn;
	}
	getTransform(name) {
		const fn = this.grammar.transforms[name];
		if (!fn) throw new Error(`Transform '${name}' is not defined`);
		return fn;
	}
	eval(expression, context = {}) {
		return this.createExpression(expression).eval(context);
	}
	evalAsString(expression, context = {}) {
		return this.createExpression(expression).evalAsString(context);
	}
	evalAsNumber(expression, context = {}) {
		return this.createExpression(expression).evalAsNumber(context);
	}
	evalAsBoolean(expression, context = {}) {
		return this.createExpression(expression).evalAsBoolean(context);
	}
	evalAsArray(expression, context = {}) {
		return this.createExpression(expression).evalAsArray(context);
	}
	evalAsEnum(expression, context = {}, allowedValues) {
		return this.createExpression(expression).evalAsEnum(context, allowedValues);
	}
	evalWithDefault(expression, context = {}, defaultValue) {
		return this.createExpression(expression).evalWithDefault(context, defaultValue);
	}
	expr(strings, ...args) {
		const exprStr = strings.reduce((acc, str, idx) => {
			const arg = idx < args.length ? args[idx] : "";
			acc += str + arg;
			return acc;
		}, "");
		return this.createExpression(exprStr);
	}
	removeOp(operator) {
		if (Object.prototype.hasOwnProperty.call(this.grammar.elements, operator) && (this.grammar.elements[operator].type === "binaryOp" || this.grammar.elements[operator].type === "unaryOp")) delete this.grammar.elements[operator];
	}
	autocomplete(expression, cursorPosition, context = {}) {
		return new Autocomplete(this.grammar).getSuggestions(expression, cursorPosition, context);
	}
	_addGrammarElement(str, obj) {
		this.grammar.elements[str] = obj;
	}
};
const jexl = new Jexl();
async function evaluateListenerCondition(condition, eventData, props) {
	try {
		return !!await jexl.eval(condition, {
			event: eventData,
			props
		});
	} catch (error) {
		console.error(`LiveComponent: could not evaluate LiveListener condition "${condition}".`, error);
		return false;
	}
}
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
		let promises = [];
		for (const newNode of nodesToAppend) {
			let newElt = document.createRange().createContextualFragment(newNode.outerHTML).firstChild;
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
function setDeepData(data, propertyPath, value) {
	const { currentLevelData, finalData, finalKey, parts } = parseDeepData(data, propertyPath);
	if (typeof currentLevelData !== "object") {
		const lastPart = parts.pop();
		if (typeof currentLevelData === "undefined") throw new Error(`Cannot set data-model="${propertyPath}". The parent "${parts.join(".")}" data does not exist. Did you forget to expose "${parts[0]}" as a LiveProp?`);
		throw new Error(`Cannot set data-model="${propertyPath}". The parent "${parts.join(".")}" data does not appear to be an object (it's "${currentLevelData}"). Did you forget to add exposed={"${lastPart}"} to its LiveProp?`);
	}
	if (currentLevelData[finalKey] === void 0) {
		const lastPart = parts.pop();
		if (parts.length > 0) throw new Error(`The model name ${propertyPath} was never initialized. Did you forget to add exposed={"${lastPart}"} to its LiveProp?`);
		throw new Error(`The model name "${propertyPath}" was never initialized. Did you forget to expose "${lastPart}" as a LiveProp? Available models values are: ${Object.keys(data).length > 0 ? Object.keys(data).join(", ") : "(none)"}`);
	}
	currentLevelData[finalKey] = value;
	return finalData;
}
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
	getCurrentProps() {
		let props = this.getOriginalProps();
		[this.pendingProps, this.dirtyProps].forEach((changedProps) => {
			Object.keys(changedProps).forEach((name) => {
				props = setDeepData(props, name, changedProps[name]);
			});
		});
		return props;
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
		this.isRemoved = false;
		this.requestDebounceTimeout = null;
		this.element = element;
		this.name = name;
		this.backend = backend;
		this.elementDriver = elementDriver;
		this.id = id;
		this.listeners = /* @__PURE__ */ new Map();
		listeners.forEach((listener) => {
			if (!this.listeners.has(listener.event)) this.listeners.set(listener.event, []);
			this.listeners.get(listener.event)?.push({
				action: listener.action,
				condition: listener.condition || null
			});
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
		(this.listeners.get(name) || []).forEach(({ action, condition }) => {
			if (!condition) {
				this.action(action, data, 1);
				return;
			}
			evaluateListenerCondition(condition, data, this.valueStore.getCurrentProps()).then((matches) => {
				if (matches) this.action(action, data, 1);
			});
		});
	}
	isTurboEnabled() {
		return typeof Turbo !== "undefined" && !this.element.closest("[data-turbo=\"false\"]");
	}
	removeFromPage() {
		this.isRemoved = true;
		this.disconnect();
		const element = this.element;
		for (const name of element.getAttributeNames()) if (name.startsWith("data-live-") && name.endsWith("-value")) element.removeAttribute(name);
		element.setAttribute("data-live-removing", "");
		requestAnimationFrame(() => {
			const animations = (element.getAnimations?.({ subtree: true }) ?? []).filter((animation) => animation.effect?.getComputedTiming().endTime !== Number.POSITIVE_INFINITY);
			Promise.allSettled(animations.map((animation) => animation.finished)).then(() => {
				element.remove();
			});
		});
	}
	tryStartingRequest() {
		if (this.isRemoved) return;
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
		const actionsToSend = this.pendingActions.slice(0, 50);
		const remainingActions = this.pendingActions.slice(50);
		const requestConfig = {
			props: this.valueStore.getOriginalProps(),
			actions: actionsToSend,
			updated: this.valueStore.getDirtyProps(),
			children: {},
			updatedPropsFromParent: this.valueStore.getUpdatedPropsFromParent(),
			files: filesToSend
		};
		this.hooks.triggerHook("request:started", requestConfig);
		this.backendRequest = this.backend.makeRequest(requestConfig.props, requestConfig.actions, requestConfig.updated, requestConfig.children, requestConfig.updatedPropsFromParent, requestConfig.files);
		this.hooks.triggerHook("loading.state:started", this.element, this.backendRequest);
		this.pendingActions = remainingActions;
		this.valueStore.flushDirtyPropsToPending();
		this.isRequestPending = remainingActions.length > 0;
		this.backendRequest.promise.then(async (response) => {
			const backendResponse = new BackendResponse_default(response);
			const headers = backendResponse.response.headers;
			for (const input of Object.values(this.pendingFiles)) input.value = "";
			const html = await backendResponse.getBody();
			if (!headers.get("Content-Type")?.includes("application/vnd.live-component+html") && !headers.get("X-Live-Redirect") && !headers.has("X-Live-Remove")) {
				const controls = { displayError: true };
				this.valueStore.pushPendingPropsBackToDirty();
				this.hooks.triggerHook("response:error", backendResponse, controls);
				if (controls.displayError) this.renderError(html);
				this.backendRequest = null;
				thisPromiseResolve(backendResponse);
				return response;
			}
			if (backendResponse.isRemoved()) {
				this.isRemoved = true;
				this.processRerender(html, backendResponse);
				this.backendRequest = null;
				thisPromiseResolve(backendResponse);
				this.removeFromPage();
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
		try {
			const downloadUrl = backendResponse.getDownloadUrl();
			const download = backendResponse.getDownload();
			if (downloadUrl) triggerDownload({ url: downloadUrl });
			else if (download) triggerDownload(download);
		} catch (error) {
			console.error("Could not start the download:", error);
		}
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
			if ("toJSON" === prop || "then" === prop) return;
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
function triggerDownload(download) {
	const fromUrl = "url" in download;
	const href = fromUrl ? download.url : URL.createObjectURL(download.blob);
	const link = Object.assign(document.createElement("a"), {
		href,
		download: fromUrl ? "" : download.filename,
		style: "display: none"
	});
	document.body.appendChild(link);
	link.click();
	setTimeout(() => {
		document.body.removeChild(link);
		if (!fromUrl) URL.revokeObjectURL(href);
	}, 75);
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
		const modelDirectives = getAllModelDirectiveFromElements(this.component.element);
		this.parentModelBindings = modelDirectives.map(get_model_binding_default);
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
		if (isLoading) targetElement.setAttribute("aria-busy", "true");
		else targetElement.removeAttribute("aria-busy");
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
		matchingElements = matchingElements.filter((elt) => !elt.closest("[data-live-ignore]"));
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
