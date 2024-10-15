/**
 * Generate a markdown table with the difference in size of the dist files between the base and the PR.
 */

/*
Usage:
```shell
BASE_DIST_FILES='{"src/Autocomplete/assets/dist/controller.js":{"size":15382,"size_gz":3716},"src/Chartjs/assets/dist/controller.js":{"size":2281,"size_gz":771},"src/Cropperjs/assets/dist/controller.js":{"size":1044,"size_gz":475}}' \
PR_DIST_FILES='{"src/Chartjs/assets/dist/controller.js":{"size":1281,"size_gz":171},"src/Cropperjs/assets/dist/controller.js":{"size":1044,"size_gz":475},"src/Cropperjs/assets/dist/style.min.css":{"size":32,"size_gz":66},"src/Dropzone/assets/dist/controller.js":{"size":3199,"size_gz":816},"src/Map/src/Bridge/Google/assets/dist/foo.js":{"size":3199,"size_gz":816}}' \
GITHUB_REPOSITORY='symfony/ux' \
GITHUB_HEAD_REF='my-branch-name' \
  node .github/generate-dist-files-size-diff.mjs
```
 */

if (!process.env.BASE_DIST_FILES) {
    throw new Error('Missing or invalid "BASE_DIST_FILES" env variable.');
}

if (!process.env.PR_DIST_FILES) {
    throw new Error('Missing or invalid "PR_DIST_FILES" env variable.');
}

if (!process.env.GITHUB_REPOSITORY) {
    throw new Error('Missing or invalid "GITHUB_REPOSITORY" env variable.');
}

if (!process.env.GITHUB_HEAD_REF) {
    throw new Error('Missing or invalid "GITHUB_HEAD_REF" env variable.');
}

/**
 * Adapted from https://gist.github.com/zentala/1e6f72438796d74531803cc3833c039c?permalink_comment_id=4455218#gistcomment-4455218
 * @param {number} bytes
 * @param {number} digits
 * @returns {string}
 */
function formatBytes(bytes, digits = 2) {
    if (bytes === 0) {
        return '0 B';
    }
    const sizes = [`B`, 'kB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));

    return parseFloat((bytes / Math.pow(1024, i)).toFixed(digits)) + ' ' + sizes[i];
}

/**
 * @param {number} from
 * @param {number} to
 * @returns {number}
 */
function computeDiffPercent(from, to) {
    if (from === to) {
        return 0;
    }

    return Math.round((from - to) / from * -100);
}

/**
 * @param {number} percent
 * @returns {string}
 */
function formatDiffPercent(percent) {
    return percent > 0 ? `+${percent}% 📈` : percent < 0 ? `${percent}% 📉` : `${percent}%`;
}

export function main() {
    const repoUrl = `https://github.com/${process.env.GITHUB_REPOSITORY}`;
    /** @type {Record<string, {size: number, size_gz: number}>} */
    const base = JSON.parse(process.env.BASE_DIST_FILES);
    /** @type {Record<string, {size: number, size_gz: number}>} */
    const pr = JSON.parse(process.env.PR_DIST_FILES);
    let output = '<h1>📊 Dist packagesFiles size difference</h1>\n\n';

    /**
     * @type {Map<string, {
     *     meta: {
     *         packageName: string,
     *         bridgeName: string,
     *         url: string,
     *     },
     *     files: Set<{
     *         state: 'added' | 'removed' | 'changed',
     *         before: {size: number, sizeGz: number},
     *         after: {size: number, sizeGz: number},
     *         diffPercent: {size: number, sizeGz: number},
     *         meta: {fileNameShort: string, fileNameUrl: string}
     *     }>
     * }>}
     */
    const packagesFiles = [...new Set([...Object.keys(pr), ...Object.keys(base)])]
        .sort()
        .reduce((acc, file) => {
            const beforeSize = base[file]?.size || 0;
            const afterSize = pr[file]?.size || 0;
            const beforeSizeGz = base[file]?.size_gz || 0;
            const afterSizeGz = pr[file]?.size_gz || 0;

            if (beforeSize !== afterSize) {
                const isBridge = file.includes('src/Bridge'); // we assume that's enough for now
                const packageName = file.split('/')[1];
                const bridgeName = isBridge ? file.split('/')[4] : '';
                const key = isBridge ? `${packageName} (Bridge ${bridgeName})` : packageName;
                if (!acc.has(key)) {
                    acc.set(key, {
                        meta: {
                            packageName,
                            bridgeName,
                            url: isBridge ? `${repoUrl}/tree/${process.env.GITHUB_HEAD_REF}/src/${packageName}/src/Bridge/${bridgeName}/assets/dist` : `${repoUrl}/tree/${process.env.GITHUB_HEAD_REF}/src/${packageName}/assets/dist`,
                        }, files: new Set(),
                    });
                }

                const added = !base[file] && pr[file];
                const removed = base[file] && !pr[file];

                acc.get(key).files.add({
                    state: added ? 'added' : (removed ? 'removed' : 'changed'),
                    before: { size: beforeSize, sizeGz: beforeSizeGz },
                    after: { size: afterSize, sizeGz: afterSizeGz },
                    diffPercent: {
                        size: removed ? -100 : (added ? 100 : computeDiffPercent(beforeSize, afterSize)),
                        sizeGz: removed ? -100 : (added ? 100 : computeDiffPercent(beforeSizeGz, afterSizeGz)),
                    },
                    meta: {
                        fileNameShort: file.replace(isBridge ? `src/${file.split('/')[1]}/src/Bridge/${file.split('/')[4]}/assets/dist/` : `src/${file.split('/')[1]}/assets/dist/`, ''),
                        fileNameUrl: `${repoUrl}/blob/${process.env.GITHUB_HEAD_REF}/${file}`,
                    },
                });
            }

            return acc;
        }, new Map);

    if (packagesFiles.size === 0) {
        output += 'ℹ️ No difference in dist packagesFiles.\n';
        return output;
    }

    output += 'Thanks for the PR! Here is the difference in size of the dist packagesFiles between the base and the PR.\n';
    output += 'Please review the changes and make sure they are expected.\n\n';
    output += `<table>
    <thead><tr><th>File</th><th>Before (Size / Gzip)</th><th>After (Size / Gzip)</th></tr></thead>
    <tbody>`;
    for (const [pkgKey, pkg] of packagesFiles.entries()) {
        output += `<tr><td colspan="3"><a href="${pkg.meta.url}"><b>${pkgKey}</b></a></td></tr>`;
        for (const file of pkg.files) {
            output += `<tr>
                <td><a href="${file.meta.fileNameUrl}"><code>${file.meta.fileNameShort}</code></a></td>
            `;
            output += file.state === 'added' 
                ? `<td><em>Added</em></td>`
                : `<td>
                    <code>${formatBytes(file.before.size)}</code>
                    / <code>${formatBytes(file.before.sizeGz)}</code> 
                </td>`; 
            output += file.state === 'removed'
                ? `<td><em>Removed</em></td>`
                : `<td>
                    <code>${formatBytes(file.after.size)}</code>${file.state === 'changed' ? `<sup>${formatDiffPercent(file.diffPercent.size)}</sup>` : ''}
                    / <code>${formatBytes(file.after.sizeGz)}</code>${file.state === 'changed' ? `<sup>${formatDiffPercent(file.diffPercent.sizeGz)}</sup>` : ''}
                </td>`;
            output += `</tr>`;
        }
    }
    output += `</tbody>
</table>
`;

    return output;
}

if (!process.env.CI) {
    console.log(main());
}
