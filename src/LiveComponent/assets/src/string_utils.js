/**
 * Splits each string in an array containing a space into an extra array item:
 *
 * Input:
 *      [
 *          'foo',
 *          'bar baz',
 *      ]
 *
 * Output:
 *      ['foo', 'bar', 'baz']
 *
 * @param {string[]} parts
 * @return {string[]}
 */
export function combineSpacedArray(parts) {
    const finalParts = [];
    parts.forEach((part) => {
        finalParts.push(...part.split(' '))
    });

    return finalParts;
}
