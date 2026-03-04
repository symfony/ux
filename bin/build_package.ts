/**
 * This file is used to compile the assets from an UX package.
 */

import * as fs from 'node:fs';
import * as path from 'node:path';
import { parseArgs } from 'node:util';
import { globSync } from 'tinyglobby';
import { build } from 'tsdown';
import { readPackageJSON } from 'pkg-types';
import tsConfigPackage from '../tsconfig.package.json' with { type: 'json' };

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
    const isReactOrVueOrSvelte = ['@symfony/ux-react', '@symfony/ux-vue', '@symfony/ux-svelte'].some((name) =>
        packageData.name.startsWith(name)
    );

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

    const outDir = path.join(packageRoot, 'dist');

    await build({
        entry: inputFiles,
        outDir,
        clean: true,
        external: Array.from(external),
        format: 'esm',
        platform: 'browser',
        target: tsConfigPackage.compilerOptions.target,
        tsconfig: path.join(packageRoot, 'tsconfig.json'),
        dts: {
            entry: inputFiles.filter((inputFile) => !inputFile.endsWith('.css')),
        },
        watch: isWatch,
        // Prevent esbuild to inline relative and "external" imports (like "./components.js" for React, Vue, Svelte).
        unbundle: isStimulusBundle || isReactOrVueOrSvelte,
        inlineOnly: ['idiomorph'],
        plugins: [
            {
                name: 'symfony-ux:clean-output',
                renderChunk(code, chunk) {
                    // Remove #region/#endregion comments from all files
                    let result = code.replace(/^\s*\/\/#(?:end)?region[^\n]*\n?/gm, '');

                    // Remove JSDoc comments only from .js files (preserve them in .d.ts)
                    if (chunk.fileName.endsWith('.js')) {
                        result = result.replace(/^\s*\/\*\*[\s\S]*?\*\/\s*\n/gm, (match) =>
                            match.includes('@deprecated') ? match : ''
                        );
                    }

                    return result;
                },
            },
            {
                /**
                 * Rolldown plugin to minify CSS files using LightningCSS.
                 *
                 * tsdown's `minify: true` only minifies JS, not CSS assets.
                 * We use LightningCSS to minify CSS files.
                 */
                name: 'symfony-ux:css-minify',
                async generateBundle(_options, bundle) {
                    const { transform } = await import('lightningcss');

                    for (const [fileName, asset] of Object.entries(bundle)) {
                        if (asset.type !== 'asset' || !fileName.endsWith('.css')) {
                            continue;
                        }

                        const result = transform({
                            filename: fileName,
                            code: Buffer.from(asset.source as string),
                            minify: true,
                        });

                        asset.source = result.code.toString();
                        console.log(`[Symfony UX] Minified CSS: ${fileName}`);
                    }
                },
            },
        ],
        hooks: {
            /**
             * After the build is complete, rename CSS files to .min.css and remove empty JS wrappers.
             */
            'build:done': async () => {
                const files = await fs.promises.readdir(outDir);

                for (const file of files) {
                    const filePath = path.join(outDir, file);

                    // Rename .css to .min.css
                    if (file.endsWith('.css') && !file.endsWith('.min.css')) {
                        const newPath = filePath.replace(/\.css$/, '.min.css');
                        await fs.promises.rename(filePath, newPath);
                        console.log(`[Symfony UX] Renamed: ${file} → ${path.basename(newPath)}`);

                        try {
                            const jsWrapperPath = filePath.replace(/\.css$/, '.js');
                            await fs.promises.access(jsWrapperPath);
                            await fs.promises.unlink(jsWrapperPath);
                            console.log(`[Symfony UX] Removed JS wrapper: ${path.basename(jsWrapperPath)}`);
                        } catch (err) {
                            // No JS wrapper found, nothing to remove
                        }
                    }
                }
            },
        },
    });
}

main();
