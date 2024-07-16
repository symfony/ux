export type DomainType = string;
export type LocaleType = string;
export type TranslationsType = Record<DomainType, {
    parameters: ParametersType;
}>;
export type NoParametersType = Record<string, never>;
export type ParametersType = Record<string, string | number | Date> | NoParametersType;
export type RemoveIntlIcuSuffix<T> = T extends `${infer U}+intl-icu` ? U : T;
export type DomainsOf<M> = M extends Message<infer Translations, LocaleType> ? keyof Translations : never;
export type LocaleOf<M> = M extends Message<TranslationsType, infer Locale> ? Locale : never;
export type ParametersOf<M, D extends DomainType> = M extends Message<infer Translations, LocaleType> ? Translations[D] extends {
    parameters: infer Parameters;
} ? Parameters : never : never;
export type RegisteredTranslationsType = Record<DomainType, Record<LocaleType, Record<string, string>>>;
export interface Message<Translations extends TranslationsType, Locale extends LocaleType> {
    id: string;
    translations: {
        [domain in DomainType]: {
            [locale in Locale]: string;
        };
    };
}
export declare function setLocale(locale: LocaleType | null): void;
export declare function getLocale(): LocaleType;
export declare function setLocaleFallbacks(localeFallbacks: Record<LocaleType, LocaleType>): void;
export declare function getLocaleFallbacks(): Record<LocaleType, LocaleType>;
export declare function trans<M extends Message<TranslationsType, LocaleType>, D extends DomainsOf<M>, P extends ParametersOf<M, D>>(...args: P extends NoParametersType ? [message: M, parameters?: P, domain?: RemoveIntlIcuSuffix<D>, locale?: LocaleOf<M>] : [message: M, parameters: P, domain?: RemoveIntlIcuSuffix<D>, locale?: LocaleOf<M>]): string;
export declare function registerDomain(domainTranslations: RegisteredTranslationsType): void;
