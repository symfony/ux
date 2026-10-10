var RouteNotFoundError = class extends Error {
	constructor(message) {
		super(message);
		this.name = "RouteNotFoundError";
	}
};
var MissingMandatoryParametersError = class extends Error {
	constructor(message) {
		super(message);
		this.name = "MissingMandatoryParametersError";
	}
};
var InvalidParameterError = class extends Error {
	constructor(message) {
		super(message);
		this.name = "InvalidParameterError";
	}
};
const PATH_DECODED = {
	"%2F": "/",
	"%252F": "%2F",
	"%40": "@",
	"%3A": ":",
	"%3B": ";",
	"%2C": ",",
	"%3D": "=",
	"%2B": "+",
	"%21": "!",
	"%2A": "*",
	"%7C": "|"
};
const PATH_DECODED_PATTERN = new RegExp(Object.keys(PATH_DECODED).join("|"), "g");
const QUERY_FRAGMENT_DECODED = {
	"%2F": "/",
	"%252F": "%2F",
	"%3F": "?",
	"%40": "@",
	"%3A": ":",
	"%21": "!",
	"%3B": ";",
	"%2C": ",",
	"%2A": "*"
};
const QUERY_FRAGMENT_DECODED_PATTERN = new RegExp(Object.keys(QUERY_FRAGMENT_DECODED).join("|"), "g");
const LEFT_UNENCODED_BY_ENCODE_URI_COMPONENT = /[!'()*]/g;
const UNRESERVED_CHARS = /^[A-Za-z0-9\-._~]*$/;
const NUMERIC = /^\s*[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?\s*$/;
function toPhpString(value) {
	if (value === null || value === void 0 || value === false) return "";
	return value === true ? "1" : String(value);
}
function isPhpTruthy(value) {
	return value !== null && value !== void 0 && value !== false && value !== "" && value !== "0" && value !== 0;
}
function isPhpArray(value) {
	if (Array.isArray(value)) return true;
	if (typeof value !== "object" || value === null) return false;
	const prototype = Object.getPrototypeOf(value);
	return prototype === Object.prototype || prototype === null;
}
function isEmptyPhpArray(value) {
	return Object.keys(value).length === 0;
}
function looseEquals(a, b) {
	if (typeof a === "boolean" || typeof b === "boolean") return isPhpTruthy(a) === isPhpTruthy(b);
	if (a === null || a === void 0 || b === null || b === void 0) {
		const other = a ?? b;
		if (other === null || other === void 0) return true;
		return typeof other === "string" ? other === "" : !isPhpTruthy(other);
	}
	const left = toPhpString(a);
	const right = toPhpString(b);
	if (NUMERIC.test(left) && NUMERIC.test(right)) return Number(left) === Number(right);
	return left === right;
}
function rawUrlEncode(value) {
	if (UNRESERVED_CHARS.test(value)) return value;
	return encodeURIComponent(value).replace(LEFT_UNENCODED_BY_ENCODE_URI_COMPONENT, (char) => `%${char.charCodeAt(0).toString(16).toUpperCase()}`);
}
function encodePath(path) {
	return rawUrlEncode(path).replace(PATH_DECODED_PATTERN, (match) => PATH_DECODED[match]);
}
function decodeQueryOrFragment(encoded) {
	return encoded.replace(QUERY_FRAGMENT_DECODED_PATTERN, (match) => QUERY_FRAGMENT_DECODED[match]);
}
function httpBuildQuery(data) {
	const pairs = [];
	appendQueryPairs(pairs, data, null);
	return pairs.join("&");
}
function appendQueryPairs(pairs, data, prefix) {
	for (const [key, value] of Object.entries(data)) {
		if (value === null || value === void 0) continue;
		const encodedKey = prefix === null ? rawUrlEncode(key) : `${prefix}%5B${rawUrlEncode(key)}%5D`;
		if (isPhpArray(value)) {
			appendQueryPairs(pairs, value, encodedKey);
			continue;
		}
		pairs.push(`${encodedKey}=${rawUrlEncode(typeof value === "boolean" ? value ? "1" : "0" : String(value))}`);
	}
}
function getDefaultLocale() {
	const lang = typeof document !== "undefined" ? document.documentElement.lang : "";
	return lang ? lang.replaceAll("-", "_") : "en";
}
function getParameters(context) {
	return context.parameters !== void 0 && "_locale" in context.parameters ? context.parameters : {
		_locale: getDefaultLocale(),
		...context.parameters
	};
}
function getBaseUrl(context) {
	return context.baseUrl ?? "";
}
function getPathInfo(context) {
	if (context.pathInfo !== void 0) return context.pathInfo;
	const baseUrl = getBaseUrl(context);
	const pathname = getLocation()?.pathname ?? "/";
	return baseUrl !== "" && pathname.startsWith(baseUrl) ? pathname.slice(baseUrl.length) || "/" : pathname;
}
function getHost(context) {
	return (context.host ?? getLocation()?.hostname ?? "").toLowerCase();
}
function getScheme(context) {
	return (context.scheme ?? getLocationScheme()).toLowerCase();
}
function getHttpPort(context) {
	return context.httpPort ?? getLocationPort("http", 80);
}
function getHttpsPort(context) {
	return context.httpsPort ?? getLocationPort("https", 443);
}
function getLocation() {
	return typeof window !== "undefined" ? window.location : null;
}
function getLocationScheme() {
	return getLocation()?.protocol.slice(0, -1) ?? "http";
}
function getLocationPort(scheme, defaultPort) {
	const port = getLocation()?.port;
	return port && getLocationScheme() === scheme ? Number(port) : defaultPort;
}
const UNRESERVED_CHARS_AND_SLASH = /^[A-Za-z0-9\-._~/]*$/;
const compiledRoutes = /* @__PURE__ */ new WeakMap();
function generate(routes, name, parameters, referenceType, context) {
	const contextParameters = getParameters(context);
	let route;
	const requestedLocale = parameters._locale ?? contextParameters._locale;
	let locale = isPhpTruthy(requestedLocale) ? toPhpString(requestedLocale) : false;
	while (locale !== false) {
		route = getOwnRoute(routes, `${name}.${locale}`);
		if (route !== void 0 && route.defaults._canonical_route === name) break;
		const separator = locale.indexOf("_");
		locale = separator === -1 ? false : locale.slice(0, separator);
	}
	route ??= getOwnRoute(routes, name);
	if (route === void 0) throw new RouteNotFoundError(`Unable to generate a URL for the named route "${name}" as such route does not exist.`);
	const compiled = compile(route);
	const defaults = route.defaults;
	if (defaults._canonical_route != null && defaults._locale != null) {
		if (!compiled.variableSet.has("_locale")) parameters = without(parameters, "_locale");
		else if (parameters._locale == null) parameters = {
			...parameters,
			_locale: defaults._locale
		};
	}
	return doGenerate(compiled, defaults, route.schemes, parameters, name, referenceType, context, contextParameters);
}
function doGenerate(compiled, defaults, schemes, parameters, name, referenceType, context, contextParameters) {
	const defaultQuery = defaults._query ?? [];
	if (!isPhpArray(defaultQuery)) throw new InvalidParameterError(`Default "_query" must be an array of query parameters for route "${name}".`);
	let queryParameters = [];
	if (parameters._query != null) {
		if (!isPhpArray(parameters._query)) throw new InvalidParameterError("Parameter \"_query\" must be an array of query parameters.");
		queryParameters = parameters._query;
		parameters = without(parameters, "_query");
	}
	const mergedParameters = {
		...defaults,
		...contextParameters,
		...parameters
	};
	const missing = compiled.variables.filter((variable) => !Object.hasOwn(mergedParameters, variable));
	if (missing.length > 0) throw new MissingMandatoryParametersError(`Some mandatory parameters are missing ("${missing.join("\", \"")}") to generate a URL for route "${name}".`);
	let url = "";
	let optional = true;
	for (const token of compiled.tokens) {
		if (!token.variable) {
			url = token.text + url;
			optional = false;
			continue;
		}
		const value = mergedParameters[token.name];
		if (!optional || token.important || !Object.hasOwn(defaults, token.name) || value != null && toPhpString(value) !== toPhpString(defaults[token.name])) {
			checkRequirement(token, value, name);
			url = token.prefix + toPhpString(value) + url;
			optional = false;
		}
	}
	if (url === "") url = "/";
	if (!UNRESERVED_CHARS_AND_SLASH.test(url)) url = encodePath(url);
	if (url.includes("/.")) url = url.split("/").map((segment) => segment === "." ? "%2E" : segment === ".." ? "%2E%2E" : segment).join("/");
	let schemeAuthority = "";
	let host;
	let scheme;
	if (schemes.length > 0) {
		scheme = getScheme(context);
		if (!schemes.includes(scheme)) {
			referenceType = 0;
			scheme = schemes[0];
		}
	}
	if (compiled.hostTokens.length > 0) {
		let routeHost = "";
		for (const token of compiled.hostTokens) if (token.variable) {
			const value = mergedParameters[token.name];
			checkRequirement(token, value, name);
			routeHost = token.prefix + toPhpString(value) + routeHost;
		} else routeHost = token.text + routeHost;
		host = getHost(context);
		if (routeHost !== host) {
			host = routeHost;
			if (referenceType !== 0) referenceType = 3;
		}
	}
	if (referenceType === 0 || referenceType === 3) {
		host ??= getHost(context);
		scheme ??= getScheme(context);
		if (host !== "" || scheme !== "" && scheme !== "http" && scheme !== "https") {
			let port = "";
			if (scheme === "http") {
				const httpPort = getHttpPort(context);
				port = httpPort !== 80 ? `:${httpPort}` : "";
			} else if (scheme === "https") {
				const httpsPort = getHttpsPort(context);
				port = httpsPort !== 443 ? `:${httpsPort}` : "";
			}
			schemeAuthority = referenceType === 3 || scheme === "" ? "//" : `${scheme}://`;
			schemeAuthority += host + port;
		}
	}
	url = referenceType === 2 ? getRelativePath(getPathInfo(context), url) : schemeAuthority + getBaseUrl(context) + url;
	let extra = {};
	for (const key of Object.keys(parameters)) {
		if (compiled.variableSet.has(key)) continue;
		if (Object.hasOwn(defaults, key) && looseEquals(parameters[key], defaults[key])) continue;
		extra[key] = parameters[key];
	}
	if (!isEmptyPhpArray(defaultQuery) || !isEmptyPhpArray(queryParameters)) extra = {
		...defaultQuery,
		...extra,
		...queryParameters
	};
	let fragment = defaults._fragment ?? "";
	if (extra._fragment != null) {
		fragment = extra._fragment;
		extra = without(extra, "_fragment");
	}
	if (!isEmptyPhpArray(extra)) {
		const query = httpBuildQuery(extra);
		if (query !== "") url += `?${decodeQueryOrFragment(query)}`;
	}
	const fragmentString = toPhpString(fragment);
	if (fragmentString !== "") url += `#${decodeQueryOrFragment(rawUrlEncode(fragmentString))}`;
	return url;
}
function getRelativePath(basePath, targetPath) {
	if (basePath === targetPath) return "";
	const sourceDirs = (basePath.startsWith("/") ? basePath.slice(1) : basePath).split("/");
	const targetDirs = (targetPath.startsWith("/") ? targetPath.slice(1) : targetPath).split("/");
	sourceDirs.pop();
	const targetFile = targetDirs.pop();
	let common = 0;
	while (common < sourceDirs.length && common < targetDirs.length && sourceDirs[common] === targetDirs[common]) common++;
	const path = "../".repeat(sourceDirs.length - common) + [...targetDirs.slice(common), targetFile].join("/");
	const colonPosition = path.indexOf(":");
	const slashPosition = path.indexOf("/");
	return path === "" || path[0] === "/" || colonPosition !== -1 && (colonPosition < slashPosition || slashPosition === -1) ? `./${path}` : path;
}
function checkRequirement(token, value, name) {
	if (token.regex !== null && !token.regex.test(toPhpString(value))) throw new InvalidParameterError(`Parameter "${token.name}" for route "${name}" must match "${token.source}" ("${toPhpString(value)}" given) to generate a corresponding URL.`);
}
function getOwnRoute(routes, name) {
	return Object.hasOwn(routes, name) ? routes[name] : void 0;
}
function without(parameters, key) {
	const copy = {};
	for (const name of Object.keys(parameters)) if (name !== key) copy[name] = parameters[name];
	return copy;
}
function compile(route) {
	let compiled = compiledRoutes.get(route);
	if (compiled !== void 0) return compiled;
	const tokens = route.tokens.map(compileToken);
	const hostTokens = route.hostTokens.map(compileToken);
	const variables = [];
	for (const token of [...hostTokens].reverse().concat([...tokens].reverse())) if (token.variable && !variables.includes(token.name)) variables.push(token.name);
	compiled = {
		tokens,
		hostTokens,
		variables,
		variableSet: new Set(variables)
	};
	compiledRoutes.set(route, compiled);
	return compiled;
}
function compileToken(token) {
	if (token[0] === "text") return {
		variable: false,
		text: token[1]
	};
	return {
		variable: true,
		prefix: token[1],
		name: token[3],
		source: token[2],
		regex: createRequirementRegex(token[2], token[4] === true),
		important: token[5] === true
	};
}
function createRequirementRegex(source, utf8) {
	if (source === null) return null;
	try {
		return new RegExp(`^(?:${source})$`, utf8 ? "iu" : "i");
	} catch {
		return null;
	}
}
function createRouter({ routes, context = {} }) {
	return {
		path(name, parameters = {}, relative = false) {
			return generate(routes, name, parameters, relative ? 2 : 1, context);
		},
		url(name, parameters = {}, schemeRelative = false) {
			return generate(routes, name, parameters, schemeRelative ? 3 : 0, context);
		}
	};
}
export { InvalidParameterError, MissingMandatoryParametersError, RouteNotFoundError, createRouter };
