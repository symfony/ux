import { InvalidParameterError, MissingMandatoryParametersError, RouteNotFoundError } from './errors';
import {
    decodeQueryOrFragment,
    encodePath,
    httpBuildQuery,
    isEmptyPhpArray,
    isPhpArray,
    isPhpTruthy,
    looseEquals,
    rawUrlEncode,
    toPhpString,
} from './php';
import { getBaseUrl, getHost, getHttpPort, getHttpsPort, getParameters, getPathInfo, getScheme } from './context';
import type { RequestContext, RouteDefinition, Routes, Token } from './types';

export const ABSOLUTE_URL = 0;
export const ABSOLUTE_PATH = 1;
export const RELATIVE_PATH = 2;
export const NETWORK_PATH = 3;

type ReferenceType = typeof ABSOLUTE_URL | typeof ABSOLUTE_PATH | typeof RELATIVE_PATH | typeof NETWORK_PATH;

type CompiledToken =
    | { variable: false; text: string }
    | { variable: true; prefix: string; name: string; source: string | null; regex: RegExp | null; important: boolean };

interface CompiledRoute {
    tokens: CompiledToken[];
    hostTokens: CompiledToken[];
    variables: string[];
    variableSet: Set<string>;
}

const UNRESERVED_CHARS_AND_SLASH = /^[A-Za-z0-9\-._~/]*$/;
const compiledRoutes = new WeakMap<RouteDefinition, CompiledRoute>();

export function generate(
    routes: Routes,
    name: string,
    parameters: Record<string, unknown>,
    referenceType: ReferenceType,
    context: Partial<RequestContext>
): string {
    const contextParameters = getParameters(context);
    let route: RouteDefinition | undefined;
    const requestedLocale = parameters._locale ?? contextParameters._locale;
    let locale: string | false = isPhpTruthy(requestedLocale) ? toPhpString(requestedLocale) : false;

    // Same loop as UrlGenerator::generate(): the last looked-up variant is kept even when its canonical name differs
    while (locale !== false) {
        route = getOwnRoute(routes, `${name}.${locale}`);
        if (route !== undefined && route.defaults._canonical_route === name) {
            break;
        }
        const separator = locale.indexOf('_');
        locale = separator === -1 ? false : locale.slice(0, separator);
    }

    route ??= getOwnRoute(routes, name);
    if (route === undefined) {
        throw new RouteNotFoundError(
            `Unable to generate a URL for the named route "${name}" as such route does not exist.`
        );
    }

    const compiled = compile(route);
    const defaults = route.defaults;

    if (defaults._canonical_route != null && defaults._locale != null) {
        if (!compiled.variableSet.has('_locale')) {
            parameters = without(parameters, '_locale');
        } else if (parameters._locale == null) {
            parameters = { ...parameters, _locale: defaults._locale };
        }
    }

    return doGenerate(compiled, defaults, route.schemes, parameters, name, referenceType, context, contextParameters);
}

function doGenerate(
    compiled: CompiledRoute,
    defaults: Record<string, unknown>,
    schemes: string[],
    parameters: Record<string, unknown>,
    name: string,
    referenceType: ReferenceType,
    context: Partial<RequestContext>,
    contextParameters: Record<string, unknown>
): string {
    const defaultQuery = defaults._query ?? [];
    if (!isPhpArray(defaultQuery)) {
        throw new InvalidParameterError(`Default "_query" must be an array of query parameters for route "${name}".`);
    }

    let queryParameters: Record<string, unknown> | unknown[] = [];
    if (parameters._query != null) {
        if (!isPhpArray(parameters._query)) {
            throw new InvalidParameterError('Parameter "_query" must be an array of query parameters.');
        }
        queryParameters = parameters._query;
        parameters = without(parameters, '_query');
    }

    const mergedParameters: Record<string, unknown> = { ...defaults, ...contextParameters, ...parameters };

    const missing = compiled.variables.filter((variable) => !Object.hasOwn(mergedParameters, variable));
    if (missing.length > 0) {
        throw new MissingMandatoryParametersError(
            `Some mandatory parameters are missing ("${missing.join('", "')}") to generate a URL for route "${name}".`
        );
    }

    let url = '';
    let optional = true;
    for (const token of compiled.tokens) {
        if (!token.variable) {
            url = token.text + url;
            optional = false;
            continue;
        }

        const value = mergedParameters[token.name];
        if (
            !optional ||
            token.important ||
            !Object.hasOwn(defaults, token.name) ||
            (value != null && toPhpString(value) !== toPhpString(defaults[token.name]))
        ) {
            checkRequirement(token, value, name);
            url = token.prefix + toPhpString(value) + url;
            optional = false;
        }
    }

    if (url === '') {
        url = '/';
    }

    if (!UNRESERVED_CHARS_AND_SLASH.test(url)) {
        url = encodePath(url);
    }

    if (url.includes('/.')) {
        url = url
            .split('/')
            .map((segment) => (segment === '.' ? '%2E' : segment === '..' ? '%2E%2E' : segment))
            .join('/');
    }

    let schemeAuthority = '';
    let host: string | undefined;
    let scheme: string | undefined;

    if (schemes.length > 0) {
        scheme = getScheme(context);
        if (!schemes.includes(scheme)) {
            referenceType = ABSOLUTE_URL;
            scheme = schemes[0];
        }
    }

    if (compiled.hostTokens.length > 0) {
        let routeHost = '';
        for (const token of compiled.hostTokens) {
            if (token.variable) {
                const value = mergedParameters[token.name];
                checkRequirement(token, value, name);
                routeHost = token.prefix + toPhpString(value) + routeHost;
            } else {
                routeHost = token.text + routeHost;
            }
        }

        host = getHost(context);
        if (routeHost !== host) {
            host = routeHost;
            if (referenceType !== ABSOLUTE_URL) {
                referenceType = NETWORK_PATH;
            }
        }
    }

    if (referenceType === ABSOLUTE_URL || referenceType === NETWORK_PATH) {
        host ??= getHost(context);
        scheme ??= getScheme(context);

        if (host !== '' || (scheme !== '' && scheme !== 'http' && scheme !== 'https')) {
            let port = '';
            if (scheme === 'http') {
                const httpPort = getHttpPort(context);
                port = httpPort !== 80 ? `:${httpPort}` : '';
            } else if (scheme === 'https') {
                const httpsPort = getHttpsPort(context);
                port = httpsPort !== 443 ? `:${httpsPort}` : '';
            }

            schemeAuthority = referenceType === NETWORK_PATH || scheme === '' ? '//' : `${scheme}://`;
            schemeAuthority += host + port;
        }
    }

    url =
        referenceType === RELATIVE_PATH
            ? getRelativePath(getPathInfo(context), url)
            : schemeAuthority + getBaseUrl(context) + url;

    let extra: Record<string, unknown> = {};
    for (const key of Object.keys(parameters)) {
        if (compiled.variableSet.has(key)) {
            continue;
        }
        if (Object.hasOwn(defaults, key) && looseEquals(parameters[key], defaults[key])) {
            continue;
        }
        extra[key] = parameters[key];
    }

    if (!isEmptyPhpArray(defaultQuery) || !isEmptyPhpArray(queryParameters)) {
        extra = { ...defaultQuery, ...extra, ...queryParameters };
    }

    let fragment: unknown = defaults._fragment ?? '';
    if (extra._fragment != null) {
        fragment = extra._fragment;
        extra = without(extra, '_fragment');
    }

    if (!isEmptyPhpArray(extra)) {
        const query = httpBuildQuery(extra);
        if (query !== '') {
            url += `?${decodeQueryOrFragment(query)}`;
        }
    }

    const fragmentString = toPhpString(fragment);
    if (fragmentString !== '') {
        url += `#${decodeQueryOrFragment(rawUrlEncode(fragmentString))}`;
    }

    return url;
}

export function getRelativePath(basePath: string, targetPath: string): string {
    if (basePath === targetPath) {
        return '';
    }

    const sourceDirs = (basePath.startsWith('/') ? basePath.slice(1) : basePath).split('/');
    const targetDirs = (targetPath.startsWith('/') ? targetPath.slice(1) : targetPath).split('/');
    sourceDirs.pop();
    const targetFile = targetDirs.pop() as string;

    let common = 0;
    while (common < sourceDirs.length && common < targetDirs.length && sourceDirs[common] === targetDirs[common]) {
        common++;
    }

    const path = '../'.repeat(sourceDirs.length - common) + [...targetDirs.slice(common), targetFile].join('/');
    const colonPosition = path.indexOf(':');
    const slashPosition = path.indexOf('/');

    return path === '' ||
        path[0] === '/' ||
        (colonPosition !== -1 && (colonPosition < slashPosition || slashPosition === -1))
        ? `./${path}`
        : path;
}

function checkRequirement(token: Extract<CompiledToken, { variable: true }>, value: unknown, name: string): void {
    if (token.regex !== null && !token.regex.test(toPhpString(value))) {
        throw new InvalidParameterError(
            `Parameter "${token.name}" for route "${name}" must match "${token.source}" ("${toPhpString(value)}" given) to generate a corresponding URL.`
        );
    }
}

function getOwnRoute(routes: Routes, name: string): RouteDefinition | undefined {
    return Object.hasOwn(routes, name) ? routes[name] : undefined;
}

function without(parameters: Record<string, unknown>, key: string): Record<string, unknown> {
    const copy: Record<string, unknown> = {};
    for (const name of Object.keys(parameters)) {
        if (name !== key) {
            copy[name] = parameters[name];
        }
    }

    return copy;
}

function compile(route: RouteDefinition): CompiledRoute {
    let compiled = compiledRoutes.get(route);
    if (compiled !== undefined) {
        return compiled;
    }

    const tokens = route.tokens.map(compileToken);
    const hostTokens = route.hostTokens.map(compileToken);
    const variables: string[] = [];
    for (const token of [...hostTokens].reverse().concat([...tokens].reverse())) {
        if (token.variable && !variables.includes(token.name)) {
            variables.push(token.name);
        }
    }

    compiled = { tokens, hostTokens, variables, variableSet: new Set(variables) };
    compiledRoutes.set(route, compiled);

    return compiled;
}

function compileToken(token: Token): CompiledToken {
    if (token[0] === 'text') {
        return { variable: false, text: token[1] };
    }

    return {
        variable: true,
        prefix: token[1],
        name: token[3],
        source: token[2],
        regex: createRequirementRegex(token[2], token[4] === true),
        important: token[5] === true,
    };
}

function createRequirementRegex(source: string | null, utf8: boolean): RegExp | null {
    if (source === null) {
        return null;
    }

    try {
        return new RegExp(`^(?:${source})$`, utf8 ? 'iu' : 'i');
    } catch {
        return null;
    }
}
