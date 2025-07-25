/**
 * This file is used to compile the assets from an UX package.
 */

import * as fs from 'node:fs';
import * as path from 'node:path';
import { parseArgs } from 'node:util';
import { globSync } from 'tinyglobby';
import { build } from 'tsup';
import { readPackageJSON } from "pkg-types";

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

    const packageData = await readPackageJSON(path.join(packageRoot, 'package.json'));
    const isStimulusBundle = '@symfony/stimulus-bundle' === packageData.name;
    const isReactOrVueOrSvelte = ['@symfony/ux-react', '@symfony/ux-vue', '@symfony/ux-svelte'].some(name => packageData.name.startsWith(name));

    const inputCssFile = packageData?.config?.css_source;
    const inputFiles = [
        ...globSync('src/*controller.ts'),
        ...(isStimulusBundle ? ['src/loader.ts', 'src/controllers.ts'] : []),
        ...(isReactOrVueOrSvelte ? ['src/loader.ts', 'src/components.ts'] : []),
        ...(inputCssFile ? [inputCssFile] : []),
    ];

    const external = new Set([
        // We force "dependencies" and "peerDependencies" to be external to avoid bundling them.
        ...Object.keys(packageData.dependencies || {}),
        ...Object.keys(packageData.peerDependencies || {}),
    ]);

    inputFiles.forEach((file) => {
        // custom handling for StimulusBundle
        if (file.includes('StimulusBundle/assets/src/loader.ts')) {
            external.add('./controllers.js');
        }

        // React, Vue, Svelte
        if (file.includes('assets/src/loader.ts')) {
            external.add('./components.js');
        }
    });

    await build({
        entry: inputFiles,
        outDir: path.join(packageRoot, 'dist'),
        clean: true,
        external: Array.from(external),
        format: 'esm',
        platform: 'browser',
        tsconfig: path.join(import.meta.dirname, '../tsconfig.packages.json'),
        dts: {
            entry: inputFiles.filter(inputFile => !inputFile.endsWith('.css')),
        },
        watch: isWatch,
        splitting: false,
        esbuildOptions(options) {
            // Disabling `bundle` option prevent esbuild to inline relative (but external) imports (like "./components.js" for React, Vue, Svelte).
            options.bundle = !(isStimulusBundle || isReactOrVueOrSvelte);
        },
        plugins: [
            {
                /**
                 * This plugin is used to minify CSS files using LightningCSS.
                 *
                 * Even if tsup supports CSS minification through ESBuild by setting the `minify: true` option,
                 * it also minifies JS files but we don't want that.
                 */
                name: 'symfony-ux:minify-css',
                async renderChunk(code, chunkInfo) {
                    if (!/\.css$/.test(chunkInfo.path)) {
                        return null;
                    }

                    const { transform } = await import('lightningcss');
                    const result = transform({
                        filename: chunkInfo.path,
                        code: Buffer.from(code),
                        minify: true,
                    });

                    console.log(`[Symfony UX] Minified CSS file: ${chunkInfo.path}`);

                    return {
                        code: result.code.toString(),
                        map: result.map ? result.map.toString() : null,
                    }
                },
            },

            /**
             * Unlike tsdown/rolldown and the option "cssEntryFileNames", tsup does not support
             * customizing the output file names for CSS files.
             * A plugin is needed to rename the written CSS files to add the ".min" suffix.
             */
            {
                name: 'symfony-ux:append-min-to-css',
                async buildEnd({ writtenFiles }) {
                    for (const writtenFile of writtenFiles) {
                        if (!writtenFile.name.endsWith('.css')) {
                            continue;
                        }

                        const newName = writtenFile.name.replace(/\.css$/, '.min.css');
                        await fs.promises.rename(writtenFile.name, newName);

                        console.info(`[Symfony UX] Renamed ${writtenFile.name} to ${newName}`);
                    }
                }
            }
        ],
    });
}

main();
