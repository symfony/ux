import {
    getLocale,
    type Message,
    type NoParametersType,
    setLocale,
    setLocaleFallbacks,
    throwWhenNotFound,
    trans,
} from '../src/translator';

describe('Translator', () => {
    beforeEach(() => {
        setLocale(null);
        setLocaleFallbacks({});
        throwWhenNotFound(false);
        document.documentElement.lang = '';
        document.documentElement.removeAttribute('data-symfony-ux-translator-locale');
    });

    describe('getLocale', () => {
        test('default locale', () => {
            // 'en' is the default locale
            expect(getLocale()).toEqual('en');

            // or the locale from <html lang="...">, if exists
            document.documentElement.lang = 'fr';
            expect(getLocale()).toEqual('fr');

            // or the locale from <html data-symfony-ux-translator-locale="...">, if exists
            document.documentElement.setAttribute('data-symfony-ux-translator-locale', 'it');
            expect(getLocale()).toEqual('it');

            setLocale('de');
            expect(getLocale()).toEqual('de');
        });
    });

    describe('setLocale', () => {
        test('custom locale', () => {
            setLocale('fr');

            expect(getLocale()).toEqual('fr');
        });
    });

    describe('trans', () => {
        test('basic message', () => {
            const MESSAGE_BASIC: Message<{ messages: { parameters: NoParametersType } }, 'en'> = {
                id: 'message.basic',
                translations: {
                    messages: {
                        en: 'A basic message',
                    },
                },
            };

            expect(trans(MESSAGE_BASIC)).toEqual('A basic message');
            expect(trans(MESSAGE_BASIC, {})).toEqual('A basic message');
            expect(trans(MESSAGE_BASIC, {}, 'messages')).toEqual('A basic message');
            expect(trans(MESSAGE_BASIC, {}, 'messages', 'en')).toEqual('A basic message');

            // @ts-expect-error "%count%" is not a valid parameter
            expect(trans(MESSAGE_BASIC, { '%count%': 1 })).toEqual('A basic message');

            // @ts-expect-error "foo" is not a valid domain
            expect(trans(MESSAGE_BASIC, {}, 'foo')).toEqual('message.basic');

            // @ts-expect-error "fr" is not a valid locale
            expect(trans(MESSAGE_BASIC, {}, 'messages', 'fr')).toEqual('message.basic');
        });

        test('basic message with parameters', () => {
            const MESSAGE_BASIC_WITH_PARAMETERS: Message<
                { messages: { parameters: { '%parameter1%': string; '%parameter2%': string } } },
                'en'
            > = {
                id: 'message.basic.with.parameters',
                translations: {
                    messages: {
                        en: 'A basic message %parameter1% %parameter2%',
                    },
                },
            };

            expect(
                trans(MESSAGE_BASIC_WITH_PARAMETERS, {
                    '%parameter1%': 'foo',
                    '%parameter2%': 'bar',
                })
            ).toEqual('A basic message foo bar');

            expect(
                trans(
                    MESSAGE_BASIC_WITH_PARAMETERS,
                    {
                        '%parameter1%': 'foo',
                        '%parameter2%': 'bar',
                    },
                    'messages'
                )
            ).toEqual('A basic message foo bar');

            expect(
                trans(
                    MESSAGE_BASIC_WITH_PARAMETERS,
                    {
                        '%parameter1%': 'foo',
                        '%parameter2%': 'bar',
                    },
                    'messages',
                    'en'
                )
            ).toEqual('A basic message foo bar');

            // @ts-expect-error Parameters "%parameter1%" and "%parameter2%" are missing
            expect(trans(MESSAGE_BASIC_WITH_PARAMETERS, {})).toEqual('A basic message %parameter1% %parameter2%');

            // @ts-expect-error Parameter "%parameter2%" is missing
            expect(trans(MESSAGE_BASIC_WITH_PARAMETERS, { '%parameter1%': 'foo' })).toEqual(
                'A basic message foo %parameter2%'
            );

            expect(
                trans(
                    MESSAGE_BASIC_WITH_PARAMETERS,
                    {
                        '%parameter1%': 'foo',
                        '%parameter2%': 'bar',
                        // @ts-expect-error "foobar" is not a valid domain
                    },
                    'foobar'
                )
            ).toEqual('message.basic.with.parameters');

            expect(
                trans(
                    MESSAGE_BASIC_WITH_PARAMETERS,
                    {
                        '%parameter1%': 'foo',
                        '%parameter2%': 'bar',
                        // @ts-expect-error "fr" is not a valid locale
                    },
                    'messages',
                    'fr'
                )
            ).toEqual('message.basic.with.parameters');
        });

        test('intl message', () => {
            const MESSAGE_INTL: Message<{ 'messages+intl-icu': { parameters: NoParametersType } }, 'en'> = {
                id: 'message.intl',
                translations: {
                    'messages+intl-icu': {
                        en: 'An intl message',
                    },
                },
            };

            expect(trans(MESSAGE_INTL)).toEqual('An intl message');
            expect(trans(MESSAGE_INTL, {})).toEqual('An intl message');
            expect(trans(MESSAGE_INTL, {}, 'messages')).toEqual('An intl message');
            expect(trans(MESSAGE_INTL, {}, 'messages', 'en')).toEqual('An intl message');

            // @ts-expect-error "%count%" is not a valid parameter
            expect(trans(MESSAGE_INTL, { '%count%': 1 })).toEqual('An intl message');

            // @ts-expect-error "foo" is not a valid domain
            expect(trans(MESSAGE_INTL, {}, 'foo')).toEqual('message.intl');

            // @ts-expect-error "fr" is not a valid locale
            expect(trans(MESSAGE_INTL, {}, 'messages', 'fr')).toEqual('message.intl');
        });

        test('intl message with parameters', () => {
            const INTL_MESSAGE_WITH_PARAMETERS: Message<
                {
                    'messages+intl-icu': {
                        parameters: {
                            gender_of_host: 'male' | 'female' | string;
                            num_guests: number;
                            host: string;
                            guest: string;
                        };
                    };
                },
                'en'
            > = {
                id: 'message.intl.with.parameters',
                translations: {
                    'messages+intl-icu': {
                        en: `
{gender_of_host, select,
    female {{num_guests, plural, offset:1
        =0 {{host} does not give a party.}
        =1 {{host} invites {guest} to her party.}
        =2 {{host} invites {guest} and one other person to her party.}
        other {{host} invites {guest} as one of the # people invited to her party.}}}
    male {{num_guests, plural, offset:1
        =0 {{host} does not give a party.}
        =1 {{host} invites {guest} to his party.}
        =2 {{host} invites {guest} and one other person to his party.}
        other {{host} invites {guest} as one of the # people invited to his party.}}}
    other {{num_guests, plural, offset:1
        =0 {{host} does not give a party.}
        =1 {{host} invites {guest} to their party.}
        =2 {{host} invites {guest} and one other person to their party.}
        other {{host} invites {guest} as one of the # people invited to their party.}}}}`.trim(),
                    },
                },
            };

            expect(
                trans(INTL_MESSAGE_WITH_PARAMETERS, {
                    gender_of_host: 'male',
                    num_guests: 123,
                    host: 'John',
                    guest: 'Mary',
                })
            ).toEqual('John invites Mary as one of the 122 people invited to his party.');

            expect(
                trans(
                    INTL_MESSAGE_WITH_PARAMETERS,
                    {
                        gender_of_host: 'female',
                        num_guests: 44,
                        host: 'Mary',
                        guest: 'John',
                    },
                    'messages'
                )
            ).toEqual('Mary invites John as one of the 43 people invited to her party.');

            expect(
                trans(
                    INTL_MESSAGE_WITH_PARAMETERS,
                    {
                        gender_of_host: 'female',
                        num_guests: 1,
                        host: 'Lola',
                        guest: 'Hugo',
                    },
                    'messages',
                    'en'
                )
            ).toEqual('Lola invites Hugo to her party.');

            expect(() => {
                // @ts-expect-error Parameters "gender_of_host", "num_guests", "host", and "guest" are missing
                trans(INTL_MESSAGE_WITH_PARAMETERS, {});
            }).toThrow(/^The intl string context variable "gender_of_host" was not provided/);

            expect(() => {
                // @ts-expect-error Parameters "num_guests", "host", and "guest" are missing
                trans(INTL_MESSAGE_WITH_PARAMETERS, {
                    gender_of_host: 'male',
                });
            }).toThrow(/^The intl string context variable "num_guests" was not provided/);

            expect(() => {
                // @ts-expect-error Parameters "host", and "guest" are missing
                trans(INTL_MESSAGE_WITH_PARAMETERS, {
                    gender_of_host: 'male',
                    num_guests: 123,
                });
            }).toThrow(/^The intl string context variable "host" was not provided/);

            expect(() => {
                // @ts-expect-error Parameter "guest" is missing
                trans(INTL_MESSAGE_WITH_PARAMETERS, {
                    gender_of_host: 'male',
                    num_guests: 123,
                    host: 'John',
                });
            }).toThrow(/^The intl string context variable "guest" was not provided/);

            expect(
                trans(
                    INTL_MESSAGE_WITH_PARAMETERS,
                    {
                        gender_of_host: 'male',
                        num_guests: 123,
                        host: 'John',
                        guest: 'Mary',
                    },
                    // @ts-expect-error Domain "foobar" is invalid
                    'foobar'
                )
            ).toEqual('message.intl.with.parameters');

            expect(
                trans(
                    INTL_MESSAGE_WITH_PARAMETERS,
                    {
                        gender_of_host: 'male',
                        num_guests: 123,
                        host: 'John',
                        guest: 'Mary',
                    },
                    'messages',
                    // @ts-expect-error Locale "fr" is invalid
                    'fr'
                )
            ).toEqual('message.intl.with.parameters');
        });

        test('same message id for multiple domains', () => {
            const MESSAGE_MULTI_DOMAINS: Message<
                { foobar: { parameters: NoParametersType }; messages: { parameters: NoParametersType } },
                'en'
            > = {
                id: 'message.multi_domains',
                translations: {
                    foobar: {
                        en: 'A message from foobar catalogue',
                    },
                    messages: {
                        en: 'A message from messages catalogue',
                    },
                },
            };

            expect(trans(MESSAGE_MULTI_DOMAINS)).toEqual('A message from messages catalogue');
            expect(trans(MESSAGE_MULTI_DOMAINS, {})).toEqual('A message from messages catalogue');
            expect(trans(MESSAGE_MULTI_DOMAINS, {}, 'messages')).toEqual('A message from messages catalogue');
            expect(trans(MESSAGE_MULTI_DOMAINS, {}, 'foobar')).toEqual('A message from foobar catalogue');

            expect(trans(MESSAGE_MULTI_DOMAINS, {}, 'messages', 'en')).toEqual('A message from messages catalogue');
            expect(trans(MESSAGE_MULTI_DOMAINS, {}, 'foobar', 'en')).toEqual('A message from foobar catalogue');

            // @ts-expect-error Domain "acme" is invalid
            expect(trans(MESSAGE_MULTI_DOMAINS, {}, 'acme', 'fr')).toEqual('message.multi_domains');

            // @ts-expect-error Locale "fr" is invalid
            expect(trans(MESSAGE_MULTI_DOMAINS, {}, 'messages', 'fr')).toEqual('message.multi_domains');

            // @ts-expect-error Locale "fr" is invalid
            expect(trans(MESSAGE_MULTI_DOMAINS, {}, 'foobar', 'fr')).toEqual('message.multi_domains');
        });

        test('same message id for multiple domains, and different parameters', () => {
            const MESSAGE_MULTI_DOMAINS_WITH_PARAMETERS: Message<
                {
                    foobar: { parameters: { '%parameter2%': string } };
                    messages: { parameters: { '%parameter1%': string } };
                },
                'en'
            > = {
                id: 'message.multi_domains.different_parameters',
                translations: {
                    foobar: {
                        en: 'A message from foobar catalogue with a parameter %parameter2%',
                    },
                    messages: {
                        en: 'A message from messages catalogue with a parameter %parameter1%',
                    },
                },
            };

            expect(trans(MESSAGE_MULTI_DOMAINS_WITH_PARAMETERS, { '%parameter1%': 'foo' })).toEqual(
                'A message from messages catalogue with a parameter foo'
            );
            expect(trans(MESSAGE_MULTI_DOMAINS_WITH_PARAMETERS, { '%parameter1%': 'foo' }, 'messages')).toEqual(
                'A message from messages catalogue with a parameter foo'
            );
            expect(trans(MESSAGE_MULTI_DOMAINS_WITH_PARAMETERS, { '%parameter1%': 'foo' }, 'messages', 'en')).toEqual(
                'A message from messages catalogue with a parameter foo'
            );
            expect(trans(MESSAGE_MULTI_DOMAINS_WITH_PARAMETERS, { '%parameter2%': 'foo' }, 'foobar')).toEqual(
                'A message from foobar catalogue with a parameter foo'
            );
            expect(trans(MESSAGE_MULTI_DOMAINS_WITH_PARAMETERS, { '%parameter2%': 'foo' }, 'foobar', 'en')).toEqual(
                'A message from foobar catalogue with a parameter foo'
            );

            // @ts-expect-error Parameter "%parameter1%" is missing
            expect(trans(MESSAGE_MULTI_DOMAINS_WITH_PARAMETERS, {})).toEqual(
                'A message from messages catalogue with a parameter %parameter1%'
            );

            // @ts-expect-error Domain "baz" is invalid
            expect(trans(MESSAGE_MULTI_DOMAINS_WITH_PARAMETERS, { '%parameter1%': 'foo' }, 'baz')).toEqual(
                'message.multi_domains.different_parameters'
            );

            // @ts-expect-error Locale "fr" is invalid
            expect(trans(MESSAGE_MULTI_DOMAINS_WITH_PARAMETERS, { '%parameter1%': 'foo' }, 'messages', 'fr')).toEqual(
                'message.multi_domains.different_parameters'
            );
        });

        test('missing message should return the message id when `throwWhenNotFound` is false', () => {
            throwWhenNotFound(false);
            setLocale('fr');

            const MESSAGE_IN_ANOTHER_DOMAIN: Message<{ security: { parameters: NoParametersType } }, 'en'> = {
                id: 'Invalid credentials.',
                translations: {
                    messages: {
                        en: 'Invalid credentials.',
                    },
                },
            };

            expect(trans(MESSAGE_IN_ANOTHER_DOMAIN)).toEqual('Invalid credentials.');
        });

        test('missing message should throw an error if `throwWhenNotFound` is true', () => {
            throwWhenNotFound(true);
            setLocale('fr');

            const MESSAGE_IN_ANOTHER_DOMAIN: Message<{ security: { parameters: NoParametersType } }, 'en'> = {
                id: 'Invalid credentials.',
                translations: {
                    messages: {
                        en: 'Invalid credentials.',
                    },
                },
            };

            expect(() => {
                trans(MESSAGE_IN_ANOTHER_DOMAIN);
            }).toThrow(`No translation message found with id "Invalid credentials.".`);
        });

        test('message from intl domain should be prioritized over its non-intl equivalent', () => {
            const MESSAGE: Message<
                { 'messages+intl-icu': { parameters: NoParametersType }; messages: { parameters: NoParametersType } },
                'en'
            > = {
                id: 'message',
                translations: {
                    'messages+intl-icu': {
                        en: 'A intl message',
                    },
                    messages: {
                        en: 'A basic message',
                    },
                },
            };

            expect(trans(MESSAGE)).toEqual('A intl message');
            expect(trans(MESSAGE, {})).toEqual('A intl message');
            expect(trans(MESSAGE, {}, 'messages')).toEqual('A intl message');
            expect(trans(MESSAGE, {}, 'messages', 'en')).toEqual('A intl message');
        });

        test('fallback behavior', () => {
            setLocaleFallbacks({ fr_FR: 'fr', fr: 'en', en_US: 'en', en_GB: 'en', de_DE: 'de', de: 'en' });

            const MESSAGE: Message<{ messages: { parameters: NoParametersType } }, 'en' | 'en_US' | 'fr'> = {
                id: 'message',
                translations: {
                    messages: {
                        en: 'A message in english',
                        en_US: 'A message in english (US)',
                        fr: 'Un message en français',
                    },
                },
            };

            const MESSAGE_INTL: Message<{ messages: { parameters: NoParametersType } }, 'en' | 'en_US' | 'fr'> = {
                id: 'message_intl',
                translations: {
                    messages: {
                        en: 'A intl message in english',
                        en_US: 'A intl message in english (US)',
                        fr: 'Un message intl en français',
                    },
                },
            };

            const MESSAGE_FRENCH_ONLY: Message<{ messages: { parameters: NoParametersType } }, 'fr'> = {
                id: 'message_french_only',
                translations: {
                    messages: {
                        fr: 'Un message en français uniquement',
                    },
                },
            };

            expect(trans(MESSAGE, {}, 'messages', 'en')).toEqual('A message in english');
            expect(trans(MESSAGE_INTL, {}, 'messages', 'en')).toEqual('A intl message in english');
            expect(trans(MESSAGE, {}, 'messages', 'en_US')).toEqual('A message in english (US)');
            expect(trans(MESSAGE_INTL, {}, 'messages', 'en_US')).toEqual('A intl message in english (US)');
            expect(trans(MESSAGE, {}, 'messages', 'en_GB' as 'en')).toEqual('A message in english');
            expect(trans(MESSAGE_INTL, {}, 'messages', 'en_GB' as 'en')).toEqual('A intl message in english');

            expect(trans(MESSAGE, {}, 'messages', 'fr')).toEqual('Un message en français');
            expect(trans(MESSAGE_INTL, {}, 'messages', 'fr')).toEqual('Un message intl en français');
            expect(trans(MESSAGE, {}, 'messages', 'fr_FR' as 'fr')).toEqual('Un message en français');
            expect(trans(MESSAGE_INTL, {}, 'messages', 'fr_FR' as 'fr')).toEqual('Un message intl en français');

            expect(trans(MESSAGE, {}, 'messages', 'de_DE' as 'en')).toEqual('A message in english');
            expect(trans(MESSAGE_INTL, {}, 'messages', 'de_DE' as 'en')).toEqual('A intl message in english');

            expect(trans(MESSAGE_FRENCH_ONLY, {}, 'messages', 'fr')).toEqual('Un message en français uniquement');
            expect(trans(MESSAGE_FRENCH_ONLY, {}, 'messages', 'en' as 'fr')).toEqual('message_french_only');
        });
    });
});
