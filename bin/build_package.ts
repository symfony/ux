/**
 * This file is used to compile the assets from an UX package.
 */

import * as fs from 'node:fs';
import * as path from 'node:path';
import { parseArgs } from 'node:util';
import * as LightningCSS from 'lightningcss';
import { globSync } from 'tinyglobby';
import { build } from 'tsdown';

const args = parseArgs({
    allowPositionals: true,
    options: {
        watch: {
            type: 'boolean',
            description: 'Watch the source files for changes and rebuild when necessary.',
        },
    },
});

async function main() {
    const packageRoot = path.resolve(process.cwd(), args.positionals[0]);
    const isWatch = args.values.watch || false;

    if (!fs.existsSync(packageRoot)) {
        console.error(`The package directory "${packageRoot}" does not exist.`);
        process.exit(1);
    }

    if (!fs.existsSync(path.join(packageRoot, 'package.json'))) {
        console.error(`The package directory "${packageRoot}" does not contain a package.json file.`);
        process.exit(1);
    }

    const packageData = await import(path.join(packageRoot, 'package.json'), { with: { type: 'json' } });
    const packageName = packageData.name;
    const srcDir = path.join(packageRoot, 'src');
    const distDir = path.join(packageRoot, 'dist');

    if (!fs.existsSync(srcDir)) {
        console.error(`The package directory "${packageRoot}" does not contain a "src" directory.`);
        process.exit(1);
    }

    const inputFiles = [
        ...globSync(path.join(srcDir, '*controller.ts')),
        ...(['@symfony/ux-react', '@symfony/ux-vue', '@symfony/ux-svelte'].includes(packageName)
            ? [path.join(srcDir, 'loader.ts'), path.join(srcDir, 'components.ts')]
            : []),
        ...(packageName === '@symfony/stimulus-bundle'
            ? [path.join(srcDir, 'loader.ts'), path.join(srcDir, 'controllers.ts')]
            : []),
        ...(packageData?.config?.css_source ? [packageData.config.css_source] : []),
    ];

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

    build({
        entry: inputFiles,
        outDir: distDir,
        clean: true,
        outputOptions: {
            cssEntryFileNames: '[name].min.css',
        },
        external: peerDependencies,
        format: 'esm',
        platform: 'browser',
        tsconfig: path.join(import.meta.dirname, '../tsconfig.packages.json'),
        // The target should be kept in sync with `tsconfig.packages.json` file.
        // In the future, I hope the target will be read from the `tsconfig.packages.json` file, but for now we need to specify it manually.
        target: 'es2021',
        watch: isWatch,
        plugins: [

            /**
             * Guarantees that any files imported from a peer dependency are treated as an external.
             *
             * For example, if we import `chart.js/auto`, that would not normally
             * match the "chart.js" we pass to the "externals" config. This plugin
             * catches that case and adds it as an external.
             *
             * Inspired by https://github.com/oat-sa/rollup-plugin-wildcard-external
             */
            {
                name: 'wildcard-externals',
                resolveId(source: string, importer: string) {
                    if (!importer) {
                        return null; // other ids should be handled as usually
                    }

                    const matchesExternal = peerDependencies.some((peerDependency) => {
                        return source.includes(`/${peerDependency}/`)
                    });

                    if (matchesExternal) {
                        return {
                            id: source,
                            external: true,
                            moduleSideEffects: true,
                        };
                    }

                    return null; // other ids should be handled as usually
                },
            },
            // Since minifying files is not configurable per file, we need to use a custom plugin to handle CSS minification.
            {
                name: 'minimize-css',
                transform: {
                    filter: {
                        id: /\.css$/,
                    },
                    handler (code, id) {
                        const { code: minifiedCode } = LightningCSS.transform({
                            filename: path.basename(id),
                            code: Buffer.from(code),
                            minify: true,
                            sourceMap: false,
                        });

                        return { code: minifiedCode.toString(), map: null };
                    }
                },
            },
        ],
        hooks: {
            async 'build:done'() {
                // TODO: Idk why, but when we build a CSS file (e.g. `style.css`), it also generate an empty JS file (e.g. `style.js`).
                if (packageData?.config?.css_source) {
                    const unwantedJsFile = path.join(distDir, path.basename(packageData.config.css_source, '.css') + '.js');
                    await fs.promises.rm(unwantedJsFile, { force: true });
                }
            }
        }
    }).catch((error) => {
        console.error('Error during build:', error);
        process.exit(1);
    });
}

main();
