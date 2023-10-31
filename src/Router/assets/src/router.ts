/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

export type LocaleType = string;
export type ContextType = {
    base_url: string;
    host: string;
    scheme: 'http' | 'https';
    http_port: number;
    https_port: number;
    locale: LocaleType;
}

export type ConfigurationType = {
    context: ContextType,
};

let _configuration: ConfigurationType | null = null;
const _defaultConfiguration: ConfigurationType = {
    context: {
        base_url: '',
        host: window.location.host,
        scheme: window.location.protocol.slice(0, -1) as 'http' | 'https',
        http_port: 80,
        https_port: 443,
        locale: document.documentElement.lang // <html lang="en">
            || 'en'
    },
};

export function setConfiguration(configuration: ConfigurationType | null) {
    _configuration = configuration;
}

export function getConfiguration(): ConfigurationType {
    return (
        _configuration ||
        {
            ..._defaultConfiguration,
            ...(JSON.parse(document.documentElement.getAttribute('data-symfony-ux-router-configuration') || '{}') as ConfigurationType),
        }
    );
}

export type RouteDefinition = {
    path: string,
    variables: Record<string, unknown>
    tokens: Array<
        { 0: 'variable', 1: string, 2: string, 3: string, 4: boolean }
        | { 0: 'text', 1: string }
    >
};
type ParametersOf<R> = {
    _locale?: string;
    _fragment?: string;
};

export function path<RD extends RouteDefinition>(
    routeDefinition: RD,
    parameters: ParametersOf<RD> = {},
    relative = false,
): string
{
    return generate(routeDefinition, parameters, relative ? ReferenceType.RELATIVE_PATH : ReferenceType.ABSOLUTE_PATH)
}

export function url<RD extends RouteDefinition>(
    routeDefinition: RD,
    parameters: ParametersOf<RD> = {},
    schemaRelative = false,
): string
{
    return generate(routeDefinition, parameters, schemaRelative ? ReferenceType.NETWORK_PATH : ReferenceType.ABSOLUTE_URL)
}

export const enum ReferenceType {
    RELATIVE_PATH,
    ABSOLUTE_PATH,
    NETWORK_PATH,
    ABSOLUTE_URL,
}

export function generate<RD extends RouteDefinition>(
    routeDefinition: RD,
    parameters: ParametersOf<RD> = {},
    referenceType: ReferenceType = ReferenceType.ABSOLUTE_PATH,
): string {
    const locale: LocaleType = parameters['_locale'] || getConfiguration().context.locale;
    console.log({
        routeDefinition,
        parameters,
        referenceType,
        locale,
    })
}