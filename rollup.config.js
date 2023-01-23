const resolve = require('@rollup/plugin-node-resolve');
const commonjs = require('@rollup/plugin-commonjs');
const typescript = require('@rollup/plugin-typescript');
const fs = require('fs');
const glob = require('glob');
const path = require('path');

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

/**
 * Moves the generated TypeScript declaration files to the correct location.
 *
 * This could probably be configured in the TypeScript plugin.
 */
const moveTypescriptDeclarationsPlugin = (packagePath) => ({
    name: 'move-ts-declarations',
    writeBundle: async () => {
        const files = glob.sync(path.join(packagePath, 'dist', '*', 'assets', 'src', '**/*.d.ts'));
        files.forEach((file) => {
            // a bit odd, but remove first 7 directories, which will leave
            // only the relative path to the file
            const relativePath = file.split('/').slice(7).join('/');

            const targetFile = path.join(packagePath, 'dist', relativePath);
            if (!fs.existsSync(path.dirname(targetFile))) {
                fs.mkdirSync(path.dirname(targetFile), { recursive: true });
            }
            fs.renameSync(file, targetFile);
        });
    }
});

const files = glob.sync('src/*/assets/src/*controller.ts');
module.exports = files.map((file) => {
    const packageRoot = path.join(file, '..', '..');
    const packagePath = path.join(packageRoot, 'package.json');
    const packageData = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
    const peerDependencies = [
        '@hotwired/stimulus',
        ...(packageData.peerDependencies ? Object.keys(packageData.peerDependencies) : [])
    ];

    return {
        input: file,
        output: {
            file: path.join(packageRoot, 'dist', path.basename(file, '.ts') + '.js'),
            format: 'esm',
        },
        external: peerDependencies,
        plugins: [
            resolve(),
            typescript({
                filterRoot: packageRoot,
                include: ['src/**/*.ts'],
                compilerOptions: {
                    outDir: 'dist',
                    declaration: true,
                    emitDeclarationOnly: true,
                }
            }),
            commonjs({
                namedExports: {
                    'react-dom/client': ['createRoot'],
                },
            }),
            wildcardExternalsPlugin(peerDependencies),
            moveTypescriptDeclarationsPlugin(packageRoot),
        ],
    };
});
