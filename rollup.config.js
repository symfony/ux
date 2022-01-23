import resolve from '@rollup/plugin-node-resolve';
import typescript from '@rollup/plugin-typescript';
import glob from 'glob';
import path from 'path';
import pkgUp from 'pkg-up';

/**
 * Guarantees that any files imported from a peer dependency are treated as an external.
 *
 * For example, if we import `chart.js/auto`, that would not normally
 * match the "chart.js" we pass to the "externals" config. This plugin
 * catches that case and adds it as an external.
 *
 * Inspired by https://github.com/oat-sa/rollup-plugin-wildcard-external
 */
const wildcardExternalsPlugin = (peerDependencies) => ({
    name: 'wildcard-externals',
    resolveId(source, importer) {
        if (importer) {
            let matchesExternal = false;
            peerDependencies.forEach((peerDependency) => {
                if (source.includes(`/${peerDependency}/`)) {
                    matchesExternal = true;
                }
            });

            if (matchesExternal) {
                return {
                    id: source,
                    external: true,
                    moduleSideEffects: true
                };
            }
        }

        return null; // other ids should be handled as usually
    }
});

const files = glob.sync("src/**/assets/src/*controller.ts");
const packages = files.map((file) => {
    const absolutePath = path.join(__dirname, file);
    const packageData = require(pkgUp.sync({ cwd: absolutePath }));
    const peerDependencies = [
        '@hotwired/stimulus',
        ...(packageData.peerDependencies ? Object.keys(packageData.peerDependencies) : [])
    ];

    return {
        input: file,
        output: {
            file: path.join(absolutePath, '..', '..', 'dist', path.basename(file, '.ts') + '.js'),
            format: 'esm',
        },
        external: peerDependencies,
        plugins: [
            resolve(),
            typescript(),
            wildcardExternalsPlugin(peerDependencies)
        ],
    };
});

export default packages;
