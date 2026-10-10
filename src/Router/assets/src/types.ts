declare const parametersType: unique symbol;
declare const canonicalNameType: unique symbol;

export type TextToken = ['text', string];
export type VariableToken = ['variable', string, string | null, string, boolean?, boolean?];
export type Token = TextToken | VariableToken;

export interface RouteDefinition {
    tokens: Token[];
    defaults: Record<string, unknown>;
    hostTokens: Token[];
    schemes: string[];
}

export interface Route<
    TParameters extends object = Record<never, never>,
    TCanonicalName extends string = never,
> extends RouteDefinition {
    readonly [parametersType]?: TParameters;
    readonly [canonicalNameType]?: TCanonicalName;
}

export type Routes = Record<string, RouteDefinition>;

export type QueryValue =
    | string
    | number
    | boolean
    | null
    | undefined
    | readonly QueryValue[]
    | { readonly [key: string]: QueryValue };

export interface SpecialParameters {
    _query?: Record<string, QueryValue>;
    _fragment?: string;
    _locale?: string;
}

export interface RequestContext {
    baseUrl: string;
    pathInfo: string;
    host: string;
    scheme: string;
    httpPort: number;
    httpsPort: number;
    parameters: Record<string, unknown>;
}

type CanonicalNameOf<TRoute> = TRoute extends { readonly [canonicalNameType]?: infer TName }
    ? TName extends string
        ? TName
        : never
    : never;

type OwnParametersOf<TRoute> = TRoute extends { readonly [parametersType]?: infer TParameters }
    ? unknown extends TParameters
        ? Record<string, unknown>
        : TParameters
    : Record<string, unknown>;

export type RouteName<TRoutes extends Routes> = {
    [TKey in keyof TRoutes & string]: [CanonicalNameOf<TRoutes[TKey]>] extends [never]
        ? TKey
        : CanonicalNameOf<TRoutes[TKey]>;
}[keyof TRoutes & string];

export type ParametersOf<TRoutes extends Routes, TName extends string> = {
    [TKey in keyof TRoutes & string]: [CanonicalNameOf<TRoutes[TKey]>] extends [never]
        ? TName extends TKey
            ? OwnParametersOf<TRoutes[TKey]>
            : never
        : CanonicalNameOf<TRoutes[TKey]> extends TName
          ? OwnParametersOf<TRoutes[TKey]>
          : never;
}[keyof TRoutes & string] &
    SpecialParameters;

type RequiresParameters<TParameters> = Record<never, never> extends TParameters ? false : true;

export type PathArguments<TParameters> =
    RequiresParameters<TParameters> extends true
        ? [parameters: TParameters, relative?: boolean]
        : [parameters?: TParameters, relative?: boolean];

export type UrlArguments<TParameters> =
    RequiresParameters<TParameters> extends true
        ? [parameters: TParameters, schemeRelative?: boolean]
        : [parameters?: TParameters, schemeRelative?: boolean];

export interface Router<TRoutes extends Routes> {
    path<TName extends RouteName<TRoutes>>(name: TName, ...args: PathArguments<ParametersOf<TRoutes, TName>>): string;
    url<TName extends RouteName<TRoutes>>(name: TName, ...args: UrlArguments<ParametersOf<TRoutes, TName>>): string;
}
