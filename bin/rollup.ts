import * as fs from 'node:fs';
import * as path from 'node:path';
import commonjs from '@rollup/plugin-commonjs';
import resolve from '@rollup/plugin-node-resolve';
import typescript from '@rollup/plugin-typescript';
import type { Plugin, RollupOptions } from 'rollup';
import { globSync } from 'tinyglobby';

/**
 * Guarantees that any files imported from a peer dependency are treated as an external.
 *
 * For example, if we import `chart.js/auto`, that would not normally
 * match the "chart.js" we pass to the "externals" config. This plugin
 * catches that case and adds it as an external.
 *
 * Inspired by https://github.com/oat-sa/rollup-plugin-wildcard-external
 */
function wildcardExternalsPlugin(peerDependencies: Array<string>): Plugin {
    return {
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
                        moduleSideEffects: true,
                    };
                }
            }

            return null; // other ids should be handled as usually
        },
    };
}

/**
 * Moves the generated TypeScript declaration files to the correct location.
 *
 * This could probably be configured in the TypeScript plugin.
 */
function moveTypescriptDeclarationsPlugin(packageRoot: string): Plugin {
    return {
        name: 'move-ts-declarations',
        writeBundle: async () => {
            const isBridge = packageRoot.includes('src/Bridge');
            const globPattern = path.join('dist', '**', 'assets', 'src', '**/*.d.ts');
            const files = globSync(globPattern);

            files.forEach((file) => {
                const relativePath = file;
                // a bit odd, but remove first 7 or 4 directories, which will leave only the relative path to the file
                // ex: dist/Chartjs/assets/src/controller.d.ts' => 'dist/controller.d.ts'
                const targetFile = relativePath.replace(
                    `${relativePath
                        .split('/')
                        .slice(1, isBridge ? 7 : 4)
                        .join('/')}/`,
                    ''
                );
                if (!fs.existsSync(path.dirname(targetFile))) {
                    fs.mkdirSync(path.dirname(targetFile), { recursive: true });
                }
                fs.renameSync(file, targetFile);
            });
        },
    };
}

export function getRollupConfiguration({
    packageRoot,
    inputFiles,
    isWatch,
    additionalPlugins,
}: {
    packageRoot: string;
    inputFiles: string[];
    isWatch: boolean;
    additionalPlugins: Array<Plugin>;
}): RollupOptions {
    const packagePath = path.join(packageRoot, 'package.json');
    const packageData = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
    const peerDependencies = [
        '@hotwired/stimulus',
        ...(packageData.peerDependencies ? Object.keys(packageData.peerDependencies) : []),
    ];

    inputFiles.forEach((file) => {
        // custom handling for StimulusBundle
        if (file.includes('StimulusBundle/assets/src/loader.ts')) {
            peerDependencies.push('./controllers.js');
        }

        // React, Vue
        if (file.includes('assets/src/loader.ts')) {
            peerDependencies.push('./components.js');
        }
    });

    const outDir = path.join(packageRoot, 'dist');

    return {
        input: inputFiles,
        output: {
            dir: outDir,
            entryFileNames: '[name].js',
            format: 'esm',
        },
        external: peerDependencies,
        plugins: [
            resolve(),
            typescript({
                filterRoot: '.',
                tsconfig: path.join(import.meta.dirname, '..', 'tsconfig.packages.json'),
                noEmitOnError: !isWatch,
                include: [
                    'src/**/*.ts',
                    // TODO: Remove for the next major release
                    // "@rollup/plugin-typescript" v11.0.0 fixed an issue (https://github.com/rollup/plugins/pull/1310) that
                    // cause a breaking change for UX React users, the dist file requires "react-dom/client" instead of "react-dom"
                    // and it will break for users using the Symfony AssetMapper without Symfony Flex (for automatic "importmap.php" upgrade).
                    '**/node_modules/react-dom/client.js',
                ],
                compilerOptions: {
                    outDir: outDir,
                    declaration: true,
                    emitDeclarationOnly: true,
                },
            }),
            commonjs(),
            wildcardExternalsPlugin(peerDependencies),
            moveTypescriptDeclarationsPlugin(packageRoot),
            ...additionalPlugins,
        ],
    };
}
