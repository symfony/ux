import { beforeEach, describe, expect, test } from 'vitest';
import { createTranslator } from '../../src/translator_controller';
import type { Message, NoParametersType } from '../../src/types';

describe('Translator', () => {
    beforeEach(() => {
        document.documentElement.lang = '';
        document.documentElement.removeAttribute('data-symfony-ux-translator-locale');
    });

    describe('create translator with locale', () => {
        test('default locale', () => {
            let translator = createTranslator({ messages: {} });

            // 'en' is the default locale
            expect(translator.getLocale()).toEqual('en');

            // or the locale from <html lang="...">, if exists
            document.documentElement.lang = 'fr';
            translator = createTranslator({ messages: {} });
            expect(translator.getLocale()).toEqual('fr');

            // or the locale from <html data-symfony-ux-translator-locale="...">, if exists
            document.documentElement.setAttribute('data-symfony-ux-translator-locale', 'it');
            translator = createTranslator({ messages: {} });
            expect(translator.getLocale()).toEqual('it');
        });

        test('custom locale', () => {
            document.documentElement.lang = 'fr';
            document.documentElement.setAttribute('data-symfony-ux-translator-locale', 'it');

            const translator = createTranslator({ messages: {}, locale: 'de' });
            expect(translator.getLocale()).toEqual('de');
        });

        test('with subcode', () => {
            // allow format according to W3C
            document.documentElement.lang = 'de-AT';
            const translator = createTranslator({ messages: {} });
            expect(translator.getLocale()).toEqual('de_AT');

            // or "incorrect" Symfony locale format
            document.documentElement.lang = 'de_AT';
            expect(translator.getLocale()).toEqual('de_AT');
        });
    });

    describe('trans', () => {
        test('basic message', () => {
            const messages: {
                'message.basic': Message<{ messages: { parameters: NoParametersType } }, 'en'>;
            } = {
                'message.basic': {
                    translations: {
                        messages: {
                            en: 'A basic message',
                        },
                    },
                },
            };
            const translator = createTranslator({ messages });

            expect(translator.trans('message.basic')).toEqual('A basic message');
            expect(translator.trans('message.basic', {})).toEqual('A basic message');
            expect(translator.trans('message.basic', {}, 'messages')).toEqual('A basic message');
            expect(translator.trans('message.basic', {}, 'messages', 'en')).toEqual('A basic message');

            // @ts-expect-error "%count%" is not a valid parameter
            expect(translator.trans('message.basic', { '%count%': 1 })).toEqual('A basic message');

            // @ts-expect-error "foo" is not a valid domain
            expect(translator.trans('message.basic', {}, 'foo')).toEqual('message.basic');

            // @ts-expect-error "fr" is not a valid locale
            expect(translator.trans('message.basic', {}, 'messages', 'fr')).toEqual('message.basic');
        });

        test('basic message with parameters', () => {
            const messages: {
                'message.basic.with.parameters': Message<
                    { messages: { parameters: { '%parameter1%': string; '%parameter2%': string } } },
                    'en'
                >;
            } = {
                'message.basic.with.parameters': {
                    translations: {
                        messages: {
                            en: 'A basic message %parameter1% %parameter2%',
                        },
                    },
                },
            };
            const translator = createTranslator({ messages });

            expect(
                translator.trans('message.basic.with.parameters', {
                    '%parameter1%': 'foo',
                    '%parameter2%': 'bar',
                })
            ).toEqual('A basic message foo bar');

            expect(
                translator.trans(
                    'message.basic.with.parameters',
                    {
                        '%parameter1%': 'foo',
                        '%parameter2%': 'bar',
                    },
                    'messages'
                )
            ).toEqual('A basic message foo bar');

            expect(
                translator.trans(
                    'message.basic.with.parameters',
                    {
                        '%parameter1%': 'foo',
                        '%parameter2%': 'bar',
                    },
                    'messages',
                    'en'
                )
            ).toEqual('A basic message foo bar');

            // @ts-expect-error Parameters "%parameter1%" and "%parameter2%" are missing
            expect(translator.trans('message.basic.with.parameters', {})).toEqual(
                'A basic message %parameter1% %parameter2%'
            );

            // @ts-expect-error Parameter "%parameter2%" is missing
            expect(translator.trans('message.basic.with.parameters', { '%parameter1%': 'foo' })).toEqual(
                'A basic message foo %parameter2%'
            );

            expect(
                translator.trans(
                    'message.basic.with.parameters',
                    {
                        '%parameter1%': 'foo',
                        '%parameter2%': 'bar',
                    },
                    // @ts-expect-error "foobar" is not a valid domain
                    'foobar'
                )
            ).toEqual('message.basic.with.parameters');

            expect(
                translator.trans(
                    'message.basic.with.parameters',
                    {
                        '%parameter1%': 'foo',
                        '%parameter2%': 'bar',
                    },
                    'messages',
                    // @ts-expect-error "fr" is not a valid locale
                    'fr'
                )
            ).toEqual('message.basic.with.parameters');
        });

        test('intl message', () => {
            const messages: {
                'message.intl': Message<{ 'messages+intl-icu': { parameters: NoParametersType } }, 'en'>;
            } = {
                'message.intl': {
                    translations: {
                        'messages+intl-icu': {
                            en: 'An intl message',
                        },
                    },
                },
            };
            const translator = createTranslator({ messages });

            expect(translator.trans('message.intl')).toEqual('An intl message');
            expect(translator.trans('message.intl', {})).toEqual('An intl message');
            expect(translator.trans('message.intl', {}, 'messages')).toEqual('An intl message');
            expect(translator.trans('message.intl', {}, 'messages', 'en')).toEqual('An intl message');

            // @ts-expect-error "%count%" is not a valid parameter
            expect(translator.trans('message.intl', { '%count%': 1 })).toEqual('An intl message');

            // @ts-expect-error "foo" is not a valid domain
            expect(translator.trans('message.intl', {}, 'foo')).toEqual('message.intl');

            // @ts-expect-error "fr" is not a valid locale
            expect(translator.trans('message.intl', {}, 'messages', 'fr')).toEqual('message.intl');
        });

        test('intl message with parameters', () => {
            const messages: {
                'message.intl.with.parameters': Message<
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
                >;
            } = {
                'message.intl.with.parameters': {
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
                },
            };
            const translator = createTranslator({ messages });

            expect(
                translator.trans('message.intl.with.parameters', {
                    gender_of_host: 'male',
                    num_guests: 123,
                    host: 'John',
                    guest: 'Mary',
                })
            ).toEqual('John invites Mary as one of the 122 people invited to his party.');

            expect(
                translator.trans(
                    'message.intl.with.parameters',
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
                translator.trans(
                    'message.intl.with.parameters',
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
                translator.trans('message.intl.with.parameters', {});
            }).toThrow(/^The intl string context variable "gender_of_host" was not provided/);

            expect(() => {
                // @ts-expect-error Parameters "num_guests", "host", and "guest" are missing
                translator.trans('message.intl.with.parameters', {
                    gender_of_host: 'male',
                });
            }).toThrow(/^The intl string context variable "num_guests" was not provided/);

            expect(() => {
                // @ts-expect-error Parameters "host", and "guest" are missing
                translator.trans('message.intl.with.parameters', {
                    gender_of_host: 'male',
                    num_guests: 123,
                });
            }).toThrow(/^The intl string context variable "host" was not provided/);

            expect(() => {
                // @ts-expect-error Parameter "guest" is missing
                translator.trans('message.intl.with.parameters', {
                    gender_of_host: 'male',
                    num_guests: 123,
                    host: 'John',
                });
            }).toThrow(/^The intl string context variable "guest" was not provided/);

            expect(
                translator.trans(
                    'message.intl.with.parameters',
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
                translator.trans(
                    'message.intl.with.parameters',
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
            const messages: {
                'message.multi_domains': Message<
                    { foobar: { parameters: NoParametersType }; messages: { parameters: NoParametersType } },
                    'en'
                >;
            } = {
                'message.multi_domains': {
                    translations: {
                        foobar: {
                            en: 'A message from foobar catalogue',
                        },
                        messages: {
                            en: 'A message from messages catalogue',
                        },
                    },
                },
            };
            const translator = createTranslator({ messages });

            expect(translator.trans('message.multi_domains')).toEqual('A message from messages catalogue');
            expect(translator.trans('message.multi_domains', {})).toEqual('A message from messages catalogue');
            expect(translator.trans('message.multi_domains', {}, 'messages')).toEqual(
                'A message from messages catalogue'
            );
            expect(translator.trans('message.multi_domains', {}, 'foobar')).toEqual('A message from foobar catalogue');

            expect(translator.trans('message.multi_domains', {}, 'messages', 'en')).toEqual(
                'A message from messages catalogue'
            );
            expect(translator.trans('message.multi_domains', {}, 'foobar', 'en')).toEqual(
                'A message from foobar catalogue'
            );

            // @ts-expect-error Domain "acme" is invalid
            expect(translator.trans('message.multi_domains', {}, 'acme', 'fr')).toEqual('message.multi_domains');

            // @ts-expect-error Locale "fr" is invalid
            expect(translator.trans('message.multi_domains', {}, 'messages', 'fr')).toEqual('message.multi_domains');

            // @ts-expect-error Locale "fr" is invalid
            expect(translator.trans('message.multi_domains', {}, 'foobar', 'fr')).toEqual('message.multi_domains');
        });

        test('same message id for multiple domains, and different parameters', () => {
            const messages: {
                'message.multi_domains.different_parameters': Message<
                    {
                        foobar: { parameters: { '%parameter2%': string } };
                        messages: { parameters: { '%parameter1%': string } };
                    },
                    'en'
                >;
            } = {
                'message.multi_domains.different_parameters': {
                    translations: {
                        foobar: {
                            en: 'A message from foobar catalogue with a parameter %parameter2%',
                        },
                        messages: {
                            en: 'A message from messages catalogue with a parameter %parameter1%',
                        },
                    },
                },
            };
            const translator = createTranslator({ messages });

            expect(translator.trans('message.multi_domains.different_parameters', { '%parameter1%': 'foo' })).toEqual(
                'A message from messages catalogue with a parameter foo'
            );
            expect(
                translator.trans('message.multi_domains.different_parameters', { '%parameter1%': 'foo' }, 'messages')
            ).toEqual('A message from messages catalogue with a parameter foo');
            expect(
                translator.trans(
                    'message.multi_domains.different_parameters',
                    { '%parameter1%': 'foo' },
                    'messages',
                    'en'
                )
            ).toEqual('A message from messages catalogue with a parameter foo');
            expect(
                translator.trans('message.multi_domains.different_parameters', { '%parameter2%': 'foo' }, 'foobar')
            ).toEqual('A message from foobar catalogue with a parameter foo');
            expect(
                translator.trans(
                    'message.multi_domains.different_parameters',
                    { '%parameter2%': 'foo' },
                    'foobar',
                    'en'
                )
            ).toEqual('A message from foobar catalogue with a parameter foo');

            // @ts-expect-error Parameter "%parameter1%" is missing
            expect(translator.trans('message.multi_domains.different_parameters', {})).toEqual(
                'A message from messages catalogue with a parameter %parameter1%'
            );

            expect(
                // @ts-expect-error Domain "baz" is invalid
                translator.trans('message.multi_domains.different_parameters', { '%parameter1%': 'foo' }, 'baz')
            ).toEqual('message.multi_domains.different_parameters');

            expect(
                translator.trans(
                    'message.multi_domains.different_parameters',
                    { '%parameter1%': 'foo' },
                    'messages',
                    // @ts-expect-error Locale "fr" is invalid
                    'fr'
                )
            ).toEqual('message.multi_domains.different_parameters');
        });

        test('missing message should return the message id when `throwWhenNotFound` is false', () => {
            const messages: {
                'message.id': Message<{ security: { parameters: NoParametersType } }, 'en'>;
            } = {
                'message.id': {
                    translations: {
                        messages: {
                            en: 'Invalid credentials.',
                        },
                    },
                },
            };

            const translator = createTranslator({
                messages,
                locale: 'fr',
                throwWhenNotFound: false,
            });

            expect(translator.trans('message.id')).toEqual('message.id');
        });

        test('missing message should throw an error if `throwWhenNotFound` is true', () => {
            const messages: {
                'message.id': Message<{ security: { parameters: NoParametersType } }, 'en'>;
            } = {
                'message.id': {
                    translations: {
                        messages: {
                            en: 'Invalid credentials.',
                        },
                    },
                },
            };

            const translator = createTranslator({
                messages,
                locale: 'fr',
                throwWhenNotFound: true,
            });

            expect(() => {
                translator.trans('message.id');
            }).toThrow(`No translation message found with id "message.id".`);
        });

        test('message from intl domain should be prioritized over its non-intl equivalent', () => {
            const messages: {
                message: Message<
                    {
                        'messages+intl-icu': { parameters: NoParametersType };
                        messages: { parameters: NoParametersType };
                    },
                    'en'
                >;
            } = {
                message: {
                    translations: {
                        'messages+intl-icu': {
                            en: 'A intl message',
                        },
                        messages: {
                            en: 'A basic message',
                        },
                    },
                },
            };
            const translator = createTranslator({ messages });

            expect(translator.trans('message')).toEqual('A intl message');
            expect(translator.trans('message', {})).toEqual('A intl message');
            expect(translator.trans('message', {}, 'messages')).toEqual('A intl message');
            expect(translator.trans('message', {}, 'messages', 'en')).toEqual('A intl message');
        });

        test('fallback behavior', () => {
            const messages: {
                message: Message<{ messages: { parameters: NoParametersType } }, 'en' | 'en_US' | 'fr'>;
                message_intl: Message<{ messages: { parameters: NoParametersType } }, 'en' | 'en_US' | 'fr'>;
                message_french_only: Message<{ messages: { parameters: NoParametersType } }, 'fr'>;
            } = {
                message: {
                    translations: {
                        messages: {
                            en: 'A message in english',
                            en_US: 'A message in english (US)',
                            fr: 'Un message en français',
                        },
                    },
                },
                message_intl: {
                    translations: {
                        messages: {
                            en: 'A intl message in english',
                            en_US: 'A intl message in english (US)',
                            fr: 'Un message intl en français',
                        },
                    },
                },
                message_french_only: {
                    translations: {
                        messages: {
                            fr: 'Un message en français uniquement',
                        },
                    },
                },
            };
            const translator = createTranslator({
                messages,
                localeFallbacks: {
                    fr_FR: 'fr',
                    fr: 'en',
                    en_US: 'en',
                    en_GB: 'en',
                    de_DE: 'de',
                    de: 'en',
                },
            });

            expect(translator.trans('message', {}, 'messages', 'en')).toEqual('A message in english');
            expect(translator.trans('message_intl', {}, 'messages', 'en')).toEqual('A intl message in english');
            expect(translator.trans('message', {}, 'messages', 'en_US')).toEqual('A message in english (US)');
            expect(translator.trans('message_intl', {}, 'messages', 'en_US')).toEqual('A intl message in english (US)');
            expect(translator.trans('message', {}, 'messages', 'en_GB' as 'en')).toEqual('A message in english');
            expect(translator.trans('message_intl', {}, 'messages', 'en_GB' as 'en')).toEqual(
                'A intl message in english'
            );

            expect(translator.trans('message', {}, 'messages', 'fr')).toEqual('Un message en français');
            expect(translator.trans('message_intl', {}, 'messages', 'fr')).toEqual('Un message intl en français');
            expect(translator.trans('message', {}, 'messages', 'fr_FR' as 'fr')).toEqual('Un message en français');
            expect(translator.trans('message_intl', {}, 'messages', 'fr_FR' as 'fr')).toEqual(
                'Un message intl en français'
            );

            expect(translator.trans('message', {}, 'messages', 'de_DE' as 'en')).toEqual('A message in english');
            expect(translator.trans('message_intl', {}, 'messages', 'de_DE' as 'en')).toEqual(
                'A intl message in english'
            );

            expect(translator.trans('message_french_only', {}, 'messages', 'fr')).toEqual(
                'Un message en français uniquement'
            );
            expect(translator.trans('message_french_only', {}, 'messages', 'en' as 'fr')).toEqual(
                'message_french_only'
            );
        });
    });

    describe('destructuring', () => {
        test('createTranslator returns expected methods', () => {
            const messages: {
                'message.basic': Message<{ messages: { parameters: NoParametersType } }, 'en' | 'fr'>;
            } = {
                'message.basic': {
                    translations: {
                        messages: {
                            en: 'A basic message',
                            fr: 'Un message basique',
                        },
                    },
                },
            };
            const translator = createTranslator({ messages });

            const { setLocale, getLocale, setThrowWhenNotFound, trans } = translator;

            expect(typeof setLocale).toBe('function');
            expect(typeof getLocale).toBe('function');
            expect(typeof setThrowWhenNotFound).toBe('function');
            expect(typeof trans).toBe('function');

            expect(getLocale()).toEqual('en');
            expect(trans('message.basic')).toEqual('A basic message');
            setLocale('fr');
            expect(getLocale()).toEqual('fr');
            expect(trans('message.basic')).toEqual('Un message basique');
        });
    });
});
