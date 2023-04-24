/**
 * PHP strtr's equivalent, inspired and adapted from https://stackoverflow.com/a/37949642.
 *
 * @private
 *
 * @param string The string to replace in
 * @param replacePairs The pairs of characters to replace
 */
export function strtr(string: string, replacePairs: Record<string, string | number>): string {
    const regex: Array<string> = Object.entries(replacePairs).map(([from]) => {
        return from.replace(/([-[\]{}()*+?.\\^$|#,])/g, '\\$1');
    });

    if (regex.length === 0) {
        return string;
    }

    return string.replace(new RegExp(regex.join('|'), 'g'), (matched) => replacePairs[matched].toString());
}
