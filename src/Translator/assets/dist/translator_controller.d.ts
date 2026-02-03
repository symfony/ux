type MessageId = string;
type DomainType = string;
type LocaleType = string;

type TranslationsType = Record<DomainType, { parameters: ParametersType }>;
type NoParametersType = Record<string, never>;
type ParametersType = Record<string, string | number | Date> | NoParametersType;

type RemoveIntlIcuSuffix<T> = T extends `${infer U}+intl-icu` ? U : T;
type DomainsOf<M> = M extends Message<infer Translations, LocaleType> ? keyof Translations : never;
type LocaleOf<M> = M extends Message<TranslationsType, infer Locale> ? Locale : never;
type ParametersOf<M, D extends DomainType> =
    M extends Message<infer Translations, LocaleType>
        ? Translations[D] extends { parameters: infer Parameters }
            ? Parameters
            : never
        : never;

interface Message<Translations extends TranslationsType, Locale extends LocaleType> {
    translations: {
        [domain in DomainType]: {
            [locale in Locale]: string;
        };
    };
}

type Messages = Record<MessageId, Message<TranslationsType, LocaleType>>;

declare function getDefaultLocale(): LocaleType;
declare function createTranslator<TMessages extends Messages>({ messages, locale, localeFallbacks, throwWhenNotFound, }: {
    messages: TMessages;
    locale?: LocaleType;
    localeFallbacks?: Record<LocaleType, LocaleType>;
    throwWhenNotFound?: boolean;
}): {
    setLocale(locale: LocaleType): void;
    getLocale(): LocaleType;
    setThrowWhenNotFound(throwWhenNotFound: boolean): void;
    trans<TMessageId extends keyof TMessages & MessageId, TMessage extends TMessages[TMessageId], TDomain extends DomainsOf<TMessage>, TParameters extends ParametersOf<TMessage, TDomain>>(id: TMessageId, parameters?: TParameters, domain?: RemoveIntlIcuSuffix<TDomain> | undefined, locale?: LocaleOf<TMessage>): string;
};

export { type DomainType, type DomainsOf, type LocaleOf, type LocaleType, type Message, type MessageId, type Messages, type NoParametersType, type ParametersOf, type ParametersType, type RemoveIntlIcuSuffix, type TranslationsType, createTranslator, getDefaultLocale };
