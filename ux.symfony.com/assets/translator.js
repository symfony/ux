/*
 * This file is part of the Symfony UX Translator package.
 *
 * If folder "../var/translations" does not exist, or some translations are missing,
 * you must warmup your Symfony cache to refresh JavaScript translations.
 *
 * If you use TypeScript, you can rename this file to "translator.ts" to take advantage of types checking.
 */

import { trans, getLocale, setLocale, setLocaleFallbacks } from '@symfony/ux-translator';
import { localeFallbacks } from '../var/translations/configuration';

setLocaleFallbacks(localeFallbacks);

export { trans, setLocale };
export * from '../var/translations';
