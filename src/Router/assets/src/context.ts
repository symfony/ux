import type { RequestContext } from './types';

// Each value missing from the given context is read from the page only when the generator needs it
export function getDefaultLocale(): string {
    const lang = typeof document !== 'undefined' ? document.documentElement.lang : '';

    return lang ? lang.replaceAll('-', '_') : 'en';
}

export function getParameters(context: Partial<RequestContext>): Record<string, unknown> {
    return context.parameters !== undefined && '_locale' in context.parameters
        ? context.parameters
        : { _locale: getDefaultLocale(), ...context.parameters };
}

export function getBaseUrl(context: Partial<RequestContext>): string {
    return context.baseUrl ?? '';
}

export function getPathInfo(context: Partial<RequestContext>): string {
    if (context.pathInfo !== undefined) {
        return context.pathInfo;
    }

    const baseUrl = getBaseUrl(context);
    const pathname = getLocation()?.pathname ?? '/';

    return baseUrl !== '' && pathname.startsWith(baseUrl) ? pathname.slice(baseUrl.length) || '/' : pathname;
}

export function getHost(context: Partial<RequestContext>): string {
    return (context.host ?? getLocation()?.hostname ?? '').toLowerCase();
}

export function getScheme(context: Partial<RequestContext>): string {
    return (context.scheme ?? getLocationScheme()).toLowerCase();
}

export function getHttpPort(context: Partial<RequestContext>): number {
    return context.httpPort ?? getLocationPort('http', 80);
}

export function getHttpsPort(context: Partial<RequestContext>): number {
    return context.httpsPort ?? getLocationPort('https', 443);
}

function getLocation(): Location | null {
    return typeof window !== 'undefined' ? window.location : null;
}

function getLocationScheme(): string {
    return getLocation()?.protocol.slice(0, -1) ?? 'http';
}

function getLocationPort(scheme: string, defaultPort: number): number {
    const port = getLocation()?.port;

    return port && getLocationScheme() === scheme ? Number(port) : defaultPort;
}
