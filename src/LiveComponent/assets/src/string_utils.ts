/**
 * Splits each string in an array containing a space into an extra array item:
 *
 * Input:
 *      [
 *          'foo',
 *          'bar baz',
 *          ' foobar  foobaz ',
 *      ]
 *
 * Output:
 *      ['foo', 'bar', 'baz', 'foobar', 'foobaz']
 *
 */
export function combineSpacedArray(parts: Array<string>) {
    const finalParts: Array<string> = [];
    parts.forEach((part) => {
        finalParts.push(...trimAll(part).split(' '));
    });

    return finalParts;
}

export function trimAll(str: string) {
    return str.replace(/[\s]+/g, ' ').trim();
}

/**
 * Normalizes model names with [] into the "." syntax.
 *
 * For example: "user[firstName]" becomes "user.firstName"
 */
export function normalizeModelName(model: string): string {
    return (
        model
            // Names ending in "[]" represent arrays in HTML.
            // To get normalized name we need to ignore this part.
            // For example: "user[mailing][]" becomes "user.mailing" (and has array typed value)
            .replace(/\[]$/, '')
            .split('[')
            // ['object', 'foo', 'bar', 'ya']
            .map(function (s) {
                return s.replace(']', '');
            })
            .join('.')
    );
}
