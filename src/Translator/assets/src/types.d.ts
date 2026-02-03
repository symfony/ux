export type MessageId = string;
export type DomainType = string;
export type LocaleType = string;

export type TranslationsType = Record<DomainType, { parameters: ParametersType }>;
export type NoParametersType = Record<string, never>;
export type ParametersType = Record<string, string | number | Date> | NoParametersType;

export type RemoveIntlIcuSuffix<T> = T extends `${infer U}+intl-icu` ? U : T;
export type DomainsOf<M> = M extends Message<infer Translations, LocaleType> ? keyof Translations : never;
export type LocaleOf<M> = M extends Message<TranslationsType, infer Locale> ? Locale : never;
export type ParametersOf<M, D extends DomainType> =
    M extends Message<infer Translations, LocaleType>
        ? Translations[D] extends { parameters: infer Parameters }
            ? Parameters
            : never
        : never;

export interface Message<Translations extends TranslationsType, Locale extends LocaleType> {
    translations: {
        [domain in DomainType]: {
            [locale in Locale]: string;
        };
    };
}

export type Messages = Record<MessageId, Message<TranslationsType, LocaleType>>;
