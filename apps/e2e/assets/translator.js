import { createTranslator } from '@symfony/ux-translator';
import { messages, localeFallbacks, defaultLocale } from '../var/translations/index.js';

/*
 * This file is part of the Symfony UX Translator package.
 *
 * If folder "../var/translations" does not exist, or some translations are missing,
 * you must warmup your Symfony cache to refresh JavaScript translations.
 *
 * If you use TypeScript, you can rename this file to "translator.ts" to take advantage of types checking.
 */

export const translator = createTranslator({
    messages,
    localeFallbacks,
    defaultLocale,
});

export const { trans, setLocale } = translator;
