// @vitest-environment node
import { expect, test } from 'vitest';
import { createTranslator } from '../../src/translator_controller';

test('creates a translator without document', () => {
    expect(createTranslator({ messages: {} }).getLocale()).toEqual('en');
});

test('falls back to the default locale without document', () => {
    expect(createTranslator({ messages: {}, defaultLocale: 'fr' }).getLocale()).toEqual('fr');
});
