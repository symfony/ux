/**
 * Translation parameters come from a fixed set of names defined in the source
 * code, so the same patterns are recompiled over and over without this cache.
 */
const compiledPatterns = new Map<string, RegExp>();

const SPECIAL_CHARS_REGEX = /([-[\]{}()*+?.\\^$|#,])/g;

/**
 * PHP strtr's equivalent, inspired and adapted from https://stackoverflow.com/a/37949642.
 *
 * @private
 *
 * @param string The string to replace in
 * @param replacePairs The pairs of characters to replace
 */
export function strtr(string: string, replacePairs: Record<string, string | number | Date>): string {
    const keys = Object.keys(replacePairs);

    if (keys.length === 0) {
        return string;
    }

    const pattern = keys.map((from) => from.replace(SPECIAL_CHARS_REGEX, '\\$1')).join('|');

    let regex = compiledPatterns.get(pattern);
    if (regex === undefined) {
        regex = new RegExp(pattern, 'g');
        compiledPatterns.set(pattern, regex);
    }

    return string.replace(regex, (matched) => replacePairs[matched].toString());
}
