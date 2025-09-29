type DomainType = string;
type LocaleType = string;
type TranslationsType = Record<DomainType, {
    parameters: ParametersType;
}>;
type NoParametersType = Record<string, never>;
type ParametersType = Record<string, string | number | Date> | NoParametersType;
type RemoveIntlIcuSuffix<T> = T extends `${infer U}+intl-icu` ? U : T;
type DomainsOf<M> = M extends Message<infer Translations, LocaleType> ? keyof Translations : never;
type LocaleOf<M> = M extends Message<TranslationsType, infer Locale> ? Locale : never;
type ParametersOf<M, D extends DomainType> = M extends Message<infer Translations, LocaleType> ? Translations[D] extends {
    parameters: infer Parameters;
} ? Parameters : never : never;
interface Message<Translations extends TranslationsType, Locale extends LocaleType> {
    id: string;
    translations: {
        [domain in DomainType]: {
            [locale in Locale]: string;
        };
    };
}
declare function setLocale(locale: LocaleType | null): void;
declare function getLocale(): LocaleType;
declare function throwWhenNotFound(enabled: boolean): void;
declare function setLocaleFallbacks(localeFallbacks: Record<LocaleType, LocaleType>): void;
declare function getLocaleFallbacks(): Record<LocaleType, LocaleType>;
declare function trans<M extends Message<TranslationsType, LocaleType>, D extends DomainsOf<M>, P extends ParametersOf<M, D>>(...args: P extends NoParametersType ? [message: M, parameters?: P, domain?: RemoveIntlIcuSuffix<D>, locale?: LocaleOf<M>] : [message: M, parameters: P, domain?: RemoveIntlIcuSuffix<D>, locale?: LocaleOf<M>]): string;

export { type DomainType, type DomainsOf, type LocaleOf, type LocaleType, type Message, type NoParametersType, type ParametersOf, type ParametersType, type RemoveIntlIcuSuffix, type TranslationsType, getLocale, getLocaleFallbacks, setLocale, setLocaleFallbacks, throwWhenNotFound, trans };
